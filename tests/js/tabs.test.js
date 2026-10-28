import { test } from 'node:test';
import assert from 'node:assert/strict';
import { openTabsFor } from '../../resources/js/editor/tabs.js';

// Node has no DOM: the few members openTabsFor reads, and a record of the
// Enter each tab is given.
globalThis.CSS = { escape: (s) => s };
globalThis.KeyboardEvent = class { constructor(type, init) { Object.assign(this, { type }, init); } };

function form(open) {
    const pressed = [];
    const tab = (id) => ({
        getAttribute: (name) => ({ 'aria-selected': open.includes(id) ? 'true' : 'false' })[name],
        dispatchEvent: (e) => pressed.push(id + ':' + e.key),
    });
    const tabs = { outer: tab('outer'), inner: tab('inner') };

    // The field sits in the inner panel, which sits in the outer one.
    const outer = { id: 'outer', parentElement: { closest: () => null } };
    const inner = { id: 'inner', parentElement: { closest: () => outer } };
    const field = { closest: () => inner };
    const root = { querySelector: (s) => tabs[s.match(/aria-controls="(.+)"/)[1]] };

    return { field, root, pressed };
}

test('opens every closed tab around the field, outermost first', () => {
    const { field, root, pressed } = form([]);

    assert.equal(openTabsFor(field, root), true);
    assert.deepEqual(pressed, ['outer:Enter', 'inner:Enter']);
});

test('leaves an open tab alone', () => {
    const { field, root, pressed } = form(['outer']);

    assert.equal(openTabsFor(field, root), true);
    assert.deepEqual(pressed, ['inner:Enter']);
});

test('says so when nothing hides the field', () => {
    const { field, root, pressed } = form(['outer', 'inner']);

    assert.equal(openTabsFor(field, root), false);
    assert.deepEqual(pressed, []);
    assert.equal(openTabsFor({ closest: () => null }, root), false);
    assert.equal(openTabsFor(null, root), false);
});
