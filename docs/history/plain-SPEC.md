# Plain — Build Spec

**For:** handoff to Claude Code
**Date:** September 8, 2026
**Product:** `bpmore/statamic-plain` — readability and plain-language checking for Statamic 6
**Packaging:** $39 standalone addon, **and** bundled free inside A11y Report
**Behavior:** advisory by default, gating opt-in

---

## 1. Get the WCAG claim right, because it shapes the whole pitch

**SC 3.1.5 Reading Level is Level AAA, not AA** — and it is not satisfied by making text simpler. It's satisfied by *providing a supplemental version or supplemental content* when text exceeds lower secondary reading level, after removing proper names and titles.

So Plain must never say "this page fails WCAG." It doesn't. Two consequences:

1. In A11y Report's criteria worksheet, 3.1.5 is a **AAA** row, reported separately from the AA conformance claim, and Plain's score is *evidence toward* it, never a pass/fail verdict.
2. The product's primary framing is **plain language and health literacy**, not WCAG. That's a stronger pitch anyway — the buyers who care (health systems, government, universities) are driven by plain-language mandates and patient comprehension, not by a AAA criterion nobody claims.

Anchor the positioning on **ISO 24495-1:2023**, the international plain language standard, whose four governing principles map cleanly onto your rule categories: readers can find what they need, understand what they find, and use what they understand.

---

## 2. Packaging

Three packages so the same engine serves both distributions without cross-entitlement machinery, which the Statamic marketplace doesn't offer.

```
bpmore/readability-core     Framework-agnostic PHP. Text extraction interfaces,
                            formulas, rule engine, findings. No Statamic.

bpmore/statamic-plain       The $39 addon. Publish-form panel, opt-in gate,
                            config, CP screens, Antlers tags.

A11y Report                 Adds readability-core as a dependency and exposes
                            readability as a scan dimension + the 3.1.5 row.
```

A11y Report customers get readability because their addon depends on the engine. Anyone who wants readability without the conformance suite buys Plain for $39. No license bridging required.

**If both are installed** they don't conflict — they're different surfaces. Plain owns the publish-form panel and the gate; A11y Report owns the scheduled site-wide scan and the criterion row. Detect A11y Report and show a one-line CP notice explaining the division so nobody thinks it's double-scanning.

---

## 3. The engine

**Formulas.** `davechild/textstatistics` (Composer, PHP 7.2+) provides Flesch Reading Ease, Flesch-Kincaid Grade, Gunning Fog, Coleman-Liau, SMOG, ARI, Dale-Chall and Spache.

**Recommendation: use it for syllable counting only, and implement the formula layer yourself.** Syllable counting is the genuinely hard part and the part that library solves well. The formulas themselves are arithmetic, and owning them means you control rounding, can report multiple grades consistently, and aren't blocked if that library goes unmaintained. Wrap it behind a `SyllableCounter` interface so it's swappable.

**Report a band, not a number.** A single decimal grade implies precision these formulas don't have. Show "Grade 9–10" with the target band, and put the raw scores behind a details toggle. This one decision will prevent most of your support arguments.

**English only, and say so loudly.** Every one of these formulas is calibrated on English. Running them on German or Spanish produces confident nonsense. Read the site's locale; if it isn't English, disable analysis for that site with an explicit message rather than silently scoring. This is a correctness issue, not a nicety.

---

## 4. What gets analyzed

The hardest design problem here is deciding what text counts. Get this wrong and every score is garbage.

**Configurable per blueprint** — an allowlist of fields (Bard, markdown, textarea, text). Default to the primary content field only.

**Exclusions applied before scoring**, all configurable:

- Code blocks, preformatted text
- Blockquotes (you're scoring your author, not the person quoted)
- Reference lists, citations, bibliographies — these will wreck a score
- Proper names and titles (SC 3.1.5 explicitly excludes them)
- Addresses, phone numbers, tables of figures
- Anything inside a configured "ignore" HTML class or Bard set

**Score at two levels.** Whole-document, and per-section by heading. A page can average grade 8 while the paragraph that actually matters sits at grade 16, and only the per-section view surfaces that.

---

## 5. Rules beyond the formulas

The formulas are the headline; these are what actually make writing better.

| Rule | Detects | Default |
|---|---|---|
| Long sentences | Sentences over N words (default 25) | Warn |
| Very long sentences | Over 2N words | Error-level flag |
| Long paragraphs | Over N words or N sentences | Warn |
| Passive voice | "to be" + past participle heuristic | Warn, with a per-site rate threshold rather than per-instance |
| Unexpanded acronyms | An acronym used without a prior expansion on the page | Warn |
| Jargon / banned phrases | Site-configurable dictionary with suggested replacements | Warn |
| Nominalizations | "-tion/-ment/-ance" nouns that hide a verb | Tip |
| Hedging and filler | "in order to", "utilize", "please be advised" | Tip |

**Passive voice needs a rate, not a count.** Some passive is correct English. Flagging every instance trains authors to ignore the panel. Flag when the document exceeds a configured percentage.

**The jargon dictionary is the sticky feature.** A health system's list of forbidden internal acronyms is institutional knowledge they'll spend an afternoon building and then never leave. Ship a starter dictionary (federal plain-language substitutions), make it editable in the CP, make it exportable and importable, and make it per-site.

---

## 6. Behavior — advisory by default

**Publish-form panel** showing: grade band against target, per-section grades, flagged sentences with inline highlighting, and the specific rule hits. Same plain-words voice as A11y Gate — say what to do, not which rule fired.

**Gating is opt-in, per collection**, and even when enabled it should only block on the hard structural rules (very long sentences, banned phrases), never on a grade score alone. A grade band is a heuristic; blocking a press release because it scored 11.2 is how you generate your worst support tickets and get uninstalled. Make this the documented default and explain why in the docs — the restraint is a selling point to the comms directors who will otherwise veto the addon.

---

## 7. Control panel and templating

- **Publish-form panel** (the main surface)
- **Utility screen**: site-wide readability distribution, worst offenders by collection, acronym and jargon frequency across the site
- **Dashboard widget**: median grade, trend, count above target
- **Jargon dictionary editor**
- **Permissions**: `view readability`, `manage jargon dictionary`, `configure readability`

**Antlers tag** `{{ plain:score }}` so a site can surface a reading level on the page itself — some health systems want this visible to patients.

**Command**: `php please plain:check {--site=} {--collection=} {--export=csv}`, exit non-zero above a configured threshold for CI.

---

## 8. Config sketch

```php
return [
    'enabled' => true,
    'locales' => ['en', 'en_US', 'en_GB'],   // analysis skipped outside these
    'target'  => ['grade' => 8, 'tolerance' => 1],
    'fields'  => ['*' => ['content'], 'article' => ['content', 'summary']],
    'exclude' => [
        'bard_sets' => ['code', 'citation'],
        'html_classes' => ['no-readability'],
        'blockquotes' => true, 'references' => true,
    ],
    'rules' => [
        'long_sentence' => ['words' => 25, 'severity' => 'warning'],
        'very_long_sentence' => ['words' => 50, 'severity' => 'error'],
        'long_paragraph' => ['words' => 150, 'severity' => 'warning'],
        'passive_voice' => ['max_percent' => 20, 'severity' => 'warning'],
        'unexpanded_acronym' => ['severity' => 'warning'],
        'jargon' => ['dictionary' => 'default', 'severity' => 'warning'],
    ],
    'gate' => [
        'enabled' => false,
        'collections' => [],
        'block_on' => ['very_long_sentence', 'jargon'],  // never on grade alone
    ],
];
```

---

## 9. First session for Code

1. **`readability-core` scaffolded and testable standalone.** No Statamic.
2. **Text extraction and normalization** — HTML/Bard to analyzable plain text with the exclusion pipeline. This is where the accuracy lives; do it carefully and test it against real messy content.
3. **Formula layer** over a `SyllableCounter` interface, with `davechild/textstatistics` as the first implementation. Unit tests with known reference texts and their published scores.
4. **Rule engine** with the eight rules above as discrete, individually testable rule classes.
5. Stop. Everything after this is Statamic surface work and is much easier once the engine is trustworthy.

---

## 10. Open questions

- **Sentence segmentation** is harder than it looks — "Dr. Smith went to St. Louis Inc. yesterday." Naive period-splitting will produce wrong sentence counts, which corrupts every formula. Evaluate a proper segmenter before hand-rolling one.
- **Bard traversal**: score the rendered HTML or walk the Bard node tree? The node tree gives better exclusion control and lets you map a finding back to a specific set for inline highlighting. Probably worth it.
- **Dale-Chall and Spache** need word lists and are US-education-specific. Include or skip for v1?
- **Per-section scoring** needs heading-based document chunking; confirm that works reliably across Bard, markdown and raw HTML fields.
- **PHP floor** — don't repeat A11y Report's 8.4 requirement here unless something needs it. This engine has no reason not to run on 8.2.

---

## Sources

- [WCAG 2.1 SC 3.1.5 Reading Level (AAA)](https://www.w3.org/WAI/WCAG22/Understanding/reading-level.html)
- [ISO 24495-1:2023 Plain language — governing principles](https://www.iso.org/standard/78907.html) · [PLAIN summary](https://plainlanguage.com/what-is-plain-language/iso-plain-language-standard/)
- [DaveChild/Text-Statistics](https://github.com/DaveChild/Text-Statistics)
