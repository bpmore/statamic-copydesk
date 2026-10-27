<?php

declare(strict_types=1);

use Bpmore\Copydesk\Style\RuleDefinition;
use Bpmore\Copydesk\Style\RuleFactory;
use Bpmore\ReadabilityCore\Extraction\PlainTextExtractor;
use Bpmore\ReadabilityCore\Rule\Dictionary;
use Bpmore\ReadabilityCore\Rule\Finding;
use Bpmore\ReadabilityCore\Rule\Jargon;
use Bpmore\ReadabilityCore\Rule\Severity;

/**
 * @param  array<string, mixed>  $words
 * @return list<Finding>
 */
function wordList(array $words, string $text, array $extra = []): array
{
    $definition = RuleDefinition::fromArray(['id' => 'plain-words', 'type' => 'word_list', 'message' => 'Say it plainly.', 'words' => $words] + $extra, 'house.yaml');

    return RuleFactory::make($definition)->check((new PlainTextExtractor)->extract($text));
}

it('finds a word and its inflections, and says what to say instead', function () {
    $findings = wordList(['utilize' => 'use'], 'We utilize forms. Staff utilized them.');

    expect($findings)->toHaveCount(2)
        ->and($findings[0]->rule)->toBe('plain-words')
        ->and($findings[0]->severity)->toBe(Severity::Warning)
        ->and($findings[0]->excerpt)->toBe('utilize')
        ->and($findings[0]->suggestion)->toBe('use')
        ->and($findings[0]->message)->toBe('Say "use" instead of "utilize". Say it plainly.')
        ->and([$findings[0]->location->block, $findings[0]->location->offset, $findings[0]->location->length])->toBe([0, 3, 7])
        ->and($findings[1]->excerpt)->toBe('utilized');
});

it('keeps the author\'s capitals in the suggestion', function () {
    $findings = wordList(['utilize' => 'use', 'eiseo' => 'eiSEO'], 'Utilize it. UTILIZE IT. Try eiseo.');

    expect(array_map(fn (Finding $f) => $f->suggestion, $findings))->toBe(['Use', 'USE', 'eiSEO']);
});

it('reports the longer phrase once, not the shorter one inside it', function () {
    $findings = wordList(['in order to' => 'to', 'order' => 'sequence'], 'In order to help, sort the order.');

    expect(array_map(fn (Finding $f) => $f->excerpt, $findings))->toBe(['In order to', 'order']);
});

it('says when a word has nothing to say instead', function () {
    $findings = wordList(['please be advised that' => null], 'Please be advised that the clinic is closed.');

    expect($findings[0]->message)->toBe('"Please be advised that": say it another way. Say it plainly.')
        ->and($findings[0]->suggestion)->toBeNull();
});

it('lets an entry carry its own severity and reason', function () {
    $findings = wordList([
        'chairman' => ['replace' => ['chair', 'chairperson'], 'severity' => 'error', 'reason' => 'Name the role, not the person.'],
        'utilize' => 'use',
    ], 'The chairman will utilize it.', ['severity' => 'info']);

    expect($findings[0]->severity)->toBe(Severity::Error)
        ->and($findings[0]->message)->toBe('Say "chair" or "chairperson" instead of "chairman". Name the role, not the person.')
        ->and($findings[1]->severity)->toBe(Severity::Tip);
});

it('measures offsets in bytes, as the engine does, past accented text', function () {
    $findings = wordList(['utilize' => 'use'], 'Café staff utilize it.');

    expect($findings[0]->location->offset)->toBe(strlen('Café staff '));
});

it('finds what the engine\'s jargon rule finds, so a Plain dictionary moves over unchanged', function () {
    $terms = ['utilize' => 'use', 'in order to' => 'to', 'notify' => 'tell', 'please be advised that' => [], 'prior to' => ['before']];
    $text = "Please be advised that we notified you. In order to\nhelp, utilize this prior to Monday. Notifying staff utilizes time.";
    $document = (new PlainTextExtractor)->extract($text);

    $engine = (new Jargon([], Dictionary::fromArray($terms)))->check($document);
    $ours = wordList(array_map(fn ($r) => $r === [] ? null : $r, $terms), $text);

    $where = fn (array $findings) => array_map(fn (Finding $f) => [$f->location->block, $f->location->offset, $f->excerpt], $findings);

    expect($where($ours))->toBe($where($engine))->not->toBeEmpty();
});
