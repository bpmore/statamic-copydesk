<?php

declare(strict_types=1);

use Bpmore\Copydesk\Gate\A11yGateBridge;
use Bpmore\Copydesk\Gate\CopydeskGateContributor;
use Bpmore\Copydesk\Gate\OverridePasses;
use Bpmore\Copydesk\Gate\PublishGate;
use Bpmore\Copydesk\Settings;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\User;

require_once __DIR__.'/../Stubs/A11yGateContract.php';

/**
 * Copydesk inside A11y Gate's publish check (Phase 1.7, task 3): A11y Gate
 * asks, Copydesk answers, and Copydesk's own listener stands aside so the
 * author is refused once.
 */
beforeEach(function () {
    pro();
    config()->set('copydesk.gate', ['enabled' => true, 'collections' => ['pages']]);
    Collection::make('pages')->save();
    A11yGateBridge::join();
});

afterEach(function () {
    A11yGateBridge::leave();
});

function longPage(bool $published = true)
{
    return Entry::make()->collection('pages')->slug('long')->published($published)
        ->data(['title' => 'Long', 'content' => bard('Word '.implode(' ', array_fill(0, 59, 'word')).'.')]);
}

it('answers A11y Gate with the same lines it would refuse with itself', function () {
    $answer = app(CopydeskGateContributor::class)->inspect(longPage());

    expect($answer->blocks)->toBeTrue()
        ->and($answer->lines[0])->toBe('This page was not published. One thing has to be fixed first.')
        ->and($answer->lines[1])->toStartWith('This sentence runs to 60 words')
        ->and(app(CopydeskGateContributor::class)->key())->toBe('copydesk');
});

it('stands its own listener aside, so the author is refused once', function () {
    // No A11y Gate listener runs in this test, so nothing refuses at all:
    // which is how we know Copydesk's own listener did not.
    $entry = longPage();

    expect(fn () => $entry->save())->not->toThrow(ValidationException::class);
});

it('keeps its own rules inside A11y Gate: drafts pass, a broken check passes and is logged', function () {
    expect(app(CopydeskGateContributor::class)->inspect(longPage(published: false))->blocks)->toBeFalse();

    config()->set('copydesk.rules', ['no_such_rule' => []]);
    app()->forgetInstance(Settings::class);
    app()->forgetInstance(PublishGate::class);
    Log::shouldReceive('error')->once()->withArgs(fn (string $message) => str_contains($message, 'saved unchecked'));

    expect(app(CopydeskGateContributor::class)->inspect(longPage())->blocks)->toBeFalse();
});

it('honours a pass from Publish anyway', function () {
    config()->set('statamic.editions.pro', true);
    $this->setTestRoles(['editor' => ['access cp', PublishGate::OVERRIDE]]);
    $editor = User::make()->email('editor@example.test')->assignRole('editor');
    $editor->save();
    $this->actingAs($editor);

    app(OverridePasses::class)->grant((string) $editor->id(), null, 'pages', 'Legal wrote it this way.');

    expect(app(CopydeskGateContributor::class)->inspect(longPage())->blocks)->toBeFalse();
});

it('gates on its own when A11y Gate has not signed it up', function () {
    A11yGateBridge::leave();

    expect(fn () => longPage()->save())->toThrow(ValidationException::class);
});
