<?php

declare(strict_types=1);

use Bpmore\Copydesk\Packs\PackLibrary;
use Bpmore\Copydesk\Style\Pack;
use Bpmore\Copydesk\Style\PackSet;
use Bpmore\ReadabilityCore\Extraction\HtmlExtractor;
use Bpmore\ReadabilityCore\Rule\Finding;
use Bpmore\ReadabilityCore\Rule\RuleSet;
use Symfony\Component\Yaml\Yaml;

/**
 * The four packs Copydesk ships, read the way a site reads them.
 */
function bundled(): PackSet
{
    return app(PackLibrary::class)->set();
}

/** @return list<array{string, string}> rule and what it flagged, in reading order */
function flaggedBy(string $pack, string $html): array
{
    $set = bundled();
    $findings = (new RuleSet([]))->with(...$set->runnable([$pack]))->check((new HtmlExtractor)->extract($html));

    return array_map(fn (Finding $f) => [$f->rule, $f->excerpt], $findings->all());
}

it('ships the four packs the spec names, and every one loads', function () {
    expect(bundled()->names())->toBe(['core-punctuation', 'inclusive-language', 'news-style-starter', 'plain-language']);
});

it('finds core punctuation', function () {
    expect(flaggedBy('core-punctuation', '<p>Wait , what?? Plain words — and  short ones.</p>'))->toBe([
        ['no-space-before-punctuation', ' ,'],
        ['no-repeated-punctuation', '??'],
        ['no-em-dash', '—'],
        ['no-double-space', '  '],
    ]);
});

it('carries plainlanguage.gov\'s list, entry for entry, as the engine\'s starter has it', function () {
    $starter = json_decode((string) file_get_contents(__DIR__.'/../../vendor/bpmore/readability-core/resources/dictionaries/plain-language.json'), true);
    unset($starter['_source']);

    $words = [];

    foreach (bundled()->rules(['plain-language'])[0]->options['words'] as $word) {
        $words[$word['phrase']] = $word['replacements'];
    }

    expect($words)->toBe($starter)
        ->and(flaggedBy('plain-language', '<p>We will utilize the forms in order to commence.</p>'))->toBe([
            ['plain-words', 'utilize'], ['plain-words', 'in order to'], ['plain-words', 'commence'],
        ]);
});

it('finds words that leave people out, each with a reason', function () {
    $findings = (new RuleSet([]))->with(...bundled()->runnable(['inclusive-language']))
        ->check((new HtmlExtractor)->extract('<p>The chairman, who is wheelchair-bound, kept a blacklist.</p>'))->all();

    expect(array_map(fn (Finding $f) => [$f->rule, $f->excerpt, $f->suggestion], $findings))->toBe([
        ['inclusive-roles', 'chairman', 'chair'],
        ['inclusive-people', 'wheelchair-bound', 'uses a wheelchair'],
        ['inclusive-phrases', 'blacklist', 'blocklist'],
    ])->and($findings[1]->message)->toContain('A wheelchair gives freedom of movement');

    foreach (bundled()->rules(['inclusive-language']) as $rule) {
        foreach ($rule->options['words'] as $word) {
            expect($word['reason'])->not->toBeNull("\"{$word['phrase']}\" has no reason");
        }
    }
});

it('finds the newsroom conventions for numbers, times and dates', function () {
    expect(flaggedBy('news-style-starter', '<p>12 nurses start at 9:00 a.m. on March 3rd. Lunch is at 12 p.m. Turnout rose 5%. Doors close at 7 PM.</p>'))->toBe([
        ['news-no-numeral-start', '12'],
        ['news-no-on-the-hour', '9:00 a.m.'],
        ['news-no-date-ordinal', 'March 3rd'],
        ['news-noon-midnight', '12 p.m.'],
        ['news-percent-word', '5%'],
        ['news-am-pm', '7 PM'],
    ])->and(flaggedBy('news-style-starter', '<p>Doors open at 9 a.m. and close at 5:30 p.m. on March 3. Turnout rose 5 percent.</p>'))->toBe([]);
});

it('says nothing that copies or claims a commercial stylebook', function () {
    foreach (glob(__DIR__.'/../../resources/packs/*.yaml') as $file) {
        $text = (string) file_get_contents($file);

        expect($text)->not->toMatch('/\bAP\b|Associated Press/', basename($file).' names AP')
            ->and($text)->not->toContain("\u{2014}", basename($file).' has an em dash');
    }
});

it('loads every example pack in the editors\' guide', function () {
    preg_match_all('/```yaml\n(.*?)```/s', (string) file_get_contents(__DIR__.'/../../docs/packs.md'), $blocks);

    expect($blocks[1])->toHaveCount(2);

    foreach ($blocks[1] as $i => $yaml) {
        $pack = Pack::fromArray("example-{$i}", Yaml::parse($yaml), "docs/packs.md example {$i}");
        $set = new PackSet([...array_map(fn (string $n) => Pack::fromArray($n, Yaml::parseFile(__DIR__."/../../resources/packs/{$n}.yaml"), $n), bundled()->names()), $pack]);

        expect($set->rules(["example-{$i}"]))->not->toBeEmpty()
            ->and($set->rules(["example-{$i}"], 'patient_education'))->not->toBeEmpty();
    }
});

it('has a review list that matches the packs as they are (acceptance 9)', function () {
    require_once __DIR__.'/../../bin/pack-review.php';

    expect((string) file_get_contents(__DIR__.'/../../docs/pack-review.md'))
        ->toBe(packReview(__DIR__.'/../../resources/packs'), 'The packs changed: run `composer pack-review`.');
});
