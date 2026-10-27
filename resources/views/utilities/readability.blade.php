{{--
    The readability page under Tools: the site's pages graded, hardest first,
    the acronyms and jargon that come up most, and import and export for the
    dictionary.

    This markup is compiled as a Vue template, not printed as HTML: a utility
    view is handed to `DynamicHtmlRenderer`, which does
    `defineComponent({ template: html })`, so Statamic's own components resolve
    here as they do in the control panel's own pages. Blade runs first, on the
    server, which is how the sites and the CSRF field get in; Blade's `{{ }}`
    and Vue's are the same characters, so Blade output uses `{!! !!}` and
    every other word here is static text. An unclosed tag is a Vue compile
    error that shows as a blank page, and a test parses this file for that.

    Editing the dictionary itself happens on the settings screen; this page
    is for the file in and the file out, which a form there cannot do.

    The scan is of the site selected in the control panel, cached for an
    hour; the button scans again. A scan runs as queued jobs; while it does,
    $scan is null and the page shows how far it has got. Tables are plain elements with the few
    classes the control panel's own build is known to carry.
--}}

<ui-header title="Readability" icon="fieldtype-textarea" />

<div class="space-y-6">

    @if ($companion !== null)
    <ui-description text="{!! e($companion) !!}" />
    @endif

    @if ($scan === null)
    <ui-card-panel heading="{!! e($site->name()) !!}">
        <copydesk-scan-progress url="{!! e(cp_route('utilities.copydesk.progress', $site->handle())) !!}" />
    </ui-card-panel>
    @elseif ($scan->refusal !== null)
    <ui-card-panel heading="{!! e($site->name()) !!}">
        <p>{!! e($scan->refusal) !!}</p>
    </ui-card-panel>
    @elseif ($scan->isEmpty())
    <ui-card-panel heading="{!! e($site->name()) !!}">
        <div class="space-y-3">
            <p>No page on this site has anything to grade yet{!! $scan->skipped > 0 ? ': '.e($scan->skipped === 1 ? '1 published page was' : $scan->skipped.' published pages were').' looked at, and none had text in a field this addon reads' : '' !!}. The fields it reads are set in the addon\'s configuration.</p>
            <form method="post" action="{!! e(cp_route('utilities.copydesk.rescan', $site->handle())) !!}">{!! csrf_field() !!}<ui-button size="sm" type="submit" text="Scan again" /></form>
        </div>
    </ui-card-panel>
    @else
    @php($against = $scan->againstTarget())
    <ui-card-panel heading="{!! e($site->name()) !!}: how its pages read">
        <div class="space-y-4">
            <p>
                {!! e(count($scan->entries) === 1 ? '1 published page graded' : count($scan->entries).' published pages graded') !!}
                against a target of {!! e($scan->target->band->label()) !!}:
                <strong>{!! e($against['above']) !!}</strong> above it,
                <strong>{!! e($against['on_target']) !!}</strong> on it,
                <strong>{!! e($against['below']) !!}</strong> below it{!! $scan->skipped > 0 ? '; '.e($scan->skipped).' with nothing to grade' : '' !!}.
            </p>
            <table class="w-full text-sm">
                <thead><tr class="text-left"><th class="py-1 pr-4">Band</th><th class="py-1 pr-4">Pages</th><th class="py-1">Against the target</th></tr></thead>
                <tbody>
                @foreach ($scan->distribution() as $row)
                    <tr><td class="py-1 pr-4">{!! e($row['band']) !!}</td><td class="py-1 pr-4">{!! e($row['count']) !!}</td><td class="py-1">{!! e(['on_target' => 'on target', 'above' => 'above', 'below' => 'below'][$row['comparison']]) !!}</td></tr>
                @endforeach
                </tbody>
            </table>
            <table class="w-full text-sm">
                <thead><tr class="text-left"><th class="py-1 pr-4">Collection</th><th class="py-1 pr-4">Pages</th><th class="py-1 pr-4">Median page</th><th class="py-1">Above the target</th></tr></thead>
                <tbody>
                @foreach ($scan->byCollection() as $row)
                    <tr><td class="py-1 pr-4">{!! e($row['title']) !!}</td><td class="py-1 pr-4">{!! e($row['entries']) !!}</td><td class="py-1 pr-4">{!! e($row['median']) !!}</td><td class="py-1">{!! e($row['above']) !!}</td></tr>
                @endforeach
                </tbody>
            </table>
            @if ($scan->failures !== [])
            <ui-description text="{!! e(count($scan->failures) === 1 ? '1 page could not be graded: ' : count($scan->failures).' pages could not be graded: ') !!}{!! e(implode('; ', $scan->failures)) !!}" />
            @endif
            <div class="flex flex-wrap items-center gap-3">
                <form method="post" action="{!! e(cp_route('utilities.copydesk.rescan', $site->handle())) !!}">{!! csrf_field() !!}<ui-button size="sm" type="submit" text="Scan again" /></form>
                <span class="text-xs">Last scanned {!! e(\Illuminate\Support\Carbon::parse($scan->scannedAt)->diffForHumans()) !!}.</span>
            </div>
        </div>
    </ui-card-panel>

    <ui-card-panel heading="Hardest pages">
        <table class="w-full text-sm">
            <thead><tr class="text-left"><th class="py-1 pr-4">Page</th><th class="py-1 pr-4">Collection</th><th class="py-1 pr-4">Reads at</th><th class="py-1">To fix / to look at</th></tr></thead>
            <tbody>
            @foreach ($scan->worst() as $entry)
                <tr>
                    <td class="py-1 pr-4"><a href="{!! e($entry->editUrl) !!}" class="underline underline-offset-2">{!! e($entry->title) !!}</a></td>
                    <td class="py-1 pr-4">{!! e($entry->collectionTitle) !!}</td>
                    <td class="py-1 pr-4">{!! e($entry->band['label']) !!}</td>
                    <td class="py-1">{!! e($entry->errors) !!} / {!! e($entry->warnings) !!}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </ui-card-panel>

    <ui-card-panel heading="Acronyms nobody spelled out">
        @if ($scan->acronyms() === [])
        <p class="text-sm">None found. Every acronym on the site is spelled out where it is first used, or is one everybody knows.</p>
        @else
        <div class="space-y-3">
            <p class="text-sm">Most frequent first. An acronym here is a candidate for the dictionary, with what to say instead, or for the list of ones your readers already know.</p>
            <table class="w-full text-sm">
                <thead><tr class="text-left"><th class="py-1 pr-4">Acronym</th><th class="py-1 pr-4">Times</th><th class="py-1">Pages</th></tr></thead>
                <tbody>
                @foreach (array_slice($scan->acronyms(), 0, 25) as $row)
                    <tr><td class="py-1 pr-4">{!! e(strtoupper($row['term'])) !!}</td><td class="py-1 pr-4">{!! e($row['count']) !!}</td><td class="py-1">{!! e($row['pages']) !!}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </ui-card-panel>

    <ui-card-panel heading="Jargon that comes up most">
        @if ($scan->jargon() === [])
        <p class="text-sm">None found. Nothing on the site is on the dictionary, the starter included.</p>
        @else
        <table class="w-full text-sm">
            <thead><tr class="text-left"><th class="py-1 pr-4">Term</th><th class="py-1 pr-4">Times</th><th class="py-1">Pages</th></tr></thead>
            <tbody>
            @foreach (array_slice($scan->jargon(), 0, 25) as $row)
                <tr><td class="py-1 pr-4">{!! e($row['term']) !!}</td><td class="py-1 pr-4">{!! e($row['count']) !!}</td><td class="py-1">{!! e($row['pages']) !!}</td></tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </ui-card-panel>
    @endif

    @if ($scan !== null && $scan->refusal === null && $scan->pagesWithIssues() !== [])
    @php($issuePages = $scan->pagesWithIssues())
    <ui-card-panel heading="What to fix, by rule">
        <div class="space-y-3">
            <p class="text-sm">
                Every published page, against the site's house rules and the readability rules. Errors stop a publish when the gate is on.
                @if ($scan->ignored() > 0)
                {!! e($scan->ignored() === 1 ? '1 finding is' : $scan->ignored().' findings are') !!} ignored by authors ("Ignore here") and not counted below.
                @endif
            </p>
            <table class="w-full text-sm">
                <thead><tr class="text-left"><th class="py-1 pr-4">Rule</th><th class="py-1 pr-4">How bad</th><th class="py-1 pr-4">Times</th><th class="py-1">Pages</th></tr></thead>
                <tbody>
                @foreach ($scan->byRule() as $row)
                    <tr><td class="py-1 pr-4">{!! e($row['rule']) !!}</td><td class="py-1 pr-4">{!! e(['error' => 'to fix', 'warning' => 'to look at', 'tip' => 'tip'][$row['severity']] ?? $row['severity']) !!}</td><td class="py-1 pr-4">{!! e($row['count']) !!}</td><td class="py-1">{!! e($row['pages']) !!}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </ui-card-panel>

    <ui-card-panel heading="What to fix, by collection">
        <table class="w-full text-sm">
            <thead><tr class="text-left"><th class="py-1 pr-4">Collection</th><th class="py-1 pr-4">Pages with issues</th><th class="py-1 pr-4">To fix</th><th class="py-1 pr-4">To look at</th><th class="py-1 pr-4">Tips</th><th class="py-1">Ignored</th></tr></thead>
            <tbody>
            @foreach ($scan->issuesByCollection() as $row)
                <tr><td class="py-1 pr-4">{!! e($row['title']) !!}</td><td class="py-1 pr-4">{!! e($row['pages']) !!}</td><td class="py-1 pr-4">{!! e($row['errors']) !!}</td><td class="py-1 pr-4">{!! e($row['warnings']) !!}</td><td class="py-1 pr-4">{!! e($row['tips']) !!}</td><td class="py-1">{!! e($row['ignored']) !!}</td></tr>
            @endforeach
            </tbody>
        </table>
    </ui-card-panel>

    <ui-card-panel heading="Pages with the most to fix">
        <div class="space-y-3">
            <table class="w-full text-sm">
                <thead><tr class="text-left"><th class="py-1 pr-4">Page</th><th class="py-1 pr-4">Collection</th><th class="py-1 pr-4">To fix</th><th class="py-1 pr-4">To look at</th><th class="py-1 pr-4">Tips</th><th class="py-1">Ignored</th></tr></thead>
                <tbody>
                @foreach (array_slice($issuePages, 0, 25) as $page)
                    <tr>
                        <td class="py-1 pr-4"><a href="{!! e($page->editUrl) !!}" class="underline underline-offset-2">{!! e($page->title) !!}</a></td>
                        <td class="py-1 pr-4">{!! e($page->collectionTitle) !!}</td>
                        <td class="py-1 pr-4">{!! e($page->errors) !!}</td>
                        <td class="py-1 pr-4">{!! e($page->warnings) !!}</td>
                        <td class="py-1 pr-4">{!! e($page->tips) !!}</td>
                        <td class="py-1">{!! e($page->ignored) !!}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="flex flex-wrap items-center gap-3">
                <ui-button size="sm" href="{!! e(cp_route('utilities.copydesk.issues', $site->handle())) !!}" text="Download every issue (CSV)" />
                @if (count($issuePages) > 25)
                <span class="text-xs">{!! e(count($issuePages) - 25) !!} more pages are in the download.</span>
                @endif
            </div>
        </div>
    </ui-card-panel>
    @endif

    @if (($overrides ?? []) !== [])
    <ui-card-panel heading="Published past the gate">
        <div class="space-y-3">
            <p class="text-sm">Every page someone published while the gate would have stopped it, newest first, with the reason they gave.</p>
            <table class="w-full text-sm">
                <thead><tr class="text-left"><th class="py-1 pr-4">Page</th><th class="py-1 pr-4">Rules</th><th class="py-1 pr-4">Who and when</th><th class="py-1">Reason</th></tr></thead>
                <tbody>
                @foreach ($overrides as $row)
                    <tr>
                        <td class="py-1 pr-4">{!! e($row['title'] ?? '') !!} <span class="text-xs">({!! e($row['collection'] ?? '') !!})</span></td>
                        <td class="py-1 pr-4">{!! e(implode(', ', (array) ($row['rules'] ?? []))) !!}</td>
                        <td class="py-1 pr-4">{!! e($row['user_name'] ?? '') !!}, {!! e(\Illuminate\Support\Carbon::parse($row['at'] ?? 'now')->toDayDateTimeString()) !!}</td>
                        <td class="py-1">{!! e($row['reason'] ?? '') !!}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </ui-card-panel>
    @endif

    @if (isset($packs))
    <ui-card-panel heading="House style packs">
        <div class="space-y-3">
            <p class="text-sm">Copydesk ships {!! e(implode(', ', $packs['bundled'])) !!}. A site's own packs live in resources/copydesk and can extend them.</p>
            @if ($packs['site'] !== [])
            <ul class="text-sm space-y-1">
                @foreach ($packs['site'] as $pack)
                <li>
                    <span class="font-medium">{!! e($pack) !!}</span>
                    @if ($packs['canEdit'])
                    <a class="underline underline-offset-2 ml-2" href="{!! e(cp_route('copydesk.packs.edit', $pack)) !!}">Edit the rules</a>
                    @endif
                </li>
                @endforeach
            </ul>
            @else
            <p class="text-sm">This site has no pack of its own yet.</p>
            @endif
            @if ($packs['canEdit'])
            <form method="post" action="{!! e(cp_route('copydesk.packs.store')) !!}" class="flex flex-wrap items-end gap-2">
                {!! csrf_field() !!}
                <label class="text-sm" for="copydesk-new-pack">New pack name</label>
                <input id="copydesk-new-pack" name="name" type="text" class="copydesk-reason" style="max-width: 16rem" placeholder="house" required pattern="[a-z0-9]+(-[a-z0-9]+)*" />
                <ui-button size="sm" type="submit" text="Start a pack" />
            </form>
            @endif
        </div>
    </ui-card-panel>
    @endif

    <ui-card-panel heading="Your dictionary">
        <div class="space-y-3">
            <p>
                The words and phrases your readers do not know, with what to say instead,
                are edited under Addons, Copydesk, Settings. A starter list of plain-language
                substitutions is built in; what you add goes on top of it.
            </p>
            <p>
                Here you can take a site's own entries out as a file, or bring a file in.
                The file is plain JSON, a term on the left and what to say instead on the
                right, so a list built for one site can be carried to another, or edited
                by hand and brought back.
            </p>
        </div>
    </ui-card-panel>

    @foreach ($sites as $site)
    <ui-card-panel heading="{!! e($site->name()) !!}">
        <div class="space-y-4">
            <div class="flex flex-wrap items-center gap-3">
                @if ($canManage)<ui-button size="sm" href="{!! e(cp_route('utilities.copydesk.export', $site->handle())) !!}" text="Export this site's entries" />@endif
                <span class="text-sm">{!! e($counts[$site->handle()] === 1 ? '1 entry of its own' : $counts[$site->handle()].' entries of its own') !!}, on top of the starter.</span>
            </div>

            @if (! $canManage)
            <p class="text-sm">Importing and exporting the dictionary is not something your role can do.</p>
            @else
            <form method="post" action="{!! e(cp_route('utilities.copydesk.import', $site->handle())) !!}" enctype="multipart/form-data" class="space-y-3">
                {!! csrf_field() !!}
                <div class="space-y-1">
                    <label class="block text-sm font-medium" for="copydesk-file-{!! e($site->handle()) !!}">A dictionary file to bring in</label>
                    <input id="copydesk-file-{!! e($site->handle()) !!}" type="file" name="file" accept="application/json,.json" required="required" class="text-sm" />
                </div>
                <div class="space-y-1 text-sm">
                    <label class="flex items-center gap-2"><input type="radio" name="mode" value="merge" checked="checked" /> Add to what is here; the file wins where both have a term</label>
                    <label class="flex items-center gap-2"><input type="radio" name="mode" value="replace" /> Replace this site's own entries with the file</label>
                </div>
                <div><ui-button size="sm" type="submit" text="Import" /></div>
            </form>
            @endif
        </div>
    </ui-card-panel>
    @endforeach

</div>
