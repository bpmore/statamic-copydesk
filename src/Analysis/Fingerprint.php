<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Analysis;

use Bpmore\ReadabilityCore\Rule\Finding;
use Bpmore\ReadabilityCore\Text\Block;
use Bpmore\ReadabilityCore\Text\EnglishSegmenter;

/**
 * What "Ignore here" remembers: the rule, and a hash of the sentence the
 * finding sits in. Stored on the entry, in a hidden field.
 *
 * The sentence, and not the words or their place, so an ignore holds while
 * the author edits other paragraphs, and lapses when they change the
 * sentence itself: a rewritten sentence is a new question. Spacing is
 * folded first, so retyping a space does not undo an ignore.
 *
 * The editor and the full check build the same blocks from the same field
 * (the same extractor, the same exclusions), so a fingerprint made as the
 * author types matches the one the gate makes when they publish.
 */
final class Fingerprint
{
    /** The hidden field on the entry that holds the list. */
    public const FIELD = 'copydesk_ignores';

    /**
     * @param  bool  $inWritten  the finding's place is in the text as written (Block::$written)
     */
    public static function of(Block $block, Finding $finding, bool $inWritten): ?string
    {
        if ($finding->location === null) {
            return null;
        }

        $text = ($inWritten ? $block->written : null) ?? $block->text;
        $offset = $finding->location->offset;
        $sentence = null;

        foreach ((new EnglishSegmenter)->sentences($text) as $candidate) {
            if ($offset >= $candidate->offset && $offset < $candidate->offset + strlen($candidate->text)) {
                $sentence = $candidate->text;
                break;
            }
        }

        // Not in any sentence (a heading the splitter makes nothing of):
        // the whole block stands in for it.
        $sentence ??= $text;
        $folded = (string) preg_replace('/\s+/u', ' ', trim($sentence));

        return $finding->rule.':'.substr(sha1($folded), 0, 12);
    }

    /**
     * The fingerprints stored on an entry, whatever shape the value has
     * come back in.
     *
     * @return list<string>
     */
    public static function stored(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter($value, static fn (mixed $v): bool => is_string($v) && $v !== '')));
    }
}
