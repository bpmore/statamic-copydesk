import { test } from 'node:test';
import assert from 'node:assert/strict';
import { rangeOf } from '../../resources/js/editor/positions.js';

// The few ProseMirror node members rangeOf reads, built from Bard's JSON.
function node(json) {
    if (json.type === 'text') {
        return { type: { name: 'text' }, isText: true, isTextblock: false, text: json.text, nodeSize: json.text.length, childCount: 0 };
    }

    const children = (json.content ?? []).map(node);
    const leaf = ['hardBreak', 'image', 'set', 'horizontalRule'].includes(json.type);

    return {
        type: { name: json.type },
        isText: false,
        isTextblock: ['paragraph', 'heading'].includes(json.type),
        childCount: children.length,
        child: (i) => children[i],
        nodeSize: leaf ? 1 : 2 + children.reduce((n, c) => n + c.nodeSize, 0),
    };
}

const doc = (...content) => node({ type: 'doc', content });
const p = (...content) => ({ type: 'paragraph', content });
const t = (text) => ({ type: 'text', text });

// What ProseMirror's textBetween would give, from positions back to text.
function textAt(json, from, to) {
    let out = '';
    let pos = 0;

    (function walk(n) {
        if (n.type === 'text') {
            for (const ch of n.text.split('')) {
                if (pos >= from && pos < to) out += ch;
                pos++;
            }
            return;
        }
        if (['hardBreak', 'image', 'set'].includes(n.type)) { if (pos >= from && pos < to && n.type === 'hardBreak') out += '\n'; pos++; return; }
        if (n.type !== 'doc') pos++;
        (n.content ?? []).forEach(walk);
        if (n.type !== 'doc') pos++;
    })(json);

    return out;
}

test('places an issue in the first paragraph', () => {
    const json = { type: 'doc', content: [p(t('Plain words — short ones.'))] };
    const range = rangeOf(node(json), { path: '0', offset: 12, length: 1 });

    assert.deepEqual(range, { from: 13, to: 14 });
    assert.equal(textAt(json, range.from, range.to), '—');
});

test('counts the nodes before it, and an empty paragraph as two', () => {
    const json = { type: 'doc', content: [p(t('One.')), { type: 'paragraph' }, { type: 'set', attrs: {} }, p(t('We utilize it.'))] };
    const range = rangeOf(node(json), { path: '3', offset: 3, length: 7 });

    assert.equal(textAt(json, range.from, range.to), 'utilize');
});

test('walks into a list item by its path', () => {
    const json = { type: 'doc', content: [{ type: 'bulletList', content: [{ type: 'listItem', content: [p(t('We utilize it.'))] }] }] };
    const range = rangeOf(node(json), { path: '0.0.0', offset: 3, length: 7 });

    assert.equal(textAt(json, range.from, range.to), 'utilize');
});

test('counts a hard break as one character and an image as none', () => {
    const json = { type: 'doc', content: [p(t('Line.'), { type: 'hardBreak' }, { type: 'image' }, t('We utilize it.'))] };
    // "Line.\nWe utilize it." in the server's text: utilize starts at 9.
    const range = rangeOf(node(json), { path: '0', offset: 9, length: 7 });

    assert.equal(textAt(json, range.from, range.to), 'utilize');
});

test('starts after an image that sits right before the word', () => {
    const json = { type: 'doc', content: [p(t('A '), { type: 'image' }, t('utilize'))] };
    const range = rangeOf(node(json), { path: '0', offset: 2, length: 7 });

    assert.equal(textAt(json, range.from, range.to), 'utilize');
    assert.equal(range.from, 4);
});

test('counts across text nodes with different marks', () => {
    const json = { type: 'doc', content: [p(t('Run '), t('a  b'), t(' and  go.'))] };
    const range = rangeOf(node(json), { path: '0', offset: 12, length: 2 });

    assert.equal(textAt(json, range.from, range.to), '  ');
});

test('counts an emoji as two units, as the server does', () => {
    const json = { type: 'doc', content: [p(t('😀 “Café” — yes.'))] };
    const range = rangeOf(node(json), { path: '0', offset: 10, length: 1 });

    assert.equal(textAt(json, range.from, range.to), '—');
});

test('does not place what it cannot: a path through a set, past the end, or no position', () => {
    const d = doc(p(t('Short.')));

    assert.equal(rangeOf(d, { path: '0.text.0', offset: 0, length: 1 }), null);
    assert.equal(rangeOf(d, { path: '4', offset: 0, length: 1 }), null);
    assert.equal(rangeOf(d, { path: '0', offset: 5, length: 9 }), null);
    assert.equal(rangeOf(d, { path: null, offset: null, length: null }), null);
    assert.equal(rangeOf(doc({ type: 'bulletList', content: [] }), { path: '0', offset: 0, length: 1 }), null);
});
