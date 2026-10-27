<?php

declare(strict_types=1);

use Bpmore\Copydesk\ServiceProvider;
use Bpmore\ReadabilityCore\Text\EnglishSegmenter;
use Statamic\Facades\Addon;

it('boots as a Statamic addon', function () {
    expect($this->app->getProvider(ServiceProvider::class))->toBeInstanceOf(ServiceProvider::class);

    $addon = Addon::get('bpmore/statamic-copydesk');

    expect($addon)->not->toBeNull();
    expect($addon->id())->toBe('bpmore/statamic-copydesk');
    expect($addon->slug())->toBe('copydesk');

    // The test harness's manifest has no display name; the real one reads it from here.
    $manifest = json_decode((string) file_get_contents(__DIR__.'/../../composer.json'), true);
    expect($manifest['extra']['statamic']['name'])->toBe('Copydesk');
    expect($manifest['extra']['laravel']['providers'])->toBe([ServiceProvider::class]);
});

it('has the engine on its autoloader', function () {
    expect((new EnglishSegmenter)->sentences('Dr. Smith went to St. Louis Inc. yesterday. He came back today.'))->toHaveCount(2);
});

it('ships a Vite manifest where the control panel looks for it', function () {
    // Statamic's registerVite points Laravel's Vite at <buildDirectory>, and
    // Laravel reads <buildDirectory>/manifest.json. A missing or misplaced
    // manifest throws "Vite manifest not found" on every control panel page.
    $build = __DIR__.'/../../resources/dist/build';

    expect(is_file($build.'/manifest.json'))->toBeTrue('no manifest.json in resources/dist/build: run `npm run build`');

    $manifest = json_decode((string) file_get_contents($build.'/manifest.json'), true);

    expect($manifest)->toBeArray()->toHaveKey('resources/js/cp.js');

    foreach ($manifest as $entry) {
        if (isset($entry['file'])) {
            expect(is_file($build.'/'.$entry['file']))->toBeTrue("manifest names {$entry['file']}, which is not in the build");
        }
    }
});
