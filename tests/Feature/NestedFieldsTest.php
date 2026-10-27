<?php

declare(strict_types=1);

use Bpmore\Copydesk\Analysis\Analyzer;
use Bpmore\Copydesk\Analysis\Fingerprint;
use Bpmore\Copydesk\Settings;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;

/**
 * The full check reads the prose nested in a configured field: the same
 * fields the editor underlines, so the panel, the gate and the report count
 * what the author saw.
 */
beforeEach(function () {
    Collection::make('pages')->save();
    config()->set('copydesk.fields', ['*' => ['content', 'sections']]);
    app()->forgetInstance(Settings::class);
});

/** Sixty words: the very-long rule speaks, once per sentence. */
function nestedLong(): string
{
    return 'Word '.implode(' ', array_fill(0, 59, 'word')).'.';
}

/** A form with a Bard and a Grid, and every kind of nesting in them. Not saved. */
function nestedBlueprint(): Statamic\Fields\Blueprint
{
    $prose = [
        ['handle' => 'body', 'field' => ['type' => 'bard']],
        ['handle' => 'notes', 'field' => ['type' => 'markdown']],
        ['handle' => 'summary', 'field' => ['type' => 'textarea']],
        ['handle' => 'caption', 'field' => ['type' => 'text']],
    ];

    return Blueprint::make('nested')->setNamespace('collections.pages')->setContents(['fields' => [
        ['handle' => 'content', 'field' => ['type' => 'bard', 'sets' => [
            'quote' => ['fields' => $prose],
            'code' => ['fields' => $prose],
        ]]],
        ['handle' => 'sections', 'field' => ['type' => 'grid', 'fields' => [
            ['handle' => 'blocks', 'field' => ['type' => 'replicator', 'sets' => [
                'text' => ['fields' => $prose],
                'code' => ['fields' => $prose],
            ]]],
            ['handle' => 'more', 'field' => ['type' => 'group', 'fields' => $prose]],
        ]]],
    ]]);
}

function nestedReport(array $values): array
{
    return app(Analyzer::class)->analyze(nestedBlueprint(), $values, 'en_US')->toArray();
}

/** The findings of the very-long rule, by field. */
function longIn(array $report): array
{
    return array_values(array_map(fn ($f) => $f['field'], array_filter($report['findings'], fn ($f) => $f['rule'] === 'very_long_sentence')));
}

it('reads a Bard in a Replicator set in a Grid, and counts it as the Grid', function () {
    $report = nestedReport(['sections' => [
        ['blocks' => [['type' => 'text', 'body' => bard(nestedLong())]]],
    ]]);

    expect(longIn($report))->toBe(['sections'])
        ->and($report['fields'])->toBe(['content', 'sections']);
});

it('reads markdown and textarea nested at any depth, and leaves nested text fields alone', function () {
    $report = nestedReport(['sections' => [
        ['blocks' => [['type' => 'text', 'notes' => nestedLong(), 'caption' => nestedLong()]], 'more' => ['summary' => nestedLong(), 'caption' => nestedLong()]],
    ]]);

    expect(longIn($report))->toBe(['sections', 'sections']);
});

it('reads the fields in a Bard set that are not Bard, once each', function () {
    $set = ['type' => 'set', 'attrs' => ['id' => 'a', 'values' => ['type' => 'quote', 'body' => bard(nestedLong()), 'summary' => nestedLong(), 'caption' => nestedLong()]]];

    $report = nestedReport(['content' => [...bard('Fine.'), $set]]);

    // The nested Bard, read by the engine as before, and the textarea, read
    // here. Not the caption, and nothing twice.
    expect(longIn($report))->toBe(['content', 'content']);
});

it('skips disabled sets and excluded sets, however deep', function () {
    config()->set('copydesk.exclude.bard_sets', ['code']);
    app()->forgetInstance(Settings::class);

    $report = nestedReport([
        'content' => [['type' => 'set', 'attrs' => ['id' => 'a', 'enabled' => false, 'values' => ['type' => 'quote', 'summary' => nestedLong()]]],
            ['type' => 'set', 'attrs' => ['id' => 'b', 'values' => ['type' => 'code', 'summary' => nestedLong()]]]],
        'sections' => [['blocks' => [
            ['type' => 'text', 'enabled' => false, 'body' => bard(nestedLong())],
            ['type' => 'code', 'body' => bard(nestedLong()), 'notes' => nestedLong()],
        ]]],
    ]);

    expect(longIn($report))->toBe([]);
});

it('keeps an ignore made in a nested field', function () {
    $values = ['sections' => [['blocks' => [['type' => 'text', 'body' => bard(nestedLong())]]]]];
    $print = nestedReport($values)['findings'][0]['fingerprint'];

    $report = nestedReport([...$values, Fingerprint::FIELD => [$print]]);

    expect(longIn($report))->toBe([])->and($report['summary']['ignored'])->toBe(1);
});

it('puts the panel on a form whose only configured field is a Grid of prose', function () {
    config()->set('copydesk.fields', ['*' => ['sections']]);
    app()->forgetInstance(Settings::class);
    $blueprint = nestedBlueprint();

    EntryBlueprintFound::dispatch($blueprint);

    expect($blueprint->field('readability_panel')?->get('prose_fields'))->toBe(['sections']);
});

it('leaves a Grid with no prose in it unread', function () {
    $blueprint = Blueprint::make('plain')->setNamespace('collections.pages')->setContents(['fields' => [
        ['handle' => 'sections', 'field' => ['type' => 'grid', 'fields' => [['handle' => 'label', 'field' => ['type' => 'text']]]]],
    ]]);

    $report = app(Analyzer::class)->analyze($blueprint, ['sections' => [['label' => nestedLong()]]], 'en_US')->toArray();

    expect($report['fields'])->toBe([])->and($report['findings'])->toBe([]);
});
