<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Style\Rules;

use Bpmore\Copydesk\Style\RuleDefinition;
use Bpmore\Copydesk\Style\StyleRule;
use Bpmore\ReadabilityCore\Rule\Finding;
use Bpmore\ReadabilityCore\Rule\PassiveVoice;
use Bpmore\ReadabilityCore\Text\Block;
use Bpmore\ReadabilityCore\Text\EnglishSegmenter;
use Bpmore\ReadabilityCore\Text\Segmenter;
use Bpmore\ReadabilityCore\Text\Words;

/**
 * One check on each sentence: too many words, the passive voice, or a
 * numeral at the start.
 *
 * Sentences are the engine's: its segmenter, its word count and its passive
 * test, so a house limit of 25 words and the readability panel's long
 * sentence agree on what 25 words is. Headings are not sentences and are
 * left alone, as the engine's own tally leaves them.
 *
 * Unlike the engine's passive-voice rule, which reports a rate across the
 * page, this flags each passive sentence: a house that writes this rule has
 * decided it wants them all. A numeral finding points at the number itself,
 * so the editor underlines what to spell out.
 */
final class Sentence extends StyleRule
{
    private readonly Segmenter $segmenter;

    public function __construct(RuleDefinition $definition, ?Segmenter $segmenter = null)
    {
        parent::__construct($definition);

        $this->segmenter = $segmenter ?? new EnglishSegmenter;
    }

    protected function inBlock(Block $block, int $index): array
    {
        if ($block->isHeading()) {
            return [];
        }

        $findings = [];
        $check = $this->definition->options['check'];

        foreach ($this->segmenter->sentences($block->text) as $sentence) {
            $finding = match ($check) {
                'max_words' => $this->tooLong($index, $sentence->offset, $sentence->text),
                'passive' => PassiveVoice::isPassive($sentence->text)
                    ? $this->finding($index, $sentence->offset, $sentence->text, 'This sentence is in the passive voice; say who does what. '.$this->definition->message)
                    : null,
                'starts_with_numeral' => $this->startsWithNumeral($index, $sentence->offset, $sentence->text),
            };

            if ($finding !== null) {
                $findings[] = $finding;
            }
        }

        return $findings;
    }

    private function tooLong(int $index, int $offset, string $text): ?Finding
    {
        $count = count(Words::of($text));
        $limit = (int) $this->definition->options['max_words'];

        return $count <= $limit ? null : $this->finding(
            $index,
            $offset,
            $text,
            sprintf('This sentence runs to %d words; the house limit is %d. %s', $count, $limit, $this->definition->message),
        );
    }

    private function startsWithNumeral(int $index, int $offset, string $text): ?Finding
    {
        if (preg_match('/^[\p{Pi}\p{Ps}"\']*(\p{N}[\p{N},.]*)/u', $text, $m, PREG_OFFSET_CAPTURE) !== 1) {
            return null;
        }

        [$number, $at] = $m[1];

        return $this->finding(
            $index,
            $offset + $at,
            rtrim($number, ',.'),
            'This sentence starts with a numeral; spell it out or reword the start. '.$this->definition->message,
        );
    }
}
