# PROGRESS — Plain

**Spec:** `SPEC.md` · **Packages:** `bpmore/readability-core` + `bpmore/statamic-plain` · **$39 standalone, bundled free in A11y Report**
**Rule:** do the next unfinished task only, then update this file, verify the build, and commit.

## Phase 1 — Core engine (no Statamic)
- [x] Scaffold `readability-core`, composer-installable and testable standalone
- [ ] Text extraction + normalization: HTML/Bard → analyzable plain text
- [ ] Exclusion pipeline: code blocks, blockquotes, references, proper names, addresses, ignore-classes
- [ ] Sentence segmentation that survives "Dr. Smith went to St. Louis Inc." — evaluate a real segmenter before hand-rolling
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
