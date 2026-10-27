<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Scan;

use Bpmore\Copydesk\Edition;
use Statamic\Facades\User;

/**
 * Who may see the site report: Copydesk Pro, and `view copydesk report`.
 */
final class SiteReport
{
    public const PERMISSION = 'view copydesk report';

    /** Why the current user cannot see the report, or null when they can. */
    public static function refusal(): ?string
    {
        if (! Edition::isPro()) {
            return 'The site report is part of Copydesk Pro.';
        }

        if (! User::current()?->can(self::PERMISSION)) {
            return 'Your role cannot see the site report.';
        }

        return null;
    }

    public static function authorize(): void
    {
        $refusal = self::refusal();

        abort_if($refusal !== null, 403, (string) $refusal);
    }
}
