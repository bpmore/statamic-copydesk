<?php

declare(strict_types=1);

use Bpmore\Copydesk\Edition;
use Statamic\Facades\Addon;

/**
 * Free and Pro. Nothing is gated on the edition yet; every Pro feature will
 * ask Edition::isPro(), and these are the answers it gets.
 */
it('offers Free and Pro, Free first, so a site that chose nothing runs Free', function () {
    $manifest = json_decode((string) file_get_contents(__DIR__.'/../../composer.json'), true);

    expect($manifest['extra']['statamic']['editions'])->toBe(['free', 'pro'])
        ->and(Addon::get(Edition::PACKAGE)->editions()->all())->toBe(['free', 'pro']);
});

it('runs Free when the site has not chosen', function () {
    expect(Edition::current())->toBe('free')
        ->and(Edition::isPro())->toBeFalse();
});

it('runs Pro when the site chooses it', function () {
    config()->set('statamic.editions.addons.'.Edition::PACKAGE, 'pro');

    expect(Edition::current())->toBe('pro')
        ->and(Edition::isPro())->toBeTrue();
});

it('runs Free when the site chooses it by name', function () {
    config()->set('statamic.editions.addons.'.Edition::PACKAGE, 'free');

    expect(Edition::isPro())->toBeFalse();
});

it('refuses an edition that does not exist, rather than guess', function () {
    config()->set('statamic.editions.addons.'.Edition::PACKAGE, 'premium');

    expect(fn () => Edition::isPro())->toThrow(Exception::class, 'Invalid edition [premium]');
});
