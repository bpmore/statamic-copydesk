<?php

declare(strict_types=1);

use Bpmore\Copydesk\Gate\GateSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;

/**
 * The gate: opt-in, per collection, and never on a grade.
 */
beforeEach(function () {
    Collection::make('pages')->save();
    Collection::make('notes')->save();
    config()->set('copydesk.gate', ['enabled' => true, 'collections' => ['pages'], 'block_on' => ['very_long_sentence', 'jargon']]);
});

/** A sentence of sixty words: over twice the long limit, so the very-long rule speaks. */
function sixtyWords(): string
{
    return 'Word '.implode(' ', array_fill(0, 59, 'word')).'.';
}

const GRADUATE_LEVEL = 'Immunization substantially reduces hospitalization. Epidemiological surveillance corroborates it. Immunocompromised populations benefit disproportionately.';

function page(array|string $content, bool $published = true, string $collection = 'pages'): Statamic\Contracts\Entries\Entry
{
    return Entry::make()->collection($collection)->slug('p')->published($published)->data(['title' => 'P', 'content' => $content]);
}

function refusal(callable $save): ?array
{
    try {
        $save();
    } catch (ValidationException $e) {
        return $e->errors()['copydesk'] ?? null;
    }

    return null;
}

it('refuses to publish a page with a very long sentence, and says what to fix and where', function () {
    $lines = refusal(fn () => page(bard('Fine.', sixtyWords()))->save());

    expect($lines)->not->toBeNull();
    expect($lines[0])->toBe('This page was not published. One thing has to be fixed first.');
    expect($lines[1])->toStartWith('This sentence runs to 60 words')->toEndWith('(in content)');
    expect($lines[2])->toBe('The grade never stops a publish, and neither do the other findings; they are in the Readability panel.');
});

it('never blocks on the grade, however hard the page reads', function () {
    // Graduate-level on every formula, in short sentences with no banned
    // term. It publishes. A grade band is a heuristic and a press release
    // stopped for scoring 11 is how a checker gets uninstalled.
    $entry = page(bard(GRADUATE_LEVEL));

    expect(refusal(fn () => $entry->save()))->toBeNull();
    expect(Entry::find($entry->id()))->not->toBeNull();

    // And there is no setting that could make it otherwise.
    expect(array_keys(get_object_vars(GateSettings::fromConfig([]))))->toBe(['enabled', 'collections', 'blockOn']);
});

it('lets a page through when the only findings are warnings and tips', function () {
    $long = 'Word '.implode(' ', array_fill(0, 30, 'word')).'.';   // long, not very long

    expect(refusal(fn () => page(bard($long, 'Basically we utilize forms in order to help.'))->save()))->toBeNull();
});

it('blocks on a banned term, and not on a term the dictionary gives a replacement for', function () {
    config()->set('copydesk.rules.jargon.dictionary', ['utilize' => 'use', 'please be advised that' => []]);

    expect(refusal(fn () => page(bard('We utilize forms.'))->save()))->toBeNull();

    $lines = refusal(fn () => page(bard('Please be advised that we utilize forms.'))->save());
    expect($lines)->not->toBeNull();
    expect($lines[1])->toContain('"Please be advised that" is on the site\'s list of words to avoid');
    expect($lines)->toHaveCount(3);
});

it('does not check a draft', function () {
    expect(refusal(fn () => page(bard(sixtyWords()), published: false)->save()))->toBeNull();
});

it('leaves a collection the site did not name alone', function () {
    expect(refusal(fn () => page(sixtyWords(), collection: 'notes')->save()))->toBeNull();
});

it('gates every collection when enabled with none named, and none at all when off, which is the default', function () {
    config()->set('copydesk.gate.collections', []);
    expect(refusal(fn () => page(sixtyWords(), collection: 'notes')->save()))->not->toBeNull();

    config()->set('copydesk.gate.enabled', false);
    expect(refusal(fn () => page(bard(sixtyWords()))->save()))->toBeNull();

    expect(GateSettings::fromConfig([])->enabled)->toBeFalse();
    expect(GateSettings::fromConfig([])->blockOn)->toBe(['very_long_sentence', 'jargon']);
    expect(GateSettings::fromConfig(['enabled' => true])->gates('anything'))->toBeTrue();
});

it('checks the values being saved, not the ones on disk', function () {
    $entry = page(bard('Fine.'));
    $entry->save();

    $entry->set('content', bard(sixtyWords()));

    expect(refusal(fn () => $entry->save()))->not->toBeNull();
});

it('stands aside for a site whose language it cannot check', function () {
    Site::setSites(['default' => ['name' => 'French', 'locale' => 'fr_FR', 'url' => '/']]);

    expect(refusal(fn () => page(bard(sixtyWords()))->save()))->toBeNull();
});

it('lets the save through and says so in the log when the check itself cannot run', function () {
    // A rule handle nobody has heard of makes the rule set refuse to build.
    // A broken config is this addon's problem, and it must never be the
    // reason a site cannot publish.
    config()->set('copydesk.rules', ['no_such_rule' => []]);
    Log::shouldReceive('error')->once()->withArgs(fn (string $message) => str_contains($message, 'saved unchecked') && str_contains($message, 'no_such_rule'));

    $entry = page(bard(sixtyWords()));

    expect(refusal(fn () => $entry->save()))->toBeNull();
    expect(Entry::find($entry->id()))->not->toBeNull();
});

it('draws its refusal in the panel under its own key', function () {
    $script = (string) file_get_contents(__DIR__.'/../../resources/js/copydesk-panel.js');

    expect($script)->toContain('.copydesk')->toContain('showRefusal');
});
