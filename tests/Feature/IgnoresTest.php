<?php

declare(strict_types=1);

use Bpmore\Copydesk\Analysis\Analyzer;
use Bpmore\Copydesk\Analysis\Fingerprint;
use Bpmore\Copydesk\Settings;
use Illuminate\Validation\ValidationException;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\User;

/**
 * "Ignore here" (Phase 1.6): a fingerprint of the rule and the sentence,
 * kept on the entry, honoured by the editor, the panel and the gate, and
 * counted so it cannot hide a problem quietly.
 */
beforeEach(function () {
    Collection::make('pages')->save();
    $this->user = User::make()->makeSuper();
    $this->user->save();
    $this->entry = Entry::make()->collection('pages')->slug('page')->data(['title' => 'Page', 'content' => bard('Fine.')]);
    $this->entry->save();
});

/** @return array<string, mixed> the panel's report for these values */
function panelReport(array $values): array
{
    return app(Analyzer::class)->analyze(Collection::findByHandle('pages')->entryBlueprint(), $values, 'en', 'default')->toArray();
}

function dash(array $report): ?array
{
    return collect($report['findings'])->firstWhere('rule', 'no-em-dash');
}

it('gives the editor and the full check the same fingerprint for the same sentence', function () {
    $content = bard('Intro here.', 'Plain words — short ones. Then more.');

    $live = collect(test()->actingAs(test()->user)->postJson(cp_route('copydesk.lint'), [
        'reference' => test()->entry->reference(), 'kind' => 'bard', 'field' => 'content', 'value' => $content,
    ])->json('issues'))->firstWhere('rule', 'no-em-dash');

    expect($live['fingerprint'])->toStartWith('no-em-dash:')
        ->and(dash(panelReport(['title' => 'P', 'content' => $content]))['fingerprint'])->toBe($live['fingerprint']);
});

it('leaves an ignored finding out, and counts it', function () {
    $content = bard('Plain words — short ones.');
    $print = dash(panelReport(['content' => $content]))['fingerprint'];

    $after = panelReport(['content' => $content, Fingerprint::FIELD => [$print]]);

    expect(dash($after))->toBeNull()
        ->and($after['summary']['ignored'])->toBe(1);
});

it('keeps the ignore while other sentences change, and drops it when its own sentence does', function () {
    $print = dash(panelReport(['content' => bard('Plain words — short ones. Then more.')]))['fingerprint'];

    // Another paragraph added, extra spaces typed: still the same sentence.
    expect(dash(panelReport(['content' => bard('A new opening.', 'Plain  words — short ones. Then more.'), Fingerprint::FIELD => [$print]])))->toBeNull()
        // The sentence itself rewritten: a new question, asked again.
        ->and(dash(panelReport(['content' => bard('Plain words — very short ones.'), Fingerprint::FIELD => [$print]])))->not->toBeNull();
});

it('does not let an ignore for one rule hide another rule in the same sentence', function () {
    $content = bard('We utilize it — often.');
    $print = dash(panelReport(['content' => $content]))['fingerprint'];
    $after = panelReport(['content' => $content, Fingerprint::FIELD => [$print]]);

    expect(collect($after['findings'])->pluck('rule'))->toContain('plain-words')->not->toContain('no-em-dash');
});

it('puts a hidden field for the ignores on every form that has the panel', function () {
    $field = Collection::findByHandle('pages')->entryBlueprint()->field(Fingerprint::FIELD);

    expect($field)->not->toBeNull()
        ->and($field->type())->toBe('list')
        ->and($field->get('visibility'))->toBe('hidden');
});

it('takes an ignored finding out of the gate too', function () {
    config()->set('copydesk.gate', ['enabled' => true, 'collections' => ['pages'], 'block_on' => ['very_long_sentence']]);
    // Read from config once per request; the entry saved before this test
    // already read it, with the gate off.
    app()->forgetInstance(Settings::class);
    $long = 'Word '.implode(' ', array_fill(0, 59, 'word')).'.';
    $print = collect(panelReport(['content' => bard($long)])['findings'])->firstWhere('rule', 'very_long_sentence')['fingerprint'];

    $save = function (array $ignores): ?array {
        try {
            Entry::make()->collection('pages')->slug('long')->published(true)
                ->data(['title' => 'Long', 'content' => bard('Word '.implode(' ', array_fill(0, 59, 'word')).'.'), Fingerprint::FIELD => $ignores])
                ->save();
        } catch (ValidationException $e) {
            return $e->errors()['copydesk'] ?? null;
        }

        return null;
    };

    expect($save([]))->not->toBeNull()
        ->and($save([$print]))->toBeNull();
});

it('reads the stored list whatever shape it comes back in', function () {
    expect(Fingerprint::stored(null))->toBe([])
        ->and(Fingerprint::stored('a:b'))->toBe([])
        ->and(Fingerprint::stored(['a:1', '', 'a:1', 3, 'b:2']))->toBe(['a:1', 'b:2']);
});
