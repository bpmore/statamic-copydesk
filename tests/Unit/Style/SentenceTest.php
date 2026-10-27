<?php

declare(strict_types=1);

use Bpmore\Copydesk\Style\RuleDefinition;
use Bpmore\Copydesk\Style\RuleFactory;
use Bpmore\ReadabilityCore\Extraction\HtmlExtractor;
use Bpmore\ReadabilityCore\Rule\Finding;
use Bpmore\ReadabilityCore\Rule\LongSentence;

/**
 * @return list<Finding>
 */
function sentences(array $rule, string $html): array
{
    $definition = RuleDefinition::fromArray($rule + ['id' => 'house-sentences', 'type' => 'sentence', 'message' => 'House style.'], 'house.yaml');

    return RuleFactory::make($definition)->check((new HtmlExtractor)->extract($html));
}

it('flags a sentence over the house limit, and not one at it', function () {
    $findings = sentences(['check' => 'max_words', 'max_words' => 5], '<p>One two three four five. One two three four five six.</p>');

    expect($findings)->toHaveCount(1)
        ->and($findings[0]->excerpt)->toBe('One two three four five six.')
        ->and($findings[0]->message)->toBe('This sentence runs to 6 words; the house limit is 5. House style.')
        ->and($findings[0]->location->offset)->toBe(strlen('One two three four five. '));
});

it('counts words the way the engine\'s long-sentence rule does', function () {
    $html = '<p>'.implode(' ', array_fill(0, 26, 'word')).'. Dr. Smith went to St. Louis Inc. yesterday, '.implode(' ', array_fill(0, 22, 'and')).'.</p>';
    $document = (new HtmlExtractor)->extract($html);

    $engine = array_map(fn (Finding $f) => $f->location->offset, (new LongSentence(['words' => 25]))->check($document));
    $ours = array_map(fn (Finding $f) => $f->location->offset, sentences(['check' => 'max_words', 'max_words' => 25], $html));

    expect($ours)->toBe($engine)->not->toBeEmpty();
});

it('flags each passive sentence', function () {
    $findings = sentences(['check' => 'passive'], '<p>The form was signed by the patient. The nurse signed the form. Mistakes were made.</p>');

    expect(array_map(fn (Finding $f) => $f->excerpt, $findings))->toBe(['The form was signed by the patient.', 'Mistakes were made.']);
});

it('points at the numeral that starts a sentence', function () {
    $findings = sentences(['check' => 'starts_with_numeral'], '<p>We saw 12 patients. 12 more came later. "3,000 visits" is the goal. Three is fine.</p>');

    expect(array_map(fn (Finding $f) => $f->excerpt, $findings))->toBe(['12', '3,000'])
        ->and($findings[0]->location->offset)->toBe(strlen('We saw 12 patients. '))
        ->and($findings[1]->location->offset)->toBe(strlen('We saw 12 patients. 12 more came later. "'));
});

it('leaves headings alone', function () {
    expect(sentences(['check' => 'starts_with_numeral'], '<h2>10 ways to stay well</h2>'))->toBe([]);
});
