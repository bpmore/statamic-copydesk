<?php

declare(strict_types=1);

use Bpmore\Copydesk\Support\CsvSafe;

/**
 * The issues download is opened in a spreadsheet, and a page title is an
 * author's to write.
 */
it('puts an apostrophe before a cell a spreadsheet would run as a formula', function (string $start) {
    expect(CsvSafe::cell($start.'SUM(A1)'))->toBe("'".$start.'SUM(A1)');
})->with(['=', '+', '-', '@', "\t", "\r"]);

it('leaves every other cell as it is, numbers included', function () {
    expect(CsvSafe::cell('A flu shot keeps you well'))->toBe('A flu shot keeps you well')
        ->and(CsvSafe::cell('What = is'))->toBe('What = is')
        ->and(CsvSafe::cell("'=already text"))->toBe("'=already text")
        ->and(CsvSafe::cell(''))->toBe('')
        ->and(CsvSafe::cell(-1))->toBe(-1)
        ->and(CsvSafe::cell(null))->toBeNull();
});
