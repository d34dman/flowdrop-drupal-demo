#!/usr/bin/env python3
"""Prints every collected run for one cell/page (and optionally model), newest last.

Usage: summarize.py <workflow_or_Bn> <page> [model_substring]
"""
import json, sys
from pathlib import Path
CELL = {'B0':'bench_0_floor','B1':'bench_1_reference','B2':'bench_2_raw_html_llm','B3':'bench_3_markdown_llm',
        'B4':'bench_4_ai_agent_tool','B5':'bench_5_react_agent','B5a':'bench_5a_react_agent_naive',
        'B6':'bench_6_agent_autonomous','B7':'bench_7_react_optimized','B8':'bench_8_react_with_tools_in_parent',
        'B9':'bench_9_reflexion_with_tools_in_parent'}
wf = CELL.get(sys.argv[1], sys.argv[1]); page = sys.argv[2]; model = sys.argv[3] if len(sys.argv) > 3 else ''
d = Path(__file__).parent / 'results'
tags = {}
for l in (d / 'runs.jsonl').read_text().splitlines():
    if l.strip(): j = json.loads(l); tags[j['run_id']] = j.get('tag', '')
print(f"{'run':>10} {'tag':28} {'status':9} {'model':26} {'sec':>7} {'calls':>5} {'in':>7} {'out':>6} {'cached':>6} {'usd':>8} {'chars':>6}")
for l in (d / 'metrics.jsonl').read_text().splitlines():
    if not l.strip(): continue
    r = json.loads(l)
    if r['workflow'] != wf or r['url_key'] != page: continue
    m = ','.join(r['models'])
    if model and model not in m: continue
    print(f"{r['run_id'][-10:]:>10} {tags.get(r['run_id'],'')[:28]:28} {r['pipeline_status']:9} {m[:26]:26} "
          f"{r['total_seconds']:7.1f} {r['llm_calls']:5d} {r['input_tokens']:7d} {r['output_tokens']:6d} "
          f"{r['cached_tokens']:6d} {r['cost_usd']:8.4f} {r['output_chars']:6d}")
