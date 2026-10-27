/**
 * How far a background site scan has got, on the page under Tools. Asks
 * every two seconds, says it in a polite live region, and reloads the page
 * when the report is ready.
 */
(function () {
    Statamic.$components.register('copydesk-scan-progress', {
        props: ['url'],

        setup(props) {
            const state = Vue.reactive({ done: 0, total: null });
            let timer = null;

            async function ask() {
                try {
                    const { data } = await Statamic.$app.config.globalProperties.$axios.get(props.url);

                    if (data.total === null) {
                        window.location.reload();

                        return;
                    }

                    state.done = data.done;
                    state.total = data.total;
                } catch (e) {
                    // Asked again on the next tick; one missed answer is not news.
                }

                timer = setTimeout(ask, 2000);
            }

            Vue.onMounted(ask);
            Vue.onBeforeUnmount(() => clearTimeout(timer));

            return { state };
        },

        template: `
            <div class="space-y-2" role="status" aria-live="polite">
                <p class="text-sm">{{ state.total === null ? 'Starting the scan.' : 'Scanning: ' + state.done + ' of ' + state.total + ' pages done.' }}</p>
                <p class="text-xs">The report shows here when it is done. You can leave this page; the scan carries on.</p>
            </div>
        `,
    });
})();
