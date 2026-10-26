<?php

namespace App\Http\Controllers;

use App\Models\AgrObject;
use App\Services\ReadingsService;
use App\Services\ReportBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * «Näidud»: показания водомеров и аллокаторов дома, график потребления,
 * экспорт в Excel / PDF, ручная правка показаний и фиксация месяца
 */
class ReadingsController extends Controller
{
    public function __construct(private ReadingsService $readings)
    {
    }

    public function index(Request $request, $object)
    {
        $object = AgrObject::findOrFail($object);

        return view('readings.index', $this->viewData($request, $object) + [
            'fixedPeriods' => $this->readings->fixedPeriods((int) $object->id),
        ]);
    }

    public function xlsx(Request $request, $object, ReportBuilder $reports)
    {
        $object = AgrObject::findOrFail($object);
        $data = $this->viewData($request, $object);

        $spreadsheet = $reports->readings($object, $data['devices'], $data['monthly'], $data['periods'], $data['title']);
        $fileName = 'Naidud_' . ReportBuilder::fileSafe($object->address . ' ' . $object->City
            . ($data['filters']['location'] !== '' ? ' krt ' . $data['filters']['location'] : ''));

        return $reports->download($spreadsheet, $fileName);
    }

    public function print(Request $request, $object)
    {
        $object = AgrObject::findOrFail($object);

        return view('readings.print', $this->viewData($request, $object) + [
            'logo' => $object->reportLogoPath(),
        ]);
    }

    /**
     * История одного прибора и формы ручной правки
     */
    public function device(Request $request, $object, int $devid)
    {
        $object = AgrObject::findOrFail($object);
        $device = $this->findDevice($object, $devid);

        return view('readings.device', [
            'object' => $object,
            'device' => $device,
            'history' => $this->readings->history((int) $object->id, $device),
            'corrections' => $this->readings->corrections((int) $object->id, $devid),
            'back' => $request->query('back'),
        ]);
    }

    public function storeHistory(Request $request, $object, int $devid)
    {
        $object = AgrObject::findOrFail($object);
        $device = $this->findDevice($object, $devid);
        $validated = $this->validateReading($request);

        $this->readings->addHistory((int) $object->id, $device, $validated['date'], (float) $validated['value'], $this->userName());
        $this->logAction(sprintf('Added manual reading %s for device %d of object #%d', $validated['value'], $devid, $object->id));

        return back()->with('success', 'Näit lisatud');
    }

    public function updateHistory(Request $request, $object, int $devid, int $row)
    {
        $object = AgrObject::findOrFail($object);
        $device = $this->findDevice($object, $devid);
        $validated = $this->validateReading($request);

        $this->readings->updateHistory((int) $object->id, $device, $row, $validated['date'], (float) $validated['value'], $this->userName());
        $this->logAction(sprintf('Edited reading #%d of device %d of object #%d', $row, $devid, $object->id));

        return back()->with('success', 'Näit muudetud');
    }

    public function destroyHistory($object, int $devid, int $row)
    {
        $object = AgrObject::findOrFail($object);
        $device = $this->findDevice($object, $devid);

        $this->readings->deleteHistory((int) $object->id, $device, $row, $this->userName());
        $this->logAction(sprintf('Deleted reading #%d of device %d of object #%d', $row, $devid, $object->id));

        return back()->with('success', 'Näit kustutatud');
    }

    public function updateCurrent(Request $request, $object, int $devid)
    {
        $object = AgrObject::findOrFail($object);
        $device = $this->findDevice($object, $devid);
        $validated = $request->validate([
            'date' => 'required|date',
            'value' => 'required|numeric|min:0',
            'mvalue' => 'nullable|numeric|min:0',
            'prev_value' => 'nullable|numeric|min:0',
        ], [
            'date.required' => 'Sisesta kuupäev',
            'value.required' => 'Sisesta näit',
        ]);

        $this->readings->updateCurrent((int) $object->id, $device, [
            'date' => Carbon::parse($validated['date'])->format('Y-m-d H:i:s'),
            'value' => (float) $validated['value'],
            'mvalue' => isset($validated['mvalue']) ? (float) $validated['mvalue'] : null,
            'prev_value' => isset($validated['prev_value']) ? (float) $validated['prev_value'] : null,
        ], $this->userName());
        $this->logAction(sprintf('Edited current values of device %d of object #%d', $devid, $object->id));

        return back()->with('success', 'Praegused väärtused salvestatud');
    }

    public function fixMonth(Request $request, $object)
    {
        $object = AgrObject::findOrFail($object);
        $validated = $request->validate(['period' => ['required', 'date_format:Y-m']], ['period.*' => 'Vali kuu']);

        $count = $this->readings->fixMonth((int) $object->id, $validated['period'], $this->userName());
        $this->logAction(sprintf('Fixed readings of %s for object #%d (%d devices)', $validated['period'], $object->id, $count));

        return back()->with('success', sprintf('Kuu %s näidud fikseeritud (%d seadet)', $validated['period'], $count));
    }

    /**
     * Фильтры страницы и данные для таблицы / графика / экспорта.
     * snapshot=2026-09 — показать зафиксированный месяц вместо текущих значений.
     */
    private function viewData(Request $request, AgrObject $object): array
    {
        $request->validate([
            'kind' => ['nullable', Rule::in(array_keys(ReadingsService::KINDS))],
            'from' => ['nullable', 'date_format:Y-m'],
            'to' => ['nullable', 'date_format:Y-m'],
            'snapshot' => ['nullable', 'date_format:Y-m'],
            'location' => ['nullable', 'string', 'max:15'],
        ]);

        $to = $request->input('to') ?: now()->format('Y-m');
        $from = $request->input('from') ?: Carbon::createFromFormat('Y-m-d', $to . '-01')->subMonths(11)->format('Y-m');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $filters = [
            'kind' => $request->input('kind') ?: null,
            'location' => trim((string) $request->input('location')),
            'from' => $from,
            'to' => $to,
            'snapshot' => $request->input('snapshot') ?: null,
        ];

        $objectId = (int) $object->id;
        $devices = $filters['snapshot']
            ? $this->readings->fixedDevices($objectId, $filters['snapshot'], $filters['kind'], $filters['location'])
            : $this->readings->devices($objectId, $filters['kind'], $filters['location']);
        $periods = ReadingsService::periods($from, $to);
        $monthly = $this->readings->monthly($objectId, $devices, $from, $to);

        $title = 'Näidud ja kulu'
            . ($filters['kind'] ? ' — ' . ReadingsService::KINDS[$filters['kind']] : '')
            . ($filters['location'] !== '' ? ' — korter ' . $filters['location'] : '')
            . ($filters['snapshot'] ? ' — fikseeritud ' . $filters['snapshot'] : '');

        return [
            'object' => $object,
            'filters' => $filters,
            'devices' => $devices,
            'periods' => $periods,
            'monthly' => $monthly,
            'totals' => ReadingsService::totals($devices, $monthly, $periods),
            'locations' => $this->readings->locations($objectId),
            'hasTables' => $this->readings->hasTables($objectId),
            'title' => $title,
        ];
    }

    private function findDevice(AgrObject $object, int $devid): array
    {
        $device = $this->readings->device((int) $object->id, $devid);
        abort_if($device === null, 404);

        return $device;
    }

    private function validateReading(Request $request): array
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'value' => 'required|numeric|min:0',
        ], [
            'date.required' => 'Sisesta kuupäev',
            'value.required' => 'Sisesta näit',
        ]);
        $validated['date'] = Carbon::parse($validated['date'])->format('Y-m-d H:i:s');

        return $validated;
    }

    private function userName(): string
    {
        $user = session('user');

        return (string) ($user->login ?? $user->name ?? '');
    }
}
