<?php

declare(strict_types=1);

namespace Bpmore\Plain;

use Bpmore\Plain\Gate\GateSettings;
use Bpmore\Plain\Http\Controllers\DictionaryController;
use Bpmore\Plain\Scan\ScanHistory;
use Bpmore\Plain\Scan\SiteScan;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Gate as Access;
use Statamic\Addons\Addon;
use Statamic\Facades\Permission;
use Statamic\Facades\Site;
use Statamic\Facades\User;
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

    protected $commands = [
        Commands\CheckCommand::class,
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
        $this->app->bind(GateSettings::class, static fn ($app): GateSettings => $app->make(Settings::class)->gate());
        $this->app->singleton(ScanHistory::class, static fn ($app): ScanHistory => ScanHistory::inStorage($app->make(Filesystem::class)));

        // The Readability band on Site Weather's tile. Tagged by name, as a
        // string: if Site Weather is not installed nothing resolves the tag,
        // the class is never autoloaded, and its missing interface is never
        // noticed. Site Weather is a suggestion, not a dependency.
        $this->app->tag(Weather\ReadabilityContributor::class, 'site-weather.contributors');
    }

    /**
     * A page under Tools: the site's pages graded, hardest first, the
     * acronyms and jargon that come up most, and the dictionary's file in
     * and file out. Its routes live under the utility, so its permission
     * covers them.
     */
    public function bootAddon(): void
    {
        $this->bootPermissions();

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
                        'canManage' => (bool) User::current()?->can('manage jargon dictionary'),
                        'companion' => Companion::notice(),
                    ];
                })
                ->routes(function ($router) {
                    $router->post('rescan/{site}', [DictionaryController::class, 'rescan'])->name('rescan');
                    $router->get('export/{site}', [DictionaryController::class, 'export'])->name('export');
                    $router->post('import/{site}', [DictionaryController::class, 'import'])->name('import');
                })
        ));
    }

    /**
     * Three permissions, each with a meaning of its own beside the two
     * Statamic gives every addon (access to the utility page, and editing
     * the addon's settings):
     *
     * - `view readability`: check a page from the publish form; see the
     *   dashboard widget. Not the utility page, which is Statamic's own
     *   "access utility" permission, nor the tag, which is the front end.
     * - `manage jargon dictionary`: import and export the dictionary, and
     *   open the settings screen, where its rows are edited.
     * - `configure readability`: the settings screen as a whole.
     *
     * The settings screen is Statamic's; its controller asks its own
     * policy whether a user may edit this addon's settings, and the policy
     * asks for "edit bpmore/statamic-plain settings". For this addon that
     * question is answered here instead, with the two permissions above,
     * so a site's roles speak this addon's language. Statamic's own gate
     * hook only handles abilities that are registered permissions, so a
     * super user is let through here as well.
     */
    private function bootPermissions(): void
    {
        Permission::extend(function () {
            Permission::group('plain', 'Plain', function () {
                Permission::register('view readability')
                    ->label('Check readability')
                    ->description('Grade a page from the publish form, and see the Readability widget on the dashboard.');
                Permission::register('manage jargon dictionary')
                    ->label('Manage the dictionary')
                    ->description('Add, change, import and export the words to avoid and what to say instead.');
                Permission::register('configure readability')
                    ->label('Configure readability')
                    ->description('Change the addon\'s settings: the dictionary today, and the target and gate as they move onto the settings screen.');
            });
        });

        Access::before(function ($user, string $ability, array $arguments) {
            if ($ability !== 'editSettings' || ! (($arguments[0] ?? null) instanceof Addon) || $arguments[0]->id() !== 'bpmore/statamic-plain') {
                return null;
            }

            $user = User::fromUser($user);

            return $user?->isSuper()
                || $user?->hasPermission('configure readability')
                || $user?->hasPermission('manage jargon dictionary');
        });
    }
}
