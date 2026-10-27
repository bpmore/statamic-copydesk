/**
 * Asks the server about one field, 600 ms after the author stops typing.
 *
 * One checker per field. A request still on its way when the next one is
 * sent is cancelled, so an answer never lands on newer text than it was
 * asked about, and nothing piles up while someone types quickly. When the
 * server says no (no permission, or a 403 from entry access) the field
 * stops asking: the answer will not change while the form is open.
 */
const DELAY = 600;

export function createChecker({ target, kind, read, onResult }) {
    let timer = null;
    let controller = null;
    let stopped = false;

    async function run() {
        timer = null;
        controller?.abort();
        controller = new AbortController();

        const snapshot = read();
        const axios = Statamic.$app.config.globalProperties.$axios;

        try {
            const { data } = await axios.post(cp_url('copydesk/lint'), {
                ...target.request(),
                field: target.field,
                kind,
                value: snapshot.value,
            }, { signal: controller.signal });

            onResult(data, snapshot);
        } catch (e) {
            if (e?.code === 'ERR_CANCELED' || e?.name === 'CanceledError') {
                return;
            }

            // Quiet on purpose: a toast on every pause in typing would be
            // worse than no underlines, and the panel's own check still
            // says what went wrong, out loud.
            if ([401, 403, 404, 419].includes(e?.response?.status)) {
                stop();
            }
        }
    }

    function schedule() {
        if (stopped) {
            return;
        }

        clearTimeout(timer);
        timer = setTimeout(run, DELAY);
    }

    function stop() {
        stopped = true;
        clearTimeout(timer);
        controller?.abort();
    }

    return { schedule, stop, now: run };
}
