<?php

use Darvis\Printserver\Http\Controllers\PrintJobController;
use Darvis\Printserver\Http\Middleware\VerifyPrintboxToken;
use Darvis\Printserver\Support\PrintserverConfig;
use Illuminate\Support\Facades\Route;

/*
 * The endpoints a print box uses to fetch its work. No session and no login: a
 * box identifies itself with a token. See the readme of darvis/printserver.
 */
Route::prefix(PrintserverConfig::prefix())
    ->middleware(array_merge([VerifyPrintboxToken::class], PrintserverConfig::middleware()))
    ->group(function () {
        Route::post('/claim', [PrintJobController::class, 'claim'])->name('printserver.claim');
        Route::get('/file/{uuid}', [PrintJobController::class, 'file'])->name('printserver.file');
        Route::post('/done/{uuid}', [PrintJobController::class, 'done'])->name('printserver.done');
        Route::post('/failed/{uuid}', [PrintJobController::class, 'failed'])->name('printserver.failed');
    });
