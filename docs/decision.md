# Decision: Copydesk and Plain

**Status:** all three questions answered by Brent on 2026-10-26.

1. **Merge.** Plain's code is the base.
2. **Pricing:** Free plus $79 Pro. Plain 1.x was never sold, so there are no $39 buyers to carry over.
3. **Name: rename to Copydesk.** No live site runs Plain, and the repo is private and not on Packagist, so no upgrade step is needed.

## The rename

Because nobody has installed Plain, the merged product starts as **Copydesk 1.0**, not Plain 2.0. No upgrade code, no old names kept for compatibility.

| Today (Plain) | After |
|---|---|
| GitHub `bpmore/statamic-plain` | `bpmore/statamic-copydesk` (renamed on GitHub, so history and the old link's redirect are kept) |
| Package `bpmore/statamic-plain` | `bpmore/statamic-copydesk` |
| Namespace `Bpmore\Plain\` | `Bpmore\Copydesk\` |
| Config `config/statamic-plain.php`, tag `statamic-plain-config` | `config/copydesk.php`, tag `copydesk-config` (CONVENTIONS' `config/<slug>.php`) |
| Settings file `resources/addons/statamic-plain.yaml` | `resources/addons/copydesk.yaml` (Statamic names it after the slug, now `copydesk`) |
| Storage `storage/statamic/plain/` | `storage/statamic/copydesk/` |
| Command `plain:check` | `copydesk:scan`, as the Copydesk spec names it |
| Tag `{{ plain:score }}` | `{{ copydesk:score }}` |
| Permissions `view readability`, `manage jargon dictionary`, `configure readability`; utility `access plain utility` | Kept for now, under a "Copydesk" group; utility becomes `access copydesk utility`. Merged with the Copydesk spec's list in Phase 1 |
| Error key `plain`, CP route `plain/check` | `copydesk`, `copydesk/check` (A11y Gate plan updated to match) |
| Env `PLAIN_ENABLED` | `COPYDESK_ENABLED` |
| Utility handle `plain`, view namespace `plain::` | `copydesk` |

Kept as is: `readability-core` and its namespace, the `no-readability` HTML class (A11y Report reads the same one), the Site Weather key `readability`.

Other repos to touch:

- **A11y Report:** `src/Support/Companion.php` checks for `bpmore/statamic-plain` by name and its overview sentence says "Plain." Its README, CHANGELOG, config comments and `docs/DECISIONS.md` mention Plain too. Without the update, the companion notice quietly stops showing.
- **Plain's own docs:** README, CHANGELOG, SPEC and PROGRESS describe Plain. The README gets rewritten for Copydesk; the old SPEC and PROGRESS move to `docs/history/`.
**Date:** 2026-10-26
**Recommendation:** **A. Merge.** Copydesk becomes Plain 2.0.

## What was read

- `bpmore/statamic-plain` at `872b700` (2026-09-15), release 1.0.1. Every source file, its SPEC, README, CHANGELOG, config and the relevant parts of PROGRESS.
- `bpmore/readability-core` at `5e22959` (2026-09-14). The README, the rule contract (`Rule`, `ConfiguredRule`, `RuleSet`, `Finding`, `Location`, `Severity`) and the file list. Its code is identical to the copy inside Plain at `packages/readability-core`; only `LICENSE.md` differs.
- `bpmore/statamic-site-weather`: the `WeatherContributor` contract and `Reading`.
- `statamic-a11y-gate` (local copy, `e7614af`): how its checks are listed.

## What Plain already does

| Area | Plain 1.0 today |
|---|---|
| Scoring | Four formulas (Flesch-Kincaid, Gunning Fog, SMOG, Coleman-Liau) from `readability-core`. Shown as a band ("Grade 9 to 10") from their median, never a decimal. Whole page and per section. Reading ease is computed but not shown. |
| What is read | Bard (node tree), markdown (rendered by Statamic), textarea and text fields, chosen per blueprint in config. Code, quotes, references, addresses, tables of figures, ignored Bard sets and HTML classes are left out. Names are left out for grading only. English only, with a sentence saying so. |
| Rules | Eight, all in `readability-core`: long sentence, very long sentence, long paragraph, passive voice (as a rate), unexpanded acronym, jargon (dictionary), nominalization, filler. Severities: error, warning, tip. |
| Dictionary | Starter list from plainlanguage.gov, plus a site's own words per site, edited on the addon settings screen, saved in `resources/addons/statamic-plain.yaml`, import and export as JSON. |
| Editor UI | A sidebar panel added to every entry form with a prose field. The author presses a button; the server grades the unsaved form and lists findings with the words marked in their sentence. **No live underlines in Bard.** Offsets are already turned from bytes into characters on the server. |
| Storage | Nothing stored on entries. Site scans kept as files, last 90 per site, in `storage/statamic/plain/scans/`. |
| Gate | Off by default, per collection. Blocks a publish (never a draft) only on rules named in `block_on`: very long sentence, and jargon terms with no suggested replacement. **Never on a grade, and it cannot be set to.** Fails open: if the check crashes, the save goes through and the log says why. No override permission. |
| Site report | Tools, Readability: every published page graded, hardest first, spread against target, median per collection, most common jargon and acronyms. Runs inside the web request, cached one hour. **Not queued.** |
| Command | `plain:check {--site} {--collection} {--export=csv} {--fail-above} {--fail-on-errors}`. |
| Other | Dashboard widget, `{{ plain:score }}` tag, Site Weather band with key `readability`, three permissions. |
| Build | No npm and no Vite. The panel is a plain script that uses Statamic's runtime Vue. |
| Price | $39, one edition. No free tier. |

## What Copydesk adds

- Live underlines in Bard as the editor types (TipTap decorations), with a popover, suggestion buttons and "Ignore here."
- Rule packs in YAML with six rule types (`word_list`, `term`, `pattern`, `punctuation`, `casing`, `sentence`), scopes, exceptions, packs that extend other packs.
- Four bundled packs, written from scratch.
- "Ignore here" fingerprints stored on the entry.
- A gate that blocks on any rule marked `error`, plus an override permission with a logged reason.
- A queued site scan in chunks of 50, with CSV.
- A rule editor in the control panel, with a test box.
- Free and Pro editions. Pro adds AI rewrite, report emails and a Site Weather signal.

About half of Copydesk's v1 list is already in Plain: panel, scoring, gate, dictionary, site report, command, CSV, Site Weather band, permissions, field reading for Bard, markdown, textarea and text.

## Why merge

1. **Same buyer, same screen.** Both are for health systems and public bodies, both live in the entry sidebar, both gate publishing. Layering means a site with both has two sidebar panels, two publish gates, two word lists, two report pages, two commands and two sets of permissions for one job.
2. **Layer, as the spec words it, breaks the conventions.** Option B says Copydesk "reuses Plain's scoring service." That service (`Bpmore\Plain\Analysis\Analyzer`) lives in Plain, not in the engine. Using it makes Plain a required install, and CONVENTIONS forbids one bpmore addon from requiring another. The only clean layer is to depend on `readability-core` alone and rebuild Plain's Statamic glue (field reading, blueprint lookup, the check endpoint) a second time.
3. **The engine is already built to take new rules.** `RuleSet::fromConfig()` accepts a custom list of rule types and `RuleSet::with()` adds rules. Copydesk's rule types can be written as `Rule` classes that read a `Document` and return `Finding`s with `Location`s. Nothing in the engine has to be rewritten.
4. **Site Weather only takes one band per key.** The first contributor to claim a key wins. Plain already reports `readability`. A separate Copydesk "percent of pages at target" signal would repeat it. Merged, there is one band.
5. The spec's own test: "Recommended if Plain already has the editor UI and scoring." It has both. It lacks live underlines, which Copydesk adds either way.

## What merging costs

- **Price and editions change for a product that is already out.** Plain is $39, one edition. Copydesk is Free plus $79 Pro, and Free includes the readability score. That gives away what Plain 1.0 buyers paid for. See question 1.
- **The name.** Packagist name, slug, permission names and config file are all `statamic-plain`. Merging keeps them unless you choose a rename (question 2).
- **Plain gains a build step.** Live underlines in Bard need a Bard extension. The plain-script approach may not stretch that far, and CONVENTIONS asks for Vite like Lifecycle anyway. Unchecked until Phase 1.
- **Two gate rules have to become one.** Plain blocks on rules named in `block_on`. Copydesk blocks on any rule marked `error`. Proposed: block on `error` severity, and keep Plain's promise that a grade never blocks. Plain's `very_long_sentence` is already `error` by default, so it would keep blocking when the gate is on.
- **Severity names differ.** Copydesk says `info`, the engine says `tip`. Proposed: accept `info` as another word for `tip` in the engine, so both YAML files work.
- **Plain stores nothing on entries.** Copydesk's ignores need a hidden `copydesk_ignores` field. That is a new kind of data for Plain. It is the editor's choice, not a computed score, so it fits, but the README has to say so.
- **The dictionary and `word_list` overlap.** Proposed: Plain's dictionary becomes the site's `word_list` pack, so a 1.x site keeps its words with no export or import.
- **The site scan has to move to a queue.** Plain's scan runs in the web request. Copydesk wants chunks of 50 in the background.
- **Plain's docs use em dashes.** The Plain README and the engine README have them. CONVENTIONS' done test (grep for U+2014) would fail until they are rewritten.

## Where the code would live

- The work happens in `bpmore/statamic-plain`, as 2.0.
- New rule types go in `readability-core` as plain PHP `Rule` classes. Reading YAML stays in the addon, so the engine still needs nothing at runtime.
- This folder (`statamic-copydesk`) keeps the spec and this file. No code goes here.

## Not settled by this file

- **A11y Gate has no place to plug in.** Its checks are a fixed list in `CheckPack::CHECKS` and they read rendered HTML, not fields. CONVENTIONS says to propose an extension point in that repo instead of a second gate. Plain 1.0 already ships its own gate. This affects either option. Plan: `docs/a11y-gate-plan.md`.
- **The number shown.** Copydesk's spec says "Flesch-Kincaid grade level and Flesch reading ease." Plain shows a band from four formulas, on purpose. Proposed: keep the band as the headline and show Flesch-Kincaid and reading ease in the details. Acceptance test 5 passes either way, since both come from `readability-core`.

## Questions for Brent

1. **Pricing.** Plain 2.0 as Free plus $79 Pro (Copydesk's plan), with existing $39 buyers getting Pro? Or something else?
2. **Name.** Keep "Plain," or rename the product to "Copydesk" at 2.0?
3. **The decision itself.** Merge (recommended) or Layer?
