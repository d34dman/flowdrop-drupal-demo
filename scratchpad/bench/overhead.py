#!/usr/bin/env python3
"""Orchestrator overhead: measured wall clock minus summed node execution time.

Node time comes from each job's execution_time_us; wall clock is timed around
launch(). What is left is everything FlowDrop itself did: validation, pipeline
creation, job generation, dispatch and persistence.
"""
import json, statistics, sys, pathlib

d = pathlib.Path(sys.argv[1] if len(sys.argv) > 1 else 'scratchpad/bench/results')
rows = [json.loads(l) for l in (d / 'metrics.jsonl').read_text().splitlines() if l.strip()]
rows = [r for r in rows if r.get('wall_seconds') is not None]

for r in rows:
    r['overhead'] = r['wall_seconds'] - r['total_seconds']

def table(rs, title):
    if not rs:
        return
    print(f"\n{title}")
    print(f"{'workflow':<28}{'page':<8}{'wall':>9}{'nodes':>9}{'overhead':>10}{'%':>7}{'jobs':>6}{'payload':>10}")
    for r in sorted(rs, key=lambda x: (x['workflow'], x['url_key'])):
        pct = 100 * r['overhead'] / r['wall_seconds'] if r['wall_seconds'] else 0
        print(f"{r['workflow']:<28}{r['url_key']:<8}{r['wall_seconds']:>8.3f}s"
              f"{r['total_seconds']:>8.3f}s{r['overhead']:>9.3f}s{pct:>6.1f}%"
              f"{r.get('job_count', 0):>6}{r.get('payload_bytes', 0)/1024:>9.0f}K")

floor = [r for r in rows if r['workflow'] == 'bench_0_floor']
rest = [r for r in rows if r['workflow'] != 'bench_0_floor']

if floor:
    o = [r['overhead'] for r in floor]
    w = [r['wall_seconds'] for r in floor]
    print(f"FLOOR (bench_0_floor, n={len(o)})")
    print(f"  wall     median {statistics.median(w):.3f}s  "
          f"min {min(w):.3f}  max {max(w):.3f}"
          + (f"  sd {statistics.stdev(w):.3f}" if len(w) > 1 else ""))
    print(f"  overhead median {statistics.median(o):.3f}s  "
          f"min {min(o):.3f}  max {max(o):.3f}")

# Overhead by (workflow, page), aggregated across repetitions.
print(f"\nBY CELL (median across reps)")
print(f"{'workflow':<28}{'page':<8}{'n':>3}{'overhead med':>14}{'min':>9}{'max':>9}{'payload':>10}")
cells = {}
for r in rows:
    cells.setdefault((r['workflow'], r['url_key']), []).append(r)
for (wf, page), rs in sorted(cells.items()):
    o = sorted(x['overhead'] for x in rs)
    pl = statistics.median(x.get('payload_bytes', 0) for x in rs) / 1024
    print(f"{wf:<28}{page:<8}{len(o):>3}{statistics.median(o):>13.3f}s"
          f"{min(o):>8.3f}s{max(o):>8.3f}s{pl:>9.0f}K")

table(rest, "EVERY RUN")

# Does overhead track payload size? Fit on bench_1_reference alone: same graph,
# same node count, only the payload varies — so the slope is attributable.
# Failed runs are excluded: a run that aborts reports wall clock with no node
# time behind it, which lands in this arithmetic as enormous fake overhead.
pts = [(r.get('payload_bytes', 0), r['overhead']) for r in rows
       if r.get('payload_bytes') and r.get('pipeline_status') == 'completed'
       and r['workflow'] == 'bench_1_reference']
if len(pts) > 2:
    xs = [p / 1024 / 1024 for p, _ in pts]
    ys = [o for _, o in pts]
    mx, my = statistics.mean(xs), statistics.mean(ys)
    num = sum((x - mx) * (y - my) for x, y in zip(xs, ys))
    den = sum((x - mx) ** 2 for x in xs)
    sy = statistics.pstdev(ys); sx = statistics.pstdev(xs)
    r_ = num / len(xs) / (sx * sy) if sx and sy else 0
    slope = num / den if den else 0
    intercept = my - slope * mx
    print(f"\nPAYLOAD vs OVERHEAD (bench_1_reference, completed only)")
    print(f"  n={len(pts)}  r={r_:.3f}  slope={slope:.3f} s/MB  intercept={intercept:.3f}s")
    if floor:
        fl = statistics.median(x['overhead'] for x in floor)
        print(f"  fixed cost (1-node floor)      {fl:.3f}s")
        print(f"  + graph cost (3 nodes, 0 bytes) {intercept - fl:+.3f}s"
              f"  = {(intercept - fl) / 2:.3f}s per extra node")
        print(f"  + payload cost                  {slope:.3f}s per MB persisted")
