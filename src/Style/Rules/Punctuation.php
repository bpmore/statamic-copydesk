<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Style\Rules;

use Bpmore\Copydesk\Style\StyleRule;
use Bpmore\ReadabilityCore\Text\Block;

/**
 * The built-in punctuation checks a pack turns on by name.
 *
 * - `em_dash`: an em dash (U+2014).
 * - `double_space`: two or more spaces between words.
 * - `quotes`: quotes of the kind the house does not use. With `curly`,
 *   straight quotes and apostrophes are flagged, and the suggestion is the
 *   curly one that fits where it stands (opening after a space or at the
 *   start, closing otherwise). With `straight`, curly ones are flagged.
 * - `space_before_punctuation`: a space before , . ; : ! or ?
 * - `repeated_punctuation`: the same mark twice or more ("!!", "??", ",,"),
 *   and two dots or four or more. Three dots are an ellipsis and are left
 *   alone.
 *
 * Each finding says what was found and what to do, then the rule's message.
 *
 * These checks read what the author wrote, not the engine's cleaned text,
 * because the cleaning is exactly what hides them: it folds runs of spaces,
 * straightens quotes, and tidies a space before a comma away. So the
 * findings are positions in `Block::$written`. A mark in text the engine
 * does not read at all (inline code, an address cut out of the sentence)
 * is left alone, as every other rule leaves it.
 */
final class Punctuation extends StyleRule
{
    public function readsWritten(): bool
    {
        return true;
    }

    protected function inBlock(Block $block, int $index): array
    {
        $findings = [];
        $text = $this->textOf($block);

        foreach ($this->definition->options['checks'] as $check) {
            foreach ($this->find($check, $text) as [$offset, $written, $says, $suggestion]) {
                if ($block->written !== null && $block->isRead($offset, $offset + strlen($written)) === false) {
                    continue;
                }

                $findings[] = $this->finding($index, $offset, $written, $says.' '.$this->definition->message, $suggestion);
            }
        }

        usort($findings, static fn ($a, $b): int => $a->location->offset <=> $b->location->offset);

        return $findings;
    }

    /**
     * @return list<array{int, string, string, ?string}> offset, the text as written, what to say, the suggestion
     */
    private function find(string $check, string $text): array
    {
        return match ($check) {
            'em_dash' => self::all('/—/u', $text, static fn (): array => ['An em dash.', null]),
            'double_space' => self::all('/(?<=\S) {2,}(?=\S)/u', $text, static fn (): array => ['More than one space between words.', ' ']),
            'quotes' => $this->definition->options['quotes'] === 'curly' ? self::straightQuotes($text) : self::curlyQuotes($text),
            'space_before_punctuation' => self::all('/(?<=\S)[ \t]+[,.;:!?](?!\.)/u', $text, static fn (string $m): array => ['A space before "'.ltrim($m).'".', ltrim($m)]),
            'repeated_punctuation' => self::all('/([!?,;:])\1+|(?<!\.)\.\.(?!\.)|\.{4,}/u', $text, static fn (string $m): array => [
                '"'.$m.'" is more marks than one.',
                $m[0] === '.' && strlen($m) > 3 ? '...' : $m[0],
            ]),
        };
    }

    /**
     * @param  callable(string): array{string, ?string}  $describe
     * @return list<array{int, string, string, ?string}>
     */
    private static function all(string $regex, string $text, callable $describe): array
    {
        preg_match_all($regex, $text, $matches, PREG_OFFSET_CAPTURE);

        return array_map(static fn (array $m): array => [$m[1], $m[0], ...$describe($m[0])], $matches[0]);
    }

    /**
     * Straight quotes, in a house that uses curly ones, each with the curly
     * quote that belongs where it stands.
     *
     * @return list<array{int, string, string, ?string}>
     */
    private static function straightQuotes(string $text): array
    {
        preg_match_all('/["\']/u', $text, $matches, PREG_OFFSET_CAPTURE);

        $found = [];

        foreach ($matches[0] as [$quote, $offset]) {
            $before = $offset === 0 ? '' : substr($text, $offset - 1, 1);
            $opening = $before === '' || ctype_space($before) || in_array($before, ['(', '[', '{', '-'], true);

            $curly = match (true) {
                $quote === '"' => $opening ? '“' : '”',
                default => $opening ? '‘' : '’',
            };

            $found[] = [$offset, $quote, $quote === "'" && ! $opening ? 'A straight apostrophe.' : 'A straight quote.', $curly];
        }

        return $found;
    }

    /**
     * Curly quotes, in a house that uses straight ones.
     *
     * @return list<array{int, string, string, ?string}>
     */
    private static function curlyQuotes(string $text): array
    {
        return self::all('/[“”‘’]/u', $text, static fn (string $m): array => [
            'A curly quote.',
            in_array($m, ['“', '”'], true) ? '"' : "'",
        ]);
    }
}
