#!/usr/bin/env python3
"""Generates site/redaction-benchmark/artifacts/redactor-model-matrix-v2.html from runs_v2.csv.

The v2 successor of the Redactor Model Matrix: variant x model x page on the rubric-v2 axes.
Regenerate after any rescoring; do not edit the HTML by hand.
"""
import json, os
from v2data import load
from v2style import CSS, FONTS

HERE = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.join(HERE, '..', 'site', 'redaction-benchmark', 'artifacts', 'redactor-model-matrix-v2.html')
D = load()

html = r'''<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Redactor Model Matrix v2</title>
<style>body{margin:0}img{max-width:100%}[hidden]{display:none!important}</style>
</head>
<body>
__FONTS__
__CSS__
<style>
.pt{font-family:var(--mono);font-size:9.5px;fill:var(--ink);paint-order:stroke;stroke:var(--surf);stroke-width:2.5px;stroke-linejoin:round}
.fct{font-family:var(--sans);font-size:12px;font-weight:700;fill:var(--ink)}
.fcts{font-family:var(--mono);font-size:9px;fill:var(--muted)}
.ring{fill:none;stroke:var(--silent);stroke-width:1.6;stroke-dasharray:3 2.5}
.trail{fill:none;stroke-width:1.4;opacity:.5}
.mk{stroke:var(--surf);stroke-width:1.5}
.mk:hover{stroke:var(--ink);stroke-width:2}
.tb th:nth-child(-n+3),.tb td:nth-child(-n+3){text-align:left}
.tb th:nth-child(4),.tb td:nth-child(4){text-align:left}
</style>

<header class="wrap">
  <p class="eyebrow">▌▌▌▌ &nbsp;FlowDrop redaction benchmark &nbsp;·&nbsp; rubric v2</p>
  <h1>Redactor Model Matrix,<br>graded against the gold.</h1>
  <p class="stand">Eight workflow variants, four models, three pages: __N__ graded runs. The v1 matrix ranked cells
  by bytes kept and marks placed; this one grades each run against a hand-cleaned gold document and shows fidelity,
  what was actually redacted, and cost, with the outcome class on every mark. Colour is the model throughout; marker
  shape is the page. One draw per cell unless the table says otherwise. The v1 page is
  <a href="../archive/v1/redactor-model-matrix.html">archived</a>.</p>
</header>

<section><div class="wrap">
  <h2>Figure 1 · Fidelity against cost, one panel per variant</h2>
  <p>Up and to the left is better: the whole gold body reproduced, for little money. Each colour is a model walked
  small → medium → large; a dashed red ring marks a run that was not <em>correct</em>, whatever its fidelity, so a
  ringed point at the top is a run that copied the document faithfully and still got the redaction wrong.</p>
  <div class="figbox">
    <svg id="f1" role="img" aria-label="Eight scatter panels of fidelity against cost, one per variant, coloured by model"></svg>
    <div class="legend" id="lg1"></div>
    <div class="figcap">Shared log cost axis across panels. Fidelity is the share of gold sentences found in the output at
    ≥ 0.90 similarity, redaction-tolerant. Runs that delivered nothing sit at fidelity 0 on the left edge. Hover for numbers.</div>
  </div>
  <div class="note"><h3>What the ring adds</h3>
    <p id="n1"></p></div>
</div></section>

<section><div class="wrap">
  <h2>Figure 2 · What was redacted on the medium page</h2>
  <p>The medium page has 30 WordPress mentions in its body and 38 of Drupal. For every variant and model the bar
  is split into the marks that landed on a target (blue), the target mentions left readable (red), and, when it
  happened, marks that landed on a protected name such as Drupal (yellow, drawn to the right of the bar). A correct
  cell is a solid blue bar and nothing else.</p>
  <div class="figbox">
    <svg id="f2" role="img" aria-label="Stacked bars per variant and model: correct marks, leaks, and marks on protected names, medium page"></svg>
    <div class="legend" id="lg2"></div>
    <div class="figcap">Medium page only. Marks are counted as runs of ▌, so a nine-bar glyph is one mark (v1 counted two).
    Marks in the retained IBM chrome are excluded. Backdrop, a Drupal fork, does not appear on this page.</div>
  </div>
</div></section>

<section><div class="wrap">
  <h2>Figure 3 · Fidelity as the page grows</h2>
  <p>The same fidelity numbers with page size on the horizontal axis. A flat line at 1.0 is a variant that
  reproduces the document at any size on that model. Where v1 showed Sonnet 5 "sagging" on B5, v2 shows a flat
  line: the sag was Drupal.org menu chrome, not content.</p>
  <div class="figbox">
    <svg id="f3" role="img" aria-label="Eight line panels of fidelity by page size, one per variant, coloured by model"></svg>
    <div class="legend" id="lg3"></div>
    <div class="figcap">Horizontal axis: raw HTML size of the small, medium and large pages. Ringed points are runs that were not correct. A missing point is a cell that was never run.</div>
  </div>
</div></section>

<section><div class="wrap">
  <h2>Figure 4 · What every cell cost</h2>
  <p>One row per variant and page, one dot per model, on a log axis. The letter in the dot is the outcome class, so
  the expensive failures and the cheap successes can be read off directly.</p>
  <div class="figbox">
    <svg id="f4" role="img" aria-label="Dot plot of cost per cell on a log scale, one row per variant and page, letters for class"></svg>
    <div class="legend" id="lg4"></div>
    <div class="figcap">Log scale. Repeated draws of a cell are drawn as separate dots. Paused runs are not shown.</div>
  </div>
</div></section>

<section><div class="wrap">
  <h2>Every cell, every metric</h2>
  <div class="figbox" style="padding:0 0 4px">
    <table class="tb" id="tbl"><thead><tr>
      <th>Variant</th><th>Page</th><th>Model</th><th>Class</th><th>Cost</th><th>Wall</th><th>Calls</th><th>Tokens in</th><th>Tokens out</th>
      <th>Marks</th><th>Leaks</th><th>Drupal kept</th><th>Recall</th><th>Precision</th><th>Subject</th><th>Fidelity</th><th>Fabric.</th></tr></thead>
      <tbody></tbody></table>
  </div>
</div></section>

<script>
const D = __DATA__;
const NS = 'http://www.w3.org/2000/svg';
const PAGES = ['small', 'medium', 'large'];
const LET = {correct: 'C', degraded: 'D', silent: 'S', format: 'F', loud: 'L'};
const MCOL = {'Haiku 4.5': 'var(--m1)', 'Sonnet 4.6': 'var(--m2)', 'Sonnet 5': 'var(--m3)', 'Opus 5': 'var(--m4)'};
const el = (n, a = {}) => { const e = document.createElementNS(NS, n); for (const k in a) e.setAttribute(k, a[k]); return e; };
const txt = (s, a = {}) => { const t = el('text', a); t.textContent = s; return t; };
const money = v => '$' + (v < 1 ? v.toFixed(3) : v.toFixed(2));
const f3 = v => v == null ? '—' : v.toFixed(2);
const tip = r => `${r.v} · ${r.page} · ${r.model} · ${r.cls}\nfidelity ${f3(r.fidelity)} · recall ${f3(r.recall)} · precision ${f3(r.precision)} · subject ${f3(r.subject)} · fabrication ${f3(r.fabrication)}\n${r.glyphs ?? 0} marks · ${r.leaks ?? 0} leaks · ${r.calls} calls · ${money(r.cost)} · ${Math.round(r.wall)}s`;
const LO = 0.001, HI = 3;
const logx = (v, x0, w) => x0 + (Math.log10(Math.max(v, LO)) - Math.log10(LO)) / (Math.log10(HI) - Math.log10(LO)) * w;
function mark(svg, shape, x, y, r, fill, extra = {}) {
  let m;
  if (shape === 'circle') m = el('circle', {cx: x, cy: y, r});
  else if (shape === 'square') m = el('rect', {x: x - r, y: y - r, width: 2 * r, height: 2 * r});
  else m = el('polygon', {points: `${x},${y - r * 1.2} ${x - r * 1.1},${y + r * .8} ${x + r * 1.1},${y + r * .8}`});
  m.setAttribute('fill', fill); m.setAttribute('class', 'mk'); for (const k in extra) m.setAttribute(k, extra[k]);
  svg.appendChild(m); return m;
}
const SHAPE = {small: 'circle', medium: 'square', large: 'triangle'};
const modelLegend = () => D.models.map(m => `<span class="it"><span class="sw" style="background:${MCOL[m]}"></span>${m}</span>`).join('');
const pageLegend = '<span class="it" style="margin-left:auto">page:</span><span class="it">● small</span><span class="it">■ medium</span><span class="it">▲ large</span>';
const ringLegend = '<span class="it"><span class="sw" style="border:1.6px dashed var(--silent);background:none"></span>not correct</span>';

// ---- panels helper: 8 variants in 2 rows of 4
function panels(svg, H, draw) {
  const W = 980, cols = 4, rows = Math.ceil(D.variants.length / cols), left0 = 44, gapX = 18, top = 30, gapY = 46, pw = (W - left0 - 10 - gapX * (cols - 1)) / cols, ph = H;
  svg.setAttribute('viewBox', `0 0 ${W} ${top + rows * ph + (rows - 1) * gapY + 40}`);
  D.variants.forEach((v, i) => {
    const c = i % cols, r = Math.floor(i / cols), x0 = left0 + c * (pw + gapX), y0 = top + r * (ph + gapY);
    svg.appendChild(txt(v, {x: x0, y: y0 - 14, class: 'fct'}));
    svg.appendChild(txt(D.vname[v], {x: x0 + 26, y: y0 - 14, class: 'fcts'}));
    draw(v, x0, y0, pw, ph, c === 0);
  });
}

// ---- figure 1
(function () {
  const svg = document.getElementById('f1'); const PH = 170;
  panels(svg, PH, (v, x0, y0, pw, ph, first) => {
    const y = f => y0 + ph - f * ph;
    [0, .25, .5, .75, 1].forEach(f => { svg.appendChild(el('line', {x1: x0, y1: y(f), x2: x0 + pw, y2: y(f), class: 'gl'}));
      if (first) svg.appendChild(txt(f.toFixed(2), {x: x0 - 6, y: y(f) + 3.5, class: 'tk', 'text-anchor': 'end'})); });
    [0.001, 0.01, 0.1, 1].forEach(c => { const x = logx(c, x0, pw); svg.appendChild(el('line', {x1: x, y1: y0, x2: x, y2: y0 + ph, class: 'gl'}));
      svg.appendChild(txt(money(c).replace('.000', ''), {x, y: y0 + ph + 13, class: 'tk', 'text-anchor': 'middle'})); });
    svg.appendChild(el('rect', {x: x0, y: y0, width: pw, height: ph, fill: 'none', class: 'ax'}));
    D.models.forEach(m => {
      const rs = PAGES.map(p => D.runs.filter(r => r.v === v && r.model === m && r.page === p)).flat();
      const pts = rs.map(r => [logx(r.cost, x0, pw), y(r.fidelity ?? 0), r]);
      const byPage = PAGES.map(p => pts.find(q => q[2].page === p)).filter(Boolean);
      if (byPage.length > 1) svg.appendChild(el('polyline', {points: byPage.map(q => q.slice(0, 2).join(',')).join(' '), class: 'trail', stroke: MCOL[m]}));
      pts.forEach(([x, yy, r]) => {
        const mk = mark(svg, SHAPE[r.page], x, yy, 5, MCOL[m]); const t = el('title'); t.textContent = tip(r); mk.appendChild(t);
        if (r.cls !== 'correct') svg.appendChild(el('circle', {cx: x, cy: yy, r: 9, class: 'ring'}));
      });
    });
  });
  document.getElementById('lg1').innerHTML = modelLegend() + ringLegend + pageLegend;
  const ringedHigh = D.runs.filter(r => r.cls !== 'correct' && (r.fidelity ?? 0) >= 0.95);
  document.getElementById('n1').textContent = `${ringedHigh.length} of the ${D.runs.filter(r => r.cls !== 'correct').length} runs that were not correct have fidelity 0.95 or better: they reproduced the document and failed on redaction, over-redaction or invented text. Under v1 those runs ranked with the successes. The three worst by class are B4 Sonnet 5 medium (subject 0.26, redacted Drupal), B5 Haiku medium (recall 0, redacted nothing) and B4 Sonnet 4.6 small (six competitor names written into a page that had none).`;
})();

// ---- figure 2: medium page redaction
(function () {
  const svg = document.getElementById('f2');
  const rows = [];
  D.variants.forEach(v => D.models.forEach(m => { const rs = D.runs.filter(r => r.v === v && r.model === m && r.page === 'medium' && r.recall != null); rs.forEach(r => rows.push(r)); }));
  const W = 980, L = 200, T = 30, RH = 16, GAP = 12; let y = T;
  const groups = D.variants.map(v => rows.filter(r => r.v === v));
  const H = T + rows.length * RH + groups.length * GAP + 30;
  svg.setAttribute('viewBox', `0 0 ${W} ${H}`);
  const MAX = 75, x = n => L + n / MAX * 560;
  [0, 10, 20, 30, 40, 50, 60, 70].forEach(n => { svg.appendChild(el('line', {x1: x(n), y1: T - 6, x2: x(n), y2: H - 24, class: 'gl'})); svg.appendChild(txt(String(n), {x: x(n), y: T - 12, class: 'tk', 'text-anchor': 'middle'})); });
  svg.appendChild(el('line', {x1: x(30), y1: T - 6, x2: x(30), y2: H - 24, class: 'ax', 'stroke-dasharray': '4 3'}));
  svg.appendChild(txt('30 targets', {x: x(30) + 4, y: H - 10, class: 'tk'}));
  groups.forEach((g, gi) => {
    if (!g.length) return;
    svg.appendChild(txt(g[0].v, {x: 14, y: y + 12, class: 'fct'})); svg.appendChild(txt(D.vname[g[0].v], {x: 14, y: y + 26, class: 'fcts'}));
    g.forEach(r => {
      const gc = r.gc ?? 0, lk = r.leaks ?? 0, gp = r.gp ?? 0, go = r.go ?? 0;
      svg.appendChild(txt(r.model, {x: L - 8, y: y + 11, class: 'tk', 'text-anchor': 'end'}));
      const b1 = el('rect', {x: x(0), y: y + 2, width: Math.max(x(gc) - x(0), 0), height: RH - 4, fill: MCOL[r.model], rx: 2});
      const t = el('title'); t.textContent = tip(r) + `\n${gc} marks on targets · ${lk} left readable · ${gp} on protected names · ${go} on invented text`; b1.appendChild(t); svg.appendChild(b1);
      if (lk) svg.appendChild(el('rect', {x: x(gc) + 2, y: y + 2, width: Math.max(x(lk) - x(0) - 2, 1), height: RH - 4, fill: 'var(--silent)', rx: 2}));
      if (gp + go) { svg.appendChild(el('rect', {x: x(Math.max(gc + lk, 0)) + 6, y: y + 2, width: Math.max(x(gp + go) - x(0), 1), height: RH - 4, fill: 'var(--format)', rx: 2}));
        svg.appendChild(txt(`${gp + go} on protected / invented`, {x: x(gc + lk + gp + go) + 12, y: y + 11, class: 'tk'})); }
      else if (lk) svg.appendChild(txt(`${lk} readable`, {x: x(gc + lk) + 8, y: y + 11, class: 'tk'}));
      svg.appendChild(txt(LET[r.cls], {x: W - 20, y: y + 11, class: 'pt', 'text-anchor': 'middle', fill: r.cls === 'correct' ? 'var(--ok)' : 'var(--silent)'}));
      y += RH;
    });
    y += GAP;
  });
  document.getElementById('lg2').innerHTML = '<span class="it"><span class="sw" style="background:var(--muted);border-radius:2px"></span>marks on targets (model colour)</span><span class="it"><span class="sw" style="background:var(--silent);border-radius:2px"></span>target mentions left readable</span><span class="it"><span class="sw" style="background:var(--format);border-radius:2px"></span>marks on protected names or invented text</span><span class="it">letter = class</span>';
})();

// ---- figure 3: fidelity by page size
(function () {
  const svg = document.getElementById('f3'); const PH = 150;
  const kb = D.pagekb, xs = {small: 0, medium: .5, large: 1};
  panels(svg, PH, (v, x0, y0, pw, ph, first) => {
    const y = f => y0 + ph - f * ph, x = p => x0 + 14 + xs[p] * (pw - 28);
    [0, .5, 1].forEach(f => { svg.appendChild(el('line', {x1: x0, y1: y(f), x2: x0 + pw, y2: y(f), class: 'gl'})); if (first) svg.appendChild(txt(f.toFixed(1), {x: x0 - 6, y: y(f) + 3.5, class: 'tk', 'text-anchor': 'end'})); });
    PAGES.forEach(p => svg.appendChild(txt(`${kb[p]} KB`, {x: x(p), y: y0 + ph + 13, class: 'tk', 'text-anchor': 'middle'})));
    svg.appendChild(el('rect', {x: x0, y: y0, width: pw, height: ph, fill: 'none', class: 'ax'}));
    D.models.forEach(m => {
      const pts = PAGES.map(p => { const rs = D.runs.filter(r => r.v === v && r.model === m && r.page === p); return rs.length ? [x(p), rs] : null; }).filter(Boolean);
      const line = pts.map(([px, rs]) => [px, y(Math.max(...rs.map(r => r.fidelity ?? 0)))]);
      if (line.length > 1) svg.appendChild(el('polyline', {points: line.map(q => q.join(',')).join(' '), class: 'trail', stroke: MCOL[m]}));
      pts.forEach(([px, rs]) => rs.forEach(r => { const yy = y(r.fidelity ?? 0); const mk = mark(svg, SHAPE[r.page], px, yy, 4.5, MCOL[m]); const t = el('title'); t.textContent = tip(r); mk.appendChild(t);
        if (r.cls !== 'correct') svg.appendChild(el('circle', {cx: px, cy: yy, r: 8.5, class: 'ring'})); }));
    });
  });
  document.getElementById('lg3').innerHTML = modelLegend() + ringLegend;
})();

// ---- figure 4: cost per cell
(function () {
  const svg = document.getElementById('f4');
  const W = 980, L = 190, T = 30, RH = 26; const rows = []; D.variants.forEach(v => PAGES.forEach(p => rows.push([v, p])));
  const H = T + rows.length * RH + 30; svg.setAttribute('viewBox', `0 0 ${W} ${H}`);
  const xw = W - L - 30;
  [0.001, 0.003, 0.01, 0.03, 0.1, 0.3, 1, 3].forEach(c => { const x = logx(c, L, xw); svg.appendChild(el('line', {x1: x, y1: T - 6, x2: x, y2: H - 24, class: 'gl'})); svg.appendChild(txt(money(c).replace(/0+$/, '').replace(/\.$/, ''), {x, y: T - 12, class: 'tk', 'text-anchor': 'middle'})); });
  rows.forEach(([v, p], i) => {
    const y = T + i * RH + RH / 2;
    svg.appendChild(el('line', {x1: L, y1: y, x2: W - 30, y2: y, class: p === 'small' ? 'ax' : 'gl'}));
    if (p === 'small') svg.appendChild(txt(v, {x: 14, y: y + 4, class: 'fct'}));
    if (p === 'medium') svg.appendChild(txt(D.vname[v], {x: 14, y: y + 4, class: 'fcts'}));
    svg.appendChild(txt(p, {x: L - 8, y: y + 3.5, class: 'tk', 'text-anchor': 'end'}));
    D.runs.filter(r => r.v === v && r.page === p).forEach(r => {
      const x = logx(r.cost, L, xw);
      const d = el('circle', {cx: x, cy: y, r: 8, fill: MCOL[r.model], class: 'mk'}); const t = el('title'); t.textContent = tip(r); d.appendChild(t); svg.appendChild(d);
      svg.appendChild(txt(LET[r.cls], {x, y: y + 3.5, 'text-anchor': 'middle', style: 'font-family:var(--mono);font-size:9px;font-weight:500;fill:#fff;pointer-events:none'}));
    });
  });
  document.getElementById('lg4').innerHTML = modelLegend() + '<span class="it">C correct · D degraded · S silent · F format · L loud</span>';
})();

// ---- table
const tb = document.querySelector('#tbl tbody'); const ord = {small: 0, medium: 1, large: 2};
[...D.runs].sort((a, b) => a.v.localeCompare(b.v) || ord[a.page] - ord[b.page] || D.models.indexOf(a.model) - D.models.indexOf(b.model)).forEach(r => {
  const tr = document.createElement('tr');
  const c = (v, inv) => v == null ? '<td>—</td>' : `<td class="${(inv ? v > 0.25 : v < 0.75) ? 'low' : ''}">${v.toFixed(3)}</td>`;
  tr.innerHTML = `<td>${r.v}</td><td>${r.page}</td><td><span class="sw" style="background:${MCOL[r.model]};margin-right:6px"></span>${r.model}</td><td><span class="chip ${r.cls}">${LET[r.cls]}</span></td>` +
    `<td>${money(r.cost)}</td><td>${r.wall ? Math.round(r.wall) + 's' : '—'}</td><td>${r.calls}</td><td>${r.tin.toLocaleString()}</td><td>${r.tout.toLocaleString()}</td>` +
    `<td>${r.glyphs ?? '—'}</td><td>${r.leaks ?? '—'}</td><td>${r.drupal ?? '—'}</td>` + c(r.recall) + c(r.precision) + c(r.subject) + c(r.fidelity) + c(r.fabrication, true);
  tb.appendChild(tr);
});
</script>
</body>
</html>
'''
html = html.replace('__FONTS__', FONTS).replace('__CSS__', CSS).replace('__DATA__', json.dumps(D, separators=(',', ':'))).replace('__N__', str(D['n']))
open(OUT, 'w', encoding='utf-8').write(html)
print(f'wrote {os.path.relpath(OUT, HERE)}: {D["n"]} runs, {os.path.getsize(OUT)//1024} KB')
