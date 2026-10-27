<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Fieldtypes;

use Bpmore\Copydesk\Edition;
use Bpmore\Copydesk\Gate\PublishGate;
use Statamic\Facades\User;
use Statamic\Fields\Fieldtype;

/**
 * The readability panel, as a field a site can place in a blueprint.
 *
 * A fieldtype rather than something injected into the publish form,
 * because it is the extension point Statamic offers inside the entry
 * screen. Where it appears is the site's decision, and the sidebar is the
 * obvious home; the listener puts it there for any form it can read.
 *
 * It stores nothing. `process()` and `preProcess()` return null so a
 * blueprint carrying this field never writes a key into the entry's file:
 * a grade left in the content directory would be a stale copy of an
 * answer that changes every time the page does.
 *
 * The handle is derived from the class name: `readability_panel`, and the
 * Vue component is `readability_panel-fieldtype`. Renaming the class
 * renames the field and breaks every blueprint using it.
 */
class ReadabilityPanel extends Fieldtype
{
    protected $icon = 'fieldtype-textarea';

    protected static $title = 'Readability';

    public function preProcess($data)
    {
        return null;
    }

    public function process($data)
    {
        return null;
    }

    public function augment($value)
    {
        return null;
    }

    /**
     * Whether this user may check, and may publish past the gate, handed to
     * the component as `meta` when the form loads, so the panel can say so
     * instead of showing a button that would only ever answer 403.
     */
    public function preload()
    {
        return [
            'can' => (bool) User::current()?->can('view readability'),
            // Whether to offer "Publish anyway" when the gate refuses.
            'canOverride' => Edition::isPro() && (bool) User::current()?->can(PublishGate::OVERRIDE),
        ];
    }
}
