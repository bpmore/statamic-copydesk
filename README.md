# Plain

Readability and plain-language checking for Statamic 6: a grade band
against the level you write for, per-section grades, and what to fix, on
the publish form. Advisory by default; a gate is opt-in per collection and
never blocks on a grade alone.

The engine is [`bpmore/readability-core`](packages/readability-core), in
this repository, framework-agnostic and with no runtime dependencies. This
addon is its Statamic surface.

## Status

The engine is complete. On the publish form: a readability panel in the
sidebar of every entry form that has a configured prose field, with a
"Check readability" button that grades the page as it stands, unsaved
changes included — the band against the site's target, each section under
a heading on its own, and what to fix, with the flagged words marked in
their sentence. The rest of the control-panel surface arrives task by
task; `PROGRESS.md` is the record.

## The dictionary

The sticky feature: the words and phrases your readers do not know, with
what to say instead. A starter of plainlanguage.gov's substitutions is
built in. What a site adds goes on top, edited under **Addons, Plain,
Settings** — a term, what to say instead (empty to ban it), a note, and
the sites it is for (none for every site) — and is kept in
`resources/addons/statamic-plain.yaml`, in version control with the rest
of the site.

Under **Tools, Readability**, a site's own entries come out as a JSON
file and a file goes in, merging or replacing, so a list built for one
site can be carried to another or edited by hand and brought back. The
file is the same shape the starter ships in.

## The site, whole

The same page under Tools grades every published page of the site:
how they spread across the bands against the target, each collection's
median page, the hardest pages with a link to each, and the acronyms
nobody spelled out and the jargon that comes up most — the list a
dictionary gets built from. Cached for an hour per site, with a button
to scan again.

## The gate

Off by default. Turned on, it stops a publish — never a draft — in the
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

Configuration is `config/statamic-plain.php` (publish it with
`php please vendor:publish --tag=statamic-plain-config`): the locales that
are English, the target grade and tolerance, which fields are prose by
blueprint, the exclusions, each rule's options, and the gate.

## Working on it

```sh
composer install        # the addon, with the engine from packages/readability-core
vendor/bin/pest         # boots Statamic through Testbench
vendor/bin/pint --test
```

The engine's own suite runs from its directory:
`cd packages/readability-core && composer install && vendor/bin/pest`.

To see the addon in a real site, point the site's `composer.json` at both
packages by path — Composer does not read the repositories a dependency
declares — and require the addon:

```json
"repositories": [
    { "type": "path", "url": "../statamic-plain", "options": { "symlink": true } },
    { "type": "path", "url": "../statamic-plain/packages/readability-core",
      "options": { "symlink": true, "versions": { "bpmore/readability-core": "0.1.0" } } }
],
"require": { "bpmore/statamic-plain": "@dev" }
```
