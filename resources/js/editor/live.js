/**
 * What every field on the form found as the author typed, for the sidebar
 * panel: counts by severity, and a way to jump to each issue.
 *
 * Each checked field reports here under its own key and takes its entry
 * back when it goes away (a Replicator set removed, the form closed).
 */
const { reactive } = window.Vue;

export const live = reactive({ fields: {} });

/**
 * @param {string} key  the field's place in the form
 * @param {{label: string, issues: Array<{issue: object, reveal: Function}>}} entry
 */
export function report(key, entry) {
    live.fields[key] = entry;
}

export function forget(key) {
    delete live.fields[key];
}
