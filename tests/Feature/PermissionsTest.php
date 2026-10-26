<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Statamic\Facades\Addon;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Permission;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Widgets\Loader;

/**
 * Three permissions with three meanings, beside the two Statamic gives
 * every addon.
 */
beforeEach(function () {
    Site::setSites(['default' => ['name' => 'Main', 'locale' => 'en_US', 'url' => '/']]);
    Collection::make('pages')->save();

    // More than one user in a test is a Pro feature.
    config()->set('statamic.editions.pro', true);

    $this->setTestRoles([
        'checker' => ['access cp', 'view pages entries', 'access copydesk utility', 'view readability'],
        'lexicographer' => ['access cp', 'access copydesk utility', 'manage jargon dictionary'],
        'admin' => ['access cp', 'configure readability'],
        'reader' => ['access cp', 'view pages entries'],
    ]);
});

const PLAIN_PAGE = 'We help you. Call us today.';

function userWithRole(string $role): Statamic\Contracts\Auth\User
{
    $user = User::make()->email("$role@example.test")->assignRole($role);
    $user->save();

    return $user;
}

it('registers the three under their own group, in plain words', function () {
    $group = Permission::boot()->flattened()->filter(fn ($p) => str_contains($p->value(), 'readability') || str_contains($p->value(), 'jargon'));

    expect($group->map->value()->values()->all())->toBe(['view readability', 'manage jargon dictionary', 'configure readability']);
    expect($group->map->label()->values()->all())->toBe(['Check readability', 'Manage the dictionary', 'Configure readability']);
});

it('lets only a user who may view readability check a page, and tells the panel so', function () {
    $entry = Entry::make()->collection('pages')->slug('p')->data(['title' => 'P', 'content' => bard(PLAIN_PAGE)]);
    $entry->save();
    $payload = ['reference' => $entry->reference(), 'values' => ['content' => bard(PLAIN_PAGE)]];

    $this->actingAs(userWithRole('checker'))->postJson(cp_route('copydesk.check'), $payload)->assertOk();
    $this->actingAs(userWithRole('reader'))->postJson(cp_route('copydesk.check'), $payload)
        ->assertStatus(403)->assertJsonPath('message', 'You do not have permission to check readability.');

    $panel = Collection::findByHandle('pages')->entryBlueprint()->field('readability_panel')->fieldtype();
    $this->actingAs(userWithRole('checker'));
    expect($panel->preload())->toBe(['can' => true]);
    $this->actingAs(userWithRole('reader'));
    expect($panel->preload())->toBe(['can' => false]);
});

it('shows the widget only to a user who may view readability', function () {
    $html = fn () => (string) app(Loader::class)->load('readability', ['type' => 'readability'])->html();

    $this->actingAs(userWithRole('reader'));
    expect($html())->toContain('not something your role can do');

    $this->actingAs(userWithRole('checker'));
    expect($html())->toContain('No scan has run yet');
});

it('lets only a user who may manage the dictionary import and export it, and offers the forms only to them', function () {
    $this->actingAs(userWithRole('lexicographer'))->get(cp_route('utilities.copydesk.export', 'default'))->assertOk();
    $this->actingAs(userWithRole('checker'))->get(cp_route('utilities.copydesk.export', 'default'))
        ->assertStatus(403);

    expect(utilityText($this->actingAs(userWithRole('lexicographer'))->get(cp_route('utilities.index').'/copydesk')->assertOk()->getContent()))
        ->toContain("Export this site's entries")->not->toContain('not something your role can do');
    expect(utilityText($this->actingAs(userWithRole('checker'))->get(cp_route('utilities.index').'/copydesk')->assertOk()->getContent()))
        ->toContain('Importing and exporting the dictionary is not something your role can do')->not->toContain("Export this site's entries");
});

it('opens the settings screen to whoever may configure readability or manage the dictionary, in this addon\'s own words', function () {
    $addon = Addon::get('bpmore/statamic-copydesk');

    $this->actingAs(userWithRole('admin'));
    expect(Gate::allows('editSettings', $addon))->toBeTrue();

    $this->actingAs(userWithRole('lexicographer'));
    expect(Gate::allows('editSettings', $addon))->toBeTrue();

    $this->actingAs(userWithRole('checker'));
    expect(Gate::allows('editSettings', $addon))->toBeFalse();
    // Statamic sends an unauthorized page view back to the dashboard with the reason.
    $this->actingAs(userWithRole('checker'))->get($addon->settingsUrl())
        ->assertRedirect(cp_route('index'))->assertSessionHas('error', 'This action is unauthorized.');

    $super = User::make()->makeSuper();
    $super->save();
    $this->actingAs($super);
    expect(Gate::allows('editSettings', $addon))->toBeTrue();
});
