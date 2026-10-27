<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Http\Controllers;

use Bpmore\Copydesk\Analysis\Linter;
use Illuminate\Http\Request;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Facades\Collection;
use Statamic\Facades\Data;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;

/**
 * What a field asks as the author types: check this one field's value now.
 *
 * Called by the editor every time the author pauses (600 ms), so it does
 * the least it can: one field, no grades. It asks the same permission and
 * entry access as the panel's check, because it takes arbitrary text and
 * must not be a way to read an entry somebody cannot open.
 */
class LintController extends CpController
{
    public function __invoke(Request $request, Linter $linter): array
    {
        abort_unless(User::current()?->can('view readability'), 403, 'You do not have permission to check readability.');

        $kind = (string) $request->input('kind');
        abort_unless(in_array($kind, Linter::KINDS, true), 422, 'That is not a kind of field Copydesk checks.');

        $field = (string) $request->input('field');
        abort_if($field === '', 422, 'Say which field this is.');

        [$collection, $site] = $this->context($request);

        return $linter->lint($kind, $request->input('value'), $collection, $field, (string) $site->locale(), (string) $site->handle());
    }

    /**
     * The collection and site the field belongs to: from the entry being
     * edited, or, before its first save, from the form.
     *
     * @return array{?string, \Statamic\Sites\Site}
     */
    private function context(Request $request): array
    {
        $reference = (string) $request->input('reference');

        if ($reference !== '') {
            $entry = Data::find($reference);
            abort_if(! $entry instanceof EntryContract, 404, 'That entry could not be found, so nothing about it was checked.');
            $this->authorize('view', $entry);

            return [(string) $entry->collection()?->handle(), $entry->site()];
        }

        $handle = (string) $request->input('collection');
        $collection = $handle === '' ? null : Collection::findByHandle($handle);
        abort_if($handle !== '' && ! $collection, 404, "The collection [{$handle}] could not be found, so nothing was checked.");

        if ($collection !== null) {
            $this->authorize('create', [EntryContract::class, $collection]);
        }

        return [$collection?->handle(), Site::get((string) $request->input('site')) ?? Site::selected()];
    }
}
