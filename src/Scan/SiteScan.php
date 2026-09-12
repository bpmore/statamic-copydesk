<?php

declare(strict_types=1);

namespace Bpmore\Plain\Scan;

use Bpmore\Plain\Analysis\Analyzer;
use Bpmore\Plain\Settings;
use Bpmore\ReadabilityCore\Rule\Finding;
use Bpmore\ReadabilityCore\Rule\Jargon;
use Bpmore\ReadabilityCore\Rule\Severity;
use Bpmore\ReadabilityCore\Rule\UnexpandedAcronym;
use Illuminate\Contracts\Cache\Repository as Cache;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Entry as Entries;
use Statamic\Facades\Site;

/**
 * Every published page of a site, graded. What the utility page and the
 * dashboard widget read.
 *
 * Cached for an hour per site, with a way to scan again. A health system
 * has thousands of pages, and the place to pay for grading them all is
 * once, on request, not on every load of a page under Tools.
 */
final class SiteScan
{
    public const TTL = 3600;

    public function __construct(
        private readonly Analyzer $analyzer,
        private readonly Settings $settings,
        private readonly Cache $cache,
        private readonly ScanHistory $history,
    ) {}

    /** The scan, from the cache when there is one. */
    public function cached(string $site): ScanReport
    {
        $data = $this->cache->get(self::key($site));

        if (is_array($data)) {
            return ScanReport::fromArray($data);
        }

        $report = $this->run($site);
        $this->cache->put(self::key($site), $report->toArray(), self::TTL);

        return $report;
    }

    public function forget(string $site): void
    {
        $this->cache->forget(self::key($site));
    }

    public function run(string $site): ScanReport
    {
        $target = $this->settings->target();
        $locale = (string) Site::get($site)?->locale();
        $decision = $this->settings->localeGuard()->check($locale);

        // Not recorded: a site that cannot be checked has no result, and a
        // trend with a zero in it would say the site got easier.
        if (! $decision->allowed) {
            return new ScanReport($site, $target, [], 0, now()->toIso8601String(), $decision->message());
        }

        $results = [];
        $skipped = 0;

        foreach (Entries::query()->where('site', $site)->where('published', true)->get() as $entry) {
            $result = $this->scan($entry, $locale, $site);

            if ($result === null) {
                $skipped++;
            } else {
                $results[] = $result;
            }
        }

        $report = new ScanReport($site, $target, $results, $skipped, now()->toIso8601String());
        $this->history->record($report);

        return $report;
    }

    private function scan(Entry $entry, string $locale, string $site): ?EntryResult
    {
        $report = $this->analyzer->analyze($entry->blueprint(), $entry->data()->all(), $locale, $site);
        $band = $report->band();

        if ($band === null) {
            return null;
        }

        $findings = $report->findings;

        return new EntryResult(
            id: (string) $entry->id(),
            title: (string) ($entry->get('title') ?? $entry->slug()),
            collection: (string) $entry->collection()->handle(),
            collectionTitle: (string) $entry->collection()->title(),
            editUrl: (string) $entry->editUrl(),
            band: ['low' => $band->low, 'high' => $band->high, 'label' => $band->label()],
            consensus: round((float) $report->grades?->consensus(), 1),
            comparison: $report->comparison()?->value,
            errors: count($findings->ofSeverity(Severity::Error)),
            warnings: count($findings->ofSeverity(Severity::Warning)),
            tips: count($findings->ofSeverity(Severity::Tip)),
            acronyms: array_map(static fn (Finding $f): string => rtrim($f->excerpt, "'s"), $findings->ofRule(UnexpandedAcronym::HANDLE)->all()),
            jargon: array_map(static fn (Finding $f): string => $f->excerpt, $findings->ofRule(Jargon::HANDLE)->all()),
        );
    }

    private static function key(string $site): string
    {
        return 'statamic-plain.scan.'.$site;
    }
}
