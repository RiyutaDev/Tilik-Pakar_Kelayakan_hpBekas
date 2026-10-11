<?php

use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceCatalogController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/devices/autocomplete', [DashboardController::class, 'autocomplete'])->name('devices.autocomplete');
Route::get('/riwayat', [DashboardController::class, 'history'])->name('assessments.history');
Route::get('/devices', [DeviceCatalogController::class, 'index'])->name('devices.index');
Route::post('/devices/search', [DeviceCatalogController::class, 'search'])->middleware('throttle:30,1')->name('devices.search');
Route::post('/devices/select', [DeviceCatalogController::class, 'select'])->middleware('throttle:30,1')->name('devices.select');
Route::post('/assessments', [AssessmentController::class, 'store'])->name('assessments.store');
Route::get('/assessments/{assessmentSession}', [AssessmentController::class, 'show'])->name('assessments.show');
Route::post('/assessments/{assessmentSession}/evaluate', [AssessmentController::class, 'evaluate'])->name('assessments.evaluate');
Route::get('/assessments/{assessmentSession}/result', [AssessmentController::class, 'result'])->name('assessments.result');
