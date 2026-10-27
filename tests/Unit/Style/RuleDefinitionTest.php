<?php

declare(strict_types=1);

use Bpmore\Copydesk\Style\InvalidRule;
use Bpmore\Copydesk\Style\RuleDefinition;
use Bpmore\Copydesk\Style\RuleType;
use Bpmore\ReadabilityCore\Rule\Severity;

/**
 * The rule schema: what a pack may say, and what it is told when it says
 * something else. Every refusal names the pack and the rule.
 */
function styleRule(array $raw): RuleDefinition
{
    return RuleDefinition::fromArray($raw, 'house.yaml', 3);
}

function ruleRefusal(array|string|null $raw): string
{
    try {
        RuleDefinition::fromArray($raw, 'house.yaml', 3);
    } catch (InvalidRule $e) {
        return $e->getMessage();
    }

    throw new RuntimeException('Expected the rule to be refused.');
}

it('reads the spec\'s own example', function () {
    $rule = styleRule([
        'id' => 'no-em-dash',
        'type' => 'pattern',
        'pattern' => '—',
        'message' => 'Use a colon, comma, parentheses, or two sentences instead of an em dash.',
        'suggestions' => [', ', ': '],
        'severity' => 'error',
        'scope' => ['collections' => ['*'], 'fields' => ['*']],
        'exceptions' => [],
    ]);

    expect($rule->id)->toBe('no-em-dash')
        ->and($rule->type)->toBe(RuleType::Pattern)
        ->and($rule->severity)->toBe(Severity::Error)
        ->and($rule->suggestions)->toBe([', ', ': '])
        ->and($rule->options)->toBe(['pattern' => '—', 'flags' => ''])
        ->and($rule->scope->covers('news', 'content'))->toBeTrue()
        ->and($rule->source)->toBe('house.yaml');
});

it('calls info a tip, defaults to a warning, and covers everything unless scoped', function () {
    $base = ['id' => 'r', 'type' => 'term', 'terms' => ['eiSEO'], 'message' => 'Spell it eiSEO.'];

    expect(styleRule($base + ['severity' => 'info'])->severity)->toBe(Severity::Tip)
        ->and(styleRule($base)->severity)->toBe(Severity::Warning)
        ->and(styleRule($base)->scope->covers('anything', 'anywhere'))->toBeTrue();

    $scoped = styleRule($base + ['scope' => ['collections' => 'patient_education']]);

    expect($scoped->scope->covers('patient_education', 'content'))->toBeTrue()
        ->and($scoped->scope->covers('news', 'content'))->toBeFalse();
});

it('reads every shape a word list entry can take', function () {
    $words = styleRule([
        'id' => 'plain-words', 'type' => 'word_list', 'message' => 'Say it plainly.',
        'words' => [
            'utilize' => 'use',
            'in order to' => ['to'],
            'please be advised that' => null,
            'chairman' => ['replace' => ['chair', 'chairperson'], 'severity' => 'error', 'reason' => 'Use a word for the role, not the person.'],
        ],
    ])->options['words'];

    expect($words)->toBe([
        ['phrase' => 'utilize', 'replacements' => ['use'], 'severity' => null, 'reason' => null],
        ['phrase' => 'in order to', 'replacements' => ['to'], 'severity' => null, 'reason' => null],
        ['phrase' => 'please be advised that', 'replacements' => [], 'severity' => null, 'reason' => null],
        ['phrase' => 'chairman', 'replacements' => ['chair', 'chairperson'], 'severity' => Severity::Error, 'reason' => 'Use a word for the role, not the person.'],
    ]);
});

it('reads the other types\' own keys', function () {
    expect(styleRule(['id' => 'p', 'type' => 'punctuation', 'checks' => ['em_dash', 'quotes'], 'quotes' => 'curly', 'message' => 'm'])->options)
        ->toBe(['checks' => ['em_dash', 'quotes'], 'quotes' => 'curly'])
        ->and(styleRule(['id' => 'c', 'type' => 'casing', 'style' => 'sentence', 'message' => 'm'])->options)
        ->toBe(['style' => 'sentence'])
        ->and(styleRule(['id' => 's', 'type' => 'sentence', 'check' => 'max_words', 'max_words' => 30, 'message' => 'm'])->options)
        ->toBe(['check' => 'max_words', 'max_words' => 30])
        ->and(styleRule(['id' => 'n', 'type' => 'sentence', 'check' => 'starts_with_numeral', 'message' => 'm'])->options)
        ->toBe(['check' => 'starts_with_numeral', 'max_words' => null])
        ->and(styleRule(['id' => 'x', 'type' => 'pattern', 'pattern' => 'foo', 'flags' => 'i', 'message' => 'm'])->options)
        ->toBe(['pattern' => 'foo', 'flags' => 'i']);
});

it('tells literal exceptions from regular expressions', function () {
    $rule = styleRule(['id' => 'r', 'type' => 'term', 'terms' => ['eiSEO'], 'message' => 'm', 'exceptions' => ['eiseo.app', '/@eiseo\b/i']]);

    expect($rule->exceptions)->toBe(['eiseo.app', '/@eiseo\b/i'])
        ->and(RuleDefinition::isRegex('eiseo.app'))->toBeFalse()
        ->and(RuleDefinition::isRegex('/@eiseo\b/i'))->toBeTrue();
});

it('refuses what is not a rule, naming the pack and the place', function () {
    expect(ruleRefusal('just a string'))->toBe('house.yaml, rule #3: a rule is a set of keys (id, type, message, ...), not "just a string".')
        ->and(ruleRefusal(['id' => 'No Em Dash', 'type' => 'term']))->toStartWith('house.yaml, rule #3: "id" must be lowercase letters')
        ->and(ruleRefusal(['id' => 'r', 'type' => 'spelling']))->toBe('house.yaml, rule "r": "type" must be one of word_list, term, pattern, punctuation, casing, sentence; got "spelling".');
});

it('refuses a misspelt key and offers the one that was meant', function () {
    expect(ruleRefusal(['id' => 'r', 'type' => 'term', 'terms' => ['eiSEO'], 'message' => 'm', 'severty' => 'error']))
        ->toBe('house.yaml, rule "r": "severty" is not a key a term rule has. Did you mean "severity"?')
        ->and(ruleRefusal(['id' => 'r', 'type' => 'term', 'terms' => ['eiSEO'], 'message' => 'm', 'pattern' => 'x']))
        ->toBe('house.yaml, rule "r": "pattern" is not a key a term rule has.');
});

it('refuses a rule with something missing or empty', function () {
    expect(ruleRefusal(['id' => 'r', 'type' => 'term', 'message' => 'm']))->toBe('house.yaml, rule "r": a term rule needs "terms".')
        ->and(ruleRefusal(['id' => 'r', 'type' => 'term', 'terms' => []]))->toBe('house.yaml, rule "r": "message" must say, in a sentence, what to do instead.')
        ->and(ruleRefusal(['id' => 'r', 'type' => 'term', 'terms' => [], 'message' => 'm']))->toBe('house.yaml, rule "r": "terms" is empty, so the rule would never apply.')
        ->and(ruleRefusal(['id' => 'r', 'type' => 'word_list', 'words' => [], 'message' => 'm']))->toStartWith('house.yaml, rule "r": "words" is a list of word: what to say instead');
});

it('refuses a severity, scope or exception it cannot use', function () {
    $base = ['id' => 'r', 'type' => 'term', 'terms' => ['eiSEO'], 'message' => 'm'];

    expect(ruleRefusal($base + ['severity' => 'critical']))->toBe('house.yaml, rule "r": "severity" is "critical"; use info, warning or error.')
        ->and(ruleRefusal($base + ['scope' => ['collection' => ['news']]]))->toBe('house.yaml, rule "r": "scope" has "collection"; it takes "collections" and "fields". Did you mean "collections"?')
        ->and(ruleRefusal($base + ['exceptions' => ['/(unclosed/']]))->toBe('house.yaml, rule "r": the exception /(unclosed/ is written as a regular expression, and it does not compile.');
});

it('refuses type keys that cannot work', function () {
    $words = ['id' => 'r', 'type' => 'word_list', 'message' => 'm'];

    expect(ruleRefusal($words + ['words' => ['utilize' => ['replace' => 'use', 'severity' => 'loud']]]))
        ->toBe('house.yaml, rule "r": the severity of "utilize" is "loud"; use info, warning or error.')
        ->and(ruleRefusal($words + ['words' => ['utilize' => ['replce' => 'use']]]))
        ->toBe('house.yaml, rule "r": the word "utilize" has "replce"; it takes "replace", "severity" and "reason". Did you mean "replace"?')
        ->and(ruleRefusal(['id' => 'r', 'type' => 'punctuation', 'checks' => ['quotes'], 'message' => 'm']))
        ->toBe('house.yaml, rule "r": the "quotes" check needs "quotes": curly or straight, whichever the house uses.')
        ->and(ruleRefusal(['id' => 'r', 'type' => 'punctuation', 'checks' => ['em_dash'], 'quotes' => 'curly', 'message' => 'm']))
        ->toStartWith('house.yaml, rule "r": "quotes" says which quotes to use, but "checks" does not include "quotes"')
        ->and(ruleRefusal(['id' => 'r', 'type' => 'punctuation', 'checks' => ['em-dash'], 'message' => 'm']))
        ->toBe('house.yaml, rule "r": a check is "em-dash"; use em_dash, double_space, quotes, space_before_punctuation, repeated_punctuation. Did you mean "em_dash"?')
        ->and(ruleRefusal(['id' => 'r', 'type' => 'casing', 'style' => 'Title', 'message' => 'm']))
        ->toBe('house.yaml, rule "r": style is "Title"; use sentence, title. Did you mean "title"?')
        ->and(ruleRefusal(['id' => 'r', 'type' => 'sentence', 'check' => 'max_words', 'message' => 'm']))
        ->toBe('house.yaml, rule "r": the max_words check needs "max_words": a whole number of words, at least 1; got nothing.')
        ->and(ruleRefusal(['id' => 'r', 'type' => 'sentence', 'check' => 'passive', 'max_words' => 20, 'message' => 'm']))
        ->toBe('house.yaml, rule "r": "max_words" only goes with the max_words check, not "passive".')
        ->and(ruleRefusal(['id' => 'r', 'type' => 'pattern', 'pattern' => 'x', 'flags' => 'g', 'message' => 'm']))
        ->toBe('house.yaml, rule "r": "flags" may use i, m, s, x and U; got "g".');
});
