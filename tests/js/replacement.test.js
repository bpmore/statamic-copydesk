import { test } from 'node:test';
import assert from 'node:assert/strict';
import { widen } from '../../resources/js/editor/replacement.js';

function apply(text, from, to, suggestion) {
    const { back, forward } = widen(text.slice(0, from), text.slice(to), suggestion);

    return text.slice(0, from - back) + suggestion + text.slice(to + forward);
}

test('a spaced em dash becomes a comma that reads right', () => {
    assert.equal(apply('Plain words — short ones.', 12, 13, ', '), 'Plain words, short ones.');
    assert.equal(apply('Plain words — short ones.', 12, 13, ': '), 'Plain words: short ones.');
});

test('an unspaced em dash too', () => {
    assert.equal(apply('words—more', 5, 6, ', '), 'words, more');
});

test('a word for a word changes nothing around it', () => {
    assert.equal(apply('We utilize it.', 3, 10, 'use'), 'We use it.');
});

test('removing a phrase leaves one space', () => {
    assert.equal(apply('Note please be advised that it is late.', 5, 27, ''), 'Note it is late.');
    assert.equal(apply('Please be advised that it is late.', 0, 23, ''), 'it is late.');
});

test('a line break the author made stays', () => {
    assert.equal(apply('words\n— more', 6, 7, ', '), 'words\n, more');
});
