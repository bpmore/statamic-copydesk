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
- [x] Locale guard: English only; disable with an explicit message otherwise

## Phase 2 — Rules
- [x] One class per rule, individually testable
- [x] Long sentence / very long sentence
- [x] Long paragraph
- [x] Passive voice — **document-level rate, not per-instance**
- [x] Unexpanded acronyms
- [x] Jargon / banned phrases with suggested replacements
- [x] Nominalizations, hedging and filler (tips)
- [x] Per-section scoring by heading, not just whole-document

## Phase 3 — Statamic addon
- [x] Scaffold `statamic-plain`
- [x] Publish-form panel: grade band vs target, per-section grades, inline highlighting
- [x] Gate: opt-in per collection, **never blocks on grade alone**
- [ ] Jargon dictionary editor, per site, import/export, starter dictionary shipped
- [ ] Utility screen: distribution, worst offenders, acronym/jargon frequency
- [ ] Dashboard widget
- [ ] **Report a Readability band to Site Weather.** One class implementing `Bpmore\SiteWeather\Contracts\WeatherContributor` (`key()` `readability`, `label()` `Readability`, `reading()`), tagged by name as `site-weather.contributors` in the provider's `register()` — Site Weather is a `suggest`, plus `require-dev` (path repository `../statamic-site-weather` until it is on Packagist). `reading()` reads stored results only — the share of checked entries above the target grade — never recomputes; asks whether the addon is set up before it reads; returns `Reading::unknown()` with a next-step headline when not set up or nothing measured; a measured state carries the latest `computed_at`; `url` is the utility screen from the task above. Thresholds are this product's call. Follow `../statamic-a11y-docs/src/Weather/DocumentsContributor.php` and its test. Then drop the fake `readability` band from `../statamic-dev/app/Providers/SiteWeatherDemoProvider.php` and confirm the real one on the dev-site tile.
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

### 2026-09-11 — Phase 1, task 9: locale guard — Phase 1 complete

- **Shipped:** `Locale\LocaleGuard` (`fromConfig(['locales' => [...]])`, default
  `['en']`; `check(string): Decision`, `allows(string): bool`) and
  `Locale\Decision` (`allowed`, `locale`, `language`, `message()`). The core
  cannot read a Statamic site's locale — the addon passes it in (Phase 3: `Site::
  current()->locale()`); the core owns the decision and the words.
- **Matching:** a bare language in the setting (`en`) allows every region of it
  (`en_AU`, `en-GB`); a full locale (`en_US`) allows only itself. Case and `-`/`_`
  are ignored. The spec's example list `['en', 'en_US', 'en_GB']` is therefore
  redundant but harmless.
- **No locale is refused too** (`''`, `'default'`): scoring text whose language
  nobody stated is the silent failure the spec calls a correctness issue. The
  message says to set the site's locale.
- **Message:** English, plain words, says what to do ("add fr to the locales
  setting to check it anyway"). Language names come from intl when present
  ("French (fr)"), else the code ("set to fr"); a message, not a score, so host
  variation is acceptable. `Decision` carries `locale` and `language` so the
  addon can write its own translated message.
- **Phase 1 is complete.** The engine: extraction (HTML/Bard/plain) → exclusions →
  segmentation → syllables → tallies → four formulas → band vs target, plus the
  locale guard. No runtime dependencies. 186 tests. Nothing in `Phase 2` has been
  started.

### 2026-09-11 — Phase 2, task 1: the rule engine's contracts

- **Shipped:** `Rule\Rule` (`handle()`, `check(Document): list<Finding>`),
  `Rule\ConfiguredRule` (options from the spec's `['words' => 25, 'severity' =>
  'warning']` shape; per-rule default severity; `intOption()` falls back on
  nonsense), `Rule\Severity` (error/warning/tip, ranked; an unknown value throws),
  `Rule\Location` (block index + byte offset + length), `Rule\Finding` (handle,
  severity, plain-words message, location or null for document-level, excerpt,
  optional suggestion), `Rule\Findings` (reading order, document-level last;
  filters by rule/severity/block/scope; merge), `Rule\RuleSet` (`fromConfig`
  binds options by handle, defaults for unmentioned rules, `enabled => false`
  skips, **unknown handle throws** naming the known ones; `check()`, `without()`,
  `with()`).
- **Messages are for the author, not the developer:** "This sentence is 34 words;
  try breaking it in two", never "long_sentence threshold exceeded". The handle
  is for the gate and the config. Each rule writes its own message; the core
  ships English and carries enough on the Finding for the addon to translate.
- **Registry is empty on purpose.** `RuleSet::registry()` is where each later
  task adds its rule (handle → factory taking options). The test "ships no rules
  yet" is a statement about now and is replaced as rules land.
- Rules receive the document **with names in** (`Pipeline::without(ProperNames::
  class)`), per the task-3 decision; a rule that needs sentences takes a
  Segmenter.
- **Tests:** 196 passing; the engine is tested with two stub rules defined in the
  test file (one located, one document-level).

### 2026-09-11 — Phase 2, task 2: long sentence / very long sentence

- **Shipped:** `Rule\LongSentence` (`long_sentence`, > `words` [25], warning) and
  `Rule\VeryLongSentence` (`very_long_sentence`, > `words` [2 × long's, so 50],
  error), both registered; `Rule\Sentences` (internal) walks a document's
  sentences with block index and word count, shared by every rule that needs
  sentences so none segments its own way.
- **A sentence is never said twice.** A 60-word sentence is over both limits;
  two findings for one sentence teach authors to ignore the panel. `LongSentence`
  takes a `cap` and stops where very-long starts. The registry is the one place
  that sees the whole `rules` section, so its factories wire the cap: very-long's
  threshold, or `null` (no ceiling) when very-long is disabled. Registry factories
  now receive `(options, allRulesConfig)`; extra args are ignored by closures that
  do not declare them, so the stub registries in tests still work.
- **Very-long defaults to 2 × long**, per the spec's table, not a fixed 50: a
  site that sets `long_sentence.words = 20` gets 40 without setting both. An
  explicit `very_long_sentence.words` wins.
- Messages: "This sentence runs to 26 words. Break it in two; about 25 is as many
  as a reader can follow at once." / "…far past the 50 a reader can follow. Break
  it into three or four." Location is the sentence (block, byte offset, length).
- Rules run on every block, headings included; a 30-word heading is a finding.
- One thing learned writing the smoke test: synthetic sentences must start with a
  capital, or the segmenter (correctly) reads "word. word" as one sentence.
- **Tests:** 203 passing.

### 2026-09-11 — Phase 2, task 3: long paragraph

- **Shipped:** `Rule\LongParagraph` (`long_paragraph`; > `words` [150] **or** >
  `sentences` [8]; warning), registered. The spec's config gives only `words`;
  the `sentences` default of 8 is plainlanguage.gov's ("no more than 150 words in
  three to eight sentences").
- Every block but a heading counts — a 160-word bullet is a paragraph in
  disguise. The finding covers the whole block (offset 0, full length); the
  excerpt is the first sentence so the panel can name it. The message says which
  limit was crossed, or both.
- **Process slip, fixed in the next commit:** `496445d` went in with two failing
  tests because `pest | grep` hid pest's exit status. Neither failure was in the
  code (one test used single letters as words, which the segmenter reads as
  initials; the other's 152-word paragraph tripped the new rule). **Before
  committing, run `vendor/bin/pest` unpiped and let its exit status gate the
  commit** — `vendor/bin/pest > /dev/null 2>&1 && git commit …`.
- **Tests:** 209 passing.

### 2026-09-11 — Phase 2, task 4: passive voice, as a rate

- **Shipped:** `Rule\PassiveVoice` (`passive_voice`; `max_percent` [20],
  `min_sentences` [5], warning), registered. `PassiveVoice::isPassive(string)` is
  public for reuse. **One finding about the whole document** (location null) when
  the share of passive *sentences* passes the limit; nothing at all below it.
- **`Finding` gained `spans`** (list of Locations, default []): the places a
  whole-document finding points at, for highlighting. The passive finding attaches
  every passive sentence so the panel can mark them without them being findings
  of their own — which is how "flagged sentences with inline highlighting" and
  "a rate, not a count" both hold.
- **Detection:** a form of *to be*, up to three steps over adverbs / `not` /
  `being` / `-ly` words, then a participle (`-ed` of ≥4 letters, or ~120
  irregulars). A short list of participial adjectives (`tired`, `interested`,
  `worried`, `supposed`…) is left alone; the rest of the false positives are what
  the rate is for. "The clinic was founded in 1879" counts — it is passive — and
  that is fine at 1 of 10. 17/17 on the sentence set; contractions ("they're
  treated") are not handled, noted.
- **`min_sentences`** (default 5) is an addition to the spec's config: 1 passive
  in 3 sentences is 33% and means nothing.
- **Tests:** 231 passing (exit status checked).

### 2026-09-11 — Phase 2, task 5: unexpanded acronyms

- **Shipped:** `Rule\UnexpandedAcronym` (`unexpanded_acronym`; `ignore` list added
  to a built-in `KNOWN` list; warning), registered. One finding per acronym, at
  its first use, never per occurrence.
- **What counts as spelled out:** the plain-language convention, either way round
  — `Centers for Disease Control and Prevention (CDC)` or `CDC (Centers for …)` —
  and a phrase whose initials spell the acronym with small words skipped (`World
  Health Organization` → WHO, `emergency department` → ED; the initials check
  matches a prefix, so `Centers for Disease Control` defines CDC even without the
  brackets). The expansion must be at or before the first use; one that comes
  later gets its own message ("spelled out later than this, its first use").
- **An acronym** is two or more capitals with digits/periods/hyphens allowed
  (`U.S.`, `COVID-19`, `H1N1`), plural or possessive folded (`MRIs`, `CDC's`,
  `M.R.I.` → one acronym). Mixed-case terms like `HbA1c` are not acronyms here —
  the jargon dictionary (next task) is where those belong. Headings are not
  scanned. The `KNOWN` list is universal short forms (`TV`, `PDF`, `ER`, `HIV`,
  `US`…); site-specific vocabulary goes in `ignore` or the dictionary.
- **Surprise:** `CDC` is made entirely of Roman-numeral letters and the first
  "not a numeral" check rejected it. The check is now a real numeral pattern
  (`IV`, `XIV`, `MCMXC` are numerals; `CDC`, `DVD` are not). Found by the test for
  possessives, which is why there is one.
- **Tests:** 241 passing (exit status checked).

### 2026-09-11 — Phase 2, task 6: jargon and banned phrases

- **Shipped:** `Rule\Dictionary` + `Rule\DictionaryEntry` (term → replacements +
  optional note; `fromArray()` in every shape a site file will take, `merge()`
  site-over-starter, `without()`, `toArray()` for export, `starter()`), the
  starter at `resources/dictionaries/plain-language.json` (177 entries from
  plainlanguage.gov's simple-words list — US federal, public domain; `_source`
  key documents it), and `Rule\Jargon` (`jargon`; `dictionary` option: `'default'`
  = starter, an array, a `Dictionary`, `'none'`; a Dictionary passed directly to
  the constructor wins, which is how the addon will supply a stored per-site one;
  an unknown handle string throws — the addon resolves handles). Registered.
- **One finding per occurrence** — each is a concrete edit, unlike passive voice
  — with the first replacement as `suggestion` and a message that says what to
  say instead ("Say \"use\" instead of \"utilize\"."), the entry's note appended.
  Banned terms (no replacements): "…is on the site's list of words to avoid. Say
  it another way."
- **Matching:** whole words, any case; a phrase's spaces match across a hard
  break; a single-word term takes its inflections with a three-rule stemmer
  (`utilize`→utilizes/utilized/utilizing, `notify`→notifies/notified/notifying,
  `assist`→assists/assisted/assisting — not `assistance`, which is its own
  entry); where two terms overlap the longer speaks and the shorter is not
  repeated inside it. Findings come back in reading order regardless of
  dictionary order.
- The CP editor, import/export UI and per-site storage are Phase 3; the core's
  `toArray()`/`fromArray()` round-trip is what they will use. A health-specific
  starter (hypertension → high blood pressure…) would be valuable for the
  buyers; not in the spec, not done, noted.
- **Tests:** 251 passing (exit status checked).

### 2026-09-11 — Phase 2, task 7: nominalizations, hedging and filler

- **Shipped:** `Rule\Filler` (`filler`; built-in `FILLER` and `HEDGES` lists, `ignore`
  option; tip — "adds nothing. Cut it." / "hedges. Say it plainly, or say how
  likely it is.") and `Rule\Nominalization` (`nominalization`; tip — "hides the
  verb. Say \"decide\"." with the verb as `suggestion`). Both registered. Phrase
  matching moved out of `Jargon` into an internal `Rule\Phrases` helper that all
  three share (whole words, any case, across hard breaks, optional inflections,
  longest match wins).
- **No double-flagging with jargon.** The spec's examples for this row ("in order
  to", "utilize", "please be advised") are in the jargon starter at warning level,
  so the filler lists deliberately exclude them; a test asserts the intersection
  with the starter is empty. Rule: a span gets one finding.
- **Nominalizations are a curated map, not a suffix heuristic.** The first cut
  matched any `-tion/-ment/-ance/-al` noun after a light verb or before "of" and
  would have flagged *make an appointment*, *the department of health*, *the goal
  of this page* — the first is the most important phrase on a health site. Now
  only ~90 mapped nouns count (two shapes: light verb + noun, determiner + noun +
  "of"); `insurance`/`assurance` dropped (*have insurance*), `seek`/`require`
  dropped as light verbs (*seek treatment*). A test pins those phrases as
  untouched.
- Both are per-occurrence tips; the panel groups by severity.
- **Tests:** 260 passing (exit status checked).

### 2026-09-11 — Phase 2, task 8: per-section scoring — Phase 2 complete

- **Shipped:** `Formula\Section` (heading or null for the lead, block indexes and
  blocks, `Counts`; `title()`, `level()`, `isLead()`, `isEmpty()`, `grades()`,
  `band()`, `contains()`, `findings(Findings)`) and `Formula\Sections::of(Document,
  Tally, ?Exclusion $beforeGrading)` + `hardestFirst()`. `Findings::inBlocks()`
  added.
- **Chunking is by block kind, every heading level, flat.** Answers the spec's
  open question: HTML, Bard and rendered markdown arrive as the same `Document`,
  so they chunk identically — one test runs the same assertions over both
  fixtures. A heading with nothing under it is a section with nothing to grade
  (`band()` null); the panel decides what to show.
- **One block numbering.** Sections are cut from the *rules'* document (names
  in), so their indexes are the ones findings carry. `ProperNames` can drop a
  block that was only a name ("Mary Smith."), which would shift every index after
  it — so the grading exclusion is applied per section, to that section's blocks
  alone. Same numbering everywhere; names still out of the grade. The whole-
  document grade should be computed the same way: `Grades::of($tally->of((new
  ProperNames)->apply($forRules)))`.
- **The spec's scenario reproduces:** a page at Grade 12–13 whose three sections
  read 1–6 and whose "fine print" reads 17+.
- No minimum section size is enforced in the core; a one-sentence section gets
  a grade because that is the point. The addon may choose to label very short
  sections rather than grade them.
- **Phase 2 is complete.** Eight rules (`long_sentence`, `very_long_sentence`,
  `long_paragraph`, `passive_voice`, `unexpanded_acronym`, `jargon`,
  `nominalization`, `filler`) and per-section grades. 267 tests. Nothing in
  Phase 3 has been started; the addon is not scaffolded.

### 2026-09-11 — Phase 3, task 1: scaffold `statamic-plain`

- **Shipped at the repo root:** `composer.json` (`bpmore/statamic-plain`,
  `type: statamic-addon`, PHP `^8.2`, `statamic/cms ^6.27` — the floor the sibling
  addons proved with the same Testbench/Pest constraints; CLAUDE.md's `^6.0`
  intent holds), `src/ServiceProvider.php` (empty `bootAddon()`; each surface
  registers itself in its own task), `tests/TestCase.php` extending
  `Statamic\Testing\AddonTestCase`, `tests/Pest.php` (feature tests boot Statamic;
  the engine's suite stays separate), `tests/ArchTest.php`, a boot smoke test,
  `phpunit.xml`, root `README.md`, and a second CI job for the root suite.
  Resolved locally: Statamic **6.32.0**, Laravel 13, Testbench 11.2, Pest 4.7.8.
- **The engine is linked by a path repository inside the repo**, pinned with the
  repository's `versions` option (`bpmore/readability-core: 0.1.0`) so the addon
  can require a bound `^0.1` and stay `composer validate --strict` clean. A
  `version` field in the engine's manifest was tried first and rejected: strict
  validation warns on it. **Phase 5:** when the engine is on Packagist, drop the
  path repository and require a real range.
- **The CLAUDE.md arch test is now live and bites.** In the root suite Illuminate
  is installed, so `not->toUse(['Statamic', 'Illuminate'])` works as written:
  verified red with a throwaway class importing `Illuminate\Support\Str`, green
  without. The engine's own token-scan stays as the standalone enforcement.
- **No npm, no Vite** — deliberately. A11y Gate's panel is a plain script
  registered via `$scripts`, using the runtime Vue template compiler Statamic
  ships, and its fieldtype is the publish-form panel. This addon follows that;
  CLAUDE.md's `npm run build` line is generic kit text. Revisit only if a surface
  needs a build.
- **Host site linked** (`statamic-dev/composer.json`): two path repositories
  (`../statamic-plain` and `../statamic-plain/packages/readability-core` with the
  `versions` pin) and `bpmore/statamic-plain: @dev`; `composer update` symlinked
  both. Backup of the host manifest in the session scratchpad. `vendor/composer/
  installed.json` carries the addon's `extra.statamic` and provider, and both
  packages autoload from the host.
- **⚠ The host does not boot right now, and not because of this addon.** The
  `statamic-lms` sibling has uncommitted work in progress: its modified
  `ServiceProvider.php` imports `Bpmore\StatamicLms\Models\Enrollment\Sources\FreeSource`
  while the untracked class lives at `Enrollment\Sources\FreeSource`. Any addon
  linked into the shared host can break its boot. Not touched. **Re-run
  `php please` in the host once LMS is committed** to see this addon discovered
  there; until then the booted-Statamic suite here is the proof.
- `tests/__fixtures__/dev-null/` exists for the harness's fake stache (only its
  `.gitkeep` is tracked); the harness's manifest has no display name, so the
  smoke test reads "Plain" from `composer.json` directly.
- **Tests:** root suite 4 passing (boots Statamic); engine suite 267.

### 2026-09-11 — Phase 3, task 2: the publish-form panel

- **Shipped:** `config/statamic-plain.php` (the spec's sketch, every key
  documented) and `Settings` (read once, a value); `Analysis\FieldText` (a field's
  value → blocks by fieldtype: Bard node tree, markdown rendered by the site's own
  parser, textarea/text plain; anything else is not prose), `Analysis\Analyzer`
  (locale guard → configured fields → exclusions per field → grade vs target →
  sections → rules) and `Analysis\Report` (`toArray()` is the endpoint's JSON);
  `Fieldtypes\ReadabilityPanel` (`readability_panel`, stores nothing);
  `Listeners\AddPanelToBlueprints` (sidebar of every collection blueprint that
  has a configured prose field, stamped with collection + blueprint for the
  create screen); `Http\Controllers\CheckEntryController` at `POST cp/plain/
  check` (`plain.check`); `resources/js/plain-panel.js` registered via
  `$scripts`. The engine gained `Pipeline::only()`.
- **The pattern is A11y Gate's, deliberately**: fieldtype as the extension
  point, the field ensured by handle like `slug` and `date`, values processed
  through the blueprint as a save would, the create screen served from stamped
  field config, the same authorization as opening the entry, a sentence for
  every failure (a bare `abort()` draws nothing, which looks like a clean page),
  and a plain script on the runtime Vue compiler — no npm.
- **One block numbering, again.** Exclusions drop blocks, so each field is
  excluded on its own *before* the fields are joined; the block→field map is
  exact and a finding names its field. `Sections` gets the grading exclusion
  (`only(ProperNames::class)`) per section as designed in Phase 2.
- **"Inline highlighting" as built:** every finding comes with `context` — the
  sentence it sits in and the flagged span's place in it, **in characters, not
  bytes**, because the panel is JavaScript and "café" would shift the mark — and
  the panel draws the sentence with `<mark>` around the words. A whole-document
  finding (passive-voice rate) carries its sentences as `spans`; the first five
  are drawn. **Not built: decoration inside the Bard editor itself.** That needs a
  Tiptap extension registered through `Statamic.$bard`, which means reading how
  the 6.x bundle exposes ProseMirror's `Plugin`/`Decoration` — a separate
  exploration, recorded here as the follow-up rather than half-done.
- **Verified in the host:** it boots again (the LMS sibling moved on); Plain is
  discovered by name, the fieldtype is registered, `cp/plain/check` routes, the
  script is published (`php please vendor:publish --tag=statamic-plain`; a real
  install publishes on `composer update`) and served as JavaScript with the
  component registration, and an anonymous POST gets 419, not 500. **Not seen
  with eyes**: the Chrome extension was not connected this session, so the panel
  has not been opened in a browser. First thing to do next session: open
  `/cp/collections/pages/entries/<id>` and press Check.
- **Test notes:** a test heading "Notes" vanished — the References exclusion
  treats a "Notes" heading as a references section, correctly; renamed. A fake
  asset id in a test made Statamic's own assets fieldtype throw during
  `process()`, as a save would; removed. The Vue-template well-formedness helper
  (ported from A11y Report) now tolerates `:prop`/`@event` names and `>` inside
  attribute values.
- **Tests:** root suite 19 (boots Statamic), engine 268.

### 2026-09-12 — Phase 3, task 3: the gate

- **Shipped:** `Gate\GateSettings` (`enabled` off by default; `collections` empty =
  every collection once enabled; `block_on` defaults `['very_long_sentence',
  'jargon']`), `Gate\GateResult`, `Gate\PublishGate::inspect(Entry)` (draft →
  not applicable; collection not gated → n/a; locale refused → n/a with the
  guard's words; analyzer throws → could-not-check), `Listeners\RefuseUnlessPlain`
  on `EntrySaving` (throws `ValidationException` keyed `plain`, status 422, as
  A11y Gate does and for its reasons: returning false says nothing). The panel
  now draws `errors.plain` as an alert. `Settings::gate()` returns `GateSettings`,
  bound in the container.
- **Never on a grade — structurally.** `GateSettings` has no field for a grade or
  a band; `PublishGate::blocking()` filters findings by rule handle only. A test
  saves a page that reads Grade 17+ on every formula and asserts the settings
  object has exactly `enabled`, `collections`, `blockOn`.
- **`jargon` in `block_on` means banned terms.** The spec's blockable thing is
  "banned phrases"; a dictionary entry with a replacement is a warning to fix
  (`utilize`), one with nothing to say instead is banned (`please be advised
  that`) and blocks. Implemented as `suggestion === null`. **Flagged as a
  reading — easy to widen to every jargon finding if that was the intent.**
- **Fails open, on purpose** — the opposite of A11y Gate. There, an unrenderable
  page is the page's problem and the save is refused. Here, an analyzer exception
  (a broken `rules` config, say) is this addon's problem: the entry saves and the
  log gets an error line naming the cause. A readability checker must never be
  why a site cannot publish. Tested with an unknown rule handle in config.
- Refusal lines: "This page was not published. One thing has to be fixed first."
  / each blocking finding's message with "(in content)" / "The grade never stops
  a publish, and neither do the other findings; they are in the Readability
  panel." The toast still says "The given data was invalid." — Statamic's
  `ValidationException::summarize()` — which is why the panel draws the lines.
- Test-data lessons: a collection with no fixture blueprint gets Statamic's
  default one, where `content` is markdown and wants a string, not a Bard tree;
  and a sixty-word constant had sixty-one words — now a computed helper.
- **Tests:** root 30 (boots Statamic), engine 268.
