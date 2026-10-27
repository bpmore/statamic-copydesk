<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Weather;

use Bpmore\Copydesk\Edition;
use Bpmore\Copydesk\Scan\ScanHistory;
use Bpmore\Copydesk\Settings;
use Bpmore\SiteWeather\Contracts\WeatherContributor;
use Bpmore\SiteWeather\Reading;
use Bpmore\SiteWeather\State;
use Carbon\CarbonImmutable;
use Statamic\Facades\Site;
use Statamic\Facades\Utility;

/**
 * The Readability band on Site Weather's tile.
 *
 * Site Weather is an optional companion, not a dependency: this class is
 * tagged by name in the service provider and only ever loaded when Site
 * Weather resolves the tag, so the interface it implements need not exist
 * on a site that has not installed it.
 *
 * It reads. The last scan's stored snapshot for the site selected in the
 * control panel, the same numbers the dashboard widget draws, and never
 * grades a page: it runs on every dashboard load for every user. Not
 * turned on, not English, or nothing scanned yet reads as unknown, never
 * as clear, with the next step in the headline.
 *
 * The weather, decided here because this addon knows its own data: the
 * share of graded pages that read above the site's target grade.
 *
 *   clear     none
 *   fair      under a tenth
 *   overcast  under a quarter
 *   rain      under half
 *   storm     half or more
 *
 * A page below the target is not a problem and is not counted against the
 * site; easier than intended is fine.
 */
final class ReadabilityContributor implements WeatherContributor
{
    private const FAIR_UNDER = 0.10;

    private const OVERCAST_UNDER = 0.25;

    private const RAIN_UNDER = 0.50;

    public function __construct(
        private readonly ScanHistory $history,
        private readonly Settings $settings,
    ) {}

    public function key(): string
    {
        return 'readability';
    }

    public function label(): string
    {
        return 'Readability';
    }

    public function reading(): Reading
    {
        if (! Edition::isPro()) {
            return Reading::unknown('The Site Weather signal is part of Copydesk Pro', null);
        }

        $url = Utility::find('copydesk')?->url();
        $site = Site::selected();

        if (! $this->settings->enabled()) {
            return Reading::unknown('Turned off in the addon\'s configuration', $url);
        }

        if (! $this->settings->localeGuard()->allows((string) $site->locale())) {
            return Reading::unknown('Not graded: this site is not written in English', $url);
        }

        $latest = $this->history->latest($site->handle());

        if ($latest === null) {
            return Reading::unknown('No pages graded yet: open Readability under Tools', $url);
        }

        if ($latest->graded === 0) {
            return Reading::unknown(sprintf('%s looked at, none with text to grade', self::pages($latest->skipped)), $url);
        }

        $computedAt = CarbonImmutable::parse($latest->computedAt);
        $target = $this->settings->target()->band->label();

        if ($latest->above === 0) {
            return new Reading(State::Clear, sprintf('All %s on or below the target of %s', self::pages($latest->graded), $target), $url, $computedAt);
        }

        $share = $latest->above / $latest->graded;

        $state = match (true) {
            $share < self::FAIR_UNDER => State::Fair,
            $share < self::OVERCAST_UNDER => State::Overcast,
            $share < self::RAIN_UNDER => State::Rain,
            default => State::Storm,
        };

        $headline = sprintf(
            '%s of %s %s above the target of %s',
            number_format($latest->above),
            self::pages($latest->graded),
            $latest->above === 1 ? 'reads' : 'read',
            $target,
        );

        return new Reading($state, $headline, $url, $computedAt);
    }

    private static function pages(int $count): string
    {
        return number_format($count).' '.($count === 1 ? 'page' : 'pages');
    }
}
