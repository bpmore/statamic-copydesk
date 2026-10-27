/**
 * "Try it on some text", in the rule editor's sidebar: the pack as the
 * form has it now, unsaved, run over what the editor types.
 */
(function () {
    const { injectPublishContext } = window.__STATAMIC__.ui;

    const WORDS = { error: 'To fix', warning: 'To look at', tip: 'Tip' };

    Statamic.$components.register('copydesk_rule_tester-fieldtype', {
        props: ['meta'],

        setup(props) {
            const container = injectPublishContext();
            const state = Vue.reactive({ text: '', trying: false, findings: null, failed: null });

            async function run() {
                state.trying = true;
                state.failed = null;

                try {
                    const { data } = await Statamic.$app.config.globalProperties.$axios.post(props.meta.url, {
                        values: container.values.value,
                        text: state.text,
                    });

                    state.findings = data.findings;
                } catch (e) {
                    state.findings = null;
                    state.failed = e.response?.data?.errors?.rules?.[0] || e.response?.data?.message || 'The rules could not be tried.';
                } finally {
                    state.trying = false;
                }
            }

            return { state, run, WORDS };
        },

        template: `
            <div class="space-y-3">
                <label class="block text-sm" for="copydesk-try-text">Text to check</label>
                <textarea id="copydesk-try-text" v-model="state.text" class="copydesk-reason" rows="5"></textarea>
                <div><ui-button size="sm" :disabled="state.trying || !meta.url" :text="state.trying ? 'Trying' : 'Try these rules'" @click="run" /></div>
                <div role="status" aria-live="polite" class="space-y-2">
                    <ui-alert v-if="state.failed" variant="error" :text="state.failed" />
                    <p v-else-if="state.findings && !state.findings.length" class="text-sm">Nothing flagged.</p>
                    <ul v-else-if="state.findings" class="space-y-2 text-sm">
                        <li v-for="(f, i) in state.findings" :key="i">
                            <strong>{{ WORDS[f.severity] || f.severity }}</strong>
                            <span>{{ ' ' + f.rule + ': ' + f.message + ' ' }}</span>
                            <mark>{{ f.excerpt }}</mark>
                        </li>
                    </ul>
                </div>
                <p class="text-xs">Saving rewrites the pack's file, so comments in it are not kept. A pack's changes for single collections are kept as they are.</p>
            </div>
        `,
    });
})();
