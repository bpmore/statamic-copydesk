ALL TASKS COMPLETE

# PROGRESS: Copydesk

**Spec:** `docs/SPEC.md` · **Conventions:** `docs/CONVENTIONS.md` · **Decisions:** `docs/decision.md`, `docs/a11y-gate-plan.md`
**Starting point:** Plain 1.0.1, renamed (see `docs/history/`). It already has the publish-form panel, readability scoring, an opt-in gate, the jargon dictionary, a site scan, `copydesk:scan`, a widget, a tag and a Site Weather band.
**Rule:** do the next unfinished task only, then update this file, verify the build, and commit.

Phase 1 is the spec's "Scope: v1", built on the Plain code in the order below: housekeeping, then the engine, then the packs, then the Statamic surfaces that use them. Pro-edition features beyond v1 (AI rewrite, scheduled emails, the Site Weather signal) are not in Phase 1.

## Decisions

Answered by Brent on 2026-10-26. Each changes a task below, which names it as (Dn).

- **D1. One copy of the engine. Decided 2026-10-26: the standalone repo only.** `packages/readability-core/` in this repo and `github.com/bpmore/readability-core` (on Packagist, used by A11y Report and greenhouse) were two copies of the same code, kept in step by nothing. This repo requires `bpmore/readability-core` from Packagist (a path repository to `../readability-core` for development) and drops `packages/`.
- **D2. The plain-language pack's word list. Decided 2026-10-26: keep plainlanguage.gov's list.** The spec says it is "written from scratch". Plain's starter is plainlanguage.gov's list, which is public domain (US federal work), so it is legal to ship. The pack uses it, credited; only the inclusive-language and news-style packs are written from scratch. This departs from the spec on purpose.
- **D3. Per-paragraph grades. Decided 2026-10-26: keep the grade range.** The spec asks for Flesch-Kincaid and reading ease "per entry and per paragraph". Plain grades whole pages and sections as bands, on purpose: four formulas on a three-sentence paragraph give noise, not a grade. The band per page and per section stays the headline; Flesch-Kincaid and reading ease show as numbers in the details; a paragraph is marked only when it reads well above target. This departs from the spec on purpose.
- **D4. Live checks in non-Bard fields. Decided 2026-10-26: a list under the field.** A textarea or text input cannot draw underlines. Bard gets underlines; markdown, textarea and text fields get the same issues as a list under the field, with the same suggestions and "Ignore here".

- **D5. Original text and positions. Decided 2026-10-26: fix it in readability-core, after the rest of Phase 1.2.** The engine cleans text before rules see it, so two punctuation checks cannot work and every finding's position is in the cleaned text, not the editor's (Notes, 2026-10-26, task 5). Phase 1.2b below does the fix before any editor work.

## Phase 1.1: Housekeeping

- [x] One engine copy (D1): require `bpmore/readability-core` from Packagist, path repository to `../readability-core` in development, remove `packages/readability-core/` and the CI job that tests it there (the engine's own repo tests it).
- [x] A Vite build, set up the way `bpmore/statamic-lifecycle` has it (Vite config, `$vite` in the service provider, built bundle committed under `resources/dist`, a CI check that the committed build is current). The panel script moves into it unchanged; its tests still pass.
- [x] Editions: `free` and `pro` in `composer.json` `extra.statamic.editions`, one helper that answers "is this Pro", and a test for each edition. Nothing is gated yet; later tasks use the helper.

## Phase 1.2: The style rule engine (plain PHP, no Statamic)

Lives in `src/Style/`, implements the engine's `Rule` interface so one `RuleSet` runs readability rules and style rules together. An arch test keeps `Bpmore\Copydesk\Style` free of `Statamic\` and `Illuminate\`. YAML is parsed at the edge; the engine takes arrays.

- [x] The rule schema from the spec (`id`, `type`, `message`, `suggestions`, `severity`, `scope`, `exceptions`, plus each type's own keys), validated with errors that name the pack file and the rule id. `info` maps to the engine's `tip`.
- [x] `word_list`: words and phrases, each with an optional replacement; whole words, any case, longest phrase wins. Each entry may carry its own severity, so a banned word can be an error and a suggestion a warning.
- [x] `term`: exact spelling and casing of a name; near misses flagged case-insensitively. Acceptance 2: "eiSEO" is not flagged, "EiSEO" and "eiseo" are.
- [x] `pattern`: Unicode regex. A pattern that does not compile is refused at load with the rule id, never at lint time.
- [x] `punctuation`: em dash, double spaces, straight vs curly quotes (configured per pack), space before punctuation, repeated punctuation.
- [x] `casing`: sentence case or title case for headings and title fields.
- [x] `sentence`: over N words, the passive-voice heuristic, a sentence that starts with a numeral. Reuses the engine's long-sentence and passive rules where they already do this, rather than a second copy.
- [x] `scope` (collections, fields) and `exceptions` (literal or regex), on every type.
- [x] Pack resolution: bundled packs, then site packs in `resources/copydesk/*.yaml`, then per-collection overrides; `extends`, disabling a rule by id, and a refusal (naming both packs) for a cycle.
- [x] Compiled packs cached, keyed by each pack file's path and modified time, so editing a YAML file takes effect on the next lint with no cache clear (acceptance 7).
- [x] A benchmark test: lint a 3,000-word entry with every bundled pack on in under 150 ms. Reported, not failed, on CI, whose machines vary; failed locally.

## Phase 1.2b: Original text and positions (D5)

In `bpmore/readability-core`, released as 0.2.0, then taken here.

- [x] Each block keeps the text as written beside the cleaned text, with a map from every cleaned position back to the written one, through normalising (collapsed spaces, straightened quotes, `…`, invisible characters) and through the exclusions that cut text out of a block (`Addresses`, `References`, `ProperNames`). Additive: nothing that reads `text` today changes. Released as 0.2.0.
- [x] Copydesk requires `^0.2`. Every finding (readability and house) is given its position in the written text; the panel and the coming lint endpoint use those.
- [x] `punctuation` reads the written text for `quotes` and `double_space`, and their skipped tests run and pass (task 5 ticked).

## Phase 1.3: Bundled packs

All wording original. Nothing copied from the AP Stylebook, and no pack named "AP".

- [x] `core-punctuation`.
- [x] `plain-language` (D2): complex word to simpler word pairs, from plainlanguage.gov's public-domain list, credited in the pack and the docs.
- [x] `inclusive-language`: written from scratch, each entry with a short reason.
- [x] `news-style-starter`: a small set of widely known newsroom conventions (numerals, percent, time, dates), described in our own words.
- [x] Docs page showing how a site with an AP licence writes its own pack.
- [x] A review list for acceptance 9: every bundled entry, for Brent to sign off that none copies AP wording. Machine checks cannot decide this; a person does.

## Phase 1.4: One analysis

- [x] The analyzer runs readability rules and style rules together, each rule only where its `scope` says, and returns one list of findings with character offsets.
- [x] Targets per collection in config (`targets: ['patient_education' => 8]`), falling back to the site target.
- [x] Plain's dictionary becomes the site's `word_list` pack: what the settings screen saves is read as a pack, so nothing a site entered is lost. Import and export keep working.
- [x] Readability details (D3): Flesch-Kincaid and reading ease beside the band, per page and per section, from `readability-core` (acceptance 5).
- [x] A paragraph that reads well above target is marked (D3). How far above counts as "well above" is a config value, with the default and its reason in the config file.

## Phase 1.5: In the editor

- [x] **Spike first.** Prove a Bard extension added through `Statamic.$bard.addExtension` can draw ProseMirror decorations using the editor's own ProseMirror, not a second bundled copy (a second copy breaks decorations silently). Write down what was found. If it cannot, stop and say so here before the next task.
- [x] `POST /cp/copydesk/lint`: one field's text in, issues with character offsets out. Needs the same permission and entry access as the panel's check.
- [x] Bard underlines, debounced 600 ms per field: dotted for info, wavy for warning, double for error, never colour alone. Acceptance 1: an em dash typed in Bard is underlined within a second.
- [x] The popover: message, suggestion buttons that replace the text, "Ignore here". Keyboard reachable, announced to screen readers, focus returned to the text after.
- [x] Markdown, textarea and text fields (D4): the field's issues as a list under it, debounced the same way, with the same suggestions and "Ignore here".
- [x] Bard nested in a Replicator set inside Grid (acceptance 8), with a fixture for it.
- [x] The sidebar panel: grade against target, issue counts by severity, a link that jumps to each issue.

## Phase 1.6: Ignores

- [x] "Ignore here" stores a fingerprint (rule id plus a hash of the sentence) in a hidden `copydesk_ignores` field on the entry. A changed sentence no longer matches, so the ignore lapses.
- [x] Ignored findings are left out of the editor and the gate, and counted in the report so they cannot hide problems quietly.

## Phase 1.7: The gate (Pro)

- [x] Blocks a publish on any finding whose severity is `error`. Never on a grade. Drafts always save. Fails open when the check itself breaks, as now. The message names each failing rule (acceptance 3).
- [x] `override copydesk gate`: "Publish anyway" with a required reason, through the one-time pass in `docs/a11y-gate-plan.md`. The reason is logged and shown in the report (acceptance 4).
- [x] A11y Gate: the contributor contract in A11y Gate (its own repo and PR), then Copydesk signing up to it and standing down its own listener when the contract is there. As planned in `docs/a11y-gate-plan.md`.

## Phase 1.8: Site report and command

- [x] The site scan runs as queued jobs of 50 entries, with progress on the report page, instead of inside the web request.
- [x] The report: issues by collection, by rule and by page; ignored counts; overrides with their reasons; CSV export.
- [x] `copydesk:scan {--site=} {--collection=} {--fail-on=error}`: exit 1 when any published entry has a finding at or above that severity, 0 otherwise (acceptance 6). The existing `--fail-above` and `--export` stay.

## Phase 1.9: Rule editor (Pro)

- [x] A form-based editor for site packs: add, edit and disable rules, and try a rule on sample text before saving. Writes YAML. Needs `manage copydesk rules`.

## Phase 1.10: Ready to ship

- [x] Permissions as the spec lists them (`view copydesk report`, `manage copydesk rules`, `override copydesk gate`) alongside the existing ones; none granted to anyone but super admins by default.
- [x] Free and Pro split as the spec lists it, through the editions helper.
- [x] Every CP screen: an axe scan and a keyboard-only walkthrough.
- [x] README and editor docs for Copydesk; CHANGELOG; `docs/marketplace.md`; a grep for U+2014 that finds only rule definitions and the specs.
- [x] Installed on greenhouse beside every other bpmore addon: no clashes in route names, permission handles, nav sections or JS globals.

## Rough size

Guesses from the size of the Plain code and the spec, not measured.

- 1.1 housekeeping: about 1 day.
- 1.2 engine: 3 to 4 days.
- 1.3 packs: 2 days, plus Brent's review.
- 1.4 analysis: 1 to 2 days.
- 1.5 editor: 4 to 6 days. The most uncertain: it depends on the spike.
- 1.6 ignores: 1 day.
- 1.7 gate: 2 to 3 days, including the A11y Gate change.
- 1.8 report and command: 2 days.
- 1.9 rule editor: 2 to 3 days.
- 1.10 ready to ship: 2 days.

## Notes

### 2026-10-26: Phase 1.1, task 1: one engine copy
- `packages/readability-core/` matched `bpmore/readability-core` v0.1.0 (the release on
  Packagist, and that repo's `main`) file for file, except two product names in `LICENSE.md`.
  So nothing was lost by deleting it. The engine now installs from Packagist as `v0.1.0`.
- **Changed from the task as written:** no path repository is committed. CI has no
  `../readability-core`, and Composer refuses a path repository whose folder is missing (the
  reason the Site Weather one is dropped on the runner). The README shows the two commands to
  link a local checkout instead, for when the engine and the addon change together.
- The engine's CI job is gone from `tests.yml`; its own repository runs its suite.
- 85 passed locally; both CI jobs replayed on fresh clones (see the commit).

### 2026-10-26: Phase 1.1, task 2: a Vite build
- Copied from Lifecycle: `vite.config.js` (Statamic's Vite plugin, so Vue and the UI kit stay
  the control panel's own; manifest at `resources/dist/build/manifest.json`, where Laravel
  looks), `package.json` (vite `^6.4.5`, past the five advisories Lifecycle just closed), `$vite`
  in the service provider in place of `$scripts`. `resources/js/cp.js` imports the panel script,
  which is unchanged. The built bundle is committed; a rebuild from `npm ci` is byte-identical.
- CI gains Lifecycle's "Control panel assets" job: build, then fail if `resources/dist` changed.
  Copydesk commits no `composer.lock`, so the runner builds against whatever Statamic resolves.
  The bundle has no imports from `@statamic/cms`, so that should not change its output; if the
  job ever goes red on an unchanged commit, that is the first thing to check.
- A test checks the manifest exists, names `resources/js/cp.js`, and every file it lists is there.
  Tests cannot see the control panel's script tags (Testbench renders none), so the panel was
  also checked on a throwaway copy of greenhouse running this branch: the edit screen loads
  `vendor/statamic-copydesk/build/assets/cp-*.js`, "Check readability" posts to
  `/cp/copydesk/check` (200) and the panel draws the band, the finding and the numbers. No
  console errors.
- **For greenhouse, when it next updates Copydesk:** run `vendor:publish --tag=copydesk`, and
  delete `public/vendor/statamic-copydesk/js/`, the old panel script nothing loads any more.
- 86 passed.

### 2026-10-26: Phase 1.1, task 3: editions
- `extra.statamic.editions` is `["free", "pro"]`, Free first, so Statamic's `Addon::edition()`
  answers Free until a site sets `statamic.editions.addons.bpmore/statamic-copydesk`. No sibling
  addon used editions before; this follows Statamic's own `Addon::edition()`.
- `Edition::isPro()` (and `Edition::current()`) is the one question every Pro feature asks. An
  unknown edition in config throws, as Statamic does, rather than quietly running Free.
- **Found:** Statamic's `AddonTestCase` writes the addon's manifest without `editions`, so under
  test the addon had none, a state no install can be in. `TestCase` now copies them from
  `composer.json`. Without that, three of the five edition tests fail (checked).
- Nothing is gated yet. 91 passed.

### 2026-10-26: Phase 1.2, task 1: the rule schema
- `Style\RuleDefinition::fromArray($raw, $source, $position)` checks one rule and returns it plain:
  id (lowercase, hyphens), type, message, suggestions, severity (`info` becomes the engine's
  `tip`; `warning` if left out), scope (`collections`, `fields`, `*` if left out), exceptions
  (text, or `/regex/flags`, checked to compile), and the type's own keys.
- **Decided here, as the spec names no keys for them:** `word_list` takes `words` (word: what to
  say instead; a word, a list, null for "say it another way", or `replace`/`severity`/`reason`);
  `term` takes `terms`; `pattern` takes `pattern` (no delimiters) and `flags` (`imsxU`);
  `punctuation` takes `checks` and `quotes` (curly/straight, only with the `quotes` check);
  `casing` takes `style` (sentence/title); `sentence` takes `check` (`max_words` with
  `max_words`, `passive`, `starts_with_numeral`). The type tasks below build on these.
- Every refusal names the pack and the rule and says what to change; an unknown key is refused
  with the nearest known key offered. Exception messages are English and not translated: they
  are for whoever edits the YAML, like Statamic's own.
- Whether `pattern` compiles is left to the `pattern` task, as planned. `RuleDefinition::compiles()`
  checks a regex without the warning PCRE raises, which `@` does not stop under a test runner
  or Laravel.
- An arch test keeps `Bpmore\Copydesk\Style` free of Statamic and Laravel.
- 102 passed. Breaking the info mapping and the unknown-key check each fails a test (checked).

### 2026-10-26: Phase 1.2, task 2: `word_list`
- `Style\StyleRule` is the engine's own `Rule`, so one `RuleSet` will run readability and house
  rules together. A finding's rule is the pack's id (hyphens), which cannot collide with the
  engine's handles (underscores). A finding carries one suggestion, the first; the rest stay on
  the definition for the editor's popover.
- `Style\Rules\WordList`: whole words, any case, a single word's inflections, longest phrase wins,
  one finding per occurrence, offsets in bytes. An entry's severity beats the rule's. The
  suggestion follows the author's capitals ("Use" for "Utilize") unless the replacement has its
  own ("eiSEO").
- `Style\Matcher` carries the engine's matching rules over, because the engine's `Phrases` is
  internal. A test runs the engine's `Jargon` and `WordList` on the same text and dictionary and
  requires the same places, so Plain's dictionary finds the same words as a pack (Phase 1.4).

### 2026-10-26: Phase 1.2, task 3: `term`
- `Style\Rules\Term`: the name in any other capitals is flagged, and a many-word name run
  together or hyphenated ("UAMSHealth", "UAMS-Health"). The exact spelling never is, nor a line
  break between its words. Whole words only; the longer name wins where two overlap.
- **Read narrowly:** "near misses" is taken as case and spacing, not typos. A dropped letter is a
  spell checker's job, which the spec leaves to the browser, and edit-distance matching on names
  would flag ordinary words that happen to be close.
- Acceptance 2 is a test: "eiSEO" passes, "EiSEO" and "eiseo" are flagged.

### 2026-10-26: Phase 1.2, task 4: `pattern`
- `Style\Rules\Pattern` runs the pack's regex between slashes (a slash in it escaped), Unicode
  on (`u`, which in PHP also makes `\b` and `\w` Unicode-aware), plus the pack's flags. A match
  of nothing is not a finding. If PCRE gives up on a block (backtrack limit), that block has no
  findings from the rule rather than an error in the editor.
- A pattern that does not compile is refused when the pack loads, in PCRE's own words:
  `house.yaml, rule "broken": "pattern" does not compile: missing closing parenthesis at offset 9.`

### 2026-10-26: Phase 1.2, task 5: `punctuation`, partly blocked. Stopped here.
- Built: `em_dash`, `space_before_punctuation` and `repeated_punctuation` work and are tested.
- **Blocked: `quotes` and `double_space`.** readability-core cleans every block before any rule
  sees it (`Text\Normalizer`, run in `Extraction\Frame`): curly quotes become straight, runs of
  spaces become one, `…` becomes `...`, invisible characters go. So a curly-quotes house gets a
  finding on every quote that is already right (5 false findings on "She said “don’t” and
  ‘go’."), a straight-quotes house never sees a curly one, and double spaces are gone before the
  check looks. Their tests are skipped with this reason; nothing is ticked.
- **The same cause reaches Phase 1.5.** Every finding's position is in the cleaned text, not the
  text in the editor. The cleaning collapses spaces, expands `…`, drops soft hyphens, and the
  structural exclusions (`Addresses`, `References`) cut text out of a block. Any of those before
  a finding moves its underline. Plain never needed exact positions: its panel shows a finding
  inside its own cleaned sentence. Live underlines do.
- **Proposed fix, waiting on Brent:** in readability-core (D1: changes go there), keep each
  block's original text and a map from cleaned positions back to it, through normalising and
  through the exclusions. Released as 0.2.0, additive, so A11y Report is unaffected. Copydesk
  then reads punctuation from the original text and gives every finding an original position.
  About 1 to 2 days, before Phase 1.5 and before `quotes`/`double_space` can be ticked.

### 2026-10-26: Phase 1.2, task 6: `casing`
- `Style\Rules\Casing` checks heading blocks only, one finding per heading that is off, the whole
  heading as the excerpt and the heading rewritten as the suggestion.
- **Added to the schema:** `keep`, words always written as given. Sentence case cannot tell a
  proper name from a capitalised word, so a house names its own ("Statamic", "UAMS Health").
  Also never changed: words in capitals, words with a capital inside ("eiSEO"), words with a
  digit, and "I".
- Sentence case restarts after a colon. Title case keeps short joining words small in the middle
  (a, an, the, and, of, to, in, ...); the first and last words always get a capital. Each part of
  a hyphenated word is a word.
- **For Phase 1.4:** a title field has to reach this rule as a heading block (it arrives as a
  paragraph from the plain-text extractor today), so one rule covers headings and titles.

### 2026-10-26: Phase 1.2, task 7: `sentence`
- `Style\Rules\Sentence` uses the engine's segmenter, `Words::of` and `PassiveVoice::isPassive`,
  all public. The engine's `Sentences` loop is internal, so the few lines of it are written here;
  a test runs the engine's `LongSentence` and this rule at 25 words on the same text and requires
  the same sentences.
- Differs from the engine on purpose: each passive sentence is a finding (the engine reports a
  rate), because a house that writes this rule wants them all; and headings are skipped, as the
  engine's tally skips them. A numeral finding points at the number, not the sentence.
- All six types are now built; `RuleFactory` covers every one.

### 2026-10-26: Phase 1.2, task 8: scope and exceptions
- In `StyleRule`, so every type has them. An exception excuses a finding that lies wholly inside
  text it matches: `eiseo.app` excuses the "eiseo" in the address and nowhere else; a finding
  that only overlaps an exception stands. A literal exception is exact (case and all); a
  `/regex/flags` one is PCRE's. A test runs an exception through each of the six types.
- Scope is `StyleRule::appliesTo($collection, $field)`. A rule sees blocks, not fields, so the
  analyzer (Phase 1.4) asks before running a rule on a field.

### 2026-10-26: Phase 1.2, task 9: packs and how they add up
- **Decided here, as the spec gives no pack format:** a pack is a YAML file named for the pack,
  with optional `description`, `extends`, `disable`, `rules`, and `collections` (per collection,
  its own `disable` and `rules`). Per-collection overrides live inside a pack, so a site keeps
  them in Git next to the rules they change.
- `Style\PackSet` takes the bundled packs, then the site's, refuses two packs with one name
  (both files named), and resolves: extended packs first, in order; then the pack's rules (an id
  already there is replaced in place); then its `disable`; then, for one collection, that
  collection's changes. A pack reached twice is applied once. A circle is refused as "a extends b
  extends a". An extended pack or disabled id that does not exist is refused with the nearest
  real name.
- **For Phase 1.4:** which packs a site has turned on (the roots passed to `rules()`) is the
  addon's call: the site's own packs, or a configured list of bundled ones, by edition.

### 2026-10-26: Phase 1.2, task 10: packs cached
- `Packs\PackLibrary` (a singleton) reads `resources/packs/*.yaml` in the addon, then
  `resources/copydesk/*.yaml` in the site, checks them into a `PackSet`, and keeps it in the
  cache and in memory for the request.
- **Changed from the task as written:** the key is each file's path and *contents*, not its
  modified time. Two saves in one second share a time; a test does exactly that and, keyed by
  time, gets the old rules (checked). Hashing a few small files costs far less than parsing them.
- Acceptance 7 is a test: change a pack, and the next read has the change with no cache cleared.
- A file that is not readable YAML is refused with its path and the parser's reason.

### 2026-10-26: Phase 1.2, task 11: the 150 ms budget
- `tests/Feature/LintBenchmarkTest.php` times everything one check does: extract, exclude,
  tally and grade, every readability rule, every bundled pack, and a heavy stand-in pack with
  all six types and plainlanguage.gov's 177 entries as a word list. Bundled packs join as Phase
  1.3 adds them. The entry is generated (3,264 words, headings, rule hits) from a fixed seed.
- Here: median of five, **98 ms** for 420 findings (PHP 8.4, Apple M-series). About a third of the
  budget is left. Phase 1.2b adds position mapping to every block, which costs something;
  this test will say how much.
- On CI (`CI` set) the time is printed, not held to; locally, over 150 ms fails.

**Phase 1.2 is done except `quotes` and `double_space` (task 5), which wait on Phase 1.2b.**

### 2026-10-26: Phase 1.2b: original text and positions (D5)
- **In readability-core** (branch `written-positions`, for 0.2.0): every block an extractor makes
  keeps `written` (for Bard, inline code included, so it matches the editor's paragraph) and a
  map from each cleaned byte back into it (`Text\Tracked`). `Normalizer::track()` and
  `Tidy::track()` mirror the old functions; `Addresses` and `References` cut and tidy keeping
  track. `Block::writtenSpan()` reads the map; `Block::isRead()` says whether a stretch of the
  written text is read at all. The cleaned text is unchanged: tests hold it to copies of the old
  code on 6,000 generated strings, and every old test passes as it was. The engine also gets its
  own GitHub workflow (it had none once Copydesk stopped testing its copy) and a CHANGELOG.
  Released as readability-core 0.2.0 (PR bpmore/readability-core#1, tagged and on Packagist).
  Its first run on GitHub's Ubuntu found one old test that depended on libxml's version (bare
  text is a paragraph under libxml 2.9, "other" under 2.15); the test now takes either.
- **Found while doing it:** `References` tidies every block, not only ones with a citation, so in
  a real check a space before a comma and a doubled comma were gone before any house rule could
  see them. So `punctuation` reads the written text for all its checks, not only `quotes` and
  `double_space`, skipping what the engine skips (inline code, cut addresses). Its findings are
  positions in the written text; `StyleRule::readsWritten()` says so, and the report uses it.
- **In Copydesk:** every finding in the panel's data has `written`: the block's Bard path and the
  offset and length in characters of the field as written. A test posts a paragraph with curly
  quotes, a double space and a no-break space before "utilize" and gets its true place back.
- **Cost:** the benchmark went from 98 ms to about 112 ms. Still under 150.
- **A11y Report** pinned the engine at `^0.1`, which would have kept it from installing beside
  Copydesk; it now takes `^0.1 || ^0.2` (bpmore/statamic-a11y-report#72), and needs a patch
  release before a site can update both.
- **For Phase 1.4:** the analyzer passes the ids of rules that read written text as the report's
  `writtenRules`; nothing does yet, because house rules are not wired into the analyzer.

### 2026-10-26: Phase 1.3: bundled packs
- `resources/packs/`: `core-punctuation` (four rules, one check each, so any can be disabled;
  quotes left to the house), `plain-language` (plainlanguage.gov's 177 entries, D2, credited in
  the pack's description and the docs; a test holds it entry for entry to the engine's starter),
  `inclusive-language` (46 entries in three rules: roles, people, phrases; every entry has a
  reason, which a test requires; context-dependent ones are `info`), `news-style-starter` (six
  conventions for numbers, times and dates, all `info`).
- **Left out on purpose:** "addict"/"addicts". A single word matches its inflections, so
  "addicted to coffee" would get a clinical suggestion; a house can add it as a phrase.
- `docs/packs.md` (for editors): the shipped packs, writing your own, every rule type, and how a
  newsroom with a licensed stylebook writes its own entries. A test loads every example in it.
- `docs/pack-review.md` (acceptance 9): every bundled entry with a box to tick, generated by
  `composer pack-review` (`bin/pack-review.php`); a test fails if a pack changes and the list is
  not regenerated. **Signed off by Brent on 2026-10-27**, in the session, for all 233 entries.
  The sign-off is kept here, not in the file: the file is generated and a test holds it to the
  generator's output, so ticking its boxes would fail that test.
- A test refuses "AP", "Associated Press" and em dashes anywhere in the pack files.
- Benchmark now runs all four bundled packs plus a smaller stand-in: about 112 ms.
- **For Phase 1.4:** the engine's own `jargon` rule uses the same plainlanguage.gov list. When the
  `plain-language` pack is on, the analyzer must not run `jargon` too, or every word is reported
  twice.

### 2026-10-26: Phase 1.4: one analysis
- `Analysis\HouseRules` gives a check its house rules: the `packs` setting if a site lists
  packs, otherwise core-punctuation, plain-language and inclusive-language plus every site pack
  (news-style-starter is opt-in), resolved for the collection; and the site's own word list.
- **The site's word list is a house rule now, keeping the id `jargon`.** The settings screen's
  rows (and `rules.jargon.dictionary`, if set) become a `word_list` rule: a word with nothing to
  say instead is an `error`, others take `rules.jargon.severity`. Keeping the id means the gate,
  the site report and the CSV read it unchanged; import and export are untouched. The engine's
  own `jargon` rule is no longer run: the plain-language pack carries its starter list, and
  running both reported every word twice. Starter words now report as `plain-words`.
- The analyzer runs house rules field by field, each only where its scope says, and shifts the
  findings into the joined document. Readability rules still see the joined document.
- **The title** reaches casing rules as a heading block, after sections and grades are cut, so it
  is never a section or graded. Skipped when a site lists `title` as a prose field.
- **Targets:** `targets` sets a grade per collection, with the site's tolerance.
- **D3:** every section in the report carries Flesch-Kincaid and reading ease, and the panel
  shows them beside the band. A test holds both to the engine's own numbers (acceptance 5).
  A paragraph whose own band starts more than `paragraphs.above_by` (3) grades past the top of
  the target is marked `hard_paragraph` (warning), once it has `paragraphs.min_words` (40).
- `writtenRules` reaches the report from the house rules, so punctuation findings report their
  place in the text as written (tested with a double space after curly quotes).
- Free and Pro are not split yet (Phase 1.10).

### 2026-10-26: Phase 1.5: in the editor
- **Spike:** Bard hands an extension `tiptap` with `core`, `vue3` and `pm.{state,model,view}`:
  the editor's own ProseMirror. The extension (`resources/js/editor/bard.js`) builds its plugin,
  `Decoration` and `DecorationSet` from those, so there is no second copy to break decorations.
- **The endpoint** (`POST /cp/copydesk/lint`) answers Bard issues by node path and UTF-16
  offset in the block as written. **Found:** a textarea's offsets were counted from its
  paragraph, not the value, so a suggestion would have landed in the wrong place; they now count
  from the start of the value (test with an accent and an empty paragraph).
- **Which fields:** the ones in `fields` config. The listener stamps the list into the panel's
  config (`prose_fields`), and the editor reads it from the blueprint in the publish container.
  A Bard, markdown or textarea field nested in one of them (a Grid, a Replicator, a Bard set) is
  checked too; a nested text field (a caption) is not, as the full check does not read it.
- **Bard:** underlines 600 ms after typing stops, placed in the document as it was when asked
  and carried through anything typed since. Dotted, wavy, double. A click or Option/Alt+Enter
  opens the box; the caret entering an underline is announced in a polite live region. Escape
  and every choice put focus back in the text. A suggestion widens over spaces so ", " for a
  spaced em dash reads "words, more", not "words ,  more".
- **Markdown, textarea, text:** Statamic's own fieldtypes, wrapped in `Statamic.configuring`,
  with the list after them. The wrapper hands the fieldtype's own properties through, so field
  actions (the markdown expand button) still show. Markdown has no positions, so its list says
  "Try: use" instead of a button.
- **Sidebar:** "As you type" in the readability panel: counts by severity, and every issue as a
  button that puts the caret on its words (and opens the box, in Bard).
- **Checked in a browser** on a greenhouse copy with this branch linked (fixture and steps in
  `docs/editor.md`): an em dash typed in Bard is underlined about 0.8 s after typing starts
  (acceptance 1); the Bard inside a Replicator inside a Grid is underlined and its issue jumps
  from the panel (acceptance 8); a suggestion replaces the text in Bard and in a textarea; no
  console errors on the test page, an article and the globals screen.
- **Found in the browser:** the box closed itself whenever the page scrolled, and Bard scrolls
  the caret into view after a click, so it never stayed open: it now follows its words. An old
  box's listener could close a new box: each now closes only its own. Statamic's textarea sets
  its `focus` prop as a DOM property, so `textarea.focus` is undefined: focused through the
  prototype.
- **For Phase 1.6:** "Ignore here" hid the issue only while the form was open. Done in 1.6.
- **Not done here:** the full check (panel grade, gate, scan) still reads only top-level Bard,
  markdown, textarea and text fields, so a Bard in a Grid is underlined as you type but not
  counted by the gate. Worth deciding before 1.7. **Done after Phase 1, 2026-10-27:**
  `FieldText::read` walks Grid, Replicator, Group and Bard sets for the same fields the editor
  checks. A nested Bard in a Bard set is still read by the engine; every other field in a set is
  taken out of the tree and read by its type, so nothing is read twice and every path stays the
  same. Nested blocks carry their set handles, so `bard_sets` exclusions reach them too.
- JS tests run with Node's own runner (`npm test`, in CI before the build): the path-to-position
  arithmetic and the spacing rule.

### 2026-10-26: Phase 1.6: ignores
- A fingerprint is the rule id and the first 12 hex of a SHA-1 of the sentence the finding is
  in, spacing folded (`Analysis\Fingerprint`). The editor's endpoint and the full check make it
  from the same blocks, so they match: a test says so, and in the browser the panel's full check
  counted the two ignores the editor had made.
- Kept in `copydesk_ignores`, a hidden `list` field the panel listener adds to every form that
  gets the panel. "Ignore here" writes it through the publish container, so it is saved with the
  entry like any other change (checked: saved, reloaded, still ignored).
- The analyzer leaves ignored findings out before the report is made, so the panel, the gate and
  the scan all skip them, and counts them (`summary.ignored`). The panel says how many are
  ignored, and has "Stop ignoring", which empties the list; every field asks again.
- An ignore holds while other sentences change and while spaces are retyped, lapses when its own
  sentence changes, and never hides a different rule in the same sentence (tests for each).
- **For Phase 1.8:** the site report should show `ignored` per page; the count is on the report
  already.

### 2026-10-26: Phase 1.7: the gate, tasks 1 and 2
- **Blocks on `error`**, nothing else (`PublishGate::blocking`). Plain 1.0's `block_on` is gone
  and no longer read; its two rules were already errors (very long sentence, a site word with
  nothing to say instead), so a site with the gate on blocks on what it did before, plus any rule
  its packs mark `error`. Pro only: on Free the gate never runs (test). README and config say so.
- **Publish anyway:** `POST /cp/copydesk/override` with a reason (three words at least) gives a
  pass in the cache for that person and that entry (or their next new page in the collection),
  ten minutes. The gate only *looks* at the pass; `SettleOverrides` (on `EntrySaved`) uses it up
  and writes the override to `storage/statamic/copydesk/overrides.jsonl` with the page, person,
  rules and reason. **Changed from the plan:** the plan had the pass spent by whichever gate ran
  and A11y Gate calling `recordOverride` on its contributors. Spending it on `EntrySaved` instead
  means a publish another gate refuses never wastes it, and A11y Gate needs no override hook
  from Copydesk at all: each add-on owns the pass for its own problems.
- The panel shows "Publish anyway" under the refusal to people with the permission, with a
  labelled reason box. The Readability utility lists "Published past the gate", newest first,
  with the reason (acceptance 4, tested through the utility page).
- **Checked in a browser** on the greenhouse copy: a 60-word sentence refused the publish with
  the rule named; "Publish anyway" with a reason; the next save went through and the override
  was logged.

### 2026-10-26: Phase 1.7, task 3: one publish check with A11y Gate
- **In A11y Gate** (branch `gate-contributors`, for 0.11.0, not pushed): `Contracts\GateContributor`
  (`key`, `label`, `inspect`) and `ContributedResult` (`passes`, `blocks(lines)`), tag
  `a11y-gate.contributors`. `RefuseUnlessAccessible` asks every contributor in the same save and
  throws one `ValidationException` with each one's lines under its own key beside `a11y_gate`.
  A contributor that throws is logged and left out; `warn` mode covers only A11y Gate's own
  checks. Six tests with a fake contributor; its whole suite passes (141). README and CHANGELOG.
- **In Copydesk:** `CopydeskGateContributor` answers with the gate's own result and lines.
  `A11yGateBridge` signs it up only when A11y Gate's contract *and* its listener exist, and then
  `RefuseUnlessPlain` stands aside. An A11y Gate without the contract leaves Copydesk gating on
  its own (test). Copydesk never requires A11y Gate: everything is by name; a test stub of the
  contract (`tests/Stubs`) is word for word the real one.
- **Not built, from the plan:** A11y Gate's own "publish anyway" (`override accessibility gate`,
  `allow_override`). Copydesk's override no longer needs anything from A11y Gate (see task 2),
  and A11y Gate's own override is a feature of A11y Gate, for its own release.
- **Checked on the greenhouse copy** with both branches linked: Copydesk is tagged, a save with a
  60-word sentence came back as one 422 with the `copydesk` key, through A11y Gate. Both lists in
  one refusal is covered by A11y Gate's test, not seen in the browser: this page has no
  accessibility error to show.
- **Found in the browser:** the panel's jump button showed the whole 60-word sentence; the
  excerpt is now cut at about 30 characters.

### 2026-10-26: Phase 1.8: site report and command
- **Queued scan:** the page under Tools starts a scan as `ScanChunk` jobs of 50 pages. Each job's
  part is kept in the cache; the last one in puts the report together once (`Cache::add` guards
  two finishing at the same moment), caches it, and records it in the trend. A scan started again
  makes older jobs stand down. With the sync queue (most sites) it all happens before the page
  draws. The command still scans in one go.
- **Progress:** `copydesk-scan-progress` asks every two seconds ("Scanning: 50 of 88 pages
  done.", in a polite live region) and reloads into the report when done. **Checked on the
  greenhouse copy with a database queue:** 88 pages in 2 jobs, progress shown, the page switched
  to the report by itself (61 graded, 27 with nothing to grade).
- **The report:** issues by rule (errors first), by collection, and the pages with the most to
  fix, each with ignored counts; overrides with reasons (from 1.7); "Download every issue (CSV)",
  a row per page and rule. Short pages with issues are now in the report too: before, a page too
  short to grade was dropped even when it broke house style.
- **Command:** `--fail-on=error|warning|tip` exits 1 when a published page has a finding at or
  above it, 0 otherwise (acceptance 6), and 2 for a severity it does not know. `--fail-on-errors`
  stays, as `--fail-on=error`. `--fail-above` and `--export` stay; the CSV export includes short
  pages with issues.
- A scan cached by the previous version still reads: new fields have defaults.

### 2026-10-26: Phase 1.9: rule editor
- **Statamic's own publish form** (`Statamic\CP\PublishForm`) with a blueprint built in code
  (`Packs\PackForm`): description, extends (checkboxes of the other packs), disable, and a
  Replicator with one set per rule type, each with only its own keys. A rule's `id` is
  `rule_id` in the form, because a Replicator row has an `id` of its own. A rule switched off
  in the Replicator goes into `disable`. The `collections` section is kept from the file as it
  is; the form does not show it, and says so.
- **Saving** checks the pack as Copydesk reads it (`Pack::fromArray`, then the whole set with
  this pack in it, so a bad `extends` or a circle is caught) and refuses with the reason; the
  file is untouched on a refusal (test). Written with Symfony YAML: comments do not survive,
  and the form says so.
- **Try it on some text:** a sidebar fieldtype (`copydesk_rule_tester`) posts the unsaved
  form and some text, and lists what the pack would flag.
- `manage copydesk rules` ("Edit house style rules") and Pro, both required (test). "House
  style packs" on the Readability page lists the packs, with "Edit the rules" and "Start a
  pack". A site pack is on by default (`packs: null`), so a new pack works at once.
- **Round trip** of every kind of rule from docs/packs.md, through the form and back, equal
  (test). **Checked in a browser:** started a pack, added a term rule, tried it ("Eiseo"
  flagged), saved, and the YAML on disk was right. A rule saved with an empty list was refused
  by the form's own "required" before reaching the server.

### 2026-10-26: Phase 1.10: ready to ship
- **Permissions:** `view copydesk report` (the site report and its CSV), `manage copydesk
  rules`, `override copydesk gate`, beside the three Plain had. Statamic gives them to nobody
  but super admins until a role has them (test lists all six).
- **Free and Pro:** Free runs punctuation and term rules from any pack, the readability rules
  and grade, the site's word list, and the editor. Pro adds every other pack and rule kind, the
  gate, the rule editor, the site report (the utility page says why it is missing, Free or no
  permission) and the Site Weather signal. **Decided here, worth a look:** the site's word list
  (Plain's dictionary) stays in Free; the spec's Free list does not name it, and taking away
  what Plain gave for free seemed wrong. Tests run as Pro where they use Pro features, and
  `EditionSplitTest` holds the line.
- **Accessibility:** axe-core 4.11 (a local copy; the CDN was blocked here) on the entry form,
  the entry form with the box open, the Readability page and the rule editor: no violation in
  any Copydesk element. The page's own (unnamed asset button, grid header cells, low-contrast
  set labels, a popover trigger's attribute) are Statamic's. **Keyboard:** from the sidebar, Tab
  reaches the panel's jump buttons; Enter puts the caret on the words; Option+Enter moves focus
  into the box; Tab moves through it; Escape returns to the text. **Found:** Tab cannot leave
  Statamic's markdown field (CodeMirror keeps it), which is Statamic's, not Copydesk's.
- **Docs:** README (house style, the editor, editions, A11y Gate, permissions, `--fail-on`),
  CHANGELOG (everything in Phase 1, and the `block_on` change), `docs/marketplace.md` (listing
  draft and the screenshots to take), `docs/packs.md` (the editor).
- **U+2014:** only in the punctuation rule, the specs, tests and docs that show the rule, and
  two of Brent's tooling files (`.claude/settings.json`, `run-build.sh`), left as they are.
- **Greenhouse:** installed beside the other 20 bpmore addons: route names all unique, no
  permission handle shared, no other addon hooks Bard or replaces a core fieldtype, and no
  console errors on the screens checked.

