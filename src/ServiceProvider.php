<?php

declare(strict_types=1);

namespace Bpmore\Plain;

use Bpmore\Plain\Http\Controllers\DictionaryController;
use Statamic\Facades\Site;
use Statamic\Facades\Utility;
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

    protected $viewNamespace = 'plain';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(Settings::class, static fn (): Settings => Settings::fromConfig());
        $this->app->bind(Gate\GateSettings::class, static fn ($app): Gate\GateSettings => $app->make(Settings::class)->gate());
    }

    /**
     * A page under Tools for the dictionary's file in and file out, which
     * the settings screen's form cannot do. The page and its two routes
     * live under the utility, so its permission covers them.
     */
    public function bootAddon(): void
    {
        Utility::extend(fn () => Utility::register(
            Utility::make('plain')
                ->title('Readability')
                ->navTitle('Readability')
                ->icon('fieldtype-textarea')
                ->description('Export a site\'s dictionary of words to avoid, or import one.')
                ->view('plain::utilities.dictionary', function () {
                    $dictionaries = $this->app->make(Dictionaries::class);

                    return [
                        'sites' => Site::all()->values(),
                        'counts' => Site::all()->mapWithKeys(fn ($site) => [$site->handle() => count($dictionaries->own($site->handle()))])->all(),
                    ];
                })
                ->routes(function ($router) {
                    $router->get('export/{site}', [DictionaryController::class, 'export'])->name('export');
                    $router->post('import/{site}', [DictionaryController::class, 'import'])->name('import');
                })
        ));
    }
}
