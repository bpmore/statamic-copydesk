<?php

declare(strict_types=1);

use Bpmore\Plain\Companion;
use Bpmore\Plain\Dictionaries;
use Bpmore\Plain\Scan\SiteScan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Statamic\Contracts\Addons\SettingsRepository;
use Statamic\Facades\Addon;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\User;

/**
 * The sticky feature: a site's own words to avoid, edited on the settings
 * screen, per site, in and out as a file.
 */
beforeEach(function () {
    // A saved settings file is shared state; every test starts from nobody
    // having opened the settings screen.
    File::delete(resource_path('addons/statamic-plain.yaml'));

    Site::setSites([
        'default' => ['name' => 'Main', 'locale' => 'en_US', 'url' => '/'],
        'clinic' => ['name' => 'Clinic', 'locale' => 'en_US', 'url' => '/clinic/'],
    ]);

    $this->user = User::make()->makeSuper();
    $this->user->save();
});

/** Rows as the settings screen saves them. */
function saveRows(array $rows): void
{
    $settings = Addon::get('bpmore/statamic-plain')->settings();
    $settings->set('dictionary', $rows);
    app(SettingsRepository::class)->save($settings);
}

// ---- the settings screen ------------------------------------------------------

it('offers a settings screen with the dictionary on it', function () {
    $addon = Addon::get('bpmore/statamic-plain');

    expect($addon->hasSettingsBlueprint())->toBeTrue();
    expect($addon->settingsUrl())->toContain('addons');

    $field = $addon->settingsBlueprint()->field('dictionary');
    expect($field->type())->toBe('grid');
    expect(array_column($field->get('fields'), 'handle'))->toBe(['term', 'say', 'note', 'sites']);
});

it('asks in plain words, with no handles or file paths', function () {
    $blueprint = Addon::get('bpmore/statamic-plain')->settingsBlueprint();
    $text = collect($blueprint->fields()->all())->flatMap(fn ($f) => [$f->display(), $f->instructions()])->filter()->implode(' ');
    $text .= ' '.collect($blueprint->tabs()->all())->flatMap(fn ($t) => $t->sections()->all())->map(fn ($s) => $s->instructions())->filter()->implode(' ');

    foreach (['config/', 'blueprint', 'handle', '.env', 'yaml', 'json'] as $jargon) {
        expect(str_contains(strtolower($text), $jargon))->toBeFalse("the settings screen must not say '{$jargon}' to somebody who was handed a site");
    }

    expect($text)->toContain('what to say instead');
});

// ---- the dictionaries -----------------------------------------------------------

it('lays the site\'s own entries over the starter: rows for every site, then rows for this site', function () {
    saveRows([
        ['term' => 'utilize', 'say' => ['employ'], 'note' => '', 'sites' => []],                 // every site, overriding the starter
        ['term' => 'HbA1c', 'say' => ['A1C'], 'note' => 'Patients know A1C.', 'sites' => ['clinic']],
        ['term' => 'utilize', 'say' => ['make use of'], 'note' => '', 'sites' => ['clinic']],     // this site, overriding the row above
        ['term' => 'ASAP', 'say' => [], 'note' => '', 'sites' => ['default']],
        ['term' => '  ', 'say' => ['ignored'], 'note' => '', 'sites' => []],
    ]);

    $dictionaries = app(Dictionaries::class);

    $main = $dictionaries->forSite('default');
    expect($main->get('utilize')->replacements)->toBe(['employ']);
    expect($main->get('asap')->isBanned())->toBeTrue();
    expect($main->has('hba1c'))->toBeFalse();
    expect($main->get('prior to')->replacements)->toBe(['before']);   // the starter, still there

    $clinic = $dictionaries->forSite('clinic');
    expect($clinic->get('utilize')->replacements)->toBe(['make use of']);
    expect($clinic->get('hba1c')->note)->toBe('Patients know A1C.');
    expect($clinic->has('asap'))->toBeFalse();

    expect(count($dictionaries->own('clinic')))->toBe(2);
    expect(count($dictionaries->forSite('clinic')))->toBe(count($dictionaries->starter()) + 1);   // utilize replaces, HbA1c adds
});

it('exports a site\'s own entries in the shape a file holds, and nothing from the starter', function () {
    saveRows([
        ['term' => 'HbA1c', 'say' => ['A1C'], 'note' => 'Patients know A1C.', 'sites' => ['clinic']],
        ['term' => 'ASAP', 'say' => [], 'note' => '', 'sites' => []],
    ]);

    expect(app(Dictionaries::class)->export('clinic'))->toBe([
        'ASAP' => [],
        'HbA1c' => ['replacements' => ['A1C'], 'note' => 'Patients know A1C.'],
    ]);
    expect(app(Dictionaries::class)->export('default'))->toBe(['ASAP' => []]);
});

it('imports a file into a site, merging or replacing, and leaves rows for every site alone', function () {
    saveRows([
        ['term' => 'ASAP', 'say' => [], 'note' => '', 'sites' => []],
        ['term' => 'utilize', 'say' => ['employ'], 'note' => '', 'sites' => ['clinic']],
        ['term' => 'old', 'say' => ['former'], 'note' => '', 'sites' => ['clinic']],
    ]);
    $dictionaries = app(Dictionaries::class);

    $count = $dictionaries->import('clinic', ['utilize' => 'use', 'HbA1c' => ['replacements' => ['A1C'], 'note' => 'Say A1C.']], replace: false);

    expect($count)->toBe(3);
    $own = $dictionaries->own('clinic');
    expect($own->get('utilize')->replacements)->toBe(['use']);   // the file won
    expect($own->has('old'))->toBeTrue();
    expect($own->has('asap'))->toBeTrue();                       // the every-site row, untouched

    $count = $dictionaries->import('clinic', ['fresh' => 'new'], replace: true);

    expect($count)->toBe(1);
    expect(array_keys($dictionaries->export('clinic')))->toBe(['ASAP', 'fresh']);
    expect($dictionaries->export('default'))->toBe(['ASAP' => []]);

    // Round trip: what came out goes back in unchanged.
    expect(Dictionaries::class)->toBeString();
    $exported = $dictionaries->export('clinic');
    $dictionaries->import('clinic', $exported, replace: true);
    expect($dictionaries->export('clinic'))->toBe($exported);
});

// ---- the check uses it ----------------------------------------------------------

it('finds a site\'s own term on the publish form, and flags it as banned when it is', function () {
    Collection::make('pages')->save();
    saveRows([['term' => 'HbA1c', 'say' => [], 'note' => 'Say A1C.', 'sites' => ['default']]]);
    $entry = Entry::make()->collection('pages')->slug('p')->data(['title' => 'P', 'content' => bard('Fine.')]);
    $entry->save();

    $response = $this->actingAs($this->user)->postJson(cp_route('plain.check'), [
        'reference' => $entry->reference(),
        'values' => ['content' => bard('Ask about your HbA1c.')],
    ]);

    $finding = collect($response->json('findings'))->where('rule', 'jargon')->first();
    expect($finding['excerpt'])->toBe('HbA1c');
    expect($finding['suggestion'])->toBeNull();
    expect($finding['message'])->toContain('list of words to avoid')->toContain('Say A1C.');
});

// ---- the page under Tools --------------------------------------------------------

it('is reachable under Tools, with an export and an import for every site', function () {
    saveRows([['term' => 'HbA1c', 'say' => ['A1C'], 'note' => '', 'sites' => ['clinic']]]);

    $response = $this->actingAs($this->user)->get(cp_route('utilities.index').'/plain');

    $response->assertOk();
    $text = utilityText($response->getContent());
    expect($text)->toContain('Main')->toContain('Clinic');
    expect($text)->toContain('/cp/utilities/plain/export/default')->toContain('/cp/utilities/plain/import/clinic');
    expect($text)->toContain('0 entries of its own')->toContain('1 entry of its own');
});

it('exports a site\'s entries as a JSON file', function () {
    saveRows([['term' => 'HbA1c', 'say' => ['A1C'], 'note' => '', 'sites' => ['clinic']]]);

    $response = $this->actingAs($this->user)->get(cp_route('utilities.plain.export', 'clinic'));

    $response->assertOk()->assertHeader('Content-Disposition', 'attachment; filename="plain-dictionary-clinic.json"');
    expect($response->json())->toBe(['HbA1c' => ['A1C']]);

    $this->actingAs($this->user)->get(cp_route('utilities.plain.export', 'nope'))->assertStatus(404);
});

it('imports a JSON file into a site and says how many entries it has now', function () {
    $file = UploadedFile::fake()->createWithContent('words.json', json_encode(['utilize' => 'use', 'ASAP' => []]));

    $response = $this->actingAs($this->user)->post(cp_route('utilities.plain.import', 'clinic'), ['file' => $file, 'mode' => 'merge']);

    $response->assertSessionHas('success', 'The dictionary now has 2 entries of its own.');
    expect(app(Dictionaries::class)->export('clinic'))->toBe(['utilize' => ['use'], 'ASAP' => []]);
});

it('refuses a file that is not a dictionary, in a sentence', function () {
    $file = UploadedFile::fake()->createWithContent('words.json', '["just", "a", "list"]');

    $this->actingAs($this->user)
        ->from(cp_route('utilities.index').'/plain')
        ->post(cp_route('utilities.plain.import', 'clinic'), ['file' => $file, 'mode' => 'merge'])
        ->assertSessionHasErrors(['file' => 'That file is not a dictionary. It should be JSON with a term on the left and what to say instead on the right, the way an exported one is.']);

    expect(app(Dictionaries::class)->export('clinic'))->toBe([]);
});

it('ships a utility view Vue can compile', function () {
    $scan = app(SiteScan::class)->run('default');
    $data = ['site' => Site::get('default'), 'scan' => $scan, 'sites' => Site::all()->values(), 'counts' => ['default' => 0, 'clinic' => 1]];

    // With the import and export forms, and with the sentence in their place.
    assertVueTemplateIsWellFormed(view('plain::utilities.readability', $data + ['canManage' => true, 'companion' => null])->render());
    assertVueTemplateIsWellFormed(view('plain::utilities.readability', $data + ['canManage' => false, 'companion' => Companion::notice()])->render());
});
