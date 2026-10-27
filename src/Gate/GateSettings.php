<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Gate;

/**
 * The gate's settings, read once from config and passed around as a value.
 *
 * Off by default, and even on it blocks only on a finding whose severity
 * is `error`, never on a grade. There is no setting for a grade or a band
 * and there will not be one: a grade band is a heuristic, and a press
 * release stopped for scoring 11 is how a checker gets uninstalled. Which
 * rules are errors is the site's to say, in its packs and in `rules`.
 */
final class GateSettings
{
    /**
     * @param  list<string>  $collections  handles to gate; empty means every collection, once enabled
     */
    public function __construct(
        public readonly bool $enabled = false,
        public readonly array $collections = [],
    ) {}

    /**
     * Plain 1.0's `block_on` is not read: what blocks is now the severity.
     *
     * @param  array<string, mixed>  $config  the `gate` section
     */
    public static function fromConfig(array $config): self
    {
        return new self(
            enabled: (bool) ($config['enabled'] ?? false),
            collections: array_values(array_filter(array_map(strval(...), (array) ($config['collections'] ?? [])))),
        );
    }

    public function gates(string $collection): bool
    {
        return $this->enabled && ($this->collections === [] || in_array($collection, $this->collections, true));
    }
}
