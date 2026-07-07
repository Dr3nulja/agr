<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Legacy\LegacyObjectsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IngestDataController extends Controller
{
    public function store(Request $request, LegacyObjectsService $legacyObjectsService): JsonResponse
    {
        $imei = (string) $request->input('imei', '');
        $data = (string) $request->input('data', '');

        if ($imei === '' || $data === '') {
            return response()->json(['ok' => false], 400);
        }

        $decodedImei = $legacyObjectsService->decodeLegacyImei($imei);
        $payload = base64_decode(str_replace(' ', '+', $data), true);

        if ($decodedImei === null || $payload === false) {
            return response()->json(['ok' => false], 400);
        }

        $count = $legacyObjectsService->ingestLegacyRadioPacket($decodedImei, $payload);

        return response()->json(['ok' => true, 'rows' => $count]);
    }
}