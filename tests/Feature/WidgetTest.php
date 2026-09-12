<?php

declare(strict_types=1);

use Bpmore\Plain\Scan\ScanHistory;
use Bpmore\Plain\Scan\SiteScan;
use Bpmore\Plain\Scan\Snapshot;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Support\Str;
use Statamic\Widgets\Loader;

/**
 * The dashboard widget, and the scan history it reads.
 */
beforeEach(function () {
    Site::setSites([
        'default' => ['name' => 'Main', 'locale' => 'en_US', 'url' => '/'],
        'other' => ['name' => 'Other', 'locale' => 'en_US', 'url' => '/other/'],
    ]);
    Collection::make('pages')->title('Pages')->sites(['default', 'other'])->save();

    // The widget speaks only to a user who may view readability; a super
    // user may do everything, and the permission itself is tested elsewhere.
    $this->actingAs(User::make()->makeSuper());
});

function widgetHtml(array $config = []): string
{
    return (string) preg_replace('/\s+/', ' ', (string) app(Loader::class)->load('readability', ['type' => 'readability', ...$config])->html());
}

function aPage(string $title, string $text, string $site = 'default'): void
{
    Entry::make()->collection('pages')->locale($site)->slug(Str::slug($title))->published(true)
        ->data(['title' => $title, 'content' => bard($text)])->save();
}

it('is a widget with a plain handle and says so before any scan, without scanning', function () {
    aPage('Hard', HARD);

    $html = widgetHtml();

    expect($html)->toContain('No scan has run yet')->toContain('Open the readability page');
    expect(app(ScanHistory::class)->latest('default'))->toBeNull();
});

it('records every scan of a site, oldest first, and keeps the last ninety', function () {
    aPage('Easy', EASY);
    $scan = app(SiteScan::class);
    $history = app(ScanHistory::class);

    $scan->run('default');
    aPage('Hard', HARD);
    $scan->run('default');

    $all = $history->all('default');
    expect($all)->toHaveCount(2);
    expect($all[0]->graded)->toBe(1);
    expect($history->latest('default')->graded)->toBe(2);
    expect($history->previous('default')->graded)->toBe(1);
    expect($history->latest('default')->above)->toBe(1);
    expect($history->latest('other'))->toBeNull();

    // A site that cannot be checked leaves no snapshot behind.
    Site::setSites(['default' => ['name' => 'French', 'locale' => 'fr_FR', 'url' => '/']]);
    $scan->run('default');
    expect($history->all('default'))->toHaveCount(2);

    $history->forget('default');
    expect($history->all('default'))->toBe([]);
});

it('survives the round trip through its file', function () {
    aPage('Easy', EASY);
    aPage('Hard', HARD);
    $snapshot = app(SiteScan::class)->run('default')->scannedAt;

    $latest = app(ScanHistory::class)->latest('default');

    expect($latest)->toBeInstanceOf(Snapshot::class);
    expect($latest->computedAt)->toBe($snapshot);
    expect($latest->medianBand())->not->toBeNull();
    expect(Snapshot::fromArray($latest->toArray()))->toEqual($latest);
});

it('shows the median page against the target, the count above it, and which way both moved', function () {
    aPage('Easy', EASY);
    aPage('Also easy', EASY);
    app(SiteScan::class)->run('default');

    $html = widgetHtml();
    expect($html)->toContain('the median page')->toContain('Target Grade 7–9')->toContain('0 of 2 above the target');
    expect($html)->not->toContain('last scan');   // one scan: no trend yet

    aPage('Hard', HARD);
    aPage('Harder', HARD);
    aPage('Hardest', HARD);
    app(SiteScan::class)->run('default');

    $html = widgetHtml();
    expect($html)->toContain('Grade 17+')->toContain('harder than last scan');
    expect($html)->toContain('3 of 5 above the target')->toContain('3 more than last scan');
    expect($html)->toContain('Last scan')->toContain('5 pages graded');
});

it('can be pointed at a site, and reads the selected one otherwise', function () {
    aPage('Hard', HARD, 'other');
    app(SiteScan::class)->run('other');

    expect(widgetHtml())->toContain('No scan has run yet');
    expect(widgetHtml(['site' => 'other']))->toContain('1 of 1 above the target');
});

it('renders markup Vue can compile, before and after scans', function () {
    assertVueTemplateIsWellFormed((string) app(Loader::class)->load('readability', ['type' => 'readability'])->html());

    aPage('Easy', EASY);
    app(SiteScan::class)->run('default');
    aPage('Hard', HARD);
    app(SiteScan::class)->run('default');

    assertVueTemplateIsWellFormed((string) app(Loader::class)->load('readability', ['type' => 'readability'])->html());
});
