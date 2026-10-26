<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Analysis;

use Bpmore\Copydesk\Dictionaries;
use Bpmore\Copydesk\Settings;
use Bpmore\ReadabilityCore\Exclusion\ProperNames;
use Bpmore\ReadabilityCore\Formula\Grades;
use Bpmore\ReadabilityCore\Formula\Sections;
use Bpmore\ReadabilityCore\Formula\Tally;
use Bpmore\ReadabilityCore\Text\Document;
use Bpmore\ReadabilityCore\Text\EnglishSegmenter;
use Bpmore\ReadabilityCore\Text\EnglishSyllableCounter;
use Statamic\Fields\Blueprint;

/**
 * The whole check, for one form's values: the locale guard, the fields
 * the site said are prose, the exclusions, the grade against the target,
 * the sections, the rules.
 *
 * Each field is excluded on its own before the fields are joined, so the
 * block that a finding names can be traced back to its field; an
 * exclusion applied to the joined document would drop blocks and shift
 * every index after them.
 */
class Analyzer
{
    public function __construct(
        private readonly Settings $settings,
        private readonly Dictionaries $dictionaries,
    ) {}

    /**
     * @param  array<string, mixed>  $values  the form's values, processed through the blueprint
     * @param  ?string  $site  the site the entry is for, whose dictionary the jargon rule uses; the starter alone without one
     */
    public function analyze(Blueprint $blueprint, array $values, string $locale, ?string $site = null): Report
    {
        $target = $this->settings->target();
        $decision = $this->settings->localeGuard()->check($locale);

        if (! $decision->allowed) {
            return Report::refused($decision, $target);
        }

        $pipeline = $this->settings->exclusions();
        $structural = $pipeline->without(ProperNames::class);
        $grading = $pipeline->only(ProperNames::class);

        $blocks = [];
        $blockFields = [];
        $read = [];
        $notices = [];

        foreach ($this->settings->fieldsFor((string) $blueprint->handle()) as $handle) {
            $field = $blueprint->field($handle);

            if ($field === null || ! FieldText::isProse($field)) {
                continue;
            }

            $document = FieldText::extract($field, $values[$handle] ?? null, $this->settings->ignoredClasses());

            if ($document === null) {
                continue;
            }

            $read[] = $handle;

            foreach ($structural->apply($document) as $block) {
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

        return new Report(
            $decision,
            $target,
            $document,
            $read,
            $blockFields,
            $counts,
            Grades::of($counts),
            Sections::of($document, $tally, $grading),
            $this->settings->rules($site === null ? null : $this->dictionaries->forSite($site))->check($document),
            $notices,
        );
    }
}
