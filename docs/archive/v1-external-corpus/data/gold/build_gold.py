#!/usr/bin/env python3
"""Build the gold documents for rubric v2 from the B1 control conversions.

Gold = the article body only. Site chrome (menus, cookie banners, newsletter and
product promos, sidebars, Wikipedia infobox / hatnotes / edit links / citation
markers / references) is removed by explicit, reviewable line rules below, then
the text is canonicalised: link targets and images dropped, whitespace collapsed.

Run from the repo root:  python3 docs/drupalcon/data/gold/build_gold.py [controls_dir]
Default controls_dir is scratchpad/bench/results/outputs (the B1 r10 files).
"""
import json, os, re, sys

HERE = os.path.dirname(os.path.abspath(__file__))
CTRL = sys.argv[1] if len(sys.argv) > 1 else 'scratchpad/bench/results/outputs'
CONTROL = 'bench_1_reference__{page}__r10__1788101327.md'

# 1-based inclusive line ranges of the control that form the body, and ranges
# inside them that are chrome. Every number here was checked by eye.
RULES = {
    'small': {   # https://www.drupal.org/about
        'keep': [(126, 126), (195, 236)],   # "# About" title, then the six article sections
        'drop': [(205, 207)],               # tweet-this card
        'why': 'Lines 1-194 are the Drupal.org header, cookie banner, main menu, "About" '
               'sub-menu and "Recent posts" sidebar; 237-342 are the footer link columns.',
    },
    'medium': {  # https://www.ibm.com/think/topics/drupal-wordpress
        'keep': [(19, 280)],
        'drop': [(25, 25), (71, 71),        # hero and section images
                 (75, 79),                  # "Keep your head in the cloud" newsletter promo
                 (115, 127),                # "AI Academy" promo
                 (240, 260)],               # share buttons ("Link copied") and "Ebook" promo
        'why': 'Lines 1-18 are the IBM header; 281-373 are Resources, Related solutions '
               'and Take-the-next-step product promos.',
    },
    'large': {   # https://en.wikipedia.org/wiki/Drupal
        'keep': [(390, 695)],               # lead paragraph through "See also"
        'drop': [(410, 434)],               # release-history table, flattened to one unreadable line by the converter
        'why': 'Lines 1-389 are Wikipedia navigation, the infobox and a maintenance '
               'hatnote; 696-1078 are References, Further reading, External links and '
               'the page footer. Citation markers and [edit] links are stripped inline.',
    },
}

# Backdrop CMS (a 2013 Drupal fork) was a target in v1 and is dropped here: models split
# evenly on whether a fork is a competitor, and the large page's only body mention was it.
COMPETITORS = ['WordPress', 'Joomla', 'TYPO3', 'Sitecore', 'Contentful', 'Wix',
               'Squarespace', 'Magento', 'Umbraco', 'Mambo',
               'Optimizely', 'Episerver', 'Kentico', 'Plone']
# Proper nouns that must survive redaction. "Drupal" is the subject; the rest are
# names a careless redactor might sweep up. Only counted if present in the gold.
PROTECTED = ['Drupal', 'IBM', 'Acquia', 'PHP', 'Symfony', 'MySQL', 'PostgreSQL',
             'Linux', 'Apache', 'Microsoft', 'Oracle', 'Wikipedia', 'Twig', 'GitLab']


def canon(line):
    line = re.sub(r'<a [^>]*></a>', '', line)                    # empty anchor before headings
    line = re.sub(r'\\\[\[edit\]\([^)]*\)\\\]', '', line)          # \[[edit](...)\]
    line = re.sub(r'\[\\\[[^\]]*\\\]\]\(#cite_note[^)]*\)', '', line)  # [\[24\]](#cite_note-24)
    line = re.sub(r'\[\\\[update\\\]\]\([^)]*\)', '', line)
    line = re.sub(r'!\[[^\]]*\]\([^)]*\)', '', line)               # images
    line = re.sub(r'\[([^\]]*)\]\([^)]*\)', r'\1', line)           # [text](url) -> text
    line = re.sub(r'&amp;', '&', line)
    # Wikipedia's converter emits "[Democratic Party](url "Democratic Party (United States)")"
    # as "Democratic Party Democratic Party (United States)": drop the immediate repeat.
    line = re.sub(r'\b(\w+(?: \w+){0,3}) \1\b', r'\1', line)
    line = re.sub(r'[ \t ]+', ' ', line).strip()
    line = re.sub(r'^(#+) ', r'\1 ', line)
    return line


def in_ranges(n, ranges):
    return any(a <= n <= b for a, b in ranges)


def build(page):
    src = open(os.path.join(CTRL, CONTROL.format(page=page)), encoding='utf-8').read().split('\n')
    r = RULES[page]
    out = []
    for i, raw in enumerate(src, 1):
        if not in_ranges(i, r['keep']) or in_ranges(i, r['drop']):
            continue
        c = canon(raw)
        if c:
            out.append(c)
    text = '\n\n'.join(out) + '\n'
    return text


def mentions(text, names):
    found = []
    lines = text.split('\n')
    for li, line in enumerate(lines):
        for name in names:
            for m in re.finditer(r'\b' + re.escape(name) + r'[\w.-]*', line):
                found.append({'name': name, 'surface': m.group(0), 'line': li + 1,
                              'col': m.start()})
    return sorted(found, key=lambda x: (x['line'], x['col']))


summary = []
for page in ('small', 'medium', 'large'):
    text = build(page)
    open(os.path.join(HERE, f'{page}.md'), 'w', encoding='utf-8').write(text)
    t = mentions(text, COMPETITORS)
    p = mentions(text, PROTECTED)
    json.dump({'page': page, 'source': CONTROL.format(page=page), 'rules': RULES[page],
               'count': len(t), 'mentions': t},
              open(os.path.join(HERE, f'{page}.targets.json'), 'w'), indent=1, ensure_ascii=False)
    json.dump({'page': page, 'count': len(p),
               'by_name': {n: sum(1 for x in p if x['name'] == n) for n in PROTECTED
                           if any(x['name'] == n for x in p)},
               'mentions': p},
              open(os.path.join(HERE, f'{page}.protected.json'), 'w'), indent=1, ensure_ascii=False)
    summary.append((page, len(text.encode()), text.count('\n#'), len(t),
                    {n: sum(1 for x in t if x['name'] == n) for n in COMPETITORS if any(x['name'] == n for x in t)},
                    sum(1 for x in p if x['name'] == 'Drupal'), len(p)))

for s in summary:
    print(f'{s[0]:7} bytes={s[1]:6} headings={s[2]:3} targets={s[3]:3} {s[4]}  drupal={s[5]} protected={s[6]}')
