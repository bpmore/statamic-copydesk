<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Gate;

use Bpmore\Copydesk\Analysis\Report;
use Bpmore\ReadabilityCore\Rule\Finding;

/**
 * What the gate decided about one save, and why.
 */
final class GateResult
{
    public const CHECKED = 'checked';

    public const NOT_APPLICABLE = 'not-applicable';

    public const COULD_NOT_CHECK = 'could-not-check';

    /**
     * @param  list<Finding>  $blocking  the findings that stop this publish
     */
    private function __construct(
        public readonly string $outcome,
        public readonly string $reason,
        public readonly ?Report $report,
        public readonly array $blocking,
    ) {}

    /**
     * @param  list<Finding>  $blocking
     */
    public static function checked(Report $report, array $blocking): self
    {
        return new self(self::CHECKED, '', $report, $blocking);
    }

    public static function notApplicable(string $reason): self
    {
        return new self(self::NOT_APPLICABLE, $reason, null, []);
    }

    public static function couldNotCheck(string $reason): self
    {
        return new self(self::COULD_NOT_CHECK, $reason, null, []);
    }

    public function shouldRefuse(): bool
    {
        return $this->blocking !== [];
    }

    public function couldNotBeChecked(): bool
    {
        return $this->outcome === self::COULD_NOT_CHECK;
    }
}
