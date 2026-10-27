<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Gate;

use Illuminate\Contracts\Cache\Repository;
use Statamic\Contracts\Entries\Entry;

/**
 * "Publish anyway": a pass for one publish, by one person, of one entry.
 *
 * The person asks for it with a reason; the gate uses it up on their next
 * publish of that entry and lets it through. A pass and not a field on the
 * form, because a collection with revisions publishes through a request
 * that carries only a message (docs/a11y-gate-plan.md); a pass works for
 * both, and for scripts. It lapses after ten minutes, so a reason given
 * for one problem is never spent on a later one.
 *
 * A page nobody has saved has no id to ask for, so its pass is for the
 * person's next new page in that collection.
 */
final class OverridePasses
{
    public const MINUTES = 10;

    public function __construct(private readonly Repository $cache) {}

    public function grant(string $user, ?string $entry, string $collection, string $reason): void
    {
        $this->cache->put($this->key($user, $entry, $collection), $reason, now()->addMinutes(self::MINUTES));
    }

    /** The reason, if there is a pass, which is left where it is. */
    public function peek(string $user, Entry $entry): ?string
    {
        foreach ($this->keys($user, $entry) as $key) {
            $reason = $this->cache->get($key);

            if (is_string($reason) && $reason !== '') {
                return $reason;
            }
        }

        return null;
    }

    /** Use the pass up. */
    public function take(string $user, Entry $entry): void
    {
        foreach ($this->keys($user, $entry) as $key) {
            if ($this->cache->pull($key) !== null) {
                return;
            }
        }
    }

    /**
     * The entry's own pass first, then one for a new page in its collection.
     *
     * @return list<string>
     */
    private function keys(string $user, Entry $entry): array
    {
        $collection = (string) $entry->collection()?->handle();

        return array_values(array_unique([$this->key($user, $entry->id(), $collection), $this->key($user, null, $collection)]));
    }

    private function key(string $user, ?string $entry, string $collection): string
    {
        return 'copydesk.override.'.$user.'.'.($entry === null || $entry === '' ? 'new.'.$collection : $entry);
    }
}
