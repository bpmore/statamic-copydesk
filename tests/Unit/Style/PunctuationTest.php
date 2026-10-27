<?php

declare(strict_types=1);

use Bpmore\Copydesk\Style\RuleDefinition;
use Bpmore\Copydesk\Style\RuleFactory;
use Bpmore\ReadabilityCore\Extraction\PlainTextExtractor;
use Bpmore\ReadabilityCore\Rule\Finding;

/**
 * @param  list<string>  $checks
 * @return list<Finding>
 */
function punctuation(array $checks, string $text, ?string $quotes = null): array
{
    $rule = ['id' => 'house-punctuation', 'type' => 'punctuation', 'checks' => $checks, 'message' => 'House style.'];
    $definition = RuleDefinition::fromArray($quotes === null ? $rule : $rule + ['quotes' => $quotes], 'house.yaml');

    return RuleFactory::make($definition)->check((new PlainTextExtractor)->extract($text));
}

/** @return list<array{string, ?string}> what was found, and the suggestion */
function found(array $findings): array
{
    return array_map(fn (Finding $f) => [$f->excerpt, $f->suggestion], $findings);
}

it('finds an em dash', function () {
    $findings = punctuation(['em_dash'], 'Short words — plain ones.');

    expect(found($findings))->toBe([['—', null]])
        ->and($findings[0]->message)->toBe('An em dash. House style.');
});

// Blocked: the engine's Normalizer collapses spaces and straightens curly
// quotes before any rule sees the text. See PROGRESS.md, Notes, 2026-10-26.
it('finds two or more spaces between words, but not at the start of a line', function () {
    expect(found(punctuation(['double_space'], "Call us.  Now.   Today.\n  Indented.")))->toBe([['  ', ' '], ['   ', ' ']]);
})->skip('Blocked: the engine collapses spaces before rules see the text (PROGRESS.md, Notes).');

it('finds straight quotes in a curly house, with the curly quote that fits', function () {
    expect(found(punctuation(['quotes'], 'She said "don\'t" and \'go\'.', 'curly')))->toBe([
        ['"', '“'], ["'", '’'], ['"', '”'], ["'", '‘'], ["'", '’'],
    ]);
});

it('finds curly quotes in a straight house', function () {
    expect(found(punctuation(['quotes'], 'She said “don’t”.', 'straight')))->toBe([['“', '"'], ['’', "'"], ['”', '"']]);
})->skip('Blocked: the engine straightens curly quotes before rules see the text (PROGRESS.md, Notes).');

it('finds a space before punctuation, but not before an ellipsis', function () {
    expect(found(punctuation(['space_before_punctuation'], 'Wait , what ? Then ... nothing.')))->toBe([[' ,', ','], [' ?', '?']]);
});

it('finds repeated marks, but leaves an ellipsis alone', function () {
    expect(found(punctuation(['repeated_punctuation'], 'Really!! Why?? Well.. then.... and so...')))->toBe([
        ['!!', '!'], ['??', '?'], ['..', '.'], ['....', '...'],
    ]);
});

it('runs several checks and returns their findings in reading order', function () {
    expect(found(punctuation(['repeated_punctuation', 'em_dash'], 'Yes!! — no.')))->toBe([['!!', '!'], ['—', null]]);
});

it('leaves quotes alone that are already curly in a curly house', function () {
    // Today this flags every one of them: the engine has made them straight
    // before the rule looks, so the check cannot tell right from wrong.
    expect(punctuation(['quotes'], 'She said “don’t” and ‘go’.', 'curly'))->toBe([]);
})->skip('Blocked: the engine straightens curly quotes before rules see the text, so this gives false positives (PROGRESS.md, Notes).');
