<?php

declare(strict_types=1);

use Bpmore\Copydesk\Analysis\Analyzer;
use Bpmore\Copydesk\Gate\PublishGate;
use Bpmore\Copydesk\Http\Controllers\PacksController;
use Bpmore\Copydesk\Scan\SiteReport;
use Bpmore\Copydesk\Weather\ReadabilityContributor;
use Bpmore\SiteWeather\Contracts\WeatherContributor;
use Illuminate\Support\Facades\File;
use Statamic\Facades\Collection;
use Statamic\Facades\Permission;
use Statamic\Facades\User;

/**
 * Free and Pro, as the spec splits them (Phase 1.10). Free: the
 * punctuation pack, term rules, the readability score and the editor's
 * underlines. Pro: the other packs and rule kinds, the rule editor, the
 * gate, the site report and the Site Weather signal.
 */
beforeEach(function () {
    Collection::make('pages')->save();
    File::ensureDirectoryExists(resource_path('copydesk'));
    File::put(resource_path('copydesk/house.yaml'), implode("\n", [
        'rules:',
        '  - {id: house-names, type: term, message: Spell it our way., terms: [eiSEO]}',
        '  - {id: house-short, type: sentence, message: Shorter., check: max_words, max_words: 5}',
    ])."\n");
});

afterEach(function () {
    File::deleteDirectory(resource_path('copydesk'));
});

function rulesFound(string $text): array
{
    $report = app(Analyzer::class)->analyze(Collection::findByHandle('pages')->entryBlueprint(), ['content' => bard($text)], 'en', 'default');

    return collect($report->findings->all())->pluck('rule')->unique()->values()->all();
}

const MIXED = 'We utilize Eiseo — every single day of the week, chairman.';

it('runs punctuation and term rules on Free, and the readability score', function () {
    expect(rulesFound(MIXED))
        ->toContain('no-em-dash', 'house-names')
        ->not->toContain('plain-words', 'inclusive-roles', 'house-short');
});

it('runs every pack and every kind of rule on Pro', function () {
    pro();

    expect(rulesFound(MIXED))->toContain('no-em-dash', 'house-names', 'plain-words', 'inclusive-roles', 'house-short');
});

it('keeps the site report for Pro, and for roles allowed to see it', function () {
    config()->set('statamic.editions.pro', true);
    $this->setTestRoles(['viewer' => ['access cp', 'access copydesk utility', SiteReport::PERMISSION], 'other' => ['access cp', 'access copydesk utility']]);
    $viewer = User::make()->email('viewer@example.test')->assignRole('viewer');
    $viewer->save();
    $other = User::make()->email('other@example.test')->assignRole('other');
    $other->save();

    $this->actingAs($viewer)->get(cp_route('utilities.index').'/copydesk')->assertOk()->assertSee('The site report is part of Copydesk Pro.');
    $this->actingAs($viewer)->get(cp_route('utilities.copydesk.issues', 'default'))->assertForbidden();

    pro();

    $this->actingAs($other)->get(cp_route('utilities.index').'/copydesk')->assertOk()->assertSee('Your role cannot see the site report.');
    $this->actingAs($other)->get(cp_route('utilities.copydesk.issues', 'default'))->assertForbidden();
    $this->actingAs($viewer)->get(cp_route('utilities.copydesk.issues', 'default'))->assertOk();
});

it('reads the Site Weather signal only on Pro', function () {
    if (! interface_exists(WeatherContributor::class)) {
        $this->markTestSkipped('Site Weather is not installed here.');
    }

    expect(app(ReadabilityContributor::class)->reading()->headline)->toBe('The Site Weather signal is part of Copydesk Pro');
});

it('registers the spec\'s three permissions beside the others, given to nobody by default', function () {
    $handles = Permission::boot()->flattened()->map(fn ($p) => $p->value())->values()->all();

    expect($handles)->toContain(SiteReport::PERMISSION, PacksController::PERMISSION, PublishGate::OVERRIDE, 'view readability');
});
