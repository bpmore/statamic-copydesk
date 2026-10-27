<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Style;

use Bpmore\ReadabilityCore\Rule\Finding;
use Bpmore\ReadabilityCore\Rule\Location;
use Bpmore\ReadabilityCore\Rule\Rule;
use Bpmore\ReadabilityCore\Rule\Severity;
use Bpmore\ReadabilityCore\Text\Block;
use Bpmore\ReadabilityCore\Text\Document;

/**
 * A rule from a pack, as the engine runs it.
 *
 * It is the engine's own Rule, so one RuleSet runs the readability rules and
 * the house rules together and their findings come back as one list. The
 * finding's rule is the pack's id ("no-em-dash"), which is what a gate, an
 * ignore and a report count by; the engine's own rules use underscores, so
 * the two cannot collide.
 *
 * A subclass says what it finds in one block. A finding carries one
 * suggestion, the first; the rest stay on the definition, for an editor that
 * offers more than one.
 */
abstract class StyleRule implements Rule
{
    public function __construct(public readonly RuleDefinition $definition) {}

    public function handle(): string
    {
        return $this->definition->id;
    }

    /**
     * @return list<Finding>
     */
    public function check(Document $document): array
    {
        $findings = [];

        foreach ($document->blocks as $index => $block) {
            array_push($findings, ...$this->inBlock($block, $index));
        }

        return $findings;
    }

    /**
     * @return list<Finding>
     */
    abstract protected function inBlock(Block $block, int $index): array;

    /**
     * A finding of this rule, at the rule's severity unless one is given.
     */
    protected function finding(int $block, int $offset, string $written, string $message, ?string $suggestion = null, ?Severity $severity = null): Finding
    {
        return new Finding(
            $this->definition->id,
            $severity ?? $this->definition->severity,
            $message,
            new Location($block, $offset, strlen($written)),
            $written,
            $suggestion ?? ($this->definition->suggestions[0] ?? null),
        );
    }
}
