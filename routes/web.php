<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ObjectController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ObjectsController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\SoeReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(session('user_id') ? 'dashboard' : 'login');
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
    Route::get('/objects/{object}/devices/upload', [ObjectsController::class, 'showDeviceUpload'])->name('objects.devices.upload')->middleware('admin.only');
    Route::post('/objects/{object}/devices/upload', [ObjectsController::class, 'storeDeviceUpload'])->name('objects.devices.upload.store')->middleware('admin.only');
    Route::get('/objects/{object}/import-csv', [ImportController::class, 'show'])->name('objects.import-csv')->middleware('admin.only');
    Route::post('/objects/{object}/import-csv/apator-water', [ImportController::class, 'apatorWater'])->name('objects.import-csv.apator-water')->middleware('admin.only');
    Route::post('/objects/{object}/import-csv/apator-heater', [ImportController::class, 'apatorHeater'])->name('objects.import-csv.apator-heater')->middleware('admin.only');
    Route::post('/objects/{object}/import-csv/siemens', [ImportController::class, 'siemens'])->name('objects.import-csv.siemens')->middleware('admin.only');
    Route::get('/objects/{object}/soe/flats', [ObjectsController::class, 'soeFlats'])->name('objects.soe.flats')->middleware('admin.only');
    Route::post('/objects/{object}/soe/radiators/upload', [ObjectsController::class, 'uploadRadiatorList'])->name('objects.soe.radiators.upload')->middleware('admin.only');
    Route::post('/objects/{object}/soe/flats/upload', [ObjectsController::class, 'uploadFlatList'])->name('objects.soe.flats.upload')->middleware('admin.only');
    Route::post('/objects/{object}/soe/flats', [ObjectsController::class, 'storeFlat'])->name('objects.soe.flats.store')->middleware('admin.only');
    Route::post('/objects/{object}/soe/flats/{flat}', [ObjectsController::class, 'updateFlat'])->name('objects.soe.flats.update')->middleware('admin.only');
    Route::delete('/objects/{object}/soe/flats/{flat}', [ObjectsController::class, 'deleteFlat'])->name('objects.soe.flats.delete')->middleware('admin.only');
    Route::get('/objects/{object}/soe', [ObjectsController::class, 'soe'])->name('objects.soe')->middleware('admin.only');
    Route::post('/objects/{object}/soe/save', [ObjectsController::class, 'saveSoe'])->name('objects.soe.save')->middleware('admin.only');
    Route::get('/objects/{object}/soe/report.csv', [SoeReportController::class, 'csv'])->name('objects.soe.report.csv')->middleware('admin.only');
    Route::get('/objects/{object}/soe/report.xlsx', [SoeReportController::class, 'xlsx'])->name('objects.soe.report.xlsx')->middleware('admin.only');
    Route::get('/objects/{object}/export', [ExportController::class, 'export'])->name('objects.export')->middleware('admin.only');
    Route::get('/objects/{object}/export-current', [ExportController::class, 'export'])->name('objects.export.current')->middleware('admin.only');
    Route::get('/objects/{object}/export-month-start', [ExportController::class, 'exportMonthStart'])->name('objects.export.month_start')->middleware('admin.only');
    Route::get('/objects/{object}/export-alokator', [ExportController::class, 'exportAlokator'])->name('objects.export.alokator')->middleware('admin.only');
    Route::get('/objects/{object}/export-korto', [ExportController::class, 'exportKorto'])->name('objects.export.korto')->middleware('admin.only');
    Route::get('/objects/{object}/edit', [ObjectController::class, 'edit'])->name('objects.edit')->middleware('admin.only');
    Route::put('/objects/{object}', [ObjectController::class, 'update'])->name('objects.update')->middleware('admin.only');
    Route::post('/objects/{object}/check', [ObjectController::class, 'check'])->name('objects.check')->middleware('admin.only');
    Route::delete('/objects/{object}', [ObjectController::class, 'destroy'])->name('objects.destroy')->middleware('admin.only');
    Route::post('/objects/{object}/command', [ObjectsController::class, 'sendCommand'])->name('objects.command')->middleware('admin.only');
    Route::get('/objects/{object}', [ObjectController::class, 'show'])->name('objects.show');
    
    // Профиль пользователя
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
});

// ============ JSON API ============
Route::prefix('api')->group(function () {
    Route::get('/objects', [ObjectController::class, 'apiIndex'])->name('api.objects');
    Route::get('/objects/{object}', [ObjectController::class, 'apiShow'])->name('api.objects.show');
});
