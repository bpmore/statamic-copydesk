<?php

declare(strict_types=1);

namespace Bpmore\Plain;

use Bpmore\Plain\Gate\GateSettings;
use Bpmore\ReadabilityCore\Exclusion\Pipeline;
use Bpmore\ReadabilityCore\Formula\Target;
use Bpmore\ReadabilityCore\Locale\LocaleGuard;
use Bpmore\ReadabilityCore\Rule\Dictionary;
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
    public const CONFIG = 'statamic-plain';

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

    public function target(): Target
    {
        return Target::fromConfig((array) ($this->config['target'] ?? []));
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
     * The rules, with the jargon rule on the given dictionary rather than
     * the bare starter: the site's own entries come from the settings
     * screen, not the config file.
     */
    public function rules(?Dictionary $dictionary = null): RuleSet
    {
        $config = (array) ($this->config['rules'] ?? []);
        $rules = RuleSet::fromConfig($config);

        if ($dictionary === null || ! in_array(Jargon::HANDLE, $rules->handles(), true)) {
            return $rules;
        }

        $options = is_array($config[Jargon::HANDLE] ?? null) ? $config[Jargon::HANDLE] : [];

        return $rules->without(Jargon::HANDLE)->with(new Jargon($options, $dictionary));
    }

    public function gate(): GateSettings
    {
        return GateSettings::fromConfig((array) ($this->config['gate'] ?? []));
    }
}
