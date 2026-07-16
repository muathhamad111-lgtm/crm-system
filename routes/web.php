<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'home'])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/account', [ProfileController::class, 'edit'])->name('account');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // CRM modules — each file is self-contained (controllers + routes) so modules
    // can be developed independently without editing this shared file.
    foreach (glob(base_path('routes/modules/*.php')) as $moduleRoutes) {
        require $moduleRoutes;
    }
});

require __DIR__.'/auth.php';
