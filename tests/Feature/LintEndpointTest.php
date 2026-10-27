<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\User;

/**
 * The endpoint the editor asks as the author types (Phase 1.5).
 */
beforeEach(function () {
    // Pro: these tests use the packs, the site report and the signal Free leaves out.
    pro();
    Collection::make('pages')->save();
    $this->user = User::make()->makeSuper();
    $this->user->save();
    $this->entry = Entry::make()->collection('pages')->slug('page')->data(['title' => 'Page', 'content' => bard('Fine.')]);
    $this->entry->save();
});

function lint(string $kind, mixed $value, string $field = 'content', array $extra = []): TestResponse
{
    return test()->actingAs(test()->user)->postJson(cp_route('copydesk.lint'), [
        'reference' => test()->entry->reference(), 'kind' => $kind, 'field' => $field, 'value' => $value,
    ] + $extra);
}

it('underlines an em dash in Bard, with the replacements on offer (acceptance 1, server side)', function () {
    $issue = collect(lint('bard', bard('Plain words — short ones.'))->assertOk()->json('issues'))->firstWhere('rule', 'no-em-dash');

    expect($issue)->toMatchArray([
        'severity' => 'warning',
        'path' => '0',
        'offset' => 12,
        'length' => 1,
        'suggestions' => [', ', ': '],
        'excerpt' => '—',
    ]);
});

it('counts offsets the way JavaScript does, in UTF-16 units', function () {
    // The emoji is two units; the curly quotes and the accent one each.
    $issue = collect(lint('bard', bard("😀 \u{201C}Café\u{201D} — yes."))->json('issues'))->firstWhere('rule', 'no-em-dash');

    expect($issue['offset'])->toBe(10);
});

it('points into a list item by its node path', function () {
    $value = [['type' => 'bulletList', 'content' => [['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'We utilize it.']]]]]]]];

    expect(collect(lint('bard', $value)->json('issues'))->firstWhere('rule', 'plain-words'))->toMatchArray(['path' => '0.0.0', 'offset' => 3, 'length' => 7, 'suggestions' => ['use']]);
});

it('counts inline code in the offsets but never flags it', function () {
    $value = [['type' => 'paragraph', 'content' => [
        ['type' => 'text', 'text' => 'Run '],
        ['type' => 'text', 'text' => 'a  b', 'marks' => [['type' => 'code']]],
        ['type' => 'text', 'text' => ' and  go.'],
    ]]];

    $issues = collect(lint('bard', $value)->json('issues'))->where('rule', 'no-double-space')->values();

    // "Run " (4) and the code "a  b" (4) and " and" (4) come first.
    expect($issues)->toHaveCount(1)->and($issues[0]['offset'])->toBe(12);
});

it('checks a textarea with positions from the start of the value, and markdown without', function () {
    // "Café" is one character to JavaScript and five bytes here; the blank
    // paragraph in the middle makes no block, and must not shift the next.
    $value = "Café line.\r\n\r\n  \n\nWe utilize it.";
    $issue = collect(lint('textarea', $value, 'summary')->json('issues'))->firstWhere('rule', 'plain-words');

    expect($issue)->toMatchArray(['path' => null, 'length' => 7])
        ->and(mb_substr(str_replace("\r\n", "\n", $value), $issue['offset'], 7))->toBe('utilize')
        ->and(collect(lint('markdown', 'We **utilize** it.', 'notes')->json('issues'))->firstWhere('rule', 'plain-words'))
        ->toMatchArray(['path' => null, 'offset' => null, 'excerpt' => 'utilize']);
});

it('runs a rule only on the fields its scope names', function () {
    File::ensureDirectoryExists(resource_path('copydesk'));
    File::put(resource_path('copydesk/house.yaml'), "rules:\n  - id: house-names\n    type: term\n    terms: [eiSEO]\n    message: m\n    scope:\n      fields: [summary]\n");

    expect(collect(lint('textarea', 'Try eiseo.', 'summary')->json('issues'))->pluck('rule'))->toContain('house-names')
        ->and(collect(lint('bard', bard('Try eiseo.'))->json('issues'))->pluck('rule'))->not->toContain('house-names');

    File::deleteDirectory(resource_path('copydesk'));
});

it('asks the same permission as the panel, and refuses what it does not check', function () {
    config()->set('statamic.editions.pro', true);
    $reader = User::make()->email('reader@example.test');
    $reader->save();

    test()->actingAs($reader)->postJson(cp_route('copydesk.lint'), ['reference' => $this->entry->reference(), 'kind' => 'bard', 'field' => 'content', 'value' => []])->assertForbidden();
    lint('assets', [])->assertStatus(422);
});

it('says why it checks nothing on a site that is not in English', function () {
    config()->set('statamic.sites.sites.default.locale', 'fr_FR');
    Site::setSites(['default' => ['name' => 'French', 'locale' => 'fr_FR', 'url' => '/']]);
    $this->entry = Entry::make()->collection('pages')->slug('french')->data(['title' => 'P', 'content' => bard('Fine.')]);
    $this->entry->save();

    $response = lint('bard', bard('Plain words — short ones.'))->assertOk();

    expect($response->json('issues'))->toBe([])->and($response->json('refused'))->toContain('not checked');
});
