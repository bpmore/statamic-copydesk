<?php

declare(strict_types=1);

use Bpmore\Plain\Scan\ScanReport;
use Bpmore\Plain\Scan\SiteScan;
use Illuminate\Support\Facades\Cache;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Support\Str;

/**
 * The site-wide scan behind the page under Tools: distribution, the
 * hardest pages, the acronyms and jargon that come up most.
 */
beforeEach(function () {
    Site::setSites([
        'default' => ['name' => 'Main', 'locale' => 'en_US', 'url' => '/'],
        'other' => ['name' => 'Other', 'locale' => 'en_US', 'url' => '/other/'],
    ]);
    Collection::make('pages')->title('Pages')->sites(['default', 'other'])->save();
    Collection::make('news')->title('News')->sites(['default', 'other'])->save();

    $this->user = User::make()->makeSuper();
    $this->user->save();
});

const EASY = 'A flu shot keeps you well. It is free. It takes five minutes. Ask us today.';

const HARD = 'Immunization substantially reduces hospitalization. Epidemiological surveillance corroborates it. Immunocompromised populations benefit disproportionately.';

function published(string $title, string $text, string $collection = 'pages', string $site = 'default', bool $published = true): void
{
    Entry::make()->collection($collection)->locale($site)->slug(Str::slug($title))->published($published)
        ->data(['title' => $title, 'content' => bard($text)])->save();
}

it('grades every published page of the site, and only those', function () {
    published('Easy', EASY);
    published('Hard', HARD);
    published('Draft', HARD, published: false);
    published('Elsewhere', HARD, site: 'other');
    Entry::make()->collection('news')->locale('default')->slug('empty')->published(true)->data(['title' => 'Empty'])->save();

    $report = app(SiteScan::class)->run('default');

    expect(array_map(fn ($e) => $e->title, $report->entries))->toEqualCanonicalizing(['Easy', 'Hard']);
    expect($report->skipped)->toBe(1);
    expect($report->refusal)->toBeNull();
    expect($report->entries[0]->editUrl)->toContain('/cp/collections/');
});

it('spreads the pages across the bands, every band between the lowest and highest, against the target', function () {
    published('Easy', EASY);
    published('Also easy', EASY, 'news');
    published('Hard', HARD);

    $report = app(SiteScan::class)->run('default');
    $distribution = $report->distribution();

    expect($distribution[0]['count'])->toBe(2);
    expect($distribution[0]['comparison'])->toBe('below');
    expect(end($distribution))->toBe(['band' => 'Grade 17+', 'count' => 1, 'comparison' => 'above']);
    expect(count($distribution))->toBeGreaterThan(3);   // the empty bands in between are there, at zero
    expect(array_sum(array_column($distribution, 'count')))->toBe(3);
    expect($report->againstTarget())->toBe(['on_target' => 0, 'above' => 1, 'below' => 2]);
});

it('lists the hardest pages first, with their collection, and summarises each collection', function () {
    published('Easy', EASY);
    published('Hard', 'The clinic offers seasonal vaccination for adults and children, with appointments available on weekday mornings and afternoons.');
    published('Harder news', HARD, 'news');

    $report = app(SiteScan::class)->run('default');

    expect(array_map(fn ($e) => [$e->title, $e->collectionTitle], $report->worst(2)))->toBe([['Harder news', 'News'], ['Hard', 'Pages']]);
    expect(array_map(fn ($e) => $e->title, $report->worst(1)))->toBe(['Harder news']);

    $collections = $report->byCollection();
    expect(array_column($collections, 'collection'))->toBe(['news', 'pages']);
    expect($collections[0])->toBe(['collection' => 'news', 'title' => 'News', 'entries' => 1, 'median' => 'Grade 17+', 'above' => 1]);
    expect($collections[1]['entries'])->toBe(2);
    expect($collections[1]['above'])->toBe(1);
});

it('counts the acronyms and jargon that come up most, by times and by pages', function () {
    published('One', 'Ask the NHS about your MRI. The NHS again. We utilize forms in order to help.');
    published('Two', 'Call the NHS. We utilize it.', 'news');

    $report = app(SiteScan::class)->run('default');

    expect($report->acronyms())->toBe([
        ['term' => 'nhs', 'count' => 2, 'pages' => 2],   // once per page: an acronym is flagged at its first use
        ['term' => 'mri', 'count' => 1, 'pages' => 1],
    ]);
    expect($report->jargon())->toBe([
        ['term' => 'utilize', 'count' => 2, 'pages' => 2],
        ['term' => 'in order to', 'count' => 1, 'pages' => 1],
    ]);
});

it('stands aside for a site whose language it cannot check', function () {
    Site::setSites(['default' => ['name' => 'French', 'locale' => 'fr_FR', 'url' => '/']]);
    published('Page', HARD);

    $report = app(SiteScan::class)->run('default');

    expect($report->isEmpty())->toBeTrue();
    expect($report->refusal)->toContain('not checked');
});

it('caches a scan for an hour, survives the round trip, and forgets on request', function () {
    published('Easy', EASY);
    $scan = app(SiteScan::class);

    $first = $scan->cached('default');
    published('Hard', HARD);

    expect($scan->cached('default')->entries)->toHaveCount(1);   // still the cached one
    expect(Cache::get('statamic-plain.scan.default'))->toBeArray();
    expect(ScanReport::fromArray($first->toArray())->distribution())->toBe($first->distribution());

    $scan->forget('default');
    expect($scan->cached('default')->entries)->toHaveCount(2);
});

it('draws the scan on the page under Tools, and scans again on request', function () {
    published('Easy', EASY);
    published('Hard', HARD);
    published('Jargon', 'We utilize the NHS.', 'news');

    $text = utilityText($this->actingAs($this->user)->get(cp_route('utilities.index').'/plain')->assertOk()->getContent());

    expect($text)->toContain('3 published pages graded')->toContain('against a target of Grade 7–9');
    expect($text)->toContain('Hardest pages')->toContain('Grade 17+');
    expect($text)->toContain('NHS')->toContain('utilize');
    expect($text)->toContain('Scan again');

    $this->actingAs($this->user)->from(cp_route('utilities.index').'/plain')
        ->post(cp_route('utilities.plain.rescan', 'default'))
        ->assertRedirect()->assertSessionHas('success', 'Scanned again.');
    expect(Cache::get('statamic-plain.scan.default'))->toBeNull();

    $this->actingAs($this->user)->post(cp_route('utilities.plain.rescan', 'nope'))->assertStatus(404);
});

it('says so when there is nothing to grade, without a table of nothing', function () {
    Entry::make()->collection('news')->locale('default')->slug('empty')->published(true)->data(['title' => 'Empty'])->save();

    $text = utilityText($this->actingAs($this->user)->get(cp_route('utilities.index').'/plain')->assertOk()->getContent());

    expect($text)->toContain('No page on this site has anything to grade yet')->toContain('1 published page was looked at');
    expect($text)->not->toContain('Hardest pages');
});

it('ships a page Vue can compile, in every state', function () {
    published('Easy', EASY);
    published('Hard', 'We utilize the NHS. '.HARD, 'news');
    $scan = app(SiteScan::class);
    $sites = Site::all()->values();
    $counts = ['default' => 0, 'other' => 0];

    foreach ([$scan->run('default'), $scan->run('other'), new ScanReport('default', $scan->run('default')->target, [], 0, now()->toIso8601String(), 'Not checked.')] as $report) {
        assertVueTemplateIsWellFormed(view('plain::utilities.readability', ['site' => Site::get('default'), 'scan' => $report, 'sites' => $sites, 'counts' => $counts])->render());
    }
});
