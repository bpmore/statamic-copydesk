# Spec: Copydesk (house style and plain-language linter)

Working name: Copydesk. Package: `bpmore/statamic-copydesk` unless Phase 0 decides it ships as the next major version of Plain.

## Summary

Copydesk checks editorial text against a style guide and a target reading level while editors write, and can block publishing when content breaks rules marked as errors. The core is deterministic: rule packs plus readability formulas, no AI required, no content leaves the site.

## Phase 0: reconcile with Plain (do this first)

A private addon already exists: `bpmore/statamic-plain`, built on `bpmore/readability-core`. Before writing code:

1. Read both repos end to end. List what Plain already does (scoring, UI surfaces, storage, gating).
2. Write `docs/decision.md` choosing one of:
   - **A. Merge:** Copydesk becomes Plain 2.0 (Plain gains style rules). Recommended if Plain already has the editor UI and scoring.
   - **B. Layer:** Copydesk is a separate addon that depends on `readability-core` and reuses Plain's scoring service.
3. Do not duplicate readability math. All grade and ease calculations come from `readability-core`; fix bugs there.
4. Stop and ask Brent to confirm the decision before Phase 1.

The rest of this spec describes the combined product regardless of packaging.

## Users and jobs

- **Editor:** "Tell me while I type that I broke house style, and how to fix it."
- **Content lead:** "Set our rules once, see which pages break them, and stop the worst problems from going live."
- **Compliance lead (health, government):** "Prove patient-facing pages are written at grade 8 or below."

## Scope: v1

### Rule engine (plain PHP, no Statamic dependency)

Rule packs are YAML files. Each rule:

```yaml
id: no-em-dash
type: pattern            # word_list | pattern | term | casing | punctuation | sentence
pattern: '—'
message: "Use a colon, comma, parentheses, or two sentences instead of an em dash."
suggestions: [", ", ": "]
severity: error          # info | warning | error
scope:
  collections: ['*']
  fields: ['*']
exceptions: []           # literal strings or regex that suppress the rule
```

Rule types:

- `word_list`: flag listed words or phrases, each with an optional preferred replacement (for example "utilize" becomes "use").
- `term`: enforce exact spelling and casing of names ("UAMS Health", "Statamic", "eiSEO"); flag near misses case-insensitively.
- `pattern`: regex with Unicode support.
- `punctuation`: built-in checks (em dash, double spaces, straight vs curly quotes as configured, space before punctuation, repeated punctuation).
- `casing`: heading case style (sentence case or title case) applied to Bard headings and title fields.
- `sentence`: sentence length over N words, passive voice heuristic, sentences starting with numerals.

Packs can extend other packs and disable individual rules. Rule resolution order: bundled packs, then site packs in `resources/copydesk/*.yaml`, then per-collection overrides.

### Bundled packs (all wording original, see legal note)

- `core-punctuation`
- `plain-language`: complex word to simpler word pairs, written from scratch.
- `inclusive-language`: written from scratch, each entry with a short reason.
- `news-style-starter`: a small set of widely known newsroom conventions (numerals, percent, time format, dates), described in our own words. Named generically, not "AP".

**Legal note:** The AP Stylebook is copyrighted and trademarked. Do not copy its entries, examples or wording, and do not call any bundled pack "AP." Customers with an AP license can write their own pack; the docs show how.

### Readability (from readability-core)

- Flesch-Kincaid grade level and Flesch reading ease per entry and per paragraph.
- Target grade per collection in config (for example `patient_education: 8`). Show pass or fail against target.
- Strip non-prose before scoring: code blocks, tables, lists of links, set fields marked as non-prose in config.

### Where it runs

- **Live in the editor:** Bard, Markdown, textarea and text fields. The field sends text to a CP endpoint (`POST /cp/copydesk/lint`, debounced 600 ms, per field). Server runs the engine and returns issues with character offsets. Bard shows TipTap decorations (underline by severity, never color alone: dotted for info, wavy for warning, double for error) with a popover: message, suggestion buttons, "Ignore here."
- **Sidebar panel** on the entry publish form: reading grade vs target, issue counts by severity, jump to each issue.
- **On save:** `EntrySaving` runs the full lint. If any `error` rule fails and the entry is being published, block the save with a validation message listing the issues. Drafts always save. If A11y Gate is installed, register Copydesk as a Gate check instead of a second gate (see conventions).
- **Site report:** CP utility that scans all entries in the background, shows issues by collection, rule and page, and exports CSV.
- **Artisan:** `copydesk:scan {--site=} {--collection=} {--fail-on=error}` exits non-zero for CI.

### Ignores

"Ignore here" stores a fingerprint (rule id plus hash of the surrounding sentence) on the entry in a hidden field `copydesk_ignores`. If the sentence changes, the ignore expires. Report shows ignored counts so they cannot hide problems silently.

### Permissions

- `view copydesk report`
- `manage copydesk rules` (edit packs in the CP)
- `override copydesk gate` (publish despite errors, with a required reason that is logged)

### Rule editor (CP)

Form-based editor for site packs: add, edit, disable rules; test a rule against sample text before saving. Writes YAML.

## Scope: Pro edition

- Optional AI rewrite: "Rewrite this sentence to meet the rule and grade target." Provider-agnostic (OpenAI-compatible and Anthropic), key in `.env`, off by default, shows the diff before applying, never sends the whole entry unless the user chooses "rewrite paragraph."
- Scheduled report emails to content leads.
- Site Weather signal: percent of pages meeting grade target, error count trend.

## Non-goals

- Spell checking (browsers do it).
- Grammar correction beyond the listed heuristics.
- Translating or rewriting automatically without a person approving.

## Performance

- Lint a 3,000-word entry in under 150 ms server-side.
- Cache compiled rule packs; invalidate on pack file change.
- Site scan runs in queued chunks of 50 entries.

## Acceptance criteria

1. An em dash typed in Bard is underlined within one second and the popover offers a replacement.
2. A `term` rule for "eiSEO" flags "EiSEO" and "eiseo" but not "eiSEO".
3. Publishing an entry with an `error` rule failure is blocked with a message naming each failing rule; saving it as a draft succeeds.
4. A user with `override copydesk gate` can publish after entering a reason; the reason appears in the report.
5. Reading grade shown in the sidebar matches `readability-core` output for the same text.
6. `copydesk:scan --fail-on=error` exits 1 when any published entry has an error, 0 otherwise.
7. Changing a rule pack YAML takes effect without clearing caches manually.
8. Works on a Bard field nested inside a Replicator set inside Grid.
9. All bundled pack text passes a review that no entry copies AP Stylebook wording.

## Editions and price

- Free: punctuation pack, term rules, readability score, editor underlines.
- Pro ($79): all packs, rule editor, publish gate, site report, scheduled emails, AI rewrite, Site Weather signal.

## Open questions for Brent

1. Merge into Plain or layer on it (Phase 0)?
2. Should the UAMS house style ship as a private pack repo, like the Form Marshal policy pack?
