<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Legacy\LegacyObjectsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoraIngestController extends Controller
{
    public function store(Request $request, LegacyObjectsService $legacyObjectsService): JsonResponse
    {
        $payload = $request->json()->all();

        $dashId = data_get($payload, 'deviceInfo.tags.dashid');
        $deviceName = data_get($payload, 'deviceInfo.deviceName');
        $currentVolume = data_get($payload, 'object.current_volume_liters');
        $lastMonthVolume = data_get($payload, 'object.last_month_volume_liters');
        $time = data_get($payload, 'time');

        if ($dashId === null || $deviceName === null || $time === null) {
            return response()->json(['ok' => false], 400);
        }

        $timeMysql = date('Y-m-d H:i:s', strtotime((string) $time));
        $statDateMysql = date('Y-m-d', strtotime((string) $time));

        if ($currentVolume !== null) {
            $legacyObjectsService->storeLoraCurrentValue((int) $dashId, (string) $deviceName, $timeMysql, (int) $currentVolume);
        }

        if ($lastMonthVolume !== null) {
            $legacyObjectsService->storeLoraMonthlyValue((int) $dashId, (string) $deviceName, $statDateMysql, (int) $lastMonthVolume);
        }

        return response()->json(['ok' => true]);
    }
}