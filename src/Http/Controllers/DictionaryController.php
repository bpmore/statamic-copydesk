<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Http\Controllers;

use Bpmore\Copydesk\Dictionaries;
use Bpmore\Copydesk\Scan\SiteReport;
use Bpmore\Copydesk\Scan\SiteScan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
    /** Throw the cached scan of a site away, so the page grades it afresh. */
    public function rescan(string $site, SiteScan $scan): RedirectResponse
    {
        SiteReport::authorize();
        abort_if(Site::get($site) === null, 404, "There is no site called [{$site}], so there is nothing to scan.");

        $scan->forget($site);
        $scan->start($site);

        return back()->with('success', 'Scanning again.');
    }

    /** How far the background scan has got, for the page under Tools. */
    public function progress(string $site, SiteScan $scan): JsonResponse
    {
        SiteReport::authorize();
        abort_if(Site::get($site) === null, 404, "There is no site called [{$site}].");

        return response()->json($scan->progress($site) ?? ['done' => null, 'total' => null]);
    }

    /**
     * The issues as a spreadsheet: one row per page and rule, with how many
     * and how bad, and the page's ignored count.
     */
    public function issues(string $site, SiteScan $scan): StreamedResponse
    {
        SiteReport::authorize();
        abort_if(Site::get($site) === null, 404, "There is no site called [{$site}].");

        $report = $scan->cached($site);
        abort_if($report === null, 409, 'The scan is still running. Try again when it is done.');

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['site', 'collection', 'page', 'edit_url', 'rule', 'severity', 'count', 'ignored_on_page']);

            foreach ($report->pagesWithIssues() as $page) {
                foreach ($page->rules as $rule => $found) {
                    fputcsv($out, [$report->site, $page->collection, $page->title, $page->editUrl, $rule, $found['severity'], $found['count'], $page->ignored]);
                }
            }

            fclose($out);
        }, 'copydesk-issues-'.$site.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function export(string $site, Dictionaries $dictionaries): JsonResponse
    {
        abort_unless(User::current()?->can('manage jargon dictionary'), 403, 'You do not have permission to manage the dictionary.');
        abort_if(Site::get($site) === null, 404, "There is no site called [{$site}], so there is no dictionary to export.");

        return response()
            ->json($dictionaries->export($site), 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ->header('Content-Disposition', 'attachment; filename="copydesk-dictionary-'.$site.'.json"');
    }

    public function import(Request $request, string $site, Dictionaries $dictionaries): RedirectResponse
    {
        abort_unless(User::current()?->can('manage jargon dictionary'), 403, 'You do not have permission to manage the dictionary.');
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
