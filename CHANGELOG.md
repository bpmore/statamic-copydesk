# Changelog

What changed in each release, and what you have to do about it.

Versions are `MAJOR.MINOR.PATCH`. From 1.0 a breaking change raises the major.

## Unreleased

**Plain is now Copydesk.** Same code, new name, ahead of the house-style
rules that are on the way. Nothing was ever sold or installed under the old
name, so there is nothing to migrate. What changed:

- Package `bpmore/statamic-copydesk`, namespace `Bpmore\Copydesk`.
- Config `config/copydesk.php`, published with `--tag=copydesk-config`.
  The switch is `COPYDESK_ENABLED`.
- Command `php please copydesk:scan`, with the same options.
- Tag `{{ copydesk:score }}`.
- Settings saved to `resources/addons/copydesk.yaml`; scans kept in
  `storage/statamic/copydesk/`.
- The Readability page is at `/cp/utilities/copydesk`, and its
  permission is `access copydesk utility`.
- **Free and Pro editions.** Listed in `composer.json`; a site runs Free
  until it sets `'bpmore/statamic-copydesk' => 'pro'` in
  `config/statamic/editions.php`. Nothing differs between them yet.
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
