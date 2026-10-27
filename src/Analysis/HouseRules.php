<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Analysis;

use Bpmore\Copydesk\Dictionaries;
use Bpmore\Copydesk\Edition;
use Bpmore\Copydesk\Packs\PackLibrary;
use Bpmore\Copydesk\Settings;
use Bpmore\Copydesk\Style\RuleDefinition;
use Bpmore\Copydesk\Style\RuleFactory;
use Bpmore\Copydesk\Style\RuleType;
use Bpmore\Copydesk\Style\StyleRule;
use Bpmore\ReadabilityCore\Rule\Dictionary;
use Bpmore\ReadabilityCore\Rule\DictionaryEntry;

/**
 * The house rules a check runs: the packs a site has turned on, and the
 * site's own word list.
 *
 * Packs: the `packs` setting when a site lists them; otherwise the default
 * bundled packs (core punctuation, plain language, inclusive language), then
 * every pack in resources/copydesk. Resolved for the collection, so a
 * pack's collection changes apply.
 *
 * The site's word list is the dictionary Plain kept on its settings screen,
 * now run as a `word_list` rule. Its id stays `jargon`, so the gate, the
 * site report and the CSV, which all count by that id, read it as before.
 * A word with nothing to say instead is an error (Plain's gate stopped a
 * publish for those); one with a replacement takes the configured severity.
 * The engine's own jargon rule is not run beside it: the plain-language
 * pack carries the same starter list.
 *
 * Copydesk Free runs only punctuation and term rules from the packs (the
 * core-punctuation pack, and a site's own names); the rest is Pro.
 */
final class HouseRules
{
    public const SITE_WORDS = 'jargon';

    /** @var list<string> */
    public const DEFAULT_PACKS = ['core-punctuation', 'plain-language', 'inclusive-language'];

    /** @var list<RuleType> the kinds of rule Copydesk Free runs */
    public const FREE_TYPES = [RuleType::Punctuation, RuleType::Term];

    public function __construct(
        private readonly PackLibrary $library,
        private readonly Settings $settings,
        private readonly Dictionaries $dictionaries,
    ) {}

    /**
     * @return list<StyleRule>
     */
    public function for(?string $collection, ?string $site): array
    {
        $set = $this->library->set();
        $rules = $set->runnable($this->roots(), $collection);

        // Free runs punctuation and term rules, from any pack; every other
        // kind is Pro. The site's own word list stays: it is the dictionary
        // Plain always had.
        if (! Edition::isPro()) {
            $rules = array_values(array_filter($rules, static fn (StyleRule $rule): bool => in_array($rule->definition->type, self::FREE_TYPES, true)));
        }

        $words = $this->siteWords($site);

        return $words === null ? $rules : [...$rules, $words];
    }

    /**
     * The packs turned on.
     *
     * @return list<string>
     */
    public function roots(): array
    {
        $configured = $this->settings->packs();

        if ($configured !== null) {
            return $configured;
        }

        $bundled = $this->library->bundledNames();

        return [
            ...array_values(array_filter(self::DEFAULT_PACKS, static fn (string $name): bool => in_array($name, $bundled, true))),
            ...$this->library->siteNames(),
        ];
    }

    /**
     * The site's word list as a rule, or null when there is nothing on it
     * or it is turned off.
     */
    private function siteWords(?string $site): ?StyleRule
    {
        $options = $this->settings->siteWords();

        if (($options['enabled'] ?? true) === false) {
            return null;
        }

        $dictionary = is_array($options['dictionary'] ?? null) ? Dictionary::fromArray($options['dictionary']) : Dictionary::empty();

        if ($site !== null) {
            $dictionary = $dictionary->merge($this->dictionaries->own($site));
        }

        if ($dictionary->entries() === []) {
            return null;
        }

        $severity = is_string($options['severity'] ?? null) ? ($options['severity'] === 'tip' ? 'info' : $options['severity']) : 'warning';
        $words = [];

        foreach ($dictionary->entries() as $entry) {
            /** @var DictionaryEntry $entry */
            $words[$entry->term] = [
                'replace' => $entry->replacements,
                'severity' => $entry->replacements === [] ? 'error' : $severity,
                // The note, after saying where the word comes from, so the
                // author knows it is the site's own choice.
                'reason' => $entry->note === null ? null : 'It is on this site\'s list of words to avoid. '.$entry->note,
            ];
        }

        return RuleFactory::make(RuleDefinition::fromArray([
            'id' => self::SITE_WORDS,
            'type' => 'word_list',
            'message' => 'It is on this site\'s list of words to avoid.',
            'words' => $words,
        ], 'the site\'s word list'));
    }
}
