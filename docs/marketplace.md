# Copydesk: Statamic Marketplace listing

Draft copy for the listing. Brent approves it before it goes up.

## Name

Copydesk

## Short description (one line)

House style, plain language and reading level, checked as your team writes.

## Description

Copydesk checks writing against your house style and the reading level you
write for, inside the Statamic editor.

Your rules live in plain YAML packs in your site's repository: punctuation,
names spelled your way, words to avoid and what to say instead, inclusive
language, newsroom conventions. As an author types, Copydesk underlines what
breaks a rule, says why, and offers the fix. Markdown, textarea and text
fields get the same list under the field. The panel beside the form grades
the page against your target reading level, section by section.

Built for teams that have to write plainly and show it: health systems
writing for patients, public bodies under a plain-language mandate,
universities writing for applicants and families.

### Free

- The punctuation pack: em dashes, double spaces, a space before
  punctuation, repeated marks.
- Term rules: your names, spelled exactly your way.
- The reading grade, as a band against your target, per page and per
  section, with Flesch-Kincaid and reading ease.
- Your site's own list of words to avoid.
- Underlines in Bard as you type, lists under other fields, and "Ignore
  here".

### Pro ($79)

- Every pack: plain language (plainlanguage.gov's substitutions), inclusive
  language with a reason for each entry, and a newsroom starter.
- Every kind of rule: word lists, patterns, heading case, sentence checks.
- A rule editor in the control panel, with "Try it on some text".
- A publish gate on error-level findings, never on a grade, with "Publish
  anyway" and a logged reason. Joins A11y Gate's check when both are
  installed, so an author is refused once.
- The site report: every page graded, issues by rule, collection and page,
  ignored counts and overrides, and a CSV.
- `php please copydesk:scan --fail-on=error` for CI.
- A Readability band on Site Weather's tile.

### What it is not

Not a grammar or spell checker, and not a WCAG checker. English only, and it
says so rather than score another language. It never sends your content
anywhere.

## Requirements

- Statamic 6.34 or newer
- PHP 8.2 or newer

## Screenshots to take (greenhouse, light mode, 1600 wide)

1. Bard with an em dash underlined and the box open, offering "Use ,".
2. A textarea with its list of issues under it.
3. The readability panel: the band, "As you type" counts and jump links.
4. The gate's refusal with "Publish anyway" and the reason box.
5. The Readability page under Tools: issues by rule and the hardest pages.
6. The rule editor with a word list rule open and "Try it on some text".

## Support

Issues and questions: the GitHub repository's issues.
