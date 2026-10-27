<?php

declare(strict_types=1);

use Bpmore\Copydesk\Style\RuleDefinition;
use Bpmore\Copydesk\Style\RuleFactory;
use Bpmore\ReadabilityCore\Extraction\HtmlExtractor;
use Bpmore\ReadabilityCore\Rule\Finding;

/**
 * @param  list<string>  $keep
 * @return list<Finding>
 */
function casing(string $style, string $html, array $keep = []): array
{
    $definition = RuleDefinition::fromArray(['id' => 'heading-case', 'type' => 'casing', 'style' => $style, 'keep' => $keep, 'message' => 'Headings follow house style.'], 'house.yaml');

    return RuleFactory::make($definition)->check((new HtmlExtractor)->extract($html));
}

/** @return list<string> the headings as the rule would write them */
function fixed(array $findings): array
{
    return array_map(fn (Finding $f) => $f->suggestion, $findings);
}

it('puts headings in sentence case, and leaves body text alone', function () {
    $findings = casing('sentence', '<h2>Our Mission And Values</h2><p>Our Mission And Values Are Many.</p><h2>Already fine</h2>');

    expect(fixed($findings))->toBe(['Our mission and values'])
        ->and($findings[0]->excerpt)->toBe('Our Mission And Values')
        ->and($findings[0]->location->offset)->toBe(0)
        ->and($findings[0]->message)->toBe('Write headings in sentence case: "Our mission and values". Headings follow house style.');
});

it('keeps acronyms, inner capitals, numbers, "I", and the house\'s own names', function () {
    expect(fixed(casing('sentence', '<h2>How UAMS Health Uses eiSEO And Statamic 6</h2>', ['UAMS Health', 'Statamic'])))
        ->toBe(['How UAMS Health uses eiSEO and Statamic 6'])
        ->and(casing('sentence', '<h2>What I learned</h2>'))->toBe([]);
});

it('starts again after a colon in sentence case', function () {
    expect(fixed(casing('sentence', '<h2>Flu Shots: What To Know</h2>')))->toBe(['Flu shots: What to know']);
});

it('puts headings in title case, leaving short joining words small in the middle', function () {
    expect(fixed(casing('title', '<h2>the state of the art in patient care</h2>')))->toBe(['The State of the Art in Patient Care'])
        ->and(fixed(casing('title', '<h2>what we stand for</h2>')))->toBe(['What We Stand For'])
        ->and(casing('title', '<h2>A Guide to the Clinic</h2>'))->toBe([]);
});

it('treats each part of a hyphenated word as a word', function () {
    expect(fixed(casing('title', '<h2>a step-by-step follow-up</h2>')))->toBe(['A Step-By-Step Follow-Up'])
        ->and(fixed(casing('sentence', '<h2>A Step-By-Step Follow-Up</h2>')))->toBe(['A step-by-step follow-up']);
});

it('counts a kept word when deciding which word is first', function () {
    expect(fixed(casing('sentence', '<h2>Statamic Tips And Tricks</h2>', ['Statamic'])))->toBe(['Statamic tips and tricks'])
        ->and(fixed(casing('title', '<h2>made for UAMS</h2>')))->toBe(['Made for UAMS']);
});
