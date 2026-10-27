<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Style;

use Bpmore\ReadabilityCore\Rule\Severity;

/**
 * One rule as a pack writes it, checked and made plain.
 *
 * Every rule has an `id`, a `type`, a `message`, and may have `suggestions`,
 * a `severity` (`info`, `warning` or `error`; `warning` if left out),
 * a `scope` (`collections` and `fields`, each `*` if left out) and
 * `exceptions` (text, or a `/regex/flags`, that excuses a match). Each type
 * adds its own keys; RuleType lists them.
 *
 * `info` is the spec's word; the engine calls the same thing a tip, and
 * this is where one becomes the other.
 *
 * Everything that is wrong is said with the pack and the rule it is in.
 * An unknown key is refused, with the nearest known key offered, because a
 * misspelt `severty` that is silently ignored leaves a rule at a severity
 * nobody chose.
 */
final class RuleDefinition
{
    /** @var list<string> */
    private const COMMON_KEYS = ['id', 'type', 'message', 'suggestions', 'severity', 'scope', 'exceptions'];

    private const ID = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /**
     * @param  list<string>  $suggestions
     * @param  list<string>  $exceptions
     * @param  array<string, mixed>  $options  the type's own keys, checked; see RuleType
     */
    public function __construct(
        public readonly string $id,
        public readonly RuleType $type,
        public readonly string $message,
        public readonly array $suggestions,
        public readonly Severity $severity,
        public readonly Scope $scope,
        public readonly array $exceptions,
        public readonly array $options,
        public readonly string $source,
    ) {}

    /**
     * @param  mixed  $raw  one entry of a pack's `rules`, as parsed from YAML
     * @param  string  $source  the pack it came from, for messages: "house.yaml"
     * @param  int  $position  its place in the pack's list, from 1, for a rule with no usable id
     */
    public static function fromArray(mixed $raw, string $source, int $position = 1): self
    {
        $name = "#{$position}";

        if (! is_array($raw) || array_is_list($raw)) {
            throw InvalidRule::in($source, $name, 'a rule is a set of keys (id, type, message, ...), not '.self::describe($raw).'.');
        }

        $id = $raw['id'] ?? null;

        if (! is_string($id) || preg_match(self::ID, $id) !== 1) {
            throw InvalidRule::in($source, $name, '"id" must be lowercase letters, numbers and single hyphens, like "no-em-dash"; got '.self::describe($id).'.');
        }

        $name = "\"{$id}\"";

        $type = is_string($raw['type'] ?? null) ? RuleType::tryFrom($raw['type']) : null;

        if ($type === null) {
            throw InvalidRule::in($source, $name, '"type" must be one of '.implode(', ', RuleType::names()).'; got '.self::describe($raw['type'] ?? null).'.');
        }

        $known = [...self::COMMON_KEYS, ...$type->keys()['required'], ...$type->keys()['optional']];

        foreach (array_keys($raw) as $key) {
            if (! in_array($key, $known, true)) {
                throw InvalidRule::in($source, $name, "\"{$key}\" is not a key a {$type->value} rule has.".self::nearest((string) $key, $known));
            }
        }

        foreach ($type->keys()['required'] as $key) {
            if (! array_key_exists($key, $raw)) {
                throw InvalidRule::in($source, $name, "a {$type->value} rule needs \"{$key}\".");
            }
        }

        $message = $raw['message'] ?? null;

        if (! is_string($message) || trim($message) === '') {
            throw InvalidRule::in($source, $name, '"message" must say, in a sentence, what to do instead.');
        }

        return new self(
            id: $id,
            type: $type,
            message: trim($message),
            suggestions: self::strings($raw['suggestions'] ?? [], 'suggestions', $source, $name, allowBlank: true),
            severity: self::severity($raw['severity'] ?? 'warning', '"severity"', $source, $name),
            scope: self::scope($raw['scope'] ?? [], $source, $name),
            exceptions: self::exceptions($raw['exceptions'] ?? [], $source, $name),
            options: self::options($type, $raw, $source, $name),
            source: $source,
        );
    }

    /**
     * A severity as a pack writes it, as the engine's.
     */
    public static function severity(mixed $value, string $what, string $source, string $name): Severity
    {
        return match ($value) {
            'info' => Severity::Tip,
            'warning' => Severity::Warning,
            'error' => Severity::Error,
            default => throw InvalidRule::in($source, $name, "{$what} is ".self::describe($value).'; use info, warning or error.'),
        };
    }

    private static function scope(mixed $value, string $source, string $name): Scope
    {
        if (! is_array($value) || ($value !== [] && array_is_list($value))) {
            throw InvalidRule::in($source, $name, '"scope" is a set of "collections" and "fields"; got '.self::describe($value).'.');
        }

        foreach (array_keys($value) as $key) {
            if (! in_array($key, ['collections', 'fields'], true)) {
                throw InvalidRule::in($source, $name, "\"scope\" has \"{$key}\"; it takes \"collections\" and \"fields\".".self::nearest((string) $key, ['collections', 'fields']));
            }
        }

        return new Scope(
            collections: self::strings($value['collections'] ?? [Scope::ANY], 'scope.collections', $source, $name, nonEmpty: true),
            fields: self::strings($value['fields'] ?? [Scope::ANY], 'scope.fields', $source, $name, nonEmpty: true),
        );
    }

    /**
     * @return list<string>
     */
    private static function exceptions(mixed $value, string $source, string $name): array
    {
        $exceptions = self::strings($value, 'exceptions', $source, $name);

        foreach ($exceptions as $exception) {
            if (self::isRegex($exception) && ! self::compiles($exception)) {
                throw InvalidRule::in($source, $name, "the exception {$exception} is written as a regular expression, and it does not compile.");
            }
        }

        return $exceptions;
    }

    /** Whether an exception is written as `/regex/flags` rather than literal text. */
    public static function isRegex(string $exception): bool
    {
        return preg_match('#^/.+/[a-zA-Z]*$#s', $exception) === 1;
    }

    /**
     * Whether a regular expression, delimiters and all, compiles.
     *
     * PCRE reports a bad pattern as a PHP warning as well as a false return,
     * and `@` does not keep that warning from a handler that turns warnings
     * into errors (a test runner, Laravel's), so the handler is swapped for
     * the length of the one call instead.
     */
    public static function compiles(string $regex): bool
    {
        set_error_handler(static fn (): bool => true);

        try {
            return preg_match($regex, '') !== false;
        } finally {
            restore_error_handler();
        }
    }

    /**
     * The type's own keys, checked.
     *
     * @param  array<array-key, mixed>  $raw
     * @return array<string, mixed>
     */
    private static function options(RuleType $type, array $raw, string $source, string $name): array
    {
        return match ($type) {
            RuleType::WordList => ['words' => self::words($raw['words'], $source, $name)],
            RuleType::Term => ['terms' => self::strings($raw['terms'], 'terms', $source, $name, nonEmpty: true)],
            RuleType::Pattern => self::pattern($raw, $source, $name),
            RuleType::Punctuation => self::punctuation($raw, $source, $name),
            RuleType::Casing => ['style' => self::oneOf($raw['style'], ['sentence', 'title'], 'style', $source, $name)],
            RuleType::Sentence => self::sentence($raw, $source, $name),
        };
    }

    /**
     * @return list<array{phrase: string, replacements: list<string>, severity: ?Severity, reason: ?string}>
     */
    private static function words(mixed $value, string $source, string $name): array
    {
        if (! is_array($value) || $value === [] || array_is_list($value)) {
            throw InvalidRule::in($source, $name, '"words" is a list of word: what to say instead, like "utilize: use"; got '.self::describe($value).'.');
        }

        $words = [];

        foreach ($value as $phrase => $entry) {
            $phrase = trim((string) $phrase);
            $where = "the word \"{$phrase}\"";

            if ($phrase === '') {
                throw InvalidRule::in($source, $name, '"words" has an empty word.');
            }

            if ($entry === null || is_string($entry) || (is_array($entry) && array_is_list($entry))) {
                $entry = ['replace' => $entry];
            }

            if (! is_array($entry)) {
                throw InvalidRule::in($source, $name, "{$where} needs what to say instead: a word, a list of words, or nothing; got ".self::describe($entry).'.');
            }

            foreach (array_keys($entry) as $key) {
                if (! in_array($key, ['replace', 'severity', 'reason'], true)) {
                    throw InvalidRule::in($source, $name, "{$where} has \"{$key}\"; it takes \"replace\", \"severity\" and \"reason\".".self::nearest((string) $key, ['replace', 'severity', 'reason']));
                }
            }

            $reason = $entry['reason'] ?? null;

            if ($reason !== null && (! is_string($reason) || trim($reason) === '')) {
                throw InvalidRule::in($source, $name, "{$where} has a \"reason\" that is not a sentence.");
            }

            $words[] = [
                'phrase' => $phrase,
                'replacements' => self::strings($entry['replace'] ?? [], "the replacements for \"{$phrase}\"", $source, $name),
                'severity' => array_key_exists('severity', $entry) ? self::severity($entry['severity'], "the severity of \"{$phrase}\"", $source, $name) : null,
                'reason' => $reason === null ? null : trim($reason),
            ];
        }

        return $words;
    }

    /**
     * @param  array<array-key, mixed>  $raw
     * @return array{pattern: string, flags: string}
     */
    private static function pattern(array $raw, string $source, string $name): array
    {
        if (! is_string($raw['pattern']) || $raw['pattern'] === '') {
            throw InvalidRule::in($source, $name, '"pattern" must be a regular expression, written without the surrounding slashes.');
        }

        $flags = $raw['flags'] ?? '';

        if (! is_string($flags) || preg_match('/^[imsxU]*$/', $flags) !== 1) {
            throw InvalidRule::in($source, $name, '"flags" may use i, m, s, x and U; got '.self::describe($flags).'.');
        }

        return ['pattern' => $raw['pattern'], 'flags' => $flags];
    }

    /**
     * @param  array<array-key, mixed>  $raw
     * @return array{checks: list<string>, quotes: ?string}
     */
    private static function punctuation(array $raw, string $source, string $name): array
    {
        $checks = self::strings($raw['checks'], 'checks', $source, $name, nonEmpty: true);

        foreach ($checks as $check) {
            self::oneOf($check, RuleType::PUNCTUATION_CHECKS, 'a check', $source, $name);
        }

        $quotes = $raw['quotes'] ?? null;

        if (in_array('quotes', $checks, true)) {
            if ($quotes === null) {
                throw InvalidRule::in($source, $name, 'the "quotes" check needs "quotes": curly or straight, whichever the house uses.');
            }

            $quotes = self::oneOf($quotes, ['curly', 'straight'], 'quotes', $source, $name);
        } elseif ($quotes !== null) {
            throw InvalidRule::in($source, $name, '"quotes" says which quotes to use, but "checks" does not include "quotes", so nothing would check them.');
        }

        return ['checks' => $checks, 'quotes' => $quotes];
    }

    /**
     * @param  array<array-key, mixed>  $raw
     * @return array{check: string, max_words: ?int}
     */
    private static function sentence(array $raw, string $source, string $name): array
    {
        $check = self::oneOf($raw['check'], RuleType::SENTENCE_CHECKS, 'check', $source, $name);
        $max = $raw['max_words'] ?? null;

        if ($check === 'max_words') {
            if (! is_int($max) || $max < 1) {
                throw InvalidRule::in($source, $name, 'the max_words check needs "max_words": a whole number of words, at least 1; got '.self::describe($max).'.');
            }
        } elseif ($max !== null) {
            throw InvalidRule::in($source, $name, "\"max_words\" only goes with the max_words check, not \"{$check}\".");
        }

        return ['check' => $check, 'max_words' => $max];
    }

    /**
     * @param  list<string>  $allowed
     */
    private static function oneOf(mixed $value, array $allowed, string $what, string $source, string $name): string
    {
        if (! is_string($value) || ! in_array($value, $allowed, true)) {
            throw InvalidRule::in($source, $name, "{$what} is ".self::describe($value).'; use '.implode(', ', $allowed).'.'.(is_string($value) ? self::nearest($value, $allowed) : ''));
        }

        return $value;
    }

    /**
     * A string or a list of them, as a list. A single string is a list of one,
     * because that is how people write a list of one in YAML.
     *
     * @return list<string>
     */
    private static function strings(mixed $value, string $what, string $source, string $name, bool $nonEmpty = false, bool $allowBlank = false): array
    {
        if (is_string($value)) {
            $value = [$value];
        }

        if ($value === null) {
            $value = [];
        }

        if (! is_array($value) || ! array_is_list($value)) {
            throw InvalidRule::in($source, $name, "\"{$what}\" must be a list; got ".self::describe($value).'.');
        }

        foreach ($value as $item) {
            if (! is_string($item) || (! $allowBlank && trim($item) === '')) {
                throw InvalidRule::in($source, $name, "\"{$what}\" must be a list of text; it has ".self::describe($item).'.');
            }
        }

        if ($nonEmpty && $value === []) {
            throw InvalidRule::in($source, $name, "\"{$what}\" is empty, so the rule would never apply.");
        }

        return $value;
    }

    /**
     * The known key nearest a mistyped one, as a sentence, or nothing.
     *
     * @param  list<string>  $known
     */
    private static function nearest(string $key, array $known): string
    {
        $best = null;
        $distance = 3;

        foreach ($known as $candidate) {
            $d = levenshtein($key, $candidate);

            if ($d < $distance) {
                [$best, $distance] = [$candidate, $d];
            }
        }

        return $best === null ? '' : " Did you mean \"{$best}\"?";
    }

    private static function describe(mixed $value): string
    {
        return match (true) {
            $value === null => 'nothing',
            is_string($value) => "\"{$value}\"",
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value), is_float($value) => (string) $value,
            is_array($value) => array_is_list($value) ? 'a list' : 'a set of keys',
            default => get_debug_type($value),
        };
    }
}
