#!/usr/bin/env python3
"""Aggregates results.jsonl into the tables the talk needs."""
import json, sys, statistics as st
from collections import defaultdict

path = sys.argv[1] if len(sys.argv) > 1 else 'scratchpad/bench/results/results.jsonl'
rows = [json.loads(l) for l in open(path) if l.strip()]
ok = [r for r in rows if r['status'] == 'completed']

LABEL = {
    'bench_1_reference':          '1 Deterministic reference',
    'bench_2_raw_html_llm':       '2 Raw HTML -> LLM',
    'bench_3_markdown_llm':       '3 Markdown node -> LLM',
    'bench_4_ai_agent_tool':      '4 Drupal AI Agent',
    'bench_5_react_agent':        '5 ReAct agent (tuned)',
    'bench_5a_react_agent_naive': '5a ReAct agent (as-authored)',
}
ORDER = list(LABEL)

def agg(vals):
    if not vals: return (0, 0, 0)
    return (st.median(vals), min(vals), max(vals))

print(f"runs: {len(rows)} total, {len(ok)} completed, {len(rows)-len(ok)} failed\n")

# --- per variant, across all pages -------------------------------------
print("PER VARIANT (median across all pages and reps)")
print(f"{'variant':32} {'n':>3} {'wall p50':>9} {'in tok':>9} {'out tok':>8} {'cost p50':>10} {'$ total':>9} {'calls':>6}")
by_wf = defaultdict(list)
for r in ok: by_wf[r['workflow']].append(r)
for wf in ORDER:
    rs = by_wf.get(wf, [])
    if not rs: continue
    w, *_ = agg([r['wall_seconds'] for r in rs])
    i, *_ = agg([r['input_tokens'] for r in rs])
    o, *_ = agg([r['output_tokens'] for r in rs])
    c, *_ = agg([r['cost_usd'] for r in rs])
    tot = sum(r['cost_usd'] for r in rs)
    calls, *_ = agg([r['llm_calls'] for r in rs])
    print(f"{LABEL[wf]:32} {len(rs):3} {w:8.1f}s {i:9.0f} {o:8.0f} {c:10.5f} {tot:9.4f} {calls:6.0f}")

# --- scaling by page size ---------------------------------------------
print("\nINPUT TOKENS BY PAGE SIZE (median)  -- the scaling curve")
urls = ['small', 'medium', 'large']
print(f"{'variant':32} " + ' '.join(f'{u:>12}' for u in urls))
for wf in ORDER:
    cells = []
    for u in urls:
        rs = [r for r in by_wf.get(wf, []) if r['url_key'] == u]
        cells.append(f"{agg([r['input_tokens'] for r in rs])[0]:12.0f}" if rs else f"{'-':>12}")
    print(f"{LABEL[wf]:32} " + ' '.join(cells))

print("\nCOST BY PAGE SIZE (median USD)")
print(f"{'variant':32} " + ' '.join(f'{u:>12}' for u in urls))
for wf in ORDER:
    cells = []
    for u in urls:
        rs = [r for r in by_wf.get(wf, []) if r['url_key'] == u]
        cells.append(f"{agg([r['cost_usd'] for r in rs])[0]:12.5f}" if rs else f"{'-':>12}")
    print(f"{LABEL[wf]:32} " + ' '.join(cells))

# --- determinism / variance -------------------------------------------
print("\nVARIANCE ACROSS REPS (wall seconds, min-max per page)")
for wf in ORDER:
    if wf not in by_wf: continue
    parts = []
    for u in urls:
        rs = [r for r in by_wf[wf] if r['url_key'] == u]
        if not rs: continue
        _, lo, hi = agg([r['wall_seconds'] for r in rs])
        parts.append(f"{u}:{lo:.0f}-{hi:.0f}s")
    print(f"  {LABEL[wf]:32} " + '  '.join(parts))

# --- failures ----------------------------------------------------------
bad = [r for r in rows if r['status'] != 'completed']
if bad:
    print("\nFAILURES")
    for r in bad:
        print(f"  {LABEL.get(r['workflow'], r['workflow']):32} {r['url_key']:7} r{r['rep']}  {r['status']}  {(r.get('error') or '')[:90]}")
