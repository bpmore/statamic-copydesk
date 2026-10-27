/**
 * Whether a field is checked as the author types, and what the server
 * needs to know to check it.
 *
 * The site's config names the page's prose fields; the listener that puts
 * the readability panel on the form stamps that list into the panel's own
 * config, because a field knows nothing of Copydesk's config and the panel
 * is in every form's blueprint, which the publish container holds. No panel
 * on the form means nothing on it to check.
 *
 * A field is checked when it is one of those fields, or a Bard, markdown
 * or textarea field nested in one (a Bard in a Replicator set in a Grid).
 * A text field nested in one is a caption or a button label, and is left
 * alone, as the full check leaves it alone.
 */
const { unref, watch } = window.Vue;

const PANEL = 'readability_panel';

/** The hidden field on the entry where "Ignore here" is kept (Fingerprint::FIELD). */
export const IGNORES = 'copydesk_ignores';

/** The fingerprints ignored on this entry, as the form has them now. */
export function ignoresOf(container) {
    const list = unref(container?.values)?.[IGNORES];

    return Array.isArray(list) ? list.filter((v) => typeof v === 'string') : [];
}

/**
 * Ignore one finding here: its fingerprint goes into the hidden field,
 * which is saved with the entry like any other change. Nothing is ignored
 * for good until the author saves.
 */
export function ignoreIn(container, fingerprint) {
    if (! fingerprint) {
        return;
    }

    container.setFieldValue(IGNORES, [...new Set([...ignoresOf(container), fingerprint])]);
}

export function clearIgnores(container) {
    container.setFieldValue(IGNORES, []);
}

export function targetFor(container, { handle, fieldPathPrefix, config }, kind) {
    const panel = findPanel(unref(container?.blueprint));

    if (! panel) {
        return null;
    }

    const path = (fieldPathPrefix || handle || '').split('.');
    const nested = path.length > 1;

    if (! (panel.prose_fields ?? []).includes(path[0]) || (nested && kind === 'text')) {
        return null;
    }

    return {
        // The top-level field: what a rule's `scope.fields` names.
        field: path[0],
        // This field's own place in the form, unique on the page.
        key: [fieldPathPrefix, handle].filter(Boolean).join('.') || handle,
        label: config?.display || handle,
        collection: panel.collection ?? null,
        ignores: () => ignoresOf(container),
        ignore: (fingerprint) => ignoreIn(container, fingerprint),
        // Called when the list changes from somewhere else (the panel's
        // "Stop ignoring"), so the field asks again.
        onIgnoresChange: (callback) => watch(() => ignoresOf(container).join('\n'), callback),
        request: () => ({
            reference: unref(container.reference) || null,
            site: unref(container.site) || null,
            collection: panel.collection ?? null,
        }),
    };
}

// The panel's config in the blueprint the container holds: tabs, sections,
// fields, with each field's config either flat on it or under `config`.
function findPanel(node) {
    if (! node || typeof node !== 'object') {
        return null;
    }

    if (node.handle === PANEL) {
        return node.config ?? node;
    }

    for (const value of Object.values(node)) {
        const found = findPanel(value);

        if (found) {
            return found;
        }
    }

    return null;
}
