<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Commands;

use Bpmore\Copydesk\Scan\EntryResult;
use Bpmore\Copydesk\Scan\ScanReport;
use Bpmore\Copydesk\Scan\SiteScan;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;

/**
 * Grade every published page, from a terminal.
 *
 * A command as well as a screen, because the person who wants the whole
 * site graded at once is a developer, and a build or a release can be
 * stopped by an exit code where a screen cannot stop anything. Run
 * nightly, it also keeps the dashboard widget's trend and the Site
 * Weather band current: a full scan of a site is recorded the same way
 * the page under Tools records one. A scan narrowed to a collection is
 * not, because a sample in the trend would say the site changed when the
 * sample did.
 */
class CheckCommand extends Command
{
    use RunsInPlease;

    protected $signature = 'copydesk:scan
        {--site=* : Only these sites. Default is all of them.}
        {--collection=* : Only these collections. Not recorded in the trend.}
        {--export= : Write a CSV: "csv" for one under storage, or a path.}
        {--fail-above= : Exit 1 when more pages than this read above the target: a count ("10") or a share ("10%"). Default from config.}
        {--fail-on= : Exit 1 when any published page has a finding at or above this severity: error, warning or tip.}
        {--fail-on-errors : The same as --fail-on=error.}';

    protected $description = 'Grade every published page against the target reading level, with a CSV and an exit code for CI.';

    /** Each severity and the ones at or above it. */
    private const AT_OR_ABOVE = [
        'error' => ['error'],
        'warning' => ['error', 'warning'],
        'tip' => ['error', 'warning', 'tip'],
    ];

    public function handle(SiteScan $scan): int
    {
        $failOn = $this->failOn();

        if ($failOn === false) {
            $this->components->error('--fail-on takes error, warning or tip.');

            return 2;
        }

        $sites = $this->option('site') ?: Site::all()->map(fn ($s) => $s->handle())->values()->all();
        $collections = $this->option('collection');

        foreach ($sites as $handle) {
            if (Site::get($handle) === null) {
                $this->components->error("There is no site called [{$handle}].");

                return 2;
            }
        }

        foreach ($collections as $handle) {
            if (Collection::findByHandle($handle) === null) {
                $this->components->error("There is no collection called [{$handle}].");

                return 2;
            }
        }

        $reports = [];

        foreach ($sites as $handle) {
            $reports[$handle] = $collections === []
                ? $scan->run($handle)
                : $this->merged(array_map(fn (string $c) => $scan->run($handle, $c), $collections));

            $this->write($reports[$handle]);
        }

        if (($path = $this->exportPath()) !== null) {
            $this->export($reports, $path);
            $this->components->info("Written to {$path}");
        }

        return $this->verdict($reports, $failOn);
    }

    /**
     * The severity to fail on: null for none, false for one that does not
     * exist.
     */
    private function failOn(): string|false|null
    {
        $option = strtolower(trim((string) $this->option('fail-on')));

        if ($option === '' && $this->option('fail-on-errors')) {
            return 'error';
        }

        if ($option === '') {
            return null;
        }

        return array_key_exists($option, self::AT_OR_ABOVE) ? $option : false;
    }

    private function write(ScanReport $report): void
    {
        $site = Site::get($report->site);
        $this->components->twoColumnDetail("<options=bold>{$site->name()}</> ({$report->site})", '');

        if ($report->refusal !== null) {
            $this->warn("  {$report->refusal}");

            return;
        }

        if ($report->isEmpty()) {
            $this->line('  '.($report->skipped === 0 ? 'No published pages.' : "{$report->skipped} published page(s), none with text in a field this addon reads."));

            return;
        }

        $this->table(
            ['Page', 'Collection', 'Reads at', 'Target', 'Errors', 'Warnings', 'Tips'],
            array_map(fn (EntryResult $e): array => [
                mb_strimwidth($e->title, 0, 48, '…'),
                $e->collectionTitle,
                $e->band['label'],
                ['on_target' => 'on target', 'above' => 'ABOVE', 'below' => 'below'][$e->comparison] ?? '',
                $e->errors,
                $e->warnings,
                $e->tips,
            ], $report->worst(PHP_INT_MAX)),
        );

        foreach ($report->failures as $failure) {
            $this->warn("  Could not grade {$failure}");
        }

        $against = $report->againstTarget();
        $this->line(sprintf(
            '  %d graded against %s: %d above, %d on target, %d below%s.',
            count($report->entries),
            $report->target->band->label(),
            $against['above'],
            $against['on_target'],
            $against['below'],
            $report->skipped > 0 ? "; {$report->skipped} with nothing to grade" : '',
        ));
        $this->line('');
    }

    /**
     * @param  array<string, ScanReport>  $reports
     */
    private function verdict(array $reports, ?string $failOn): int
    {
        $graded = 0;
        $above = 0;
        $found = ['error' => 0, 'warning' => 0, 'tip' => 0];

        foreach ($reports as $report) {
            $graded += count($report->entries);
            $above += $report->againstTarget()['above'];

            // Every published page with something to say, short ones too.
            foreach ($report->pages() as $page) {
                $found['error'] += $page->errors;
                $found['warning'] += $page->warnings;
                $found['tip'] += $page->tips;
            }
        }

        $limit = (string) ($this->option('fail-above') ?? config('copydesk.check.fail_above') ?? '');

        if ($limit !== '' && $graded > 0) {
            $allowed = str_ends_with($limit, '%')
                ? (float) rtrim($limit, '%') / 100 * $graded
                : (float) $limit;

            if ($above > $allowed) {
                $this->components->error(sprintf('%d of %d pages read above the target; the limit is %s.', $above, $graded, $limit));

                return 1;
            }
        }

        if ($failOn !== null) {
            $count = array_sum(array_map(fn (string $severity): int => $found[$severity], self::AT_OR_ABOVE[$failOn]));

            if ($count > 0) {
                $this->components->error(sprintf('%d finding(s) at or above %s on published pages.', $count, $failOn));

                return 1;
            }
        }

        $this->components->info(sprintf('%d page(s) graded, %d above the target.', $graded, $above));

        return 0;
    }

    private function exportPath(): ?string
    {
        $export = (string) $this->option('export');

        return match (true) {
            $export === '' => null,
            strtolower($export) === 'csv' => storage_path('statamic/copydesk/copydesk-scan-'.now()->format('Y-m-d-His').'.csv'),
            default => $export,
        };
    }

    /**
     * @param  array<string, ScanReport>  $reports
     */
    private function export(array $reports, string $path): void
    {
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $out = fopen($path, 'w');
        fputcsv($out, ['site', 'collection', 'id', 'title', 'edit_url', 'band', 'band_low', 'band_high', 'consensus', 'comparison', 'errors', 'warnings', 'tips', 'acronyms', 'jargon']);

        foreach ($reports as $report) {
            foreach ([...$report->worst(PHP_INT_MAX), ...$report->ungraded] as $e) {
                fputcsv($out, [
                    $report->site, $e->collection, $e->id, $e->title, $e->editUrl,
                    $e->band['label'] ?? '', $e->band['low'] ?? '', $e->band['high'] ?? '', $e->consensus ?? '', $e->comparison ?? '',
                    $e->errors, $e->warnings, $e->tips,
                    implode('|', array_unique($e->acronyms)), implode('|', array_unique($e->jargon)),
                ]);
            }
        }

        fclose($out);
    }

    /**
     * Several collections' scans of one site, as one report. The target and
     * the site are the same in each; the refusal, if any, too.
     *
     * @param  list<ScanReport>  $reports
     */
    private function merged(array $reports): ScanReport
    {
        $first = $reports[0];

        return new ScanReport(
            $first->site,
            $first->target,
            array_merge(...array_map(fn (ScanReport $r): array => $r->entries, $reports)),
            array_sum(array_map(fn (ScanReport $r): int => $r->skipped, $reports)),
            $first->scannedAt,
            $first->refusal,
            array_merge(...array_map(fn (ScanReport $r): array => $r->failures, $reports)),
            array_merge(...array_map(fn (ScanReport $r): array => $r->ungraded, $reports)),
        );
    }
}
