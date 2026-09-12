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
