<?php

declare(strict_types=1);

use Bpmore\Copydesk\Settings;
use Bpmore\ReadabilityCore\Extraction\PlainTextExtractor;
use Bpmore\ReadabilityCore\Formula\Grades;
use Bpmore\ReadabilityCore\Formula\Tally;
use Bpmore\ReadabilityCore\Text\EnglishSegmenter;
use Bpmore\ReadabilityCore\Text\EnglishSyllableCounter;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\User;

/**
 * Phase 1.4: one check, readability and house rules together.
 */
beforeEach(function () {
    // Pro: these tests use the packs, the site report and the signal Free leaves out.
    pro();
    Collection::make('pages')->save();
    $this->user = User::make()->makeSuper();
    $this->user->save();
    File::deleteDirectory(resource_path('copydesk'));
});

afterEach(fn () => File::deleteDirectory(resource_path('copydesk')));

/** @param  array<string, mixed>  $values */
function analyse(array $values): TestResponse
{
    $entry = Entry::make()->collection('pages')->slug('page')->data(['title' => 'Page', 'content' => bard('Fine.')]);
    $entry->save();

    return test()->actingAs(test()->user)->postJson(cp_route('copydesk.check'), ['reference' => $entry->reference(), 'values' => $values])->assertOk();
}

function sitePack(string $name, string $yaml): void
{
    File::ensureDirectoryExists(resource_path('copydesk'));
    File::put(resource_path("copydesk/{$name}.yaml"), $yaml);
}

it('runs the default packs beside the readability rules, and reports a starter word once', function () {
    $findings = collect(analyse(['content' => bard('We utilize forms — often.')])->json('findings'));

    expect($findings->pluck('rule')->all())->toBe(['plain-words', 'no-em-dash'])
        ->and($findings->where('rule', 'jargon'))->toBeEmpty();
});

it('gives a house punctuation finding its place in the field as written', function () {
    $written = "Call  us \u{201C}today\u{201D}.";
    $finding = collect(analyse(['content' => bard($written)])->json('findings'))->firstWhere('rule', 'no-double-space');

    expect($finding['written']['offset'])->toBe(4)
        ->and($finding['written']['length'])->toBe(2)
        ->and($finding['context']['text'])->toBe($written);
});

it('runs a rule only on the fields its scope names', function () {
    config()->set('copydesk.fields', ['*' => ['content', 'summary']]);
    sitePack('house', <<<'YAML'
    rules:
      - id: house-names
        type: term
        terms: [eiSEO]
        message: Spell it eiSEO.
        scope:
          fields: [summary]
    YAML);

    $findings = collect(analyse(['content' => bard('Try eiseo.'), 'summary' => 'Try eiseo.'])->json('findings'));

    expect($findings->where('rule', 'house-names')->pluck('field')->all())->toBe(['summary']);
});

it('turns on only the packs a site lists, when it lists them', function () {
    config()->set('copydesk.packs', ['core-punctuation']);

    expect(collect(analyse(['content' => bard('We utilize forms — often.')])->json('findings'))->pluck('rule')->all())->toBe(['no-em-dash']);
});

it('measures a collection against its own target, and others against the site\'s', function () {
    config()->set('copydesk.targets', ['pages' => 6]);

    expect(analyse(['content' => bard('Fine.')])->json('target.label'))->toBe('Grade 5–7');

    config()->set('copydesk.targets', ['news' => 6]);
    app()->forgetInstance(Settings::class);

    expect(analyse(['content' => bard('Fine.')])->json('target.label'))->toBe('Grade 7–9');
});

it('checks the title as a heading when a casing rule is on', function () {
    sitePack('house', <<<'YAML'
    rules:
      - id: heading-case
        type: casing
        style: sentence
        message: Sentence case.
    YAML);

    $finding = collect(analyse(['title' => 'Our Mission And Values', 'content' => bard('Fine.')])->json('findings'))->firstWhere('rule', 'heading-case');

    expect($finding['field'])->toBe('title')
        ->and($finding['suggestion'])->toBe('Our mission and values');
});

it('marks a paragraph well above the target, and leaves short and near ones alone', function () {
    $hard = 'Immunization against seasonal influenza substantially reduces hospitalization among immunocompromised populations, according to epidemiological surveillance conducted by the relevant public health authorities. Their recommendations, promulgated annually, emphasize vaccination for vulnerable demographics including institutionalized individuals. Administration necessitates comprehensive documentation, contraindication screening, and pharmacovigilance procedures throughout the immunization programme.';
    $plain = 'A flu shot keeps you well. It is free. It takes five minutes. Ask us today. We are open late. Bring your card. You can walk in. No need to call first. We will see you soon. Your family can come too.';

    $findings = collect(analyse(['content' => bard($plain, $hard, 'Immunization is recommended annually.')])->json('findings'))->where('rule', 'hard_paragraph')->values();

    expect($findings)->toHaveCount(1)
        ->and($findings[0]['block'])->toBe(1)
        ->and($findings[0]['message'])->toContain('well above your target of Grade 7–9');
});

it('shows Flesch-Kincaid and reading ease for each section, as the engine computes them (acceptance 5)', function () {
    $text = 'A flu shot keeps you well. It is free. It takes five minutes. Ask us today.';
    $response = analyse(['content' => bard('# Flu shots', $text)]);
    $section = collect($response->json('sections'))->firstWhere('title', 'Flu shots');

    $grades = Grades::of((new Tally(new EnglishSegmenter, new EnglishSyllableCounter))->of((new PlainTextExtractor)->extract($text)));

    expect($section['grades']['flesch_kincaid'])->toBe(round($grades->fleschKincaid, 1))
        ->and($section['grades']['reading_ease'])->toBe(round($grades->readingEase, 1))
        ->and($response->json('grades.flesch_kincaid'))->toBe(round($grades->fleschKincaid, 1));
});
