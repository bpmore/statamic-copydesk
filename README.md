# Plain

Readability and plain-language checking for Statamic 6: a grade band
against the level you write for, per-section grades, and what to fix, on
the publish form. Advisory by default; a gate is opt-in per collection and
never blocks on a grade alone.

The engine is [`bpmore/readability-core`](packages/readability-core), in
this repository, framework-agnostic and with no runtime dependencies. This
addon is its Statamic surface.

## Status

Scaffolded. The engine is complete; the control-panel surface arrives task
by task. `PROGRESS.md` is the record.

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
