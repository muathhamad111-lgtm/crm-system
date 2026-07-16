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

// Customer 360 write / CRUD operations (staff only).
Route::post('/customers/{profile}/account', [CustomerController::class, 'updateAccount'])
    ->middleware('staff')->name('customers.account.update');
Route::post('/customers/{profile}/notes', [CustomerController::class, 'updateNotes'])
    ->middleware('staff')->name('customers.notes.update');

Route::post('/customers/{profile}/contacts', [CustomerController::class, 'storeContact'])
    ->middleware('staff')->name('customers.contacts.store');
Route::patch('/customers/{profile}/contacts/{contact}', [CustomerController::class, 'updateContact'])
    ->middleware('staff')->name('customers.contacts.update');
Route::delete('/customers/{profile}/contacts/{contact}', [CustomerController::class, 'destroyContact'])
    ->middleware('staff')->name('customers.contacts.destroy');

Route::post('/customers/{profile}/subscriptions', [CustomerController::class, 'storeSubscription'])
    ->middleware('staff')->name('customers.subscriptions.store');
Route::patch('/customers/{profile}/subscriptions/{subscription}', [CustomerController::class, 'updateSubscription'])
    ->middleware('staff')->name('customers.subscriptions.update');
Route::delete('/customers/{profile}/subscriptions/{subscription}', [CustomerController::class, 'destroySubscription'])
    ->middleware('staff')->name('customers.subscriptions.destroy');

Route::post('/customers/{profile}/activation-tasks', [CustomerController::class, 'storeActivationTask'])
    ->middleware('staff')->name('customers.activation-tasks.store');
Route::patch('/customers/{profile}/activation-tasks/{task}', [CustomerController::class, 'updateActivationTask'])
    ->middleware('staff')->name('customers.activation-tasks.update');
Route::delete('/customers/{profile}/activation-tasks/{task}', [CustomerController::class, 'destroyActivationTask'])
    ->middleware('staff')->name('customers.activation-tasks.destroy');

Route::post('/customers/{profile}/activities', [CustomerController::class, 'storeActivity'])
    ->middleware('staff')->name('customers.activities.store');

Route::post('/customers/{profile}/attachments', [CustomerController::class, 'storeAttachment'])
    ->middleware('staff')->name('customers.attachments.store');
Route::delete('/customers/{profile}/attachments/{attachment}', [CustomerController::class, 'destroyAttachment'])
    ->middleware('staff')->name('customers.attachments.destroy');

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
