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
 * Statamic answers the question and checks the licence; this only asks it,
 * so every Pro feature asks the same way. An edition the list does not have
 * is a mistake in the config, and Statamic throws rather than guess.
 */
final class Edition
{
    public const PACKAGE = 'bpmore/statamic-copydesk';

    public const FREE = 'free';

    public const PRO = 'pro';

    public static function current(): string
    {
        return (string) (Addon::get(self::PACKAGE)?->edition() ?? self::FREE);
    }

    public static function isPro(): bool
    {
        return self::current() === self::PRO;
    }
}
