#!/usr/bin/env python3
"""Appends runs from results/metrics.jsonl to docs/drupalcon/data/runs.csv, by tag.

Computes the v1 columns exactly as the frozen rows were computed (verified against
existing rows on 2026-09-05), copies the output document into data/outputs/, and
skips run_ids already in the ledger.

Usage: fold_runs.py <tag-prefix> [<tag-prefix> ...]
"""
import csv, json, os, re, shutil, sys

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.abspath(os.path.join(HERE, '..', '..'))
DATA = os.path.join(ROOT, 'docs', 'drupalcon', 'data')
RES = os.path.join(HERE, 'results')
COMP = ['WordPress', 'Joomla', 'TYPO3', 'Sitecore', 'Contentful', 'Wix', 'Squarespace', 'Magento',
        'Umbraco', 'Mambo', 'Backdrop', 'Optimizely', 'Episerver', 'Kentico', 'Plone']
CTRL = {r['page']: int(r['denoised_bytes']) for r in csv.DictReader(open(os.path.join(DATA, 'controls.csv')))}


def denoise(t):
    t = re.sub(r'\]\([^)]*\)', ']', t)
    t = re.sub(r'!\[[^\]]*\]', '', t)
    t = re.sub(r'[ \t]+', ' ', t)
    return re.sub(r'\n{3,}', '\n\n', t).strip()


prefixes = tuple(sys.argv[1:]) or sys.exit(__doc__)
ledger = os.path.join(DATA, 'runs.csv')
have = {r['run_id'] for r in csv.DictReader(open(ledger, encoding='utf-8'))}
hdr = open(ledger, encoding='utf-8').readline().strip().split(',')
added = []
with open(ledger, 'a', newline='', encoding='utf-8') as fh:
    w = csv.DictWriter(fh, fieldnames=hdr)
    for line in open(os.path.join(RES, 'metrics.jsonl'), encoding='utf-8'):
        r = json.loads(line)
        if not r.get('tag', '').startswith(prefixes) or r['run_id'] in have:
            continue
        src = os.path.join(RES, 'outputs', r['run_id'] + '.md')
        txt = open(src, encoding='utf-8', errors='replace').read() if os.path.exists(src) else ''
        completed = r['pipeline_status'] == 'completed' and txt
        if completed:
            shutil.copyfile(src, os.path.join(DATA, 'outputs', r['run_id'] + '.md'))
        tags = len(re.findall(r'</?(?:div|span|p|a|table|body|html|ul|li|h[1-6])\b[^>]*>', txt, re.I))
        den = len(denoise(txt).encode()) if txt else 0
        row = dict(
            run_id=r['run_id'], tag=r.get('tag', ''), variant=r['workflow'], page=r['url_key'],
            model=','.join(r.get('models') or []), rep=r['rep'], status=r['pipeline_status'],
            calls=r['llm_calls'], input_tokens=r['input_tokens'], output_tokens=r['output_tokens'],
            cached_tokens=r['cached_tokens'], cost_usd=r['cost_usd'], wall_seconds=r['wall_seconds'],
            output_chars=len(txt), denoised_bytes=den,
            retention_pct=f'{den / CTRL[r["url_key"]] * 100:.1f}' if completed else '',
            redactions=txt.count('▌' * 4),
            leaks=sum(len(re.findall(r'\b' + c + r'\b', txt, re.I)) for c in COMP),
            drupal_mentions=len(re.findall(r'\bdrupal\b', txt, re.I)),
            html_tag_density=f'{tags / max(len(txt) / 1000, .001):.1f}' if completed else '',
            prompt_shadowed=0)
        w.writerow(row); added.append(row)
for a in added:
    print(f"{a['run_id']:62} {a['tag']:30} {a['status']:9} ret={a['retention_pct']:>5} red={a['redactions']:>3} "
          f"leak={a['leaks']:>2} drupal={a['drupal_mentions']:>3} ${a['cost_usd']}")
print(f'{len(added)} rows appended')
