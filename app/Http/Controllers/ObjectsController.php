<?php

namespace App\Http\Controllers;

use App\Services\Legacy\LegacyObjectsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ObjectsController extends Controller
{
    public function quickUpdate(int $objectId, Request $request, LegacyObjectsService $legacyObjectsService): RedirectResponse
    {
        $data = $request->validate([
            'address' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
            'imei' => ['required', 'string', 'max:255'],
            'gsmnr' => ['nullable', 'string', 'max:255'],
            'devq' => ['nullable', 'integer'],
            'radiodevq' => ['nullable', 'integer'],
            'mainradio' => ['nullable', 'string', 'max:255'],
            'gsmserial' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'lat' => ['nullable', 'numeric'],
            'lon' => ['nullable', 'numeric'],
        ]);

        $legacyObjectsService->quickUpdateObject($objectId, $data);

        return redirect()->route('objects.show', $objectId);
    }

    public function sendCommand(int $objectId, Request $request, LegacyObjectsService $legacyObjectsService): RedirectResponse
    {
        $data = $request->validate([
            'command' => ['required', 'integer', 'between:1,5'],
        ]);

        $legacyObjectsService->queueCommand($objectId, (int) $data['command']);

        return redirect()->route('objects.show', $objectId);
    }

    public function updateDeviceRow(int $objectId, int $deviceRowId, Request $request, LegacyObjectsService $legacyObjectsService): RedirectResponse
    {
        $data = $request->validate([
            'devid' => ['required', 'integer'],
            'location' => ['required', 'string', 'max:255'],
            'devtype' => ['required', 'integer'],
        ]);

        $legacyObjectsService->updateDeviceRow($objectId, $deviceRowId, (int) $data['devid'], $data['location'], (int) $data['devtype']);

        return redirect()->route('objects.show', $objectId);
    }

    public function storeDeviceRow(int $objectId, Request $request, LegacyObjectsService $legacyObjectsService): RedirectResponse
    {
        $data = $request->validate([
            'devid' => ['required', 'integer'],
            'location' => ['required', 'string', 'max:255'],
            'devtype' => ['required', 'integer'],
        ]);

        $legacyObjectsService->addDeviceRow($objectId, (int) $data['devid'], $data['location'], (int) $data['devtype']);

        return redirect()->route('objects.show', $objectId);
    }

    public function deleteDeviceRow(int $objectId, int $deviceRowId, LegacyObjectsService $legacyObjectsService): RedirectResponse
    {
        $legacyObjectsService->deleteDeviceRow($objectId, $deviceRowId);

        return redirect()->route('objects.show', $objectId);
    }

    public function showDeviceUpload(int $objectId, LegacyObjectsService $legacyObjectsService): View
    {
        $object = $legacyObjectsService->getObjectDetails($objectId);

        abort_if($object === null, 404);

        return view('objects.device-upload', [
            'object' => $object,
        ]);
    }

    public function storeDeviceUpload(int $objectId, Request $request, LegacyObjectsService $legacyObjectsService): RedirectResponse
    {
        $request->validate([
            'fileToUpload' => ['required', 'file'],
        ]);

        $legacyObjectsService->replaceDeviceList(
            $objectId,
            $request->file('fileToUpload')->getRealPath()
        );

        return redirect()->route('objects.show', $objectId);
    }

    public function soe(int $objectId, LegacyObjectsService $legacyObjectsService): View
    {
        $object = $legacyObjectsService->getObjectDetails($objectId);

        abort_if($object === null, 404);

        return view('objects.soe', [
            'object' => $object,
            'settings' => $legacyObjectsService->getSoeSettings($objectId),
        ]);
    }

    public function saveSoe(int $objectId, Request $request, LegacyObjectsService $legacyObjectsService): RedirectResponse
    {
        $data = $request->validate([
            'kuludm2' => ['required', 'integer', 'min:0'],
            'lisamaks' => ['nullable', 'numeric'],
            'lisamaksen' => ['nullable'],
            'eraldileht' => ['nullable'],
            'ParamKulu' => ['nullable'],
            'AlgLopp' => ['nullable'],
            'm2_source' => ['required', 'integer', 'between:0,100'],
            'command' => ['nullable', 'integer', 'between:0,3'],
        ]);

        $legacyObjectsService->saveSoeSettings($objectId, $data);

        if ((int) ($data['command'] ?? 0) > 0) {
            $legacyObjectsService->queueCommand($objectId, (int) $data['command']);
        }

        return redirect()->route('objects.soe', $objectId);
    }

    public function soeFlats(int $objectId, Request $request, LegacyObjectsService $legacyObjectsService): View
    {
        $object = $legacyObjectsService->getObjectDetails($objectId);

        abort_if($object === null, 404);

        $selectedFlat = (string) $request->query('flat', '');
        $flats = $legacyObjectsService->getFlats($objectId);

        return view('objects.soe-flats', [
            'object' => $object,
            'flats' => $flats,
            'summary' => $legacyObjectsService->getFlatSummary($objectId),
            'selectedFlat' => $selectedFlat,
            'flatRadiatorSummary' => $legacyObjectsService->getFlatRadiatorSummary($objectId, $selectedFlat),
            'radiators' => $legacyObjectsService->getRadiators($objectId, $selectedFlat),
        ]);
    }

    public function uploadFlatList(int $objectId, Request $request, LegacyObjectsService $legacyObjectsService): RedirectResponse
    {
        $request->validate([
            'fileToUpload' => ['required', 'file'],
        ]);

        $legacyObjectsService->replaceFlatList(
            $objectId,
            $request->file('fileToUpload')->getRealPath()
        );

        return redirect()->route('objects.soe.flats', $objectId);
    }

    public function storeFlat(int $objectId, Request $request, LegacyObjectsService $legacyObjectsService): RedirectResponse
    {
        $data = $request->validate([
            'location' => ['required', 'string', 'max:255'],
            'size' => ['required', 'numeric'],
        ]);

        $legacyObjectsService->addFlat($objectId, $data['location'], (float) $data['size']);

        return redirect()->route('objects.soe.flats', $objectId);
    }

    public function updateFlat(int $objectId, int $flatId, Request $request, LegacyObjectsService $legacyObjectsService): RedirectResponse
    {
        $data = $request->validate([
            'location' => ['required', 'string', 'max:255'],
            'size' => ['required', 'numeric'],
        ]);

        $legacyObjectsService->updateFlat($objectId, $flatId, $data['location'], (float) $data['size']);

        return redirect()->route('objects.soe.flats', $objectId);
    }

    public function deleteFlat(int $objectId, int $flatId, LegacyObjectsService $legacyObjectsService): RedirectResponse
    {
        $legacyObjectsService->deleteFlat($objectId, $flatId);

        return redirect()->route('objects.soe.flats', $objectId);
    }

    public function uploadRadiatorList(int $objectId, Request $request, LegacyObjectsService $legacyObjectsService): RedirectResponse
    {
        $request->validate([
            'fileToUpload' => ['required', 'file'],
        ]);

        $legacyObjectsService->replaceRadiatorList(
            $objectId,
            $request->file('fileToUpload')->getRealPath()
        );

        return redirect()->route('objects.soe.flats', $objectId);
    }

}