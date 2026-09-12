<?php

declare(strict_types=1);

namespace Bpmore\Plain\Tests;

use Bpmore\Plain\ServiceProvider;
use Statamic\Facades\Blueprint;
use Statamic\Testing\AddonTestCase;
use Statamic\Testing\Concerns\FakesViews;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

/**
 * A booted Statamic, for the part of this addon that cannot be tested
 * without one: the panel, the gate, the screens.
 *
 * The engine's own tests boot nothing and live in packages/readability-core;
 * nothing here should re-test what is tested there.
 */
abstract class TestCase extends AddonTestCase
{
    use FakesViews;
    use PreventsSavingStacheItemsToDisk;

    protected string $addonServiceProvider = ServiceProvider::class;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        // Testbench ships no app key, and the control-panel routes go through
        // the session middleware, which encrypts.
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    protected function setUp(): void
    {
        parent::setUp();

        // A real blueprint with a Bard, a textarea and a markdown field,
        // because the endpoint processes submitted values through one and a
        // blueprint with no fields silently processes them to nothing.
        Blueprint::setDirectory(__DIR__.'/__fixtures__/blueprints');
    }
}
