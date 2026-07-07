<?php

namespace App\Http\Controllers;

use App\Services\Legacy\LegacyObjectsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class ObjectsController extends Controller
{
    public function create(): View
    {
        return view('objects.create');
    }

    public function store(Request $request, LegacyObjectsService $legacyObjectsService): RedirectResponse
    {
        $data = $request->validate([
            'address' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
            'imei' => ['required', 'string', 'max:255'],
            'gsmnr' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'description2' => ['nullable', 'string'],
            'customRadio' => ['required', 'integer'],
            'customRadio2' => ['required', 'integer'],
            'devq' => ['nullable', 'integer'],
            'radiodevq' => ['nullable', 'integer'],
            'mainradio' => ['nullable', 'string', 'max:255'],
            'gsmserial' => ['nullable', 'string', 'max:255'],
            'pin1' => ['nullable', 'string', 'max:255'],
            'pin2' => ['nullable', 'string', 'max:255'],
            'puk1' => ['nullable', 'string', 'max:255'],
            'puk2' => ['nullable', 'string', 'max:255'],
            'paket' => ['nullable', 'string', 'max:255'],
            'traf' => ['nullable', 'string', 'max:255'],
            'call' => ['nullable', 'string', 'max:255'],
            'summ' => ['nullable', 'string', 'max:255'],
            'imei2' => ['nullable', 'string', 'max:255'],
            'gsmnr2' => ['nullable', 'string', 'max:255'],
            'gsmserial2' => ['nullable', 'string', 'max:255'],
            'keycode' => ['nullable', 'string', 'max:255'],
            'customRadio3' => ['nullable', 'integer'],
            'lat' => ['nullable', 'numeric'],
            'lon' => ['nullable', 'numeric'],
            'saveMVal1' => ['nullable'],
        ]);

        $objectId = $legacyObjectsService->saveNewObject($data);

        return redirect()->route('objects.show', $objectId);
    }

    public function edit(int $objectId, LegacyObjectsService $legacyObjectsService): View
    {
        $object = $legacyObjectsService->getObjectDetails($objectId);

        abort_if($object === null, 404);

        return view('objects.edit', [
            'object' => $object,
        ]);
    }

    public function update(int $objectId, Request $request, LegacyObjectsService $legacyObjectsService): RedirectResponse
    {
        $data = $request->validate([
            'address' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
            'imei' => ['required', 'string', 'max:255'],
            'gsmnr' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'description2' => ['nullable', 'string'],
            'customRadio' => ['required', 'integer'],
            'customRadio2' => ['required', 'integer'],
            'devq' => ['nullable', 'integer'],
            'radiodevq' => ['nullable', 'integer'],
            'mainradio' => ['nullable', 'string', 'max:255'],
            'gsmserial' => ['nullable', 'string', 'max:255'],
            'pin1' => ['nullable', 'string', 'max:255'],
            'pin2' => ['nullable', 'string', 'max:255'],
            'puk1' => ['nullable', 'string', 'max:255'],
            'puk2' => ['nullable', 'string', 'max:255'],
            'paket' => ['nullable', 'string', 'max:255'],
            'traf' => ['nullable', 'string', 'max:255'],
            'call' => ['nullable', 'string', 'max:255'],
            'summ' => ['nullable', 'string', 'max:255'],
            'imei2' => ['nullable', 'string', 'max:255'],
            'gsmnr2' => ['nullable', 'string', 'max:255'],
            'gsmserial2' => ['nullable', 'string', 'max:255'],
            'keycode' => ['nullable', 'string', 'max:255'],
            'customRadio3' => ['nullable', 'integer'],
            'lat' => ['nullable', 'numeric'],
            'lon' => ['nullable', 'numeric'],
            'saveMVal1' => ['nullable'],
        ]);

        $legacyObjectsService->updateObject($objectId, $data);

        return redirect()->route('objects.show', $objectId);
    }

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

    public function export(int $objectId, LegacyObjectsService $legacyObjectsService): StreamedResponse
    {
        $object = $legacyObjectsService->getObject($objectId);

        abort_if($object === null, 404);

        $devices = $legacyObjectsService->getDevices($objectId, $object['dtype']);
        $fileName = 'Report_Current_' . preg_replace('/[^A-Za-z0-9_\-]+/', '_', $object['address'] . '_' . $object['city']) . '.csv';

        return response()->streamDownload(function () use ($devices): void {
            $output = fopen('php://output', 'w');

            fputcsv($output, ['Location', 'Device No', 'Type', 'Date', 'Value', 'MValue', 'Error', 'RTime', 'ETime', 'Status Date']);

            foreach ($devices as $device) {
                fputcsv($output, [
                    $device['location'],
                    $device['devid'],
                    $device['devtype_class'],
                    $device['date'],
                    $device['value'],
                    $device['mvalue'],
                    $device['ecode'],
                    $device['rtime'],
                    $device['etime'],
                    $device['sdate'],
                ]);
            }

            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportDevices(int $objectId, LegacyObjectsService $legacyObjectsService): StreamedResponse
    {
        $object = $legacyObjectsService->getObject($objectId);

        abort_if($object === null, 404);

        $devices = $legacyObjectsService->getDevices($objectId, $object['dtype']);
        $fileName = 'Devices_' . preg_replace('/[^A-Za-z0-9_\-]+/', '_', $object['address'] . '_' . $object['city']) . '.csv';

        return response()->streamDownload(function () use ($devices): void {
            $output = fopen('php://output', 'w');

            fputcsv($output, ['Device', 'Location', 'Type', 'Date', 'Value', 'MValue', 'Error Code']);

            foreach ($devices as $device) {
                fputcsv($output, [
                    $device['devid'],
                    $device['location'],
                    $device['devtype_class'],
                    $device['date'],
                    $device['value'],
                    $device['mvalue'],
                    $device['ecode'],
                ]);
            }

            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportAll(int $objectId, LegacyObjectsService $legacyObjectsService): StreamedResponse
    {
        $object = $legacyObjectsService->getObject($objectId);

        abort_if($object === null, 404);

        $devices = $legacyObjectsService->getDevices($objectId, $object['dtype']);
        $fileName = 'Report_Current_' . preg_replace('/[^A-Za-z0-9_\-]+/', '_', $object['address'] . '_' . $object['city']) . '.csv';

        return response()->streamDownload(function () use ($devices): void {
            $output = fopen('php://output', 'w');

            fputcsv($output, ['Location', 'Device No', 'Type', 'Date', 'Value', 'MValue', 'Error', 'RTime', 'ETime', 'Status Date']);

            foreach ($devices as $device) {
                fputcsv($output, [
                    $device['location'],
                    $device['devid'],
                    $device['devtype_class'],
                    $device['date'],
                    $device['value'],
                    $device['mvalue'],
                    $device['ecode'],
                    $device['rtime'],
                    $device['etime'],
                    $device['sdate'],
                ]);
            }

            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportHistory(int $objectId, Request $request, LegacyObjectsService $legacyObjectsService): StreamedResponse
    {
        $object = $legacyObjectsService->getObject($objectId);

        abort_if($object === null, 404);

        $from = (string) $request->query('from', date('Y-m-01'));
        $to = (string) $request->query('to', date('Y-m-d'));
        $history = $legacyObjectsService->getHistoryBetweenDates($objectId, $from, $to);
        $fileName = 'History_' . preg_replace('/[^A-Za-z0-9_\-]+/', '_', $object['address'] . '_' . $object['city']) . '.csv';

        return response()->streamDownload(function () use ($history): void {
            $output = fopen('php://output', 'w');

            fputcsv($output, ['Device', 'Date', 'Value']);

            foreach ($history as $row) {
                fputcsv($output, [$row['devid'], $row['date'], $row['value']]);
            }

            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function index(Request $request, LegacyObjectsService $legacyObjectsService): View
    {
        $filters = $legacyObjectsService->normalizeFilters([
            'cradio' => $request->integer('cradio', 0),
            'mradio' => $request->integer('mradio', 0),
            'chradio' => $request->integer('chradio', 0),
        ]);

        return view('objects.index', [
            'filters' => $filters,
            'objects' => $legacyObjectsService->getObjects($filters),
        ]);
    }

    public function show(int $objectId, LegacyObjectsService $legacyObjectsService): View
    {
        $object = $legacyObjectsService->getObject($objectId);

        abort_if($object === null, 404);

        return view('objects.show', [
            'object' => $object,
            'devices' => $legacyObjectsService->getDevices($objectId, $object['dtype']),
            'commands' => $legacyObjectsService->getCommandOptions(),
        ]);
    }
}