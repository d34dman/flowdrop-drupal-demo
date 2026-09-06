#!/usr/bin/env python3
"""Copies your runs into a flowdrop-ai-bench checkout, ready for a pull request.

Selects every collected run whose tag starts with one of the given prefixes, and
copies results/runs/<run_id>.json to <bench>/runs/ and results/outputs/<run_id>.md to
<bench>/outputs/. Existing files are left alone unless --force. Nothing is edited by
hand: the scorer in the bench repo derives every figure from these two files.

Usage: export.py <path-to-flowdrop-ai-bench> <tag-prefix> [<tag-prefix> ...] [--force]
                 [--include-failed]
Failed runs (pipeline_status != completed or no output) are skipped unless
--include-failed; they are still worth a PR when the failure itself is the finding.
"""
import json, os, shutil, sys

HERE = os.path.dirname(os.path.abspath(__file__))
RES = os.path.join(HERE, 'results')
args = [a for a in sys.argv[1:] if not a.startswith('--')]
flags = {a for a in sys.argv[1:] if a.startswith('--')}
if len(args) < 2: sys.exit(__doc__)
bench, prefixes = args[0], tuple(args[1:])
for d in ('runs', 'outputs'): os.makedirs(os.path.join(bench, d), exist_ok=True)

copied = skipped = 0
for fn in sorted(os.listdir(os.path.join(RES, 'runs'))):
    if not fn.endswith('.json'): continue
    r = json.load(open(os.path.join(RES, 'runs', fn), encoding='utf-8'))
    if not r.get('tag', '').startswith(prefixes): continue
    out_src = os.path.join(RES, 'outputs', r['run_id'] + '.md')
    ok = r.get('pipeline_status') == 'completed' and os.path.exists(out_src)
    if not ok and '--include-failed' not in flags:
        skipped += 1; print(f"  skip  {r['run_id']}  ({r.get('pipeline_status')}, {'no output' if not os.path.exists(out_src) else 'has output'})"); continue
    dst = os.path.join(bench, 'runs', fn)
    if os.path.exists(dst) and '--force' not in flags:
        print(f"  exists {fn} (use --force)"); continue
    shutil.copyfile(os.path.join(RES, 'runs', fn), dst)
    if os.path.exists(out_src): shutil.copyfile(out_src, os.path.join(bench, 'outputs', r['run_id'] + '.md'))
    copied += 1; print(f"  copied {r['run_id']}  {r.get('tag')}  ${r.get('cost_usd')}")
print(f"{copied} runs exported to {bench}, {skipped} failed runs skipped")
