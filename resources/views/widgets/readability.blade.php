{{--
    Compiled as a Vue template, like the utility view: Blade runs first, on
    the server, and its output goes out through {!! e() !!} so nothing here
    is mistaken for Vue's own braces.
--}}
<ui-card-panel heading="Readability">
    @if ($latest === null)
        <div class="space-y-3">
            <p class="text-sm">No scan has run yet. Open the readability page and the site is graded there.</p>
            @if ($url)<div><ui-button size="sm" href="{!! e($url) !!}" text="Open the readability page" /></div>@endif
        </div>
    @elseif ($latest->medianBand() === null)
        <div class="space-y-3">
            <p class="text-sm">Nothing to grade yet: {!! e($latest->skipped === 1 ? '1 published page was' : $latest->skipped.' published pages were') !!} looked at, and none had text in a field this addon reads.</p>
            @if ($url)<div><ui-button size="sm" href="{!! e($url) !!}" text="Open the readability page" /></div>@endif
        </div>
    @else
        <div class="space-y-3">
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-semibold">{!! e($latest->medianBand()->label()) !!}</span>
                <span class="text-sm opacity-70">the median page{!! $medianMoved === 0 ? '' : e($medianMoved > 0 ? ', harder than last scan' : ', easier than last scan') !!}</span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <ui-badge size="sm" color="{!! e($target->compare($latest->medianBand())->value === 'above' ? 'red' : 'emerald') !!}" text="Target {!! e($target->band->label()) !!}" pill="pill" />
                <ui-badge size="sm" color="{!! e($latest->above > 0 ? 'amber' : 'emerald') !!}" text="{!! e($latest->above) !!} of {!! e($latest->graded) !!} above the target" pill="pill" />
                @if ($previous !== null)
                    <span class="text-xs opacity-70">{!! e($aboveMoved === 0 ? 'no change since last scan' : ($aboveMoved > 0 ? abs($aboveMoved).' more than last scan' : abs($aboveMoved).' fewer than last scan')) !!}</span>
                @endif
            </div>
            <ui-description text="Last scan {!! e(\Illuminate\Support\Carbon::parse($latest->computedAt)->diffForHumans()) !!}, {!! e($latest->graded) !!} {!! e($latest->graded === 1 ? 'page' : 'pages') !!} graded{!! $latest->skipped > 0 ? ', '.e($latest->skipped).' with nothing to grade' : '' !!}." />
            @if ($url)<div><ui-button size="sm" href="{!! e($url) !!}" text="Open the readability page" /></div>@endif
        </div>
    @endif
</ui-card-panel>
