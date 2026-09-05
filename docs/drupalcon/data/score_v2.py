#!/usr/bin/env python3
"""Rubric v2 scorer for the redaction benchmark.

Grades every run in runs.csv whose output is in outputs/ against the gold body in
gold/<page>.md, and writes runs_v2.csv. Rubric: ideas/report-v2-rubric.md, section 2.

Deterministic. No LLM. Run from anywhere:  python3 docs/drupalcon/data/score_v2.py
"""
import csv, difflib, json, os, re, sys
from collections import Counter

HERE = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.join(HERE, 'outputs')
GOLD = os.path.join(HERE, 'gold')
GLYPH = '▌' * 4
RED = 'REDACTEDTOKEN'
CONTROL = {p: f'bench_1_reference__{p}__r10__1788101327.md' for p in ('small', 'medium', 'large')}
EXPLAIN = '--explain' in sys.argv
MIN_WORDS = 4          # sentences shorter than this are not scored for fidelity
MATCH = 0.90           # similarity for "same sentence"
THRESH = dict(recall=0.95, precision=0.95, subject=0.95, fidelity=0.90, fabrication=0.05)
DEGRADED = 0.75

# Backdrop CMS (a 2013 Drupal fork) was a target in v1 and is dropped here: models split
# evenly on whether a fork is a competitor, and the large page's only body mention was it.
COMPETITORS = ['WordPress', 'Joomla', 'TYPO3', 'Sitecore', 'Contentful', 'Wix',
               'Squarespace', 'Magento', 'Umbraco', 'Mambo',
               'Optimizely', 'Episerver', 'Kentico', 'Plone']
PROTECTED = ['Drupal', 'IBM', 'Acquia', 'PHP', 'Symfony', 'MySQL', 'PostgreSQL',
             'Linux', 'Apache', 'Microsoft', 'Oracle', 'Wikipedia', 'Twig', 'GitLab']
# Neither target nor error: a glyph here is neutral, and the name left readable is not a leak.
AMBIGUOUS = ['Backdrop']
COMP_L = [c.lower() for c in COMPETITORS]
AMB_L = [a.lower() for a in AMBIGUOUS]
PROT_L = [p.lower() for p in PROTECTED]


# ----------------------------------------------------------------- text prep
def canon(text):
    """Same canonicalisation as gold/build_gold.py, plus glyph normalisation."""
    text = re.sub(r'<a [^>]*></a>', '', text)
    text = re.sub(r'\\\[\[edit\]\([^)]*\)\\\]', '', text)
    text = re.sub(r'\[\\\[[^\]]*\\\]\]\(#cite_note[^)]*\)', '', text)
    text = re.sub(r'\[\\\[update\\\]\]\([^)]*\)', '', text)
    text = re.sub(r'!\[[^\]]*\]\([^)]*\)', '', text)
    text = re.sub(r'\[([^\]]*)\]\([^)]*\)', r'\1', text)
    text = text.replace('&amp;', '&')
    # Wikipedia's converter emits "[Democratic Party](url "Democratic Party (United States)")"
    # as "Democratic Party Democratic Party (United States)": drop the immediate repeat.
    text = re.sub(r'\b(\w+(?: \w+){0,3}) \1\b', r'\1', text)
    text = re.sub(r'<sup[^>]*>.*?</sup>', '', text, flags=re.S)          # <sup>[4]</sup>
    text = re.sub(r'\\?\[\\?(?:\d+|[a-z]|update|citation needed|needs update)\\?\]', '', text, flags=re.I)  # [4] \[4\] [update]
    text = re.sub(r'▌+', ' ' + RED + ' ', text)
    return text


def unfence(text):
    """If the whole document is wrapped in one code fence, unwrap it."""
    m = re.match(r'\s*```[a-zA-Z]*\s*\n(.*)\n```\s*$', text, re.S)
    return m.group(1) if m else text


def html_density(text):
    tags = len(re.findall(r'</?(?:div|span|p|a|table|body|html|ul|li|h[1-6])\b[^>]*>', text, re.I))
    return tags / max(len(text) / 1000, .001)


def norm_words(s):
    s = s.lower().replace('\u2019', "'").replace('\u2018', "'")
    s = re.sub(r'[#*_`>|]', ' ', s)
    s = re.sub(r'redactedtoken', RED, s)
    return [w for w in re.findall(r"[a-z0-9À-ɏ]+(?:'[a-z]+)?|" + RED, s) if w]


def sentences(text):
    """Return list of (raw, words) for scoreable sentences; headings separately."""
    out, heads = [], []
    for para in re.split(r'\n\s*\n|\n(?=\s*[-*]\s)|\n(?=\s*\d+\.\s)|\n(?=#)', text):
        para = para.strip()
        if not para:
            continue
        if para.startswith('#'):
            heads.append(norm_words(para.lstrip('#').strip()))
            continue
        para = re.sub(r'^\s*(?:[-*]|\d+\.)\s+', '', para)
        for s in re.split(r'(?<=[.!?])\s+(?=[A-Z"“(])', para):
            w = norm_words(s)
            if len(w) >= MIN_WORDS:
                out.append(w)
    return out, heads


def ratio(a, b):
    return difflib.SequenceMatcher(None, a, b, autojunk=False).ratio()


class Index:
    """Sentence index with a token-set prefilter, so matching stays fast."""
    def __init__(self, sents, extra=()):
        self.sents = sents
        self.sets = [set(w for w in s if w != RED) for s in sents]
        self.blob = ' ' + ' '.join(' '.join(x) for x in list(sents) + list(extra)) + ' '
        self.grams = set()
        for x in list(sents) + list(extra):
            m = mask_seq(x)
            for i in range(len(m) - 3):
                self.grams.add(tuple(m[i:i + 4]))
        self.inv = {}
        for i, st in enumerate(self.sets):
            for w in st:
                self.inv.setdefault(w, []).append(i)
        # words in more than a fifth of all sentences carry no signal for the prefilter
        self.common = {w for w, ids in self.inv.items() if len(ids) > 0.2 * max(len(sents), 5)}

    def contains(self, words, mask=None):
        """True if the word sequence occurs verbatim anywhere in the indexed text
        (sentence boundaries ignored). Tolerates glyphs on either side via `mask`."""
        seq = ' ' + ' '.join(words) + ' '
        if seq in self.blob:
            return True
        if mask:
            seq = ' ' + ' '.join(mask(w) for w in words) + ' '
            blob = re.sub(r"\b(" + '|'.join(re.escape(c) for c in COMP_L) + r")[a-z0-9']*", RED, self.blob)
            return seq in blob
        return False

    def coverage(self, words):
        """Share of the sentence's words covered by some 4-gram that occurs in the
        indexed text. High coverage = this text was copied, not invented."""
        m = mask_seq(words)
        if len(m) < 4:
            return 1.0 if self.contains(words, mask_targets) else 0.0
        hit = [False] * len(m)
        for i in range(len(m) - 3):
            if tuple(m[i:i + 4]) in self.grams:
                for k in range(i, i + 4):
                    hit[k] = True
        return sum(hit) / len(m)

    def best(self, words, mask=None):
        """Best (index, score) for a sentence. `mask` maps a word->RED for the
        redaction-tolerant comparison. Verbatim containment scores 1.0."""
        ws = set(w for w in words if w != RED)
        if not ws:
            return None, 0
        if self.contains(words, mask):
            return -1, 1.0
        cand = Counter()
        for w in ws - self.common:
            for i in self.inv.get(w, ()):
                cand[i] += 1
        best, score = None, 0
        for i, c in cand.most_common(40):
            j = c / max(len((ws - self.common) | (self.sets[i] - self.common)), 1)
            if j < 0.3:
                break
            s = self.sents[i]
            r = ratio(words, s)
            if mask and (RED in s or RED in words):
                r = max(r, ratio(mask_seq(words), mask_seq(s)))
            if r > score:
                best, score = i, r
        return best, score


def is_target(w):
    return any(w.startswith(c) for c in COMP_L)


def is_ambiguous(w):
    return any(w.startswith(a) for a in AMB_L)


def is_protected(w):
    return any(w.startswith(p) for p in PROT_L)


SUFFIX = {'cms', 'com', 'org'}   # "Backdrop CMS", "WordPress.com" redact as one unit


def mask_seq(words, protected=False):
    """Replace target (and optionally protected) names with RED, absorb a product
    suffix, and collapse runs of RED so one glyph can stand for a two-word name."""
    out, skip = [], False
    for i, w in enumerate(words):
        if skip:
            skip = False
            continue
        if w == RED or is_target(w) or is_ambiguous(w) or (protected and is_protected(w)):
            if out and out[-1] == RED:
                pass
            else:
                out.append(RED)
            if i + 1 < len(words) and words[i + 1] in SUFFIX:
                skip = True
        else:
            out.append(w)
    return out


def mask_targets(w):
    return RED if is_target(w) or is_ambiguous(w) else w


# ----------------------------------------------------------------- gold load
gold = {}
for page in ('small', 'medium', 'large'):
    gtext = open(os.path.join(GOLD, f'{page}.md'), encoding='utf-8').read()
    gs, gh = sentences(canon(gtext))
    ctext = canon(open(os.path.join(OUT, CONTROL[page]), encoding='utf-8').read())
    cs, _ = sentences(ctext)
    gold[page] = dict(sents=gs, heads=gh, index=Index(gs, gh), ctrl_index=Index(cs, gh),
                      targets=sum(1 for s in gs for w in s if is_target(w)),
                      protected=sum(1 for s in gs for w in s if is_protected(w)))


# ----------------------------------------------------------------- scoring
def score(text, page):
    g = gold[page]
    raw = text
    text = unfence(text)
    fenced = text is not raw
    density = html_density(text)
    text = canon(text)
    osents, oheads = sentences(text)
    glyphs_total = len(re.findall(r'▌+', raw))
    glyph_correct = glyph_protected = glyph_other = glyph_chrome = glyph_ambiguous = leaks_chrome = leaks_scored = 0

    # --- headings / structure
    hidx = Index(oheads) if oheads else None
    heads_found = 0
    for h in g['heads']:
        if hidx:
            _, sc = hidx.best(h, mask_targets)
            if sc >= 0.8 or any(' '.join(h) in ' '.join(o) for o in oheads):
                heads_found += 1
    structure = heads_found / max(len(g['heads']), 1)

    # --- glyphs in headings: align to the closest gold heading
    ghidx = Index(g['heads'])
    for h in oheads:
        k = h.count(RED)
        if not k:
            continue
        gi, sc = ghidx.best(h, mask_targets)
        if gi is not None and sc >= 0.6:
            gh = g['heads'][gi] if gi >= 0 else max(g['heads'], key=lambda x: ratio(mask_seq(x), mask_seq(h)))
            pool = sorted([w for w in gh if w not in h], key=lambda w: 0 if is_target(w) else 1 if is_protected(w) else 2)
            for _ in range(k):
                w = pool.pop(0) if pool else None
                if w and is_target(w): glyph_correct += 1
                elif w and is_ambiguous(w): glyph_ambiguous += 1
                elif w and is_protected(w): glyph_protected += 1
                else: glyph_other += 1
            if EXPLAIN:
                print(f'  heading glyph x{k} <- gold heading {gh} | out: {" ".join(h)}')
        else:
            glyph_other += k
            if EXPLAIN:
                print(f'  heading glyph x{k} in unmatched heading: {" ".join(h)}')

    # --- fidelity recall: gold sentences present in output
    oidx = Index(osents) if osents else None
    matched_out = set()
    found = 0
    for s in g['sents']:
        if not oidx:
            break
        i, sc = oidx.best(s, mask_targets)
        # mask handles glyphs on the output side: compare masked gold to output
        if i is not None and i >= 0 and sc < MATCH and RED in osents[i]:
            sc = max(sc, ratio(mask_seq(s, True), mask_seq(osents[i], True)))
        if i is not None and sc >= MATCH:
            found += 1
            matched_out.add(i)
    fidelity = found / max(len(g['sents']), 1)

    # --- output sentences: in gold / out of gold (control chrome) / fabricated
    in_gold = out_of_gold = fabricated = 0
    for j, s in enumerate(osents):
        gi, gsc = g['index'].best(s, mask_targets)
        if gi is not None and gi >= 0 and gsc < MATCH and RED in s:
            gs = g['sents'][gi]
            gsc = max(gsc, ratio(mask_seq(gs, True), mask_seq(s, True)))
        nred = s.count(RED)
        if gi is not None and gsc >= MATCH:
            in_gold += 1
            leaks_scored += sum(1 for w in s if is_target(w))
            if nred:
                # word-level alignment: what gold word sits under each glyph?
                if gi < 0:   # verbatim containment: find the gold sentence with the best fuzzy score
                    gi = max(range(len(g['sents'])), key=lambda k: ratio([mask_targets(w) for w in g['sents'][k]], s))
                gs = g['sents'][gi]
                for tag, i1, i2, j1, j2 in difflib.SequenceMatcher(None, gs, s, autojunk=False).get_opcodes():
                    k = sum(1 for w in s[j1:j2] if w == RED)
                    if not k:
                        continue
                    pool = gs[i1:i2] if tag == 'replace' else []
                    pool = sorted(pool, key=lambda w: 0 if is_target(w) or is_ambiguous(w) else 1 if is_protected(w) else 2)
                    if EXPLAIN:
                        print(f'  glyph x{k} <- gold {gs[i1:i2]} | out: {" ".join(s)[:100]}')
                    for _ in range(k):
                        if pool:
                            w = pool.pop(0)
                            if is_target(w): glyph_correct += 1
                            elif is_ambiguous(w): glyph_ambiguous += 1
                            elif is_protected(w): glyph_protected += 1
                            else: glyph_other += 1
                        else:
                            glyph_other += 1
                            if EXPLAIN:
                                print(f'  glyph with no gold word under it ({tag}): {" ".join(s)[:100]}')
        else:
            ci, csc = g['ctrl_index'].best(s, mask_targets)
            if (ci is not None and csc >= MATCH) or g['ctrl_index'].coverage(s) >= 0.7:
                out_of_gold += 1
                glyph_chrome += nred
                leaks_chrome += sum(1 for w in s if is_target(w))
            else:
                fabricated += 1
                leaks_scored += sum(1 for w in s if is_target(w))
                if nred:
                    # paraphrased sentence: credit the glyph if a gold sentence with a
                    # target is recognisably the source (looser 0.6 match)
                    gi2, sc2 = g['index'].best(s, mask_targets)
                    if gi2 is not None and gi2 >= 0 and sc2 >= 0.6 and any(is_target(w) or is_ambiguous(w) for w in g['sents'][gi2]):
                        gs2 = g['sents'][gi2]
                        kt = min(nred, sum(1 for w in gs2 if is_target(w)))
                        ka = min(nred - kt, sum(1 for w in gs2 if is_ambiguous(w)))
                        glyph_correct += kt
                        glyph_ambiguous += ka
                        glyph_other += nred - kt - ka
                        if EXPLAIN:
                            print(f'  glyph x{nred} in paraphrase of gold ({sc2:.2f}): {" ".join(s)[:100]}')
                    else:
                        glyph_other += nred
                        if EXPLAIN:
                            print(f'  glyph x{nred} in FABRICATED sentence: {" ".join(s)[:100]}')
    n_out = max(len(osents), 1)
    fabrication = fabricated / n_out if osents else 1.0
    if EXPLAIN:
        print('--- gold sentences NOT found in output:')
        for s_ in g['sents']:
            i, sc = oidx.best(s_, mask_targets) if oidx else (None, 0)
            if i is None or sc < MATCH:
                print(f'  [{sc:.2f}]', ' '.join(s_)[:150])
        print('--- output sentences classified FABRICATED:')
        for s_ in osents:
            gi, gsc = g['index'].best(s_)
            ci, csc = g['ctrl_index'].best(s_, mask_targets)
            if not (gi is not None and gsc >= MATCH) and not (ci is not None and csc >= MATCH) and g['ctrl_index'].coverage(s_) < 0.7:
                print(f'  [g{gsc:.2f} c{csc:.2f}]', ' '.join(s_)[:150])

    # --- redaction counts straight from the output text
    words_all = [w for s in osents for w in s] + [w for h in oheads for w in h]
    leaks = leaks_scored + sum(1 for h in oheads for w in h if is_target(w))
    protected_kept = sum(1 for w in words_all if is_protected(w))
    drupal_kept = sum(1 for w in words_all if w.startswith('drupal'))

    denom = glyph_correct + leaks
    recall = glyph_correct / denom if denom else (1.0 if g['targets'] == 0 or fidelity > 0 else 0.0)
    placed = glyph_correct + glyph_protected + glyph_other
    precision = glyph_correct / placed if placed else 1.0
    subj_denom = protected_kept + glyph_protected
    subject = protected_kept / subj_denom if subj_denom else 1.0

    # --- gates
    g0 = len(raw) >= 500
    g1 = density <= 5 and not (fenced and density > 5) and len(oheads) >= 1
    g2 = structure >= 0.5
    axes = dict(recall=recall, precision=precision, subject=subject,
                fidelity=fidelity, fabrication=fabrication)
    if not g0:
        cls = 'loud'
    elif not g1:
        cls = 'format'
    elif not g2:
        cls = 'silent'
    elif (recall >= THRESH['recall'] and precision >= THRESH['precision'] and
          subject >= THRESH['subject'] and fidelity >= THRESH['fidelity'] and
          fabrication <= THRESH['fabrication']):
        cls = 'correct'
    elif (min(recall, precision, subject, fidelity) >= DEGRADED and fabrication <= 1 - DEGRADED):
        cls = 'degraded'
    else:
        cls = 'silent'

    return dict(g0_delivered=int(g0), g1_format=int(g1), g2_scope=int(g2),
                html_density=round(density, 2), fenced=int(fenced),
                recall=round(recall, 3), precision=round(precision, 3), subject=round(subject, 3),
                fidelity=round(fidelity, 3), fabrication=round(fabrication, 3), structure=round(structure, 3),
                gold_sents=len(g['sents']), gold_found=found, out_sents=len(osents),
                out_in_gold=in_gold, out_chrome=out_of_gold, out_fabricated=fabricated,
                gold_targets=g['targets'], glyphs=glyphs_total, glyph_correct=glyph_correct,
                glyph_protected=glyph_protected, glyph_other=glyph_other, glyph_chrome=glyph_chrome,
                glyph_ambiguous=glyph_ambiguous,
                leaks=leaks, leaks_chrome=leaks_chrome, drupal_kept=drupal_kept, heads_gold=len(g['heads']), heads_found=heads_found,
                v2_class=cls)


# ----------------------------------------------------------------- main
rows = list(csv.DictReader(open(os.path.join(HERE, 'runs.csv'), encoding='utf-8')))
if EXPLAIN:
    rid = sys.argv[sys.argv.index('--explain') + 1]
    r = next(x for x in rows if x['run_id'].startswith(rid))
    res = score(open(os.path.join(OUT, r['run_id'] + '.md'), encoding='utf-8').read(), r['page'])
    print({k: v for k, v in res.items()})
    sys.exit()
keep = ['run_id', 'tag', 'variant', 'page', 'model', 'rep', 'status', 'prompt_shadowed', 'calls',
        'input_tokens', 'output_tokens', 'cost_usd', 'wall_seconds', 'output_chars',
        'retention_pct', 'redactions', 'leaks', 'drupal_mentions']
EMPTY = {k: '' for k in score('placeholder', 'small')}
V2 = None
out_rows = []
for r in rows:
    if r['variant'] == 'bench_0_floor':
        continue
    if r['variant'] == 'bench_1_reference' and r['rep'] != '10':
        continue
    f = os.path.join(OUT, r['run_id'] + '.md')
    base = {k: r[k] for k in keep}
    base['v1_leaks'] = base.pop('leaks'); base['v1_redactions'] = base.pop('redactions')
    base['v1_drupal'] = base.pop('drupal_mentions'); base['v1_retention'] = base.pop('retention_pct')
    if not os.path.exists(f) or r['status'] != 'completed':
        v2 = dict(EMPTY, g0_delivered=0, g1_format=0, g2_scope=0, v2_class='loud')
    else:
        v2 = score(open(f, encoding='utf-8', errors='replace').read(), r['page'])
    base.update(v2)
    out_rows.append(base)
    V2 = V2 or list(base.keys())

with open(os.path.join(HERE, 'runs_v2.csv'), 'w', newline='', encoding='utf-8') as fh:
    w = csv.DictWriter(fh, fieldnames=V2)
    w.writeheader(); w.writerows(out_rows)

# ----------------------------------------------------------------- summary
def short(v):
    return v.replace('bench_', 'B').split('_')[0].upper()

def mshort(m):
    return m.replace('claude-', '').replace('-20251001', '') or '—'

print(f'{len(out_rows)} runs scored -> runs_v2.csv\n')
print('B1 controls (sanity: fidelity 1.0, fabrication 0):')
for r in out_rows:
    if r['variant'] == 'bench_1_reference':
        print(f"  {r['page']:7} fidelity={r['fidelity']} fabrication={r['fabrication']} "
              f"chrome={r['out_chrome']} structure={r['structure']} class={r['v2_class']}")

print('\nOutcome class per variant x model (all pages, shadowed rows excluded):')
cells = {}
for r in out_rows:
    if r['variant'] in ('bench_1_reference',) or r['prompt_shadowed'] == '1':
        continue
    cells.setdefault((short(r['variant']), mshort(r['model'])), Counter())[r['v2_class']] += 1
order = ['correct', 'degraded', 'silent', 'format', 'loud']
print(f"{'cell':22} " + ' '.join(f'{o:>8}' for o in order))
for k in sorted(cells):
    c = cells[k]
    print(f"{k[0]+' '+k[1]:22} " + ' '.join(f'{c.get(o, 0):>8}' for o in order))

print('\nMedium page, per run:')
print(f"{'variant':5} {'model':11} {'rep':>3} {'v1kept':>6} {'v1red':>5} {'v1leak':>6} | "
      f"{'recall':>6} {'prec':>5} {'subj':>5} {'fidel':>5} {'fabr':>5} {'glyph':>5} {'class':>8}")
for r in sorted(out_rows, key=lambda x: (x['variant'], x['model'], x['rep'])):
    if r['page'] != 'medium' or r['variant'] == 'bench_1_reference' or r['prompt_shadowed'] == '1':
        continue
    print(f"{short(r['variant']):5} {mshort(r['model']):11} {r['rep']:>3} {r['v1_retention']:>6} {r['v1_redactions']:>5} {r['v1_leaks']:>6} | "
          f"{r['recall']:>6} {r['precision']:>5} {r['subject']:>5} {r['fidelity']:>5} {r['fabrication']:>5} {r['glyphs']:>5} {r['v2_class']:>8}")
