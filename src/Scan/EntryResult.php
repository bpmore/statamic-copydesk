<?php

declare(strict_types=1);

namespace Bpmore\Plain\Scan;

/**
 * One entry, scanned: what a row on the utility page needs, and what the
 * site-wide tallies are made from. Plain values, so a scan can be cached.
 */
final class EntryResult
{
    /**
     * @param  array{low: int, high: ?int, label: string}|null  $band
     * @param  list<string>  $acronyms  each unexpanded acronym found, as written
     * @param  list<string>  $jargon  each dictionary term found, as written
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $collection,
        public readonly string $collectionTitle,
        public readonly string $editUrl,
        public readonly ?array $band,
        public readonly ?float $consensus,
        public readonly ?string $comparison,
        public readonly int $errors,
        public readonly int $warnings,
        public readonly int $tips,
        public readonly array $acronyms,
        public readonly array $jargon,
    ) {}

    public function isGraded(): bool
    {
        return $this->band !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(...$data);
    }
}
