<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Analysis;

use Bpmore\Copydesk\Settings;
use Bpmore\Copydesk\Style\StyleRule;
use Bpmore\ReadabilityCore\Exclusion\Code;
use Bpmore\ReadabilityCore\Exclusion\ProperNames;
use Bpmore\ReadabilityCore\Extraction\BardExtractor;
use Bpmore\ReadabilityCore\Extraction\HtmlExtractor;
use Bpmore\ReadabilityCore\Extraction\PlainTextExtractor;
use Bpmore\ReadabilityCore\Rule\Finding;
use Bpmore\ReadabilityCore\Text\Block;
use Bpmore\ReadabilityCore\Text\Document;
use Statamic\Facades\Markdown;

/**
 * One field, checked as the author types: what the editor underlines.
 *
 * The same rules as the full check (the readability rules and the house
 * rules for this collection and field), on one field's value, answered as
 * positions the editor can use: the Bard node path of the block, and the
 * offset and length in UTF-16 code units of the block as written, which is
 * how JavaScript and ProseMirror count. Findings about the whole document
 * (a passive-voice rate) and paragraph grades are left to the panel: there
 * is nothing to underline for them.
 *
 * Markdown is rendered before it is read, so its findings have no place in
 * the source the author is typing; they come back with the excerpt and no
 * position, for a list under the field.
 */
final class Linter
{
    /** @var list<string> */
    public const KINDS = ['bard', 'markdown', 'textarea', 'text'];

    public function __construct(
        private readonly Settings $settings,
        private readonly HouseRules $house,
    ) {}

    /**
     * @param  string  $kind  bard, markdown, textarea or text
     * @param  mixed  $value  a Bard document (its content array) or a string
     * @return array{issues: list<array<string, mixed>>, refused: ?string}
     */
    public function lint(string $kind, mixed $value, ?string $collection, string $field, string $locale, ?string $site): array
    {
        $decision = $this->settings->localeGuard()->check($locale);

        if (! $decision->allowed) {
            return ['issues' => [], 'refused' => $decision->message()];
        }

        $document = $this->settings->exclusions()->without(ProperNames::class)->apply($this->extract($kind, $value));
        $house = array_values(array_filter($this->house->for($collection, $site), static fn (StyleRule $rule): bool => $rule->appliesTo($collection ?? '', $field)));
        $written = array_map(static fn (StyleRule $rule): string => $rule->handle(), array_filter($house, static fn (StyleRule $rule): bool => $rule->readsWritten()));
        $byId = [];

        foreach ($house as $rule) {
            $byId[$rule->handle()] = $rule;
        }

        $findings = [...$this->settings->rules()->check($document)->all()];

        foreach ($house as $rule) {
            array_push($findings, ...$rule->check($document));
        }

        $issues = [];

        foreach ($findings as $finding) {
            if ($finding->location === null) {
                continue;
            }

            $block = $document->blocks[$finding->location->block];
            $position = $kind === 'markdown' ? null : self::position($block, $finding, in_array($finding->rule, $written, true));

            $issues[] = [
                'rule' => $finding->rule,
                'severity' => $finding->severity->value,
                'message' => $finding->message,
                'excerpt' => $finding->excerpt,
                'suggestions' => self::suggestions($finding, $byId[$finding->rule] ?? null),
                'path' => $position === null ? null : $block->origin->path,
                'offset' => $position[0] ?? null,
                'length' => $position[1] ?? null,
            ];
        }

        usort($issues, static fn (array $a, array $b): int => [$a['path'] ?? '', $a['offset'] ?? 0] <=> [$b['path'] ?? '', $b['offset'] ?? 0]);

        return ['issues' => $issues, 'refused' => null];
    }

    private function extract(string $kind, mixed $value): Document
    {
        return match ($kind) {
            'bard' => is_array($value) ? (new BardExtractor(ignoredMarks: Code::INLINE_MARKS))->extract($value) : Document::empty(),
            'markdown' => is_string($value) && trim($value) !== ''
                ? (new HtmlExtractor(ignoredTags: Code::INLINE_TAGS, ignoredClasses: $this->settings->ignoredClasses()))->extract(Markdown::parse($value))
                : Document::empty(),
            default => is_string($value) ? (new PlainTextExtractor)->extract($value) : Document::empty(),
        };
    }

    /**
     * A finding's offset and length in the block as written, in UTF-16 code
     * units, or null when the block cannot say where.
     *
     * @return array{int, int}|null
     */
    private static function position(Block $block, Finding $finding, bool $inWritten): ?array
    {
        if ($block->written === null) {
            return null;
        }

        $span = $inWritten
            ? [$finding->location->offset, $finding->location->offset + $finding->location->length]
            : $block->writtenSpan($finding->location->offset, $finding->location->length);

        if ($span === null) {
            return null;
        }

        [$from, $to] = $span;

        return [self::utf16(substr($block->written, 0, $from)), self::utf16(substr($block->written, $from, $to - $from))];
    }

    private static function utf16(string $text): int
    {
        return intdiv(strlen((string) mb_convert_encoding($text, 'UTF-16LE', 'UTF-8')), 2);
    }

    /**
     * What to offer instead: the finding's own suggestion first, then the
     * rule's other suggestions.
     *
     * @return list<string>
     */
    private static function suggestions(Finding $finding, ?StyleRule $rule): array
    {
        $all = [$finding->suggestion, ...($rule?->definition->suggestions ?? [])];

        return array_values(array_unique(array_filter($all, static fn (?string $s): bool => $s !== null)));
    }
}
