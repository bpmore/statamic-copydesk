<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Gate;

use Bpmore\Copydesk\Analysis\Analyzer;
use Bpmore\Copydesk\Analysis\Report;
use Bpmore\ReadabilityCore\Rule\Finding;
use Bpmore\ReadabilityCore\Rule\Jargon;
use Statamic\Contracts\Entries\Entry;
use Throwable;

/**
 * Whether a save may go through.
 *
 * Only a published entry in a gated collection is inspected: a draft is
 * an author's own business, and stopping one would stop work in progress.
 * `Publishable::publish()` sets `published(true)` and then saves, so a
 * publish is a save and arrives here; so does re-saving a page that is
 * already live, which is right, because it would otherwise stay live and
 * broken.
 *
 * What blocks is a finding whose rule the site named in `block_on`, and
 * nothing else. The grade is never consulted. For `jargon`, only a banned
 * term blocks, one the dictionary gives nothing to say instead of: the
 * spec calls the blockable thing "banned phrases", and a term with a
 * suggested replacement is a warning to fix, not a reason to stop a
 * publish.
 */
final class PublishGate
{
    public function __construct(
        private readonly Analyzer $analyzer,
        private readonly GateSettings $settings,
    ) {}

    public function inspect(Entry $entry): GateResult
    {
        if (! $entry->published()) {
            return GateResult::notApplicable('the entry is a draft');
        }

        $collection = $entry->collection()?->handle();

        if ($collection === null || ! $this->settings->gates((string) $collection)) {
            return GateResult::notApplicable("the collection [{$collection}] is not gated");
        }

        try {
            $report = $this->analyzer->analyze($entry->blueprint(), $entry->data()->all(), (string) $entry->site()->locale(), (string) $entry->site()->handle());
        } catch (Throwable $e) {
            return GateResult::couldNotCheck($e->getMessage());
        }

        if (! $report->locale->allowed) {
            return GateResult::notApplicable((string) $report->locale->message());
        }

        return GateResult::checked($report, $this->blocking($report));
    }

    /**
     * @return list<Finding>
     */
    private function blocking(Report $report): array
    {
        return array_values(array_filter(
            $report->findings->all(),
            fn (Finding $finding): bool => $this->settings->blocksOn($finding->rule)
                && ($finding->rule !== Jargon::HANDLE || $finding->suggestion === null),
        ));
    }
}
