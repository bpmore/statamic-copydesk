<?php

declare(strict_types=1);

namespace Bpmore\Plain\Analysis;

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
            // The sentence the finding sits in, and where in it, so the panel
            // can show the flagged words in their own sentence.
            'context' => $location === null ? null : $this->context($location),
            'spans' => array_map(fn (Location $span): array => [
                'field' => $this->blockFields[$span->block] ?? null,
                'block' => $span->block,
                'offset' => $span->offset,
                'length' => $span->length,
                'context' => $this->context($span),
            ], $finding->spans),
        ];
    }

    /**
     * The sentence around a location, with the location's place in it. In
     * characters, not bytes: the panel is JavaScript, and "café" would
     * otherwise put the mark one place to the right.
     *
     * @return array{text: string, offset: int, length: int}
     */
    private function context(Location $location): array
    {
        $text = $this->document->blocks[$location->block]->text ?? '';

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
