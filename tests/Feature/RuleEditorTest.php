<?php

declare(strict_types=1);

use Bpmore\Copydesk\Analysis\HouseRules;
use Bpmore\Copydesk\Edition;
use Bpmore\Copydesk\Http\Controllers\PacksController;
use Bpmore\Copydesk\Packs\PackForm;
use Illuminate\Support\Facades\File;
use Statamic\Facades\User;
use Symfony\Component\Yaml\Yaml;

/**
 * The rule editor (Phase 1.9, Pro): a site's own packs in a form, written
 * back as YAML that Copydesk reads.
 */
beforeEach(function () {
    pro();
    config()->set('statamic.editions.pro', true);
    File::ensureDirectoryExists(resource_path('copydesk'));
    $this->setTestRoles(['styler' => ['access cp', PacksController::PERMISSION], 'writer' => ['access cp']]);
    $this->styler = User::make()->email('styler@example.test')->assignRole('styler');
    $this->styler->save();
});

afterEach(function () {
    File::deleteDirectory(resource_path('copydesk'));
});

/** The pack from docs/packs.md, every kind of rule in it. */
function editorPack(): array
{
    return [
        'description' => 'Our house style.',
        'extends' => ['core-punctuation', 'plain-language'],
        'disable' => ['no-em-dash', 'house-passive'],
        'rules' => [
            ['id' => 'house-names', 'type' => 'term', 'message' => 'Spell our names the way we do.', 'terms' => ['Greenhouse Institute', 'MyChart'], 'severity' => 'error'],
            ['id' => 'house-words', 'type' => 'word_list', 'message' => 'We say it this way.', 'words' => [
                'utilize' => 'use',
                'please be advised that' => null,
                'patient portal' => ['replace' => ['MyChart'], 'reason' => 'Patients know it by name.', 'severity' => 'warning'],
                'commence' => ['begin', 'start'],
            ]],
            ['id' => 'house-percent', 'type' => 'pattern', 'message' => 'Write percent.', 'pattern' => '\d%', 'flags' => 'i', 'severity' => 'info', 'scope' => ['collections' => ['news'], 'fields' => ['content']]],
            ['id' => 'house-quotes', 'type' => 'punctuation', 'message' => 'Curly quotes.', 'checks' => ['quotes'], 'quotes' => 'curly', 'exceptions' => ['/code/']],
            ['id' => 'house-headings', 'type' => 'casing', 'message' => 'Sentence case.', 'style' => 'sentence', 'keep' => ['Statamic']],
            ['id' => 'house-short', 'type' => 'sentence', 'message' => 'Shorter.', 'check' => 'max_words', 'max_words' => 20, 'suggestions' => ['Split it.']],
            ['id' => 'house-passive', 'type' => 'sentence', 'message' => 'Say who does it.', 'check' => 'passive'],
        ],
        'collections' => ['patient_education' => ['disable' => ['house-short']]],
    ];
}

it('turns a pack into the form and back without losing anything', function () {
    $pack = editorPack();
    $values = PackForm::toValues($pack);

    // The pack's own rule that it turns off is switched off in the list.
    expect(collect($values['rules'])->firstWhere('rule_id', 'house-passive')['enabled'])->toBeFalse()
        ->and($values['disable'])->toBe(['no-em-dash']);

    expect(PackForm::fromValues($values, $pack))->toEqual($pack);
});

it('opens a site pack in Statamic\'s own form, and refuses a pack Copydesk ships', function () {
    File::put(resource_path('copydesk/house.yaml'), Yaml::dump(editorPack(), 8, 2));

    $form = $this->actingAs($this->styler)->getJson(cp_route('copydesk.packs.edit', 'house'))->assertOk()->json();

    expect($form['title'])->toBe('House style: house')
        ->and(collect($form['values']['rules'])->pluck('rule_id')->all())->toContain('house-names', 'house-words');

    $this->actingAs($this->styler)->getJson(cp_route('copydesk.packs.edit', 'core-punctuation'))->assertNotFound();
});

it('saves the form as YAML Copydesk then reads and runs', function () {
    File::put(resource_path('copydesk/house.yaml'), Yaml::dump(['rules' => []], 8, 2));
    config()->set('copydesk.packs', ['house']);

    $this->actingAs($this->styler)->patchJson(cp_route('copydesk.packs.update', 'house'), [
        'description' => 'Ours.',
        'extends' => [],
        'disable' => [],
        'rules' => [[
            'id' => 'row1', 'type' => 'term', 'enabled' => true,
            'rule_id' => 'house-names', 'message' => 'Spell our name our way.', 'severity' => 'error', 'terms' => ['eiSEO'],
        ]],
    ])->assertOk();

    expect(Yaml::parseFile(resource_path('copydesk/house.yaml')))->toBe([
        'description' => 'Ours.',
        'rules' => [['id' => 'house-names', 'type' => 'term', 'message' => 'Spell our name our way.', 'terms' => ['eiSEO'], 'severity' => 'error']],
    ]);

    $rules = app(HouseRules::class)->for(null, 'default');
    expect(collect($rules)->map(fn ($r) => $r->handle())->all())->toContain('house-names');
});

it('refuses to save a rule Copydesk could not read, and says why', function () {
    File::put(resource_path('copydesk/house.yaml'), Yaml::dump(['rules' => []], 8, 2));

    $this->actingAs($this->styler)->patchJson(cp_route('copydesk.packs.update', 'house'), [
        'rules' => [['id' => 'row1', 'type' => 'pattern', 'enabled' => true, 'rule_id' => 'bad-pattern', 'message' => 'm', 'pattern' => '(unclosed']],
    ])->assertStatus(422)->assertJsonPath('errors.rules.0', fn (string $m) => str_contains($m, 'resources/copydesk/house.yaml') && str_contains($m, 'bad-pattern'));

    expect(Yaml::parseFile(resource_path('copydesk/house.yaml')))->toBe(['rules' => []]);
});

it('tries the unsaved rules on sample text', function () {
    File::put(resource_path('copydesk/house.yaml'), Yaml::dump(['rules' => []], 8, 2));

    $found = $this->actingAs($this->styler)->postJson(cp_route('copydesk.packs.try', 'house'), [
        'values' => ['rules' => [['type' => 'term', 'enabled' => true, 'rule_id' => 'house-names', 'message' => 'Spell it eiSEO.', 'terms' => ['eiSEO']]]],
        'text' => 'We use Eiseo daily.',
    ])->assertOk()->json('findings');

    expect($found)->toHaveCount(1)
        ->and($found[0])->toMatchArray(['rule' => 'house-names', 'excerpt' => 'Eiseo']);
});

it('starts a new pack, and refuses a name that is taken or badly formed', function () {
    $this->actingAs($this->styler)->post(cp_route('copydesk.packs.store'), ['name' => 'house'])
        ->assertRedirect(cp_route('copydesk.packs.edit', 'house'));
    expect(Yaml::parseFile(resource_path('copydesk/house.yaml'))['extends'])->toBe(['core-punctuation']);

    $this->actingAs($this->styler)->postJson(cp_route('copydesk.packs.store'), ['name' => 'house'])->assertStatus(422);
    $this->actingAs($this->styler)->postJson(cp_route('copydesk.packs.store'), ['name' => 'plain-language'])->assertStatus(422);
    $this->actingAs($this->styler)->postJson(cp_route('copydesk.packs.store'), ['name' => 'Bad Name'])->assertStatus(422);
});

it('needs the permission, and Pro', function () {
    File::put(resource_path('copydesk/house.yaml'), Yaml::dump(['rules' => []], 8, 2));
    $writer = User::make()->email('writer@example.test')->assignRole('writer');
    $writer->save();

    $this->actingAs($writer)->getJson(cp_route('copydesk.packs.edit', 'house'))->assertForbidden();

    config()->set('statamic.editions.addons.'.Edition::PACKAGE, 'free');
    $this->actingAs($this->styler)->getJson(cp_route('copydesk.packs.edit', 'house'))->assertForbidden();
});
