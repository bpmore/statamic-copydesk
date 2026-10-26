<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Scan;

use Illuminate\Filesystem\Filesystem;

/**
 * What every scan of a site came to, kept: the stored results the widget
 * draws a trend from, and the only thing anything outside this addon
 * reads. One small file per site under the app's storage, the last
 * ninety scans.
 */
final class ScanHistory
{
    public const KEEP = 90;

    public function __construct(private readonly Filesystem $files, private readonly string $directory) {}

    public static function inStorage(Filesystem $files): self
    {
        return new self($files, storage_path('statamic/copydesk/scans'));
    }

    public function record(ScanReport $report): Snapshot
    {
        $snapshot = Snapshot::of($report);
        $all = [...array_map(static fn (Snapshot $s): array => $s->toArray(), $this->all($report->site)), $snapshot->toArray()];

        $this->files->ensureDirectoryExists($this->directory);
        $this->files->put($this->path($report->site), json_encode(array_slice($all, -self::KEEP), JSON_PRETTY_PRINT).PHP_EOL);

        return $snapshot;
    }

    /**
     * Every snapshot of a site, oldest first.
     *
     * @return list<Snapshot>
     */
    public function all(string $site): array
    {
        $path = $this->path($site);

        if (! $this->files->exists($path)) {
            return [];
        }

        $data = json_decode((string) $this->files->get($path), true);

        return is_array($data) ? array_values(array_map(static fn (array $s): Snapshot => Snapshot::fromArray($s), array_filter($data, is_array(...)))) : [];
    }

    public function latest(string $site): ?Snapshot
    {
        $all = $this->all($site);

        return $all === [] ? null : $all[count($all) - 1];
    }

    /** The scan before the latest, for a trend. */
    public function previous(string $site): ?Snapshot
    {
        $all = $this->all($site);

        return count($all) < 2 ? null : $all[count($all) - 2];
    }

    public function forget(string $site): void
    {
        $this->files->delete($this->path($site));
    }

    private function path(string $site): string
    {
        return $this->directory.'/'.preg_replace('/[^\w.-]/', '_', $site).'.json';
    }
}
