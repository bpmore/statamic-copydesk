<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Gate;

/**
 * The gate's settings, read once from config and passed around as a value.
 *
 * Off by default, and even on it blocks only on the rules named here,
 * never on a grade. There is no field for a grade or a band and there will
 * not be one: a grade band is a heuristic, and a press release stopped for
 * scoring 11 is how a checker gets uninstalled. The restraint is the point.
 */
final class GateSettings
{
    /** @var list<string> */
    public const DEFAULT_BLOCK_ON = ['very_long_sentence', 'jargon'];

    /**
     * @param  list<string>  $collections  handles to gate; empty means every collection, once enabled
     * @param  list<string>  $blockOn  rule handles whose findings stop a publish
     */
    public function __construct(
        public readonly bool $enabled = false,
        public readonly array $collections = [],
        public readonly array $blockOn = self::DEFAULT_BLOCK_ON,
    ) {}

    /**
     * @param  array<string, mixed>  $config  the `gate` section
     */
    public static function fromConfig(array $config): self
    {
        return new self(
            enabled: (bool) ($config['enabled'] ?? false),
            collections: array_values(array_filter(array_map(strval(...), (array) ($config['collections'] ?? [])))),
            blockOn: array_key_exists('block_on', $config)
                ? array_values(array_filter(array_map(strval(...), (array) $config['block_on'])))
                : self::DEFAULT_BLOCK_ON,
        );
    }

    public function gates(string $collection): bool
    {
        return $this->enabled && ($this->collections === [] || in_array($collection, $this->collections, true));
    }

    public function blocksOn(string $rule): bool
    {
        return in_array($rule, $this->blockOn, true);
    }
}
