<?php

use App\Http\Controllers\Api\PublicApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('public/v1')->middleware('api.key')->group(function () {
    Route::get('ping', [PublicApiController::class, 'ping']);
    Route::get('requests', [PublicApiController::class, 'requests'])->middleware('api.key:read');
});
