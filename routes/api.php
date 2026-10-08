<?php

use App\Http\Controllers\ClinicController;
use Illuminate\Support\Facades\Route;

Route::prefix('clinic')->group(function () {
    Route::get('/bootstrap', [ClinicController::class, 'bootstrap']);
    Route::get('/slots', [ClinicController::class, 'slots']);
    Route::post('/appointments', [ClinicController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/appointments/lookup', [ClinicController::class, 'lookup'])->middleware('throttle:10,1');
    Route::middleware(['web', 'auth:clinic_admin'])->group(function () {
        Route::get('/services', [ClinicController::class, 'services']);
        Route::post('/services', [ClinicController::class, 'storeService']);
        Route::patch('/services/{service}', [ClinicController::class, 'updateService']);
        Route::delete('/services/{service}', [ClinicController::class, 'destroyService']);
        Route::get('/doctors', [ClinicController::class, 'doctors']);
        Route::post('/doctors', [ClinicController::class, 'storeDoctor']);
        Route::patch('/doctors/{doctor}', [ClinicController::class, 'updateDoctor']);
        Route::delete('/doctors/{doctor}', [ClinicController::class, 'destroyDoctor']);
        Route::get('/appointments', [ClinicController::class, 'index']);
        Route::patch('/appointments/{appointment}/status', [ClinicController::class, 'updateStatus']);
        Route::patch('/appointments/{appointment}/note', [ClinicController::class, 'updateNote']);
        Route::post('/slots', [ClinicController::class, 'storeSlot']);
        Route::patch('/slots/{slot}', [ClinicController::class, 'updateSlot']);
        Route::delete('/slots/{slot}', [ClinicController::class, 'destroySlot']);
    });
});