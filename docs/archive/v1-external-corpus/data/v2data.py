#!/usr/bin/env python3
"""Shared data loading for the generated v2 site pages (build_site_v2.py, build_matrix_v2.py).

load() returns the dict the pages inline as JSON: graded runs with axes and class, paused
runs, model and variant lists, totals. Excluded from `runs`: B1 controls, prompt-shadowed
rows, the dropped B5a arm, and paused rows (listed under `paused`).
"""
import csv, os
from collections import Counter

HERE = os.path.dirname(os.path.abspath(__file__))
MODELS = ['claude-haiku-4-5-20251001', 'claude-sonnet-4-6', 'claude-sonnet-5', 'claude-opus-5']
MNAME = {'claude-haiku-4-5-20251001': 'Haiku 4.5', 'claude-sonnet-4-6': 'Sonnet 4.6',
         'claude-sonnet-5': 'Sonnet 5', 'claude-opus-5': 'Opus 5'}
VNAME = {'B2': 'Raw HTML → LLM', 'B3': 'Markdown → LLM', 'B4': 'ai_agents + tool', 'B5': 'ReAct, content tool',
         'B6': 'Autonomous agent', 'B7': 'ReAct, URL tool', 'B8': 'ReAct, URL tool in parent', 'B9': 'Reflexion critic'}
PAGE_KB = {'small': 38, 'medium': 164, 'large': 535}


def short(v):
    return v.replace('bench_', 'B').split('_')[0].upper().replace('5A', '5a')


def num(x, d=None):
    try:
        return float(x)
    except (ValueError, TypeError):
        return d


def load():
    rows = list(csv.DictReader(open(os.path.join(HERE, 'runs_v2.csv'), encoding='utf-8')))
    for r in rows:  # a run that died before its first LLM call has no metered model; the sweep tag names it
        if not r['model']:
            r['model'] = next((m for m in MODELS if m.replace('-20251001', '') in r['tag']), '')
    graded, paused = [], []
    for r in rows:
        if r['variant'] == 'bench_1_reference' or r['prompt_shadowed'] == '1' or short(r['variant']) == 'B5a':
            continue
        (paused if r['status'] == 'paused' else graded).append(r)
    runs = [dict(
        id=r['run_id'], v=short(r['variant']), page=r['page'], model=MNAME[r['model']], cls=r['v2_class'],
        cost=num(r['cost_usd'], 0), calls=int(r['calls'] or 0), wall=num(r['wall_seconds'], 0),
        tin=int(r['input_tokens'] or 0), tout=int(r['output_tokens'] or 0), chars=int(r['output_chars'] or 0),
        recall=num(r['recall']), precision=num(r['precision']), subject=num(r['subject']),
        fidelity=num(r['fidelity']), fabrication=num(r['fabrication']), structure=num(r['structure']),
        glyphs=int(r['glyphs']) if r['glyphs'] else None, gc=int(r['glyph_correct']) if r['glyph_correct'] else None,
        gp=int(r['glyph_protected']) if r['glyph_protected'] else None, go=int(r['glyph_other']) if r['glyph_other'] else None,
        leaks=int(r['leaks']) if r['leaks'] else None, drupal=int(r['drupal_kept']) if r['drupal_kept'] else None,
        tag=r['tag']) for r in graded]
    pausedj = [dict(v=short(r['variant']), page=r['page'], model=MNAME[r['model']], cost=num(r['cost_usd'], 0), tag=r['tag']) for r in paused]
    return dict(runs=runs, paused=pausedj, models=[MNAME[m] for m in MODELS],
                variants=sorted({r['v'] for r in runs}, key=lambda v: int(v[1:])), vname=VNAME, pagekb=PAGE_KB,
                totals=Counter(r['cls'] for r in runs), n=len(runs), spend=round(sum(r['cost'] for r in runs), 2))
