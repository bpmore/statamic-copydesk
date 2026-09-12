<?php

declare(strict_types=1);

use Bpmore\Plain\ServiceProvider;
use Bpmore\ReadabilityCore\Text\EnglishSegmenter;
use Statamic\Facades\Addon;

it('boots as a Statamic addon', function () {
    expect($this->app->getProvider(ServiceProvider::class))->toBeInstanceOf(ServiceProvider::class);

    $addon = Addon::get('bpmore/statamic-plain');

    expect($addon)->not->toBeNull();
    expect($addon->id())->toBe('bpmore/statamic-plain');
    expect($addon->slug())->toBe('statamic-plain');

    // The test harness's manifest has no display name; the real one reads it from here.
    $manifest = json_decode((string) file_get_contents(__DIR__.'/../../composer.json'), true);
    expect($manifest['extra']['statamic']['name'])->toBe('Plain');
    expect($manifest['extra']['laravel']['providers'])->toBe([ServiceProvider::class]);
});

it('has the engine on its autoloader, from the path repository', function () {
    expect((new EnglishSegmenter)->sentences('Dr. Smith went to St. Louis Inc. yesterday. He came back today.'))->toHaveCount(2);
});
