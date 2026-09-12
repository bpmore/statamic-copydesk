<?php

declare(strict_types=1);

namespace Bpmore\Plain;

use Statamic\Providers\AddonServiceProvider;

/**
 * Plain: readability and plain-language checking for Statamic.
 *
 * The engine is bpmore/readability-core and knows nothing about Statamic;
 * this addon is its surface. The listener that places the panel is not
 * registered here: Statamic discovers anything in `src/Listeners` and
 * binds it to the event named by its `handle()` type hint.
 */
class ServiceProvider extends AddonServiceProvider
{
    protected $config = true;

    protected $fieldtypes = [
        Fieldtypes\ReadabilityPanel::class,
    ];

    /**
     * A plain script, not a Vite entry point: Statamic ships the Vue build
     * that compiles templates at runtime, so this addon needs no npm and
     * no build step. See the panel's own file.
     */
    protected $scripts = [
        __DIR__.'/../resources/js/plain-panel.js',
    ];

    public function register(): void
    {
        parent::register();

        $this->app->singleton(Settings::class, static fn (): Settings => Settings::fromConfig());
    }

    public function bootAddon(): void
    {
        //
    }
}
