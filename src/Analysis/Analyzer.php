<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Analysis;

use Bpmore\Copydesk\Settings;
use Bpmore\Copydesk\Style\Rules\Casing;
use Bpmore\ReadabilityCore\Exclusion\Pipeline;
use Bpmore\ReadabilityCore\Exclusion\ProperNames;
use Bpmore\ReadabilityCore\Extraction\PlainTextExtractor;
use Bpmore\ReadabilityCore\Formula\Grades;
use Bpmore\ReadabilityCore\Formula\Sections;
use Bpmore\ReadabilityCore\Formula\Tally;
use Bpmore\ReadabilityCore\Formula\Target;
use Bpmore\ReadabilityCore\Rule\Finding;
use Bpmore\ReadabilityCore\Rule\Findings;
use Bpmore\ReadabilityCore\Rule\Location;
use Bpmore\ReadabilityCore\Rule\Severity;
use Bpmore\ReadabilityCore\Text\Block;
use Bpmore\ReadabilityCore\Text\BlockKind;
use Bpmore\ReadabilityCore\Text\Document;
use Bpmore\ReadabilityCore\Text\EnglishSegmenter;
use Bpmore\ReadabilityCore\Text\EnglishSyllableCounter;
use Bpmore\ReadabilityCore\Text\Origin;
use Statamic\Fields\Blueprint;

/**
 * The whole check, for one form's values: the locale guard, the fields
 * the site said are prose, the exclusions, the grade against the target,
 * the sections, the readability rules and the house rules.
 *
 * Each field is excluded on its own before the fields are joined, so the
 * block that a finding names can be traced back to its field; an
 * exclusion applied to the joined document would drop blocks and shift
 * every index after them. House rules run field by field for the same
 * reason, and because a rule's scope names fields: each runs only on the
 * fields and the collection it is for.
 *
 * The title is not prose to grade, but house style covers it: a casing
 * rule checks it as a heading. It is added after the sections are cut, so
 * it is never a section or part of a grade.
 *
 * A paragraph that reads well above the target is marked (`hard_paragraph`):
 * the page's band can be on target while one paragraph is far past it.
 * Short paragraphs are left alone, because the formulas mean little on a
 * handful of sentences.
 *
 * A finding the author ignored on this entry (Fingerprint) is left out and
 * counted.
 */
class Analyzer
{
    public const HARD_PARAGRAPH = 'hard_paragraph';

    public function __construct(
        private readonly Settings $settings,
        private readonly HouseRules $house,
    ) {}

    /**
     * @param  array<string, mixed>  $values  the form's values, processed through the blueprint
     * @param  ?string  $site  the site the entry is for, whose word list the house rules use
     */
    public function analyze(Blueprint $blueprint, array $values, string $locale, ?string $site = null): Report
    {
        $collection = self::collection($blueprint);
        $target = $this->settings->target($collection);
        $decision = $this->settings->localeGuard()->check($locale);

        if (! $decision->allowed) {
            return Report::refused($decision, $target);
        }

        $pipeline = $this->settings->exclusions();
        $structural = $pipeline->without(ProperNames::class);
        $grading = $pipeline->only(ProperNames::class);
        $house = $this->house->for($collection, $site);

        $blocks = [];
        $blockFields = [];
        $read = [];
        $notices = [];
        $found = [];

        $fields = $this->settings->fieldsFor((string) $blueprint->handle());

        foreach ($fields as $handle) {
            $field = $blueprint->field($handle);

            // The field and the prose nested in it, as one document: a
            // finding in a Bard inside a Grid counts as being in the Grid,
            // which is what a rule's scope names.
            $document = $field === null ? null : FieldText::read($field, $values[$handle] ?? null, $this->settings->ignoredClasses());

            if ($document === null) {
                continue;
            }

            $read[] = $handle;
            $document = $structural->apply($document);

            foreach ($house as $rule) {
                if ($rule->appliesTo($collection ?? '', $handle)) {
                    foreach ($rule->check($document) as $finding) {
                        $found[] = self::shift($finding, count($blocks));
                    }
                }
            }

            foreach ($document as $block) {
                $blocks[] = $block;
                $blockFields[] = $handle;
            }
        }

        if ($read === []) {
            $notices[] = 'None of the fields this check reads is on this form. The fields it reads are set in the addon\'s settings.';
        }

        $document = new Document($blocks);
        $tally = new Tally(new EnglishSegmenter, new EnglishSyllableCounter);
        $counts = $tally->of($grading->apply($document));
        $sections = Sections::of($document, $tally, $grading);

        $found = [
            ...$this->settings->rules()->check($document)->all(),
            ...$found,
            ...$this->hardParagraphs($document, $tally, $grading, $target),
        ];

        // The title, checked as a heading by the casing rules, after the
        // sections and the grade so it is part of neither.
        $title = in_array('title', $fields, true) ? null : self::title($blueprint, $values);
        $casing = array_filter($house, static fn ($rule): bool => $rule instanceof Casing && $rule->appliesTo($collection ?? '', 'title'));

        if ($title !== null && $casing !== []) {
            foreach ($casing as $rule) {
                foreach ($rule->check(new Document([$title])) as $finding) {
                    $found[] = self::shift($finding, count($blocks));
                }
            }

            $blocks[] = $title;
            $blockFields[] = 'title';
        }

        // What the author chose to ignore here: left out of everything
        // that follows (the panel, the gate, the scan), and counted, so an
        // ignore can never quietly hide a problem.
        $written = array_values(array_map(static fn ($rule): string => $rule->handle(), array_filter($house, static fn ($rule): bool => $rule->readsWritten())));
        $ignores = Fingerprint::stored($values[Fingerprint::FIELD] ?? null);
        $ignored = 0;

        if ($ignores !== []) {
            $found = array_values(array_filter($found, static function (Finding $finding) use ($blocks, $written, $ignores, &$ignored): bool {
                $block = $finding->location === null ? null : ($blocks[$finding->location->block] ?? null);
                $print = $block === null ? null : Fingerprint::of($block, $finding, in_array($finding->rule, $written, true));

                if ($print !== null && in_array($print, $ignores, true)) {
                    $ignored++;

                    return false;
                }

                return true;
            }));
        }

        return new Report(
            $decision,
            $target,
            new Document($blocks),
            $read,
            $blockFields,
            $counts,
            Grades::of($counts),
            $sections,
            new Findings($found),
            $notices,
            $written,
            $ignored,
        );
    }

    /**
     * Paragraphs that read well above the target: their own band starts more
     * than `above_by` grades past the top of it.
     *
     * @return list<Finding>
     */
    private function hardParagraphs(Document $document, Tally $tally, Pipeline $grading, Target $target): array
    {
        $options = $this->settings->paragraphs();

        if (! $options['enabled']) {
            return [];
        }

        $limit = ($target->band->high ?? $target->band->low) + $options['above_by'];
        $findings = [];

        foreach ($document->blocks as $index => $block) {
            if ($block->kind !== BlockKind::Paragraph) {
                continue;
            }

            $counts = $tally->of($grading->apply(new Document([$block])));
            $band = $counts->words >= $options['min_words'] ? Grades::of($counts)?->band() : null;

            if ($band === null || $band->low <= $limit) {
                continue;
            }

            $findings[] = new Finding(
                self::HARD_PARAGRAPH,
                Severity::Warning,
                sprintf('This paragraph reads at %s, well above your target of %s. Shorter sentences and plainer words bring it down most.', $band->label(), $target->band->label()),
                new Location($index, 0, strlen($block->text)),
                $block->text,
            );
        }

        return $findings;
    }

    /** The collection a blueprint belongs to, or null for anything else. */
    private static function collection(Blueprint $blueprint): ?string
    {
        $namespace = $blueprint->namespace();

        return is_string($namespace) && str_starts_with($namespace, 'collections.') ? substr($namespace, strlen('collections.')) : null;
    }

    /**
     * The title, as a heading block, or null when the form has none.
     *
     * @param  array<string, mixed>  $values
     */
    private static function title(Blueprint $blueprint, array $values): ?Block
    {
        $value = $values['title'] ?? null;

        if ($blueprint->field('title') === null || ! is_string($value) || trim($value) === '') {
            return null;
        }

        $block = (new PlainTextExtractor)->extract($value)->blocks[0] ?? null;

        return $block === null ? null : new Block(BlockKind::Heading, $block->text, new Origin('h1'), 1, $block->written, $block->positions);
    }

    /**
     * A finding from one field's document, with its block numbered as in
     * the joined document.
     */
    private static function shift(Finding $finding, int $by): Finding
    {
        if ($by === 0) {
            return $finding;
        }

        $move = static fn (Location $l): Location => new Location($l->block + $by, $l->offset, $l->length);

        return new Finding(
            $finding->rule,
            $finding->severity,
            $finding->message,
            $finding->location === null ? null : $move($finding->location),
            $finding->excerpt,
            $finding->suggestion,
            array_map($move, $finding->spans),
        );
    }
}
