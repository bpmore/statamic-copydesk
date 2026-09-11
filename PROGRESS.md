# PROGRESS — Plain

**Spec:** `SPEC.md` · **Packages:** `bpmore/readability-core` + `bpmore/statamic-plain` · **$39 standalone, bundled free in A11y Report**
**Rule:** do the next unfinished task only, then update this file, verify the build, and commit.

## Phase 1 — Core engine (no Statamic)
- [x] Scaffold `readability-core`, composer-installable and testable standalone
- [x] Text extraction + normalization: HTML/Bard → analyzable plain text
- [x] Exclusion pipeline: code blocks, blockquotes, references, proper names, addresses, ignore-classes
- [x] Sentence segmentation that survives "Dr. Smith went to St. Louis Inc." — evaluate a real segmenter before hand-rolling
- [x] `SyllableCounter` interface with `davechild/textstatistics` as the first implementation
- [x] Formula layer implemented in-house over the counter (Flesch-Kincaid, SMOG, Gunning Fog, Coleman-Liau)
- [x] Unit tests against reference texts with published scores
- [x] Report a grade **band**, never a decimal
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

### 2026-09-11 — Phase 1, task 5: SyllableCounter

- **Shipped:** `Text\SyllableCounter` (interface: `syllables(string $word): int`,
  0 for no letters, ≥1 otherwise) and `Text\TextStatisticsSyllableCounter` over
  `davechild/textstatistics ^1.0` (BSD-2-Clause), now a runtime dependency. Plus
  `Text\Diacritics::strip()` — a fixed table, not intl, so the count is the same
  on every host.
- **Two corrections applied before the library sees a word**, because it keeps
  only A–Z: accents come off (`café` → 2, not `caf` → 1) and a vowelless initialism
  is read letter by letter (`CDC` → 3, `HTML` → 4; `NASA`/`COVID` still 2). Both
  only matter when `proper_names` exclusion is off, since names and acronyms are
  removed before grading anyway.
- **The library was unmaintained and already deprecated on our PHP floor.** Last
  release 2018-08-21; `Text::cleanText()` calls `utf8_decode()` (deprecated PHP
  8.2, removal planned for PHP 9) on every public entry point. Raised as a
  decision; **decided: port it** (see the follow-up entry below).
- **Accuracy, measured:** on a 36-word reference set the raw library scored 26;
  with the two corrections the systematic misses are gone, leaving ordinary
  heuristic misses (`science` 1, `create` 1, `area` 2, `queue` 2, `naïve` 1).
  These are not asserted in tests — a test that asserts a wrong number keeps it
  wrong. Good enough for a grade *band*, which is the only thing we report.
- **Tests:** 147 passing.

### 2026-09-11 — Phase 1, task 5, follow-up: the syllable rules ported in

- **Decision (Brent): port the algorithm rather than depend on the library.**
  `Text\EnglishSyllableCounter` now holds the rules of `davechild/textstatistics`
  1.0.3 (Lingua::EN::Syllable lineage) — exceptions, one/two/three-syllable
  affixes, the one-not-two and two-not-one sound lists, and the Kuwamoto
  singularizer used to look plurals up in the exceptions. `davechild/textstatistics`
  is removed from `composer.json`; the core has **no runtime dependencies** again.
- **Fidelity verified before the library was removed:** the port matched
  `Syllables::syllableCount()` on all 235,976 words of `/usr/share/dict/words`
  plus plural/hyphen/possessive forms — 0 differences. A 384-word sample of the
  library's answers is frozen in `tests/__fixtures__/syllables.json`; the test
  that checks it says plainly that the claim is fidelity, not correctness, so a
  rule improvement shows exactly which words it changed.
- **Licence compliance:** BSD-2-Clause requires the copyright notice, conditions
  and disclaimer to stay with redistributed source; they are in
  `packages/readability-core/THIRD-PARTY-LICENSES.md`, referenced from the class.
  The singularizer's MIT origin is credited the way the library credited it (a
  URL; no copyright line exists to reproduce).
- The two pre-rule corrections (diacritics off, vowelless initialisms spelled
  out) are unchanged. `TextStatisticsSyllableCounter` is deleted.
- **Tests:** 147 passing, none depending on the library.

### 2026-09-11 — Phase 1, task 6: formula layer

- **Shape:** `Formula\Counts` (sentences, words, syllables, characters, Gunning
  complex words, SMOG polysyllables; `add()` so sections sum) ← `Formula\Tally`
  (Segmenter + SyllableCounter → Counts) → `Formula\Formula` implementations
  `FleschKincaid`, `GunningFog`, `Smog`, `ColemanLiau` (`grade(Counts): float`,
  unrounded) + `FleschReadingEase::score()` (not a grade) + `Grades::of(Counts)`
  gathering all five (null when empty). `Text\Words` tokenizes: whitespace split,
  surrounding punctuation off, a token needs a letter or digit to be a word.
- **Which blocks count (the task-4 question): every block except headings.**
  Headings are section markers; counting "Who should get one?" as a one-line
  sentence flatters a page. List items count — a list is a plain-language
  technique and the formulas rewarding it is right. A number is a one-syllable
  word; "—" is not a word. Recorded in `Tally`'s docblock.
- **Gunning's complex word is done Gunning's way:** ≥3 syllables, but not a
  hyphenated compound, and not a word that only reaches three by `-es`/`-ed`/
  `-ing` (the stem is re-counted). Proper nouns are already gone via ProperNames.
  SMOG's polysyllables have no exclusions. Most implementations skip this; it
  makes Fog run lower than naive ports on -ing-heavy prose, which is the formula
  as published.
- **Constants** are the published ones (FK 0.39/11.8/15.59; Fog 0.4; SMOG
  1.0430/3.1291 with the ×30/sentences sample scaling; CL 0.0588/0.296/15.8; RE
  206.835/1.015/84.6). Each formula's docblock carries it. Grades can go negative
  on very plain text (FK −0.8 on "A flu shot keeps you well. It is free."); task
  8's band layer clamps.
- **Observed on the fixture page:** 18 sentences / 77 words = 4.3 words per
  sentence, because labels, nav and list items are one-"sentence" blocks. Real
  field content is mostly paragraphs so this is less severe there, but it is why
  per-section scoring (Phase 2) and the band matter. SMOG's 30-sentence design
  sample is noted in its docblock; nothing enforces it.
- **Tests:** 167 passing. Formula tests are against hand-computed values from one
  fixed `Counts`; the tally is tested with a table-driven stub counter so the
  arithmetic does not depend on the syllable rules. Reference texts with
  published scores are task 7, not done here.

### 2026-09-11 — Phase 1, task 7: reference texts with published scores

- **Sources:** the worked examples in Wikipedia's articles on each formula,
  fetched today rather than recalled. Flesch–Kincaid: "The cat sat on the mat."
  (RE 116), "This sentence, taken as a reading passage unto itself…" (RE 69), and
  the Australian platypus sentence (13 words, 24 syllables, RE 37.5, grade 11.3),
  plus Green Eggs and Ham's published ratios (5.7 w/s, 1.02 syl/w → −1.3).
  Coleman–Liau: the abstract of Coleman & Liau's own 1975 paper (5 sentences,
  119 words, 639 letters → 14.5). **No accessible source has a worked passage for
  SMOG or Gunning Fog** (both articles define the formula only; a university
  health-literacy PDF with a SMOG example was not text-extractable). Those two
  rest on the hand-computed formula tests from task 6 and the shared counts.
- **Results:** three of four texts exact through the whole chain — tokenizer,
  segmenter, syllable counter, formula. The Coleman–Liau abstract matches on all
  four published numbers (its fifth sentence runs past "U.S. Office", which the
  segmenter handles). The platypus sentence counts 26 syllables to the article's
  24 because "Australian" and "reptilian" are four here and three there, and
  Merriam-Webster allows either; the test asserts the undisputed counts exactly,
  the scores within what two syllables move them, and that the formulas give
  37.5/11.3 from the published syllable count.
- **Two syllable-rule defects found and fixed** (the point of owning the rules):
  (1) the add-rule `[aeiouy]sh?e[rsd]$` gave every "-sed"/"-shed" past participle
  an extra syllable — used, based, closed, washed, finished, published, diagnosed,
  advised, surprised; narrowed to `[rs]`, 427 dictionary words change by −1, all
  to the dictionary's answer (checked against the pre-change counter over the
  whole dictionary; sample reviewed by eye). (2) the `^pro` prefix rule took a
  syllable from prove/proved/proud(ly/er/est)/proof/prose/probe(d)/prone; added
  to EXCEPTIONS. Both departures are marked in the class at the point of change.
  The frozen fidelity sample moved on exactly one word ("polishedness" 4→3,
  correct), updated deliberately with the reason in the test's docblock.
- **Tests:** 172 passing.

### 2026-09-11 — Phase 1, task 8: the band

- **Shipped:** `Formula\Band` (whole-grade range; `of(float)` floors, clamps to
  1–2 at the bottom and `17+` open at the top; `around(grade, tolerance)` for
  targets; `label()` is an English convenience — the addon formats from `low`/
  `high` with its own translations), `Formula\Comparison` (OnTarget/Above/Below),
  `Formula\Target` (`fromConfig(['grade' => 8, 'tolerance' => 1])` → 7–9; defaults
  8 ± 1), and on `Grades`: `consensus()` (median of the four formulas) and
  `band()`.
- **Median, not mean:** SMOG was normed on 30-sentence samples and sits at its
  3.1 floor on short text; a median ignores one wandering formula. Consensus
  stays a decimal for ordering (worst offenders, trend) and the details view —
  its docblock says never as the headline.
- **The verdict is made from the band, not the decimal.** A band overlapping the
  target is *on target*: 9.99 shows as "Grade 9–10" and is on target for 7–9,
  because telling the reader "above" would contradict the two ranges they can
  see. This is deliberately generous by up to a grade; the tolerance config is
  where a site tightens it. "Below" is informational only.
- A negative tolerance in config is read as its distance (abs), not an inverted
  band.
- **Tests:** 179 passing.
