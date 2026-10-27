<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Packs;

use Bpmore\Copydesk\Style\InvalidPack;
use Bpmore\Copydesk\Style\Pack;
use Bpmore\Copydesk\Style\PackSet;
use Illuminate\Contracts\Cache\Repository as Cache;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * The packs on disk, read and checked once and then kept.
 *
 * The bundled packs ship in this addon's resources/packs; a site's own are
 * resources/copydesk/*.yaml in the site. Every pack file is read and parsed
 * once, then the checked PackSet is kept in the cache under a key made from
 * every file's path and contents. Editing a pack changes the key, so the
 * next check reads the new rules with no cache to clear (acceptance 7). The
 * key is the contents rather than the modified time, because two saves in
 * one second share a modified time and never share contents; reading a few
 * small files to hash them costs far less than parsing them.
 *
 * Within one request the set is also kept in memory, so a page with ten
 * fields does not hash the files ten times.
 */
final class PackLibrary
{
    private const KEY = 'copydesk.packs.';

    /** @var array<string, PackSet> */
    private array $memo = [];

    public function __construct(
        private readonly string $bundled,
        private readonly string $site,
        private readonly Cache $cache,
    ) {}

    public function set(): PackSet
    {
        $files = $this->files();
        $key = self::KEY.sha1(implode("\n", array_map(static fn (string $f): string => $f.':'.md5_file($f), $files)));

        if (isset($this->memo[$key])) {
            return $this->memo[$key];
        }

        $set = $this->cache->get($key);

        if (! $set instanceof PackSet) {
            $set = new PackSet(array_map($this->read(...), $files));
            $this->cache->forever($key, $set);
        }

        return $this->memo[$key] = $set;
    }

    /** @return list<string> the names of the packs Copydesk ships */
    public function bundledNames(): array
    {
        return self::names($this->bundled);
    }

    /** @return list<string> the names of the site's own packs */
    public function siteNames(): array
    {
        return self::names($this->site);
    }

    /** Where a site pack of this name lives, whether or not it exists yet. */
    public function sitePath(string $name): string
    {
        return rtrim($this->site, '/').'/'.$name.'.yaml';
    }

    /**
     * Every pack, read now, with this one pack as given instead of as the
     * file has it: what the rule editor checks a pack against before it
     * saves it, and tries sample text with.
     */
    public function setWith(Pack $pack): PackSet
    {
        $packs = array_values(array_filter(
            array_map($this->read(...), $this->files()),
            static fn (Pack $p): bool => $p->name !== $pack->name,
        ));

        return new PackSet([...$packs, $pack]);
    }

    /** @return list<string> */
    private static function names(string $directory): array
    {
        $files = is_dir($directory) ? (glob(rtrim($directory, '/').'/*.yaml') ?: []) : [];
        sort($files);

        return array_map(static fn (string $f): string => basename($f, '.yaml'), $files);
    }

    /**
     * Every pack file, bundled first, then the site's, each in name order.
     *
     * @return list<string>
     */
    public function files(): array
    {
        $files = [];

        foreach ([$this->bundled, $this->site] as $directory) {
            $found = is_dir($directory) ? (glob(rtrim($directory, '/').'/*.yaml') ?: []) : [];
            sort($found);
            array_push($files, ...$found);
        }

        return $files;
    }

    private function read(string $file): Pack
    {
        $name = basename($file, '.yaml');
        $source = $this->shown($file);

        try {
            $raw = Yaml::parseFile($file);
        } catch (ParseException $e) {
            throw new InvalidPack("{$source}: this is not YAML that can be read. {$e->getMessage()}");
        }

        return Pack::fromArray($name, $raw, $source);
    }

    /** A file as a person would look for it: relative to the site or the addon. */
    private function shown(string $file): string
    {
        foreach ([dirname($this->site, 2) => '', dirname($this->bundled, 2) => 'copydesk/'] as $root => $prefix) {
            if (str_starts_with($file, $root.'/')) {
                return $prefix.substr($file, strlen($root) + 1);
            }
        }

        return $file;
    }
}
