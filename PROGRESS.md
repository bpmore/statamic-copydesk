# PROGRESS — Plain

**Spec:** `SPEC.md` · **Packages:** `bpmore/readability-core` + `bpmore/statamic-plain` · **$39 standalone, bundled free in A11y Report**
**Rule:** do the next unfinished task only, then update this file, verify the build, and commit.

## Phase 1 — Core engine (no Statamic)
- [x] Scaffold `readability-core`, composer-installable and testable standalone
- [x] Text extraction + normalization: HTML/Bard → analyzable plain text
- [x] Exclusion pipeline: code blocks, blockquotes, references, proper names, addresses, ignore-classes
- [x] Sentence segmentation that survives "Dr. Smith went to St. Louis Inc." — evaluate a real segmenter before hand-rolling
- [ ] `SyllableCounter` interface with `davechild/textstatistics` as the first implementation
- [ ] Formula layer implemented in-house over the counter (Flesch-Kincaid, SMOG, Gunning Fog, Coleman-Liau)
- [ ] Unit tests against reference texts with published scores
- [ ] Report a grade **band**, never a decimal
- [ ] Locale guard: English only; disable with an explicit message otherwise

## Phase 2 — Rules
- [ ] One class per rule, individually testable
- [ ] Long sentence / very long sentence
- [ ] Long paragraph
- [ ] Passive voice — **document-level rate, not per-instance**
- [ ] Unexpanded acronyms
- [ ] Jargon / banned phrases with suggested replacements
- [ ] Nominalizations, hedging and filler (tips)
- [ ] Per-section scoring by heading, not just whole-document

## Phase 3 — Statamic addon
- [ ] Scaffold `statamic-plain`
- [ ] Publish-form panel: grade band vs target, per-section grades, inline highlighting
- [ ] Gate: opt-in per collection, **never blocks on grade alone**
- [ ] Jargon dictionary editor, per site, import/export, starter dictionary shipped
- [ ] Utility screen: distribution, worst offenders, acronym/jargon frequency
- [ ] Dashboard widget
- [ ] `{{ plain:score }}` Antlers tag
- [ ] `php please plain:check` with CSV export and CI exit code
- [ ] Permissions: view / manage dictionary / configure

## Phase 4 — A11y Report integration
- [ ] A11y Report depends on `readability-core`
- [ ] Readability as a scan dimension
- [ ] SC 3.1.5 as a **AAA row reported separately from the AA claim** — never a WCAG failure
- [ ] Both-installed notice explaining the division of surfaces

## Phase 5 — Ship
- [ ] README positioned on plain language / ISO 24495-1, not WCAG
- [ ] PHP floor 8.2
- [ ] Tag 1.0

## Notes
<!-- Record surprises, decisions and blockers here. If a task is wrong or blocked, write why and stop. -->

### 2026-09-11 — Phase 1, task 1: scaffold `readability-core`

- **Where it lives:** `packages/readability-core/` in this repo, as its own Composer
  package (`bpmore/readability-core`, PSR-4 `Bpmore\ReadabilityCore\` → `src/`) with
  its own `vendor/`, `phpunit.xml` and suite. There was no sibling directory for it
  and the working agreement commits to this repo, so a monorepo layout was the
  choice that keeps the history in one place. Consumers pull it in with a Composer
  path repository (the mechanism the host site already uses); if it should ever be
  its own repository, `git subtree split -P packages/readability-core` does it.
- **How to test it, until Phase 3:** `cd packages/readability-core && vendor/bin/pest`
  (and `vendor/bin/pint --test`). The root-level `vendor/bin/pest` in CLAUDE.md
  only exists once the addon is scaffolded at the root.
- **The arch test from CLAUDE.md is blind in this package's own suite.** Pest's
  `toUse()` (via `ta-tikoma/phpunit-architecture-test`) only records a reference
  to a class that `class_exists()`, and nothing from Statamic or Illuminate is
  installed here, by design. Verified: a class with `use Illuminate\Support\Str`
  left `not->toUse(['Statamic', 'Illuminate'])` green. `tests/ArchTest.php` keeps
  that rule (it comes alive in the addon's suite, where the framework is present)
  and adds a `PhpToken` scan of `src/` for `Statamic\` / `Illuminate\` that fails
  standalone — confirmed red with the leak, green without.
- **`src/` is empty** apart from `.gitkeep`. Nothing in the spec is foundational
  *and* outside a later task, and a placeholder class would only be deleted in
  task 2. The suite's four tests cover the scaffold's actual claims: the Composer
  mapping resolves to `src/`, and the boundary is enforced.
- **Dependencies:** `pestphp/pest ^3.8 || ^4.0` and `laravel/pint ^1.27` (no global
  pint on this machine), matching A11y Gate/Report. Resolved to Pest 4.7.8 locally
  on PHP 8.4; a `--prefer-lowest` dry run against a PHP 8.2 platform resolves
  cleanly (Pest 3.8.5), so the `^8.2` floor is real. CI at
  `.github/workflows/tests.yml` runs highest-on-8.4 and lowest-on-8.2.
- **Licence:** `"license": "proprietary"` in `composer.json` to match the sibling
  packages; no `LICENSE.md` yet. Which terms the engine ships under (A11y Gate's
  "free, not for reuse" text, or something more permissive since it is a shared
  dependency) is a decision for Phase 5, alongside the README positioning.
- `PROGRESS.md` header said the spec was `plain-build-spec.md`; it is `SPEC.md`.
  Corrected.

### 2026-09-11 — Phase 1, task 2: text extraction + normalization

- **Extraction produces structure, not a string.** `Text\Document` is an ordered
  list of `Block`s (kind, normalised text, heading level) each with an `Origin`
  (tag, ancestors, HTML classes, Bard set handles, Bard node path). Every later
  task needs it: exclusions drop blocks by what encloses them, per-section
  scoring groups blocks under headings, and inline highlighting needs the Bard
  path. `Document::text()` is the flat form for anything that only wants words.
- **One vocabulary.** The Bard extractor maps node types onto the HTML tag they
  render as (`listItem`→`li`, `codeBlock`→`pre`, `tableHeader`→`th`); a set is
  `set`. So `Block::isQuoted()` etc. ask one question whichever extractor ran.
- **Markdown is deliberately not a core concern.** The host renders it with its
  own parser and extensions and hands over HTML; a second parser here would only
  disagree with the first. The addon (Phase 3) does this.
- **Sets:** disabled sets are skipped whole; Bard fields nested in an enabled set
  are walked; a set's plain string fields are *not* read, because from here a
  caption is indistinguishable from a URL. The addon, which has the blueprint,
  hands those to `PlainTextExtractor` itself. Replicator-shaped values are walked
  and found empty, by design.
- **A newline in block text always means a hard break** (`<br>`, Bard `hardBreak`,
  or a line of `<pre>`). The first cut kept the source's own line wrapping as
  breaks; the messy-page fixture caught it. The extractors now fold source
  whitespace to spaces before the normaliser sees it.
- **Surprises:** (1) a fragment that is nothing but bare text came back empty,
  because the root frame was only flushed when a block opened after it — fixed by
  flushing at the end of the walk. (2) Pest 4 ships a global `fixture()` (path
  under `tests/Fixtures/`) that Pest 3.8 lacks; our helper is `readFixture()`.
  (3) `preg_replace` with `/u` returns null on invalid UTF-8, which would have
  silently emptied a block; `mb_scrub` first, with a test.
- **Manifest:** now requires `ext-dom`, `ext-libxml`, `ext-mbstring`; suggests
  `ext-intl` (NFC composition, skipped without it).
- **Left in the text on purpose, for later tasks:** inline `<code>`, footnote
  markers like `[1]`, an `<abbr>`'s text (its `title` expansion is dropped — if
  the acronym rule in Phase 2 wants it, extraction will need an inline
  annotation), and site chrome such as `nav`/`footer` on a full page (A11y
  Report's page scoping, Phase 4). All carry their tag/classes on the Origin.
- The Bard fixture is hand-written. Reading a real entry from a production site
  for it was refused by the permission classifier (PII) — the right call — so the
  fixture follows that site's node-type and attribute inventory (structure only,
  via grep counts), including sets with `enabled`/`values` and custom handles.
- **Tests:** 48 passing (`cd packages/readability-core && vendor/bin/pest`).

### 2026-09-11 — Phase 1, task 3: exclusion pipeline

- **Shape:** `Exclusion::apply(Document): Document`, one class per exclusion
  (`Code`, `Quotes`, `References`, `FiguresInTables`, `Addresses`, `IgnoredClasses`,
  `IgnoredSets`, `ProperNames`), composed by `Pipeline`. `Pipeline::fromConfig()`
  reads the spec's `exclude` sketch plus `code`/`addresses`/`figures`/`proper_names`
  booleans (all default on). Whole-block drops run first; text-level cuts leave a
  `Tidy::GAP` marker so the comma that belonged to the span goes with it.
- **Proper names are formula-only.** SC 3.1.5 grades "after removal of proper names
  and titles", but a long sentence is still long with the names in, the acronym
  rule needs the acronyms, and jargon needs every word. So the rules get
  `$pipeline->without(ProperNames::class)`. Detection is capitalisation-only and
  deliberately conservative: it never removes a lone sentence-initial capitalised
  word (so it misses "Mary left early."), never touches a title-case line or a
  heading, and an opening quote, a colon, or a hard break starts a new sentence.
  Known trade-off: a sentence-initial adverb before a name ("Yesterday John Smith
  said") is taken with the name unless the adverb is in the openers list.
- **Inline content needs the extractors' help.** By the time a Block exists, a
  `<span class="no-readability">` or inline `<code>` is part of its paragraph's
  text. `HtmlExtractor(ignoredTags:, ignoredClasses:)` and
  `BardExtractor(ignoredMarks:)` were added for that; `Code::INLINE_TAGS` and
  `Code::INLINE_MARKS` name what to pass. The addon must feed the same config to
  both the extractor and the pipeline.
- **Origin grew `ids` and `roles`** (element + ancestors) because that is what a
  references heuristic keys on (`<section id="footnotes">`, `role="doc-endnotes"`).
- **Surprises:** (1) a "References" heading section that ran to the next heading
  of the same level swallowed a full page's footer when no heading followed — it
  is now also bounded by the element the heading sat in. (2) The first title-case
  guard skipped every word in the openers list regardless of case, so "We thank
  Dr. Smith." looked title-cased and nothing was removed; title case now tolerates
  only the small words a title leaves lowercase, and a line ending in `.!?` is a
  sentence. (3) `core()` used `rtrim` with a set that lacked `.`, so `Health).`
  kept its bracket; the token anatomy (leading/core/trailing) was rewritten as one
  regex each.
- **Scope note:** the address patterns are English-speaking-country shapes (US
  street/zip, UK/CA postcodes, NA + international phones), consistent with the
  engine being English-only. `FiguresInTables` treats a run of consecutive
  in-table blocks as one table (HTML origins carry no table identity).
- **Tests:** 84 passing. Pipeline goldens over `messy.html` for both views are in
  `PipelineTest.php` and were each read line by line before being asserted.

### 2026-09-11 — Phase 1, task 4: sentence segmentation

- **Evaluated before hand-rolling, as the spec asked.** A 32-case corpus of the
  traps (honorifics, initials, dotted acronyms before names vs. at sentence end,
  "a.m."/"p.m.", "e.g.", decimals, domains, section numbers, ellipses, dialogue
  attribution, brackets, units, days/months) with the count a reader would give.
  Exact-count scores: **ICU `IntlBreakIterator` 25/32** (breaks after every
  honorific — PHP's intl does not expose ICU's CLDR abbreviation suppressions —
  and intl is optional for us anyway); **`vanderlee/php-sentence` 22/32** (wrong
  in both directions: "One. Two. Three." → 1, splits `example.com`, splits
  "a.m. | on Monday" before a lowercase word); **`davechild/textstatistics`
  10/32** (counts `.!?` characters; its own comment admits it); **`opencat/
  segmentation`** has no stable release and 0 downloads, not evaluated;
  **rule-based, ours: 32/32.** The corpus is my own, built from the spec's
  example and health-content patterns, not an external benchmark.
- **Shipped:** `Text\Segmenter` (interface) + `Text\EnglishSegmenter` +
  `Text\Sentence` (text, byte offset in the block). Rules: a run of `.!?` plus
  closers plus whitespace is a boundary unless a lowercase letter follows, the
  period is an honorific's or an initial's, or it is an abbreviation's / dotted
  acronym's and the next word is not a sentence opener (or a digit follows).
  Callers segment block by block; a hard break inside a block is a boundary.
- **Word lists moved to `Text\English`** (TITLES, ABBREVIATIONS, OPENERS,
  CONNECTORS, SMALL_IN_TITLES, `isDotted()`), shared by the segmenter and
  `ProperNames` so the two cannot disagree on whether "Inc." ends a sentence.
- **Known misses, accepted:** a single-letter word before a period ("vitamin C.
  Drink water", "Plan B. Then") reads as an initial; "U.S." followed by an
  opener-like proper name ("U.S. The Company") would break.
- **For task 6 (formulas):** headings, list items, captions and labels come out as
  one-sentence blocks ("Especially:", "Search"). Averaging words-per-sentence
  over those will flatter a page. The formula layer needs to decide which block
  kinds count as prose — probably paragraphs, list items of sentence length, and
  Other — rather than the segmenter pretending a label is a sentence.
- **Tests:** 120 passing.
