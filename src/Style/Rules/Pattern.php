<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Style\Rules;

use Bpmore\Copydesk\Style\RuleDefinition;
use Bpmore\Copydesk\Style\StyleRule;
use Bpmore\ReadabilityCore\Text\Block;

/**
 * A regular expression the house writes itself, with Unicode on: the spec's
 * own example is an em dash.
 *
 * The pattern was checked to compile when the pack loaded, so a bad one
 * never reaches an editor. A match of nothing (a pattern like `x*` matches
 * between every two letters) is not a finding, because there is nothing to
 * underline. If PCRE gives up on a pathological pattern part way through a
 * block, that block simply has no findings from this rule.
 */
final class Pattern extends StyleRule
{
    private readonly string $regex;

    public function __construct(RuleDefinition $definition)
    {
        parent::__construct($definition);

        $this->regex = RuleDefinition::delimit($definition->options['pattern'], $definition->options['flags']);
    }

    protected function inBlock(Block $block, int $index): array
    {
        if (preg_match_all($this->regex, $block->text, $matches, PREG_OFFSET_CAPTURE) === false) {
            return [];
        }

        $findings = [];

        foreach ($matches[0] as [$written, $offset]) {
            if ($written === '') {
                continue;
            }

            $findings[] = $this->finding($index, $offset, $written, $this->definition->message);
        }

        return $findings;
    }
}
