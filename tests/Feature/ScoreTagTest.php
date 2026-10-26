<?php

declare(strict_types=1);

use Bpmore\Copydesk\Settings;
use Illuminate\Support\Facades\Cache;
use Statamic\Facades\Antlers;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;

/**
 * The reading level on the page itself.
 */
beforeEach(function () {
    Collection::make('pages')->save();
});

function render(string $template, array $context = []): string
{
    return trim((string) Antlers::parse($template, $context, trusted: true));
}

function entryWith(string $text, string $slug = 'p'): Statamic\Contracts\Entries\Entry
{
    $entry = Entry::make()->collection('pages')->slug($slug)->published(true)->data(['title' => 'P', 'content' => bard($text)]);
    $entry->save();

    return $entry;
}

it('prints the band for the current page, and nothing else', function () {
    $entry = entryWith(EASY);

    expect(render('{{ copydesk:score }}', ['id' => $entry->id()]))->toMatch('/^Grade \d+–\d+$/');
});

it('hands a pair the parts, and says whether it graded', function () {
    $entry = entryWith(HARD);

    $out = render(
        '{{ copydesk:score }}{{ if graded }}{{ band }}|{{ low }}|{{ high }}|{{ target }}|{{ comparison }}|{{ if above_target }}above{{ /if }}|{{ words }}|{{ sentences }}{{ /if }}{{ /copydesk:score }}',
        ['id' => $entry->id()],
    );

    expect($out)->toBe('Grade 17+|17||Grade 7–9|above|above|'.render('{{ copydesk:score }}{{ words }}{{ /copydesk:score }}', ['id' => $entry->id()]).'|3');
});

it('grades another entry when told which', function () {
    entryWith(EASY, 'easy');
    $hard = entryWith(HARD, 'hard');

    expect(render('{{ copydesk:score entry="'.$hard->id().'" }}', ['id' => 'ignored']))->toBe('Grade 17+');
});

it('prints nothing on a page that cannot be graded, and tells a pair why', function () {
    $entry = entryWith(EASY);

    expect(render('{{ copydesk:score }}', []))->toBe('');
    expect(render('{{ copydesk:score }}', ['id' => 'nope']))->toBe('');
    expect(render('{{ copydesk:score }}{{ if graded }}yes{{ else }}{{ reason }}{{ /if }}{{ /copydesk:score }}', []))->toBe('There is no entry to grade.');

    // A French site, with an entry made on it: an entry keeps the site it
    // was loaded with, so the site is set before the entry exists.
    Site::setSites(['default' => ['name' => 'French', 'locale' => 'fr_FR', 'url' => '/']]);
    $french = entryWith(EASY, 'fr');
    expect(render('{{ copydesk:score }}', ['id' => $french->id()]))->toBe('');
    expect(render('{{ copydesk:score }}{{ reason }}{{ /copydesk:score }}', ['id' => $french->id()]))->toContain('not checked on this site')->toContain('fr_FR');
});

it('prints nothing when the addon is turned off', function () {
    config()->set('copydesk.enabled', false);
    app()->forgetInstance(Settings::class);
    $entry = entryWith(EASY);

    expect(render('{{ copydesk:score }}', ['id' => $entry->id()]))->toBe('');
    expect(render('{{ copydesk:score }}{{ reason }}{{ /copydesk:score }}', ['id' => $entry->id()]))->toBe('Readability checking is turned off.');
});

it('caches the grade per entry and its last change, so a template does not grade on every visit', function () {
    $entry = entryWith(EASY);
    $key = 'copydesk.score.'.$entry->id().'.'.$entry->lastModified()->getTimestamp().'.'.Site::default()->locale();

    expect(Cache::has($key))->toBeFalse();
    render('{{ copydesk:score }}', ['id' => $entry->id()]);
    expect(Cache::has($key))->toBeTrue();
    expect(Cache::get($key)['graded'])->toBeTrue();
});
