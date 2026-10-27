<?php

declare(strict_types=1);

use Bpmore\Copydesk\Style\RuleDefinition;
use Bpmore\Copydesk\Style\RuleFactory;
use Bpmore\ReadabilityCore\Exclusion\Addresses;
use Bpmore\ReadabilityCore\Extraction\BardExtractor;
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

it('finds two or more spaces between words, but not at the start of a line', function () {
    expect(found(punctuation(['double_space'], "Call us.  Now.   Today.\n  Indented.")))->toBe([['  ', ' '], ['   ', ' ']]);
});

it('finds straight quotes in a curly house, with the curly quote that fits', function () {
    expect(found(punctuation(['quotes'], 'She said "don\'t" and \'go\'.', 'curly')))->toBe([
        ['"', '“'], ["'", '’'], ['"', '”'], ["'", '‘'], ["'", '’'],
    ]);
});

it('finds curly quotes in a straight house', function () {
    expect(found(punctuation(['quotes'], 'She said “don’t”.', 'straight')))->toBe([['“', '"'], ['’', "'"], ['”', '"']]);
});

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
    expect(punctuation(['quotes'], 'She said “don’t” and ‘go’.', 'curly'))->toBe([]);
});

it('reads what the author wrote, not what the engine cleaned', function () {
    // The engine folds the spaces, straightens the quotes and tidies the
    // space before the comma away; the check still sees all three.
    $findings = punctuation(['double_space', 'quotes', 'space_before_punctuation'], 'Say  "hi" , then go.', 'curly');

    expect(found($findings))->toBe([['  ', ' '], ['"', '“'], ['"', '”'], [' ,', ',']]);
});

it('leaves inline code and cut addresses alone, as every rule does', function () {
    $bard = [['type' => 'paragraph', 'content' => [
        ['type' => 'text', 'text' => 'Run '],
        ['type' => 'text', 'text' => 'a  --  b', 'marks' => [['type' => 'code']]],
        ['type' => 'text', 'text' => ' then  call (501)  686-7000.'],
    ]]];
    $document = (new Addresses)->apply((new BardExtractor(ignoredMarks: ['code']))->extract($bard));
    $definition = RuleDefinition::fromArray(['id' => 'p', 'type' => 'punctuation', 'checks' => ['double_space'], 'message' => 'm'], 'house.yaml');
    $findings = RuleFactory::make($definition)->check($document);

    expect(array_map(fn (Finding $f) => $f->location->offset, $findings))->toBe([strpos($document->blocks[0]->written, 'then  call') + 4]);
});
