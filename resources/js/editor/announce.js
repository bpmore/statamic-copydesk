/**
 * One polite live region for the whole page, so a screen reader hears what
 * the underline under the caret says without the focus moving.
 */
let region = null;

export function announce(text) {
    if (! region) {
        region = document.createElement('div');
        region.className = 'copydesk-sr-only';
        region.setAttribute('aria-live', 'polite');
        region.setAttribute('role', 'status');
        document.body.appendChild(region);
    }

    // Cleared first, so the same words said twice are read twice.
    region.textContent = '';
    setTimeout(() => { region.textContent = text; }, 50);
}

export const MAC = /Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent);

/** The keys that open the choices for the issue under the caret. */
export const SHORTCUT = MAC ? 'Option+Enter' : 'Alt+Enter';
