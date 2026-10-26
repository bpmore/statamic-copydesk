<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Scan;

use Bpmore\ReadabilityCore\Formula\Band;

/**
 * One scan of a site, reduced to the numbers a trend is made of.
 */
final class Snapshot
{
    public function __construct(
        public readonly string $computedAt,
        public readonly int $graded,
        public readonly int $skipped,
        public readonly int $above,
        public readonly int $onTarget,
        public readonly int $below,
        /** The median page's consensus grade; null when nothing was graded. */
        public readonly ?float $median,
    ) {}

    public static function of(ScanReport $report): self
    {
        $grades = array_map(static fn (EntryResult $e): float => (float) $e->consensus, $report->entries);
        sort($grades);
        $n = count($grades);
        $against = $report->againstTarget();

        return new self(
            $report->scannedAt,
            $n,
            $report->skipped,
            $against['above'],
            $against['on_target'],
            $against['below'],
            $n === 0 ? null : ($n % 2 === 1 ? $grades[intdiv($n, 2)] : ($grades[$n / 2 - 1] + $grades[$n / 2]) / 2),
        );
    }

    public function medianBand(): ?Band
    {
        return $this->median === null ? null : Band::of($this->median);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'computed_at' => $this->computedAt,
            'graded' => $this->graded,
            'skipped' => $this->skipped,
            'above' => $this->above,
            'on_target' => $this->onTarget,
            'below' => $this->below,
            'median' => $this->median,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['computed_at'],
            (int) $data['graded'],
            (int) $data['skipped'],
            (int) $data['above'],
            (int) $data['on_target'],
            (int) $data['below'],
            isset($data['median']) ? (float) $data['median'] : null,
        );
    }
}
