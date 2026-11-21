<?php

declare(strict_types=1);

namespace Bpmore\Copydesk;

use Statamic\Facades\Addon;

/**
 * Which edition this site runs: Free or Pro.
 *
 * The editions are listed in composer.json (`extra.statamic.editions`), Free
 * first, so a site that has chosen nothing runs Free. A site chooses Pro in
 * `config/statamic/editions.php`:
 *
 *     'addons' => ['bpmore/statamic-copydesk' => 'pro'],
 *
 * Statamic handles licence validation separately. This helper only reads the
 * configured edition, and an unknown value never enables Pro. Statamic 6.43+
 * reports unknown editions on its licensing screen.
 */
final class Edition
{
    public const PACKAGE = 'bpmore/statamic-copydesk';

    public const FREE = 'free';

    public const PRO = 'pro';

    public static function current(): string
    {
        $addon = Addon::get(self::PACKAGE);
        $configured = config('statamic.editions.addons.'.self::PACKAGE);

        if ($configured !== null && ! $addon?->editions()->containsStrict($configured)) {
            return self::FREE;
        }

        return (string) ($addon?->edition() ?? self::FREE);
    }

    public static function isPro(): bool
    {
        return self::current() === self::PRO;
    }
}
