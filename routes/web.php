<?php

use App\Http\Controllers\ClinicAuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/clinic', 'clinic')->name('clinic');
Route::get('/clinic/admin/login', [ClinicAuthController::class, 'showLogin'])->name('clinic.admin.login');
Route::post('/clinic/admin/login', [ClinicAuthController::class, 'login'])->name('clinic.admin.login.submit');
Route::redirect('/login', '/clinic/admin/login')->name('login.form');
Route::post('/clinic/admin/logout', [ClinicAuthController::class, 'logout'])->middleware('auth:clinic_admin')->name('clinic.admin.logout');
Route::get('/clinic/admin', function () {
	if (!Auth::guard('clinic_admin')->check()) {
		return redirect()->route('clinic.admin.login');
	}

	return view('clinic-admin');
})->middleware('auth:clinic_admin')->name('clinic.admin');
Route::redirect('/', '/clinic');
