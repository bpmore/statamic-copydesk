<?php

declare(strict_types=1);

use Bpmore\Plain\Scan\ScanHistory;
use Illuminate\Support\Facades\Artisan;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Support\Str;

/**
 * `php please plain:check`: every page graded from a terminal, a CSV, and
 * an exit code a build can stop on.
 */
beforeEach(function () {
    Site::setSites([
        'default' => ['name' => 'Main', 'locale' => 'en_US', 'url' => '/'],
        'french' => ['name' => 'French', 'locale' => 'fr_FR', 'url' => '/fr/'],
    ]);
    Collection::make('pages')->title('Pages')->sites(['default', 'french'])->save();
    Collection::make('news')->title('News')->sites(['default'])->save();
});

function pageIn(string $collection, string $title, string $text, string $site = 'default'): void
{
    Entry::make()->collection($collection)->locale($site)->slug(Str::slug($title))->published(true)
        ->data(['title' => $title, 'content' => bard($text)])->save();
}

it('grades every site and says how each stands, exiting clean by default', function () {
    pageIn('pages', 'Easy', EASY);
    pageIn('pages', 'Hard', HARD);
    pageIn('pages', 'Bonjour', HARD, 'french');

    // The table is asserted as a table: Laravel renders one past the output
    // mock that expectsOutputToContain reads, which is what expectsTable is
    // for. The prose is read from the real buffer.
    $this->artisan('plain:check')
        ->expectsTable(['Page', 'Collection', 'Reads at', 'Target', 'Errors', 'Warnings', 'Tips'], [
            ['Hard', 'Pages', 'Grade 17+', 'ABOVE', 0, 0, 0],
            ['Easy', 'Pages', 'Grade 1–2', 'below', 0, 0, 0],
        ])
        ->assertExitCode(0);

    Artisan::call('plain:check');
    $output = Artisan::output();

    expect($output)->toContain('Main')
        ->toContain('2 graded against Grade 7–9: 1 above, 0 on target, 1 below.')
        ->toContain('French')
        ->toContain('not checked on this site')
        ->toContain('2 page(s) graded, 1 above the target.');

    // A full scan is recorded, as the page under Tools would record it.
    expect(app(ScanHistory::class)->latest('default')?->graded)->toBe(2);
    expect(app(ScanHistory::class)->latest('french'))->toBeNull();
});

it('exits 1 when more pages read above the target than the limit allows, as a count or a share', function () {
    pageIn('pages', 'Easy', EASY);
    pageIn('pages', 'Hard', HARD);

    $this->artisan('plain:check', ['--fail-above' => '0'])->expectsOutputToContain('1 of 2 pages read above the target; the limit is 0.')->assertExitCode(1);
    $this->artisan('plain:check', ['--fail-above' => '1'])->assertExitCode(0);
    $this->artisan('plain:check', ['--fail-above' => '40%'])->assertExitCode(1);
    $this->artisan('plain:check', ['--fail-above' => '50%'])->assertExitCode(0);

    config()->set('statamic-plain.check.fail_above', '0');
    $this->artisan('plain:check')->assertExitCode(1);
});

it('exits 1 on an error-level finding only when asked to', function () {
    pageIn('pages', 'Long', 'Word '.implode(' ', array_fill(0, 59, 'word')).'.');

    $this->artisan('plain:check')->assertExitCode(0);
    $this->artisan('plain:check', ['--fail-on-errors' => true])->expectsOutputToContain('1 error-level finding(s)')->assertExitCode(1);
});

it('narrows to sites and collections, and does not record a partial scan in the trend', function () {
    pageIn('pages', 'Easy', EASY);
    pageIn('news', 'Hard news', HARD);

    $this->artisan('plain:check', ['--site' => ['default'], '--collection' => ['news']])
        ->expectsTable(['Page', 'Collection', 'Reads at', 'Target', 'Errors', 'Warnings', 'Tips'], [
            ['Hard news', 'News', 'Grade 17+', 'ABOVE', 0, 0, 0],
        ])
        ->assertExitCode(0);

    Artisan::call('plain:check', ['--site' => ['default'], '--collection' => ['news']]);
    expect(Artisan::output())->toContain('1 graded against Grade 7–9: 1 above, 0 on target, 0 below.')->not->toContain('French');

    expect(app(ScanHistory::class)->latest('default'))->toBeNull();
});

it('refuses a site or collection it has never heard of, with exit code 2', function () {
    $this->artisan('plain:check', ['--site' => ['nope']])->expectsOutputToContain('There is no site called [nope].')->assertExitCode(2);
    $this->artisan('plain:check', ['--collection' => ['nope']])->expectsOutputToContain('There is no collection called [nope].')->assertExitCode(2);
});

it('writes a CSV, one row per page, hardest first', function () {
    pageIn('pages', 'Easy', EASY);
    pageIn('news', 'Jargon', 'We utilize the NHS. '.HARD);
    $path = storage_path('statamic/plain/test-export.csv');

    // Laravel's info component prints an absolute path relative to the app.
    $this->artisan('plain:check', ['--export' => $path])->expectsOutputToContain('Written to storage/statamic/plain/test-export.csv')->assertExitCode(0);

    $rows = array_map('str_getcsv', file($path, FILE_IGNORE_NEW_LINES));
    expect($rows[0])->toBe(['site', 'collection', 'id', 'title', 'edit_url', 'band', 'band_low', 'band_high', 'consensus', 'comparison', 'errors', 'warnings', 'tips', 'acronyms', 'jargon']);
    expect($rows[1][3])->toBe('Jargon');
    expect($rows[1][5])->toBe('Grade 17+');
    expect($rows[1][9])->toBe('above');
    expect($rows[1][13])->toBe('NHS');
    expect($rows[1][14])->toBe('utilize');
    expect($rows[2][3])->toBe('Easy');
    expect($rows)->toHaveCount(3);

    // "csv" alone means a dated file under storage.
    $this->artisan('plain:check', ['--export' => 'csv'])->expectsOutputToContain('storage/statamic/plain/plain-check-')->assertExitCode(0);
    expect(glob(storage_path('statamic/plain/plain-check-*.csv')))->not->toBe([]);
});
