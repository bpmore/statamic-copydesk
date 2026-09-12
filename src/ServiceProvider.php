<?php

declare(strict_types=1);

namespace Bpmore\Plain;

use Bpmore\Plain\Http\Controllers\DictionaryController;
use Bpmore\Plain\Scan\ScanHistory;
use Bpmore\Plain\Scan\SiteScan;
use Illuminate\Filesystem\Filesystem;
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

    /** The dashboard widget. Add `readability` to the widgets in `config/statamic/cp.php`. */
    protected $widgets = [
        Widgets\Readability::class,
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
        $this->app->singleton(ScanHistory::class, static fn ($app): ScanHistory => ScanHistory::inStorage($app->make(Filesystem::class)));
    }

    /**
     * A page under Tools: the site's pages graded, hardest first, the
     * acronyms and jargon that come up most, and the dictionary's file in
     * and file out. Its routes live under the utility, so its permission
     * covers them.
     */
    public function bootAddon(): void
    {
        Utility::extend(fn () => Utility::register(
            Utility::make('plain')
                ->title('Readability')
                ->navTitle('Readability')
                ->icon('fieldtype-textarea')
                ->description('How the site\'s pages read, which are hardest, and the words to avoid.')
                ->view('plain::utilities.readability', function () {
                    $dictionaries = $this->app->make(Dictionaries::class);
                    $site = Site::selected();

                    return [
                        'site' => $site,
                        'scan' => $this->app->make(SiteScan::class)->cached($site->handle()),
                        'sites' => Site::all()->values(),
                        'counts' => Site::all()->mapWithKeys(fn ($s) => [$s->handle() => count($dictionaries->own($s->handle()))])->all(),
                    ];
                })
                ->routes(function ($router) {
                    $router->post('rescan/{site}', [DictionaryController::class, 'rescan'])->name('rescan');
                    $router->get('export/{site}', [DictionaryController::class, 'export'])->name('export');
                    $router->post('import/{site}', [DictionaryController::class, 'import'])->name('import');
                })
        ));
    }
}
