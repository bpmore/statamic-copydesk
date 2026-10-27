<?php

declare(strict_types=1);

use Statamic\Events\EntryBlueprintFound;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;

beforeEach(function () {
    Collection::make('pages')->save();
    Collection::make('links')->save();
});

it('puts the panel in the sidebar of a form that has something to read, stamped with its collection and form', function () {
    $blueprint = Collection::findByHandle('pages')->entryBlueprint();
    $field = $blueprint->field('readability_panel');

    expect($field)->not->toBeNull();
    expect($field->type())->toBe('readability_panel');
    expect($field->get('collection'))->toBe('pages');
    expect($field->get('blueprint'))->toBe('pages');
    expect($blueprint->tabs()->get('sidebar')?->fields()->has('readability_panel'))->toBeTrue();
});

it('tells the editor which of the configured fields are on the form', function () {
    config()->set('copydesk.fields', ['*' => ['content', 'sections', 'missing']]);
    // Not saved: a saved blueprint would carry into the other tests.
    $blueprint = Blueprint::make('article')->setNamespace('collections.pages')->setContents(['fields' => [
        ['handle' => 'content', 'field' => ['type' => 'bard']],
        ['handle' => 'sections', 'field' => ['type' => 'grid', 'fields' => []]],
    ]]);

    EntryBlueprintFound::dispatch($blueprint);

    expect($blueprint->field('readability_panel')->get('prose_fields'))->toBe(['content', 'sections']);
});

it('leaves a form alone when none of the configured fields is on it', function () {
    config()->set('copydesk.fields', ['*' => ['body']]);

    expect(Collection::findByHandle('pages')->entryBlueprint()->field('readability_panel'))->toBeNull();
});

it('leaves every form alone when the addon is off', function () {
    config()->set('copydesk.enabled', false);

    expect(Collection::findByHandle('pages')->entryBlueprint()->field('readability_panel'))->toBeNull();
});

it('does not touch blueprints that are not for entries', function () {
    Blueprint::make('user')->setNamespace('users')->setContents(['fields' => [['handle' => 'content', 'field' => ['type' => 'bard']]]])->save();

    expect(Blueprint::find('users.user')?->field('readability_panel'))->toBeNull();
});
