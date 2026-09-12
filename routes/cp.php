<?php

declare(strict_types=1);

use Bpmore\Plain\Http\Controllers\CheckEntryController;
use Illuminate\Support\Facades\Route;

// POST rather than GET: the request carries the entry's unsaved values, which
// can be the whole page and are nobody's business in a URL or a server log.
Route::post('plain/check', CheckEntryController::class)->name('plain.check');
