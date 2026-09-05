# Published pages

Static site deployed to GitHub Pages by `.github/workflows/pages.yml` on every push to
`main` that touches this folder: <https://d34dman.github.io/flowdrop-drupal-demo/>

```
site/
  index.html                 generic list of studies. Add a <li> per new study.
  <study>/index.html         one page per study, linking its slides and pages
  <study>/slides/*.html      decks
  <study>/artifacts/*.html   current interactive pages (generated from data/, see below)
  <study>/archive/v1/*.html  the v1-rubric pages, kept as published; artifacts/ holds redirect stubs at their old URLs
```

## Studies

| Folder | Study |
|---|---|
| `redaction-benchmark/` | Seven ways to redact a page. 20-minute tech all-hands deck (4 Sep 2026); the current pages under `artifacts/` are generated from `docs/drupalcon/data/runs_v2.csv` by `data/build_site_v2.py` (scorecard) and `data/build_matrix_v2.py` (model matrix), so regenerate rather than edit; the nine v1 pages exported from claude.ai live under `archive/v1/`. |

All pages are self-contained apart from Google Fonts. Open any file directly in a
browser, or serve the folder with `python3 -m http.server`.

The `archive/v1/` files are exports; the claude.ai originals listed in
[../ARTIFACTS.md](../ARTIFACTS.md) are private to the author. They are frozen.
