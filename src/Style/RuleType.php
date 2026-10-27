<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Style;

/**
 * The six kinds of rule a pack can hold, and the keys each one adds to the
 * keys every rule has.
 *
 * - `word_list`: `words`, a map of word or phrase to what to say instead.
 *   The value is a replacement, a list of them, nothing (null: say it
 *   another way), or a map of `replace`, `severity` and `reason`.
 * - `term`: `terms`, the names to spell exactly ("UAMS Health", "eiSEO").
 * - `pattern`: `pattern`, a regular expression without delimiters, and
 *   optional `flags`.
 * - `punctuation`: `checks`, any of the built-in checks, and `quotes`
 *   (`curly` or `straight`) when `quotes` is one of them.
 * - `casing`: `style`, `sentence` or `title`, for headings and titles, and
 *   optional `keep`: words always written as given ("Statamic", "UAMS").
 * - `sentence`: `check`, one of `max_words` (with `max_words`), `passive`
 *   or `starts_with_numeral`.
 */
enum RuleType: string
{
    case WordList = 'word_list';
    case Term = 'term';
    case Pattern = 'pattern';
    case Punctuation = 'punctuation';
    case Casing = 'casing';
    case Sentence = 'sentence';

    /** @var list<string> */
    public const PUNCTUATION_CHECKS = ['em_dash', 'double_space', 'quotes', 'space_before_punctuation', 'repeated_punctuation'];

    /** @var list<string> */
    public const SENTENCE_CHECKS = ['max_words', 'passive', 'starts_with_numeral'];

    /**
     * The keys this type adds, required and optional.
     *
     * @return array{required: list<string>, optional: list<string>}
     */
    public function keys(): array
    {
        return match ($this) {
            self::WordList => ['required' => ['words'], 'optional' => []],
            self::Term => ['required' => ['terms'], 'optional' => []],
            self::Pattern => ['required' => ['pattern'], 'optional' => ['flags']],
            self::Punctuation => ['required' => ['checks'], 'optional' => ['quotes']],
            self::Casing => ['required' => ['style'], 'optional' => ['keep']],
            self::Sentence => ['required' => ['check'], 'optional' => ['max_words']],
        };
    }

    /** @return list<string> */
    public static function names(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }
}
