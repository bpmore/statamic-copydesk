<?php

declare(strict_types=1);

namespace Bpmore\Plain;

use Bpmore\ReadabilityCore\Exclusion\Pipeline;
use Bpmore\ReadabilityCore\Formula\Target;
use Bpmore\ReadabilityCore\Locale\LocaleGuard;
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

    public function rules(): RuleSet
    {
        return RuleSet::fromConfig((array) ($this->config['rules'] ?? []));
    }

    /**
     * @return array<string, mixed>
     */
    public function gate(): array
    {
        return (array) ($this->config['gate'] ?? []);
    }
}
