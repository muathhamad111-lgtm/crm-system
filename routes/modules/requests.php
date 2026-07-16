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

// Workspace actions
Route::post('/requests/{request}/assign-self', [RequestController::class, 'assignSelf'])->name('requests.assignSelf');
Route::post('/requests/{request}/escalate', [RequestController::class, 'escalate'])->name('requests.escalate');
Route::post('/requests/{request}/return-to-customer', [RequestController::class, 'returnToCustomer'])->name('requests.returnToCustomer');
Route::post('/requests/{request}/resume', [RequestController::class, 'resume'])->name('requests.resume');
Route::post('/requests/{request}/transition-stage', [RequestController::class, 'transitionStage'])->name('requests.transitionStage');
Route::post('/requests/{request}/tasks', [RequestController::class, 'addTask'])->name('requests.addTask');
Route::patch('/requests/{request}/tasks/{task}', [RequestController::class, 'updateTask'])->name('requests.updateTask');
Route::post('/requests/{request}/checklist/{item}', [RequestController::class, 'toggleChecklist'])->name('requests.toggleChecklist');
Route::post('/requests/{request}/verify', [RequestController::class, 'verifySolution'])->name('requests.verify');

// Supervisor approval
Route::post('/requests/{request}/request-approval', [RequestController::class, 'requestApproval'])->name('requests.requestApproval');
Route::post('/requests/{request}/decide-approval', [RequestController::class, 'decideApproval'])->name('requests.decideApproval');

// Tech-escalation bypass
Route::post('/requests/{request}/tech-bypass', [RequestController::class, 'techBypass'])->name('requests.techBypass');
Route::post('/requests/{request}/tech-bypass/approve', [RequestController::class, 'approveTechBypass'])->name('requests.approveTechBypass');

// Attachments
Route::post('/requests/{request}/attachments', [RequestController::class, 'uploadAttachment'])->name('requests.uploadAttachment');
Route::delete('/requests/{request}/attachments/{attachment}', [RequestController::class, 'deleteAttachment'])->name('requests.deleteAttachment');
