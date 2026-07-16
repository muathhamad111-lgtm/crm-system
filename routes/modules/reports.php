<?php

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
 | BI / reports module. Required inside the shared auth+verified group
 | (see routes/web.php). Staff-only; capabilities gate each report.
 */

Route::middleware('staff')->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('cap:report.ops')->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])
        ->middleware('cap:report.ops')->name('reports.export');
    Route::get('/reports/employee/{profile}', [ReportController::class, 'employee'])
        ->middleware('cap:report.employee')->name('reports.employee');
});
