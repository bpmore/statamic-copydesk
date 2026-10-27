<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Style\Rules;

use Bpmore\Copydesk\Style\RuleDefinition;
use Bpmore\Copydesk\Style\StyleRule;
use Bpmore\ReadabilityCore\Text\Block;

/**
 * Headings in the house's case: sentence case or title case.
 *
 * Only heading blocks are looked at. A title field reaches this rule as a
 * heading, which is how the analyzer hands it over (Phase 1.4), so one rule
 * covers both. One finding per heading that is off, with the whole heading
 * as the excerpt and the heading rewritten as the suggestion.
 *
 * Words this rule never changes, in either style: one already written with
 * a capital inside it ("eiSEO", "iPhone"), one in capitals ("UAMS"), one
 * with a digit, the word "I", and anything in the rule's `keep` list, which
 * is how a house tells sentence case its proper names ("Statamic", "Little
 * Rock"). Sentence case cannot know a name it has not been told.
 *
 * Sentence case: the first word, and the first after a colon, begin with a
 * capital; other words do not. Title case: every word begins with a capital
 * except short joining words (a, an, the, and, of, to, ...) in the middle;
 * the first and last words always do. Each part of a hyphenated word is
 * treated as a word.
 */
final class Casing extends StyleRule
{
    /** @var list<string> */
    private const MINOR = ['a', 'an', 'the', 'and', 'but', 'or', 'nor', 'for', 'so', 'yet', 'as', 'at', 'by', 'in', 'of', 'off', 'on', 'per', 'to', 'up', 'via', 'vs'];

    /** @var array<string, string> lowercase => as kept */
    private readonly array $keep;

    public function __construct(RuleDefinition $definition)
    {
        parent::__construct($definition);

        $keep = [];

        foreach ($definition->options['keep'] as $word) {
            foreach (preg_split('/\s+/u', trim($word)) ?: [] as $part) {
                $keep[mb_strtolower($part)] = $part;
            }
        }

        $this->keep = $keep;
    }

    protected function inBlock(Block $block, int $index): array
    {
        if (! $block->isHeading() || trim($block->text) === '') {
            return [];
        }

        $fixed = $this->definition->options['style'] === 'title'
            ? $this->titleCase($block->text)
            : $this->sentenceCase($block->text);

        if ($fixed === $block->text) {
            return [];
        }

        $style = $this->definition->options['style'] === 'title' ? 'title case' : 'sentence case';

        return [$this->finding($index, 0, $block->text, "Write headings in {$style}: \"{$fixed}\". {$this->definition->message}", $fixed)];
    }

    private function sentenceCase(string $text): string
    {
        return $this->eachWord($text, static fn (string $word, bool $afterColon, bool $wordStart, int $position): string => ($position === 0 || $afterColon) && $wordStart
            ? self::capitalise($word)
            : mb_strtolower($word));
    }

    private function titleCase(string $text): string
    {
        $last = -1;
        $this->eachWord($text, function (string $word, bool $afterColon, bool $wordStart, int $position) use (&$last): string {
            $last = $position;

            return $word;
        }, everyWord: true);

        return $this->eachWord($text, function (string $word, bool $afterColon, bool $wordStart, int $position) use ($last): string {
            $edge = $position === 0 || $position === $last || $afterColon;

            return ! $edge && $wordStart && in_array(mb_strtolower($word), self::MINOR, true)
                ? mb_strtolower($word)
                : self::capitalise($word);
        });
    }

    /**
     * The text with each word rewritten by $rewrite, except the words this
     * rule never changes. The callback gets the word, whether it follows a
     * colon, whether it starts a word of its own (false for the second part
     * of a hyphenated one), and which word it is, counting from 0. Kept
     * words are counted too, so first and last are right around them.
     *
     * @param  callable(string, bool, bool, int): string  $rewrite
     * @param  bool  $everyWord  hand the kept words to $rewrite too, for counting
     */
    private function eachWord(string $text, callable $rewrite, bool $everyWord = false): string
    {
        $afterColon = false;
        $wordStart = true;
        $position = -1;

        return (string) preg_replace_callback('/[\p{L}\p{N}\'’]+|[^\p{L}\p{N}\'’]+/u', function (array $m) use ($rewrite, $everyWord, &$afterColon, &$wordStart, &$position): string {
            $piece = $m[0];

            if (preg_match('/^[\p{L}\p{N}]/u', $piece) !== 1) {
                if (str_contains($piece, ':')) {
                    $afterColon = true;
                }

                $wordStart = preg_match('/\s/u', $piece) === 1 || ! str_contains($piece, '-');

                return $piece;
            }

            if ($wordStart) {
                $position++;
            }

            $out = $everyWord ? $rewrite($piece, $afterColon, $wordStart, $position) : ($this->kept($piece) ?? $rewrite($piece, $afterColon, $wordStart, $position));
            $afterColon = false;
            $wordStart = false;

            return $out;
        }, $text);
    }

    /** The word as it must stay, or null when the style decides. */
    private function kept(string $word): ?string
    {
        if (isset($this->keep[mb_strtolower($word)])) {
            return $this->keep[mb_strtolower($word)];
        }

        $rest = mb_substr($word, 1);

        return match (true) {
            $word === 'I',
            preg_match('/\p{N}/u', $word) === 1,
            mb_strlen($word) > 1 && mb_strtoupper($word) === $word && mb_strtolower($word) !== $word,
            $rest !== mb_strtolower($rest) => $word,
            default => null,
        };
    }

    private static function capitalise(string $word): string
    {
        return mb_strtoupper(mb_substr($word, 0, 1)).mb_substr($word, 1);
    }
}
