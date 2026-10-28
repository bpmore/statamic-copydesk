# Changelog

What changed in each release, and what you have to do about it.

Versions are `MAJOR.MINOR.PATCH`. From 1.0 a breaking change raises the major.

## 2.1.3 - 2026-10-27

The README, rewritten in plain English. No code changed. Nothing to do on
upgrade.

- **The README reads plainly.** Shorter sentences and plainer words, with
  lists where a paragraph listed things. The facts, commands and settings
  are the same.
- **"English only", said simply.** Copydesk only reads English, and the
  language codes that count as English are set in `config/copydesk.php`.
- **The README's links work on the Marketplace page.** They point at
  GitHub in full, so the links to the docs and to sections of the README
  open from statamic.com too.

## 2.1.2 - 2026-10-27

Two fixes to the editor on a phone, a tablet, and by keyboard. Nothing to
do on upgrade.

- **"Show ..." in the readability panel works on a phone or tablet.** There
  Statamic shows the sidebar as a tab beside Main, and the button scrolled
  to a field on the hidden tab, so nothing seemed to happen. It now opens
  the tab that holds the field first, a blueprint's own tabs too, and in
  Bard brings the words to the middle of the screen.
- **A Bard box closes from the keyboard.** A box opened by a click or by
  "Show ..." now closes on Escape in the editor, and when the author tabs
  to another field. Before, only a click elsewhere closed it.

## 2.1.1 - 2026-10-27

The README, ready for the Marketplace. No code changed. Nothing to do on
upgrade.

- **An Install section.** The command, then choosing the fields, giving
  roles the permission, turning on Pro and adding a pack.
- **A Support section.** Questions and bugs go to the repository's issues.
- **The Site Weather link works.** It pointed at a folder on the author's
  computer.
- **`copydesk:scan` is listed under Free.** It always ran in Free; the
  README now says so.

## 2.1.0 - 2026-10-27

The site report names its rules in plain words. Nothing to do on upgrade.

- **The site report names each rule in plain words.** "Acronym not spelled
  out", not `unexpanded_acronym`, in the table by rule and in "Published past
  the gate". The id stays beside the name, since `disable` takes it. A rule
  from a site's own pack is named from its id. The CSV keeps its columns and
  adds `rule_name` at the end.

## 2.0.1 - 2026-10-27

Two fixes to how the Readability panel lays out what it says. Nothing to do
on upgrade.

- **The publish refusal lists each problem on its own line.** It ran them,
  and the closing note, into one paragraph.
- **A long "Show ..." link in the As you type list wraps cleanly.** It
  wrapped as a centered box with its message pushed under it; each link now
  has its own line, with its message below.

## 2.0.0 - 2026-10-27

The first release as Copydesk: Plain's readability check, renamed, with
house style checked as the author types. A major version because the name,
the config and the gate's `block_on` all changed; nothing was ever sold or
installed under the old name, so there is nothing to migrate.

**Plain is now Copydesk.** What changed with the name:

- Package `bpmore/statamic-copydesk`, namespace `Bpmore\Copydesk`.
- Config `config/copydesk.php`, published with `--tag=copydesk-config`.
  The switch is `COPYDESK_ENABLED`.
- Command `php please copydesk:scan`, with the same options.
- Tag `{{ copydesk:score }}`.
- Settings saved to `resources/addons/copydesk.yaml`; scans kept in
  `storage/statamic/copydesk/`.
- The Readability page is at `/cp/utilities/copydesk`, and its
  permission is `access copydesk utility`.
- **House style on the publish form.** The panel's check runs the house
  rules beside the readability rules: core punctuation, plain language and
  inclusive language by default, plus any packs in `resources/copydesk`.
  The site's word list is now one of them. A collection can have its own
  target grade (`targets`), each section shows Flesch-Kincaid and reading
  ease, and a paragraph far above the target is marked.
- **Free and Pro editions.** Listed in `composer.json`; a site runs Free
  until it sets `'bpmore/statamic-copydesk' => 'pro'` in
  `config/statamic/editions.php`. Free: the punctuation pack, term rules,
  the readability grade, the word list and the editor's underlines. Pro:
  every other pack and kind of rule, the rule editor, the gate, the site
  report and the Site Weather signal.
- **Underlines as you type.** Bard underlines what breaks a rule, by
  severity, with a box of suggestions and "Ignore here"; markdown, textarea
  and text fields list the same under the field; the panel counts them and
  jumps to each. Bard nested in Replicator and Grid is checked too.
- **Nested fields count.** The panel's grade, the gate and the site report
  read the Bard, markdown and textarea fields nested in a listed Grid,
  Replicator, Group or Bard set, the same ones underlined as you type.
- **Ignore here** keeps a fingerprint of the rule and sentence on the entry
  (`copydesk_ignores`), honoured by the panel, gate and report, and always
  counted.
- **The gate blocks on `error`** findings, never on a grade. `block_on` is
  gone: what blocks is now the rule's severity. Its two rules were already
  errors, so nothing that blocked stops blocking. Pro.
- **Publish anyway**, with a reason, for `override copydesk gate`: a pass
  for one publish within ten minutes; the reason is kept in the site report.
- **One refusal with A11y Gate** 0.11 or newer: Copydesk joins its check.
- **The site report** scans in queued jobs of fifty with progress, and adds
  issues by rule, collection and page, ignored counts, overrides, and a CSV.
  Pages too short to grade are reported when they break a rule.
- **`copydesk:scan --fail-on=error|warning|tip`** exits 1 on a finding at or
  above that severity.
- **The rule editor** (Pro): a site's own packs in a form, with "Try it on
  some text", written back as YAML after the same checks Copydesk reads
  them with.
- **New permissions:** `view copydesk report`, `manage copydesk rules`,
  `override copydesk gate`.

**What to do about it:** if your config has `gate.block_on`, remove it; it
is no longer read. To keep a rule from blocking, set its severity to
`warning`.
- **Positions in the text as written.** Every finding in the panel's data
  now says where it is in the field as the author wrote it, through
  readability-core 0.2's map, which the coming editor underlines need.
- **The engine comes from Packagist.** `bpmore/readability-core` is no
  longer kept inside this repository; it installs like any other package,
  so Copydesk and A11y Report always share one copy of it.
- **Statamic 6.34 or newer.** The slug (`copydesk`) now differs from the
  package name, and Statamic before 6.34 saves addon settings under one
  and reads them under the other, so the dictionary would never load.

The entries below are from before the rename, when this was Plain.

## 1.0.1 - 2026-09-15

The manifest's description and the README's first paragraph say what this
is in the words a buyer searches for: readability, reading level, plain
language, health literacy. Nothing in the code changed.

## 1.0.0 - 2026-09-15

The first release.

**On the publish form.** A Readability panel in the sidebar of every entry
form that has a configured prose field. One button grades the page as it
stands, unsaved changes included: a band against the site's target, each
section under a heading on its own, and what to change, with the flagged
words marked in their sentence. Advisory. Nothing is stored.

**The gate.** Off by default. Turned on per collection, it stops a publish for
a very long sentence or a banned term from the dictionary, and for nothing
else. It never blocks on a grade and cannot be configured to.

**The dictionary.** The words and phrases your readers do not know, with what
to say instead: a starter of plainlanguage.gov's substitutions, and your own
on top, per site, under Addons, Plain, Settings. Import and export under
Tools, Readability.

**The site, whole.** Under Tools: every published page graded, hardest first,
how they spread against the target, each collection's median, the acronyms
nobody spelled out and the jargon that comes up most. Every scan kept, the
dashboard widget reading them, a Readability band on Site Weather's tile
when it is installed.

**Elsewhere.** `{{ plain:score }}` for a reading level on the page itself;
`php please plain:check` with a CSV and an exit code for CI; three
permissions.

**What it is not.** Not a WCAG checker. English only. The README says why.

**Requirements.** PHP 8.2 or newer, Statamic 6.31 or newer, and
`bpmore/readability-core` 0.1, the engine, which A11y Report shares.
