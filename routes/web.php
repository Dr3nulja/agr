<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ObjectController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ObjectsController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\SoeReportController;
use App\Http\Controllers\ObjectTaskController;
use App\Http\Controllers\MobileReportController;
use App\Http\Controllers\InstallationController;
use App\Http\Controllers\SimCardController;
use App\Http\Controllers\ReadingsController;
use App\Http\Controllers\Api\MobileAppController;
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
    Route::post('/objects/{object}/uncheck', [ObjectController::class, 'uncheck'])->name('objects.uncheck')->middleware('admin.only');
    Route::post('/objects/{object}/send-report', [ObjectController::class, 'sendReport'])->name('objects.send-report')->middleware('admin.only');

    // Näidud: показания, график, экспорт, ручная правка, фиксация месяца
    Route::get('/objects/{object}/readings', [ReadingsController::class, 'index'])->name('readings.index');
    Route::get('/objects/{object}/readings/export.xlsx', [ReadingsController::class, 'xlsx'])->name('readings.xlsx');
    Route::get('/objects/{object}/readings/print', [ReadingsController::class, 'print'])->name('readings.print');
    Route::get('/objects/{object}/readings/{devid}', [ReadingsController::class, 'device'])->name('readings.device')->whereNumber('devid');
    Route::middleware('admin.only')->group(function () {
        Route::post('/objects/{object}/readings/fix-month', [ReadingsController::class, 'fixMonth'])->name('readings.fix-month');
        Route::post('/objects/{object}/readings/{devid}/history', [ReadingsController::class, 'storeHistory'])->name('readings.history.store')->whereNumber('devid');
        Route::put('/objects/{object}/readings/{devid}/history/{row}', [ReadingsController::class, 'updateHistory'])->name('readings.history.update')->whereNumber(['devid', 'row']);
        Route::delete('/objects/{object}/readings/{devid}/history/{row}', [ReadingsController::class, 'destroyHistory'])->name('readings.history.destroy')->whereNumber(['devid', 'row']);
        Route::put('/objects/{object}/readings/{devid}/current', [ReadingsController::class, 'updateCurrent'])->name('readings.current.update')->whereNumber('devid');
    });
    Route::delete('/objects/{object}', [ObjectController::class, 'destroy'])->name('objects.destroy')->middleware('admin.only');
    Route::post('/objects/{object}/command', [ObjectsController::class, 'sendCommand'])->name('objects.command')->middleware('admin.only');
    Route::get('/objects/{object}', [ObjectController::class, 'show'])->name('objects.show');
    
    // Задачи для мобильного приложения
    Route::get('/tasks', [ObjectTaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks', [ObjectTaskController::class, 'store'])->name('tasks.store')->middleware('admin.only');
    Route::delete('/tasks/{task}', [ObjectTaskController::class, 'destroy'])->name('tasks.destroy')->middleware('admin.only');

    // Paigaldus: ход монтажа по объектам, отчёты из приложения, экспорт
    Route::get('/installations', [InstallationController::class, 'index'])->name('installations.index');
    Route::get('/installations/{object}', [InstallationController::class, 'show'])->name('installations.show');
    Route::get('/installations/{object}/print', [InstallationController::class, 'print'])->name('installations.print');
    Route::get('/installations/{object}/export.xlsx', [InstallationController::class, 'xlsx'])->name('installations.xlsx');

    // Регистр SIM-карт
    Route::get('/sims', [SimCardController::class, 'index'])->name('sims.index');
    Route::middleware('admin.only')->group(function () {
        Route::post('/sims', [SimCardController::class, 'store'])->name('sims.store');
        Route::post('/sims/bulk', [SimCardController::class, 'storeBulk'])->name('sims.store-bulk');
        Route::put('/sims/{sim}', [SimCardController::class, 'update'])->name('sims.update');
        Route::post('/sims/{sim}/issue', [SimCardController::class, 'issue'])->name('sims.issue');
        Route::post('/sims/{sim}/release', [SimCardController::class, 'release'])->name('sims.release');
        Route::post('/sims/{sim}/assign', [SimCardController::class, 'assign'])->name('sims.assign');
        Route::delete('/sims/{sim}', [SimCardController::class, 'destroy'])->name('sims.destroy');
    });

    // Отчёты из мобильного приложения
    Route::get('/mobile-reports/{report}/{file}', [MobileReportController::class, 'file'])->name('mobile-reports.file');
    Route::delete('/mobile-reports/{report}', [MobileReportController::class, 'destroy'])->name('mobile-reports.destroy')->middleware('admin.only');

    // Профиль пользователя
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
});

// ============ МОБИЛЬНОЕ ПРИЛОЖЕНИЕ (QRScannerApp) ============
// Тот же путь, что на боевом сервере, чтобы приложению менять только адрес
Route::get('/get_house_list', [MobileAppController::class, 'houseList'])->name('mobile.house_list');
Route::post('/insert_dev_data', [MobileAppController::class, 'insertDevData'])->name('mobile.insert_dev_data');

// ============ JSON API ============
Route::prefix('api')->group(function () {
    Route::get('/objects', [ObjectController::class, 'apiIndex'])->name('api.objects');
    Route::get('/objects/{object}', [ObjectController::class, 'apiShow'])->name('api.objects.show');
});
