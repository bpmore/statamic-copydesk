<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Gate;

use Bpmore\A11yGate\Contracts\ContributedResult;
use Bpmore\A11yGate\Contracts\GateContributor;
use Psr\Log\LoggerInterface;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\User;

/**
 * Copydesk's gate, asked by A11y Gate during the same publish.
 *
 * Only ever loaded when A11y Gate's contract exists: the service provider
 * tags it by name behind that check. Copydesk's rules hold as they do on
 * its own: drafts never stop, a grade never stops, a check that breaks lets
 * the save through and says so in the log, and a pass from "Publish anyway"
 * lets this publish through.
 */
final class CopydeskGateContributor implements GateContributor
{
    public function __construct(
        private readonly PublishGate $gate,
        private readonly LoggerInterface $log,
    ) {}

    public function key(): string
    {
        return 'copydesk';
    }

    public function label(): string
    {
        return 'Copydesk';
    }

    public function inspect(Entry $entry): ContributedResult
    {
        $result = $this->gate->inspect($entry, User::current());

        if ($result->couldNotBeChecked()) {
            $this->log->error(
                'Copydesk could not check this entry before it was saved, so it was saved unchecked: '.$result->reason,
                ['entry' => $entry->id()],
            );

            return ContributedResult::passes();
        }

        return $result->shouldRefuse() ? ContributedResult::blocks($result->lines()) : ContributedResult::passes();
    }
}
