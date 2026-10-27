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

## Phase 1.1: Housekeeping

- [x] One engine copy (D1): require `bpmore/readability-core` from Packagist, path repository to `../readability-core` in development, remove `packages/readability-core/` and the CI job that tests it there (the engine's own repo tests it).
- [ ] A Vite build, set up the way `bpmore/statamic-lifecycle` has it (Vite config, `$vite` in the service provider, built bundle committed under `resources/dist`, a CI check that the committed build is current). The panel script moves into it unchanged; its tests still pass.
- [ ] Editions: `free` and `pro` in `composer.json` `extra.statamic.editions`, one helper that answers "is this Pro", and a test for each edition. Nothing is gated yet; later tasks use the helper.

## Phase 1.2: The style rule engine (plain PHP, no Statamic)

Lives in `src/Style/`, implements the engine's `Rule` interface so one `RuleSet` runs readability rules and style rules together. An arch test keeps `Bpmore\Copydesk\Style` free of `Statamic\` and `Illuminate\`. YAML is parsed at the edge; the engine takes arrays.

- [ ] The rule schema from the spec (`id`, `type`, `message`, `suggestions`, `severity`, `scope`, `exceptions`, plus each type's own keys), validated with errors that name the pack file and the rule id. `info` maps to the engine's `tip`.
- [ ] `word_list`: words and phrases, each with an optional replacement; whole words, any case, longest phrase wins. Each entry may carry its own severity, so a banned word can be an error and a suggestion a warning.
- [ ] `term`: exact spelling and casing of a name; near misses flagged case-insensitively. Acceptance 2: "eiSEO" is not flagged, "EiSEO" and "eiseo" are.
- [ ] `pattern`: Unicode regex. A pattern that does not compile is refused at load with the rule id, never at lint time.
- [ ] `punctuation`: em dash, double spaces, straight vs curly quotes (configured per pack), space before punctuation, repeated punctuation.
- [ ] `casing`: sentence case or title case for headings and title fields.
- [ ] `sentence`: over N words, the passive-voice heuristic, a sentence that starts with a numeral. Reuses the engine's long-sentence and passive rules where they already do this, rather than a second copy.
- [ ] `scope` (collections, fields) and `exceptions` (literal or regex), on every type.
- [ ] Pack resolution: bundled packs, then site packs in `resources/copydesk/*.yaml`, then per-collection overrides; `extends`, disabling a rule by id, and a refusal (naming both packs) for a cycle.
- [ ] Compiled packs cached, keyed by each pack file's path and modified time, so editing a YAML file takes effect on the next lint with no cache clear (acceptance 7).
- [ ] A benchmark test: lint a 3,000-word entry with every bundled pack on in under 150 ms. Reported, not failed, on CI, whose machines vary; failed locally.

## Phase 1.3: Bundled packs

All wording original. Nothing copied from the AP Stylebook, and no pack named "AP".

- [ ] `core-punctuation`.
- [ ] `plain-language` (D2): complex word to simpler word pairs, from plainlanguage.gov's public-domain list, credited in the pack and the docs.
- [ ] `inclusive-language`: written from scratch, each entry with a short reason.
- [ ] `news-style-starter`: a small set of widely known newsroom conventions (numerals, percent, time, dates), described in our own words.
- [ ] Docs page showing how a site with an AP licence writes its own pack.
- [ ] A review list for acceptance 9: every bundled entry, for Brent to sign off that none copies AP wording. Machine checks cannot decide this; a person does.

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

