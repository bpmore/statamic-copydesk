<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Scan;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Fifty pages of a background site scan. On a site with no queue worker
 * (the sync queue), the jobs run as they are dispatched, and the scan is
 * done by the time the page under Tools has started it.
 */
final class ScanChunk implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  list<string>  $ids
     */
    public function __construct(
        public readonly string $site,
        public readonly string $run,
        public readonly int $index,
        public readonly array $ids,
    ) {}

    public function handle(SiteScan $scan): void
    {
        $scan->chunk($this->site, $this->run, $this->index, $this->ids);
    }
}
