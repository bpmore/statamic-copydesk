<?php

declare(strict_types=1);

namespace Bpmore\Plain\Tags;

use Bpmore\Plain\Analysis\Analyzer;
use Bpmore\Plain\Settings;
use Illuminate\Support\Facades\Cache;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Facades\Entry;
use Statamic\Tags\Tags;

/**
 * The reading level on the page itself, for the sites that want their
 * readers to see it: some health systems do.
 *
 *     {{ plain:score }}                       Grade 6–7
 *
 *     {{ plain:score }}
 *         {{ if graded }}
 *             Reading level: {{ band }}{{ if !on_target }} (we aim for {{ target }}){{ /if }}
 *         {{ /if }}
 *     {{ /plain:score }}
 *
 * Alone, the tag prints the band and nothing else: a page that cannot be
 * graded, because the site is not in English or there is nothing to read,
 * prints nothing at all, since a visitor is not the person to tell why.
 * As a pair, it hands the site the parts and says whether it graded.
 *
 * The current page, or `entry="<id>"` for another. Cached for a day per
 * entry and its last change, because a template renders on every visit
 * and a grade does not change until the page does.
 */
class Plain extends Tags
{
    protected static $handle = 'plain';

    public function score(): string|array
    {
        $entry = $this->entry();
        $values = $entry === null ? self::ungraded('There is no entry to grade.') : $this->graded($entry);

        return $this->isPair ? $values : (string) $values['band'];
    }

    /**
     * @return array<string, mixed>
     */
    private function graded(EntryContract $entry): array
    {
        // The site's language is in the key: a site that changes it must not
        // keep grades from when it was English.
        $key = 'statamic-plain.score.'.$entry->id().'.'.($entry->lastModified()?->getTimestamp() ?? 0).'.'.$entry->site()->locale();

        return Cache::remember($key, 86400, function () use ($entry): array {
            $settings = app(Settings::class);

            if (! $settings->enabled()) {
                return self::ungraded('Readability checking is turned off.');
            }

            $report = app(Analyzer::class)->analyze(
                $entry->blueprint(),
                $entry->data()->all(),
                (string) $entry->site()->locale(),
                (string) $entry->site()->handle(),
            );

            if (! $report->locale->allowed) {
                return self::ungraded((string) $report->locale->message());
            }

            $band = $report->band();

            if ($band === null) {
                return self::ungraded('Nothing on this page could be graded.');
            }

            $target = $report->target->band;
            $comparison = $report->comparison();

            return [
                'graded' => true,
                'band' => $band->label(),
                'low' => $band->low,
                'high' => $band->high,
                'target' => $target->label(),
                'target_low' => $target->low,
                'target_high' => $target->high,
                'comparison' => $comparison?->value,
                'on_target' => $comparison?->value === 'on_target',
                'above_target' => $comparison?->value === 'above',
                'words' => $report->counts->words,
                'sentences' => $report->counts->sentences,
                'reason' => null,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    private static function ungraded(string $reason): array
    {
        return [
            'graded' => false,
            'band' => '',
            'low' => null,
            'high' => null,
            'target' => '',
            'target_low' => null,
            'target_high' => null,
            'comparison' => null,
            'on_target' => false,
            'above_target' => false,
            'words' => 0,
            'sentences' => 0,
            'reason' => $reason,
        ];
    }

    private function entry(): ?EntryContract
    {
        $id = (string) ($this->params->get('entry') ?: $this->context->get('id'));

        if ($id === '') {
            return null;
        }

        $entry = Entry::find($id);

        return $entry instanceof EntryContract ? $entry : null;
    }
}
