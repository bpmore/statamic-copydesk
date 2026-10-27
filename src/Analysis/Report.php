<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Analysis;

use Bpmore\ReadabilityCore\Formula\Band;
use Bpmore\ReadabilityCore\Formula\Comparison;
use Bpmore\ReadabilityCore\Formula\Counts;
use Bpmore\ReadabilityCore\Formula\Grades;
use Bpmore\ReadabilityCore\Formula\Section;
use Bpmore\ReadabilityCore\Formula\Target;
use Bpmore\ReadabilityCore\Locale\Decision;
use Bpmore\ReadabilityCore\Rule\Finding;
use Bpmore\ReadabilityCore\Rule\Findings;
use Bpmore\ReadabilityCore\Rule\Location;
use Bpmore\ReadabilityCore\Rule\Severity;
use Bpmore\ReadabilityCore\Text\Document;
use Bpmore\ReadabilityCore\Text\EnglishSegmenter;

/**
 * What the panel is told about a page: the band against the target, the
 * sections, and every finding with enough around it to be drawn where the
 * author is looking. `toArray()` is the endpoint's JSON.
 */
final class Report
{
    /**
     * @param  list<string>  $fields  handles of the fields that were read
     * @param  list<string>  $blockFields  the field each block of the document came from, by index
     * @param  list<Section>  $sections
     * @param  list<string>  $notices  things the author should know that are not findings
     * @param  list<string>  $writtenRules  rules whose findings are already positions in what the author wrote (Block::$written)
     */
    public function __construct(
        public readonly Decision $locale,
        public readonly Target $target,
        public readonly Document $document,
        public readonly array $fields,
        public readonly array $blockFields,
        public readonly Counts $counts,
        public readonly ?Grades $grades,
        public readonly array $sections,
        public readonly Findings $findings,
        public readonly array $notices = [],
        public readonly array $writtenRules = [],
    ) {}

    public static function refused(Decision $locale, Target $target): self
    {
        return new self($locale, $target, Document::empty(), [], [], Counts::none(), null, [], Findings::none());
    }

    public function band(): ?Band
    {
        return $this->grades?->band();
    }

    public function comparison(): ?Comparison
    {
        $band = $this->band();

        return $band === null ? null : $this->target->compare($band);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $grades = $this->grades;

        return [
            'locale' => [
                'allowed' => $this->locale->allowed,
                'locale' => $this->locale->locale,
                'message' => $this->locale->message(),
            ],
            'fields' => $this->fields,
            'notices' => $this->notices,
            'band' => self::band_($this->band()),
            'target' => self::band_($this->target->band),
            'comparison' => $this->comparison()?->value,
            'grades' => $grades === null ? null : [
                'flesch_kincaid' => round($grades->fleschKincaid, 1),
                'gunning_fog' => round($grades->gunningFog, 1),
                'smog' => round($grades->smog, 1),
                'coleman_liau' => round($grades->colemanLiau, 1),
                'reading_ease' => round($grades->readingEase, 1),
                'consensus' => round($grades->consensus(), 1),
            ],
            'counts' => [
                'sentences' => $this->counts->sentences,
                'words' => $this->counts->words,
                'syllables' => $this->counts->syllables,
            ],
            'sections' => array_map(fn (Section $section): array => [
                'title' => $section->title(),
                'level' => $section->level(),
                'lead' => $section->isLead(),
                'band' => self::band_($section->band()),
                'comparison' => $section->band() === null ? null : $this->target->compare($section->band())->value,
                'words' => $section->counts->words,
                // The two numbers people most often ask for, beside the band.
                'grades' => ($g = Grades::of($section->counts)) === null ? null : [
                    'flesch_kincaid' => round($g->fleschKincaid, 1),
                    'reading_ease' => round($g->readingEase, 1),
                ],
                'findings' => count($section->findings($this->findings)),
                'blocks' => $section->indexes,
            ], $this->sections),
            'findings' => array_map($this->finding(...), $this->findings->all()),
            'summary' => [
                'errors' => count($this->findings->ofSeverity(Severity::Error)),
                'warnings' => count($this->findings->ofSeverity(Severity::Warning)),
                'tips' => count($this->findings->ofSeverity(Severity::Tip)),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function finding(Finding $finding): array
    {
        $location = $finding->location;

        return [
            'rule' => $finding->rule,
            'severity' => $finding->severity->value,
            'message' => $finding->message,
            'excerpt' => $finding->excerpt,
            'suggestion' => $finding->suggestion,
            'field' => $location === null ? null : ($this->blockFields[$location->block] ?? null),
            'block' => $location?->block,
            'offset' => $location?->offset,
            'length' => $location?->length,
            // Where it is in the field as the author wrote it, for the editor.
            'written' => $location === null ? null : $this->written($finding->rule, $location),
            // The sentence the finding sits in, and where in it, so the panel
            // can show the flagged words in their own sentence.
            'context' => $location === null ? null : $this->context($location, in_array($finding->rule, $this->writtenRules, true)),
            'spans' => array_map(fn (Location $span): array => [
                'field' => $this->blockFields[$span->block] ?? null,
                'block' => $span->block,
                'offset' => $span->offset,
                'length' => $span->length,
                'written' => $this->written($finding->rule, $span),
                'context' => $this->context($span, in_array($finding->rule, $this->writtenRules, true)),
            ], $finding->spans),
        ];
    }

    /**
     * Where a finding is in the block as the author wrote it: the block's
     * place in its field (the Bard node path, for the editor), and the
     * finding's offset and length in characters. Most rules report a place
     * in the cleaned text, carried back through the block's map; a rule
     * that reads the written text already reports one there. Null when the
     * block does not know what was written.
     *
     * @return array{path: ?string, offset: int, length: int}|null
     */
    private function written(string $rule, Location $location): ?array
    {
        $block = $this->document->blocks[$location->block] ?? null;

        if ($block === null || $block->written === null) {
            return null;
        }

        $span = in_array($rule, $this->writtenRules, true)
            ? [$location->offset, $location->offset + $location->length]
            : $block->writtenSpan($location->offset, $location->length);

        if ($span === null) {
            return null;
        }

        [$from, $to] = $span;

        return [
            'path' => $block->origin->path,
            'offset' => mb_strlen(substr($block->written, 0, $from)),
            'length' => mb_strlen(substr($block->written, $from, $to - $from)),
        ];
    }

    /**
     * The sentence around a location, with the location's place in it. In
     * characters, not bytes: the panel is JavaScript, and "café" would
     * otherwise put the mark one place to the right. A location in the
     * written text is shown in the written text's own sentence.
     *
     * @return array{text: string, offset: int, length: int}
     */
    private function context(Location $location, bool $inWritten = false): array
    {
        $block = $this->document->blocks[$location->block] ?? null;
        $text = ($inWritten ? $block?->written : null) ?? $block?->text ?? '';

        foreach ((new EnglishSegmenter)->sentences($text) as $sentence) {
            $end = $sentence->offset + strlen($sentence->text);

            if ($location->offset >= $sentence->offset && $location->offset < $end) {
                $from = $location->offset - $sentence->offset;
                $length = min($location->length, $end - $location->offset);

                return [
                    'text' => $sentence->text,
                    'offset' => mb_strlen(substr($sentence->text, 0, $from)),
                    'length' => mb_strlen(substr($sentence->text, $from, $length)),
                ];
            }
        }

        $span = substr($text, $location->offset, $location->length);

        return ['text' => $span, 'offset' => 0, 'length' => mb_strlen($span)];
    }

    /**
     * @return array{low: int, high: ?int, label: string}|null
     */
    private static function band_(?Band $band): ?array
    {
        return $band === null ? null : ['low' => $band->low, 'high' => $band->high, 'label' => $band->label()];
    }
}
