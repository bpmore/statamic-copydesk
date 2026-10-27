<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Scan;

use Bpmore\Copydesk\Analysis\Analyzer;
use Bpmore\Copydesk\Analysis\HouseRules;
use Bpmore\Copydesk\Settings;
use Bpmore\ReadabilityCore\Rule\Finding;
use Bpmore\ReadabilityCore\Rule\Severity;
use Bpmore\ReadabilityCore\Rule\UnexpandedAcronym;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Entry as Entries;
use Statamic\Facades\Site;
use Throwable;

/**
 * Every published page of a site, graded. What the utility page and the
 * dashboard widget read.
 *
 * Cached for an hour per site, with a way to scan again. A health system
 * has thousands of pages, and the place to pay for grading them all is
 * once, on request, not on every load of a page under Tools; and not in
 * the web request either: the page under Tools starts a scan as queued
 * jobs of fifty pages and shows how far it has got. The command scans in
 * one go, where waiting is fine.
 */
final class SiteScan
{
    public const TTL = 3600;

    /** Pages per background job. */
    public const CHUNK = 50;

    public function __construct(
        private readonly Analyzer $analyzer,
        private readonly Settings $settings,
        private readonly Cache $cache,
        private readonly ScanHistory $history,
    ) {}

    /**
     * The scan, from the cache when there is one; null while one is
     * running, and a new one is started when there is neither.
     */
    public function cached(string $site): ?ScanReport
    {
        $data = $this->cache->get(self::key($site));

        if (is_array($data)) {
            return ScanReport::fromArray($data);
        }

        if ($this->progress($site) === null) {
            $this->start($site);
        }

        // With the sync queue, the scan has already finished.
        $data = $this->cache->get(self::key($site));

        return is_array($data) ? ScanReport::fromArray($data) : null;
    }

    public function forget(string $site): void
    {
        $this->cache->forget(self::key($site));
        $this->cache->forget(self::key($site).'.run');
    }

    /**
     * Scan the site in the background: its published pages in jobs of
     * fifty, on the site's queue. The utility page shows the progress and
     * the report once the last job is in.
     */
    public function start(string $site): void
    {
        $ids = Entries::query()->where('site', $site)->where('published', true)->get()
            ->map(static fn (Entry $entry): string => (string) $entry->id())->values()->all();
        $run = (string) Str::uuid();
        $chunks = array_chunk($ids, self::CHUNK);

        $this->cache->put(self::key($site).'.run', ['id' => $run, 'total' => count($ids), 'chunks' => count($chunks)], self::TTL);

        if ($chunks === []) {
            $this->finish($site, $run, 0);

            return;
        }

        foreach ($chunks as $index => $chunk) {
            ScanChunk::dispatch($site, $run, $index, $chunk);
        }
    }

    /**
     * How far a background scan has got: pages done of the total, or null
     * when none is running.
     *
     * @return array{done: int, total: int}|null
     */
    public function progress(string $site): ?array
    {
        $state = $this->cache->get(self::key($site).'.run');

        if (! is_array($state)) {
            return null;
        }

        return ['done' => (int) $this->cache->get(self::key($site).'.'.$state['id'].'.done', 0), 'total' => (int) $state['total']];
    }

    /**
     * One job's fifty pages. The last job in puts the report together.
     *
     * @param  list<string>  $ids
     */
    public function chunk(string $site, string $run, int $index, array $ids): void
    {
        $state = $this->cache->get(self::key($site).'.run');

        // A scan that was started again, or forgotten, since this was queued.
        if (! is_array($state) || $state['id'] !== $run) {
            return;
        }

        $entries = array_values(array_filter(array_map(static fn (string $id): ?Entry => Entries::find($id), $ids)));
        $part = $this->scanAll($entries, (string) Site::get($site)?->locale(), $site);

        $this->cache->put(self::key($site).'.'.$run.'.'.$index, [
            'entries' => array_map(static fn (EntryResult $e): array => $e->toArray(), $part['entries']),
            'ungraded' => array_map(static fn (EntryResult $e): array => $e->toArray(), $part['ungraded']),
            'skipped' => $part['skipped'],
            'failures' => $part['failures'],
        ], self::TTL);
        $this->cache->increment(self::key($site).'.'.$run.'.done', count($ids));

        $this->finish($site, $run, (int) $state['chunks']);
    }

    /**
     * Every published page of a site, scanned here and now: for the
     * command line, where waiting is fine.
     *
     * @param  ?string  $collection  only this collection; a partial scan is not recorded, because a trend with a sample in it would say the site changed when the sample did
     */
    public function run(string $site, ?string $collection = null): ScanReport
    {
        $target = $this->settings->target();
        $locale = (string) Site::get($site)?->locale();
        $decision = $this->settings->localeGuard()->check($locale);

        // Not recorded: a site that cannot be checked has no result, and a
        // trend with a zero in it would say the site got easier.
        if (! $decision->allowed) {
            return new ScanReport($site, $target, [], 0, now()->toIso8601String(), $decision->message());
        }

        $query = Entries::query()->where('site', $site)->where('published', true);

        if ($collection !== null) {
            $query->where('collection', $collection);
        }

        $part = $this->scanAll($query->get()->all(), $locale, $site);
        $report = new ScanReport($site, $target, $part['entries'], $part['skipped'], now()->toIso8601String(), null, $part['failures'], $part['ungraded']);

        if ($collection === null) {
            $this->history->record($report);
        }

        return $report;
    }

    /**
     * Once every job of a run is in: one report, cached and recorded, once.
     */
    private function finish(string $site, string $run, int $chunks): void
    {
        $parts = [];

        for ($i = 0; $i < $chunks; $i++) {
            $part = $this->cache->get(self::key($site).'.'.$run.'.'.$i);

            if (! is_array($part)) {
                return;
            }

            $parts[] = $part;
        }

        // Two jobs can both see the last part arrive; only one writes.
        if (! $this->cache->add(self::key($site).'.'.$run.'.finished', true, self::TTL)) {
            return;
        }

        $target = $this->settings->target();
        $decision = $this->settings->localeGuard()->check((string) Site::get($site)?->locale());

        $report = $decision->allowed
            ? new ScanReport(
                $site,
                $target,
                array_map(EntryResult::fromArray(...), array_merge(...array_column($parts, 'entries') ?: [[]])),
                array_sum(array_column($parts, 'skipped')),
                now()->toIso8601String(),
                null,
                array_merge(...array_column($parts, 'failures') ?: [[]]),
                array_map(EntryResult::fromArray(...), array_merge(...array_column($parts, 'ungraded') ?: [[]])),
            )
            : new ScanReport($site, $target, [], 0, now()->toIso8601String(), $decision->message());

        $this->cache->put(self::key($site), $report->toArray(), self::TTL);

        if ($decision->allowed) {
            $this->history->record($report);
        }

        for ($i = 0; $i < $chunks; $i++) {
            $this->cache->forget(self::key($site).'.'.$run.'.'.$i);
        }

        $this->cache->forget(self::key($site).'.'.$run.'.done');
        $this->cache->forget(self::key($site).'.run');
    }

    /**
     * @param  iterable<Entry>  $entries
     * @return array{entries: list<EntryResult>, ungraded: list<EntryResult>, skipped: int, failures: list<string>}
     */
    private function scanAll(iterable $entries, string $locale, string $site): array
    {
        $out = ['entries' => [], 'ungraded' => [], 'skipped' => 0, 'failures' => []];

        if (! $this->settings->localeGuard()->check($locale)->allowed) {
            return $out;
        }

        foreach ($entries as $entry) {
            // One page with data the check cannot read must not stop the
            // other nine hundred. It is named, with what went wrong.
            try {
                $result = $this->scan($entry, $locale, $site);
            } catch (Throwable $e) {
                $out['failures'][] = ((string) ($entry->get('title') ?? $entry->slug())).': '.$e->getMessage();

                continue;
            }

            match (true) {
                $result === null => $out['skipped']++,
                $result->isGraded() => $out['entries'][] = $result,
                default => $out['ungraded'][] = $result,
            };
        }

        return $out;
    }

    /**
     * One page: graded, or, too short to grade, still reported when it
     * has issues. Null when there is nothing to say about it.
     */
    private function scan(Entry $entry, string $locale, string $site): ?EntryResult
    {
        $report = $this->analyzer->analyze($entry->blueprint(), $entry->data()->all(), $locale, $site);
        $band = $report->band();
        $findings = $report->findings;

        if ($band === null && $findings->all() === []) {
            return null;
        }

        $rules = [];

        foreach ($findings->all() as $finding) {
            $rules[$finding->rule] ??= ['severity' => $finding->severity->value, 'count' => 0];
            $rules[$finding->rule]['count']++;
        }

        return new EntryResult(
            id: (string) $entry->id(),
            title: (string) ($entry->get('title') ?? $entry->slug()),
            collection: (string) $entry->collection()->handle(),
            collectionTitle: (string) $entry->collection()->title(),
            editUrl: (string) $entry->editUrl(),
            band: $band === null ? null : ['low' => $band->low, 'high' => $band->high, 'label' => $band->label()],
            consensus: $band === null ? null : round((float) $report->grades?->consensus(), 1),
            comparison: $report->comparison()?->value,
            errors: count($findings->ofSeverity(Severity::Error)),
            warnings: count($findings->ofSeverity(Severity::Warning)),
            tips: count($findings->ofSeverity(Severity::Tip)),
            acronyms: array_map(static fn (Finding $f): string => rtrim($f->excerpt, "'s"), $findings->ofRule(UnexpandedAcronym::HANDLE)->all()),
            jargon: array_map(static fn (Finding $f): string => $f->excerpt, $findings->ofRule(HouseRules::SITE_WORDS, 'plain-words')->all()),
            rules: $rules,
            ignored: $report->ignored,
        );
    }

    private static function key(string $site): string
    {
        return 'copydesk.scan.'.$site;
    }
}
