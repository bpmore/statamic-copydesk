/**
 * A suggestion as it should land: how far to widen the flagged range, and
 * what to put there, so the spacing around it reads right.
 *
 * The em dash rule suggests ", " for "words — more". Put in place of the
 * dash alone that reads "words ,  more"; widened over the spaces either
 * side it reads "words, more". A suggestion that starts with a mark takes
 * the spaces before it; one that ends with a space takes the spaces after
 * it; removing a word between two spaces takes one of them. Only spaces and
 * tabs: a line break the author made stays.
 *
 * @param {string} before  the text before the flagged range, in its block
 * @param {string} after   the text after it
 * @param {string} text    the suggestion
 * @returns {{back: number, forward: number}}
 */
export function widen(before, after, text) {
    const spacesBefore = (before.match(/[ \t]+$/) ?? [''])[0].length;
    const spacesAfter = (after.match(/^[ \t]+/) ?? [''])[0].length;

    if (text === '') {
        return { back: 0, forward: spacesBefore > 0 || before === '' ? spacesAfter : 0 };
    }

    return {
        back: /^[,.;:!?)\]]/.test(text) ? spacesBefore : 0,
        forward: /[ \t]$/.test(text) ? spacesAfter : 0,
    };
}
