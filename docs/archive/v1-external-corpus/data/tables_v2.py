#!/usr/bin/env python3
"""Prints the rubric-v2 tables (Markdown) from runs_v2.csv. Used verbatim in
data/RUNS_V2.md and v2/02-scorecard.md, 04-cost-per-correct.md.

Excluded from class counts: bench_1 controls, prompt-shadowed rows (the three early B7
draws), the dropped B5a arm, and `paused` rows (runs stalled on a FlowDrop confirmation
gate or a pending job, so no model answer exists to grade). Both exclusions are listed.
"""
import csv, os, sys
from collections import Counter, defaultdict

HERE = os.path.dirname(os.path.abspath(__file__))
rows = list(csv.DictReader(open(os.path.join(HERE, 'runs_v2.csv'), encoding='utf-8')))
for r in rows:  # a run that died before its first LLM call has no metered model; the sweep tag names it
    if not r['model']:
        r['model'] = next((m for m in ('claude-haiku-4-5-20251001', 'claude-sonnet-4-6', 'claude-sonnet-5', 'claude-opus-5')
                           if m.replace('-20251001', '') in r['tag']), '')
MODELS = ['claude-haiku-4-5-20251001', 'claude-sonnet-4-6', 'claude-sonnet-5', 'claude-opus-5']
MNAME = {'claude-haiku-4-5-20251001': 'haiku 4.5', 'claude-sonnet-4-6': 'sonnet 4.6',
         'claude-sonnet-5': 'sonnet 5', 'claude-opus-5': 'opus 5'}
PAGES = ['small', 'medium', 'large']
CLASSES = ['correct', 'degraded', 'silent', 'format', 'loud']
LETTER = dict(correct='C', degraded='D', silent='S', format='F', loud='L')


def short(v):
    return v.replace('bench_', 'B').split('_')[0].upper().replace('5A', '5a')


def scored(r):
    return (r['variant'] != 'bench_1_reference' and r['prompt_shadowed'] != '1'
            and short(r['variant']) != 'B5a' and r['status'] != 'paused')


S = [r for r in rows if scored(r)]
paused = [r for r in rows if r['status'] == 'paused']
variants = sorted({short(r['variant']) for r in S}, key=lambda v: int(v[1:]))
out = []
P = out.append

P('## Outcome class per cell\n')
P('One letter per run: **C**orrect · **D**egraded · **S**ilent · **F**ormat · **L**oud.')
P(f'Shadowed-prompt B7 draws, the dropped B5a arm and {len(paused)} `paused` runs (stalled before any')
P('answer existed, see below) are excluded.\n')
P('| Variant | Page | ' + ' | '.join(MNAME[m] for m in MODELS) + ' |')
P('|---|---|' + '---|' * len(MODELS))
for v in variants:
    for p in PAGES:
        cells = []
        for m in MODELS:
            c = ''.join(LETTER[r['v2_class']] for r in S if short(r['variant']) == v and r['page'] == p and r['model'] == m)
            cells.append(c or '·')
        P(f'| {v} | {p} | ' + ' | '.join(cells) + ' |')

P('\n## Per variant\n')
P('| Variant | Runs | Correct | Degraded | Silent | Format | Loud |')
P('|---|---|---|---|---|---|---|')
tot = Counter()
for v in variants:
    c = Counter(r['v2_class'] for r in S if short(r['variant']) == v)
    tot += c
    P(f'| {v} | {sum(c.values())} | ' + ' | '.join(str(c.get(k, 0)) for k in CLASSES) + ' |')
P(f'| **all** | {sum(tot.values())} | ' + ' | '.join(f'**{tot.get(k, 0)}**' for k in CLASSES) + ' |')

P('\n## Per model\n')
P('| Model | Runs | Correct | Degraded | Silent | Format | Loud |')
P('|---|---|---|---|---|---|---|')
for m in MODELS:
    c = Counter(r['v2_class'] for r in S if r['model'] == m)
    P(f'| {MNAME[m]} | {sum(c.values())} | ' + ' | '.join(str(c.get(k, 0)) for k in CLASSES) + ' |')

P('\n## Every silent failure\n')
P('| Page | Variant | Model | What went wrong |')
P('|---|---|---|---|')
for r in sorted([r for r in S if r['v2_class'] == 'silent'], key=lambda r: (PAGES.index(r['page']), r['variant'], r['model'])):
    why = []
    f = lambda k: float(r[k])
    if f('fidelity') < 0.75: why.append(f"fidelity {r['fidelity']}: truncated")
    if f('recall') < 0.75: why.append(f"recall {r['recall']}: {r['leaks']} leak(s), {r['glyph_correct']} correct glyph(s)")
    if f('precision') < 0.75: why.append(f"precision {r['precision']}: {r['glyph_other']} glyph(s) on invented text, {r['glyph_protected']} on protected names")
    if f('subject') < 0.75: why.append(f"subject {r['subject']}: {r['glyph_protected']} glyphs on protected names")
    if f('fabrication') > 0.25: why.append(f"fabrication {r['fabrication']}")
    if r['g2_scope'] == '0': why.append(f"structure {r['structure']}: not the same document")
    P(f"| {r['page']} | {short(r['variant'])} | {MNAME[r['model']]} | {'; '.join(why)} |")

P('\n## Every degraded run\n')
P('| Page | Variant | Model | Lowest axis |')
P('|---|---|---|---|')
for r in sorted([r for r in S if r['v2_class'] == 'degraded'], key=lambda r: (PAGES.index(r['page']), r['variant'], r['model'])):
    ax = min(['recall', 'precision', 'subject', 'fidelity'], key=lambda k: float(r[k]))
    note = f"{ax} {r[ax]}"
    if ax == 'recall': note += f" ({r['leaks']} of {int(r['leaks']) + int(r['glyph_correct'])} target mentions left readable)"
    if float(r['fabrication']) > 0.05: note += f"; fabrication {r['fabrication']}"
    P(f"| {r['page']} | {short(r['variant'])} | {MNAME[r['model']]} | {note} |")

P('\n## Paused runs (excluded)\n')
P('| Page | Variant | Model | Tag | Cost |')
P('|---|---|---|---|---|')
for r in paused:
    P(f"| {r['page']} | {short(r['variant'])} | {MNAME[r['model']]} | `{r['tag']}` | ${float(r['cost_usd']):.4f} |")

P('\n## Cost per correct run\n')
P('Cell spend ÷ correct draws, per variant × model, all pages pooled. `∞` = money spent, nothing correct.')
P('Spend includes the failed and paused draws of the cell.\n')
P('| Variant | ' + ' | '.join(MNAME[m] for m in MODELS) + ' |')
P('|---|' + '---|' * len(MODELS))
for v in variants:
    cells = []
    for m in MODELS:
        rs = [r for r in rows if short(r['variant']) == v and r['model'] == m and r['prompt_shadowed'] != '1']
        if not rs:
            cells.append('·'); continue
        spend = sum(float(r['cost_usd'] or 0) for r in rs)
        ok = sum(1 for r in rs if r['v2_class'] == 'correct')
        cells.append(f'${spend / ok:.2f} ({ok}/{len(rs)})' if ok else f'∞ (0/{len(rs)})')
    P(f'| {v} | ' + ' | '.join(cells) + ' |')

P('\n## Cheapest correct run per page\n')
P('| Page | Variant | Model | Cost | Calls | Wall |')
P('|---|---|---|---|---|---|')
for p in PAGES:
    ok = sorted([r for r in S if r['page'] == p and r['v2_class'] == 'correct'], key=lambda r: float(r['cost_usd']))
    for r in ok[:3]:
        P(f"| {p} | {short(r['variant'])} | {MNAME[r['model']]} | ${float(r['cost_usd']):.4f} | {r['calls']} | {(f"{float(r['wall_seconds']):.0f}s" if float(r['wall_seconds']) else '—')} |")

P('\n## Medium page, correct runs by cost\n')
P('The only page with both redaction targets and a protected subject.\n')
P('| Variant | Model | Cost | Recall | Precision | Subject | Fidelity | Fabrication |')
P('|---|---|---|---|---|---|---|---|')
for r in sorted([r for r in S if r['page'] == 'medium' and r['v2_class'] == 'correct'], key=lambda r: float(r['cost_usd'])):
    P(f"| {short(r['variant'])} | {MNAME[r['model']]} | ${float(r['cost_usd']):.4f} | {r['recall']} | {r['precision']} | {r['subject']} | {r['fidelity']} | {r['fabrication']} |")

P('\n## Spend by class\n')
P('| Class | Runs | Spend |')
P('|---|---|---|')
for k in CLASSES:
    rs = [r for r in S if r['v2_class'] == k]
    P(f"| {k} | {len(rs)} | ${sum(float(r['cost_usd'] or 0) for r in rs):.2f} |")
P(f"| paused (excluded) | {len(paused)} | ${sum(float(r['cost_usd'] or 0) for r in paused):.2f} |")

print('\n'.join(out))
