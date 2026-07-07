<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Legacy\LegacyObjectsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceIdController extends Controller
{
    public function index(Request $request, LegacyObjectsService $legacyObjectsService): JsonResponse
    {
        $imei = (string) $request->query('imei', '');

        if ($imei === '') {
            return response()->json(['ok' => false], 400);
        }

        $decodedImei = $legacyObjectsService->decodeLegacyImei($imei);

        if ($decodedImei === null) {
            return response()->json(['ok' => false], 400);
        }

        return response()->json([
            'ok' => true,
            'data' => $legacyObjectsService->getLegacyDeviceIds((string) $decodedImei),
        ]);
    }

    public function update(Request $request, LegacyObjectsService $legacyObjectsService): JsonResponse
    {
        $data = $request->validate([
            'oid' => ['required', 'integer'],
            'pid' => ['required', 'integer'],
            'nid' => ['required', 'integer'],
        ]);

        $legacyObjectsService->changeDeviceId((int) $data['oid'], (int) $data['pid'], (int) $data['nid']);

        return response()->json(['ok' => true]);
    }
}