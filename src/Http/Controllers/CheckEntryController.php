<?php

declare(strict_types=1);

namespace Bpmore\Plain\Http\Controllers;

use Bpmore\Plain\Analysis\Analyzer;
use Illuminate\Http\Request;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Facades\Collection;
use Statamic\Facades\Data;
use Statamic\Facades\Site;
use Statamic\Fields\Blueprint;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Support\Arr;

/**
 * What the panel asks: check this page as it stands in the form right now.
 *
 * The values arrive unsaved, and that is the whole reason this is a
 * controller rather than a read of the file on disk. A panel that graded
 * the saved version would be at its most wrong exactly when it matters:
 * after an author has rewritten a page and before they press publish.
 *
 * Every failure says why, in a sentence. A bare `abort()` answers with an
 * empty message, and a panel that draws nothing after a failed request
 * looks exactly like a page with nothing wrong with it.
 */
class CheckEntryController extends CpController
{
    public function __invoke(Request $request, Analyzer $analyzer): array
    {
        $reference = (string) $request->input('reference');

        [$blueprint, $values, $locale] = $reference === ''
            ? $this->beingCreated($request)
            : $this->beingEdited($request, $reference);

        return $analyzer->analyze($blueprint, $values, $locale)->toArray();
    }

    /**
     * An entry that exists: its blueprint, the form's values laid over it,
     * and its site's language.
     *
     * @return array{Blueprint, array<string, mixed>, string}
     */
    private function beingEdited(Request $request, string $reference): array
    {
        $entry = Data::find($reference);

        abort_if(! $entry instanceof EntryContract, 404, 'That entry could not be found, so nothing about it was checked.');

        // The same permission Statamic requires to open the entry. This endpoint
        // takes arbitrary values, so it must not be a way to read one somebody
        // cannot already open.
        $this->authorize('view', $entry);

        $blueprint = $entry->blueprint();

        return [$blueprint, $this->submitted($request, $blueprint), (string) $entry->site()->locale()];
    }

    /**
     * An entry that does not exist yet: the collection and blueprint come
     * from the panel's own field config, stamped there by the listener that
     * places the field, and the site is the one the form is for.
     *
     * @return array{Blueprint, array<string, mixed>, string}
     */
    private function beingCreated(Request $request): array
    {
        $handle = (string) $request->input('collection');

        abort_if(
            $handle === '',
            422,
            'This page has not been saved yet, and the panel was not told which collection it belongs to. Save it once, then check it.'
        );

        $collection = Collection::findByHandle($handle);

        abort_if(! $collection, 404, "The collection [{$handle}] could not be found, so nothing was checked.");

        $this->authorize('create', [EntryContract::class, $collection]);

        $wanted = (string) $request->input('blueprint');
        $blueprint = $collection->entryBlueprint($wanted !== '' ? $wanted : null);

        abort_if(! $blueprint, 404, 'That form could not be found, so nothing was checked.');

        $site = Site::get((string) $request->input('site')) ?? Site::selected();

        return [$blueprint, $this->submitted($request, $blueprint), (string) $site->locale()];
    }

    /**
     * The form's values, run through the blueprint the way a save would, so
     * a Bard field arrives as the node tree Statamic stores.
     *
     * @return array<string, mixed>
     */
    private function submitted(Request $request, Blueprint $blueprint): array
    {
        return Arr::except(
            $blueprint->fields()->addValues((array) $request->input('values', []))->process()->values()->all(),
            ['slug'],
        );
    }
}
