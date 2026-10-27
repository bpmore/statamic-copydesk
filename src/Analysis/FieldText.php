<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Analysis;

use Bpmore\ReadabilityCore\Exclusion\Code;
use Bpmore\ReadabilityCore\Extraction\BardExtractor;
use Bpmore\ReadabilityCore\Extraction\HtmlExtractor;
use Bpmore\ReadabilityCore\Extraction\PlainTextExtractor;
use Bpmore\ReadabilityCore\Text\Block;
use Bpmore\ReadabilityCore\Text\Document;
use Bpmore\ReadabilityCore\Text\Origin;
use Statamic\Facades\Markdown;
use Statamic\Fields\Field;

/**
 * A field's value as the engine's blocks, by what kind of field it is.
 *
 * Bard is walked as the node tree Statamic stores; a Bard field set to
 * save HTML instead stores a string of markup, and is read as markup.
 * Markdown is rendered with the site's own parser and its own extensions,
 * so the text scored is the text published. Textarea and text are read as
 * they are. Anything else is not prose and is left alone, whatever the
 * config lists.
 *
 * A configured field is read with the prose nested in it (`read`): the
 * Bard, markdown and textarea fields in a Grid, a Replicator, a Group or
 * a Bard set, at any depth. The same fields the editor checks as the
 * author types, so the gate and the report count what was underlined. A
 * nested text field is a caption or a button label, and is left alone.
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
            'bard' => match (true) {
                is_array($value) => (new BardExtractor(ignoredMarks: Code::INLINE_MARKS))->extract($value),
                is_string($value) && is_array(json_decode($value, true)) => (new BardExtractor(ignoredMarks: Code::INLINE_MARKS))->extract($value),
                is_string($value) && trim($value) !== '' => (new HtmlExtractor(ignoredTags: Code::INLINE_TAGS, ignoredClasses: $ignoredClasses))->extract($value),
                default => Document::empty(),
            },
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

    /**
     * A configured field's value as blocks: the field itself when it is
     * prose, then the prose nested in it, in the order the form has it.
     * Null when there is no prose in it at all.
     *
     * A block nested in a set carries the set's handle, and every handle
     * around it, so excluding a set (`bard_sets`) leaves out what is in it
     * however deep. The engine reads a Bard field nested in a Bard set
     * itself, so those stay in the tree; any other field in a set is taken
     * out of the tree and read here, where the blueprint says what it is.
     *
     * @param  list<string>  $ignoredClasses
     */
    public static function read(Field $field, mixed $value, array $ignoredClasses = []): ?Document
    {
        if (! self::isProse($field) && ! self::holdsProse($field)) {
            return null;
        }

        $blocks = [];
        self::collect($field, $value, $ignoredClasses, [], true, $blocks);

        return new Document($blocks);
    }

    /** Whether a Bard, markdown or textarea field is nested anywhere in this one. */
    public static function holdsProse(Field $field, int $depth = 0): bool
    {
        // Deep enough for any real form; a fieldset that imports itself stops here.
        if ($depth > 8) {
            return false;
        }

        foreach (self::children($field) as $child) {
            if (self::isNestedProse($child) || self::holdsProse($child, $depth + 1)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $classes
     * @param  list<string>  $sets  the set handles around this field, outermost first
     * @param  list<Block>  $blocks
     */
    private static function collect(Field $field, mixed $value, array $classes, array $sets, bool $top, array &$blocks): void
    {
        switch ($field->type()) {
            case 'bard':
                if (is_string($value) && is_array($decoded = json_decode($value, true))) {
                    $value = $decoded;
                }

                $later = [];

                if (is_array($value) && array_is_list($value)) {
                    $value = self::lift($field, $value, $sets, $later);
                }

                self::append(self::extract($field, $value, $classes), $sets, $blocks);

                foreach ($later as [$nested, $nestedValue, $nestedSets]) {
                    self::collect($nested, $nestedValue, $classes, $nestedSets, false, $blocks);
                }

                return;

            case 'grid':
                foreach (self::rows($value) as $row) {
                    foreach ($field->fieldtype()->fields()->all() as $handle => $nested) {
                        self::collect($nested, $row[$handle] ?? null, $classes, $sets, false, $blocks);
                    }
                }

                return;

            case 'group':
                if (is_array($value)) {
                    foreach ($field->fieldtype()->fields()->all() as $handle => $nested) {
                        self::collect($nested, $value[$handle] ?? null, $classes, $sets, false, $blocks);
                    }
                }

                return;

            case 'replicator':
                foreach (self::rows($value) as $row) {
                    $set = $row['type'] ?? null;

                    if (! is_string($set) || ($row['enabled'] ?? true) === false) {
                        continue;
                    }

                    foreach ($field->fieldtype()->fields($set)->all() as $handle => $nested) {
                        self::collect($nested, $row[$handle] ?? null, $classes, [...$sets, $set], false, $blocks);
                    }
                }

                return;

            default:
                if ($top || self::isNestedProse($field)) {
                    self::append(self::extract($field, $value, $classes), $sets, $blocks);
                }
        }
    }

    /**
     * A Bard tree with every field in its sets that is not Bard taken out,
     * into `$later`, to be read by its own type. The node list keeps its
     * shape, so every path the editor uses stays the same.
     *
     * @param  list<mixed>  $nodes
     * @param  list<string>  $sets
     * @param  list<array{Field, mixed, list<string>}>  $later
     * @return list<mixed>
     */
    private static function lift(Field $bard, array $nodes, array $sets, array &$later): array
    {
        foreach ($nodes as $i => $node) {
            if (! is_array($node) || ($node['type'] ?? null) !== 'set' || ! is_array($node['attrs']['values'] ?? null)) {
                continue;
            }

            $values = $node['attrs']['values'];
            $set = $values['type'] ?? null;

            // A disabled set is skipped whole, by the engine and here.
            if (! is_string($set) || ($node['attrs']['enabled'] ?? true) === false) {
                continue;
            }

            $fields = $bard->fieldtype()->fields($set)->all();

            foreach ($values as $handle => $value) {
                $nested = $handle === 'type' ? null : $fields->get($handle);

                if ($nested === null) {
                    continue;
                }

                if ($nested->type() === 'bard') {
                    if (is_array($value) && array_is_list($value)) {
                        $values[$handle] = self::lift($nested, $value, [...$sets, $set], $later);
                    }

                    continue;
                }

                unset($values[$handle]);
                $later[] = [$nested, $value, [...$sets, $set]];
            }

            $nodes[$i]['attrs']['values'] = $values;
        }

        return $nodes;
    }

    /**
     * @param  list<string>  $sets
     * @param  list<Block>  $blocks
     */
    private static function append(?Document $document, array $sets, array &$blocks): void
    {
        foreach ($document?->blocks ?? [] as $block) {
            $blocks[] = $sets === [] ? $block : new Block(
                $block->kind,
                $block->text,
                new Origin($block->origin->tag, $block->origin->ancestors, $block->origin->classes, [...$sets, ...$block->origin->sets], $block->origin->path, $block->origin->ids, $block->origin->roles),
                $block->level,
                $block->written,
                $block->positions,
            );
        }
    }

    /** A field read when it is nested: prose, but not a text field. */
    private static function isNestedProse(Field $field): bool
    {
        return $field->type() !== 'text' && self::isProse($field);
    }

    /**
     * The fields a container holds, across all its sets.
     *
     * @return iterable<Field>
     */
    private static function children(Field $field): iterable
    {
        return match ($field->type()) {
            'grid', 'group' => $field->fieldtype()->fields()->all(),
            'replicator', 'bard' => collect(array_keys($field->fieldtype()->flattenedSetsConfig()->all()))
                ->flatMap(fn (string $set) => $field->fieldtype()->fields($set)->all()->values()),
            default => [],
        };
    }

    /**
     * The rows of a Grid or Replicator value.
     *
     * @return list<array<string, mixed>>
     */
    private static function rows(mixed $value): array
    {
        return is_array($value) ? array_values(array_filter($value, is_array(...))) : [];
    }
}
