<?php

declare(strict_types=1);

namespace Bpmore\Plain;

use Statamic\Facades\Addon;

/**
 * Accessibility Report, when it is installed beside this addon.
 *
 * The two share an engine and do not share a surface. This addon owns the
 * publish-form panel, the gate, the dictionary and the site page under
 * Tools, and grades an entry's fields as its author writes them.
 * Accessibility Report owns the scheduled scan of the site as served and
 * the 3.1.5 row in its conformance document. A site with both installed
 * sees two readings of its pages, one from each side of the publish
 * button, and somebody meeting both for the first time can reasonably
 * think one of them is a mistake. The sentence here says otherwise, once,
 * where the site-wide numbers are.
 *
 * Detected through Statamic's addon manifest rather than by class, so a
 * test can install the neighbour by writing a manifest entry, and so that
 * a package on disk that Statamic has not registered counts as absent.
 */
final class Companion
{
    public const A11Y_REPORT = 'bpmore/statamic-a11y-report';

    public static function a11yReportInstalled(): bool
    {
        return Addon::get(self::A11Y_REPORT) !== null;
    }

    /**
     * The one line to show when Accessibility Report is installed too, or
     * null when it is not.
     */
    public static function notice(): ?string
    {
        if (! self::a11yReportInstalled()) {
            return null;
        }

        return 'Accessibility Report is installed as well. It grades every page as served on its own scheduled scan and reports the reading level under WCAG 3.1.5 in its conformance document; this page, the publish-form panel and the gate are Plain\'s, and grade what an author writes. They are two readings of the same site, not two scans of the same thing.';
    }
}
