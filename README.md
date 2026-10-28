# Copydesk

House style, plain language and readability checking for Statamic 6. It
checks writing against the site's own house style, as rules kept in plain
YAML packs (punctuation, names spelled your way, words to avoid, inclusive
language, newsroom conventions), underlines what breaks them as the author
types, and grades the
reading level of a page as a band against the level you write for, section
by section, and says what to change: the sentence that runs too long, the
term your readers do not know and what to say instead, the acronym nobody
spelled out, the passive voice when there is too much of it. It is built
for health literacy, where the reader is a patient and the page has to
work the first time, and for any site under a plain-language mandate. On
the publish form, as the author writes; across the whole site, under
Tools; and in CI. Advisory by default. A gate is opt-in per collection and
never blocks on a grade.

It is for the sites that are asked to write plainly and have to show it:
health systems writing for patients, public bodies under a plain-language
mandate, universities writing for applicants and their families. The
question those readers ask is not whether a page passes a standard. It is
whether the person it was written for can read it.

The engine is [`bpmore/readability-core`](https://github.com/bpmore/readability-core),
its own package, framework-agnostic and with no runtime dependencies. This
addon is its Statamic surface.

## Install

Statamic 6.34 or newer, on PHP 8.2 or newer. From the site's folder:

```sh
composer require bpmore/statamic-copydesk
```

That is all a site needs to start. There is nothing to build and no
database table to make. Open any entry whose blueprint has a field called
`content` and the Readability panel is in the sidebar.

1. **Choose the fields.** Copydesk reads the field called `content` on
   every blueprint until you say otherwise. To read others, publish the
   config with `php please vendor:publish --tag=copydesk-config` and list
   them under `fields` in `config/copydesk.php`, by blueprint handle:
   `'*' => ['content']` for every blueprint, `'article' => ['summary']` to
   add one to a single blueprint.
2. **Give roles the permissions.** A super user can do everything. Anyone
   else needs **Check readability** on their role to use the panel; the
   rest are under [Who may do what](#who-may-do-what).
3. **Turn on Pro, if you bought it.** Set the edition in
   `config/statamic/editions.php`, as under [Editions](#editions).
4. **Add your house style.** Put a pack in `resources/copydesk/`, as
   [docs/packs.md](docs/packs.md) shows.

Updating keeps everything a site has made: its packs, its dictionary, its
ignores and its config.

## Plain language, as the standard defines it

[ISO 24495-1:2023](https://www.iso.org/standard/78907.html) is the
international standard for plain language. It defines plain language as
communication whose wording, structure and design are so clear that the
intended readers can easily find what they need, understand what they find,
and use that information, and it sets out four governing principles:
readers get what they need (relevant), can easily find it (findable), can
easily understand it (understandable), and can easily use it (usable).

Copydesk checks the parts of that a machine can check, and says which:

- **Understandable** is most of what it does. The grade band is four
  readability formulas (Flesch-Kincaid, Gunning Fog, SMOG, Coleman-Liau)
  agreeing on a median, shown as a band such as *Grade 9–10* and never as a
  decimal, because the formulas are not that precise and a decimal invites
  arguments a band does not. Around the band are the rules that make
  writing plainer: a sentence over 25 words, and one over 50; a paragraph
  over 150 words or eight sentences; passive voice as a rate across the
  page, not per instance, because some passive is correct English; an
  acronym used before it is spelled out; a term on the dictionary, with
  what to say instead (*utilize*, *in order to*, *prior to*, from the
  starter list); a nominalization hiding a verb (*make a decision* for
  *decide*); and, as tips rather than warnings, filler (*basically*, *it
  is important to note that*) and hedging (*perhaps*, *tends to*).
- **Findable** is the per-section view. A page is graded whole and under
  each heading on its own, because a page can average Grade 8 while the
  paragraph that matters reads at Grade 16, and only the section view
  shows it.
- **Relevant** and **usable** are judged by people who know the readers.
  The dictionary is where that knowledge lives: the words your readers do
  not know, decided by the people who talk to them, kept per site and
  carried between sites.

## What it is not

It is not a WCAG checker, and it never says a page fails WCAG. The one
success criterion about reading level, 3.1.5, is Level AAA, and it is not
met by writing simply: it is met by offering a simpler version or
supplement wherever text reads above lower secondary level once names and
titles are set aside. A grade cannot decide that. Copydesk sets names and
titles aside the way the criterion does, and reports the grade as
evidence about the writing; [A11y Report](https://github.com/bpmore/statamic-a11y-report)
is where that evidence reaches a 3.1.5 row, apart from its Level AA claim.

Copydesk only reads English. Its reading-level formulas were all built for
English. So Copydesk does not check a site in another language. The
language codes that count as English, like `en` or `en_GB`, are set in
`config/copydesk.php`.

## House style

The rules are kept in packs: YAML files that anyone on the team can read.
Copydesk ships four: `core-punctuation`, `plain-language` (plainlanguage.gov's
substitutions), `inclusive-language` (each entry with its reason) and
`news-style-starter` (a few newsroom conventions in Copydesk's own words, not
any stylebook). A site's own packs live in `resources/copydesk`, extend the
shipped ones, replace or turn off any rule, and can add rules for one
collection. Six kinds of rule: word lists, names spelled exactly, patterns,
punctuation, heading case, and sentence checks. How to write one is in
[docs/packs.md](docs/packs.md); in Pro, a form under Tools edits them, with
"Try it on some text" before saving.

## In the editor

As the author types, each prose field asks the server 600 ms after they
stop. In Bard the flagged words are underlined, dotted for a tip, wavy for
something to look at, double for something to fix, so colour is never the
only sign. A click, or Option+Enter with the caret in the words, opens the
message, a button for each suggestion and "Ignore here". Markdown, textarea
and text fields list the same issues under the field. Bard inside a
Replicator inside a Grid is checked too, and counted by the grade, the gate
and the report. The readability panel counts the issues by severity, with a
button that jumps to each one.

"Ignore here" keeps a fingerprint of the rule and the sentence on the entry
(a hidden `copydesk_ignores` field), so the ignore holds after a save, in
the gate and in the report, until the sentence changes. Ignored findings
are always counted, never hidden. More in [docs/editor.md](docs/editor.md).

## What gets graded

Configurable per blueprint: an allowlist of the fields that are prose
(Bard, markdown, textarea, text), the primary content field by default.
Before anything is counted, the parts of a page that are not the author's
prose are set aside, each of them configurable: code and preformatted
text, quotations, reference lists and citations, proper names and titles,
addresses and phone numbers, tables of figures, and anything inside a
Bard set or an HTML class you name.

## The dictionary

The sticky feature: the words and phrases your readers do not know, with
what to say instead. A starter of plainlanguage.gov's substitutions is
built in. What a site adds goes on top, edited under **Addons, Copydesk,
Settings**: a term, what to say instead (empty to ban it), a note, and
the sites it is for (none for every site). It is kept in
`resources/addons/copydesk.yaml`, in version control with the rest
of the site.

Under **Tools, Readability**, a site's own entries come out as a JSON
file and a file goes in, merging or replacing, so a list built for one
site can be carried to another or edited by hand and brought back. The
file is the same shape the starter ships in.

## The site, whole (Pro)

The same page under Tools grades every published page of the site, in
queued jobs of fifty with its progress shown:
how they spread across the bands against the target, each collection's
median page, the hardest pages with a link to each, and the acronyms
nobody spelled out and the jargon that comes up most, which is the list a
dictionary gets built from. Then what to fix: every issue by rule, by
collection and by page, with ignored counts, every publish someone let
past the gate with their reason, and a CSV of every issue. Cached for an
hour per site, with a button to scan again.

Every scan is also kept, the last ninety per site, in
`storage/statamic/copydesk/scans/`. The dashboard widget reads them. Add
`readability` to the widgets in `config/statamic/cp.php`, and it shows the
median page's band against the target, how many pages read above it, and
which way both moved since the last scan. It never scans on its own.

With [Site Weather](https://github.com/bpmore/statamic-site-weather) installed, the same stored
scan puts a Readability band on its tile: clear when no page reads above
the target, fair under a tenth of pages, overcast under a quarter, rain
under half, storm from half. Not turned on, not English, or nothing
scanned reads as unknown with the next step, never as clear.

## From a terminal, and in CI

```sh
php please copydesk:scan                       # every site, a table per site
php please copydesk:scan --site=default --collection=news
php please copydesk:scan --export=csv          # a dated CSV under storage/statamic/copydesk, or --export=path.csv
php please copydesk:scan --fail-above=10%      # exit 1 when more than a tenth of pages read above the target; or a count
php please copydesk:scan --fail-on=error       # exit 1 when a published page has an error; or warning, or tip
```

A full scan of a site is recorded the same way the page under Tools
records one, so a nightly run keeps the widget and the Site Weather band
current; a scan narrowed to a collection is not. The threshold's default
is `check.fail_above` in the config, off unless set. One page the check
cannot read is named and the rest are graded. Exit codes: 0, 1 when a
threshold is crossed, 2 for a site, collection or severity that does not
exist. `--fail-on-errors` still works, as `--fail-on=error`.

## On the page itself

Some health systems want the reading level visible to patients.

```antlers
{{ copydesk:score }}                                {{# Grade 6–7 #}}

{{ copydesk:score }}
    {{ if graded }}
        Reading level: {{ band }}{{ if !on_target }} (we aim for {{ target }}){{ /if }}
    {{ /if }}
{{ /copydesk:score }}
```

Alone, the tag prints the band and nothing else; a page that cannot be
graded prints nothing, since a visitor is not the person to tell why. As a
pair it hands the site `graded`, `band`, `low`, `high`, `target`,
`comparison`, `on_target`, `above_target`, `words`, `sentences` and a
`reason` when not graded. `entry="<id>"` grades another entry. Cached per
entry and its last change.

## The gate (Pro)

Off by default. Turned on, it stops a publish (never a draft) in the
collections you name (or all of them, if you name none), and only for
findings whose severity is `error`: a very long sentence, a word on the
site's list with nothing to say instead, and any rule a pack marks as an
error. Warnings and tips never stop a publish.

Someone with the **Publish past the gate** permission (`override copydesk
gate`) sees "Publish anyway" in the panel when the gate refuses. They give
a reason, and their next publish of that page, within ten minutes, goes
through. The reason, the person and the rules are kept and shown in the
site report.

**It never blocks on a grade, and it cannot be configured to.** A grade
band is a heuristic. A press release stopped for scoring 11 is how a
checker gets uninstalled, and the restraint is the point.

When the check itself cannot run, the save goes through and the log says
so: a readability check that fails is this addon's problem, and this
addon must never be the reason a site cannot publish.

Configuration is `config/copydesk.php` (publish it with
`php please vendor:publish --tag=copydesk-config`): the locales that
are English, the target grade and tolerance, which fields are prose by
blueprint, the exclusions, each rule's options, and the gate.

## Editions

Copydesk comes in two editions.

- **Free:** the punctuation pack and term rules (a site's own names, spelled
  its way), the readability grade and panel, the site's word list, the
  editor's underlines, and `copydesk:scan` in a terminal or CI.
- **Pro:** every pack and every kind of rule, the rule editor, the publish
  gate with "Publish anyway", the site report with its CSV, and the Site
  Weather signal.

A site runs Free until it chooses Pro, in `config/statamic/editions.php`:

```php
'addons' => [
    'bpmore/statamic-copydesk' => 'pro',
],
```

Statamic checks the licence for the edition a site chooses. A name other
than `free` or `pro` there is refused with an error rather than guessed at.

## With A11y Gate

Both stop a publish. With an A11y Gate that takes contributors (0.11 or
newer) installed, Copydesk joins its check: an author is refused once,
with both lists, each in its own panel. With an older A11y Gate, or none,
Copydesk's gate runs on its own. Neither requires the other.

## With A11y Report

Copydesk is sold on its own; A11y Report includes the engine and
grades every page it scans, so its customers have readability without
this addon. The two share the engine and do not share a surface. Copydesk
owns the publish-form panel, the gate, the dictionary and the Readability
page under Tools, and grades what an author writes, field by field, before
it is published. A11y Report grades each page as served, on its
own scheduled scan, and reports the reading level under WCAG 3.1.5 apart
from its conformance claim. A site with both installed sees two readings of its pages,
one from each side of the publish button; the Readability page says so in a
sentence when it finds A11y Report installed, and Accessibility
Report says the same on its overview. Neither scans for the other, and
neither slows the other.

## Who may do what

Six permissions, under **Copydesk** on a role, beside the two Statamic
gives every addon (`access copydesk utility` for the Readability page, and
the settings one, which this addon answers itself):

- **Check readability**: grade a page from the publish form, and see the
  dashboard widget. Without it the panel says so instead of offering the
  button, and the widget says so instead of the median. Checking an entry
  also needs whatever Statamic needs to open it.
- **Manage the dictionary**: import and export the words to avoid, and
  open the settings screen where they are edited.
- **Configure readability**: open the settings screen. Today that is the
  dictionary; the target and gate land there as they move off the config
  file.
- **See the site report** (`view copydesk report`, Pro): the report on the
  Readability page, and its CSV.
- **Edit house style rules** (`manage copydesk rules`, Pro): the rule editor.
- **Publish past the gate** (`override copydesk gate`, Pro): "Publish
  anyway", once, with a reason that is kept.

A super user may do all of it, as everywhere else; nobody else has any of
them until a role is given it.

## Support

Questions, bugs and requests go to the repository's
[issues](https://github.com/bpmore/statamic-copydesk/issues). Say which
version of Copydesk and of Statamic the site runs, and what you expected
to happen.

## Working on it

PHP 8.2 or newer, Statamic 6.34 or newer. The floor is a tested fact: a job
in CI installs the oldest version of everything the manifest allows, on
8.2, and runs both suites there. Below 6.34, Statamic saves an addon's
settings under its slug (`copydesk`) but looks for them under its package
name (`statamic-copydesk`), so the dictionary a site edits is never read
back. 6.34.0 is the first release with the fix (statamic/cms#15495).

```sh
composer install        # the addon, with the engine from Packagist
vendor/bin/pest         # boots Statamic through Testbench
vendor/bin/pint --test
npm ci && npm run build # after any change in resources/js; the build is committed
```

The engine has its own repository and suite. To work on both at once,
clone it beside this one and point Composer at it, without committing the
change:

```sh
composer config repositories.readability-core path ../readability-core
composer update bpmore/readability-core
```

To see the addon in a real site, point the site's `composer.json` at this
folder by path and require the addon. The engine comes from Packagist:

```json
"repositories": [
    { "type": "path", "url": "../statamic-copydesk", "options": { "symlink": true } }
],
"require": { "bpmore/statamic-copydesk": "@dev" }
```
