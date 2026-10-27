/**
 * Where an issue the server found is in the editor's own document.
 *
 * The server places an issue by the Bard path of its block ("1.0.0": the
 * second top-level node, its first child, that child's first child) and an
 * offset and length in the block's text as written, counted in UTF-16 units,
 * which is how a JavaScript string counts. Here that becomes a ProseMirror
 * range.
 *
 * In the server's text a text node is its own characters, a hard break is
 * one character ("\n"), and any other inline node (an image, a mention) is
 * nothing; in the document each of them takes its own size. A path through a
 * set (it has a field name in it, "3.text.0") is never placed here: a Bard
 * inside a set is an editor of its own, and checks itself.
 *
 * Written against the few ProseMirror node members it needs (childCount,
 * child, nodeSize, isText, isTextblock, text, type.name), so it can be
 * tested without an editor.
 *
 * @returns {{from: number, to: number}|null}
 */
export function rangeOf(doc, issue) {
    if (issue.path == null || issue.offset == null || issue.length == null) {
        return null;
    }

    let node = doc;
    // Where the content of `node` starts: 0 for the document itself.
    let start = 0;

    for (const segment of String(issue.path).split('.')) {
        if (! /^\d+$/.test(segment)) {
            return null;
        }

        const index = Number(segment);

        if (index >= node.childCount) {
            return null;
        }

        for (let i = 0; i < index; i++) {
            start += node.child(i).nodeSize;
        }

        node = node.child(index);
        // Past the child's opening token, to the start of its content.
        start += 1;
    }

    if (! node.isTextblock) {
        return null;
    }

    const from = positionAt(node, start, issue.offset, 1);
    const to = positionAt(node, start, issue.offset + issue.length, -1);

    return from === null || to === null || to <= from ? null : { from, to };
}

/**
 * The document position of a character offset in a textblock. A range's
 * start (bias 1) that falls between two children belongs to the later one,
 * so it skips an image right before the flagged word; a range's end (bias
 * -1) belongs to the earlier one.
 */
function positionAt(block, start, offset, bias) {
    let chars = 0;
    let pos = start;

    for (let i = 0; i < block.childCount; i++) {
        const child = block.child(i);
        const length = child.isText ? child.text.length : (child.type.name === 'hardBreak' ? 1 : 0);

        if (length > 0 && (offset < chars + length || (bias < 0 && offset === chars + length))) {
            return pos + (offset - chars);
        }

        chars += length;
        pos += child.nodeSize;
    }

    return offset === chars ? pos : null;
}
