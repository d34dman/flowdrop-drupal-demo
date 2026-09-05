#!/usr/bin/env python3
"""Generates site/redaction-benchmark/artifacts/rubric-v2-scorecard.html from runs_v2.csv.

The page is static HTML with the data inlined as JSON; regenerate it after any rescoring
rather than editing it by hand. Colour: class hues validated with the dataviz palette
checker (light #2a78d6/#d03b3b/#eda100, dark #3987e5/#d03b3b/#c98500); degraded and loud
are outlined chips so identity never rests on hue; every chip carries its letter.
"""
import json, os

HERE = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.join(HERE, '..', 'site', 'redaction-benchmark', 'artifacts', 'rubric-v2-scorecard.html')
from v2data import load
from v2style import CSS
D = load()
runs, pausedj = D['runs'], D['paused']

html = r'''<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Rubric v2 Scorecard</title>
<style>body{margin:0}img{max-width:100%}[hidden]{display:none!important}</style>
</head>
<body>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700&family=IBM+Plex+Mono:wght@400;500&family=Source+Serif+4:opsz,wght@8..60,400;8..60,600&display=swap">
__CSS__

<header class="wrap">
  <p class="eyebrow">▌▌▌▌ &nbsp;FlowDrop redaction benchmark &nbsp;·&nbsp; rubric v2</p>
  <h1>Every run, graded against<br>a document decided in advance.</h1>
  <p class="stand">The v1 pages counted bytes kept and marks placed. Both were fooled by the same run.
  This page grades the same __N__ runs against hand-cleaned gold documents on five axes and sorts each
  into one of five outcomes. Nothing here was rerun; the raw data is identical to v1 and the conclusions
  are re-derived. Method and tables:
  <a href="https://github.com/d34dman/flowdrop-drupal-demo/tree/main/docs/drupalcon/v2">docs/drupalcon/v2</a>.</p>
</header>

<section><div class="wrap">
  <div class="tiles" id="tiles"></div>
  <p style="font-size:.93rem;color:var(--muted)">Silent means the pipeline reported <code>completed</code>, the output is
  Markdown about the right document, and at least one axis is below 0.75. Format is HTML handed back. Loud is nothing
  delivered. Degraded passed every gate and missed a threshold, usually two or three of thirty WordPress mentions.</p>
</div></section>

<section><div class="wrap">
  <h2>Figure 1 · Outcome per cell</h2>
  <p>One chip per draw. Variant by page down the side, model across. A cell with several chips was run several
  times; a dot means the cell was never run. Read down the Opus column and across the B3 row first.</p>
  <div class="figbox">
    <table class="mx" id="mx"></table>
    <div class="legend" id="lg1"></div>
    <div class="figcap">Shadowed-prompt B7 draws, the dropped B5a arm and __NP__ runs that paused on a FlowDrop confirmation
    gate or a pending job are excluded (no answer exists to grade). Hover a chip for the run's axes.</div>
  </div>
  <div class="note"><h3>Where the seven silent failures are</h3>
    <p id="silentnote"></p></div>
</div></section>

<section><div class="wrap">
  <h2>Figure 2 · Six axes, one line per run</h2>
  <p>Cost and the five graded axes, oriented so that up is always better. Correct runs are the blue band along the
  top; anything that leaves the band is labelled. Fabrication is the hallucination axis: the share of output
  sentences found neither in the gold body nor anywhere on the page.</p>
  <div class="bar">
    <div style="display:flex;gap:12px;align-items:center"><span class="lbl">Page</span><div class="seg" id="pages"></div></div>
    <div id="models"></div>
    <div style="display:flex;gap:12px;align-items:center"><span class="lbl">Show</span><div class="seg" id="show"></div></div>
  </div>
  <div class="figbox">
    <svg id="pc" viewBox="0 0 960 400" role="img" aria-label="Parallel coordinates of cost, recall, precision, subject, fidelity and fabrication for each graded run"></svg>
    <div class="legend" id="lg2"></div>
    <div class="figcap">Format and loud failures have no graded axes and are not drawn; they are in the table below. The small
    page has no competitor to redact, so recall, precision and subject are 1.0 by definition unless a run invented
    text; the large page's one competitor-ish name (Backdrop, a Drupal fork) is neutral. Hover a line for the run.</div>
  </div>
</div></section>

<section><div class="wrap">
  <h2>Figure 3 · Cost per correct run</h2>
  <p>Cell spend divided by correct draws, all pages pooled, per variant and model. Spend includes the cell's
  failed and paused draws, because that money was spent getting the correct one. A hollow marker at the right
  edge is a cell that spent money and produced nothing correct.</p>
  <div class="figbox">
    <svg id="cpc" viewBox="0 0 960 420" role="img" aria-label="Dot plot of cost per correct run, one row per variant, one dot per model, log scale"></svg>
    <div class="legend" id="lg3"></div>
    <div class="figcap">Log scale. The label beside each dot is correct draws over launches. Model colour follows the
    other pages on this site.</div>
  </div>
</div></section>

<section><div class="wrap">
  <h2>Every graded run</h2>
  <div class="figbox" style="padding:0 0 4px">
    <table class="tb" id="tbl"><thead><tr>
      <th>Class</th><th>Variant</th><th>Page</th><th>Model</th><th>Recall</th><th>Precision</th><th>Subject</th>
      <th>Fidelity</th><th>Fabric.</th><th>Marks</th><th>Leaks</th><th>Calls</th><th>Cost</th></tr></thead>
      <tbody></tbody></table>
  </div>
  <p style="font-size:.85rem;color:var(--muted)">Values below 0.75 are marked. Sorted by variant, page, model; several rows for one cell are repeated draws.</p>
</div></section>

<section><div class="wrap">
  <h2>How to read it</h2>
  <div class="note"><h3>Why the v1 numbers were replaced</h3>
    <p>Retention divided by the whole page penalised a model for dropping IBM's cookie banner and could not see invented
    prose. Counting <code>▌▌▌▌</code> ranked the run that redacted "Drupal" 37 times first. Zero leaks on a page with
    no competitors was trivially true. v2 grades against a gold body per page, with every competitor mention and every
    protected name listed in advance, so recall, precision and subject preservation are all measurable, and fabrication
    exists at all.</p></div>
  <div class="note"><h3>What changed in the conclusions</h3>
    <p>v1's failure #4, three B5 draws at 51 / 71 / 95 % retention, is withdrawn: all three have fidelity 1.0 and differ
    only in how much Drupal.org menu they kept. The cheapest correct run on the small and medium pages is B3 on Haiku,
    not Sonnet 5. Two failures v1 could not see: an agent writing its own reasoning, six competitor names included,
    into a page that had none (B4 Sonnet 4.6, small), and every URL-tool variant leaving the title heading
    <code># Drupal versus WordPress</code> readable while redacting the body (B7, B8, B9, six draws on three models).</p></div>
  <div class="note"><h3>Caveats</h3>
    <p>Most cells are one draw; the matrix shows draws, not rates. Sentence matching is fuzzy, so a heavily paraphrased
    sentence counts as missing or invented. Thresholds: recall, precision, subject and fidelity ≥ 0.95, fabrication
    ≤ 0.05 for correct; every axis ≥ 0.75 for degraded. The scorer is deterministic and runs in two seconds; no LLM
    judge is involved. Total metered spend across all 203 runs in the ledger: $61.39.</p></div>
</div></section>

<script>
const D = __DATA__;
const NS = 'http://www.w3.org/2000/svg';
const PAGES = ['small', 'medium', 'large'];
const CLS = ['correct', 'degraded', 'silent', 'format', 'loud'];
const LET = {correct: 'C', degraded: 'D', silent: 'S', format: 'F', loud: 'L'};
const CCOL = {correct: 'var(--ok)', degraded: 'var(--ok)', silent: 'var(--silent)', format: 'var(--format)', loud: 'var(--loud)'};
const MCOL = {'Haiku 4.5': 'var(--m1)', 'Sonnet 4.6': 'var(--m2)', 'Sonnet 5': 'var(--m3)', 'Opus 5': 'var(--m4)'};
const el = (n, a = {}) => { const e = document.createElementNS(NS, n); for (const k in a) e.setAttribute(k, a[k]); return e; };
const txt = (s, a = {}) => { const t = el('text', a); t.textContent = s; return t; };
const f3 = v => v == null ? '—' : v.toFixed(3);
const money = v => '$' + (v === 0 ? '0' : v < 1 ? v.toFixed(2) : v.toFixed(2));
const tip = r => `${r.v} · ${r.page} · ${r.model} · ${r.cls}\nrecall ${f3(r.recall)} · precision ${f3(r.precision)} · subject ${f3(r.subject)}\nfidelity ${f3(r.fidelity)} · fabrication ${f3(r.fabrication)}\n${r.glyphs ?? 0} marks · ${r.leaks ?? 0} leaks · ${r.calls} calls · ${money(r.cost)}`;

// ---- tiles
const tiles = document.getElementById('tiles');
[['n', 'graded runs', null], ...CLS.map(c => [c, c, c])].forEach(([k, l, c]) => {
  const d = document.createElement('div'); d.className = 'tile';
  const n = k === 'n' ? D.n : (D.totals[k] || 0);
  d.innerHTML = `<div class="n">${c ? `<span class="chip ${c}">${LET[c]}</span>` : ''}${n}</div><div class="l">${l}</div>`;
  tiles.appendChild(d);
});

// ---- figure 1: matrix
const mx = document.getElementById('mx');
let h = '<thead><tr><th>Variant</th><th></th><th>Page</th>' + D.models.map(m => `<th>${m}</th>`).join('') + '</tr></thead><tbody>';
D.variants.forEach(v => PAGES.forEach((p, i) => {
  h += `<tr${i === 0 ? ' class="grp"' : ''}><td class="v">${i === 0 ? v : ''}</td><td class="vn">${i === 0 ? D.vname[v] : ''}</td><td class="pg">${p}</td>`;
  D.models.forEach(m => {
    const rs = D.runs.filter(r => r.v === v && r.page === p && r.model === m);
    h += '<td>' + (rs.length ? rs.map(r => `<span class="chip ${r.cls}" title="${tip(r).replace(/"/g, '&quot;')}">${LET[r.cls]}</span>`).join('') : '<span class="chip none">·</span>') + '</td>';
  });
  h += '</tr>';
}));
mx.innerHTML = h + '</tbody>';
document.getElementById('lg1').innerHTML = CLS.map(c => `<span class="it"><span class="chip ${c}">${LET[c]}</span>${c}</span>`).join('') + '<span class="it"><span class="chip none">·</span>not run</span>';
const sil = D.runs.filter(r => r.cls === 'silent');
document.getElementById('silentnote').textContent = sil.map(r => `${r.v} ${r.model} ${r.page}`).join(' · ') +
  '. Six are in variants that let the model decide what to do with the page; the seventh is B3 on Haiku truncating the large page at fidelity 0.08.';

// ---- figure 2: parallel coordinates
const AXES = [
  {key: 'cost', label: 'Cost', sub: 'USD, lower better', dir: 'low', fmt: v => money(v)},
  {key: 'recall', label: 'Recall', sub: 'targets redacted', dir: 'high', fmt: v => v.toFixed(2)},
  {key: 'precision', label: 'Precision', sub: 'marks on targets', dir: 'high', fmt: v => v.toFixed(2)},
  {key: 'subject', label: 'Subject', sub: 'protected names kept', dir: 'high', fmt: v => v.toFixed(2)},
  {key: 'fidelity', label: 'Fidelity', sub: 'gold sentences found', dir: 'high', fmt: v => v.toFixed(2)},
  {key: 'fabrication', label: 'Fabrication', sub: 'invented, lower better', dir: 'low', fmt: v => v.toFixed(2)},
];
let page = 'medium', show = 'all';
const modelOn = Object.fromEntries(D.models.map(m => [m, true]));
const M = {l: 80, r: 150, t: 40, b: 30}, W = 960, H = 400, w = W - M.l - M.r, hh = H - M.t - M.b;
const axX = i => M.l + i * (w / (AXES.length - 1));
function render2() {
  const svg = document.getElementById('pc'); svg.innerHTML = '';
  const rs = D.runs.filter(r => r.page === page && modelOn[r.model] && r.recall != null &&
    (show === 'all' || (show === 'correct' ? r.cls === 'correct' : r.cls !== 'correct')));
  const costHi = Math.max(0.01, ...D.runs.filter(r => r.page === page && r.recall != null).map(r => r.cost)) * 1.05;
  const dom = a => a.key === 'cost' ? [0, costHi] : [0, 1];
  const pos = (a, v) => { const [lo, hi] = dom(a), t = (v - lo) / (hi - lo); return M.t + hh - (a.dir === 'high' ? t : 1 - t) * hh; };
  AXES.forEach((a, i) => {
    const x = axX(i);
    svg.appendChild(el('line', {x1: x, y1: M.t, x2: x, y2: M.t + hh, class: 'ax'}));
    svg.appendChild(txt(a.label, {x, y: M.t - 20, class: 'axl', 'text-anchor': 'middle'}));
    svg.appendChild(txt(a.sub, {x, y: M.t - 8, class: 'axsub', 'text-anchor': 'middle'}));
    const [lo, hi] = dom(a);
    [0, .25, .5, .75, 1].forEach(f => { const v = lo + (hi - lo) * f, y = pos(a, v);
      svg.appendChild(el('line', {x1: x - 4, y1: y, x2: x + 4, y2: y, class: 'ax'}));
      svg.appendChild(txt(a.fmt(v), {x: x - 8, y: y + 3.5, class: 'tk', 'text-anchor': 'end'})); });
    if (a.key !== 'cost') { const y = pos(a, a.dir === 'high' ? .75 : .25);
      svg.appendChild(el('line', {x1: x - 14, y1: y, x2: x + 14, y2: y, class: 'ax', 'stroke-dasharray': '3 3'})); }
  });
  const order = [...rs].sort((a, b) => (a.cls === 'correct' ? 0 : 1) - (b.cls === 'correct' ? 0 : 1));
  const used = [];
  order.forEach(r => {
    const pts = AXES.map((a, i) => [axX(i), pos(a, r[a.key])]);
    const ln = el('polyline', {points: pts.map(p => p.join(',')).join(' '), class: 'line' + (show === 'all' && r.cls === 'correct' ? ' dim' : ''),
      stroke: CCOL[r.cls], 'stroke-dasharray': r.cls === 'degraded' ? '6 4' : 'none'});
    if (r.cls === 'correct' && show === 'all') ln.setAttribute('opacity', '.35');
    const t = el('title'); t.textContent = tip(r); ln.appendChild(t); svg.appendChild(ln);
    if (r.cls !== 'correct' || show === 'correct') {
      let y = pts[pts.length - 1][1];
      while (used.some(u => Math.abs(u - y) < 12)) y += 12;
      used.push(y);
      svg.appendChild(txt(`${r.v} ${r.model}`, {x: axX(AXES.length - 1) + 10, y: y + 3.5, class: 'ptlbl', fill: r.cls === 'degraded' ? 'var(--ok)' : CCOL[r.cls]}));
    }
  });
  if (!rs.length) svg.appendChild(txt('No graded runs match this filter.', {x: W / 2, y: H / 2, class: 'axl', 'text-anchor': 'middle'}));
  document.querySelectorAll('#pages button').forEach(b => b.setAttribute('aria-pressed', String(b.dataset.p === page)));
  document.querySelectorAll('#show button').forEach(b => b.setAttribute('aria-pressed', String(b.dataset.s === show)));
}
const pg = document.getElementById('pages');
PAGES.forEach(p => { const b = document.createElement('button'); b.dataset.p = p; b.textContent = p; b.onclick = () => { page = p; render2(); }; pg.appendChild(b); });
const sh = document.getElementById('show');
[['all', 'all'], ['correct', 'correct only'], ['fail', 'not correct']].forEach(([s, l]) => { const b = document.createElement('button'); b.dataset.s = s; b.textContent = l; b.onclick = () => { show = s; render2(); }; sh.appendChild(b); });
const mo = document.getElementById('models');
D.models.forEach(m => { const l = document.createElement('label'); l.className = 'chk';
  l.innerHTML = `<input type="checkbox" checked><span class="sw" style="background:${MCOL[m]}"></span>${m}`;
  l.querySelector('input').onchange = e => { modelOn[m] = e.target.checked; render2(); }; mo.appendChild(l); });
document.getElementById('lg2').innerHTML = [['correct', 'correct'], ['degraded', 'degraded (dashed)'], ['silent', 'silent failure']]
  .map(([c, l]) => `<span class="it"><span class="sw" style="background:${CCOL[c]};border-radius:2px;width:14px;height:3px"></span>${l}</span>`).join('') +
  '<span class="it">dashed tick on an axis = the 0.75 degraded floor</span>';
render2();

// ---- figure 3: cost per correct run
(function () {
  const svg = document.getElementById('cpc');
  const L = 200, R = 120, T = 36, RH = 56, W3 = 960;
  const rowsV = D.variants;
  const cells = [];
  rowsV.forEach(v => D.models.forEach(m => {
    const rs = D.runs.filter(r => r.v === v && r.model === m), ps = D.paused.filter(r => r.v === v && r.model === m);
    if (!rs.length) return;
    const spend = rs.reduce((s, r) => s + r.cost, 0) + ps.reduce((s, r) => s + r.cost, 0);
    const ok = rs.filter(r => r.cls === 'correct').length;
    cells.push({v, m, spend, ok, n: rs.length + ps.length, cpc: ok ? spend / ok : null});
  }));
  const vals = cells.filter(c => c.cpc).map(c => c.cpc), lo = 0.01, hi = Math.max(...vals) * 1.3;
  const x = v => L + (Math.log10(v) - Math.log10(lo)) / (Math.log10(hi) - Math.log10(lo)) * (W3 - L - R);
  const Hh = T + rowsV.length * RH + 20;
  svg.setAttribute('viewBox', `0 0 ${W3} ${Hh}`);
  [0.01, 0.03, 0.1, 0.3, 1, 3].filter(v => v <= hi).forEach(v => {
    svg.appendChild(el('line', {x1: x(v), y1: T - 6, x2: x(v), y2: Hh - 20, class: 'gl'}));
    svg.appendChild(txt(money(v), {x: x(v), y: T - 12, class: 'tk', 'text-anchor': 'middle'}));
  });
  svg.appendChild(txt('∞', {x: W3 - R + 40, y: T - 12, class: 'tk', 'text-anchor': 'middle'}));
  rowsV.forEach((v, i) => {
    const y = T + i * RH + RH / 2;
    svg.appendChild(el('line', {x1: L, y1: y, x2: W3 - R + 60, y2: y, class: 'gl'}));
    svg.appendChild(txt(v, {x: 14, y: y - 3, class: 'rowl'}));
    svg.appendChild(txt(D.vname[v], {x: 14, y: y + 11, class: 'rows'}));
    const cs = cells.filter(c => c.v === v);
    cs.forEach((c, k) => {
      const cx = c.cpc ? x(c.cpc) : W3 - R + 40, jitter = (k - (cs.length - 1) / 2) * 11;
      const d = el('circle', {cx, cy: y + jitter, r: 6, class: 'dot', fill: c.cpc ? MCOL[c.m] : 'none', stroke: c.cpc ? 'var(--surf)' : MCOL[c.m]});
      if (!c.cpc) d.setAttribute('stroke-width', '2');
      const t = el('title'); t.textContent = `${c.v} · ${c.m}\n${c.ok} correct of ${c.n} launches · spend ${money(c.spend)}` + (c.cpc ? `\n${money(c.cpc)} per correct run` : '\nno correct run');
      d.appendChild(t); svg.appendChild(d);
      svg.appendChild(txt(`${c.ok}/${c.n}`, {x: cx + 10, y: y + jitter + 3.5, class: 'tk'}));
    });
  });
  document.getElementById('lg3').innerHTML = D.models.map(m => `<span class="it"><span class="sw" style="background:${MCOL[m]}"></span>${m}</span>`).join('') +
    '<span class="it"><span class="sw" style="border:2px solid var(--muted);background:none"></span>no correct run</span>';
})();

// ---- table
const tb = document.querySelector('#tbl tbody');
const ord = {small: 0, medium: 1, large: 2};
[...D.runs].sort((a, b) => a.v.localeCompare(b.v) || ord[a.page] - ord[b.page] || D.models.indexOf(a.model) - D.models.indexOf(b.model)).forEach(r => {
  const tr = document.createElement('tr');
  const cell = (v, inv) => v == null ? '<td>—</td>' : `<td class="${(inv ? v > 0.25 : v < 0.75) ? 'low' : ''}">${v.toFixed(3)}</td>`;
  tr.innerHTML = `<td><span class="chip ${r.cls}">${LET[r.cls]}</span> ${r.cls}</td><td>${r.v}</td><td>${r.page}</td><td>${r.model}</td>` +
    cell(r.recall) + cell(r.precision) + cell(r.subject) + cell(r.fidelity) + cell(r.fabrication, true) +
    `<td>${r.glyphs ?? '—'}</td><td>${r.leaks ?? '—'}</td><td>${r.calls}</td><td>${money(r.cost)}</td>`;
  tb.appendChild(tr);
});
</script>
</body>
</html>
'''
html = html.replace('__CSS__', CSS).replace('__DATA__', json.dumps(D, separators=(',', ':'))).replace('__N__', str(len(runs))).replace('__NP__', str(len(pausedj)))
open(OUT, 'w', encoding='utf-8').write(html)
print(f'wrote {os.path.relpath(OUT, HERE)}: {len(runs)} graded runs, {len(pausedj)} paused, {os.path.getsize(OUT)//1024} KB')
