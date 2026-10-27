<?php

declare(strict_types=1);

use Bpmore\Copydesk\Analysis\RuleName;

/**
 * The site report names rules in plain words.
 */
it('names the readability rules in plain words', function () {
    expect(RuleName::of('unexpanded_acronym'))->toBe('Acronym not spelled out')
        ->and(RuleName::of('very_long_sentence'))->toBe('Very long sentence')
        ->and(RuleName::of('hard_paragraph'))->toBe('Paragraph far above the target grade');
});

it('names a site pack\'s rule from its id', function () {
    expect(RuleName::of('house-names'))->toBe('House names')
        ->and(RuleName::of('our_style'))->toBe('Our style');
});

it('has a plain name for every rule in the bundled packs', function () {
    $ids = [];

    foreach (glob(__DIR__.'/../../resources/packs/*.yaml') ?: [] as $file) {
        preg_match_all('/^\s*- id: (\S+)$/m', (string) file_get_contents($file), $found);
        array_push($ids, ...$found[1]);
    }

    expect($ids)->not->toBeEmpty();

    // A name that is only the id with its hyphens taken out means the
    // rule was added to a pack without a name here.
    foreach ($ids as $id) {
        expect(RuleName::of($id))->not->toBe(ucfirst(str_replace('-', ' ', $id)), "Give the bundled rule [{$id}] a plain name in RuleName.");
    }
});
