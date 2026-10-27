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
 *
 * Two things every type shares are handled here. Exceptions: a finding is
 * dropped when it lies wholly inside text an exception matches, so
 * `eiseo.app` excuses the "eiseo" in a web address and nowhere else. A
 * literal exception matches exactly as written; a `/regex/flags` one as
 * PCRE does. Scope: which collections and fields the rule is for. A rule
 * cannot see that from a block, so whoever assembles the document asks
 * appliesTo() before running it.
 */
abstract class StyleRule implements Rule
{
    public function __construct(public readonly RuleDefinition $definition) {}

    public function handle(): string
    {
        return $this->definition->id;
    }

    /**
     * Whether this rule's findings are positions in what the author wrote
     * (`Block::$written`) rather than in the cleaned text the engine reads.
     * A rule that checks marks the cleaning would hide (two spaces, a curly
     * quote) reads the written text; every other rule reads the cleaned
     * text, where "don’t" and "don't" are the same word.
     */
    public function readsWritten(): bool
    {
        return false;
    }

    /** The text this rule reads in a block, by readsWritten(). */
    protected function textOf(Block $block): string
    {
        return $this->readsWritten() ? ($block->written ?? $block->text) : $block->text;
    }

    /** Whether this rule is for a field in a collection, by its scope. */
    public function appliesTo(string $collection, string $field): bool
    {
        return $this->definition->scope->covers($collection, $field);
    }

    /**
     * @return list<Finding>
     */
    public function check(Document $document): array
    {
        $findings = [];

        foreach ($document->blocks as $index => $block) {
            $excused = $this->excused($this->textOf($block));

            foreach ($this->inBlock($block, $index) as $finding) {
                if (! self::within($finding->location?->offset ?? -1, $finding->location?->length ?? 0, $excused)) {
                    $findings[] = $finding;
                }
            }
        }

        return $findings;
    }

    /**
     * The stretches of a block's text the rule's exceptions match.
     *
     * @return list<array{int, int}> start and end, in bytes
     */
    private function excused(string $text): array
    {
        $spans = [];

        foreach ($this->definition->exceptions as $exception) {
            $regex = RuleDefinition::isRegex($exception) ? $exception : '/'.preg_quote($exception, '/').'/u';

            if (preg_match_all($regex, $text, $matches, PREG_OFFSET_CAPTURE) > 0) {
                foreach ($matches[0] as [$matched, $offset]) {
                    $spans[] = [$offset, $offset + strlen($matched)];
                }
            }
        }

        return $spans;
    }

    /**
     * @param  list<array{int, int}>  $spans
     */
    private static function within(int $offset, int $length, array $spans): bool
    {
        foreach ($spans as [$from, $to]) {
            if ($offset >= $from && $offset + $length <= $to) {
                return true;
            }
        }

        return false;
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
