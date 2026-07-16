<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

/*
 | Appointments + Calendar + Notifications module.
 | Required inside the ['auth','verified'] group in routes/web.php.
 */

// ---- Appointments (customer) ----
Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
Route::get('/appointments/new', [AppointmentController::class, 'create'])->name('appointments.create');
Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
Route::get('/appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');
Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
Route::post('/appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('appointments.reschedule');

// ---- Appointments (staff) ----
Route::post('/appointments/{appointment}/confirm', [AppointmentController::class, 'confirm'])->middleware('staff')->name('appointments.confirm');
Route::post('/appointments/{appointment}/reject', [AppointmentController::class, 'reject'])->middleware('staff')->name('appointments.reject');
Route::get('/admin/appointments', [AppointmentController::class, 'manage'])->middleware('staff')->name('appointments.manage');

// ---- Calendar (staff) ----
Route::get('/calendar', [CalendarController::class, 'index'])->middleware('staff')->name('calendar.index');
Route::post('/calendar', [CalendarController::class, 'store'])->middleware('staff')->name('calendar.store');
Route::patch('/calendar/{event}', [CalendarController::class, 'update'])->middleware('staff')->name('calendar.update');
Route::delete('/calendar/{event}', [CalendarController::class, 'destroy'])->middleware('staff')->name('calendar.destroy');

// ---- Notifications ----
Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
