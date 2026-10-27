<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Gate;

/**
 * Whether Copydesk's gate runs inside A11y Gate's, as one of its
 * contributors (docs/a11y-gate-plan.md).
 *
 * Only when A11y Gate is installed in a version that has the contract, and
 * only once the service provider has signed Copydesk up. Copydesk never
 * requires A11y Gate: everything here is by name, and an A11y Gate too old
 * for contributors leaves Copydesk gating on its own, so a site is never
 * left ungated.
 */
final class A11yGateBridge
{
    public const CONTRACT = 'Bpmore\\A11yGate\\Contracts\\GateContributor';

    public const TAG = 'a11y-gate.contributors';

    /** The listener that asks contributors; without it nobody would ask Copydesk. */
    public const LISTENER = 'Bpmore\\A11yGate\\Listeners\\RefuseUnlessAccessible';

    private static bool $joined = false;

    public static function available(): bool
    {
        return interface_exists(self::CONTRACT) && class_exists(self::LISTENER);
    }

    /** Called by the service provider once Copydesk is tagged. */
    public static function join(): void
    {
        self::$joined = true;
    }

    public static function leave(): void
    {
        self::$joined = false;
    }

    public static function joined(): bool
    {
        return self::$joined;
    }
}
