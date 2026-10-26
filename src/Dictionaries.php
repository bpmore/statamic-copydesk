<?php

declare(strict_types=1);

namespace Bpmore\Copydesk;

use Bpmore\ReadabilityCore\Rule\Dictionary;
use Bpmore\ReadabilityCore\Rule\DictionaryEntry;
use Statamic\Contracts\Addons\SettingsRepository;
use Statamic\Facades\Addon;

/**
 * The site's dictionaries: the starter, with what the site added on top,
 * per site.
 *
 * The site's own entries are rows in the addon's settings, one file in
 * `resources/addons`, edited on the settings screen and kept in version
 * control with the rest of the site. Each row names the sites it is for,
 * or none for every site. A site's dictionary is the starter, then the
 * rows for every site, then the rows for that site, later ones winning.
 *
 * Import and export move a site's own entries in and out as the array
 * shape the engine's Dictionary reads and writes, so a list built on one
 * site can be carried to another, and an afternoon's institutional
 * knowledge is never trapped in one install.
 */
final class Dictionaries
{
    public const PACKAGE = 'bpmore/statamic-copydesk';

    public const SETTING = 'dictionary';

    public function __construct(private readonly SettingsRepository $repository) {}

    public function starter(): Dictionary
    {
        return Dictionary::starter();
    }

    /** The dictionary the rules use for a site: the starter with the site's own entries laid over it. */
    public function forSite(string $site): Dictionary
    {
        return $this->starter()->merge($this->own($site));
    }

    /** What the site added itself, for a site: nothing from the starter. */
    public function own(string $site): Dictionary
    {
        $everywhere = [];
        $here = [];

        foreach ($this->rows() as $row) {
            $sites = $row['sites'];

            if ($sites === []) {
                $everywhere[] = self::entry($row);
            } elseif (in_array($site, $sites, true)) {
                $here[] = self::entry($row);
            }
        }

        return (new Dictionary($everywhere))->merge(new Dictionary($here));
    }

    /**
     * A site's own entries, in the shape fromArray() reads: what a file holds.
     *
     * @return array<string, mixed>
     */
    public function export(string $site): array
    {
        return $this->own($site)->toArray();
    }

    /**
     * Take a file's entries into a site's dictionary. Replacing drops the
     * rows that were for this site alone first; merging lays the file over
     * them, the file winning on a term both have. Rows for every site are
     * left alone either way.
     *
     * @param  array<string, mixed>  $terms  the shape fromArray() reads
     * @return int how many entries the site has of its own afterwards
     */
    public function import(string $site, array $terms, bool $replace): int
    {
        $incoming = Dictionary::fromArray($terms);
        $kept = [];
        $existing = [];

        foreach ($this->rows() as $row) {
            if ($row['sites'] === [$site]) {
                $existing[] = self::entry($row);
            } else {
                $kept[] = $row;
            }
        }

        $mine = $replace ? $incoming : (new Dictionary($existing))->merge($incoming);

        foreach ($mine->entries() as $entry) {
            $kept[] = ['term' => $entry->term, 'say' => $entry->replacements, 'note' => $entry->note ?? '', 'sites' => [$site]];
        }

        $settings = Addon::get(self::PACKAGE)->settings();
        $settings->set(self::SETTING, $kept);
        $this->repository->save($settings);

        return count($mine);
    }

    /**
     * The rows on the settings screen, tidied: a term, a list of things to
     * say, a note, and the sites it is for.
     *
     * @return list<array{term: string, say: list<string>, note: string, sites: list<string>}>
     */
    public function rows(): array
    {
        $rows = [];

        foreach ((array) Addon::get(self::PACKAGE)->settings()->get(self::SETTING, []) as $row) {
            if (! is_array($row) || trim((string) ($row['term'] ?? '')) === '') {
                continue;
            }

            $rows[] = [
                'term' => trim((string) $row['term']),
                'say' => array_values(array_filter(array_map(static fn ($s): string => trim((string) $s), (array) ($row['say'] ?? [])), static fn (string $s): bool => $s !== '')),
                'note' => trim((string) ($row['note'] ?? '')),
                'sites' => array_values(array_filter(array_map(strval(...), (array) ($row['sites'] ?? [])))),
            ];
        }

        return $rows;
    }

    /**
     * @param  array{term: string, say: list<string>, note: string, sites: list<string>}  $row
     */
    private static function entry(array $row): DictionaryEntry
    {
        return new DictionaryEntry($row['term'], $row['say'], $row['note'] === '' ? null : $row['note']);
    }
}
