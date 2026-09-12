<?php

declare(strict_types=1);

namespace Bpmore\Plain\Analysis;

use Bpmore\ReadabilityCore\Exclusion\Code;
use Bpmore\ReadabilityCore\Extraction\BardExtractor;
use Bpmore\ReadabilityCore\Extraction\HtmlExtractor;
use Bpmore\ReadabilityCore\Extraction\PlainTextExtractor;
use Bpmore\ReadabilityCore\Text\Document;
use Statamic\Facades\Markdown;
use Statamic\Fields\Field;

/**
 * A field's value as the engine's blocks, by what kind of field it is.
 *
 * Bard is walked as the node tree Statamic stores. Markdown is rendered
 * with the site's own parser and its own extensions, so the text scored is
 * the text published. Textarea and text are read as they are. Anything
 * else is not prose and is left alone, whatever the config lists.
 */
final class FieldText
{
    /** @var list<string> */
    public const PROSE_TYPES = ['bard', 'markdown', 'textarea', 'text'];

    /**
     * @param  list<string>  $ignoredClasses  the site's "leave this out" classes, for rendered markup
     */
    public static function extract(Field $field, mixed $value, array $ignoredClasses = []): ?Document
    {
        return match ($field->type()) {
            'bard' => is_array($value) || is_string($value)
                ? (new BardExtractor(ignoredMarks: Code::INLINE_MARKS))->extract($value)
                : Document::empty(),
            'markdown' => is_string($value) && trim($value) !== ''
                ? (new HtmlExtractor(ignoredTags: Code::INLINE_TAGS, ignoredClasses: $ignoredClasses))->extract(Markdown::parse($value))
                : Document::empty(),
            'textarea', 'text' => is_string($value)
                ? (new PlainTextExtractor)->extract($value)
                : Document::empty(),
            default => null,
        };
    }

    public static function isProse(Field $field): bool
    {
        return in_array($field->type(), self::PROSE_TYPES, true);
    }
}
