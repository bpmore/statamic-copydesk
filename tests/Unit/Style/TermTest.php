<?php

declare(strict_types=1);

use Bpmore\Copydesk\Style\RuleDefinition;
use Bpmore\Copydesk\Style\RuleFactory;
use Bpmore\ReadabilityCore\Extraction\PlainTextExtractor;
use Bpmore\ReadabilityCore\Rule\Finding;

/**
 * @param  list<string>  $terms
 * @return list<Finding>
 */
function terms(array $terms, string $text): array
{
    $definition = RuleDefinition::fromArray(['id' => 'house-names', 'type' => 'term', 'terms' => $terms, 'message' => 'Spell names the way their owners do.'], 'house.yaml');

    return RuleFactory::make($definition)->check((new PlainTextExtractor)->extract($text));
}

/** @return list<string> */
function flagged(array $findings): array
{
    return array_map(fn (Finding $f) => $f->excerpt, $findings);
}

it('flags "EiSEO" and "eiseo" but not "eiSEO" (acceptance 2)', function () {
    $findings = terms(['eiSEO'], 'eiSEO reads it. EiSEO and eiseo do not.');

    expect(flagged($findings))->toBe(['EiSEO', 'eiseo'])
        ->and($findings[0]->suggestion)->toBe('eiSEO')
        ->and($findings[0]->message)->toBe('Write "eiSEO", not "EiSEO". Spell names the way their owners do.')
        ->and($findings[0]->location->offset)->toBe(strlen('eiSEO reads it. '));
});

it('flags a many-word name in other capitals, run together or hyphenated', function () {
    $findings = terms(['UAMS Health'], 'UAMS Health, Uams Health, UAMSHealth and UAMS-Health.');

    expect(flagged($findings))->toBe(['Uams Health', 'UAMSHealth', 'UAMS-Health']);
});

it('does not call a line break between the words a different spelling', function () {
    expect(terms(['UAMS Health'], "Call UAMS\nHealth today."))->toBe([]);
});

it('leaves a longer word that only starts with the name alone', function () {
    expect(terms(['Statamic'], 'statamicish things, and statamic.'))->toHaveCount(1)
        ->and(flagged(terms(['Statamic'], 'statamicish things, and statamic.')))->toBe(['statamic']);
});

it('prefers the longer name where two overlap', function () {
    expect(flagged(terms(['UAMS', 'UAMS Health'], 'uams health and Uams.')))->toBe(['uams health', 'Uams']);
});

it('reads a name the pack wrote with stray spaces as the name', function () {
    expect(terms(['  UAMS   Health '], 'UAMS Health is right.'))->toBe([]);
});
