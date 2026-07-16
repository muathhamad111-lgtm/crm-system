<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\StoreContactController;
use App\Http\Controllers\TtsCustomerController;
use Illuminate\Support\Facades\Route;

// Customers directory + 360 profile.
Route::get('/customers', [CustomerController::class, 'index'])
    ->middleware('staff')->name('customers.index');
Route::get('/customers/{profile}', [CustomerController::class, 'show'])
    ->name('customers.show');

// Store (TTS) synced customers.
Route::get('/tts-customers', [TtsCustomerController::class, 'index'])
    ->middleware('staff')->name('tts.index');
Route::get('/tts-customers/{ttsCustomer}', [TtsCustomerController::class, 'show'])
    ->middleware('staff')->name('tts.show');

// Store contact inbox.
Route::get('/store-contact-inbox', [StoreContactController::class, 'index'])
    ->middleware('staff')->name('store-inbox.index');
Route::post('/store-contact-inbox/{message}/status', [StoreContactController::class, 'updateStatus'])
    ->middleware('staff')->name('store-inbox.update');
