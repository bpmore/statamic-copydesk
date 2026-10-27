<?php

declare(strict_types=1);

use Bpmore\Copydesk\Packs\PackLibrary;
use Bpmore\Copydesk\Style\InvalidPack;
use Illuminate\Support\Facades\File;

/**
 * The packs on disk, read once and kept, and read again the moment one
 * changes (acceptance 7).
 */
beforeEach(function () {
    $this->site = resource_path('copydesk');
    File::deleteDirectory($this->site);
    File::ensureDirectoryExists($this->site);
    $this->library = fn () => new PackLibrary(__DIR__.'/../__fixtures__/packs', $this->site, cache()->store());
});

afterEach(fn () => File::deleteDirectory($this->site));

function housePack(string $severity): string
{
    return <<<YAML
    extends: [fixture-punctuation]
    rules:
      - id: house-names
        type: term
        terms: [eiSEO]
        message: Spell it eiSEO.
        severity: {$severity}
    YAML;
}

it('reads the bundled packs, then the site\'s', function () {
    File::put($this->site.'/house.yaml', housePack('warning'));

    $set = ($this->library)()->set();

    expect($set->names())->toBe(['fixture-punctuation', 'house'])
        ->and(array_map(fn ($r) => $r->id, $set->rules(['house'])))->toBe(['no-em-dash', 'house-names']);
});

it('takes a changed pack on the next read, with no cache cleared (acceptance 7)', function () {
    File::put($this->site.'/house.yaml', housePack('warning'));
    $library = ($this->library)();

    expect($library->set()->rules(['house'])[1]->severity->value)->toBe('warning');

    // Same length, same second: only the contents tell them apart.
    File::put($this->site.'/house.yaml', housePack('error  '));
    File::put($this->site.'/house.yaml', housePack('error'));

    expect(($this->library)()->set()->rules(['house'])[1]->severity->value)->toBe('error')
        ->and($library->set()->rules(['house'])[1]->severity->value)->toBe('error');
});

it('keeps the checked set in the cache, under a key made from the files\' contents', function () {
    File::put($this->site.'/house.yaml', housePack('warning'));
    $library = ($this->library)();
    $set = $library->set();

    $key = 'copydesk.packs.'.sha1(implode("\n", array_map(fn (string $f) => $f.':'.md5_file($f), $library->files())));

    expect(cache()->get($key))->toEqual($set)
        ->and(($this->library)()->set())->toEqual($set);
});

it('names the file when one is not YAML it can read', function () {
    File::put($this->site.'/broken.yaml', "rules:\n  - id: [unclosed\n");

    expect(fn () => ($this->library)()->set())->toThrow(InvalidPack::class, 'resources/copydesk/broken.yaml: this is not YAML that can be read.');
});

it('reads no site packs when the folder is not there', function () {
    File::deleteDirectory($this->site);

    expect(($this->library)()->set()->names())->toBe(['fixture-punctuation']);
});
