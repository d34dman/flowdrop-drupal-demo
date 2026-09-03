# Published pages

Static site deployed to GitHub Pages by `.github/workflows/pages.yml` on every push to
`main` that touches this folder: <https://d34dman.github.io/flowdrop-drupal-demo/>

```
site/
  index.html                 generic list of studies. Add a <li> per new study.
  <study>/index.html         one page per study, linking its slides and pages
  <study>/slides/*.html      decks
  <study>/artifacts/*.html   interactive pages
```

## Studies

| Folder | Study |
|---|---|
| `redaction-benchmark/` | Seven ways to redact a page. 20-minute tech all-hands deck (4 Sep 2026) plus the nine interactive pages built during the research, exported from claude.ai and wrapped as standalone HTML. |

All pages are self-contained apart from Google Fonts. Open any file directly in a
browser, or serve the folder with `python3 -m http.server`.

The `artifacts/` files are exports; the claude.ai originals listed in
[../ARTIFACTS.md](../ARTIFACTS.md) are private to the author. Edit the copies here
if something needs to change.
