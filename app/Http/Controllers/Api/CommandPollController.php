<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Legacy\LegacyObjectsService;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

class CommandPollController extends Controller
{
    public function show(Request $request, LegacyObjectsService $legacyObjectsService): Response
    {
        $imei = (string) $request->query('imei', '');
        $ver = (string) $request->query('ver', '');
        $csq = (int) $request->query('csq', 0);

        if ($imei === '') {
            return response('', 200);
        }

        $decodedImei = $legacyObjectsService->decodeLegacyImei($imei);

        if ($decodedImei === null) {
            return response('', 200);
        }

        $legacyObjectsService->updateLastSessionTime($decodedImei, $ver !== '' ? base64_decode($ver) ?: '' : '', $csq);

        $command = $legacyObjectsService->getPendingCommandByImei((string) $decodedImei);

        if ($command === null) {
            return response('', 200);
        }

        return response(base64_encode($command), 200)
            ->header('Content-Type', 'text/plain');
    }
}