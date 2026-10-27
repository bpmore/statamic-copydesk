# Copydesk

Readability and plain language checking for Statamic 6. It grades the
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

It is English only, and says so. Every formula it uses was calibrated on
English, and a grade for a French page is a confident number that means
nothing, so a site whose locale is not in the configured list is refused
with a sentence rather than scored.

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

## The site, whole

The same page under Tools grades every published page of the site:
how they spread across the bands against the target, each collection's
median page, the hardest pages with a link to each, and the acronyms
nobody spelled out and the jargon that comes up most, which is the list a
dictionary gets built from. Cached for an hour per site, with a button
to scan again.

Every scan is also kept, the last ninety per site, in
`storage/statamic/copydesk/scans/`. The dashboard widget reads them. Add
`readability` to the widgets in `config/statamic/cp.php`, and it shows the
median page's band against the target, how many pages read above it, and
which way both moved since the last scan. It never scans on its own.

With [Site Weather](../statamic-site-weather) installed, the same stored
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
php please copydesk:scan --fail-on-errors      # exit 1 on any very long sentence or banned term
```

A full scan of a site is recorded the same way the page under Tools
records one, so a nightly run keeps the widget and the Site Weather band
current; a scan narrowed to a collection is not. The threshold's default
is `check.fail_above` in the config, off unless set. One page the check
cannot read is named and the rest are graded. Exit codes: 0, 1 when a
threshold is crossed, 2 for a site or collection that does not exist.

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

## The gate

Off by default. Turned on, it stops a publish (never a draft) in the
collections you name (or all of them, if you name none), and only for the
findings of the rules in `block_on`: a very long sentence, and a banned
term from the dictionary, one you gave nothing to say instead of. A term
with a suggested replacement is a warning to fix, not a reason to stop a
publish.

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

Copydesk comes in two editions, Free and Pro. A site runs Free until it
chooses Pro, in `config/statamic/editions.php`:

```php
'addons' => [
    'bpmore/statamic-copydesk' => 'pro',
],
```

Statamic checks the licence for the edition a site chooses. A name other
than `free` or `pro` there is refused with an error rather than guessed at.

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

Three permissions, under **Copydesk** on a role, beside the two Statamic
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

A super user may do all of it, as everywhere else. The gate is not a
permission: it runs on every save Statamic lets through, whoever is
saving, because a page is either publishable or it is not.

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
