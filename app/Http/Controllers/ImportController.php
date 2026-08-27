<?php

namespace App\Http\Controllers;

use App\Services\Legacy\LegacyObjectsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function show(int $objectId, LegacyObjectsService $legacyObjectsService): View
    {
        $object = $legacyObjectsService->getObjectDetails($objectId);

        abort_if($object === null, 404);

        return view('objects.import-csv', [
            'object' => $object,
        ]);
    }

    public function apatorWater(int $objectId, Request $request, LegacyObjectsService $legacyObjectsService): RedirectResponse
    {
        $data = $request->validate([
            'fileToUpload' => ['required', 'file'],
            'dtime' => ['required', 'string'],
        ]);

        $dateTime = date('Y-m-d H:i:00', strtotime($data['dtime']));
        $imported = $legacyObjectsService->importApatorWaterCsv($objectId, $request->file('fileToUpload')->getRealPath(), $dateTime);

        return redirect()->route('objects.show', $objectId)->with('success', "Imported {$imported} readings");
    }

    public function apatorHeater(int $objectId, Request $request, LegacyObjectsService $legacyObjectsService): RedirectResponse
    {
        $request->validate([
            'fileToUpload' => ['required', 'file'],
        ]);

        $imported = $legacyObjectsService->importApatorHeaterCsv($objectId, $request->file('fileToUpload')->getRealPath());

        return redirect()->route('objects.show', $objectId)->with('success', "Imported {$imported} readings");
    }

    public function siemens(int $objectId, Request $request, LegacyObjectsService $legacyObjectsService): RedirectResponse
    {
        $request->validate([
            'fileToUpload' => ['required', 'file'],
        ]);

        $imported = $legacyObjectsService->importSiemensCsv($objectId, $request->file('fileToUpload')->getRealPath());

        return redirect()->route('objects.show', $objectId)->with('success', "Imported {$imported} readings");
    }
}
