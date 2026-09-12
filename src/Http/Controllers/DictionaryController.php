<?php

declare(strict_types=1);

namespace Bpmore\Plain\Http\Controllers;

use Bpmore\Plain\Dictionaries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Statamic\Facades\Site;
use Statamic\Http\Controllers\CP\CpController;

/**
 * A site's own dictionary, out as a file and in from one.
 *
 * The file is the array shape the engine's Dictionary reads and writes,
 * as JSON: a term, then what to say instead, or nothing to ban it, or
 * `replacements` and a `note`. The same shape the starter ships in, so a
 * site can export, edit by hand, and import, or carry a list to another
 * site.
 */
class DictionaryController extends CpController
{
    public function export(string $site, Dictionaries $dictionaries): JsonResponse
    {
        abort_if(Site::get($site) === null, 404, "There is no site called [{$site}], so there is no dictionary to export.");

        return response()
            ->json($dictionaries->export($site), 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ->header('Content-Disposition', 'attachment; filename="plain-dictionary-'.$site.'.json"');
    }

    public function import(Request $request, string $site, Dictionaries $dictionaries): RedirectResponse
    {
        abort_if(Site::get($site) === null, 404, "There is no site called [{$site}], so there is nothing to import into.");

        $request->validate([
            'file' => ['required', 'file', 'max:2048'],
            'mode' => ['required', 'in:merge,replace'],
        ]);

        $terms = json_decode((string) $request->file('file')->get(), true);

        if (! is_array($terms) || ($terms !== [] && array_is_list($terms))) {
            return back()->withErrors(['file' => 'That file is not a dictionary. It should be JSON with a term on the left and what to say instead on the right, the way an exported one is.']);
        }

        // The starter's own marker, if somebody exported it and sent it back.
        unset($terms['_source']);

        $count = $dictionaries->import($site, $terms, replace: $request->input('mode') === 'replace');

        return back()->with('success', $count === 1 ? 'The dictionary now has 1 entry of its own.' : "The dictionary now has {$count} entries of its own.");
    }
}
