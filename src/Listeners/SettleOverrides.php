<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Listeners;

use Bpmore\Copydesk\Gate\PublishGate;
use Statamic\Events\EntrySaved;

/**
 * Once a publish someone let through has saved, its pass is used up and
 * its reason written down. Not before: another gate (A11y Gate) can still
 * refuse the save after Copydesk let it through, and then the pass waits
 * for the next try.
 */
final class SettleOverrides
{
    public function __construct(private readonly PublishGate $gate) {}

    public function handle(EntrySaved $event): void
    {
        $this->gate->settle($event->entry);
    }
}
