/**
 * The words for each severity, the same as the panel's badges: the style of
 * the underline says it too, so colour is never the only sign.
 */
export const SEVERITY = {
    error: { word: 'To fix', order: 0 },
    warning: { word: 'To look at', order: 1 },
    tip: { word: 'Tip', order: 2 },
    info: { word: 'Tip', order: 2 },
};

export function severityOf(issue) {
    return SEVERITY[issue.severity] ? issue.severity : 'warning';
}

/** What a suggestion button says: the words, or what removing them means. */
export function suggestionLabel(text) {
    const shown = text.trim();

    return shown === '' ? 'Remove it' : 'Use “' + shown + '”';
}
