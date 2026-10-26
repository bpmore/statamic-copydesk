<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Scan;

use Bpmore\ReadabilityCore\Formula\Band;
use Bpmore\ReadabilityCore\Formula\Target;

/**
 * A site scanned: what the utility page shows. The questions are the
 * spec's: how the site's pages are spread across the bands, which pages
 * are hardest, and which acronyms and jargon come up most, so a
 * dictionary can be built from what authors actually write.
 */
final class ScanReport
{
    /**
     * @param  list<EntryResult>  $entries  the entries that were graded
     * @param  ?string  $refusal  the locale guard's words, when the site's language could not be checked
     * @param  list<string>  $failures  entries the check threw on, each "title: what went wrong"; one odd page must not stop a scan of a thousand
     */
    public function __construct(
        public readonly string $site,
        public readonly Target $target,
        public readonly array $entries,
        public readonly int $skipped,
        public readonly string $scannedAt,
        public readonly ?string $refusal = null,
        public readonly array $failures = [],
    ) {}

    public function isEmpty(): bool
    {
        return $this->entries === [];
    }

    /**
     * Entries per band, lowest band first, every band between the lowest
     * and highest present, so the spread reads as a shape and not a list.
     *
     * @return list<array{band: string, count: int, comparison: string}>
     */
    public function distribution(): array
    {
        if ($this->entries === []) {
            return [];
        }

        $counts = [];

        foreach ($this->entries as $entry) {
            $counts[$entry->band['low']] = ($counts[$entry->band['low']] ?? 0) + 1;
        }

        $rows = [];

        for ($low = min(array_keys($counts)); $low <= max(array_keys($counts)); $low++) {
            $band = Band::of((float) $low);
            $rows[] = ['band' => $band->label(), 'count' => $counts[$low] ?? 0, 'comparison' => $this->target->compare($band)->value];
        }

        return $rows;
    }

    /**
     * @return array{on_target: int, above: int, below: int}
     */
    public function againstTarget(): array
    {
        $tally = ['on_target' => 0, 'above' => 0, 'below' => 0];

        foreach ($this->entries as $entry) {
            $tally[$entry->comparison] = ($tally[$entry->comparison] ?? 0) + 1;
        }

        return $tally;
    }

    /**
     * The hardest pages first.
     *
     * @return list<EntryResult>
     */
    public function worst(int $limit = 10): array
    {
        $entries = $this->entries;
        usort($entries, static fn (EntryResult $a, EntryResult $b): int => [$b->consensus, $a->title] <=> [$a->consensus, $b->title]);

        return array_slice($entries, 0, $limit);
    }

    /**
     * Per collection: how many pages, the band their median page reads
     * at, and how many read above the target.
     *
     * @return list<array{collection: string, title: string, entries: int, median: string, above: int}>
     */
    public function byCollection(): array
    {
        $groups = [];

        foreach ($this->entries as $entry) {
            $groups[$entry->collection]['title'] = $entry->collectionTitle;
            $groups[$entry->collection]['grades'][] = $entry->consensus;
            $groups[$entry->collection]['above'] = ($groups[$entry->collection]['above'] ?? 0) + ($entry->comparison === 'above' ? 1 : 0);
        }

        ksort($groups);
        $rows = [];

        foreach ($groups as $handle => $group) {
            sort($group['grades']);
            $n = count($group['grades']);
            $median = $n % 2 === 1 ? $group['grades'][intdiv($n, 2)] : ($group['grades'][$n / 2 - 1] + $group['grades'][$n / 2]) / 2;

            $rows[] = ['collection' => $handle, 'title' => $group['title'], 'entries' => $n, 'median' => Band::of($median)->label(), 'above' => $group['above']];
        }

        return $rows;
    }

    /**
     * Unexpanded acronyms across the site, most frequent first: the list a
     * dictionary gets built from.
     *
     * @return list<array{term: string, count: int, pages: int}>
     */
    public function acronyms(): array
    {
        return $this->frequency(static fn (EntryResult $e): array => $e->acronyms);
    }

    /**
     * @return list<array{term: string, count: int, pages: int}>
     */
    public function jargon(): array
    {
        return $this->frequency(static fn (EntryResult $e): array => $e->jargon);
    }

    /**
     * @param  callable(EntryResult): list<string>  $terms
     * @return list<array{term: string, count: int, pages: int}>
     */
    private function frequency(callable $terms): array
    {
        $rows = [];

        foreach ($this->entries as $entry) {
            foreach (array_count_values(array_map(mb_strtolower(...), $terms($entry))) as $term => $count) {
                $rows[$term] ??= ['term' => (string) $term, 'count' => 0, 'pages' => 0];
                $rows[$term]['count'] += $count;
                $rows[$term]['pages']++;
            }
        }

        usort($rows, static fn (array $a, array $b): int => [$b['count'], $a['term']] <=> [$a['count'], $b['term']]);

        return array_values($rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'site' => $this->site,
            'target' => ['grade' => (int) round(($this->target->band->low + $this->target->band->high) / 2), 'tolerance' => (int) (($this->target->band->high - $this->target->band->low) / 2)],
            'entries' => array_map(static fn (EntryResult $e): array => $e->toArray(), $this->entries),
            'skipped' => $this->skipped,
            'scannedAt' => $this->scannedAt,
            'refusal' => $this->refusal,
            'failures' => $this->failures,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['site'],
            Target::grade($data['target']['grade'], $data['target']['tolerance']),
            array_map(static fn (array $e): EntryResult => EntryResult::fromArray($e), $data['entries']),
            $data['skipped'],
            $data['scannedAt'],
            $data['refusal'],
            $data['failures'] ?? [],
        );
    }
}
