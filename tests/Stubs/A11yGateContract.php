<?php

declare(strict_types=1);

/*
 * A11y Gate's contributor contract, as A11y Gate 0.11 defines it, for
 * testing Copydesk's side without requiring A11y Gate. Kept word for word
 * with bpmore/statamic-a11y-gate src/Contracts; declared only when the
 * real one is not installed.
 */

namespace Bpmore\A11yGate\Contracts;

use Statamic\Contracts\Entries\Entry;

if (! interface_exists(GateContributor::class)) {
    interface GateContributor
    {
        public const TAG = 'a11y-gate.contributors';

        public function key(): string;

        public function label(): string;

        public function inspect(Entry $entry): ContributedResult;
    }

    final class ContributedResult
    {
        /** @param list<string> $lines */
        private function __construct(public readonly bool $blocks, public readonly array $lines) {}

        public static function passes(): self
        {
            return new self(false, []);
        }

        /** @param list<string> $lines */
        public static function blocks(array $lines): self
        {
            return new self(true, array_values(array_map(strval(...), $lines)));
        }
    }
}
