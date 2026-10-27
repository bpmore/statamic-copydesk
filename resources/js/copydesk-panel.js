/**
 * The readability panel, as a fieldtype a site places in a blueprint.
 *
 * Statamic puts Vue on `window.Vue` with the runtime template compiler, so
 * a `template` string works, and the panel needs no single-file component.
 * The pattern, and every control-panel API used here, come from
 * Accessibility Gate, where they were read out of the control panel's own
 * unminified build.
 *
 * It shows two things. What the fields found as the author typed, counted
 * by severity, each issue a button that jumps to it; that is live, and
 * comes from the editor (./editor). And the grade, on demand, from the
 * full check, which reads the whole page.
 *
 * Everything visible is Statamic's own component library, so a defect in a
 * control is Statamic's to fix rather than ours to have shipped.
 */
import { live } from './editor/live.js';
import { SEVERITY, severityOf } from './editor/severity.js';

(function () {
    // Provided by the publish container: `values` is the form as it stands
    // right now, `reference` is "entry::<id>" on an edit screen and nothing
    // on a create screen. Both are refs.
    const { injectPublishContext } = window.__STATAMIC__.ui;

    const SEVERITIES = [
        { key: 'error', color: 'red', one: '1 to fix', many: (n) => n + ' to fix' },
        { key: 'warning', color: 'amber', one: '1 to look at', many: (n) => n + ' to look at' },
        { key: 'tip', color: 'gray', one: '1 tip', many: (n) => n + ' tips' },
    ];

    const COMPARISON = {
        on_target: { color: 'emerald', words: 'on target' },
        above: { color: 'red', words: 'harder than your target' },
        below: { color: 'emerald', words: 'easier than your target, which is fine' },
    };

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // How much of a long sentence to show either side of the mark. A
    // 46-word sentence with five things wrong in it was drawn five times
    // over, in full, in a sidebar; seen on a real screen, not guessed.
    const WINDOW = 70;

    // The flagged words marked inside their own sentence, or, when the
    // sentence is long and the mark is not the whole of it, inside a window
    // of it cut at word boundaries. Offsets are in characters, converted on
    // the server, so an accent before the mark does not shift it. `<mark>`
    // is bare: the browser's own yellow, and no class the control panel's
    // stylesheet could have dropped.
    function highlighted(context) {
        if (! context) {
            return '';
        }

        const chars = Array.from(context.text);
        let before = chars.slice(0, context.offset).join('');
        const marked = chars.slice(context.offset, context.offset + context.length).join('');
        let after = chars.slice(context.offset + context.length).join('');

        if (chars.length > 2 * WINDOW && context.length < chars.length) {
            if (before.length > WINDOW) {
                before = '…' + before.slice(-WINDOW).replace(/^\S*\s/, '');
            }

            if (after.length > WINDOW) {
                after = after.slice(0, WINDOW).replace(/\s\S*$/, '') + '…';
            }
        }

        return escapeHtml(before) + '<mark>' + escapeHtml(marked) + '</mark>' + escapeHtml(after);
    }

    Statamic.$components.register('readability_panel-fieldtype', {
        // `config` is the field's config from the blueprint; the listener that
        // places this field stamps the collection and blueprint handles into
        // it, which is what lets a page be checked before its first save.
        props: ['config', 'meta'],

        setup(props) {
            const container = injectPublishContext();

            const state = Vue.reactive({
                checking: false,
                result: null,
                failed: null,
                // Whether the check did not run because something went wrong,
                // or because it was never going to run yet.
                notYet: false,
            });

            async function check() {
                state.checking = true;
                state.failed = null;
                state.notYet = false;

                // Statamic's axios instance: CSRF token and the headers that
                // make a 422 come back as JSON.
                const axios = Statamic.$app.config.globalProperties.$axios;

                try {
                    const { data } = await axios.post(cp_url('copydesk/check'), {
                        reference: container.reference?.value,
                        values: container.values.value,
                        site: container.site?.value,
                        collection: props.config?.collection,
                        blueprint: props.config?.blueprint,
                    });

                    state.result = data;
                } catch (e) {
                    // Said out loud rather than swallowed: a panel that showed
                    // nothing after a failed request would look exactly like a
                    // page with nothing to fix. `||` and not `??`, so an empty
                    // message still draws. 422 means the check was never going
                    // to run: an unsaved page, and nothing is wrong.
                    state.notYet = e.response?.status === 422;
                    state.failed = e.response?.data?.message || 'The check could not be run.';
                    state.result = null;
                } finally {
                    state.checking = false;
                }
            }

            // What the gate said when it refused a publish, drawn where the
            // author is already looking. Statamic puts a 422's `errors` onto
            // the publish container keyed as the endpoint sent them, and the
            // toast says only "The given data was invalid.", so this is the
            // one place the refusal is read. Whichever is newest is shown: a
            // refusal clears an older check, and Statamic hands the container
            // a new errors object on every failed save, so a second refusal
            // clears a check pressed in between.
            const refusal = Vue.computed(() => {
                const lines = (container.errors?.value ?? {}).copydesk;

                return Array.isArray(lines) ? lines : [];
            });

            Vue.watch(refusal, (lines) => {
                if (lines.length > 0) {
                    state.result = null;
                    state.failed = null;
                }
            });

            const showRefusal = Vue.computed(() => refusal.value.length > 0 && ! state.result && ! state.failed && ! state.checking);

            const verdict = Vue.computed(() => {
                const r = state.result;

                if (! r || ! r.band) {
                    return null;
                }

                const how = COMPARISON[r.comparison] ?? COMPARISON.on_target;

                return { color: how.color, text: 'Your target is ' + r.target.label + '. This page reads ' + how.words + '.' };
            });

            // Findings by severity, errors first, each group with the words for
            // its badge. Whole-document findings (a passive-voice rate) carry
            // their sentences as spans and are drawn with the first few.
            const groups = Vue.computed(() => {
                const findings = state.result?.findings ?? [];

                return SEVERITIES
                    .map((s) => {
                        const items = findings.filter((f) => f.severity === s.key);

                        return { key: s.key, color: s.color, items, badge: items.length === 1 ? s.one : s.many(items.length) };
                    })
                    .filter((g) => g.items.length > 0);
            });

            const sections = Vue.computed(() => (state.result?.sections ?? []).map((s) => ({
                ...s,
                name: s.lead ? 'Before the first heading' : s.title,
                color: s.band ? (COMPARISON[s.comparison]?.color ?? 'gray') : 'gray',
                label: s.band ? s.band.label : 'Nothing to grade',
            })));

            function shown(spans) {
                return spans.slice(0, 5);
            }

            // Every field's issues from as the author typed, worst first,
            // in the order of the form within each severity.
            const liveItems = Vue.computed(() => Object.values(live.fields)
                .flatMap((field) => field.issues.map((item) => ({ ...item, label: field.label })))
                .sort((a, b) => SEVERITY[severityOf(a.issue)].order - SEVERITY[severityOf(b.issue)].order));

            const liveCounts = Vue.computed(() => SEVERITIES
                .map((s) => {
                    const n = liveItems.value.filter((item) => severityOf(item.issue) === s.key || (s.key === 'tip' && item.issue.severity === 'info')).length;

                    return { key: s.key, color: s.color, n, badge: n === 1 ? s.one : s.many(n) };
                })
                .filter((c) => c.n > 0));

            const LIVE_SHOWN = 20;

            function jumpLabel(item) {
                return 'Show ' + (item.issue.excerpt ? '\u201C' + item.issue.excerpt + '\u201D' : 'it') + ' in ' + item.label;
            }

            return { state, check, verdict, groups, sections, highlighted, shown, refusal, showRefusal, liveItems, liveCounts, LIVE_SHOWN, jumpLabel, severityWord: (issue) => SEVERITY[severityOf(issue)].word };
        },

        // No panel or header of its own: a field is already drawn inside the
        // publish form's card under the display name from the blueprint.
        //
        // Every button and badge sits in its own `div`, because both are
        // `inline-flex` and `space-y` only spaces block-level children.
        //
        // Badge text stays to a few words: `ui-badge` does not wrap. The
        // sentences go in `ui-description`, which does.
        //
        // Written for the person writing the page. The band and the verdict
        // come first, because that is the question they pressed the button
        // to ask. Sections next, because a page can read fine on average and
        // hide a paragraph that does not. Then what to fix, worst first, each
        // finding with its sentence and the flagged words marked in it.
        // The numbers behind the band are there, behind a toggle, for anyone
        // who wants them; the decimals are never the headline.
        template: `
            <div class="space-y-3">
                <div v-if="liveItems.length" class="space-y-2">
                    <ui-heading size="sm" text="As you type" />
                    <div class="flex flex-wrap gap-2">
                        <div v-for="c in liveCounts" :key="c.key"><ui-badge :color="c.color" :text="c.badge" /></div>
                    </div>
                    <ul class="space-y-1">
                        <li v-for="(item, i) in liveItems.slice(0, LIVE_SHOWN)" :key="'l' + i" class="text-xs">
                            <button type="button" class="copydesk-jump" @click="item.reveal()">{{ jumpLabel(item) }}</button>
                            <span>{{ ': ' + severityWord(item.issue) + '. ' + item.issue.message }}</span>
                        </li>
                    </ul>
                    <p v-if="liveItems.length > LIVE_SHOWN" class="text-xs">{{ 'And ' + (liveItems.length - LIVE_SHOWN) + ' more.' }}</p>
                </div>

                <ui-alert
                    v-if="showRefusal"
                    variant="error"
                    :heading="refusal[0]"
                    :text="refusal.slice(1).join(' ')"
                />

                <ui-description
                    v-if="meta && meta.can === false"
                    text="Checking readability is not something your role can do."
                />

                <div v-else>
                    <ui-button
                        size="sm"
                        :text="state.checking ? 'Checking' : 'Check readability'"
                        :disabled="state.checking"
                        @click="check"
                    />
                </div>

                <ui-skeleton v-if="state.checking" class="h-16 w-full" />

                <ui-alert
                    v-else-if="state.failed"
                    :variant="state.notYet ? 'warning' : 'error'"
                    :text="state.failed"
                />

                <template v-else-if="state.result">
                    <ui-alert
                        v-if="!state.result.locale.allowed"
                        variant="warning"
                        :text="state.result.locale.message"
                    />

                    <template v-else>
                        <ui-description v-for="(notice, i) in state.result.notices" :key="'n' + i" :text="notice" />

                        <div v-if="state.result.band" class="space-y-2">
                            <div><ui-badge :color="verdict.color" :text="state.result.band.label" /></div>
                            <ui-description :text="verdict.text" />
                        </div>

                        <ui-description v-else-if="!state.result.notices.length" text="Nothing to grade yet: none of the fields this check reads has any text." />

                        <div v-if="sections.length > 1" class="space-y-2">
                            <ui-heading size="sm" text="By section" />
                            <div v-for="(s, i) in sections" :key="'s' + i" class="flex flex-wrap items-center gap-2">
                                <span class="text-sm">{{ s.name }}</span>
                                <ui-badge :color="s.color" :text="s.label" />
                                <span v-if="s.grades" class="text-xs">Flesch-Kincaid {{ s.grades.flesch_kincaid }} · reading ease {{ s.grades.reading_ease }}</span>
                                <span v-if="s.findings" class="text-xs">{{ s.findings === 1 ? '1 finding' : s.findings + ' findings' }}</span>
                            </div>
                        </div>

                        <div v-for="g in groups" :key="g.key" class="space-y-3">
                            <div><ui-badge :color="g.color" :text="g.badge" /></div>
                            <div v-for="(f, i) in g.items" :key="g.key + i" class="space-y-1">
                                <ui-description :text="f.message" />
                                <p v-if="f.context" class="text-xs" v-html="highlighted(f.context)"></p>
                                <p v-for="(span, j) in shown(f.spans)" :key="g.key + i + 's' + j" class="text-xs" v-html="highlighted(span.context)"></p>
                                <p v-if="f.spans.length > 5" class="text-xs">{{ 'And ' + (f.spans.length - 5) + ' more.' }}</p>
                            </div>
                        </div>

                        <div v-if="state.result.band && !groups.length"><ui-badge color="emerald" text="Nothing to fix" /></div>

                        <details v-if="state.result.grades" class="text-xs">
                            <summary>The numbers behind the band</summary>
                            <p>{{ 'Flesch-Kincaid ' + state.result.grades.flesch_kincaid + ' · Gunning Fog ' + state.result.grades.gunning_fog + ' · SMOG ' + state.result.grades.smog + ' · Coleman-Liau ' + state.result.grades.coleman_liau }}</p>
                            <p>{{ 'Reading ease ' + state.result.grades.reading_ease + ' · ' + state.result.counts.words + ' words in ' + state.result.counts.sentences + ' sentences' }}</p>
                            <p>{{ 'Read from: ' + state.result.fields.join(', ') }}</p>
                        </details>
                    </template>
                </template>

                <ui-description
                    v-else
                    text="Grades this page as it stands here, including changes you have not saved, against the reading level the site writes for."
                />
            </div>
        `,
    });
})();
