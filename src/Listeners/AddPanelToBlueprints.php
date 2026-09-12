<?php

declare(strict_types=1);

namespace Bpmore\Plain\Listeners;

use Bpmore\Plain\Analysis\FieldText;
use Bpmore\Plain\Fieldtypes\ReadabilityPanel;
use Bpmore\Plain\Settings;
use Statamic\Events\EntryBlueprintFound;

/**
 * Puts the readability panel in the sidebar of every entry form that has
 * something to read, so nobody edits seven blueprint files by hand.
 *
 * The mechanism is Statamic's own: `slug` and `date` are not in your yaml
 * either, they are added as the blueprint is found. The field behaves like
 * a native one and disappears cleanly the day the addon is removed.
 *
 * A form with none of the configured fields on it gets no panel: there
 * would be nothing to check, and a panel that says so forever is noise.
 *
 * The collection and blueprint are stamped into the field's config, and
 * that is the only way the panel can check a page before its first save.
 * Statamic's create screen sends no entry reference and never sets the
 * blueprint's parent, so a fieldtype has nothing to ask; field config
 * does reach the browser, so the answer is put there by the one piece of
 * code that already knows it. `ensureField` is by handle, so a site that
 * placed the field itself keeps its own placement.
 */
final class AddPanelToBlueprints
{
    public const HANDLE = 'readability_panel';

    public function __construct(private readonly Settings $settings) {}

    public function handle(EntryBlueprintFound $event): void
    {
        if (! $this->settings->enabled()) {
            return;
        }

        // From the blueprint's namespace rather than `$event->entry`, which is
        // null whenever a blueprint is found without one: creating an entry,
        // editing the blueprint itself, or any listing screen.
        $namespace = $event->blueprint->namespace();

        if (! is_string($namespace) || ! str_starts_with($namespace, 'collections.')) {
            return;
        }

        $blueprint = $event->blueprint;
        $readable = false;

        foreach ($this->settings->fieldsFor((string) $blueprint->handle()) as $handle) {
            $field = $blueprint->field($handle);

            if ($field !== null && FieldText::isProse($field)) {
                $readable = true;
                break;
            }
        }

        if (! $readable) {
            return;
        }

        $blueprint->ensureField(
            self::HANDLE,
            [
                'type' => (new ReadabilityPanel)->handle(),
                'display' => 'Readability',
                'collection' => substr($namespace, strlen('collections.')),
                'blueprint' => $blueprint->handle(),
            ],
            'sidebar',
        );
    }
}
