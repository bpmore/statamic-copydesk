<?php

declare(strict_types=1);

use Bpmore\Copydesk\Analysis\Analyzer;
use Bpmore\Copydesk\Analysis\HouseRules;
use Bpmore\Copydesk\Analysis\Report;
use Bpmore\Copydesk\Scan\ScanChunk;
use Bpmore\Copydesk\Scan\ScanHistory;
use Bpmore\Copydesk\Scan\ScanReport;
use Bpmore\Copydesk\Scan\SiteScan;
use Bpmore\Copydesk\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Fields\Blueprint;
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
    expect(Cache::get('copydesk.scan.default'))->toBeArray();
    expect(ScanReport::fromArray($first->toArray())->distribution())->toBe($first->distribution());

    $scan->forget('default');
    expect($scan->cached('default')->entries)->toHaveCount(2);
});

it('draws the scan on the page under Tools, and scans again on request', function () {
    published('Easy', EASY);
    published('Hard', HARD);
    published('Jargon', 'We utilize the NHS.', 'news');

    $text = utilityText($this->actingAs($this->user)->get(cp_route('utilities.index').'/copydesk')->assertOk()->getContent());

    expect($text)->toContain('3 published pages graded')->toContain('against a target of Grade 7–9');
    expect($text)->toContain('Hardest pages')->toContain('Grade 17+');
    expect($text)->toContain('NHS')->toContain('utilize');
    expect($text)->toContain('Scan again');

    $this->actingAs($this->user)->from(cp_route('utilities.index').'/copydesk')
        ->post(cp_route('utilities.copydesk.rescan', 'default'))
        ->assertRedirect()->assertSessionHas('success', 'Scanning again.');
    // The sync queue: the new scan is done before the redirect.
    expect(Cache::get('copydesk.scan.default'))->toBeArray();

    $this->actingAs($this->user)->post(cp_route('utilities.copydesk.rescan', 'nope'))->assertStatus(404);
});

it('says so when there is nothing to grade, without a table of nothing', function () {
    Entry::make()->collection('news')->locale('default')->slug('empty')->published(true)->data(['title' => 'Empty'])->save();

    $text = utilityText($this->actingAs($this->user)->get(cp_route('utilities.index').'/copydesk')->assertOk()->getContent());

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
        assertVueTemplateIsWellFormed(view('copydesk::utilities.readability', ['site' => Site::get('default'), 'scan' => $report, 'sites' => $sites, 'counts' => $counts, 'canManage' => true, 'companion' => null])->render());
    }
});

it('reads a Bard field that was saved as HTML as the markup it is', function () {
    // A Bard field set to save HTML stores a string of markup, not a node
    // tree. It reaches the scan, the gate and the tag through the entry's
    // data; the publish form always sends nodes.
    Entry::make()->collection('pages')->locale('default')->slug('html')->published(true)
        ->data(['title' => 'Saved as HTML', 'content' => '<h2>Notes for you</h2><p>We utilize forms in order to help.</p>'])->save();

    $report = app(SiteScan::class)->run('default');

    expect($report->entries)->toHaveCount(1);
    expect($report->entries[0]->jargon)->toBe(['utilize', 'in order to']);
    expect($report->failures)->toBe([]);
});

it('names a page the check threw on and grades the rest, rather than stopping', function () {
    published('Easy', EASY);
    published('Odd', HARD);

    app()->bind(Analyzer::class, fn ($app) => new class($app->make(Settings::class), $app->make(HouseRules::class)) extends Analyzer
    {
        public function analyze(Blueprint $blueprint, array $values, string $locale, ?string $site = null): Report
        {
            if (($values['title'] ?? null) === 'Odd') {
                throw new RuntimeException('a field this addon could not read');
            }

            return parent::analyze($blueprint, $values, $locale, $site);
        }
    });

    $report = app(SiteScan::class)->run('default');

    expect(array_map(fn ($e) => $e->title, $report->entries))->toBe(['Easy']);
    expect($report->failures)->toBe(['Odd: a field this addon could not read']);
    expect(ScanReport::fromArray($report->toArray())->failures)->toBe($report->failures);
});

it('scans in queued jobs of fifty, says how far it has got, and puts one report together at the end', function () {
    foreach (range(1, 120) as $i) {
        published("Page {$i}", EASY);
    }

    Queue::fake();
    $scan = app(SiteScan::class);

    expect($scan->cached('default'))->toBeNull()
        ->and($scan->progress('default'))->toBe(['done' => 0, 'total' => 120]);

    $jobs = Queue::pushed(ScanChunk::class);
    expect($jobs)->toHaveCount(3)
        ->and(array_map(fn (ScanChunk $job) => count($job->ids), $jobs->all()))->toBe([50, 50, 20]);

    $jobs->each(fn (ScanChunk $job) => $job->handle($scan));

    expect($scan->progress('default'))->toBeNull()
        ->and(count($scan->cached('default')->entries))->toBe(120)
        ->and(app(ScanHistory::class)->all('default'))->toHaveCount(1);
});

it('shows the progress on the page under Tools while a scan runs', function () {
    published('Easy', EASY);
    Queue::fake();

    $text = utilityText($this->actingAs($this->user)->get(cp_route('utilities.index').'/copydesk')->assertOk()->getContent());

    expect($text)->toContain('copydesk-scan-progress');
    $this->actingAs($this->user)->get(cp_route('utilities.copydesk.progress', 'default'))->assertOk()->assertExactJson(['done' => 0, 'total' => 1]);
});

it('reports issues by rule, by collection and by page, with short pages and ignored counts', function () {
    published('Long', 'Word '.implode(' ', array_fill(0, 59, 'word')).'.');
    published('Short', 'Plain words — short.', 'news');
    Entry::make()->collection('pages')->locale('default')->slug('ignored')->published(true)
        ->data(['title' => 'Ignored', 'content' => bard('Plain words — short.'), 'copydesk_ignores' => ['placeholder']])->save();

    $report = app(SiteScan::class)->run('default');

    expect(collect($report->byRule())->firstWhere('rule', 'very_long_sentence'))->toMatchArray(['severity' => 'error', 'count' => 1, 'pages' => 1])
        ->and(collect($report->byRule())->firstWhere('rule', 'no-em-dash'))->toMatchArray(['severity' => 'warning', 'pages' => 2])
        ->and($report->byRule()[0]['severity'])->toBe('error')
        ->and(array_map(fn ($p) => $p->title, $report->pagesWithIssues()))->toBe(['Long', 'Ignored', 'Short'])
        ->and(collect($report->issuesByCollection())->firstWhere('collection', 'news'))->toMatchArray(['pages' => 1, 'warnings' => 1]);
});

it('counts what authors ignored', function () {
    $content = bard('Plain words — short ones.');
    $print = collect(app(Analyzer::class)->analyze(Collection::findByHandle('pages')->entryBlueprint(), ['content' => $content], 'en', 'default')->toArray()['findings'])->firstWhere('rule', 'no-em-dash')['fingerprint'];
    Entry::make()->collection('pages')->locale('default')->slug('ignored')->published(true)
        ->data(['title' => 'Ignored', 'content' => $content, 'copydesk_ignores' => [$print]])->save();

    $report = app(SiteScan::class)->run('default');

    expect($report->ignored())->toBe(1)
        ->and(collect($report->byRule())->firstWhere('rule', 'no-em-dash'))->toBeNull();
});

it('downloads every issue as a CSV, a row per page and rule', function () {
    published('Long', 'Word '.implode(' ', array_fill(0, 59, 'word')).'.');

    $csv = $this->actingAs($this->user)->get(cp_route('utilities.copydesk.issues', 'default'))->assertOk()->streamedContent();
    $rows = array_map(str_getcsv(...), array_filter(explode("\n", $csv)));

    expect($rows[0])->toBe(['site', 'collection', 'page', 'edit_url', 'rule', 'severity', 'count', 'ignored_on_page'])
        ->and(collect($rows)->first(fn ($r) => $r[4] === 'very_long_sentence'))->toMatchArray([0 => 'default', 2 => 'Long', 5 => 'error', 6 => '1']);
});
