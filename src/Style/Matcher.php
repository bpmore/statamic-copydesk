<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Style;

/**
 * Finds words and phrases in text: whole words, any case, a phrase's spaces
 * matching across a line break, and a single word taking its inflections.
 *
 * The same rules as readability-core's jargon rule, so the dictionary a site
 * built for Plain finds the same words once it is a `word_list` pack. That
 * class is internal to the engine, so the rules are kept here rather than
 * borrowed; a test holds the two to the same answers.
 */
final class Matcher
{
    /**
     * A pattern for one word or phrase. A single word also finds its
     * inflections: "utilize" finds "utilizes", "utilized", "utilizing";
     * "notify" finds "notifies", "notified", "notifying".
     */
    public static function pattern(string $phrase, bool $inflections = true): string
    {
        $words = preg_split('/\s+/u', trim($phrase)) ?: [];

        if (! $inflections || count($words) > 1 || preg_match('/\p{L}$/u', $phrase) !== 1) {
            return '/(?<![\p{L}\p{N}])'.implode('\s+', array_map(static fn (string $w): string => preg_quote($w, '/'), $words)).'(?![\p{L}\p{N}])/iu';
        }

        $inflected = match (true) {
            str_ends_with($phrase, 'e') => preg_quote(substr($phrase, 0, -1), '/').'(?:e|es|ed|ing)',
            str_ends_with($phrase, 'y') => preg_quote(substr($phrase, 0, -1), '/').'(?:y|ies|ied|ying)',
            default => preg_quote($phrase, '/').'(?:s|es|ed|ing)?',
        };

        return '/(?<![\p{L}\p{N}])'.$inflected.'(?![\p{L}\p{N}])/iu';
    }

    /**
     * Every match of every pattern, longest first, with a shorter phrase
     * never reported inside a longer one that already matched there.
     *
     * @template T
     *
     * @param  list<array{string, T}>  $patterns  a pattern, and what to hand back with each of its matches
     * @return list<array{int, string, T}> byte offset, the text as written, the payload; in reading order
     */
    public static function findAll(string $text, array $patterns): array
    {
        $found = [];
        $taken = [];

        foreach ($patterns as [$pattern, $payload]) {
            preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches[0] as [$written, $offset]) {
                $end = $offset + strlen($written);

                foreach ($taken as [$from, $to]) {
                    if ($offset < $to && $end > $from) {
                        continue 2;
                    }
                }

                $taken[] = [$offset, $end];
                $found[] = [$offset, $written, $payload];
            }
        }

        usort($found, static fn (array $a, array $b): int => $a[0] <=> $b[0]);

        return $found;
    }

    /**
     * A replacement in the case the author used: "Use" for "Utilize" at the
     * start of a sentence, "USE" for "UTILIZE". Anything else is left as
     * the pack wrote it, so "eiSEO" stays "eiSEO".
     */
    public static function inCaseOf(string $written, string $replacement): string
    {
        if ($replacement === '' || mb_strtolower($replacement) !== $replacement) {
            return $replacement;
        }

        if (mb_strlen($written) > 1 && mb_strtoupper($written) === $written) {
            return mb_strtoupper($replacement);
        }

        $first = mb_substr($written, 0, 1);

        return mb_strtoupper($first) === $first && mb_strtolower($first) !== $first
            ? mb_strtoupper(mb_substr($replacement, 0, 1)).mb_substr($replacement, 1)
            : $replacement;
    }
}
