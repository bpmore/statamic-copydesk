<?php

declare(strict_types=1);

namespace Bpmore\Copydesk;

use Bpmore\Copydesk\Gate\A11yGateBridge;
use Bpmore\Copydesk\Gate\CopydeskGateContributor;
use Bpmore\Copydesk\Gate\GateSettings;
use Bpmore\Copydesk\Gate\OverrideLog;
use Bpmore\Copydesk\Gate\OverridePasses;
use Bpmore\Copydesk\Gate\PublishGate;
use Bpmore\Copydesk\Http\Controllers\DictionaryController;
use Bpmore\Copydesk\Packs\PackLibrary;
use Bpmore\Copydesk\Scan\ScanHistory;
use Bpmore\Copydesk\Scan\SiteScan;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Gate as Access;
use Statamic\Addons\Addon;
use Statamic\Facades\Permission;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Facades\Utility;
use Statamic\Providers\AddonServiceProvider;

/**
 * Copydesk: readability and plain-language checking for Statamic.
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
     * Built with Vite, as Lifecycle is: the Bard underlines need a bundle,
     * and the built files are committed under resources/dist so a site never
     * runs a build. `npm run build` after changing anything in resources/js.
     */
    protected $vite = [
        'input' => ['resources/js/cp.js'],
        'publicDirectory' => 'resources/dist',
    ];

    protected $viewNamespace = 'copydesk';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(Settings::class, static fn (): Settings => Settings::fromConfig());
        $this->app->bind(GateSettings::class, static fn ($app): GateSettings => $app->make(Settings::class)->gate());
        // One gate per request: it holds an override between the save it let
        // through and that save finishing (SettleOverrides).
        $this->app->singleton(PublishGate::class);
        $this->app->singleton(OverridePasses::class, static fn ($app): OverridePasses => new OverridePasses($app->make('cache')->store()));
        $this->app->singleton(OverrideLog::class, static fn ($app): OverrideLog => OverrideLog::inStorage($app->make(Filesystem::class)));
        $this->app->singleton(ScanHistory::class, static fn ($app): ScanHistory => ScanHistory::inStorage($app->make(Filesystem::class)));

        // The house rule packs: the bundled ones, then the site's own in
        // resources/copydesk. Read once and kept; see the class.
        $this->app->singleton(PackLibrary::class, static fn ($app): PackLibrary => new PackLibrary(
            __DIR__.'/../resources/packs',
            resource_path('copydesk'),
            $app->make('cache')->store(),
        ));

        // The Readability band on Site Weather's tile. Tagged by name, as a
        // string: if Site Weather is not installed nothing resolves the tag,
        // the class is never autoloaded, and its missing interface is never
        // noticed. Site Weather is a suggestion, not a dependency.
        $this->app->tag(Weather\ReadabilityContributor::class, 'site-weather.contributors');

        // One publish check with A11y Gate, when it is installed in a version
        // that takes contributors: it asks Copydesk, and Copydesk's own
        // listener stands aside. Otherwise Copydesk gates on its own.
        if (A11yGateBridge::available()) {
            $this->app->tag(CopydeskGateContributor::class, A11yGateBridge::TAG);
            A11yGateBridge::join();
        }
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
            Utility::make('copydesk')
                ->title('Readability')
                ->navTitle('Readability')
                ->icon('fieldtype-textarea')
                ->description('How the site\'s pages read, which are hardest, and the words to avoid.')
                ->view('copydesk::utilities.readability', function () {
                    $dictionaries = $this->app->make(Dictionaries::class);
                    $site = Site::selected();

                    return [
                        'site' => $site,
                        'scan' => $this->app->make(SiteScan::class)->cached($site->handle()),
                        'sites' => Site::all()->values(),
                        'counts' => Site::all()->mapWithKeys(fn ($s) => [$s->handle() => count($dictionaries->own($s->handle()))])->all(),
                        'canManage' => (bool) User::current()?->can('manage jargon dictionary'),
                        'companion' => Companion::notice(),
                        'overrides' => array_slice($this->app->make(OverrideLog::class)->all(), 0, 50),
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
     * asks for "edit bpmore/statamic-copydesk settings". For this addon that
     * question is answered here instead, with the two permissions above,
     * so a site's roles speak this addon's language. Statamic's own gate
     * hook only handles abilities that are registered permissions, so a
     * super user is let through here as well.
     */
    private function bootPermissions(): void
    {
        Permission::extend(function () {
            Permission::group('copydesk', 'Copydesk', function () {
                Permission::register('view readability')
                    ->label('Check readability')
                    ->description('Grade a page from the publish form, and see the Readability widget on the dashboard.');
                Permission::register('manage jargon dictionary')
                    ->label('Manage the dictionary')
                    ->description('Add, change, import and export the words to avoid and what to say instead.');
                Permission::register(PublishGate::OVERRIDE)
                    ->label('Publish past the gate')
                    ->description('Publish a page the Copydesk gate stopped, once, giving a reason that is kept in the site report.');
                Permission::register('configure readability')
                    ->label('Configure readability')
                    ->description('Change the addon\'s settings: the dictionary today, and the target and gate as they move onto the settings screen.');
            });
        });

        Access::before(function ($user, string $ability, array $arguments) {
            if ($ability !== 'editSettings' || ! (($arguments[0] ?? null) instanceof Addon) || $arguments[0]->id() !== 'bpmore/statamic-copydesk') {
                return null;
            }

            $user = User::fromUser($user);

            return $user?->isSuper()
                || $user?->hasPermission('configure readability')
                || $user?->hasPermission('manage jargon dictionary');
        });
    }
}
