/**
 * Copydesk in the editor: underlines in Bard, a list under markdown,
 * textarea and text fields.
 *
 * Bard takes extensions through its own API. The other three are Statamic's
 * own components, wrapped once the control panel has registered them
 * (`configuring` runs after the core fieldtypes are registered and before
 * the app mounts) and before anything draws one.
 */
import './editor.css';
import { bardLint } from './bard.js';
import { withIssueList } from './field-list.js';

Statamic.$bard.addExtension(bardLint);

Statamic.configuring(() => {
    const app = Statamic.$app;

    for (const [name, kind] of [['markdown-fieldtype', 'markdown'], ['textarea-fieldtype', 'textarea'], ['text-fieldtype', 'text']]) {
        const original = app.component(name);

        if (original) {
            app.component(name, withIssueList(original, kind));
        }
    }
});
