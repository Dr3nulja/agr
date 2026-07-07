<?php

use App\Http\Controllers\Api\CommandPollController;
use App\Http\Controllers\Api\IngestDataController;
use App\Http\Controllers\Api\LoraIngestController;
use App\Http\Controllers\Api\DeviceIdController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {
    Route::get('/command', [CommandPollController::class, 'show']);
    Route::post('/ingest-data', [IngestDataController::class, 'store']);
    Route::post('/lora-ingest', [LoraIngestController::class, 'store']);
    Route::get('/device-ids', [DeviceIdController::class, 'index']);
    Route::post('/device-id', [DeviceIdController::class, 'update']);
});
