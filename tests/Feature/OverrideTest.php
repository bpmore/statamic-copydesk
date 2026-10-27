<?php

declare(strict_types=1);

use Bpmore\Copydesk\Edition;
use Bpmore\Copydesk\Gate\OverrideLog;
use Bpmore\Copydesk\Gate\OverridePasses;
use Bpmore\Copydesk\Gate\PublishGate;
use Illuminate\Validation\ValidationException;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\User;

/**
 * "Publish anyway" (Phase 1.7, acceptance 4): a person allowed to says
 * why, and their next publish of the page goes through, once; the reason
 * is kept for the site report.
 */
beforeEach(function () {
    pro();
    config()->set('statamic.editions.pro', true);
    config()->set('copydesk.gate', ['enabled' => true, 'collections' => ['pages']]);
    Collection::make('pages')->save();

    $this->setTestRoles([
        'editor' => ['access cp', 'view pages entries', 'edit pages entries', 'create pages entries', 'publish pages entries', 'view readability', PublishGate::OVERRIDE],
        'writer' => ['access cp', 'view pages entries', 'edit pages entries', 'create pages entries', 'publish pages entries', 'view readability'],
    ]);

    $this->editor = User::make()->email('editor@example.test')->set('name', 'Edie Tor')->assignRole('editor');
    $this->editor->save();
    $this->writer = User::make()->email('writer@example.test')->assignRole('writer');
    $this->writer->save();

    $this->entry = Entry::make()->collection('pages')->slug('long')->published(false)
        ->data(['title' => 'Long', 'content' => bard('Word '.implode(' ', array_fill(0, 59, 'word')).'.')]);
    $this->entry->save();
});

function publishAs($user, $entry): ?array
{
    test()->actingAs($user);

    try {
        $entry->published(true)->save();
    } catch (ValidationException $e) {
        return $e->errors()['copydesk'] ?? null;
    }

    return null;
}

function askToOverride($user, array $payload)
{
    return test()->actingAs($user)->postJson(cp_route('copydesk.override'), $payload);
}

it('lets someone allowed publish once, with a reason, and keeps the reason (acceptance 4)', function () {
    expect(publishAs($this->editor, $this->entry))->not->toBeNull();

    askToOverride($this->editor, ['reference' => $this->entry->reference(), 'reason' => 'Legal wrote this sentence word for word.'])
        ->assertOk()
        ->assertJsonPath('message', 'Press Publish again within 10 minutes. Your reason is kept with the page.');

    expect(publishAs($this->editor, $this->entry))->toBeNull();

    $logged = app(OverrideLog::class)->all()[0];
    expect($logged)->toMatchArray([
        'entry' => $this->entry->id(),
        'title' => 'Long',
        'collection' => 'pages',
        'user_name' => 'Edie Tor',
        'reason' => 'Legal wrote this sentence word for word.',
        'rules' => ['very_long_sentence'],
    ]);

    // Once: the next publish is checked again.
    expect(publishAs($this->editor, $this->entry))->not->toBeNull();

    // And the reason is in the site report.
    $admin = User::make()->email('admin@example.test')->makeSuper();
    $admin->save();

    test()->actingAs($admin)->get(cp_route('utilities.copydesk'))
        ->assertOk()
        ->assertSee('Published past the gate')
        ->assertSee('Legal wrote this sentence word for word.')
        ->assertSee('Very long sentence');
});

it('does not spend the pass on a publish that never saved', function () {
    app(OverridePasses::class)->grant((string) $this->editor->id(), $this->entry->id(), 'pages', 'Legal wrote this sentence.');
    test()->actingAs($this->editor);

    $gate = app(PublishGate::class);
    $published = $this->entry->published(true);

    // Copydesk lets it through; another gate refuses; no EntrySaved.
    expect($gate->inspect($published, $this->editor)->shouldRefuse())->toBeFalse()
        ->and($gate->inspect($published, $this->editor)->shouldRefuse())->toBeFalse()
        ->and(app(OverrideLog::class)->all())->toBe([]);
});

it('refuses the pass to someone without the permission, and ignores one they somehow hold', function () {
    askToOverride($this->writer, ['reference' => $this->entry->reference(), 'reason' => 'I would like to publish this.'])->assertForbidden();

    app(OverridePasses::class)->grant((string) $this->writer->id(), $this->entry->id(), 'pages', 'Slipped in somehow, never mind.');

    expect(publishAs($this->writer, $this->entry))->not->toBeNull();
});

it('asks for a reason that says something', function () {
    askToOverride($this->editor, ['reference' => $this->entry->reference(), 'reason' => 'ok'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Say why this page should publish as it is, in a few words.');
});

it('gives a pass for a page nobody has saved yet, by its collection', function () {
    askToOverride($this->editor, ['collection' => 'pages', 'reason' => 'A quote we must keep as it is.'])->assertOk();

    $new = Entry::make()->collection('pages')->slug('new')->data(['title' => 'New', 'content' => bard('Word '.implode(' ', array_fill(0, 59, 'word')).'.')]);

    expect(publishAs($this->editor, $new))->toBeNull();
});

it('is part of Pro', function () {
    config()->set('statamic.editions.addons.'.Edition::PACKAGE, 'free');

    askToOverride($this->editor, ['reference' => $this->entry->reference(), 'reason' => 'Legal wrote this sentence.'])->assertForbidden();
});
