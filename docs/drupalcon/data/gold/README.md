# Gold documents — rubric v2

The reference each benchmark output is graded against in report v2
(see [../../ideas/report-v2-rubric.md](../../ideas/report-v2-rubric.md)). One set per page.

| File | What |
|---|---|
| `<page>.md` | The **article body** of the page, canonicalised: link targets and images dropped, citation markers and `[edit]` links removed, whitespace collapsed. Built from the B1 control conversion by `build_gold.py`. |
| `<page>.targets.json` | Every competitor mention in the gold body, with line and column. These are the redactions a correct run must place. |
| `<page>.protected.json` | Every mention of a name that must **not** be redacted (`Drupal` and other proper nouns present in the body). |
| `build_gold.py` | Reproduces everything above from `scratchpad/bench/results/outputs/bench_1_reference__<page>__r10__1788101327.md`. The chrome removed is stated as explicit line ranges with a reason, so the choices are reviewable. |

## Why not the B1 control?

The B1 conversion is the whole page. The small control is mostly Drupal.org menus, the
medium control carries IBM's header, newsletter and product promos, and the large control
is Wikipedia navigation, an infobox, and 380 lines of references. A model that drops that
chrome and keeps the article was penalised by v1's retention metric. The gold body is
what a reader would call "the document".

## Counts

| Page | Gold bytes | Control bytes | Headings | Target mentions | Drupal mentions | Other protected |
|---|---|---|---|---|---|---|
| small | 3,212 | 16,240 | 7 | **0** | 25 | — |
| medium | 8,715 | 14,497 | 9 | **30** (WordPress) | 38 | IBM 1, PHP 3, MySQL 1, Linux 1, Apache 1 |
| large | 20,583 | 101,790 | 19 | **1** (Backdrop) | 154 | PHP 8, Twig 5, Symfony 2, Microsoft 1 |

Two corrections to the v1 numbers follow from this:

- **Medium has 30 targets, not 36.** The other six are the browser title
  `Drupal versus WordPress | IBM` on line 1 of the control, `Drupal%20WordPress` in two
  share-link titles, and `drupal-wordpress` in three share-link URLs. None is prose. v1's
  case-insensitive leak counter saw all six, so a run with 30 glyphs and 0 leaks was
  already complete, and a run with 33–36 was redacting URLs.
- **Large has 1 target, not 5.** Of the control's five hits, two were the same
  "Backdrop CMS" link (text plus title attribute) and three sit inside References: Mambo
  in a citation title, WordPress and Joomla in a cited article title. The Wikipedia
  article body mentions one competitor. A run placing 4–8 glyphs on the large page is
  redacting citations, and whether that counts is a rubric decision, not a fact.

## Decisions taken here (revisit if the rubric disagrees)

1. **References, Further reading and External links are chrome** on the large page. They
   are citations, not the article. A run that keeps them is not penalised (output
   sentences outside the gold are reported as "out of gold", not as fabrication); a run
   that drops them is not penalised either.
2. **The flattened release-history table** on the large page (control lines 410–434,
   rendered by the converter as one unreadable line) is dropped. No output can match it
   sentence-for-sentence and it would only add noise to fidelity.
3. **Promo blocks inside the article** (IBM newsletter, AI Academy, tweet-this card) are
   chrome even though they sit between body paragraphs.
4. **Target surface forms**: a mention is `\bName[\w.-]*`, case-sensitive, so
   `WordPress.com` and `Drupal's` each count once against their base name, and
   `drupal-wordpress` inside a URL does not count at all.
5. **The small page has no targets by design.** It is the false-positive control; any glyph
   placed there is wrong.

## Not yet done

- A by-eye read of all three `<page>.md` files against the live pages. The rules were
  checked line-by-line against the control, not against the browser rendering.
- ~~The rubric's fidelity scorer~~ — done: `../score_v2.py`, results in `../RUNS_V2.md`.
