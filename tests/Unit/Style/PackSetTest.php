<?php

declare(strict_types=1);

use Bpmore\Copydesk\Style\InvalidPack;
use Bpmore\Copydesk\Style\InvalidRule;
use Bpmore\Copydesk\Style\Pack;
use Bpmore\Copydesk\Style\PackSet;
use Bpmore\Copydesk\Style\Rules\WordList;
use Bpmore\ReadabilityCore\Rule\Severity;

function packRule(string $id, string $severity = 'warning', string $message = 'm'): array
{
    return ['id' => $id, 'type' => 'term', 'terms' => ['eiSEO'], 'message' => $message, 'severity' => $severity];
}

function makePack(string $name, mixed $raw, string $dir = 'resources/copydesk'): Pack
{
    return Pack::fromArray($name, $raw, "{$dir}/{$name}.yaml");
}

/** @return array<string, string> id => severity, in order */
function resolved(PackSet $set, array $roots, ?string $collection = null): array
{
    $out = [];

    foreach ($set->rules($roots, $collection) as $rule) {
        $out[$rule->id] = $rule->severity->value;
    }

    return $out;
}

function packError(callable $fn): string
{
    try {
        $fn();
    } catch (InvalidPack|InvalidRule $e) {
        return $e->getMessage();
    }

    throw new RuntimeException('Expected a refusal.');
}

beforeEach(function () {
    $this->bundled = [
        makePack('core-punctuation', ['rules' => [packRule('no-em-dash', 'error'), packRule('no-double-space')]], 'resources/packs'),
        makePack('plain-language', ['rules' => [packRule('plain-words')]], 'resources/packs'),
    ];
});

it('adds up a site pack and the bundled packs it extends, in order', function () {
    $set = new PackSet([...$this->bundled, makePack('house', ['extends' => ['core-punctuation', 'plain-language'], 'rules' => [packRule('house-names')]])]);

    expect(resolved($set, ['house']))->toBe([
        'no-em-dash' => 'error', 'no-double-space' => 'warning', 'plain-words' => 'warning', 'house-names' => 'warning',
    ]);
});

it('lets a later pack replace an earlier rule by id, keeping its place', function () {
    $set = new PackSet([...$this->bundled, makePack('house', ['extends' => ['core-punctuation'], 'rules' => [packRule('no-em-dash', 'info')]])]);

    expect(resolved($set, ['house']))->toBe(['no-em-dash' => 'tip', 'no-double-space' => 'warning']);
});

it('turns rules off by id', function () {
    $set = new PackSet([...$this->bundled, makePack('house', ['extends' => ['core-punctuation'], 'disable' => ['no-double-space']])]);

    expect(resolved($set, ['house']))->toBe(['no-em-dash' => 'error']);
});

it('applies a collection\'s changes only to that collection, last', function () {
    $set = new PackSet([...$this->bundled, makePack('house', [
        'extends' => ['core-punctuation'],
        'collections' => ['patient_education' => ['disable' => ['no-em-dash'], 'rules' => [packRule('grade-words', 'error')]]],
    ])]);

    expect(resolved($set, ['house'], 'patient_education'))->toBe(['no-double-space' => 'warning', 'grade-words' => 'error'])
        ->and(resolved($set, ['house'], 'news'))->toBe(['no-em-dash' => 'error', 'no-double-space' => 'warning'])
        ->and(resolved($set, ['house']))->toBe(['no-em-dash' => 'error', 'no-double-space' => 'warning']);
});

it('applies a pack reached twice once', function () {
    $set = new PackSet([
        ...$this->bundled,
        makePack('a', ['extends' => ['core-punctuation'], 'disable' => ['no-em-dash']]),
        makePack('b', ['extends' => ['core-punctuation']]),
    ]);

    expect(resolved($set, ['a', 'b']))->toBe(['no-double-space' => 'warning']);
});

it('hands back runnable rules', function () {
    $set = new PackSet([makePack('house', ['rules' => [['id' => 'w', 'type' => 'word_list', 'words' => ['utilize' => 'use'], 'message' => 'm']]])]);

    expect($set->runnable(['house'])[0])->toBeInstanceOf(WordList::class);
});

it('refuses two packs extending each other, naming the circle', function () {
    $set = new PackSet([makePack('a', ['extends' => ['b']]), makePack('b', ['extends' => ['a']])]);

    expect(packError(fn () => $set->rules(['a'])))->toBe('resources/copydesk/a.yaml: a extends b extends a. Packs cannot extend each other in a circle.');
});

it('refuses names that do not exist, offering the nearest', function () {
    $set = new PackSet([...$this->bundled, makePack('house', ['extends' => ['core-punctuaton']])]);
    $disabling = new PackSet([...$this->bundled, makePack('house', ['extends' => ['core-punctuation'], 'disable' => ['no-emdash']])]);

    expect(packError(fn () => $set->rules(['house'])))->toBe('resources/copydesk/house.yaml: extends "core-punctuaton", and there is no pack called that. Did you mean "core-punctuation"?')
        ->and(packError(fn () => $disabling->rules(['house'])))->toBe('resources/copydesk/house.yaml: disables "no-emdash", and no rule before it is called that. Did you mean "no-em-dash"?')
        ->and(packError(fn () => $set->rules(['hous'])))->toBe('There is no pack called "hous" to turn on. Did you mean "house"?');
});

it('refuses a site pack with a bundled pack\'s name, naming both files', function () {
    expect(packError(fn () => new PackSet([...$this->bundled, makePack('core-punctuation', [])])))
        ->toBe('resources/copydesk/core-punctuation.yaml: there is already a pack called "core-punctuation" (resources/packs/core-punctuation.yaml). Give this one another name and extend that one instead.');
});

it('refuses a pack it cannot read, and a rule in one, with the file named', function () {
    expect(packError(fn () => makePack('house', ['extend' => ['x']])))->toBe('resources/copydesk/house.yaml: "extend" is not a key a pack has; use description, extends, disable, rules, collections. Did you mean "extends"?')
        ->and(packError(fn () => makePack('house', ['rules' => [packRule('a'), packRule('a')]])))->toBe('resources/copydesk/house.yaml: two rules are called "a" (#1 and #2); give one another id.')
        ->and(packError(fn () => makePack('house', ['rules' => [packRule('a', 'loud')]])))->toBe('resources/copydesk/house.yaml, rule "a": "severity" is "loud"; use info, warning or error.')
        ->and(packError(fn () => makePack('house', ['collections' => ['news' => ['disabled' => ['a']]]])))->toBe('resources/copydesk/house.yaml, collection "news": "disabled" is not a key here; use disable, rules. Did you mean "disable"?');
});

it('reads an empty file as an empty pack', function () {
    expect(makePack('empty', null)->rules)->toBe([]);
});
