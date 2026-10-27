# House style packs

Copydesk checks writing against rules kept in packs: plain YAML files, in
your site's repository, that anyone on the team can read.

## The packs Copydesk ships

| Pack | What it checks |
|---|---|
| `core-punctuation` | Em dashes, two spaces between words, a space before punctuation, and repeated marks like "!!". |
| `plain-language` | 177 complex words and phrases, each with a plainer one, from plainlanguage.gov (US federal work, public domain). |
| `inclusive-language` | Words that name a gender instead of a role, describe people by a condition, or carry history the writer may not intend. Each comes with a reason. |
| `news-style-starter` | A few conventions most newsrooms share, for numbers, times and dates. |

## Your own pack

Put a file in `resources/copydesk/` in your site, named for the pack:
`resources/copydesk/house.yaml` is the pack `house`. Copydesk reads it the
next time anyone checks a page. There is no cache to clear.

```yaml
description: Our house style.

# Start from the packs Copydesk ships, then change them.
extends: [core-punctuation, plain-language]

# Turn off a rule from a pack you extend, by its id.
disable: [no-em-dash]

rules:
  - id: house-names
    type: term
    terms: [Greenhouse Institute, MyChart]
    message: Spell our names the way we do.
    severity: error

  - id: house-words
    type: word_list
    message: We say it this way.
    words:
      utilize: use
      please be advised that: ~        # nothing instead: say it another way
      patient portal:
        replace: [MyChart]
        reason: Patients know it by name.
        severity: warning

# Changes for one collection only.
collections:
  patient_education:
    rules:
      - id: short-sentences
        type: sentence
        check: max_words
        max_words: 20
        message: Patients read these pages under stress. Keep sentences short.
```

A rule with the same `id` as one in a pack you extend replaces it. A
misspelt key, an unknown rule id or a pattern that does not work is
refused with the file and the rule named, so a mistake never quietly turns
a rule off.

## Editing a pack in the control panel (Pro)

People with the **Edit house style rules** permission (`manage copydesk rules`) see "House
style packs" on the Readability page under Tools. "Start a pack" makes a file in
`resources/copydesk`, and "Edit the rules" opens it as a form: one block per rule, with only
the keys its kind has. Switch a rule off with its toggle; it stays in the file and its id goes
into `disable`.

"Try it on some text", beside the form, runs the pack as the form has it now, before saving.
Saving checks the pack exactly as Copydesk will read it, and refuses one it could not, with the
reason. Saving rewrites the file, so comments in it are not kept, and a pack's `collections`
section, which the form does not show, is kept as it is. The packs Copydesk ships cannot be
edited: start your own and extend them.

## The kinds of rule

Every rule has an `id` (lowercase, hyphens), a `type`, a `message` that
says what to do, and may have `suggestions`, a `severity` (`info`,
`warning` or `error`; `warning` if you leave it out), a `scope`
(`collections` and `fields`, both every one unless you say) and
`exceptions` (text, or `/a regex/`, that is never flagged).

| Type | Its own keys | What it does |
|---|---|---|
| `word_list` | `words` | Flags words and phrases, any case, and a single word's other forms ("utilized" for "utilize"). Each word can have what to say instead, its own `severity`, and a `reason`. |
| `term` | `terms` | Flags a name written any other way: other capitals, run together, hyphenated. The exact spelling is never flagged. |
| `pattern` | `pattern`, `flags` | Flags what a regular expression matches. Written without slashes. |
| `punctuation` | `checks`, `quotes` | Any of `em_dash`, `double_space`, `quotes`, `space_before_punctuation`, `repeated_punctuation`. `quotes` needs `quotes: curly` or `quotes: straight`. |
| `casing` | `style`, `keep` | Puts headings in `sentence` or `title` case. `keep` lists names that always keep their capitals. |
| `sentence` | `check`, `max_words` | `max_words` (with a number), `passive`, or `starts_with_numeral`. |

## If you follow a licensed stylebook

Copydesk does not ship any commercial stylebook, and the
`news-style-starter` pack is not one: it is a handful of conventions in
Copydesk's own words. A newsroom that licenses a stylebook can write the
entries it relies on into its own pack, in its own words, the same way as
any house rule:

```yaml
description: Our newsroom's style, as we apply it.
extends: [core-punctuation]

rules:
  - id: newsroom-titles
    type: word_list
    message: Use the short form of the title on second reference.
    words:
      superintendent of schools: [superintendent]

  - id: newsroom-masthead
    type: term
    terms: [Greenhouse Gazette]
    message: Our paper's name, exactly as on the masthead.
```

The pack lives in your repository and is yours. Copydesk only runs it.
