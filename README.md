# Copydesk

Copydesk checks writing in Statamic 6. It looks at three things:

- **House style.** Your own rules, kept in simple text files: punctuation,
  names spelled your way, words to avoid, inclusive language and newsroom
  habits.
- **Plain language.** Sentences that run too long, words your readers may
  not know, acronyms nobody spelled out, and too much passive voice. Each
  one comes with what to change.
- **Reading level.** A grade for the whole page and for each section,
  compared with the level you write for.

It works in three places: on the edit form as the author types, across the
whole site under Tools, and from the command line in CI. It only gives
advice unless you turn on the gate. The gate is off by default, you choose
which collections it covers, and it never stops a page because of its
grade.

Copydesk is for sites that have to write plainly and show that they do:
health systems writing for patients, public bodies with a plain-language
rule, and universities writing for students and their families. For those
readers, the test is simple. Can the person the page was written for read
it?

The checking is done by [`bpmore/readability-core`](https://github.com/bpmore/readability-core),
a separate package that works without Statamic and needs nothing else to
run. Copydesk is the Statamic side of it.

## Install

Statamic 6.34 or newer, on PHP 8.2 or newer. From the site's folder:

```sh
composer require bpmore/statamic-copydesk
```

That is all a site needs to start. There is nothing to build and no
database table to make. Open any entry whose blueprint has a field called
`content`, and the Readability panel is in the sidebar.

1. **Choose the fields.** Copydesk reads the field called `content` on
   every blueprint until you say otherwise. To read others, publish the
   config with `php please vendor:publish --tag=copydesk-config` and list
   them under `fields` in `config/copydesk.php`, by blueprint handle:
   `'*' => ['content']` for every blueprint, `'article' => ['summary']` to
   add one to a single blueprint.
2. **Give roles the permissions.** A super user can do everything. Anyone
   else needs **Check readability** on their role to use the panel. The
   rest are under [Who may do what](#who-may-do-what).
3. **Turn on Pro, if you bought it.** Set the edition in
   `config/statamic/editions.php`, as shown under [Editions](#editions).
4. **Add your house style.** Put a pack in `resources/copydesk/`, as
   [docs/packs.md](docs/packs.md) shows.

Updating keeps everything a site has made: its packs, its dictionary, its
ignores and its config.

## Plain language, as the standard defines it

[ISO 24495-1:2023](https://www.iso.org/standard/78907.html) is the
international standard for plain language. It says plain language is
writing so clear that readers can find what they need, understand it, and
use it. It names four principles: relevant, findable, understandable and
usable.

A computer can check some of that. Copydesk checks those parts:

- **Understandable** is most of what Copydesk does.
  - The grade comes from four reading-level formulas: Flesch-Kincaid,
    Gunning Fog, SMOG and Coleman-Liau. Copydesk takes the middle result
    and shows it as a range, like *Grade 9–10*. It never shows a decimal
    like 9.4, because the formulas are not that exact.
  - Next to the grade, Copydesk flags what makes writing harder to read:
    a sentence over 25 words, and again over 50; a paragraph over 150
    words or eight sentences; an acronym used before it is spelled out; a
    word from the dictionary, with what to say instead (*utilize*, *in
    order to*, *prior to*); and a verb turned into a noun (*make a
    decision* for *decide*).
  - Passive voice is counted across the whole page, not flagged each
    time, because some passive voice is fine.
  - Filler (*basically*, *it is important to note that*) and hedging
    (*perhaps*, *tends to*) are shown as tips, not warnings.
- **Findable** is the section view. Copydesk grades the whole page, and
  each section under a heading on its own. A page can average Grade 8
  while the paragraph that matters most reads at Grade 16. Only the
  section view shows that.
- **Relevant** and **usable** need people who know the readers. The
  dictionary is where your team writes that down: the words your readers
  do not know, chosen by the people who talk to them. It is kept per site,
  and you can move it to another site.

## What it is not

Copydesk is not a WCAG checker. It never says a page fails WCAG.

WCAG has one rule about reading level, 3.1.5, at Level AAA. Simple writing
alone does not meet it. A page meets it by offering a simpler version, or
extra help, wherever the text reads above a lower secondary education
level, not counting names and titles. A grade cannot decide that. Copydesk
leaves out names and titles the same way the rule does, and reports the
grade as information about the writing. [A11y Report](https://github.com/bpmore/statamic-a11y-report)
is where that information shows up against 3.1.5, separate from its Level
AA claim.

Copydesk only reads English. Its reading-level formulas were all built for
English. So Copydesk does not check a site in another language. The
language codes that count as English, like `en` or `en_GB`, are set in
`config/copydesk.php`.

## House style

Your rules live in packs. A pack is a YAML file, a simple text file that
anyone on the team can read. Copydesk comes with four:

- `core-punctuation`
- `plain-language`: plainlanguage.gov's list of plainer words
- `inclusive-language`: each entry says why
- `news-style-starter`: a few newsroom habits, in Copydesk's own words. It
  is not taken from any stylebook.

Your own packs go in `resources/copydesk`. A pack can build on the ones
Copydesk ships, replace or turn off any rule, and add rules for one
collection only. There are six kinds of rule: word lists, names spelled
exactly, patterns, punctuation, heading case and sentence checks.
[docs/packs.md](docs/packs.md) shows how to write one. In Pro, you can also
edit packs in a form under Tools, and use "Try it on some text" before you
save.

## In the editor

Each field checks itself 600 ms after the author stops typing.

- **Bard:** the problem words are underlined. The line style shows how
  much it matters, so colour is never the only sign: dotted for a tip,
  wavy for something to look at, double for something to fix. Click the
  words, or put the cursor in them and press Option+Enter, to open a box.
  It shows the message, a button for each suggested fix, and "Ignore here".
- **Markdown, textarea and text fields:** the same issues show as a list
  under the field.
- **Fields inside other fields:** a Bard field inside a Replicator inside a
  Grid is checked too. It counts toward the grade, the gate and the report.

The readability panel counts the issues by how serious they are. Each one
has a button that jumps to it.

"Ignore here" saves a fingerprint of the rule and the sentence on the
entry, in a hidden field called `copydesk_ignores`. The ignore lasts after
a save, and the gate and the report respect it, until the sentence
changes. Ignored issues are always counted, never hidden. More in
[docs/editor.md](docs/editor.md).

## What gets graded

You choose which fields count as the page's writing, per blueprint. Bard,
markdown, textarea and text fields can be read. By default, Copydesk reads
the main content field.

Before grading, Copydesk sets aside the parts of a page that are not the
author's own writing. You can change each of these:

- code and preformatted text
- quotations
- reference lists and citations
- proper names and titles
- addresses and phone numbers
- tables of figures
- anything inside a Bard set or an HTML class you name

## The dictionary

The dictionary is the list of words and phrases your readers do not know,
with what to say instead. It comes with a starter list from
plainlanguage.gov. Your site's own words go on top.

Edit them under **Addons, Copydesk, Settings**. Each entry has the word,
what to say instead (leave it empty to ban the word), a note, and which
sites it is for (leave it empty for every site). The list is saved in
`resources/addons/copydesk.yaml`, so it is in version control with the
rest of the site.

Under **Tools, Readability**, you can export your site's entries as a JSON
file and import one back, either merged with what is there or replacing
it. That lets you move a list from one site to another, or edit it by hand
and bring it back. The file uses the same format as the starter list.

## The whole site (Pro)

The Readability page under Tools grades every published page on the site.
It works through them fifty at a time in the background and shows its
progress. Then it shows:

- how the pages spread across the grade ranges, compared with your target
- the middle page for each collection
- the hardest pages, each with a link
- the acronyms nobody spelled out, and the jargon that comes up most. This
  is the list to build your dictionary from.
- every issue by rule, by collection and by page, with how many were
  ignored
- every time someone published past the gate, with their reason
- a CSV of every issue

Results are kept for an hour per site, and a button runs the scan again.

Copydesk also keeps the last ninety scans per site, in
`storage/statamic/copydesk/scans/`. The dashboard widget reads them. To
show it, add `readability` to the widgets in `config/statamic/cp.php`. It
shows the middle page's grade against your target, how many pages read
above it, and which way both moved since the last scan. The widget never
starts a scan itself.

With [Site Weather](https://github.com/bpmore/statamic-site-weather)
installed, the same saved scan adds a Readability rating to its tile:

- **clear:** no page reads above the target
- **fair:** under a tenth of pages do
- **overcast:** under a quarter
- **rain:** under half
- **storm:** half or more

If Copydesk is turned off, the site is not in English, or nothing has been
scanned yet, the tile says it does not know and gives the next step. It
never shows clear when it does not know.

## From the command line, and in CI

```sh
php please copydesk:scan                       # every site, a table per site
php please copydesk:scan --site=default --collection=news
php please copydesk:scan --export=csv          # a dated CSV under storage/statamic/copydesk, or --export=path.csv
php please copydesk:scan --fail-above=10%      # exit 1 when more than a tenth of pages read above the target; or a count
php please copydesk:scan --fail-on=error       # exit 1 when a published page has an error; or warning, or tip
```

A scan of a whole site is saved the same way as a scan from the Tools
page, so a nightly run keeps the widget and Site Weather up to date. A
scan of one collection is not saved.

The default for `--fail-above` is `check.fail_above` in the config. It is
off unless you set it. If Copydesk cannot read one page, it names that page
and grades the rest. The exit code is 0 when all is well, 1 when a limit
is crossed, and 2 when the site, collection or severity you named does not
exist. The older `--fail-on-errors` still works, and means `--fail-on=error`.

## On the page itself

Some health systems want to show patients the reading level.

```antlers
{{ copydesk:score }}                                {{# Grade 6–7 #}}

{{ copydesk:score }}
    {{ if graded }}
        Reading level: {{ band }}{{ if !on_target }} (we aim for {{ target }}){{ /if }}
    {{ /if }}
{{ /copydesk:score }}
```

Used alone, the tag prints the grade range and nothing else. If a page
cannot be graded, it prints nothing, because a visitor does not need to
know why. Used as a pair, it gives you `graded`, `band`, `low`, `high`,
`target`, `comparison`, `on_target`, `above_target`, `words`, `sentences`,
and a `reason` when the page was not graded. Add `entry="<id>"` to grade a
different entry. The result is cached for each entry until the entry
changes.

## The gate (Pro)

The gate is off by default. When it is on, it can stop a page from being
published. It never stops a draft. It works in the collections you name,
or in all of them if you name none.

It only stops a page for issues marked `error`: a very long sentence, a
word on the site's list with nothing to say instead, and any rule a pack
marks as an error. Warnings and tips never stop a publish.

Someone with the **Publish past the gate** permission (`override copydesk
gate`) sees "Publish anyway" in the panel when the gate stops a page. They
give a reason. Their next publish of that page then goes through, if it
happens within ten minutes. The site report keeps the reason, the person
and the rules.

**The gate never stops a page because of its grade, and it cannot be set
to.** A grade is only an estimate. A checker that blocks a press release
for scoring Grade 11 is a checker people uninstall.

If the check itself fails to run, the save still goes through, and the log
says so. A broken check is Copydesk's problem, and it should never be the
reason a site cannot publish.

The settings are in `config/copydesk.php`. Publish it with
`php please vendor:publish --tag=copydesk-config`. It holds the language
codes that count as English, the target grade and how far from it still
counts as on target, which fields count as writing for each blueprint,
what gets set aside, each rule's options, and the gate.

## Editions

Copydesk comes in two editions.

- **Free:** the punctuation pack and name rules (your site's own names,
  spelled your way), the reading grade and panel, the site's word list,
  the underlines in the editor, and `copydesk:scan` from the command line
  or CI.
- **Pro:** every pack and every kind of rule, the rule editor, the publish
  gate with "Publish anyway", the site report with its CSV, and the
  rating on Site Weather's tile.

A site runs Free until it chooses Pro, in `config/statamic/editions.php`:

```php
'addons' => [
    'bpmore/statamic-copydesk' => 'pro',
],
```

Statamic checks the licence for the edition a site chooses. Any name there
other than `free` or `pro` causes an error, so a typo is never quietly
ignored.

## With A11y Gate

Both add-ons can stop a publish. If A11y Gate 0.11 or newer is installed,
Copydesk joins its check. The author is stopped once and sees both lists,
each in its own panel. With an older A11y Gate, or none, Copydesk's gate
works on its own. Neither add-on needs the other.

## With A11y Report

Copydesk is sold on its own. A11y Report includes the same engine and
grades every page it scans, so its customers get reading levels without
Copydesk.

The two share the engine but do different jobs:

- **Copydesk** grades what the author writes, field by field, before it
  is published. It has the panel on the edit form, the gate, the
  dictionary and the Readability page under Tools.
- **A11y Report** grades each page as visitors see it, on its own
  scheduled scan. It reports the reading level under WCAG 3.1.5, separate
  from its conformance claim.

A site with both gets two readings of its pages, one from before
publishing and one from after. The Readability page says so in one
sentence when it finds A11y Report installed, and A11y Report says the
same on its overview. Neither one scans for the other, and neither slows
the other down.

## Who may do what

Copydesk adds six permissions, under **Copydesk** on a role. Statamic also
gives every add-on two of its own: `access copydesk utility` for the
Readability page, and one for the settings screen, which Copydesk handles
itself.

- **Check readability:** grade a page from the edit form, and see the
  dashboard widget. Without it, the panel and the widget say so instead.
  To check an entry, a person also needs Statamic's own permission to open
  it.
- **Manage the dictionary:** import and export the word list, and open the
  settings screen where it is edited.
- **Configure readability:** open the settings screen. For now that screen
  only holds the dictionary. The target and the gate will move there from
  the config file later.
- **See the site report** (`view copydesk report`, Pro): the report on the
  Readability page, and its CSV.
- **Edit house style rules** (`manage copydesk rules`, Pro): the rule
  editor.
- **Publish past the gate** (`override copydesk gate`, Pro): "Publish
  anyway", once, with a reason that is kept.

A super user can do all of it, as everywhere else in Statamic. Nobody else
has any of these until you add them to a role.

## Support

Questions, bugs and requests go to the repository's
[issues](https://github.com/bpmore/statamic-copydesk/issues). Say which
version of Copydesk and of Statamic the site runs, and what you expected
to happen.

## Working on it

PHP 8.2 or newer, Statamic 6.34 or newer. Those minimums are tested: one
CI job installs the oldest versions of everything Copydesk allows, on PHP
8.2, and runs the tests there. Statamic before 6.34 saves an add-on's
settings under one name (`copydesk`) and looks for them under another
(`statamic-copydesk`), so a site's dictionary edits would never load.
Statamic 6.34.0 fixed that (statamic/cms#15495).

```sh
composer install        # the addon, with the engine from Packagist
vendor/bin/pest         # boots Statamic through Testbench
vendor/bin/pint --test
npm ci && npm run build # after any change in resources/js; the build is committed
```

The engine has its own repository and tests. To work on both at once,
clone it next to this one and point Composer at it. Do not commit that
change:

```sh
composer config repositories.readability-core path ../readability-core
composer update bpmore/readability-core
```

To try the add-on in a real site, point the site's `composer.json` at this
folder and require the add-on. The engine comes from Packagist:

```json
"repositories": [
    { "type": "path", "url": "../statamic-copydesk", "options": { "symlink": true } }
],
"require": { "bpmore/statamic-copydesk": "@dev" }
```
