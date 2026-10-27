<?php

declare(strict_types=1);

// The boundary that makes the engine reusable: nothing in it knows about
// Statamic or Laravel. The engine's own suite enforces this with a token
// scan, because nothing from either is installed there for Pest to see.
// Here both are installed, so this is the rule as CLAUDE.md wrote it, and
// it bites.
arch('core stays framework-agnostic')
    ->expect('Bpmore\ReadabilityCore')
    ->not->toUse(['Statamic', 'Illuminate']);

arch('the addon declares strict types')
    ->expect('Bpmore\Copydesk')
    ->toUseStrictTypes();

// The style rule engine is plain PHP, so it can be tested with a string and
// nothing else, and so YAML is parsed at the edge rather than inside it.
arch('the style engine stays framework-agnostic')
    ->expect('Bpmore\Copydesk\Style')
    ->not->toUse(['Statamic', 'Illuminate']);
