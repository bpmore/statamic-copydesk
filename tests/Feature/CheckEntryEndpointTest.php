<?php

declare(strict_types=1);

use Illuminate\Testing\TestResponse;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\User;

/**
 * The endpoint the panel asks. Its whole reason to exist is the unsaved
 * values, so that is what most of this file is about.
 */
beforeEach(function () {
    Collection::make('pages')->save();

    $this->user = User::make()->makeSuper();
    $this->user->save();
});

const PLAIN_TEXT = 'A flu shot keeps you well. It is free. It takes five minutes. Ask us today.';

const HARD_TEXT = 'Immunization against seasonal influenza substantially reduces hospitalization among immunocompromised populations, according to epidemiological surveillance conducted by the relevant public health authorities, whose recommendations are promulgated annually.';

function savedPage(array $content): Statamic\Contracts\Entries\Entry
{
    $entry = Entry::make()->collection('pages')->slug('flu')->published(true)->data(['title' => 'Flu', 'content' => $content]);
    $entry->save();

    return $entry;
}

function check(array $payload): TestResponse
{
    return test()->actingAs(test()->user)->postJson(cp_route('copydesk.check'), $payload);
}

it('grades the values in the form, not the ones on disk', function () {
    // On disk this page is plain; the author has just pasted the fine print
    // and has not saved. A panel that answered from the file would show a
    // plain page at the exact moment the author needed to be told otherwise.
    $entry = savedPage(bard(PLAIN_TEXT));

    $response = check(['reference' => $entry->reference(), 'values' => ['content' => bard(HARD_TEXT)]]);

    $response->assertOk();
    expect($response->json('locale.allowed'))->toBeTrue();
    expect($response->json('band.label'))->toBe('Grade 17+');
    expect($response->json('comparison'))->toBe('above');
    expect($response->json('target.label'))->toBe('Grade 7–9');
    expect($response->json('fields'))->toBe(['content']);
    expect($response->json('counts.words'))->toBeGreaterThan(20);
    expect($response->json('grades.consensus'))->toBeGreaterThan(17);
});

it('reports a plain page as on or below target, with nothing to fix', function () {
    $entry = savedPage(bard(HARD_TEXT));

    $response = check(['reference' => $entry->reference(), 'values' => ['content' => bard(PLAIN_TEXT)]]);

    $response->assertOk();
    expect($response->json('comparison'))->toBe('below');
    expect($response->json('findings'))->toBe([]);
    expect($response->json('summary'))->toBe(['ignored' => 0, 'errors' => 0, 'warnings' => 0, 'tips' => 0]);
});

it('sends every finding with its field, its sentence, and where in the sentence the flagged words are', function () {
    config()->set('copydesk.fields', ['*' => ['content', 'summary']]);
    $entry = savedPage(bard(PLAIN_TEXT));
    $long = 'Word '.implode(' ', array_fill(0, 30, 'word')).'.';

    $response = check(['reference' => $entry->reference(), 'values' => [
        'content' => bard('We utilize forms.', $long),
        'summary' => 'In order to help, we utilize café forms.',
    ]]);

    $response->assertOk();
    $findings = collect($response->json('findings'));

    // The starter words come from the plain-language pack now, not the
    // engine's jargon rule; the site's own words keep the id `jargon`.
    $jargon = $findings->where('rule', 'plain-words')->values();
    expect($jargon)->toHaveCount(3);
    expect($jargon[0]['field'])->toBe('content');
    expect($jargon[0]['context'])->toBe(['text' => 'We utilize forms.', 'offset' => 3, 'length' => 7]);
    expect($jargon[0]['suggestion'])->toBe('use');

    // The summary is its own field, and its offsets are in characters: the
    // accent in "café" sits before nothing here, but the count is by
    // character regardless.
    $inSummary = $jargon->where('field', 'summary')->values();
    expect($inSummary->pluck('excerpt')->all())->toBe(['In order to', 'utilize']);
    expect($inSummary[1]['context'])->toBe(['text' => 'In order to help, we utilize café forms.', 'offset' => 21, 'length' => 7]);

    $sentence = $findings->where('rule', 'long_sentence')->first();
    expect($sentence['field'])->toBe('content');
    expect($sentence['block'])->toBe(1);
    expect($sentence['severity'])->toBe('warning');
    expect($response->json('summary.warnings'))->toBeGreaterThanOrEqual(4);
});

it('grades each section under a heading on its own', function () {
    $entry = savedPage(bard(PLAIN_TEXT));

    $response = check(['reference' => $entry->reference(), 'values' => ['content' => bard('# Plain', PLAIN_TEXT, '# The fine print', HARD_TEXT)]]);

    $sections = $response->json('sections');
    expect(array_column($sections, 'title'))->toBe(['Plain', 'The fine print']);
    expect($sections[0]['comparison'])->toBe('below');
    expect($sections[1]['band']['label'])->toBe('Grade 17+');
    expect($sections[1]['blocks'])->toBe([2, 3]);
});

it('reads only the fields the site configured, and only the kinds that are prose', function () {
    config()->set('copydesk.fields', ['*' => ['content'], 'pages' => ['summary', 'notes', 'hero', 'missing']]);
    $entry = savedPage(bard(PLAIN_TEXT));

    $response = check(['reference' => $entry->reference(), 'values' => [
        'content' => bard(PLAIN_TEXT),
        'summary' => 'We utilize it.',
        // Not "Notes": a heading that says so is a references section to the
        // exclusions, and goes with everything under it. Which is right.
        'notes' => "## Details\n\nWe *utilize* it `in order to` help.",
    ]]);

    // `hero` is an assets field and `missing` is not on the form: neither is read.
    expect($response->json('fields'))->toBe(['content', 'summary', 'notes']);
    // The markdown was rendered first: the heading is a section, the
    // emphasis is words, and the inline code was left out.
    expect(array_column($response->json('sections'), 'title'))->toBe(['', 'Details']);
    expect(collect($response->json('findings'))->where('rule', 'plain-words')->pluck('field')->all())->toBe(['summary', 'notes']);
});

it('refuses another language with the guard\'s own words rather than a wrong number', function () {
    config()->set('statamic.sites.sites.default.locale', 'fr_FR');
    Site::setSites(['default' => ['name' => 'French', 'locale' => 'fr_FR', 'url' => '/']]);
    $entry = savedPage(bard(PLAIN_TEXT));

    $response = check(['reference' => $entry->reference(), 'values' => ['content' => bard(HARD_TEXT)]]);

    $response->assertOk();
    expect($response->json('locale.allowed'))->toBeFalse();
    expect($response->json('locale.message'))->toContain('not checked')->toContain('fr_FR');
    expect($response->json('band'))->toBeNull();
    expect($response->json('findings'))->toBe([]);
});

it('checks a page nobody has saved yet, from the collection and form the panel names', function () {
    $response = check(['values' => ['title' => 'New', 'content' => bard(HARD_TEXT)], 'collection' => 'pages', 'blueprint' => 'pages']);

    $response->assertOk();
    expect($response->json('band.label'))->toBe('Grade 17+');
});

it('says so, in a sentence, when it cannot check', function () {
    check(['values' => ['content' => bard(PLAIN_TEXT)]])
        ->assertStatus(422)
        ->assertJsonPath('message', 'This page has not been saved yet, and the panel was not told which collection it belongs to. Save it once, then check it.');

    check(['reference' => 'entry::nope', 'values' => []])
        ->assertStatus(404)
        ->assertJsonPath('message', 'That entry could not be found, so nothing about it was checked.');

    check(['values' => [], 'collection' => 'nope'])
        ->assertStatus(404)
        ->assertJsonPath('message', 'The collection [nope] could not be found, so nothing was checked.');
});

it('says when none of the fields it reads is on the form', function () {
    config()->set('copydesk.fields', ['*' => ['body']]);
    $entry = savedPage(bard(PLAIN_TEXT));

    $response = check(['reference' => $entry->reference(), 'values' => ['content' => bard(PLAIN_TEXT)]]);

    expect($response->json('fields'))->toBe([]);
    expect($response->json('band'))->toBeNull();
    expect($response->json('notices.0'))->toContain('None of the fields this check reads');
});

it('requires the same permission as opening the entry', function () {
    $entry = savedPage(bard(PLAIN_TEXT));
    $nobody = User::make();
    $nobody->save();

    $this->actingAs($nobody)
        ->postJson(cp_route('copydesk.check'), ['reference' => $entry->reference(), 'values' => []])
        ->assertStatus(403);
});

it('says where each finding is in the field as the author wrote it', function () {
    // Curly quotes, a double space and a no-break space before the word:
    // the engine cleans all three away, so the cleaned offset of "utilize"
    // is not its place in the editor. The written one is.
    $written = "Staff \u{201C}really\u{201D}  should\u{00A0}utilize the forms.";
    $entry = savedPage(bard($written));

    $findings = collect(check(['reference' => $entry->reference(), 'values' => ['content' => bard($written)]])->assertOk()->json('findings'));
    $jargon = $findings->firstWhere('rule', 'plain-words');

    expect($jargon['excerpt'])->toBe('utilize')
        ->and($jargon['offset'])->not->toBe(mb_strpos($written, 'utilize'))
        ->and($jargon['written']['offset'])->toBe(mb_strpos($written, 'utilize'))
        ->and($jargon['written']['length'])->toBe(7)
        ->and($jargon['written']['path'])->not->toBeNull();
});
