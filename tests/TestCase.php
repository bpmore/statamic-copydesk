<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Tests;

use Bpmore\Copydesk\ServiceProvider;
use Illuminate\Support\Facades\File;
use Statamic\Addons\Manifest;
use Statamic\Facades\Blueprint;
use Statamic\Testing\AddonTestCase;
use Statamic\Testing\Concerns\FakesRoles;
use Statamic\Testing\Concerns\FakesViews;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

/**
 * A booted Statamic, for the part of this addon that cannot be tested
 * without one: the panel, the gate, the screens.
 *
 * The engine, bpmore/readability-core, has its own repository and tests;
 * nothing here should re-test what is tested there.
 */
abstract class TestCase extends AddonTestCase
{
    use FakesRoles;
    use FakesViews;
    use PreventsSavingStacheItemsToDisk;

    protected string $addonServiceProvider = ServiceProvider::class;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        // Testbench ships no app key, and the control-panel routes go through
        // the session middleware, which encrypts.
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        // Statamic's harness writes the addon's manifest without its editions,
        // which a real install reads from composer.json. Without them every
        // test would run with no edition at all, a state no site can be in.
        $manifest = $app->make(Manifest::class);
        $composer = json_decode((string) file_get_contents(__DIR__.'/../composer.json'), true);
        $manifest->manifest[$composer['name']]['editions'] = $composer['extra']['statamic']['editions'] ?? [];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // A real blueprint with a Bard, a textarea and a markdown field,
        // because the endpoint processes submitted values through one and a
        // blueprint with no fields silently processes them to nothing.
        Blueprint::setDirectory(__DIR__.'/__fixtures__/blueprints');

        // The addon's settings are a real file in the app's resource path,
        // and a test that saves one would otherwise decide the answer for
        // every test after it. The scan history is the same kind of thing.
        File::delete(resource_path('addons/copydesk.yaml'));
        File::deleteDirectory(storage_path('statamic/copydesk'));
    }
}
