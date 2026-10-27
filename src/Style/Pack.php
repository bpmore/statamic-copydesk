<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Style;

/**
 * One rule pack, checked.
 *
 * A pack is a YAML file with these keys, all optional:
 *
 *     description: What the pack is for, in a sentence.
 *     extends: [core-punctuation, plain-language]   # packs whose rules come first
 *     disable: [no-em-dash]                        # rule ids, from this pack or one it extends, to turn off
 *     rules:                                       # this pack's own rules; one with an id already seen replaces it
 *       - id: ...
 *     collections:                                 # changes for one collection only, applied last
 *       patient_education:
 *         disable: [...]
 *         rules: [...]
 *
 * A pack's name is its file name without `.yaml`. Every rule is checked as
 * the pack is read, so a bad rule stops the load with its file and id. Two
 * rules with the same id in one pack is a mistake and is said so; across
 * packs it is how a later pack replaces an earlier pack's rule.
 */
final class Pack
{
    /** @var list<string> */
    private const KEYS = ['description', 'extends', 'disable', 'rules', 'collections'];

    /**
     * @param  list<string>  $extends
     * @param  list<string>  $disable
     * @param  list<RuleDefinition>  $rules
     * @param  array<string, array{disable: list<string>, rules: list<RuleDefinition>}>  $collections
     */
    public function __construct(
        public readonly string $name,
        public readonly string $source,
        public readonly ?string $description,
        public readonly array $extends,
        public readonly array $disable,
        public readonly array $rules,
        public readonly array $collections,
    ) {}

    /**
     * @param  mixed  $raw  the parsed YAML
     * @param  string  $source  where it came from, for messages: "resources/copydesk/house.yaml"
     */
    public static function fromArray(string $name, mixed $raw, string $source): self
    {
        if ($raw === null) {
            $raw = [];
        }

        if (! is_array($raw) || ($raw !== [] && array_is_list($raw))) {
            throw new InvalidPack("{$source}: a pack is a set of keys (extends, disable, rules, collections), not a list.");
        }

        foreach (array_keys($raw) as $key) {
            if (! in_array($key, self::KEYS, true)) {
                throw new InvalidPack("{$source}: \"{$key}\" is not a key a pack has; use ".implode(', ', self::KEYS).'.'.self::nearest((string) $key, self::KEYS));
            }
        }

        $description = $raw['description'] ?? null;

        if ($description !== null && ! is_string($description)) {
            throw new InvalidPack("{$source}: \"description\" must be a sentence.");
        }

        $collections = [];

        foreach (self::map($raw['collections'] ?? [], 'collections', $source) as $collection => $changes) {
            $where = "{$source}, collection \"{$collection}\"";
            $changes = self::map($changes, "collections.{$collection}", $source);

            foreach (array_keys($changes) as $key) {
                if (! in_array($key, ['disable', 'rules'], true)) {
                    throw new InvalidPack("{$where}: \"{$key}\" is not a key here; use disable, rules.".self::nearest((string) $key, ['disable', 'rules']));
                }
            }

            $collections[(string) $collection] = [
                'disable' => self::names($changes['disable'] ?? [], 'disable', $where),
                'rules' => self::rules($changes['rules'] ?? [], $where),
            ];
        }

        return new self(
            name: $name,
            source: $source,
            description: $description === null ? null : trim($description),
            extends: self::names($raw['extends'] ?? [], 'extends', $source),
            disable: self::names($raw['disable'] ?? [], 'disable', $source),
            rules: self::rules($raw['rules'] ?? [], $source),
            collections: $collections,
        );
    }

    /**
     * @return list<RuleDefinition>
     */
    private static function rules(mixed $raw, string $source): array
    {
        if ($raw === null) {
            return [];
        }

        if (! is_array($raw) || ($raw !== [] && ! array_is_list($raw))) {
            throw new InvalidPack("{$source}: \"rules\" must be a list, each rule starting with \"- id:\".");
        }

        $rules = [];
        $seen = [];

        foreach ($raw as $i => $rule) {
            $definition = RuleDefinition::fromArray($rule, $source, $i + 1);

            if (isset($seen[$definition->id])) {
                throw new InvalidPack("{$source}: two rules are called \"{$definition->id}\" (#{$seen[$definition->id]} and #".($i + 1).'); give one another id.');
            }

            $seen[$definition->id] = $i + 1;
            $rules[] = $definition;
        }

        return $rules;
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function map(mixed $raw, string $what, string $source): array
    {
        if ($raw === null) {
            return [];
        }

        if (! is_array($raw) || ($raw !== [] && array_is_list($raw))) {
            throw new InvalidPack("{$source}: \"{$what}\" must be a set of keys, not a list.");
        }

        return $raw;
    }

    /**
     * @return list<string>
     */
    private static function names(mixed $raw, string $what, string $source): array
    {
        if (is_string($raw)) {
            $raw = [$raw];
        }

        if ($raw === null) {
            return [];
        }

        if (! is_array($raw) || ! array_is_list($raw)) {
            throw new InvalidPack("{$source}: \"{$what}\" must be a list of names.");
        }

        foreach ($raw as $name) {
            if (! is_string($name) || trim($name) === '') {
                throw new InvalidPack("{$source}: \"{$what}\" must be a list of names.");
            }
        }

        return array_values(array_map(trim(...), $raw));
    }

    /**
     * @param  list<string>  $known
     */
    public static function nearest(string $name, array $known): string
    {
        $best = null;
        $distance = 3;

        foreach ($known as $candidate) {
            $d = levenshtein($name, $candidate);

            if ($d < $distance) {
                [$best, $distance] = [$candidate, $d];
            }
        }

        return $best === null ? '' : " Did you mean \"{$best}\"?";
    }
}
