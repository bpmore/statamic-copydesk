<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Style\Rules;

use Bpmore\Copydesk\Style\Matcher;
use Bpmore\Copydesk\Style\RuleDefinition;
use Bpmore\Copydesk\Style\StyleRule;
use Bpmore\ReadabilityCore\Text\Block;

/**
 * Names spelt exactly as the house spells them: "UAMS Health", "Statamic",
 * "eiSEO".
 *
 * A near miss is the name in any other capitals ("EiSEO", "eiseo", "uams
 * health"), and, for a name of more than one word, the words run together
 * or joined by a hyphen ("UAMSHealth", "UAMS-Health"). The exact spelling is
 * never flagged; a line break between the words of a name is not a
 * different spelling. Whole words only, so "Statamicish" is left alone.
 * Misspellings beyond these (a dropped letter) are a spell checker's job,
 * which the spec leaves to the browser.
 */
final class Term extends StyleRule
{
    /** @var list<array{string, string}> pattern, and the name as it should be written */
    private readonly array $patterns;

    public function __construct(RuleDefinition $definition)
    {
        parent::__construct($definition);

        // As the comparison will see them: trimmed, one space between words.
        $terms = array_map(static fn (string $t): string => (string) preg_replace('/\s+/u', ' ', trim($t)), $definition->options['terms']);

        usort($terms, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        $this->patterns = array_map(static fn (string $term): array => [self::pattern($term), $term], $terms);
    }

    protected function inBlock(Block $block, int $index): array
    {
        $findings = [];

        foreach (Matcher::findAll($block->text, $this->patterns) as [$offset, $written, $term]) {
            if (preg_replace('/\s+/u', ' ', $written) === $term) {
                continue;
            }

            $findings[] = $this->finding(
                $index,
                $offset,
                $written,
                sprintf('Write "%s", not "%s". %s', $term, $written, $this->definition->message),
                $term,
            );
        }

        return $findings;
    }

    /**
     * The name in any case, its words apart, run together or hyphenated.
     */
    private static function pattern(string $term): string
    {
        $words = array_map(static fn (string $w): string => preg_quote($w, '/'), preg_split('/\s+/u', trim($term)) ?: []);

        return '/(?<![\p{L}\p{N}])'.implode('(?:\s+|-|)', $words).'(?![\p{L}\p{N}])/iu';
    }
}
