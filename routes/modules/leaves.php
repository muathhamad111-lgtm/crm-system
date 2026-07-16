<?php

use App\Http\Controllers\LeaveController;
use Illuminate\Support\Facades\Route;

/*
 | Employee leaves self-service. Required inside the shared auth+verified
 | group (see routes/web.php). Staff-only.
 */

Route::middleware('staff')->group(function () {
    Route::get('/leaves', [LeaveController::class, 'index'])->name('leaves.index');
    Route::post('/leaves', [LeaveController::class, 'store'])->name('leaves.store');
    Route::post('/leaves/{leave}/cancel', [LeaveController::class, 'cancel'])->name('leaves.cancel');
});
