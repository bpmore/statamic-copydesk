<?php

declare(strict_types=1);

use Bpmore\Copydesk\Style\RuleDefinition;
use Bpmore\Copydesk\Style\RuleFactory;
use Bpmore\ReadabilityCore\Extraction\HtmlExtractor;
use Bpmore\ReadabilityCore\Rule\Finding;

/**
 * @return list<string> what was flagged
 */
function excused(array $rule, string $html): array
{
    $definition = RuleDefinition::fromArray($rule + ['id' => 'r', 'message' => 'm'], 'house.yaml');

    return array_map(fn (Finding $f) => $f->excerpt, RuleFactory::make($definition)->check((new HtmlExtractor)->extract($html)));
}

it('drops a finding that lies inside a literal exception, and only there', function () {
    expect(excused(['type' => 'term', 'terms' => ['eiSEO'], 'exceptions' => ['eiseo.app']], '<p>Visit eiseo.app to try eiseo.</p>'))
        ->toBe(['eiseo']);
});

it('takes a regex exception as PCRE does, flags and all', function () {
    expect(excused(['type' => 'term', 'terms' => ['eiSEO'], 'exceptions' => ['/@eiseo\b/i']], '<p>Follow @EISEO and @eiseo, not EiSEO.</p>'))
        ->toBe(['EiSEO']);
});

it('keeps a finding that only overlaps an exception', function () {
    expect(excused(['type' => 'word_list', 'words' => ['in order to' => 'to'], 'exceptions' => ['order']], '<p>In order to help.</p>'))
        ->toBe(['In order to']);
});

it('applies exceptions to every type of rule', function (array $rule, string $html) {
    expect(excused($rule, $html))->toBe([]);
})->with([
    'word_list' => [['type' => 'word_list', 'words' => ['utilize' => 'use'], 'exceptions' => ['Utilize Health']], '<p>Utilize Health is a clinic.</p>'],
    'pattern' => [['type' => 'pattern', 'pattern' => '—', 'exceptions' => ['A—Z']], '<p>Index A—Z.</p>'],
    'punctuation' => [['type' => 'punctuation', 'checks' => ['repeated_punctuation'], 'exceptions' => ['Yahoo!!']], '<p>We use Yahoo!! daily.</p>'],
    'casing' => [['type' => 'casing', 'style' => 'sentence', 'exceptions' => ['/^.*$/']], '<h2>Our Mission</h2>'],
    'sentence' => [['type' => 'sentence', 'check' => 'starts_with_numeral', 'exceptions' => ['/^\d{4}\b/']], '<p>2026 was busy.</p>'],
]);

it('says which collections and fields a rule is for', function () {
    $rule = RuleFactory::make(RuleDefinition::fromArray([
        'id' => 'patient-words', 'type' => 'word_list', 'words' => ['utilize' => 'use'], 'message' => 'm',
        'scope' => ['collections' => ['patient_education'], 'fields' => ['content', 'summary']],
    ], 'house.yaml'));

    expect($rule->appliesTo('patient_education', 'content'))->toBeTrue()
        ->and($rule->appliesTo('patient_education', 'title'))->toBeFalse()
        ->and($rule->appliesTo('news', 'content'))->toBeFalse();
});
