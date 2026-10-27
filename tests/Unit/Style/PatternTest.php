<?php

declare(strict_types=1);

use Bpmore\Copydesk\Style\InvalidRule;
use Bpmore\Copydesk\Style\RuleDefinition;
use Bpmore\Copydesk\Style\RuleFactory;
use Bpmore\ReadabilityCore\Extraction\PlainTextExtractor;
use Bpmore\ReadabilityCore\Rule\Finding;
use Bpmore\ReadabilityCore\Rule\Severity;

/**
 * @return list<Finding>
 */
function pattern(array $rule, string $text): array
{
    $definition = RuleDefinition::fromArray($rule + ['id' => 'house-pattern', 'type' => 'pattern', 'message' => 'Fix this.'], 'house.yaml');

    return RuleFactory::make($definition)->check((new PlainTextExtractor)->extract($text));
}

it('finds the spec\'s em dash, with its message and first suggestion', function () {
    $findings = pattern([
        'pattern' => '—',
        'message' => 'Use a colon, comma, parentheses, or two sentences instead of an em dash.',
        'suggestions' => [', ', ': '],
        'severity' => 'error',
    ], 'Plain words — and short ones.');

    expect($findings)->toHaveCount(1)
        ->and($findings[0]->excerpt)->toBe('—')
        ->and($findings[0]->suggestion)->toBe(', ')
        ->and($findings[0]->severity)->toBe(Severity::Error)
        ->and($findings[0]->location->offset)->toBe(strlen('Plain words '))
        ->and($findings[0]->location->length)->toBe(strlen('—'));
});

it('runs with Unicode on and the pack\'s flags', function () {
    expect(pattern(['pattern' => '\bcafé\b', 'flags' => 'i'], 'The CAFÉ opens. The café closes.'))->toHaveCount(2)
        ->and(pattern(['pattern' => '\bcafé\b'], 'The CAFÉ opens. The café closes.'))->toHaveCount(1);
});

it('takes a slash in the pattern as a slash', function () {
    expect(array_map(fn (Finding $f) => $f->excerpt, pattern(['pattern' => '\d+/\d+'], 'Due 10/31, not 10-31.')))->toBe(['10/31']);
});

it('ignores matches of nothing', function () {
    expect(pattern(['pattern' => 'x*'], 'abc xx def'))->toHaveCount(1);
});

it('refuses a pattern that does not compile when the pack loads, in PCRE\'s words', function () {
    expect(fn () => RuleDefinition::fromArray(['id' => 'broken', 'type' => 'pattern', 'pattern' => '(unclosed', 'message' => 'm'], 'house.yaml'))
        ->toThrow(InvalidRule::class, 'house.yaml, rule "broken": "pattern" does not compile: missing closing parenthesis at offset 9.');
});
