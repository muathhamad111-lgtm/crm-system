<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\SlaComplianceController;
use Illuminate\Support\Facades\Route;

// ---- Admin console (system_admin only) ----
Route::get('/admin', [AdminController::class, 'index'])
    ->middleware('admin')->name('admin.index');

Route::post('/admin/settings', [AdminController::class, 'updateSetting'])
    ->middleware('admin')->name('admin.setting');

Route::post('/admin/categories/{category}', [AdminController::class, 'updateCategory'])
    ->middleware('admin')->name('admin.category.update');

Route::post('/admin/priority-multiplier', [AdminController::class, 'setPriorityMultiplier'])
    ->middleware('admin')->name('admin.multiplier');

Route::post('/admin/roles/assign', [AdminController::class, 'assignRole'])
    ->middleware('admin')->name('admin.role.assign');

Route::post('/admin/roles/remove', [AdminController::class, 'removeRole'])
    ->middleware('admin')->name('admin.role.remove');

// ---- SLA compliance dashboard (staff) ----
Route::get('/sla-compliance', [SlaComplianceController::class, 'index'])
    ->middleware('staff')->name('sla.compliance');
