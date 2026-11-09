<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Support;

/**
 * A cell a spreadsheet will show rather than run.
 *
 * Excel, Sheets and LibreOffice read a cell that starts with `=`, `+`, `-`
 * or `@` as a formula, and a tab or carriage return at the start as the
 * same thing wrapped in whitespace. A page title is an author's to write,
 * and a formula in one would run on the machine of whoever opens the
 * download. A leading apostrophe is the spreadsheet's own way of saying
 * "this is text", and it does not show in the cell.
 */
final class CsvSafe
{
    private const TRIGGERS = ['=', '+', '-', '@', "\t", "\r"];

    public static function cell(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        return in_array($value[0], self::TRIGGERS, true) ? "'".$value : $value;
    }
}
