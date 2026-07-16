<?php

use App\Http\Controllers\RatingController;
use App\Http\Controllers\SuggestionController;
use Illuminate\Support\Facades\Route;

// Suggestions (idea board) + engagement
Route::get('/suggestions', [SuggestionController::class, 'board'])->name('suggestions.board');
Route::get('/suggestions/new', [SuggestionController::class, 'create'])->name('suggestions.create');
Route::post('/suggestions', [SuggestionController::class, 'store'])->name('suggestions.store');
Route::get('/suggestions/mine', [SuggestionController::class, 'mine'])->name('suggestions.mine');
Route::get('/suggestions/inbox', [SuggestionController::class, 'inbox'])->middleware('staff')->name('suggestions.inbox');
Route::get('/suggestions/{request}', [SuggestionController::class, 'show'])->name('suggestions.show');

Route::post('/suggestions/{request}/vote', [SuggestionController::class, 'vote'])->name('suggestions.vote');
Route::post('/suggestions/{request}/comment', [SuggestionController::class, 'comment'])->name('suggestions.comment');
Route::post('/suggestions/{request}/rate', [SuggestionController::class, 'rate'])->name('suggestions.rate');
Route::post('/suggestions/{request}/advance', [SuggestionController::class, 'advance'])->middleware('staff')->name('suggestions.advance');
Route::post('/suggestions/{request}/score', [SuggestionController::class, 'score'])->middleware('staff')->name('suggestions.score');
Route::post('/suggestions/{request}/publish', [SuggestionController::class, 'publish'])->middleware('staff')->name('suggestions.publish');

// Ratings (CSAT)
Route::get('/ratings', [RatingController::class, 'index'])->name('ratings.index');
