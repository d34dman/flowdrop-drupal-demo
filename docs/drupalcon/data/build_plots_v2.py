#!/usr/bin/env python3
"""Generates site/redaction-benchmark/artifacts/redaction-benchmark-plots-v2.html from runs_v2.csv.

The v2 successor of the Redaction Benchmark Plots: one model at a time (single-select, Sonnet 4.6
by default), eight variants across three pages, on the rubric-v2 axes. Regenerate after any
rescoring; do not edit the HTML by hand.
"""
import json, os
from v2data import load
from v2style import CSS, FONTS

HERE = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.join(HERE, '..', 'site', 'redaction-benchmark', 'artifacts', 'redaction-benchmark-plots-v2.html')
D = load()

html = r'''<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Redaction Benchmark Plots v2</title>
<style>body{margin:0}img{max-width:100%}[hidden]{display:none!important}</style>
</head>
<body>
__FONTS__
__CSS__
<style>
:root{--v1:#2E7355;--v2:#B0531D;--v3:#1B6FA8;--v4:#8A4F9E;--v5:#B03A5B;--v6:#0F7C86;--v7:#8A6D1F;--v8:#3F6F2A}
@media (prefers-color-scheme:dark){:root:not([data-theme="light"]){--v1:#5FBF92;--v2:#D98A4A;--v3:#5AA8DC;--v4:#BE8FD1;--v5:#E0798F;--v6:#4FC2CC;--v7:#D4B44A;--v8:#8FCC6A}}
:root[data-theme="dark"]{--v1:#5FBF92;--v2:#D98A4A;--v3:#5AA8DC;--v4:#BE8FD1;--v5:#E0798F;--v6:#4FC2CC;--v7:#D4B44A;--v8:#8FCC6A}
.pt{font-family:var(--mono);font-size:9.5px;fill:var(--ink);paint-order:stroke;stroke:var(--surf);stroke-width:2.5px;stroke-linejoin:round}
.axl{font-family:var(--sans);font-size:10.5px;font-weight:700;fill:var(--muted);letter-spacing:.09em;text-transform:uppercase}
.fct{font-family:var(--sans);font-size:12px;font-weight:700;fill:var(--ink)}
.ring{fill:none;stroke:var(--silent);stroke-width:1.6;stroke-dasharray:3 2.5}
.trail{fill:none;stroke-width:1.4;opacity:.5}
.mk{stroke:var(--surf);stroke-width:1.5}
.mk:hover{stroke:var(--ink);stroke-width:2}
.filterbar{position:sticky;top:0;z-index:30;background:var(--surf);border-bottom:1px solid var(--rule);padding:10px 0;box-shadow:0 6px 14px -12px rgba(0,0,0,.35)}
.filterbar .wrap{display:flex;flex-wrap:wrap;gap:8px 18px;align-items:center}
.fgrp{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.chip2{display:inline-flex;align-items:center;gap:6px;font-family:var(--mono);font-size:.72rem;padding:3px 10px;border:1px solid var(--rule);border-radius:99px;background:transparent;color:var(--muted);cursor:pointer;line-height:1.5}
.chip2[aria-pressed="true"]{color:var(--ink);border-color:currentColor}
.chip2[aria-pressed="false"]{opacity:.5}
.chip2 .dsw{width:9px;height:9px;border-radius:50%;flex:none;display:inline-block}
.chip2:hover{border-color:var(--accent)}
.fcount{font-family:var(--mono);font-size:.7rem;color:var(--muted);margin-left:auto}
.mstat{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin:18px 0 6px}
.tb th:nth-child(-n+3),.tb td:nth-child(-n+3){text-align:left}
</style>

<header class="wrap">
  <p class="eyebrow">▌▌▌▌ &nbsp;FlowDrop redaction benchmark &nbsp;·&nbsp; rubric v2</p>
  <h1>Redaction Benchmark Plots,<br>one model at a time.</h1>
  <p class="stand">Eight workflow variants across three page sizes, plotted five ways, for whichever model the bar below
  selects. The v1 page plotted retention and glyph counts for Sonnet 4.6 alone; this one grades every run against a
  hand-cleaned gold document and shows fidelity, tokens, latency, leaks and cost, with the outcome class on every mark.
  Colour is the variant throughout; marker shape is the page. Small, medium and large are input page sizes
  (~38 KB, ~164 KB, ~535 KB of raw HTML). The v1 page is <a href="../archive/v1/redaction-benchmark-plots.html">archived</a>.</p>
</header>

<div class="filterbar"><div class="wrap">
  <div class="fgrp"><span class="lbl">Model</span><div class="seg" id="fmodel"></div></div>
  <div class="fgrp"><span class="lbl">Variant</span><div id="fvar" style="display:flex;flex-wrap:wrap;gap:5px"></div></div>
  <div class="fgrp"><span class="lbl">Page</span><div id="fpage" style="display:flex;gap:5px"></div><button class="chip2" id="freset" aria-pressed="true">reset</button></div>
  <span class="fcount" id="fcount"></span>
</div></div>

<section><div class="wrap">
  <div class="mstat" id="mstat"></div>
  <div class="note"><h3 id="mtitle"></h3><p id="mnote"></p></div>
</div></section>

<section><div class="wrap">
  <h2>Figure 1 · What you pay against what you keep</h2>
  <p>The central tradeoff. Horizontal is cost on a log axis, vertical is fidelity: the share of gold sentences found
  in the output. Up and to the left is better. Each line joins one variant's three pages small → medium → large and is
  labelled at its last point. A dashed red ring marks a run that was not <em>correct</em>, whatever its fidelity: a
  ringed point at the top copied the document faithfully and still got the redaction wrong.</p>
  <div class="figbox">
    <svg id="f1" role="img" aria-label="Scatter of cost against fidelity, one line per variant"></svg>
    <div class="legend" id="lg1"></div>
    <div class="figcap">The vertical axis is stretched above the dashed 0.90 line, where most runs sit. Runs that delivered
    nothing sit at fidelity 0. The cost axis rescales to the filtered runs. Hover for numbers.</div>
  </div>
</div></section>

<section><div class="wrap">
  <h2>Figure 2 · Latency tracks what comes out, not what goes in</h2>
  <p>The same runs in both panels; only the horizontal axis changes. Wall time is the whole workflow, including
  FlowDrop's fetch, conversion and any tool round-trips, so a variant with more LLM calls sits higher for the same
  output length.</p>
  <div class="figbox">
    <svg id="f2" role="img" aria-label="Two panels of wall time against output tokens and input tokens"></svg>
    <div class="legend" id="lg2"></div>
    <div class="figcap">Both token axes are log. Wall time in seconds, linear, rescaled to the filtered runs.</div>
  </div>
</div></section>

<section><div class="wrap">
  <h2>Figure 3 · How input cost scales with the page</h2>
  <p>Input tokens per run against page size. A variant that passes the page content through a tool argument pays for it
  on every call; one that hands the model a URL and receives Markdown pays once and less. The slope of each line is the
  price of the design, independent of the model.</p>
  <div class="figbox">
    <svg id="f3" role="img" aria-label="Input tokens by page size, one line per variant"></svg>
    <div class="legend" id="lg3"></div>
    <div class="figcap">Input tokens, log scale, summed over every LLM call in the run. Horizontal axis is raw HTML size.</div>
  </div>
</div></section>

<section><div class="wrap">
  <h2>Figure 4 · What got through</h2>
  <p>For every variant and page, the target mentions left readable (red) and the marks that landed on a protected name
  such as Drupal or on invented text (yellow). A correct cell has no bar at all. Only the medium page has competitor
  mentions to redact; the small and large pages can fail here only by over-redacting or by inventing names to redact.</p>
  <div class="figbox">
    <svg id="f4" role="img" aria-label="Leaks and protected-name hits per variant and page"></svg>
    <div class="legend" id="lg4"></div>
    <div class="figcap">Counts of mentions and marks. Cells with no graded redaction (format or loud failures, or never run) are
    listed as such. The medium page has 30 target mentions in its body; the small and large pages have none.</div>
  </div>
</div></section>

<section><div class="wrap">
  <h2>Figure 5 · Orchestration overhead against cost</h2>
  <p>LLM calls per run against what the run cost. Single-shot variants sit at one call; the ReAct and Reflexion designs
  spend extra calls on tool use and critique. Whether those calls buy anything is what Figure 1 and Figure 4 answer.</p>
  <div class="figbox">
    <svg id="f5" role="img" aria-label="LLM calls against cost, one trail per variant"></svg>
    <div class="legend" id="lg5"></div>
    <div class="figcap">Cost on a log axis rescaled to the filtered runs. The letter in each mark is the outcome class.</div>
  </div>
</div></section>

<section><div class="wrap">
  <h2>Every run on this model</h2>
  <div class="figbox" style="padding:0 0 4px">
    <table class="tb" id="tbl"><thead><tr>
      <th>Variant</th><th>Page</th><th>Class</th><th>Cost</th><th>Wall</th><th>Calls</th><th>Tokens in</th><th>Tokens out</th>
      <th>Marks</th><th>Leaks</th><th>Drupal kept</th><th>Recall</th><th>Precision</th><th>Subject</th><th>Fidelity</th><th>Fabric.</th></tr></thead>
      <tbody></tbody></table>
  </div>
</div></section>

<script>
const D = __DATA__;
const NS = 'http://www.w3.org/2000/svg';
const PAGES = ['small', 'medium', 'large'];
const LET = {correct: 'C', degraded: 'D', silent: 'S', format: 'F', loud: 'L'};
const CLSN = {correct: 'correct', degraded: 'degraded', silent: 'silent failure', format: 'format failure', loud: 'loud failure'};
const VCOL = {}; D.variants.forEach((v, i) => VCOL[v] = `var(--v${i + 1})`);
const SHAPE = {small: 'circle', medium: 'square', large: 'triangle'};
const F = {model: 'Sonnet 4.6', vars: new Set(D.variants), pages: new Set(PAGES)};
const R = () => D.runs.filter(r => r.model === F.model && F.vars.has(r.v) && F.pages.has(r.page));
const VS = () => D.variants.filter(v => F.vars.has(v));
const PS = () => PAGES.filter(p => F.pages.has(p));
const el = (n, a = {}) => { const e = document.createElementNS(NS, n); for (const k in a) if (a[k] != null) e.setAttribute(k, a[k]); return e; };
const txt = (s, a = {}) => { const t = el('text', a); t.textContent = s; return t; };
const money = v => '$' + (v < 1 ? v.toFixed(3) : v.toFixed(2));
const f3 = v => v == null ? '—' : v.toFixed(2);
const fmtN = n => n >= 1e6 ? (n / 1e6).toFixed(1) + 'M' : n >= 1e3 ? Math.round(n / 1e3) + 'k' : String(n);
const tip = r => `${r.v} ${D.vname[r.v]} · ${r.page} · ${r.model} · ${CLSN[r.cls]}\nfidelity ${f3(r.fidelity)} · recall ${f3(r.recall)} · precision ${f3(r.precision)} · subject ${f3(r.subject)} · fabrication ${f3(r.fabrication)}\n${r.glyphs ?? 0} marks · ${r.leaks ?? 0} leaks · ${r.calls} calls · ${r.tin.toLocaleString()} in / ${r.tout.toLocaleString()} out · ${money(r.cost)} · ${Math.round(r.wall)}s`;
function mark(svg, shape, x, y, r, fill, extra = {}) {
  let m;
  if (shape === 'circle') m = el('circle', {cx: x, cy: y, r});
  else if (shape === 'square') m = el('rect', {x: x - r, y: y - r, width: 2 * r, height: 2 * r});
  else m = el('polygon', {points: `${x},${y - r * 1.2} ${x - r * 1.1},${y + r * .8} ${x + r * 1.1},${y + r * .8}`});
  m.setAttribute('fill', fill); m.setAttribute('class', 'mk'); for (const k in extra) m.setAttribute(k, extra[k]);
  svg.appendChild(m); return m;
}
const withTip = (m, r) => { const t = el('title'); t.textContent = tip(r); m.appendChild(t); return m; };
// log scale with a padded, nice domain computed from the filtered data
function logScale(vals, x0, w, floor = 1e-4) {
  const v = vals.filter(x => x > 0); const lo = v.length ? Math.min(...v) : 1, hi = v.length ? Math.max(...v) : 10;
  const a = Math.floor(Math.log10(Math.max(lo, floor)) * 2) / 2, b = Math.max(a + 0.5, Math.ceil(Math.log10(hi) * 2) / 2);
  const s = x => x0 + (Math.log10(Math.max(x, floor)) - a) / (b - a) * w;
  const ticks = []; for (let e = Math.ceil(a); e <= b; e++) ticks.push(Math.pow(10, e));
  if (ticks.length < 3) for (let e = Math.floor(a); e <= b; e++) [2, 5].forEach(k => { const t = k * Math.pow(10, e); if (t >= Math.pow(10, a) && t <= Math.pow(10, b)) ticks.push(t); });
  return {s, ticks: [...new Set(ticks)].sort((p, q) => p - q)};
}
function linScale(vals, y0, h, floor0 = true) {
  const hi = vals.length ? Math.max(...vals) : 1; const step = Math.pow(10, Math.floor(Math.log10(hi || 1))); let top = Math.ceil(hi / step) * step; if (top / step < 2) top = Math.ceil(hi / (step / 4)) * (step / 4);
  const s = v => y0 + h - v / top * h; const ticks = []; const n = 5; for (let i = 0; i <= n; i++) ticks.push(top * i / n);
  return {s, ticks, top};
}
// Fidelity axis: 0–0.9 takes the bottom 30% of the panel, 0.9–1.0 the top 70%.
const FB = 0.9, FSPLIT = 0.3;
const fy = f => f <= FB ? (f / FB) * FSPLIT : FSPLIT + ((f - FB) / (1 - FB)) * (1 - FSPLIT);
const FTICKS = [0, 0.5, 0.9, 0.95, 1];
const variantLegend = () => VS().map(v => `<span class="it"><span class="sw" style="background:${VCOL[v]}"></span>${v} <span style="color:var(--faint)">${D.vname[v]}</span></span>`).join('');
const pageLegend = '<span class="it" style="margin-left:auto">page:</span><span class="it">● small</span><span class="it">■ medium</span><span class="it">▲ large</span>';
const ringLegend = '<span class="it"><span class="sw" style="border:1.6px dashed var(--silent);background:none"></span>not correct</span>';
const empty = (svg, W, H, msg = 'Nothing selected.') => { svg.setAttribute('viewBox', `0 0 ${W} ${H}`); svg.appendChild(txt(msg, {x: W / 2, y: H / 2, class: 'tk', 'text-anchor': 'middle'})); };
const frame = (svg, x0, y0, w, h) => svg.appendChild(el('rect', {x: x0, y: y0, width: w, height: h, fill: 'none', class: 'ax'}));
const byVariant = runs => VS().map(v => [v, PAGES.map(p => runs.filter(r => r.v === v && r.page === p)).flat()]).filter(([, rs]) => rs.length);

// ---- figure 1: cost vs fidelity
function fig1() {
  const svg = document.getElementById('f1'); svg.innerHTML = ''; const runs = R();
  const W = 960, H = 440, L = 56, T = 20, Rm = 90, B = 44, pw = W - L - Rm, ph = H - T - B; svg.setAttribute('viewBox', `0 0 ${W} ${H}`);
  if (!runs.length) return empty(svg, W, H);
  const {s: x, ticks} = logScale(runs.map(r => r.cost), L, pw, 1e-4); const y = f => T + ph - fy(f) * ph;
  FTICKS.forEach(f => { svg.appendChild(el('line', {x1: L, y1: y(f), x2: L + pw, y2: y(f), class: f === FB ? 'ax' : 'gl', 'stroke-dasharray': f === FB ? '3 3' : null})); svg.appendChild(txt(f.toFixed(2), {x: L - 8, y: y(f) + 3.5, class: 'tk', 'text-anchor': 'end'})); });
  ticks.forEach(c => { svg.appendChild(el('line', {x1: x(c), y1: T, x2: x(c), y2: T + ph, class: 'gl'})); svg.appendChild(txt(money(c).replace(/0+$/, '').replace(/\.$/, ''), {x: x(c), y: T + ph + 16, class: 'tk', 'text-anchor': 'middle'})); });
  frame(svg, L, T, pw, ph);
  svg.appendChild(txt('cost per run', {x: L + pw / 2, y: H - 8, class: 'axl', 'text-anchor': 'middle'}));
  svg.appendChild(txt('fidelity', {x: 12, y: T + ph / 2, class: 'axl', transform: `rotate(-90 12 ${T + ph / 2})`, 'text-anchor': 'middle'}));
  byVariant(runs).forEach(([v, rs]) => {
    const pts = rs.map(r => [x(r.cost), y(r.fidelity ?? 0), r]);
    if (pts.length > 1) svg.appendChild(el('polyline', {points: pts.map(q => q.slice(0, 2).join(',')).join(' '), class: 'trail', stroke: VCOL[v]}));
    pts.forEach(([px, py, r]) => { withTip(mark(svg, SHAPE[r.page], px, py, 5.5, VCOL[v]), r); if (r.cls !== 'correct') svg.appendChild(el('circle', {cx: px, cy: py, r: 9.5, class: 'ring'})); });
    const last = pts[pts.length - 1]; svg.appendChild(txt(v, {x: last[0] + 11, y: last[1] + 3.5, class: 'pt', fill: VCOL[v]}));
  });
  document.getElementById('lg1').innerHTML = variantLegend() + ringLegend + pageLegend;
}

// ---- figure 2: wall vs tokens out / tokens in
function fig2() {
  const svg = document.getElementById('f2'); svg.innerHTML = ''; const runs = R().filter(r => r.wall > 0);
  const W = 960, H = 360, T = 20, B = 44, L = 60, gap = 50, pw = (W - L - 20 - gap) / 2, ph = H - T - B; svg.setAttribute('viewBox', `0 0 ${W} ${H}`);
  if (!runs.length) return empty(svg, W, H);
  const {s: y, ticks: yt} = linScale(runs.map(r => r.wall), T, ph);
  [['tout', 'output tokens'], ['tin', 'input tokens']].forEach(([k, label], i) => {
    const x0 = L + i * (pw + gap); const {s: x, ticks} = logScale(runs.map(r => r[k]), x0, pw, 1);
    yt.forEach(t => { svg.appendChild(el('line', {x1: x0, y1: y(t), x2: x0 + pw, y2: y(t), class: 'gl'})); if (i === 0) svg.appendChild(txt(Math.round(t) + 's', {x: x0 - 8, y: y(t) + 3.5, class: 'tk', 'text-anchor': 'end'})); });
    ticks.forEach(t => { svg.appendChild(el('line', {x1: x(t), y1: T, x2: x(t), y2: T + ph, class: 'gl'})); svg.appendChild(txt(fmtN(t), {x: x(t), y: T + ph + 16, class: 'tk', 'text-anchor': 'middle'})); });
    frame(svg, x0, T, pw, ph); svg.appendChild(txt(label, {x: x0 + pw / 2, y: H - 8, class: 'axl', 'text-anchor': 'middle'}));
    byVariant(runs).forEach(([v, rs]) => rs.forEach(r => { withTip(mark(svg, SHAPE[r.page], x(r[k]), y(r.wall), 5.5, VCOL[v]), r); if (r.cls !== 'correct') svg.appendChild(el('circle', {cx: x(r[k]), cy: y(r.wall), r: 9.5, class: 'ring'})); }));
  });
  svg.appendChild(txt('wall time', {x: 12, y: T + ph / 2, class: 'axl', transform: `rotate(-90 12 ${T + ph / 2})`, 'text-anchor': 'middle'}));
  document.getElementById('lg2').innerHTML = variantLegend() + ringLegend + pageLegend;
}

// ---- figure 3: input tokens by page size
function fig3() {
  const svg = document.getElementById('f3'); svg.innerHTML = ''; const runs = R();
  const W = 960, H = 380, L = 70, T = 20, Rm = 90, B = 44, pw = W - L - Rm, ph = H - T - B; svg.setAttribute('viewBox', `0 0 ${W} ${H}`);
  if (!runs.length) return empty(svg, W, H);
  const xs = {small: 0, medium: .5, large: 1}, x = p => L + 40 + xs[p] * (pw - 80);
  const lo = Math.min(...runs.map(r => r.tin || 1)), hi = Math.max(...runs.map(r => r.tin || 1));
  const a = Math.floor(Math.log10(lo)), b = Math.ceil(Math.log10(hi)); const y = v => T + ph - (Math.log10(Math.max(v, 1)) - a) / (b - a) * ph;
  for (let e = a; e <= b; e++) { const t = Math.pow(10, e); svg.appendChild(el('line', {x1: L, y1: y(t), x2: L + pw, y2: y(t), class: 'gl'})); svg.appendChild(txt(fmtN(t), {x: L - 8, y: y(t) + 3.5, class: 'tk', 'text-anchor': 'end'}));
    if (e < b) [2, 5].forEach(k => svg.appendChild(el('line', {x1: L, y1: y(k * t), x2: L + pw, y2: y(k * t), class: 'gl', opacity: .5}))); }
  PAGES.forEach(p => svg.appendChild(txt(`${p} · ${D.pagekb[p]} KB`, {x: x(p), y: T + ph + 16, class: 'tk', 'text-anchor': 'middle'})));
  frame(svg, L, T, pw, ph);
  svg.appendChild(txt('input tokens', {x: 12, y: T + ph / 2, class: 'axl', transform: `rotate(-90 12 ${T + ph / 2})`, 'text-anchor': 'middle'}));
  byVariant(runs).forEach(([v, rs]) => {
    const pts = PAGES.map(p => { const q = rs.filter(r => r.page === p); return q.length ? [x(p), y(Math.max(...q.map(r => r.tin))), q] : null; }).filter(Boolean);
    if (pts.length > 1) svg.appendChild(el('polyline', {points: pts.map(q => q.slice(0, 2).join(',')).join(' '), class: 'trail', stroke: VCOL[v]}));
    pts.forEach(([px, , q]) => q.forEach(r => withTip(mark(svg, SHAPE[r.page], px, y(r.tin), 5.5, VCOL[v]), r)));
    const last = pts[pts.length - 1]; svg.appendChild(txt(v, {x: last[0] + 11, y: last[1] + 3.5, class: 'pt', fill: VCOL[v]}));
  });
  document.getElementById('lg3').innerHTML = variantLegend() + pageLegend;
}

// ---- figure 4: leaks and protected-name hits per cell
function fig4() {
  const svg = document.getElementById('f4'); svg.innerHTML = ''; const runs = R(); const ps = PS();
  const rows = []; VS().forEach(v => ps.forEach(p => rows.push([v, p, runs.filter(r => r.v === v && r.page === p)])));
  const W = 960, L = 230, T = 30, RH = 18, GAP = 10; const H = T + rows.length * RH + VS().length * GAP + 30; svg.setAttribute('viewBox', `0 0 ${W} ${H}`);
  if (!rows.length) return empty(svg, W, 120);
  const MAX = Math.max(10, ...runs.map(r => (r.leaks ?? 0) + (r.gp ?? 0) + (r.go ?? 0))); const x = n => L + n / MAX * (W - L - 160);
  const step = MAX > 40 ? 10 : MAX > 20 ? 5 : MAX > 10 ? 2 : 1;
  for (let n = 0; n <= MAX; n += step) { svg.appendChild(el('line', {x1: x(n), y1: T - 6, x2: x(n), y2: H - 24, class: 'gl'})); svg.appendChild(txt(String(n), {x: x(n), y: T - 12, class: 'tk', 'text-anchor': 'middle'})); }
  let y = T, lastV = null;
  rows.forEach(([v, p, rs]) => {
    if (v !== lastV) { if (lastV !== null) y += GAP; svg.appendChild(txt(v, {x: 14, y: y + 13, class: 'fct'})); svg.appendChild(txt(D.vname[v], {x: 40, y: y + 13, class: 'tk'})); lastV = v; }
    svg.appendChild(txt(p, {x: L - 8, y: y + 13, class: 'tk', 'text-anchor': 'end'}));
    if (!rs.length) { svg.appendChild(txt('not run', {x: x(0) + 4, y: y + 13, class: 'tk', opacity: .6})); y += RH; return; }
    rs.forEach(r => {
      if (r.recall == null) { svg.appendChild(txt(`${CLSN[r.cls]} · no graded redaction`, {x: x(0) + 4, y: y + 13, class: 'tk', fill: 'var(--silent)'})); }
      else {
        const lk = r.leaks ?? 0, bad = (r.gp ?? 0) + (r.go ?? 0);
        if (lk) { const b = el('rect', {x: x(0), y: y + 3, width: Math.max(x(lk) - x(0), 1.5), height: RH - 6, fill: 'var(--silent)', rx: 2}); withTip(b, r); svg.appendChild(b); }
        if (bad) { const b = el('rect', {x: x(lk) + (lk ? 2 : 0), y: y + 3, width: Math.max(x(bad) - x(0), 1.5), height: RH - 6, fill: 'var(--format)', rx: 2}); withTip(b, r); svg.appendChild(b); }
        const parts = []; if (lk) parts.push(`${lk} readable`); if (bad) parts.push(`${bad} on protected / invented`);
        svg.appendChild(txt(parts.length ? parts.join(' · ') : 'clean', {x: x(lk + bad) + 8, y: y + 13, class: 'tk', fill: parts.length ? null : 'var(--ok)'}));
      }
      svg.appendChild(txt(LET[r.cls], {x: W - 20, y: y + 13, class: 'pt', 'text-anchor': 'middle', fill: r.cls === 'correct' ? 'var(--ok)' : 'var(--silent)'}));
      y += RH;
    });
  });
  document.getElementById('lg4').innerHTML = '<span class="it"><span class="sw" style="background:var(--silent);border-radius:2px"></span>target mentions left readable</span><span class="it"><span class="sw" style="background:var(--format);border-radius:2px"></span>marks on protected names or invented text</span><span class="it">letter = class</span>';
}

// ---- figure 5: calls vs cost
function fig5() {
  const svg = document.getElementById('f5'); svg.innerHTML = ''; const runs = R();
  const W = 960, H = 380, L = 56, T = 20, Rm = 90, B = 44, pw = W - L - Rm, ph = H - T - B; svg.setAttribute('viewBox', `0 0 ${W} ${H}`);
  if (!runs.length) return empty(svg, W, H);
  const maxC = Math.max(2, ...runs.map(r => r.calls)); const x = c => L + 30 + (c - 1) / (maxC - 1 || 1) * (pw - 60);
  const {s: y, ticks} = logScale(runs.map(r => r.cost), 0, 1, 1e-4); const yy = c => T + ph - y(c) * ph;
  ticks.forEach(c => { svg.appendChild(el('line', {x1: L, y1: yy(c), x2: L + pw, y2: yy(c), class: 'gl'})); svg.appendChild(txt(money(c).replace(/0+$/, '').replace(/\.$/, ''), {x: L - 8, y: yy(c) + 3.5, class: 'tk', 'text-anchor': 'end'})); });
  const cstep = maxC > 12 ? 2 : 1; for (let c = 1; c <= maxC; c += cstep) { svg.appendChild(el('line', {x1: x(c), y1: T, x2: x(c), y2: T + ph, class: 'gl'})); svg.appendChild(txt(String(c), {x: x(c), y: T + ph + 16, class: 'tk', 'text-anchor': 'middle'})); }
  frame(svg, L, T, pw, ph);
  svg.appendChild(txt('LLM calls per run', {x: L + pw / 2, y: H - 8, class: 'axl', 'text-anchor': 'middle'}));
  svg.appendChild(txt('cost per run', {x: 12, y: T + ph / 2, class: 'axl', transform: `rotate(-90 12 ${T + ph / 2})`, 'text-anchor': 'middle'}));
  byVariant(runs).forEach(([v, rs]) => {
    const pts = rs.map(r => [x(r.calls), yy(r.cost), r]);
    if (pts.length > 1) svg.appendChild(el('polyline', {points: pts.map(q => q.slice(0, 2).join(',')).join(' '), class: 'trail', stroke: VCOL[v]}));
    pts.forEach(([px, py, r]) => { withTip(mark(svg, SHAPE[r.page], px, py, 8, VCOL[v]), r); svg.appendChild(txt(LET[r.cls], {x: px, y: py + 3.2, 'text-anchor': 'middle', style: 'font-family:var(--mono);font-size:8.5px;font-weight:500;fill:#fff;pointer-events:none'})); });
    const last = pts[pts.length - 1]; svg.appendChild(txt(v, {x: last[0] + 13, y: last[1] + 3.5, class: 'pt', fill: VCOL[v]}));
  });
  document.getElementById('lg5').innerHTML = variantLegend() + pageLegend + '<span class="it">C correct · D degraded · S silent · F format · L loud</span>';
}

// ---- table and model summary
function table() {
  const tb = document.querySelector('#tbl tbody'); tb.innerHTML = ''; const ord = {small: 0, medium: 1, large: 2};
  [...R()].sort((a, b) => a.v.localeCompare(b.v) || ord[a.page] - ord[b.page]).forEach(r => {
    const tr = document.createElement('tr');
    const c = (v, inv) => v == null ? '<td>—</td>' : `<td class="${(inv ? v > 0.25 : v < 0.75) ? 'low' : ''}">${v.toFixed(3)}</td>`;
    tr.innerHTML = `<td><span class="sw" style="background:${VCOL[r.v]};margin-right:6px"></span>${r.v} <span style="color:var(--faint)">${D.vname[r.v]}</span></td><td>${r.page}</td><td><span class="chip ${r.cls}">${LET[r.cls]}</span></td>` +
      `<td>${money(r.cost)}</td><td>${r.wall ? Math.round(r.wall) + 's' : '—'}</td><td>${r.calls}</td><td>${r.tin.toLocaleString()}</td><td>${r.tout.toLocaleString()}</td>` +
      `<td>${r.glyphs ?? '—'}</td><td>${r.leaks ?? '—'}</td><td>${r.drupal ?? '—'}</td>` + c(r.recall) + c(r.precision) + c(r.subject) + c(r.fidelity) + c(r.fabrication, true);
    tb.appendChild(tr);
  });
}
function summary() {
  const all = D.runs.filter(r => r.model === F.model), runs = R();
  const n = c => runs.filter(r => r.cls === c).length; const spend = runs.reduce((a, r) => a + r.cost, 0);
  const tiles = [[runs.length, 'runs shown'], [n('correct'), 'correct'], [n('degraded'), 'degraded'], [n('silent') + n('format') + n('loud'), 'failed'], [money(spend), 'spent']];
  document.getElementById('mstat').innerHTML = tiles.map(([v, l]) => `<div class="tile"><div class="n">${v}</div><div class="l">${l}</div></div>`).join('');
  const best = [...runs].filter(r => r.cls === 'correct').sort((a, b) => a.cost - b.cost)[0];
  const worst = [...runs].filter(r => r.cls !== 'correct').sort((a, b) => (a.fidelity ?? 0) - (b.fidelity ?? 0))[0];
  const cells = all.filter(r => F.vars.has(r.v) && F.pages.has(r.page)); const never = VS().length * PS().length - new Set(cells.map(r => r.v + r.page)).size;
  document.getElementById('mtitle').textContent = `${F.model}: ${all.length} graded runs across ${new Set(all.map(r => r.v)).size} variants`;
  document.getElementById('mnote').textContent = (runs.length ? `Of the ${runs.length} shown, ${n('correct')} are correct and ${runs.length - n('correct')} are not. ` : 'No run matches the filter. ') +
    (best ? `The cheapest correct run is ${best.v} on the ${best.page} page at ${money(best.cost)} with fidelity ${f3(best.fidelity)}. ` : '') +
    (worst ? `The lowest-fidelity run that was not correct is ${worst.v} on the ${worst.page} page (${CLSN[worst.cls]}, fidelity ${f3(worst.fidelity ?? 0)}). ` : '') +
    (never ? `${never} of the selected variant × page cells were never run on this model.` : 'Every selected variant × page cell was run at least once on this model.');
}

// ---- controls
function draw() {
  fig1(); fig2(); fig3(); fig4(); fig5(); table(); summary();
  document.querySelectorAll('#fmodel button').forEach(b => b.setAttribute('aria-pressed', String(b.dataset.m === F.model)));
  document.querySelectorAll('#fvar .chip2').forEach(b => b.setAttribute('aria-pressed', String(F.vars.has(b.dataset.v))));
  document.querySelectorAll('#fpage .chip2').forEach(b => b.setAttribute('aria-pressed', String(F.pages.has(b.dataset.p))));
  const total = D.runs.filter(r => r.model === F.model).length;
  document.getElementById('fcount').textContent = `${R().length} of ${total} ${F.model} runs · ${D.n} graded runs in all`;
  const fresh = F.vars.size === D.variants.length && F.pages.size === PAGES.length; document.getElementById('freset').setAttribute('aria-pressed', String(!fresh));
}
document.getElementById('fmodel').innerHTML = D.models.map(m => `<button type="button" data-m="${m}">${m}</button>`).join('');
document.getElementById('fvar').innerHTML = D.variants.map(v => `<button type="button" class="chip2" data-v="${v}" title="${D.vname[v]}"><span class="dsw" style="background:${VCOL[v]}"></span>${v}</button>`).join('');
document.getElementById('fpage').innerHTML = PAGES.map(p => `<button type="button" class="chip2" data-p="${p}">${p}</button>`).join('');
document.querySelectorAll('#fmodel button').forEach(b => b.addEventListener('click', () => { F.model = b.dataset.m; draw(); }));
document.querySelectorAll('#fvar .chip2').forEach(b => b.addEventListener('click', () => { F.vars.has(b.dataset.v) ? F.vars.delete(b.dataset.v) : F.vars.add(b.dataset.v); draw(); }));
document.querySelectorAll('#fpage .chip2').forEach(b => b.addEventListener('click', () => { F.pages.has(b.dataset.p) ? F.pages.delete(b.dataset.p) : F.pages.add(b.dataset.p); draw(); }));
document.getElementById('freset').addEventListener('click', () => { F.vars = new Set(D.variants); F.pages = new Set(PAGES); draw(); });
draw();
</script>
</body>
</html>
'''
html = html.replace('__FONTS__', FONTS).replace('__CSS__', CSS).replace('__DATA__', json.dumps(D, separators=(',', ':')))
open(OUT, 'w', encoding='utf-8').write(html)
print(f'wrote {os.path.relpath(OUT, HERE)}: {D["n"]} runs, {os.path.getsize(OUT)//1024} KB')
