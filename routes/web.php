<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ObjectController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ObjectsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// ============ АУТЕНТИФИКАЦИЯ ============
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ============ ЗАЩИЩЁННЫЕ МАРШРУТЫ ============
Route::middleware(['check.session', 'log.action'])->group(function () {
    
    // Панель управления
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/stats', [DashboardController::class, 'stats'])->name('stats');
    
    // Управление объектами
    Route::get('/objects', [ObjectController::class, 'index'])->name('objects.index');
    Route::get('/objects/create', [ObjectController::class, 'create'])->name('objects.create')->middleware('admin.only');
    Route::post('/objects', [ObjectController::class, 'store'])->name('objects.store')->middleware('admin.only');
    Route::get('/objects/{object}/soe/flats', [ObjectsController::class, 'soeFlats'])->name('objects.soe.flats')->middleware('admin.only');
    Route::post('/objects/{object}/soe/flats/upload', [ObjectsController::class, 'uploadFlatList'])->name('objects.soe.flats.upload')->middleware('admin.only');
    Route::post('/objects/{object}/soe/flats', [ObjectsController::class, 'storeFlat'])->name('objects.soe.flats.store')->middleware('admin.only');
    Route::post('/objects/{object}/soe/flats/{flat}', [ObjectsController::class, 'updateFlat'])->name('objects.soe.flats.update')->middleware('admin.only');
    Route::delete('/objects/{object}/soe/flats/{flat}', [ObjectsController::class, 'deleteFlat'])->name('objects.soe.flats.delete')->middleware('admin.only');
    Route::get('/objects/{object}/soe', [ObjectsController::class, 'soe'])->name('objects.soe')->middleware('admin.only');
    Route::post('/objects/{object}/soe/save', [ObjectsController::class, 'saveSoe'])->name('objects.soe.save')->middleware('admin.only');
    Route::get('/objects/{object}/export', [ExportController::class, 'export'])->name('objects.export')->middleware('admin.only');
    Route::get('/objects/{object}/edit', [ObjectController::class, 'edit'])->name('objects.edit')->middleware('admin.only');
    Route::put('/objects/{object}', [ObjectController::class, 'update'])->name('objects.update')->middleware('admin.only');
    Route::post('/objects/{object}/check', [ObjectController::class, 'check'])->name('objects.check')->middleware('admin.only');
    Route::delete('/objects/{object}', [ObjectController::class, 'destroy'])->name('objects.destroy')->middleware('admin.only');
    Route::get('/objects/{object}', [ObjectController::class, 'show'])->name('objects.show');
    
    // Профиль пользователя
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
});

// ============ JSON API ============
Route::prefix('api')->group(function () {
    Route::get('/objects', [ObjectController::class, 'apiIndex'])->name('api.objects');
    Route::get('/objects/{object}', [ObjectController::class, 'apiShow'])->name('api.objects.show');
});
