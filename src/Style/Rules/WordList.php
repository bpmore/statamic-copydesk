<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Style\Rules;

use Bpmore\Copydesk\Style\Matcher;
use Bpmore\Copydesk\Style\RuleDefinition;
use Bpmore\Copydesk\Style\StyleRule;
use Bpmore\ReadabilityCore\Rule\Severity;
use Bpmore\ReadabilityCore\Text\Block;

/**
 * Words and phrases to avoid, each with what to say instead.
 *
 * Whole words, any case; a single word also catches its inflections, so
 * "utilize" finds "utilized". Where two entries overlap ("in order to" and
 * "order"), the longer is the finding. One finding per occurrence, because
 * each is an edit.
 *
 * An entry's own severity wins over the rule's, so one list can ban a word
 * outright (an error) and only suggest another (a warning). The message
 * names the word as written and what to say, then gives the entry's reason,
 * or the rule's message when the entry has none.
 */
final class WordList extends StyleRule
{
    /** @var list<array{string, array{phrase: string, replacements: list<string>, severity: ?Severity, reason: ?string}}> */
    private readonly array $patterns;

    public function __construct(RuleDefinition $definition)
    {
        parent::__construct($definition);

        $words = $definition->options['words'];

        usort($words, static fn (array $a, array $b): int => mb_strlen($b['phrase']) <=> mb_strlen($a['phrase']));

        $this->patterns = array_map(static fn (array $word): array => [Matcher::pattern($word['phrase']), $word], $words);
    }

    protected function inBlock(Block $block, int $index): array
    {
        $findings = [];

        foreach (Matcher::findAll($block->text, $this->patterns) as [$offset, $written, $word]) {
            $replacements = array_map(static fn (string $r): string => Matcher::inCaseOf($written, $r), $word['replacements']);

            $findings[] = $this->finding(
                $index,
                $offset,
                $written,
                $this->message($written, $replacements, $word['reason']),
                $replacements[0] ?? null,
                $word['severity'],
            );
        }

        return $findings;
    }

    /**
     * @param  list<string>  $replacements
     */
    private function message(string $written, array $replacements, ?string $reason): string
    {
        $what = $replacements === []
            ? sprintf('"%s": say it another way.', $written)
            : sprintf('Say "%s" instead of "%s".', implode('" or "', $replacements), $written);

        return $what.' '.($reason ?? $this->definition->message);
    }
}
