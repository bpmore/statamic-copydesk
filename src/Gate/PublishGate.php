<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Gate;

use Bpmore\Copydesk\Analysis\Analyzer;
use Bpmore\Copydesk\Analysis\Report;
use Bpmore\Copydesk\Edition;
use Bpmore\ReadabilityCore\Rule\Finding;
use Bpmore\ReadabilityCore\Rule\Severity;
use Statamic\Contracts\Auth\User;
use Statamic\Contracts\Entries\Entry;
use Throwable;

/**
 * Whether a save may go through. Part of Copydesk Pro.
 *
 * Only a published entry in a gated collection is inspected: a draft is
 * an author's own business, and stopping one would stop work in progress.
 * `Publishable::publish()` sets `published(true)` and then saves, so a
 * publish is a save and arrives here; so does re-saving a page that is
 * already live, which is right, because it would otherwise stay live and
 * broken.
 *
 * What blocks is a finding whose severity is `error`, and nothing else.
 * The grade is never consulted. Which rules are errors is the site's to
 * say: in its packs, and for the readability rules in `rules`.
 *
 * Someone with `override copydesk gate` who asked for a pass, with a
 * reason, publishes anyway. The pass is only looked at here; it is used up,
 * and the reason written down, once the save has actually happened
 * (`settle()`, on EntrySaved), so a publish another gate refuses does not
 * spend it.
 */
final class PublishGate
{
    public const OVERRIDE = 'override copydesk gate';

    /** @var array<int, array{reason: string, user: User, result: GateResult}> overrides waiting for their save */
    private array $pending = [];

    public function __construct(
        private readonly Analyzer $analyzer,
        private readonly GateSettings $settings,
        private readonly OverridePasses $passes,
        private readonly OverrideLog $log,
    ) {}

    public function inspect(Entry $entry, ?User $user = null): GateResult
    {
        if (! Edition::isPro()) {
            return GateResult::notApplicable('the publish gate is part of Copydesk Pro');
        }

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

        $result = GateResult::checked($report, $this->blocking($report));

        if ($result->shouldRefuse() && $user !== null && $user->can(self::OVERRIDE)) {
            $reason = $this->passes->peek((string) $user->id(), $entry);

            if ($reason !== null) {
                $this->pending[spl_object_id($entry)] = ['reason' => $reason, 'user' => $user, 'result' => $result];

                return $result->overriddenBecause($reason);
            }
        }

        return $result;
    }

    /**
     * After the save: an override that let it through is used up and
     * written down.
     */
    public function settle(Entry $entry): void
    {
        $pending = $this->pending[spl_object_id($entry)] ?? null;
        unset($this->pending[spl_object_id($entry)]);

        if ($pending === null) {
            return;
        }

        $this->passes->take((string) $pending['user']->id(), $entry);
        $this->log->record([
            'entry' => (string) $entry->id(),
            'title' => (string) ($entry->get('title') ?? $entry->slug()),
            'collection' => (string) $entry->collection()?->handle(),
            'site' => (string) $entry->site()->handle(),
            'user' => (string) $pending['user']->id(),
            'user_name' => (string) ($pending['user']->name() ?: $pending['user']->email()),
            'reason' => $pending['reason'],
            'rules' => array_values(array_unique(array_map(static fn (Finding $f): string => $f->rule, $pending['result']->blocking))),
        ]);
    }

    /**
     * @return list<Finding>
     */
    private function blocking(Report $report): array
    {
        return array_values($report->findings->ofSeverity(Severity::Error)->all());
    }
}
