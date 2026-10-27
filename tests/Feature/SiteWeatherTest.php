<?php

declare(strict_types=1);

use Bpmore\Copydesk\Scan\EntryResult;
use Bpmore\Copydesk\Scan\ScanHistory;
use Bpmore\Copydesk\Scan\ScanReport;
use Bpmore\Copydesk\Scan\SiteScan;
use Bpmore\Copydesk\Settings;
use Bpmore\Copydesk\Weather\ReadabilityContributor;
use Bpmore\ReadabilityCore\Formula\Target;
use Bpmore\SiteWeather\Contracts\WeatherContributor;
use Bpmore\SiteWeather\Contributors;
use Bpmore\SiteWeather\Forecast;
use Bpmore\SiteWeather\Reading;
use Bpmore\SiteWeather\State;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;

/**
 * The Readability band on Site Weather's tile. Site Weather is a dev
 * dependency here, by path, so the real contract is what is tested, not a
 * stand-in; where it is not installed, as on a CI runner, this file skips.
 */
beforeEach(function () {
    // Pro: these tests use the packs, the site report and the signal Free leaves out.
    pro();
    if (! interface_exists(WeatherContributor::class)) {
        $this->markTestSkipped('Site Weather is not installed here; its contract cannot be tested.');
    }

    Site::setSites(['default' => ['name' => 'Main', 'locale' => 'en_US', 'url' => '/']]);
    Collection::make('pages')->title('Pages')->save();
});

/** A stored scan with this many pages above the target, out of this many graded. */
function seedScan(int $above, int $graded, int $skipped = 0, string $at = '2026-09-11T09:00:00+00:00'): void
{
    $entries = [];

    for ($i = 0; $i < $graded; $i++) {
        $over = $i < $above;
        $entries[] = new EntryResult((string) $i, "Page $i", 'pages', 'Pages', '/cp/x', ['low' => $over ? 12 : 6, 'high' => $over ? 13 : 7, 'label' => $over ? 'Grade 12–13' : 'Grade 6–7'], $over ? 12.5 : 6.5, $over ? 'above' : 'below', 0, 0, 0, [], []);
    }

    app(ScanHistory::class)->record(new ScanReport('default', Target::grade(8), $entries, $skipped, $at));
}

function readabilityBand(): Reading
{
    return app(ReadabilityContributor::class)->reading();
}

it('is tagged for Site Weather to find, under the readability key', function () {
    $tagged = collect(app()->tagged('site-weather.contributors'));

    expect($tagged)->toHaveCount(1)
        ->and($tagged->first())->toBeInstanceOf(ReadabilityContributor::class)
        ->and($tagged->first())->toBeInstanceOf(WeatherContributor::class)
        ->and($tagged->first()->key())->toBe('readability')
        ->and($tagged->first()->label())->toBe('Readability');
});

it('reads unknown with the next step before any scan, and points at the page under Tools', function () {
    $reading = readabilityBand();

    expect($reading->state)->toBe(State::Unknown)
        ->and($reading->headline)->toBe('No pages graded yet: open Readability under Tools')
        ->and($reading->url)->toBe('http://localhost/cp/utilities/copydesk')
        ->and($reading->computedAt)->toBeNull();
});

it('reads unknown when the addon is turned off, or the site is not in English', function () {
    seedScan(0, 4);

    config()->set('copydesk.enabled', false);
    app()->forgetInstance(Settings::class);
    expect(readabilityBand()->state)->toBe(State::Unknown)
        ->and(readabilityBand()->headline)->toBe('Turned off in the addon\'s configuration');

    config()->set('copydesk.enabled', true);
    app()->forgetInstance(Settings::class);
    Site::setSites(['default' => ['name' => 'French', 'locale' => 'fr_FR', 'url' => '/']]);
    expect(readabilityBand()->state)->toBe(State::Unknown)
        ->and(readabilityBand()->headline)->toBe('Not graded: this site is not written in English');
});

it('reads unknown, not clear, when pages were looked at and none had text to grade', function () {
    seedScan(0, 0, skipped: 3);

    expect(readabilityBand()->state)->toBe(State::Unknown)
        ->and(readabilityBand()->headline)->toBe('3 pages looked at, none with text to grade');
});

it('is clear when no page reads above the target, dated to the latest scan', function () {
    seedScan(0, 4, at: '2026-09-10T08:00:00+00:00');
    seedScan(0, 5, at: '2026-09-11T09:30:00+00:00');

    $reading = readabilityBand();

    expect($reading->state)->toBe(State::Clear)
        ->and($reading->headline)->toBe('All 5 pages on or below the target of Grade 7–9')
        ->and($reading->computedAt?->toIso8601String())->toBe('2026-09-11T09:30:00+00:00');
});

it('is fair under a tenth above, overcast under a quarter, rain under half, storm from half', function () {
    seedScan(1, 20);
    expect(readabilityBand()->state)->toBe(State::Fair)
        ->and(readabilityBand()->headline)->toBe('1 of 20 pages reads above the target of Grade 7–9');

    seedScan(2, 20);   // exactly a tenth: not under it
    expect(readabilityBand()->state)->toBe(State::Overcast);

    seedScan(4, 20);
    expect(readabilityBand()->state)->toBe(State::Overcast);

    seedScan(5, 20);
    expect(readabilityBand()->state)->toBe(State::Rain)
        ->and(readabilityBand()->headline)->toBe('5 of 20 pages read above the target of Grade 7–9');

    seedScan(10, 20);
    expect(readabilityBand()->state)->toBe(State::Storm);

    seedScan(1, 1);
    expect(readabilityBand()->state)->toBe(State::Storm)
        ->and(readabilityBand()->headline)->toBe('1 of 1 page reads above the target of Grade 7–9');
});

it('reaches the tile end to end from a real scan', function () {
    Entry::make()->collection('pages')->slug('easy')->published(true)->data(['title' => 'Easy', 'content' => bard(EASY)])->save();
    Entry::make()->collection('pages')->slug('hard')->published(true)->data(['title' => 'Hard', 'content' => bard(HARD)])->save();
    app(SiteScan::class)->run('default');

    $forecast = Forecast::from(app(Contributors::class));

    expect($forecast->bands)->toHaveCount(1)
        ->and($forecast->bands[0]->key)->toBe('readability')
        ->and($forecast->bands[0]->label)->toBe('Readability')
        ->and($forecast->overall())->toBe(State::Storm)   // one of two pages above target
        ->and($forecast->worstBand()?->reading->computedAt)->not->toBeNull()
        ->and($forecast->summary())->toBe('Overall: storm. Readability: storm, 1 of 2 pages reads above the target of Grade 7–9.');
});
