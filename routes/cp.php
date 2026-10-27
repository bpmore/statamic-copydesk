<?php

declare(strict_types=1);

use Bpmore\Copydesk\Http\Controllers\CheckEntryController;
use Bpmore\Copydesk\Http\Controllers\LintController;
use Bpmore\Copydesk\Http\Controllers\OverrideController;
use Illuminate\Support\Facades\Route;

// POST rather than GET: the request carries the entry's unsaved values, which
// can be the whole page and are nobody's business in a URL or a server log.
Route::post('copydesk/check', CheckEntryController::class)->name('copydesk.check');

// One field, as the author types: what the editor underlines. POST for the
// same reason as the check.
Route::post('copydesk/lint', LintController::class)->name('copydesk.lint');

// "Publish anyway", with a reason: a one-time pass through the gate.
Route::post('copydesk/override', OverrideController::class)->name('copydesk.override');
