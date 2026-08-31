#!/usr/bin/env python3
"""Compares the two agent architectures on equal terms.

Both now actually receive their system prompt, so the only difference is how
page content reaches the converter: B5 must pass it through the model's own
output channel as a tool argument; B7 passes a URL and gets Markdown back.
"""
import json, re, os, sys

DIR = os.path.dirname(os.path.abspath(__file__)) + '/results'
REFDEN = {'small': 6648, 'medium': 12550, 'large': 50082}
NAME = {'bench_5_react_agent': 'B5 ReAct (html tool)',
        'bench_7_react_optimized': 'B7 ReAct (url tool)'}
GLYPH = '\u258c' * 4
# Competitors actually present in the control documents, counted there rather
# than assumed: small has none, medium has WordPress x36, large has WordPress,
# Joomla, Mambo and Backdrop x2. A name still readable in the output is a leak.
COMPETITORS = ['WordPress', 'Joomla', 'TYPO3', 'Sitecore', 'Contentful', 'Wix',
               'Squarespace', 'Magento', 'Umbraco', 'Mambo', 'Backdrop',
               'Optimizely', 'Episerver', 'Kentico', 'Plone']
# Present in the control for each page, so the achievable redaction count.
TARGET = {}


def leaks(t):
    return sum(len(re.findall(r'\b' + re.escape(c) + r'\b', t, re.I)) for c in COMPETITORS)


def denoise(t):
    """Strip link targets and image alt blocks; both sides get the same
    treatment, so a model is not penalised for omitting markup."""
    t = re.sub(r'\]\([^)]*\)', ']', t)
    t = re.sub(r'!\[[^\]]*\]', '', t)
    t = re.sub(r'[ \t]+', ' ', t)
    return re.sub(r'\n{3,}', '\n\n', t).strip()


for _pg, _ref in (('small', 'small'), ('medium', 'medium'), ('large', 'large')):
    _f = sorted(__import__('glob').glob(f'{DIR}/outputs/bench_1_reference__{_pg}__*.md'))
    TARGET[_pg] = leaks(open(_f[0], encoding='utf-8', errors='replace').read()) if _f else 0

rows = []
for line in open(f'{DIR}/metrics.jsonl'):
    r = json.loads(line)
    if r.get('tag') != (sys.argv[1] if len(sys.argv) > 1 else 'final-sonnet5'):
        continue
    f = f"{DIR}/outputs/{r['run_id']}.md"
    txt = open(f, encoding='utf-8', errors='replace').read() if os.path.exists(f) else ''
    tags = len(re.findall(r'</?(?:div|span|p|a|table|body|html|ul|li|h[1-6])\b[^>]*>', txt, re.I))
    rows.append({
        'wf': r['workflow'], 'page': r['url_key'], 'calls': r['llm_calls'],
        'tin': r['input_tokens'], 'tout': r['output_tokens'],
        'wall': r['wall_seconds'], 'cost': r['cost_usd'], 'chars': len(txt),
        'kept': len(denoise(txt).encode()) / REFDEN[r['url_key']] * 100 if txt else 0,
        'red': txt.count(GLYPH), 'leak': leaks(txt),
        'status': r['pipeline_status'],
        'rawhtml': tags / max(len(txt) / 1000, .001) > 5,
    })

hdr = f"{'variant':22} {'page':7} {'calls':>5} {'in tok':>9} {'wall':>7} {'cost':>8} {'kept':>6} {'red':>4} {'leak':>5}"
print('competitor mentions in each control:',
      ', '.join(f'{k} {v}' for k, v in TARGET.items()))
print(hdr); print('-' * len(hdr))
for pg in ('small', 'medium', 'large'):
    for r in sorted([x for x in rows if x['page'] == pg], key=lambda x: x['wf']):
        flag = '  NOT-MARKDOWN' if r['rawhtml'] else ('  ' + r['status'] if r['status'] != 'completed' else '')
        print(f"{NAME.get(r['wf'], r['wf'])[:22]:22} {pg:7} {r['calls']:5} {r['tin']:9,} "
              f"{r['wall']:6.1f}s ${r['cost']:7.4f} {r['kept']:5.0f}% {r['red']:4} {r['leak']:5}{flag}")
    print()

# Head-to-head ratios, page by page: one number per axis is what a talk can use.
print('B7 relative to B5, per page (lower is better except fidelity):')
for pg in ('small', 'medium', 'large'):
    a = next((x for x in rows if x['page'] == pg and x['wf'] == 'bench_5_react_agent'), None)
    b = next((x for x in rows if x['page'] == pg and x['wf'] == 'bench_7_react_optimized'), None)
    if not a or not b or not a['cost']:
        continue
    print(f"  {pg:7} cost x{b['cost']/a['cost']:.2f}   input tokens x{b['tin']/max(a['tin'],1):.2f}   "
          f"wall x{b['wall']/max(a['wall'],.01):.2f}   fidelity {b['kept']:.0f}% vs {a['kept']:.0f}%")
tot5 = sum(x['cost'] for x in rows if x['wf'] == 'bench_5_react_agent')
tot7 = sum(x['cost'] for x in rows if x['wf'] == 'bench_7_react_optimized')
print(f"\ntotal this sweep: B5 ${tot5:.2f}   B7 ${tot7:.2f}   combined ${tot5+tot7:.2f}")
