"""Shared head (fonts + CSS) for the generated v2 pages."""
FONTS = '''<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700&family=IBM+Plex+Mono:wght@400;500&family=Source+Serif+4:opsz,wght@8..60,400;8..60,600&display=swap">
'''
CSS = r'''<style>
:root{
  --ink:#111820; --paper:#F5F6F8; --surf:#FFFFFF; --rule:#D8DCE2; --muted:#5A6470; --faint:#9AA3AE; --grid:#E9ECF0;
  --accent:#1B6FA8; --signal:#B0531D;
  --ok:#2a78d6; --silent:#d03b3b; --format:#eda100; --loud:#898781;
  --m1:#eb6834; --m2:#2a78d6; --m3:#1baf7a; --m4:#eda100;
  --sans:"Bricolage Grotesque",system-ui,-apple-system,"Segoe UI",sans-serif;
  --serif:"Source Serif 4",Georgia,"Times New Roman",serif;
  --mono:"IBM Plex Mono",ui-monospace,"SF Mono",Menlo,monospace;
}
@media (prefers-color-scheme:dark){:root:not([data-theme="light"]){
  --ink:#E8EBEF; --paper:#0F1318; --surf:#171C23; --rule:#2C333C; --muted:#98A2AE; --faint:#6D7783; --grid:#222932;
  --accent:#5AA9DC; --signal:#E08A47;
  --ok:#3987e5; --silent:#d03b3b; --format:#c98500; --loud:#898781;
  --m1:#d95926; --m2:#3987e5; --m3:#199e70; --m4:#c98500;
}}
:root[data-theme="dark"]{
  --ink:#E8EBEF; --paper:#0F1318; --surf:#171C23; --rule:#2C333C; --muted:#98A2AE; --faint:#6D7783; --grid:#222932;
  --accent:#5AA9DC; --signal:#E08A47;
  --ok:#3987e5; --silent:#d03b3b; --format:#c98500; --loud:#898781;
  --m1:#d95926; --m2:#3987e5; --m3:#199e70; --m4:#c98500;
}
*{box-sizing:border-box}
body{margin:0;background:var(--paper);color:var(--ink);font-family:var(--serif);line-height:1.6;-webkit-font-smoothing:antialiased}
.wrap{max-width:1040px;margin:0 auto;padding:0 24px}
header{padding:56px 0 8px;border-bottom:1px solid var(--rule);margin-bottom:32px}
.eyebrow{font-family:var(--mono);font-size:.72rem;letter-spacing:.13em;text-transform:uppercase;color:var(--signal);margin:0 0 .6em}
h1{font-family:var(--sans);font-weight:700;font-size:clamp(2rem,4.4vw,3rem);line-height:1.06;margin:0 0 .35em;text-wrap:balance;letter-spacing:-.02em}
.stand{font-size:1.08rem;color:var(--muted);max-width:66ch;margin:0 0 2rem}
h2{font-family:var(--sans);font-weight:700;font-size:1.35rem;margin:0 0 .5em;letter-spacing:-.01em}
h3{font-family:var(--sans);font-size:1rem;margin:0 0 .3em}
p{max-width:70ch}
a{color:var(--accent)}
section{padding:34px 0;border-bottom:1px solid var(--rule)}
section:last-of-type{border-bottom:0}
.tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin:18px 0 6px}
.tile{background:var(--surf);border:1px solid var(--rule);border-radius:10px;padding:14px 16px}
.tile .n{font-family:var(--sans);font-weight:700;font-size:2rem;letter-spacing:-.02em;line-height:1.1;display:flex;align-items:center;gap:10px}
.tile .l{font-family:var(--mono);font-size:.68rem;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-top:4px}
.bar{display:flex;flex-wrap:wrap;gap:14px;align-items:center;justify-content:space-between;background:var(--surf);border:1px solid var(--rule);border-radius:10px;padding:12px 14px;margin:14px 0 6px}
.seg{display:inline-flex;border:1px solid var(--rule);border-radius:8px;overflow:hidden}
.seg button{font-family:var(--mono);font-size:.78rem;letter-spacing:.04em;padding:7px 14px;border:0;background:transparent;color:var(--muted);cursor:pointer;border-right:1px solid var(--rule)}
.seg button:last-child{border-right:0}
.seg button[aria-pressed="true"]{background:var(--accent);color:#fff}
.lbl{font-family:var(--mono);font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--muted)}
.chk{font-family:var(--mono);font-size:.76rem;display:inline-flex;align-items:center;gap:6px;margin-right:10px;cursor:pointer}
.chk input{accent-color:var(--accent)}
.figbox{background:var(--surf);border:1px solid var(--rule);border-radius:10px;padding:8px 0 10px;margin:14px 0;overflow-x:auto}
.figcap{font-family:var(--mono);font-size:.72rem;color:var(--muted);padding:10px 16px 4px;border-top:1px solid var(--rule);margin-top:8px}
svg{display:block;width:100%;height:auto;min-width:660px}
/* class chips: letter + fill/outline; hue is secondary */
.chip{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:5px;font-family:var(--mono);font-size:.72rem;font-weight:500;margin:1px 2px;border:1.5px solid transparent;color:#fff}
.chip.correct{background:var(--ok);border-color:var(--ok)}
.chip.degraded{background:transparent;border-color:var(--ok);color:var(--ok)}
.chip.silent{background:var(--silent);border-color:var(--silent)}
.chip.format{background:var(--format);border-color:var(--format);color:#111}
.chip.loud{background:transparent;border-color:var(--loud);color:var(--loud);border-style:dashed}
.chip.none{background:transparent;border-color:transparent;color:var(--faint)}
.legend{display:flex;flex-wrap:wrap;gap:6px 18px;padding:10px 16px 2px;font-family:var(--mono);font-size:.74rem;color:var(--muted);align-items:center}
.legend .it{display:inline-flex;align-items:center;gap:7px}
.sw{display:inline-block;width:10px;height:10px;border-radius:50%}
.mx{width:100%;border-collapse:collapse;font-family:var(--mono);font-size:.8rem;min-width:660px}
.mx th{font-weight:500;color:var(--muted);font-size:.68rem;letter-spacing:.09em;text-transform:uppercase;padding:8px 10px;border-bottom:1px solid var(--rule);text-align:left}
.mx td{padding:5px 10px;border-bottom:1px solid var(--grid);vertical-align:middle;white-space:nowrap}
.mx td.v{font-family:var(--sans);font-weight:700}
.mx td.vn{color:var(--muted);font-size:.74rem}
.mx tr.grp td{border-top:1px solid var(--rule)}
.mx td.pg{color:var(--muted)}
table.tb{width:100%;border-collapse:collapse;font-family:var(--mono);font-size:.78rem;font-variant-numeric:tabular-nums;min-width:760px}
.tb th{text-align:right;font-weight:500;color:var(--muted);font-size:.66rem;letter-spacing:.08em;text-transform:uppercase;padding:7px 8px;border-bottom:1px solid var(--rule);white-space:nowrap}
.tb th:nth-child(-n+4),.tb td:nth-child(-n+4){text-align:left}
.tb td{padding:5px 8px;border-bottom:1px solid var(--grid);text-align:right;white-space:nowrap}
.tb td.low{color:var(--silent);font-weight:500}
.note{border:1px solid var(--rule);border-left:3px solid var(--signal);background:var(--surf);border-radius:8px;padding:15px 17px;margin:18px 0}
.note p{font-size:.93rem;color:var(--muted);margin:.4rem 0 0}
code{font-family:var(--mono);font-size:.86em;background:rgba(27,111,168,.08);padding:.1em .35em;border-radius:3px}
.ax{stroke:var(--rule);stroke-width:1.2}
.gl{stroke:var(--grid);stroke-width:1}
.tk{font-family:var(--mono);font-size:10px;fill:var(--muted)}
.axl{font-family:var(--sans);font-size:12px;font-weight:700;fill:var(--ink)}
.axsub{font-family:var(--mono);font-size:9px;fill:var(--muted);letter-spacing:.06em}
.line{fill:none;stroke-width:2;stroke-linejoin:round}
.line.dim{opacity:.18}
.ptlbl{font-family:var(--mono);font-size:10px;font-weight:500;fill:var(--ink)}
.dot{stroke:var(--surf);stroke-width:2}
.rowl{font-family:var(--sans);font-size:12px;font-weight:700;fill:var(--ink)}
.rows{font-family:var(--mono);font-size:9.5px;fill:var(--muted)}
@media (prefers-reduced-motion:reduce){*{transition:none!important}}
</style>
'''
