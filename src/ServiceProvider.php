<?php

declare(strict_types=1);

namespace Bpmore\Plain;

use Statamic\Providers\AddonServiceProvider;

/**
 * Plain: readability and plain-language checking for Statamic.
 *
 * The engine is bpmore/readability-core and knows nothing about Statamic;
 * this addon is its surface: the publish-form panel, the opt-in gate, the
 * jargon dictionary, the utility screen, the widget, the tag and the
 * command. Each arrives in its own task and registers itself here.
 */
class ServiceProvider extends AddonServiceProvider
{
    public function bootAddon(): void
    {
        //
    }
}
