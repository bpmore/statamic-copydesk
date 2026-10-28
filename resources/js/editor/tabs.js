/**
 * Opens the tabs that hide an element, so a jump from the panel lands on
 * words the author can see. On a phone or a tablet Statamic puts the
 * sidebar in a tab of its own beside Main, so the panel and the field it
 * points at are never shown together; a blueprint's own tabs hide fields
 * the same way, at any width.
 *
 * Then runs `then`, once the opened tabs are drawn. Nothing hidden, and it
 * runs at once.
 */
export function revealThen(el, then) {
    if (! openTabsFor(el)) {
        then();

        return;
    }

    requestAnimationFrame(() => then());
}

/** Opens each tab panel holding `el` that is not open, outermost first. Says whether it opened any. */
export function openTabsFor(el, root = document) {
    const panels = [];

    for (let panel = el?.closest('[role="tabpanel"]'); panel; panel = panel.parentElement?.closest('[role="tabpanel"]')) {
        panels.unshift(panel);
    }

    let opened = false;

    for (const panel of panels) {
        const tab = panel.id ? root.querySelector('[role="tab"][aria-controls="' + CSS.escape(panel.id) + '"]') : null;

        if (! tab || tab.getAttribute('aria-selected') === 'true') {
            continue;
        }

        // Statamic's tabs (Reka) switch on a press or on Enter, never on a
        // bare click(), so the tab gets the Enter a keyboard user would give
        // it. Seen on a real form, not guessed.
        tab.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true, cancelable: true }));
        opened = true;
    }

    return opened;
}
