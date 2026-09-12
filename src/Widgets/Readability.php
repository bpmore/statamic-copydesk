<?php

declare(strict_types=1);

namespace Bpmore\Plain\Widgets;

use Bpmore\Plain\Scan\ScanHistory;
use Bpmore\Plain\Settings;
use Statamic\Facades\Site;
use Statamic\Facades\Utility;
use Statamic\Widgets\Widget;

/**
 * The median page's band against the target, how many pages read above
 * it, and which way both moved since the last scan. On the dashboard,
 * which is what makes the product visible daily. Add `readability` to
 * the widgets in `config/statamic/cp.php`; optional `site` config picks a
 * site, the selected one otherwise.
 *
 * It reads what the last scan stored and never scans: a dashboard that
 * graded a thousand pages on every load would be the first thing removed.
 */
class Readability extends Widget
{
    public function html()
    {
        $site = (string) ($this->config('site') ?: Site::selected()->handle());
        $history = app(ScanHistory::class);
        $latest = $history->latest($site);
        $previous = $history->previous($site);

        return view('plain::widgets.readability', [
            'site' => Site::get($site),
            'latest' => $latest,
            'previous' => $previous,
            'target' => app(Settings::class)->target(),
            'url' => Utility::find('plain')?->url(),
            'medianMoved' => $latest?->medianBand() !== null && $previous?->medianBand() !== null
                ? $latest->medianBand()->low <=> $previous->medianBand()->low
                : 0,
            'aboveMoved' => $latest !== null && $previous !== null ? $latest->above - $previous->above : 0,
        ]);
    }
}
