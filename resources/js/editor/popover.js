/**
 * The box that opens on an underline: what is wrong, what to use instead,
 * and "Ignore here".
 *
 * Plain DOM rather than a Vue component, because it is opened from inside a
 * ProseMirror plugin, and one at a time for the whole page. It is a
 * non-modal dialog: Tab moves through its buttons, Escape closes it and puts
 * the caret back in the text where it was, and so does any choice made in
 * it. Opened with the keyboard, focus moves into it; opened with a click, it
 * stays in the text, where the author was typing.
 */
import { SEVERITY, severityOf, suggestionLabel } from './severity.js';

let open = null;

/**
 * @param {object} options
 * @param {() => ({left:number, top:number, bottom:number}|null)} options.anchor  where the flagged words are on screen now; null once they are gone
 * @param {object} options.issue
 * @param {boolean} options.focus  move focus into the box
 * @param {(text: string) => void} options.onReplace
 * @param {() => void} options.onIgnore
 * @param {() => void} options.onReturn  put focus back in the text
 */
export function openPopover({ anchor, issue, focus, onReplace, onIgnore, onReturn }) {
    closePopover();

    const severity = severityOf(issue);
    const box = document.createElement('div');
    const id = 'copydesk-popover-' + Math.random().toString(36).slice(2);

    box.className = 'copydesk-popover';
    box.dataset.severity = severity;
    box.setAttribute('role', 'dialog');
    box.setAttribute('aria-labelledby', id);

    const kind = document.createElement('p');
    kind.className = 'copydesk-popover__kind';
    kind.textContent = SEVERITY[severity].word;

    const message = document.createElement('p');
    message.className = 'copydesk-popover__message';
    message.id = id;
    message.textContent = issue.message;

    const actions = document.createElement('div');
    actions.className = 'copydesk-popover__actions';

    const button = (text, onClick, primary = false) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'copydesk-popover__button' + (primary ? ' copydesk-popover__button--primary' : '');
        b.textContent = text;
        b.addEventListener('click', () => {
            closePopover();
            onClick();
        });
        actions.appendChild(b);

        return b;
    };

    for (const s of issue.suggestions ?? []) {
        button(suggestionLabel(s), () => onReplace(s), true);
    }

    button('Ignore here', onIgnore);
    button('Close', onReturn);

    box.append(kind, message, actions);
    document.body.appendChild(box);
    place(box, anchor());

    box.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
            closePopover();
            onReturn();
        }
    });

    // Focus leaving the box for anywhere but the box closes it; a click in
    // the text it belongs to is handled by the editor, which reopens it if
    // the click was on an underline.
    box.addEventListener('focusout', (event) => {
        if (mine() && ! box.contains(event.relatedTarget)) {
            closePopover();
        }
    });

    // Each listener closes this box only, never a newer one: a box can be
    // replaced before its listeners are taken away.
    const mine = () => open?.box === box;

    const dismiss = (event) => {
        if (mine() && ! box.contains(event.target)) {
            closePopover();
        }
    };

    // The page scrolls under the box (the editor scrolls the caret into
    // view after a click, too), so the box follows its words, and closes
    // once they are gone.
    const onScroll = () => {
        if (! mine()) {
            return;
        }

        const rect = anchor();

        rect ? place(box, rect) : closePopover();
    };

    // After this click has finished, or it would close the box it opened.
    setTimeout(() => mine() && document.addEventListener('mousedown', dismiss, true));
    window.addEventListener('scroll', onScroll, true);

    open = {
        box,
        issue,
        teardown() {
            document.removeEventListener('mousedown', dismiss, true);
            window.removeEventListener('scroll', onScroll, true);
        },
    };

    if (focus) {
        actions.querySelector('button')?.focus();
    }
}

export function closePopover() {
    if (! open) {
        return;
    }

    const { box, teardown } = open;
    open = null;
    teardown();
    box.remove();
}

/** The issue whose box is open, if any. */
export function openIssue() {
    return open?.issue ?? null;
}

// Below the words, or above them when there is no room below; never off the
// side of the window.
function place(box, anchor) {
    const gap = 6;
    const margin = 8;
    const width = box.offsetWidth;
    const height = box.offsetHeight;
    const below = anchor.bottom + gap;
    const top = below + height > window.innerHeight - margin && anchor.top - gap - height > margin
        ? anchor.top - gap - height
        : below;

    box.style.top = Math.round(top) + 'px';
    box.style.left = Math.round(Math.max(margin, Math.min(anchor.left, window.innerWidth - width - margin))) + 'px';
}
