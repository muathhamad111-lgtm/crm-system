<?php

use App\Http\Controllers\RequestController;
use Illuminate\Support\Facades\Route;

Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
Route::get('/requests/new', [RequestController::class, 'create'])->name('requests.create');
Route::post('/requests', [RequestController::class, 'store'])->name('requests.store');
Route::get('/requests/{request}', [RequestController::class, 'show'])->name('requests.show');
Route::patch('/requests/{request}', [RequestController::class, 'update'])->name('requests.update');
Route::post('/requests/{request}/comments', [RequestController::class, 'comment'])->name('requests.comment');
Route::post('/requests/{request}/close', [RequestController::class, 'close'])->middleware('cap:request.close')->name('requests.close');
Route::post('/requests/{request}/reopen', [RequestController::class, 'reopen'])->name('requests.reopen');
Route::post('/requests/{request}/rate', [RequestController::class, 'rate'])->name('requests.rate');
