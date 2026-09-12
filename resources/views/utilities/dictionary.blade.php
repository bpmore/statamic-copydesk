{{--
    Import and export for the dictionary, under Tools.

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
--}}

<ui-header title="Readability" icon="fieldtype-textarea" />

<div class="space-y-6">

    <ui-card-panel heading="Your dictionary">
        <div class="space-y-3">
            <p>
                The words and phrases your readers do not know, with what to say instead,
                are edited under Addons, Plain, Settings. A starter list of plain-language
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
                <ui-button size="sm" href="{!! e(cp_route('utilities.plain.export', $site->handle())) !!}" text="Export this site's entries" />
                <span class="text-sm">{!! e($counts[$site->handle()] === 1 ? '1 entry of its own' : $counts[$site->handle()].' entries of its own') !!}, on top of the starter.</span>
            </div>

            <form method="post" action="{!! e(cp_route('utilities.plain.import', $site->handle())) !!}" enctype="multipart/form-data" class="space-y-3">
                {!! csrf_field() !!}
                <div class="space-y-1">
                    <label class="text-sm font-medium" for="plain-file-{!! e($site->handle()) !!}">A dictionary file to bring in</label>
                    <input id="plain-file-{!! e($site->handle()) !!}" type="file" name="file" accept="application/json,.json" required="required" class="text-sm" />
                </div>
                <div class="space-y-1 text-sm">
                    <label class="flex items-center gap-2"><input type="radio" name="mode" value="merge" checked="checked" /> Add to what is here; the file wins where both have a term</label>
                    <label class="flex items-center gap-2"><input type="radio" name="mode" value="replace" /> Replace this site's own entries with the file</label>
                </div>
                <div><ui-button size="sm" type="submit" text="Import" /></div>
            </form>
        </div>
    </ui-card-panel>
    @endforeach

</div>
