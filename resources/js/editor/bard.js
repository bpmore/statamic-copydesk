/**
 * Underlines in Bard, as the author types.
 *
 * A TipTap extension, added through `Statamic.$bard.addExtension`, built from
 * the TipTap and ProseMirror that Bard hands it rather than a copy bundled
 * here: decorations made with a second copy of ProseMirror are silently
 * ignored (the Phase 1.5 spike).
 *
 * Each Bard on the page checks itself: a Bard nested in a set, a Replicator
 * or a Grid is an editor of its own, and so gets its own underlines. The
 * server's answer is placed in the document as it was when the question was
 * asked, then carried through everything typed since, so underlines land
 * on the right words even when the author kept typing.
 */
import { announce, SHORTCUT } from './announce.js';
import { createChecker } from './checker.js';
import { forget, report } from './live.js';
import { closePopover, openIssue, openPopover } from './popover.js';
import { rangeOf } from './positions.js';
import { widen } from './replacement.js';
import { SEVERITY, severityOf } from './severity.js';
import { targetFor } from './target.js';

// The same finding to a listener, so moving the caret within one underline
// is not announced again.
const spokenKey = (issue) => issue.rule + '\u0000' + issue.excerpt;

export function bardLint({ bard, tiptap }) {
    const target = targetFor(bard.publishContainer, bard, 'bard');

    if (! target) {
        return [];
    }

    const { Extension } = tiptap.core;
    const { Plugin, PluginKey, TextSelection } = tiptap.pm.state;
    const { Decoration, DecorationSet } = tiptap.pm.view;
    const key = new PluginKey('copydeskLint');

    // The decorations and, since the last question was sent, every change
    // to the document, to carry the answer through.
    const plugin = (editor) => new Plugin({
        key,

        state: {
            init: () => ({ set: DecorationSet.empty, maps: [] }),

            apply(tr, value) {
                let { set, maps } = value;
                const meta = tr.getMeta(key);

                if (tr.docChanged) {
                    set = set.map(tr.mapping, tr.doc);
                    maps.push(tr.mapping);
                }

                if (meta?.sent) {
                    maps = [];
                }

                if (meta?.found) {
                    set = DecorationSet.create(tr.doc, meta.found.map(({ from, to, issue }) => Decoration.inline(
                        from,
                        to,
                        { class: 'copydesk-issue copydesk-issue--' + severityOf(issue), 'data-copydesk-rule': issue.rule },
                        { issue },
                    )));
                }

                if (meta?.remove) {
                    set = set.remove(set.find(undefined, undefined, (spec) => meta.remove(spec.issue)));
                }

                return { set, maps };
            },
        },

        props: {
            decorations: (state) => key.getState(state).set,

            handleClick(view, pos) {
                const found = at(view.state, pos);

                if (found) {
                    show(view, found, false);
                }

                return false;
            },

            handleKeyDown(view, event) {
                if (! (event.altKey && event.key === 'Enter')) {
                    return false;
                }

                const found = at(view.state, view.state.selection.from);

                if (! found) {
                    return false;
                }

                show(view, found, true);

                return true;
            },
        },

        view(view) {
            let spoken = null;

            const checker = createChecker({
                target,
                kind: 'bard',
                read() {
                    view.dispatch(view.state.tr.setMeta(key, { sent: true }).setMeta('addToHistory', false));

                    return { value: editor.getJSON().content ?? [], doc: view.state.doc };
                },
                onResult(data, snapshot) {
                    if (view.isDestroyed) {
                        return;
                    }

                    const { maps } = key.getState(view.state);
                    const ignores = target.ignores();
                    const found = [];

                    for (const issue of data.issues ?? []) {
                        const range = ignores.includes(issue.fingerprint) ? null : rangeOf(snapshot.doc, issue);

                        if (! range) {
                            continue;
                        }

                        const from = maps.reduce((p, m) => m.map(p, 1), range.from);
                        const to = maps.reduce((p, m) => m.map(p, -1), range.to);

                        if (from < to) {
                            found.push({ from, to, issue });
                        }
                    }

                    view.dispatch(view.state.tr.setMeta(key, { found }).setMeta('addToHistory', false));
                    publish(view);
                },
            });

            if (view.editable) {
                checker.schedule();
            }

            const stopWatching = target.onIgnoresChange(() => checker.schedule());

            return {
                update(view, previous) {
                    if (! view.state.doc.eq(previous.doc) && view.editable) {
                        checker.schedule();
                    }

                    // Say what the underline under the caret says, once each
                    // time the caret comes to one; and close a box that was
                    // opened with a click once the caret leaves its words.
                    const { selection } = view.state;
                    const here = selection.empty ? at(view.state, selection.from) : null;
                    const said = here ? spokenKey(here.issue) : null;

                    if (said && said !== spoken) {
                        announce(SEVERITY[severityOf(here.issue)].word + ': ' + here.issue.message + ' ' + SHORTCUT + ' for choices.');
                    }

                    spoken = said;

                    const shown = openIssue();

                    if (shown && ! find(view.state, shown) && view.hasFocus()) {
                        closePopover();
                    }
                },

                destroy() {
                    checker.stop();
                    stopWatching();
                    forget(target.key);

                    if (find(view.state, openIssue())) {
                        closePopover();
                    }
                },
            };
        },
    });

    // The issue under a position: the innermost, when two overlap.
    function at(state, pos) {
        const found = key.getState(state).set.find(pos, pos).filter((d) => d.from <= pos && pos <= d.to);

        if (! found.length) {
            return null;
        }

        const d = found.reduce((a, b) => (b.to - b.from < a.to - a.from ? b : a));

        return { from: d.from, to: d.to, issue: d.spec.issue };
    }

    // Where an issue is now, after whatever was typed since it was found.
    function find(state, issue) {
        if (! issue) {
            return null;
        }

        // The same object, or, after a newer answer replaced them all, the
        // same finding: same rule, same words, same place.
        const same = (other) => other === issue || (other.rule === issue.rule && other.excerpt === issue.excerpt && other.path === issue.path && other.offset === issue.offset);
        const d = key.getState(state).set.find(undefined, undefined, (spec) => same(spec.issue))[0];

        return d ? { from: d.from, to: d.to, issue } : null;
    }

    // Where the words are on screen, from where they are in the document
    // now; null once they are gone.
    function show(view, found, focus) {
        const anchor = () => {
            const now = view.isDestroyed ? null : find(view.state, found.issue);

            if (! now) {
                return null;
            }

            const start = view.coordsAtPos(now.from);
            const end = view.coordsAtPos(now.to);

            return { left: start.left, top: Math.min(start.top, end.top), bottom: Math.max(start.bottom, end.bottom) };
        };

        openPopover({
            anchor,
            issue: found.issue,
            focus,
            onReplace: (text) => replace(view, found.issue, text),
            onIgnore: () => ignore(view, found.issue),
            onReturn: () => view.focus(),
        });
    }

    function replace(view, issue, text) {
        const now = find(view.state, issue);

        if (! now) {
            view.focus();

            return;
        }

        const { doc } = view.state;
        const $from = doc.resolve(now.from);
        const $to = doc.resolve(now.to);
        const before = doc.textBetween($from.start(), now.from, '\n', '\n');
        const after = doc.textBetween(now.to, $to.end(), '\n', '\n');
        const { back, forward } = widen(before, after, text);
        const from = now.from - back;
        const tr = view.state.tr.insertText(text, from, now.to + forward);

        tr.setSelection(TextSelection.create(tr.doc, from + text.length));
        view.dispatch(tr.scrollIntoView());
        view.focus();
    }

    // Kept on the entry (the hidden field), so it holds after a save, and
    // in the gate; every underline on the same sentence for the same rule
    // goes with it.
    function ignore(view, issue) {
        target.ignore(issue.fingerprint);
        view.dispatch(view.state.tr.setMeta(key, { remove: (i) => i.fingerprint === issue.fingerprint }).setMeta('addToHistory', false));
        view.focus();
        publish(view);
    }

    // This field's issues, for the sidebar panel, each with a way to jump
    // to it: the caret on the words, scrolled into view, the box open.
    function publish(view) {
        const issues = key.getState(view.state).set.find().sort((a, b) => a.from - b.from).map((d) => ({
            issue: d.spec.issue,
            reveal() {
                const now = find(view.state, d.spec.issue);

                if (! now) {
                    return false;
                }

                view.dispatch(view.state.tr.setSelection(TextSelection.create(view.state.doc, now.from, now.to)).scrollIntoView());
                view.focus();
                requestAnimationFrame(() => show(view, now, false));

                return true;
            },
        }));

        report(target.key, { label: target.label, issues });
    }

    return Extension.create({
        name: 'copydeskLint',

        addProseMirrorPlugins() {
            return [plugin(this.editor)];
        },
    });
}
