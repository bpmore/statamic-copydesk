<?php

declare(strict_types=1);

use Bpmore\Copydesk\Packs\PackLibrary;
use Bpmore\Copydesk\Style\Pack;
use Bpmore\Copydesk\Style\PackSet;
use Bpmore\ReadabilityCore\Exclusion\Pipeline;
use Bpmore\ReadabilityCore\Exclusion\ProperNames;
use Bpmore\ReadabilityCore\Extraction\HtmlExtractor;
use Bpmore\ReadabilityCore\Formula\Grades;
use Bpmore\ReadabilityCore\Formula\Tally;
use Bpmore\ReadabilityCore\Rule\RuleSet;
use Bpmore\ReadabilityCore\Text\EnglishSegmenter;
use Bpmore\ReadabilityCore\Text\EnglishSyllableCounter;
use Bpmore\ReadabilityCore\Text\Words;

/**
 * The spec's budget: lint a 3,000-word entry in under 150 ms, server side.
 *
 * "Lint" here is everything a check of one entry does: extract the HTML,
 * apply the exclusions, tally and grade, and run every readability rule and
 * every house rule. The house rules are every bundled pack that exists plus
 * a heavy stand-in: all six types, and plainlanguage.gov's whole list as a
 * word list (the plain-language pack, D2). Bundled packs join it as Phase
 * 1.3 writes them, with no change here.
 *
 * Machines vary, and CI's most of all, so on CI the time is reported and
 * not held to; anywhere else, over budget fails.
 */
const BUDGET_MS = 150;

function benchmarkEntry(int $words): string
{
    mt_srand(42);

    $pool = explode(' ', 'the patient should visit the clinic before the appointment and bring a list of every medicine they take each day because the nurse will ask about allergies side effects and recent tests so the doctor can plan care that fits their life at home and at work');
    $hits = ['utilize', 'in order to', 'eiseo', 'was signed by the nurse', 'prior to', 'commence', '12 forms', 'Uams Health', '—', 'a number of'];

    $html = '';
    $count = 0;

    while ($count < $words) {
        $html .= '<h2>Our Plan For '.ucfirst($pool[mt_rand(0, count($pool) - 1)]).'</h2>';

        for ($p = 0; $p < 4 && $count < $words; $p++) {
            $sentences = [];

            for ($s = 0; $s < 5; $s++) {
                $length = mt_rand(8, 30);
                $sentence = [];

                for ($w = 0; $w < $length; $w++) {
                    $sentence[] = mt_rand(0, 14) === 0 ? $hits[mt_rand(0, count($hits) - 1)] : $pool[mt_rand(0, count($pool) - 1)];
                }

                $sentences[] = ucfirst(implode(' ', $sentence)).'.';
                $count += $length;
            }

            $html .= '<p>'.implode(' ', $sentences).'</p>';
        }
    }

    return $html;
}

function benchmarkPack(): Pack
{
    $starter = json_decode((string) file_get_contents(__DIR__.'/../../vendor/bpmore/readability-core/resources/dictionaries/plain-language.json'), true);
    $words = [];

    foreach ($starter as $term => $replacements) {
        if (! str_starts_with($term, '_')) {
            $words[$term] = $replacements === [] ? null : $replacements;
        }
    }

    return Pack::fromArray('benchmark', ['rules' => [
        ['id' => 'plain-words', 'type' => 'word_list', 'words' => $words, 'message' => 'Say it plainly.'],
        ['id' => 'house-names', 'type' => 'term', 'terms' => ['UAMS Health', 'eiSEO', 'Statamic', 'Greenhouse Institute', 'Little Rock', 'Arkansas Children\'s', 'MyChart', 'Epic', 'COVID-19', 'Medicare'], 'message' => 'Spell names the house way.'],
        ['id' => 'no-percent-sign', 'type' => 'pattern', 'pattern' => '\d+%', 'message' => 'Write percent.'],
        ['id' => 'times', 'type' => 'pattern', 'pattern' => '\b\d{1,2}:\d{2}\s?(?:AM|PM)\b', 'message' => 'Write a.m. and p.m.'],
        ['id' => 'dates', 'type' => 'pattern', 'pattern' => '\b(?:Jan|Feb|Mar|Apr|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\b(?!\.)', 'message' => 'Abbreviate months the house way.'],
        ['id' => 'house-punctuation', 'type' => 'punctuation', 'checks' => ['em_dash', 'double_space', 'quotes', 'space_before_punctuation', 'repeated_punctuation'], 'quotes' => 'curly', 'message' => 'House punctuation.'],
        ['id' => 'heading-case', 'type' => 'casing', 'style' => 'sentence', 'keep' => ['UAMS Health'], 'message' => 'Sentence case.'],
        ['id' => 'long-sentences', 'type' => 'sentence', 'check' => 'max_words', 'max_words' => 25, 'message' => 'Shorter.'],
        ['id' => 'passive', 'type' => 'sentence', 'check' => 'passive', 'message' => 'Say who does what.'],
        ['id' => 'numerals', 'type' => 'sentence', 'check' => 'starts_with_numeral', 'message' => 'Spell it out.'],
    ]], 'benchmark.yaml');
}

it('lints a 3,000-word entry with every pack on within the spec\'s 150 ms', function () {
    $html = benchmarkEntry(3000);
    $bundled = app(PackLibrary::class)->set();
    $set = new PackSet([benchmarkPack()]);

    $lint = function () use ($html, $bundled, $set): int {
        $document = (new HtmlExtractor)->extract($html);
        $pipeline = Pipeline::fromConfig([]);
        $forRules = $pipeline->without(ProperNames::class)->apply($document);
        $tally = new Tally(new EnglishSegmenter, new EnglishSyllableCounter);
        Grades::of($tally->of($pipeline->only(ProperNames::class)->apply($forRules)));

        $rules = RuleSet::fromConfig([])
            ->with(...$bundled->runnable($bundled->names()))
            ->with(...$set->runnable(['benchmark']));

        return $rules->check($forRules)->count();
    };

    $findings = $lint();
    $times = [];

    for ($i = 0; $i < 5; $i++) {
        $start = hrtime(true);
        $lint();
        $times[] = (hrtime(true) - $start) / 1e6;
    }

    sort($times);
    $median = $times[2];
    $words = count(Words::of(strip_tags(str_replace('<', ' <', $html))));

    fwrite(STDERR, sprintf("\n  Lint of %d words, %d packs, %d findings: median %.1f ms (budget %d ms)\n", $words, count($bundled->names()) + 1, $findings, $median, BUDGET_MS));

    expect($words)->toBeGreaterThanOrEqual(3000)
        ->and($findings)->toBeGreaterThan(50);

    if (getenv('CI') === false || getenv('CI') === '') {
        expect($median)->toBeLessThan(BUDGET_MS);
    }
});
