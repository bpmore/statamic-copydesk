<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Analysis;

/**
 * A rule's name in plain words, for the site report: "Acronym not spelled
 * out", not `unexpanded_acronym`.
 *
 * The readability rules and the bundled packs' rules are named here. A
 * rule from a site's own pack is named from its id, `house-names` as
 * "House names", since a pack has no name for a rule. The id stays beside
 * the name in the report, because `disable` takes the id.
 */
final class RuleName
{
    /** @var array<string, string> */
    private const NAMES = [
        // The engine's readability rules, and the paragraph mark.
        'very_long_sentence' => 'Very long sentence',
        'long_sentence' => 'Long sentence',
        'long_paragraph' => 'Long paragraph',
        'passive_voice' => 'Passive voice',
        'filler' => 'Filler words',
        'jargon' => 'Jargon',
        'unexpanded_acronym' => 'Acronym not spelled out',
        'nominalization' => 'Verb turned into a noun',
        Analyzer::HARD_PARAGRAPH => 'Paragraph far above the target grade',
        // core-punctuation
        'no-em-dash' => 'Em dash',
        'no-double-space' => 'Double space',
        'no-space-before-punctuation' => 'Space before punctuation',
        'no-repeated-punctuation' => 'Repeated punctuation',
        // plain-language
        'plain-words' => 'A plainer word',
        // inclusive-language
        'inclusive-roles' => 'Gendered job title',
        'inclusive-people' => 'Person-first language',
        'inclusive-phrases' => 'Phrase with unintended meaning',
        // news-style-starter
        'news-no-numeral-start' => 'Sentence starts with a numeral',
        'news-percent-word' => 'Percent as a word',
        'news-no-on-the-hour' => 'Minutes on the hour',
        'news-am-pm' => 'a.m. and p.m.',
        'news-noon-midnight' => 'Noon and midnight',
        'news-no-date-ordinal' => 'Ordinal in a date',
    ];

    public static function of(string $rule): string
    {
        return self::NAMES[$rule] ?? ucfirst(trim((string) preg_replace('/[-_\s]+/', ' ', $rule)));
    }
}
