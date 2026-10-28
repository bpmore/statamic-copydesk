/**
 * Markdown, textarea and text fields: what Copydesk found, as a list under
 * the field (D4). A textarea or an input cannot draw underlines.
 *
 * Statamic's own fieldtype is wrapped, not replaced: the wrapper draws it
 * with everything it was given, and the list after it. The field that holds
 * the fieldtype reads its field actions and Replicator preview off it
 * through a template ref, so the wrapper hands those reads through to the
 * real fieldtype; without that a field's action menu would go missing.
 */
import { createChecker } from './checker.js';
import { forget, report } from './live.js';
import { widen } from './replacement.js';
import { SEVERITY, severityOf, suggestionLabel } from './severity.js';
import { revealThen } from './tabs.js';
import { targetFor } from './target.js';

const { h, ref, shallowRef, defineComponent, onMounted, onUpdated, onBeforeUnmount } = window.Vue;

export function withIssueList(original, kind) {
    return defineComponent({
        name: 'CopydeskIssueList',
        inheritAttrs: false,

        setup(_, { attrs, expose }) {
            const inner = ref(null);

            expose(new Proxy({}, {
                get: (_, k) => inner.value?.[k],
                has: (_, k) => inner.value != null && k in inner.value,
            }));

            // A text field outside a publish form (a settings screen, the
            // blueprint builder) has no container, and is left alone.
            let container = null;

            try {
                container = window.__STATAMIC__.ui.injectPublishContext();
            } catch {
                container = null;
            }

            const target = targetFor(container, { handle: attrs.handle, fieldPathPrefix: attrs.fieldPathPrefix, config: attrs.config }, kind);

            if (! target) {
                return () => h(original, { ...attrs, ref: inner });
            }

            // What was found, and the value it was found in: a suggestion is
            // only put in place while the text is still the text it was
            // found in.
            const found = shallowRef({ issues: [], value: null });
            let last = attrs.value;

            const checker = createChecker({
                target,
                kind,
                read: () => ({ value: typeof attrs.value === 'string' ? attrs.value : '' }),
                onResult(data, snapshot) {
                    found.value = { issues: data.issues ?? [], value: snapshot.value };
                    publish();
                },
            });

            onMounted(() => checker.schedule());

            onUpdated(() => {
                if (attrs.value !== last) {
                    last = attrs.value;
                    checker.schedule();
                }
            });

            // The list is read from the form, so an ignore added or cleared
            // anywhere shows here at once.
            const stopWatching = target.onIgnoresChange(() => publish());

            onBeforeUnmount(() => {
                checker.stop();
                stopWatching();
                forget(target.key);
            });

            const shown = () => {
                const ignores = target.ignores();

                return found.value.issues.filter((i) => ! ignores.includes(i.fingerprint));
            };
            const placeable = (issue) => issue.offset != null && kind !== 'markdown';

            // The input or textarea the fieldtype drew, by the id the field
            // gave it.
            const input = () => (attrs.id ? document.getElementById(attrs.id) : null);

            // Focused through the prototype: Statamic's textarea passes a
            // `focus` prop down to the element, and Vue sets it as a DOM
            // property, so the element's own `focus` is undefined. Seen on
            // a real form, not guessed.
            const focus = (el) => el && HTMLElement.prototype.focus.call(el);

            function replace(issue, text) {
                const now = typeof attrs.value === 'string' ? attrs.value : '';

                if (now !== found.value.value) {
                    checker.now();

                    return;
                }

                const value = now.replace(/\r\n?/g, '\n');
                const from = issue.offset;
                const to = issue.offset + issue.length;
                const { back, forward } = widen(value.slice(0, from), value.slice(to), text);
                const next = value.slice(0, from - back) + text + value.slice(to + forward);
                const caret = from - back + text.length;

                attrs['onUpdate:value']?.(next);
                found.value = { issues: [], value: null };

                requestAnimationFrame(() => {
                    const el = input();
                    focus(el);
                    el?.setSelectionRange?.(caret, caret);
                });
            }

            function ignore(issue) {
                target.ignore(issue.fingerprint);
                publish();
            }

            function reveal(issue) {
                const el = input();
                const list = document.querySelector('[data-copydesk-list="' + CSS.escape(target.key) + '"]');

                revealThen(el ?? list, () => {
                    if (el && placeable(issue)) {
                        focus(el);
                        el.setSelectionRange?.(issue.offset, issue.offset + issue.length);
                    } else {
                        list?.scrollIntoView({ block: 'center' });
                        focus(el);
                    }
                });

                return true;
            }

            function publish() {
                report(target.key, { label: target.label, issues: shown().map((issue) => ({ issue, reveal: () => reveal(issue) })) });
            }

            function list() {
                const issues = shown();

                if (! issues.length) {
                    return null;
                }

                return h('div', { class: 'copydesk-field-issues', 'data-copydesk-list': target.key }, [
                    h('p', { class: 'copydesk-sr-only' }, issues.length === 1 ? 'Copydesk found 1 thing in this field.' : 'Copydesk found ' + issues.length + ' things in this field.'),
                    h('ul', { class: 'copydesk-field-issues__list' }, issues.map((issue, i) => {
                        const severity = severityOf(issue);

                        return h('li', { key: i, class: 'copydesk-field-issue', 'data-severity': severity }, [
                            h('span', { class: 'copydesk-field-issue__kind' }, SEVERITY[severity].word),
                            ' ',
                            h('span', { class: 'copydesk-field-issue__message' }, issue.message),
                            issue.excerpt ? [' ', h('mark', { class: 'copydesk-field-issue__excerpt' }, issue.excerpt)] : null,
                            h('span', { class: 'copydesk-field-issue__actions' }, [
                                ...(placeable(issue)
                                    ? (issue.suggestions ?? []).map((s) => h('button', { type: 'button', class: 'copydesk-popover__button copydesk-popover__button--primary', onClick: () => replace(issue, s) }, suggestionLabel(s)))
                                    : (issue.suggestions ?? []).length ? [h('span', { class: 'copydesk-field-issue__try' }, 'Try: ' + issue.suggestions.map((s) => s.trim() || 'leaving it out').join(', '))] : []),
                                h('button', { type: 'button', class: 'copydesk-popover__button', onClick: () => ignore(issue) }, 'Ignore here'),
                            ]),
                        ]);
                    })),
                ]);
            }

            return () => [h(original, { ...attrs, ref: inner }), list()];
        },
    });
}
