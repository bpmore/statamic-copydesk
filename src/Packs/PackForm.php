<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Packs;

use Bpmore\Copydesk\Style\RuleType;
use Statamic\Facades\Blueprint;
use Statamic\Fields\Blueprint as BlueprintContract;
use Statamic\Support\Str;

/**
 * A site pack as a control-panel form, and back (Phase 1.9).
 *
 * The form is Statamic's own publish form: one Replicator set per kind of
 * rule, so a rule's own keys show only for its kind. A rule switched off in
 * the Replicator is kept, and its id goes into the pack's `disable`, which
 * is how a pack turns one of its own rules off. A pack's `collections`
 * section has no place in the form; it is kept from the file as it is.
 *
 * Two names change on the way: a Replicator row has its own `id` and
 * `type`, so a rule's id is `rule_id` in the form, and its type is the set.
 */
final class PackForm
{
    /** @var array<string, string> severities as a pack writes them, and as the author reads them */
    private const SEVERITIES = ['info' => 'Tip', 'warning' => 'To look at', 'error' => 'To fix'];

    /**
     * @param  list<string>  $packs  the other packs this one could extend
     */
    public static function blueprint(array $packs): BlueprintContract
    {
        $common = [
            ['handle' => 'rule_id', 'field' => ['type' => 'text', 'display' => 'Id', 'instructions' => 'Lowercase letters, numbers and hyphens, like `no-em-dash`. A rule with the same id as one in a pack this extends replaces it.', 'validate' => ['required'], 'width' => 50]],
            ['handle' => 'severity', 'field' => ['type' => 'button_group', 'display' => 'How much it matters', 'options' => self::SEVERITIES, 'default' => 'warning', 'instructions' => '"To fix" stops a publish when the gate is on.', 'width' => 50]],
            ['handle' => 'message', 'field' => ['type' => 'text', 'display' => 'Message', 'instructions' => 'What the author reads: say what to do instead.', 'validate' => ['required']]],
        ];

        $tail = [
            ['handle' => 'suggestions', 'field' => ['type' => 'list', 'display' => 'Suggestions', 'instructions' => 'What to offer instead, one per line.', 'width' => 50]],
            ['handle' => 'exceptions', 'field' => ['type' => 'list', 'display' => 'Never flag', 'instructions' => 'Text, or a `/regular expression/`, that is never flagged.', 'width' => 50]],
            ['handle' => 'scope_collections', 'field' => ['type' => 'list', 'display' => 'Only in these collections', 'instructions' => 'Collection handles. Empty means every collection.', 'width' => 50]],
            ['handle' => 'scope_fields', 'field' => ['type' => 'list', 'display' => 'Only in these fields', 'instructions' => 'Field handles. Empty means every field.', 'width' => 50]],
        ];

        $sets = [
            'word_list' => ['Word list', 'Flags words and phrases, any case, and a single word\'s other forms.', [
                ['handle' => 'words', 'field' => ['type' => 'grid', 'display' => 'Words', 'mode' => 'stacked', 'add_row' => 'Add a word', 'validate' => ['required'], 'fields' => [
                    ['handle' => 'word', 'field' => ['type' => 'text', 'display' => 'Word or phrase', 'validate' => ['required'], 'width' => 50]],
                    ['handle' => 'replace', 'field' => ['type' => 'list', 'display' => 'Say instead', 'instructions' => 'Empty: say it another way.', 'width' => 50]],
                    ['handle' => 'reason', 'field' => ['type' => 'text', 'display' => 'Why', 'width' => 50]],
                    ['handle' => 'severity', 'field' => ['type' => 'select', 'display' => 'How much it matters', 'options' => ['' => 'As the rule says'] + self::SEVERITIES, 'width' => 50]],
                ]]],
            ]],
            'term' => ['Term', 'Flags a name written any other way. The exact spelling is never flagged.', [
                ['handle' => 'terms', 'field' => ['type' => 'list', 'display' => 'Terms', 'validate' => ['required']]],
            ]],
            'pattern' => ['Pattern', 'Flags what a regular expression matches.', [
                ['handle' => 'pattern', 'field' => ['type' => 'text', 'display' => 'Pattern', 'instructions' => 'Without slashes.', 'validate' => ['required'], 'width' => 66]],
                ['handle' => 'flags', 'field' => ['type' => 'text', 'display' => 'Flags', 'instructions' => '`i` for any case.', 'width' => 33]],
            ]],
            'punctuation' => ['Punctuation', 'Built-in punctuation checks.', [
                ['handle' => 'checks', 'field' => ['type' => 'checkboxes', 'display' => 'Checks', 'validate' => ['required'], 'options' => array_combine(RuleType::PUNCTUATION_CHECKS, ['Em dashes', 'Two spaces', 'Quotes', 'A space before punctuation', 'Repeated marks'])]],
                ['handle' => 'quotes', 'field' => ['type' => 'select', 'display' => 'Quotes', 'options' => ['' => 'Not checked', 'curly' => 'Curly', 'straight' => 'Straight'], 'instructions' => 'Needed when Quotes is checked.']],
            ]],
            'casing' => ['Casing', 'Puts headings in sentence or title case.', [
                ['handle' => 'style', 'field' => ['type' => 'select', 'display' => 'Style', 'options' => ['sentence' => 'Sentence case', 'title' => 'Title case'], 'default' => 'sentence', 'width' => 50]],
                ['handle' => 'keep', 'field' => ['type' => 'list', 'display' => 'Always as written', 'instructions' => 'Names that keep their capitals.', 'width' => 50]],
            ]],
            'sentence' => ['Sentence', 'Long sentences, the passive voice, or a sentence that starts with a numeral.', [
                ['handle' => 'check', 'field' => ['type' => 'select', 'display' => 'Check', 'options' => ['max_words' => 'More words than', 'passive' => 'Passive voice', 'starts_with_numeral' => 'Starts with a numeral'], 'default' => 'max_words', 'width' => 50]],
                ['handle' => 'max_words', 'field' => ['type' => 'integer', 'display' => 'Words', 'width' => 50, 'if' => ['check' => 'equals max_words']]],
            ]],
        ];

        $replicatorSets = [];

        foreach ($sets as $handle => [$display, $instructions, $fields]) {
            $replicatorSets[$handle] = ['display' => $display, 'instructions' => $instructions, 'fields' => [...$common, ...$fields, ...$tail]];
        }

        return Blueprint::make('copydesk_pack')->setContents(['tabs' => [
            'main' => ['display' => 'Pack', 'sections' => [[
                'fields' => [
                    ['handle' => 'description', 'field' => ['type' => 'textarea', 'display' => 'What this pack is for']],
                    ['handle' => 'extends', 'field' => ['type' => 'checkboxes', 'display' => 'Start from', 'instructions' => 'Packs whose rules come first. This pack\'s rules replace theirs by id.', 'options' => array_combine($packs, $packs) ?: []]],
                    ['handle' => 'disable', 'field' => ['type' => 'list', 'display' => 'Turn off', 'instructions' => 'Ids of rules from the packs above to turn off. Switch a rule of this pack off in the list below.']],
                    ['handle' => 'rules', 'field' => ['type' => 'replicator', 'display' => 'Rules', 'button_label' => 'Add a rule', 'collapse' => true, 'sets' => ['main' => ['display' => 'Rules', 'sets' => $replicatorSets]]]],
                ],
            ]]],
            'sidebar' => ['display' => 'Try it', 'sections' => [[
                'fields' => [
                    ['handle' => 'copydesk_try', 'field' => ['type' => 'copydesk_rule_tester', 'display' => 'Try it on some text']],
                ],
            ]]],
        ]]);
    }

    /**
     * A pack file's contents as the form's values.
     *
     * @param  array<string, mixed>  $yaml
     * @return array<string, mixed>
     */
    public static function toValues(array $yaml): array
    {
        $disable = array_values(array_map(strval(...), (array) ($yaml['disable'] ?? [])));
        $own = [];
        $rules = [];

        foreach ((array) ($yaml['rules'] ?? []) as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $id = (string) ($rule['id'] ?? '');
            $own[] = $id;
            $row = [
                'id' => Str::random(8),
                'type' => (string) ($rule['type'] ?? ''),
                'enabled' => ! in_array($id, $disable, true),
                'rule_id' => $id,
                'message' => (string) ($rule['message'] ?? ''),
                'severity' => (string) ($rule['severity'] ?? 'warning'),
                'suggestions' => array_values((array) ($rule['suggestions'] ?? [])),
                'exceptions' => array_values((array) ($rule['exceptions'] ?? [])),
                'scope_collections' => array_values((array) ($rule['scope']['collections'] ?? [])),
                'scope_fields' => array_values((array) ($rule['scope']['fields'] ?? [])),
            ];

            foreach (['terms', 'keep', 'checks'] as $key) {
                if (array_key_exists($key, $rule)) {
                    $row[$key] = array_values((array) $rule[$key]);
                }
            }

            foreach (['pattern', 'flags', 'quotes', 'style', 'check', 'max_words'] as $key) {
                if (array_key_exists($key, $rule)) {
                    $row[$key] = $rule[$key];
                }
            }

            if (isset($rule['words']) && is_array($rule['words'])) {
                $row['words'] = self::wordRows($rule['words']);
            }

            $rules[] = $row;
        }

        return [
            'description' => (string) ($yaml['description'] ?? ''),
            'extends' => array_values(array_map(strval(...), (array) ($yaml['extends'] ?? []))),
            // The pack's own rules are switched off in the list; only the
            // others' ids stay here.
            'disable' => array_values(array_diff($disable, $own)),
            'rules' => $rules,
        ];
    }

    /**
     * The form's values as a pack file's contents. The `collections`
     * section, which the form does not edit, comes from the file as it was.
     *
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $existing  the file as it is now
     * @return array<string, mixed>
     */
    public static function fromValues(array $values, array $existing = []): array
    {
        $yaml = [];
        $description = trim((string) ($values['description'] ?? ''));
        $disable = self::strings($values['disable'] ?? []);
        $rules = [];

        if ($description !== '') {
            $yaml['description'] = $description;
        }

        foreach ((array) ($values['rules'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $id = trim((string) ($row['rule_id'] ?? ''));
            $rule = ['id' => $id, 'type' => (string) ($row['type'] ?? ''), 'message' => trim((string) ($row['message'] ?? ''))];

            if (($row['enabled'] ?? true) === false && $id !== '') {
                $disable[] = $id;
            }

            match ($rule['type']) {
                'word_list' => $rule['words'] = self::words((array) ($row['words'] ?? [])),
                'term' => $rule['terms'] = self::strings($row['terms'] ?? []),
                'pattern' => $rule += array_filter(['pattern' => (string) ($row['pattern'] ?? ''), 'flags' => trim((string) ($row['flags'] ?? ''))], static fn (string $v, string $k): bool => $k === 'pattern' || $v !== '', ARRAY_FILTER_USE_BOTH),
                'punctuation' => $rule += array_filter(['checks' => self::strings($row['checks'] ?? []), 'quotes' => (string) ($row['quotes'] ?? '')], static fn ($v, string $k): bool => $k === 'checks' || $v !== '', ARRAY_FILTER_USE_BOTH),
                'casing' => $rule += array_filter(['style' => (string) ($row['style'] ?? 'sentence'), 'keep' => self::strings($row['keep'] ?? [])], static fn ($v, string $k): bool => $k === 'style' || $v !== [], ARRAY_FILTER_USE_BOTH),
                'sentence' => $rule += ['check' => (string) ($row['check'] ?? 'max_words')] + (($row['check'] ?? 'max_words') === 'max_words' ? ['max_words' => (int) ($row['max_words'] ?? 0)] : []),
                default => null,
            };

            $severity = (string) ($row['severity'] ?? 'warning');

            if ($severity !== 'warning' && $severity !== '') {
                $rule['severity'] = $severity;
            }

            foreach (['suggestions' => 'suggestions', 'exceptions' => 'exceptions'] as $from => $to) {
                // A suggestion may be blank on purpose (", " trims to ","), so
                // only fully empty ones go.
                $list = array_values(array_filter((array) ($row[$from] ?? []), static fn ($v): bool => is_string($v) && $v !== ''));

                if ($list !== []) {
                    $rule[$to] = $list;
                }
            }

            $scope = array_filter(['collections' => self::strings($row['scope_collections'] ?? []), 'fields' => self::strings($row['scope_fields'] ?? [])]);

            if ($scope !== []) {
                $rule['scope'] = $scope;
            }

            $rules[] = $rule;
        }

        $extends = self::strings($values['extends'] ?? []);

        if ($extends !== []) {
            $yaml['extends'] = $extends;
        }

        if ($disable !== []) {
            $yaml['disable'] = array_values(array_unique($disable));
        }

        $yaml['rules'] = $rules;

        if (isset($existing['collections'])) {
            $yaml['collections'] = $existing['collections'];
        }

        return $yaml;
    }

    /**
     * @param  array<array-key, mixed>  $words
     * @return list<array{word: string, replace: list<string>, reason: string, severity: string}>
     */
    private static function wordRows(array $words): array
    {
        $rows = [];

        foreach ($words as $word => $value) {
            $row = ['id' => Str::random(8), 'word' => (string) $word, 'replace' => [], 'reason' => '', 'severity' => ''];

            if (is_string($value)) {
                $row['replace'] = [$value];
            } elseif (is_array($value) && array_is_list($value)) {
                $row['replace'] = array_values(array_map(strval(...), $value));
            } elseif (is_array($value)) {
                $row['replace'] = array_values(array_map(strval(...), (array) ($value['replace'] ?? [])));
                $row['reason'] = (string) ($value['reason'] ?? '');
                $row['severity'] = (string) ($value['severity'] ?? '');
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Grid rows as a word list, each word as short as the YAML allows: a
     * single replacement as a string, none as nothing (`~`), and a map only
     * when there is a reason or a severity.
     *
     * @param  array<array-key, mixed>  $rows
     * @return array<string, mixed>
     */
    private static function words(array $rows): array
    {
        $words = [];

        foreach ($rows as $row) {
            $word = trim((string) ($row['word'] ?? ''));

            if ($word === '') {
                continue;
            }

            $replace = self::strings($row['replace'] ?? []);
            $reason = trim((string) ($row['reason'] ?? ''));
            $severity = (string) ($row['severity'] ?? '');

            $words[$word] = match (true) {
                $reason !== '' || $severity !== '' => array_filter(['replace' => $replace, 'reason' => $reason, 'severity' => $severity], static fn ($v): bool => $v !== '' && $v !== []),
                count($replace) === 1 => $replace[0],
                $replace === [] => null,
                default => $replace,
            };
        }

        return $words;
    }

    /**
     * @return list<string>
     */
    private static function strings(mixed $values): array
    {
        return array_values(array_filter(array_map(static fn ($v): string => trim((string) $v), (array) $values), static fn (string $v): bool => $v !== ''));
    }
}
