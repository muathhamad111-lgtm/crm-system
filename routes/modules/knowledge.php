<?php

use App\Http\Controllers\KbController;
use Illuminate\Support\Facades\Route;

/*
 | Knowledge Base module. Required inside the shared auth+verified group
 | (see routes/web.php). All routes are staff-only; fine-grained actions
 | are gated by capabilities (kb.read/author/approve/manage/rate).
 */

Route::middleware('staff')->group(function () {
    Route::get('/knowledge-base', [KbController::class, 'index'])
        ->middleware('cap:kb.read')->name('kb.index');

    // Static segments before the wildcard so they are not swallowed by {article}.
    Route::get('/knowledge-base/new', [KbController::class, 'create'])
        ->middleware('cap:kb.author')->name('kb.create');
    Route::post('/knowledge-base', [KbController::class, 'store'])
        ->middleware('cap:kb.author')->name('kb.store');

    Route::get('/knowledge-base/manage', [KbController::class, 'manage'])
        ->middleware('cap:kb.approve')->name('kb.manage');
    Route::post('/knowledge-base/gaps', [KbController::class, 'storeGap'])
        ->middleware('cap:kb.manage')->name('kb.gaps.store');
    Route::post('/knowledge-base/gaps/{gap}/status', [KbController::class, 'updateGap'])
        ->middleware('cap:kb.manage')->name('kb.gaps.status');

    Route::get('/knowledge-base/{article}', [KbController::class, 'show'])
        ->middleware('cap:kb.read')->name('kb.show');
    Route::get('/knowledge-base/{article}/edit', [KbController::class, 'edit'])
        ->middleware('cap:kb.author')->name('kb.edit');
    Route::put('/knowledge-base/{article}', [KbController::class, 'update'])
        ->middleware('cap:kb.author')->name('kb.update');

    Route::post('/knowledge-base/{article}/status', [KbController::class, 'status'])
        ->middleware('cap:kb.approve')->name('kb.status');
    Route::post('/knowledge-base/{article}/rate', [KbController::class, 'rate'])->name('kb.rate');
    Route::post('/knowledge-base/{article}/feedback', [KbController::class, 'feedback'])->name('kb.feedback');
});
