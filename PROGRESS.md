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

- [ ] The analyzer runs readability rules and style rules together, each rule only where its `scope` says, and returns one list of findings with character offsets.
- [ ] Targets per collection in config (`targets: ['patient_education' => 8]`), falling back to the site target.
- [ ] Plain's dictionary becomes the site's `word_list` pack: what the settings screen saves is read as a pack, so nothing a site entered is lost. Import and export keep working.
- [ ] Readability details (D3): Flesch-Kincaid and reading ease beside the band, per page and per section, from `readability-core` (acceptance 5).
- [ ] A paragraph that reads well above target is marked (D3). How far above counts as "well above" is a config value, with the default and its reason in the config file.

## Phase 1.5: In the editor

- [ ] **Spike first.** Prove a Bard extension added through `Statamic.$bard.addExtension` can draw ProseMirror decorations using the editor's own ProseMirror, not a second bundled copy (a second copy breaks decorations silently). Write down what was found. If it cannot, stop and say so here before the next task.
- [ ] `POST /cp/copydesk/lint`: one field's text in, issues with character offsets out. Needs the same permission and entry access as the panel's check.
- [ ] Bard underlines, debounced 600 ms per field: dotted for info, wavy for warning, double for error, never colour alone. Acceptance 1: an em dash typed in Bard is underlined within a second.
- [ ] The popover: message, suggestion buttons that replace the text, "Ignore here". Keyboard reachable, announced to screen readers, focus returned to the text after.
- [ ] Markdown, textarea and text fields (D4): the field's issues as a list under it, debounced the same way, with the same suggestions and "Ignore here".
- [ ] Bard nested in a Replicator set inside Grid (acceptance 8), with a fixture for it.
- [ ] The sidebar panel: grade against target, issue counts by severity, a link that jumps to each issue.

## Phase 1.6: Ignores

- [ ] "Ignore here" stores a fingerprint (rule id plus a hash of the sentence) in a hidden `copydesk_ignores` field on the entry. A changed sentence no longer matches, so the ignore lapses.
- [ ] Ignored findings are left out of the editor and the gate, and counted in the report so they cannot hide problems quietly.

## Phase 1.7: The gate (Pro)

- [ ] Blocks a publish on any finding whose severity is `error`. Never on a grade. Drafts always save. Fails open when the check itself breaks, as now. The message names each failing rule (acceptance 3).
- [ ] `override copydesk gate`: "Publish anyway" with a required reason, through the one-time pass in `docs/a11y-gate-plan.md`. The reason is logged and shown in the report (acceptance 4).
- [ ] A11y Gate: the contributor contract in A11y Gate (its own repo and PR), then Copydesk signing up to it and standing down its own listener when the contract is there. As planned in `docs/a11y-gate-plan.md`.

## Phase 1.8: Site report and command

- [ ] The site scan runs as queued jobs of 50 entries, with progress on the report page, instead of inside the web request.
- [ ] The report: issues by collection, by rule and by page; ignored counts; overrides with their reasons; CSV export.
- [ ] `copydesk:scan {--site=} {--collection=} {--fail-on=error}`: exit 1 when any published entry has a finding at or above that severity, 0 otherwise (acceptance 6). The existing `--fail-above` and `--export` stay.

## Phase 1.9: Rule editor (Pro)

- [ ] A form-based editor for site packs: add, edit and disable rules, and try a rule on sample text before saving. Writes YAML. Needs `manage copydesk rules`.

## Phase 1.10: Ready to ship

- [ ] Permissions as the spec lists them (`view copydesk report`, `manage copydesk rules`, `override copydesk gate`) alongside the existing ones; none granted to anyone but super admins by default.
- [ ] Free and Pro split as the spec lists it, through the editions helper.
- [ ] Every CP screen: an axe scan and a keyboard-only walkthrough.
- [ ] README and editor docs for Copydesk; CHANGELOG; `docs/marketplace.md`; a grep for U+2014 that finds only rule definitions and the specs.
- [ ] Installed on greenhouse beside every other bpmore addon: no clashes in route names, permission handles, nav sections or JS globals.

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
  not regenerated. **Sign-off by Brent is still to do**; nothing can tick that for him.
- A test refuses "AP", "Associated Press" and em dashes anywhere in the pack files.
- Benchmark now runs all four bundled packs plus a smaller stand-in: about 112 ms.
- **For Phase 1.4:** the engine's own `jargon` rule uses the same plainlanguage.gov list. When the
  `plain-language` pack is on, the analyzer must not run `jargon` too, or every word is reported
  twice.

