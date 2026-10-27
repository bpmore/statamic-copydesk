<?php

declare(strict_types=1);

namespace Bpmore\Copydesk;

use Bpmore\Copydesk\Gate\GateSettings;
use Bpmore\ReadabilityCore\Exclusion\Pipeline;
use Bpmore\ReadabilityCore\Formula\Target;
use Bpmore\ReadabilityCore\Locale\LocaleGuard;
use Bpmore\ReadabilityCore\Rule\Jargon;
use Bpmore\ReadabilityCore\Rule\RuleSet;

/**
 * The addon's settings, read once from config and passed around as a value,
 * so a test can state the setting it is testing rather than edit global
 * state, and so the engine's own readers of each section are the only
 * readers.
 */
final class Settings
{
    public const CONFIG = 'copydesk';

    /**
     * @param  array<string, mixed>  $config  the whole config file
     */
    public function __construct(private readonly array $config) {}

    public static function fromConfig(): self
    {
        return new self((array) config(self::CONFIG, []));
    }

    public function enabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? true);
    }

    public function localeGuard(): LocaleGuard
    {
        return LocaleGuard::fromConfig($this->config);
    }

    /**
     * The reading level to write for: the collection's own target when
     * `targets` names one, otherwise the site's. A collection's target is a
     * grade, with the site's tolerance either side.
     */
    public function target(?string $collection = null): Target
    {
        $site = (array) ($this->config['target'] ?? []);
        $grade = $collection === null ? null : ($this->config['targets'][$collection] ?? null);

        return Target::fromConfig(is_numeric($grade) ? ['grade' => (int) $grade, 'tolerance' => $site['tolerance'] ?? 1] : $site);
    }

    /**
     * The packs a site has turned on, or null for the default: Copydesk's
     * own (see HouseRules) and every pack in resources/copydesk.
     *
     * @return list<string>|null
     */
    public function packs(): ?array
    {
        $packs = $this->config['packs'] ?? null;

        return is_array($packs) ? array_values(array_map(strval(...), $packs)) : null;
    }

    /**
     * Options for the site's word list: `enabled`, `severity`, and an
     * optional `dictionary` of extra words, as the engine's jargon rule read
     * them. Kept under `rules.jargon`, where Plain had them.
     *
     * @return array<string, mixed>
     */
    public function siteWords(): array
    {
        return (array) ($this->config['rules']['jargon'] ?? []);
    }

    /**
     * When a paragraph is marked as reading well above the target: how many
     * grades above the top of the target, and the fewest words a paragraph
     * needs before its grade means anything.
     *
     * @return array{enabled: bool, above_by: int, min_words: int}
     */
    public function paragraphs(): array
    {
        $options = (array) ($this->config['paragraphs'] ?? []);

        return [
            'enabled' => (bool) ($options['enabled'] ?? true),
            'above_by' => max(0, (int) ($options['above_by'] ?? 3)),
            'min_words' => max(1, (int) ($options['min_words'] ?? 40)),
        ];
    }

    /**
     * The fields to read for a blueprint: the ones for every blueprint, and
     * the ones for this one.
     *
     * @return list<string>
     */
    public function fieldsFor(string $blueprint): array
    {
        $fields = (array) ($this->config['fields'] ?? ['*' => ['content']]);

        return array_values(array_unique(array_filter(array_map(strval(...), [
            ...(array) ($fields['*'] ?? []),
            ...(array) ($fields[$blueprint] ?? []),
        ]))));
    }

    /**
     * @return array<string, mixed>
     */
    public function exclude(): array
    {
        return (array) ($this->config['exclude'] ?? []);
    }

    public function exclusions(): Pipeline
    {
        return Pipeline::fromConfig($this->exclude());
    }

    /**
     * @return list<string>
     */
    public function ignoredClasses(): array
    {
        return array_values(array_filter(array_map(strval(...), (array) ($this->exclude()['html_classes'] ?? []))));
    }

    /**
     * The engine's readability rules. Not its jargon rule: the site's word
     * list and the plain-language pack are house rules now (HouseRules), and
     * running both would report every word twice.
     */
    public function rules(): RuleSet
    {
        return RuleSet::fromConfig((array) ($this->config['rules'] ?? []))->without(Jargon::HANDLE);
    }

    public function gate(): GateSettings
    {
        return GateSettings::fromConfig((array) ($this->config['gate'] ?? []));
    }
}
