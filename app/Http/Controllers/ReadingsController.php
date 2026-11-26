<?php

namespace App\Http\Controllers;

use App\Models\AgrObject;
use App\Models\LeakAlert;
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
     * История одного прибора и формы ручной правки (отдельная страница)
     */
    public function device(Request $request, $object, int $devid)
    {
        $object = AgrObject::findOrFail($object);

        return view('readings.device', $this->deviceData($object, $devid) + [
            'back' => $request->query('back'),
        ]);
    }

    /**
     * То же содержимое без шапки — подгружается в раскрывающуюся строку списка
     */
    public function panel($object, int $devid)
    {
        $object = AgrObject::findOrFail($object);

        return view('readings._device-panel', $this->deviceData($object, $devid) + ['inPanel' => true]);
    }

    /**
     * Замена прибора: новый номер вместо старого, при желании — начальный показатель
     */
    public function replaceDevice(Request $request, $object, int $devid)
    {
        $object = AgrObject::findOrFail($object);
        $device = $this->findDevice($object, $devid);
        $validated = $request->validate([
            'new_devid' => 'required|integer|min:1|max:4294967295',
            'start_value' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date|required_with:start_value',
        ], [
            'new_devid.required' => 'Sisesta uus seadme number',
            'new_devid.integer' => 'Seadme number peab olema täisarv',
            'start_date.required_with' => 'Sisesta algnäidu kuupäev',
        ]);

        $newDevice = $this->readings->replaceDevice(
            (int) $object->id,
            $device,
            (int) $validated['new_devid'],
            isset($validated['start_value']) ? (float) $validated['start_value'] : null,
            isset($validated['start_date']) ? Carbon::parse($validated['start_date'])->format('Y-m-d H:i:s') : null,
            $this->userName()
        );
        $this->logAction(sprintf('Replaced device %d with %d in object #%d', $devid, $validated['new_devid'], $object->id));

        $message = sprintf('Seade %d asendatud seadmega %d', $devid, $validated['new_devid']);
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message, 'device' => $this->deviceSummary($object, $newDevice)]);
        }

        return redirect()->route('readings.device', [$object->id, $validated['new_devid']])->with('success', $message);
    }

    public function storeHistory(Request $request, $object, int $devid)
    {
        $object = AgrObject::findOrFail($object);
        $device = $this->findDevice($object, $devid);
        $validated = $this->validateReading($request);

        $this->readings->addHistory((int) $object->id, $device, $validated['date'], (float) $validated['value'], $this->userName());
        $this->logAction(sprintf('Added manual reading %s for device %d of object #%d', $validated['value'], $devid, $object->id));

        return $this->done($request, $object, $devid, 'Näit lisatud');
    }

    public function updateHistory(Request $request, $object, int $devid, int $row)
    {
        $object = AgrObject::findOrFail($object);
        $device = $this->findDevice($object, $devid);
        $validated = $this->validateReading($request);

        $this->readings->updateHistory((int) $object->id, $device, $row, $validated['date'], (float) $validated['value'], $this->userName());
        $this->logAction(sprintf('Edited reading #%d of device %d of object #%d', $row, $devid, $object->id));

        return $this->done($request, $object, $devid, 'Näit muudetud');
    }

    public function destroyHistory(Request $request, $object, int $devid, int $row)
    {
        $object = AgrObject::findOrFail($object);
        $device = $this->findDevice($object, $devid);

        $this->readings->deleteHistory((int) $object->id, $device, $row, $this->userName());
        $this->logAction(sprintf('Deleted reading #%d of device %d of object #%d', $row, $devid, $object->id));

        return $this->done($request, $object, $devid, 'Näit kustutatud');
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

        return $this->done($request, $object, $devid, 'Praegused väärtused salvestatud');
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
            'locations' => $this->readings->locations($objectId),
            'hasTables' => $this->readings->hasTables($objectId),
            'title' => $title,
        ];
    }

    /**
     * Утечки прибора (leak_alerts) — в общий список ошибок, новые сверху
     */
    private function withLeaks(array $problems, int $objectId, int $devid): array
    {
        $leaks = LeakAlert::where('object_id', $objectId)->where('devid', $devid)->latest('day')->limit(20)->get();

        foreach ($leaks as $leak) {
            $problems[] = [
                'date' => $leak->to_date->format('Y-m-d H:i:s'),
                'text' => sprintf('Võimalik leke: %s m³ ööpäevas%s', number_format($leak->consumption, 3, '.', ' '), $leak->resolved_at ? ' (lahendatud)' : ''),
            ];
        }

        usort($problems, fn ($a, $b) => strcmp((string) $b['date'], (string) $a['date']));

        return $problems;
    }

    /**
     * Ответ на правку: JSON для панели в списке (без перезагрузки страницы), иначе — назад
     */
    private function done(Request $request, AgrObject $object, int $devid, string $message)
    {
        if (! $request->expectsJson()) {
            return back()->with('success', $message);
        }

        $device = $this->readings->device((int) $object->id, $devid);

        return response()->json(['ok' => true, 'message' => $message, 'device' => $device ? $this->deviceSummary($object, $device) : null]);
    }

    /**
     * Что нужно строке списка, чтобы обновиться без перезагрузки
     */
    private function deviceSummary(AgrObject $object, array $device): array
    {
        return [
            'devid' => $device['devid'],
            'value' => $device['value'] !== null ? number_format($device['value'], 3, '.', ' ') : '—',
            'date' => $device['date'] ? date('d.m.Y', strtotime($device['date'])) : '—',
            // Для «Seadmete näidud» на странице объекта (тот же формат, что в getDevices)
            'mvalue_raw' => (float) ($device['mvalue'] ?? 0),
            'prev_value_raw' => (float) ($device['prev_value'] ?? 0),
            'date_full' => $device['date'] ? date('H:i:s d.m.Y', strtotime($device['date'])) : '',
            'timestamp' => $device['date'] ? strtotime($device['date']) : 0,
            'panel_url' => route('readings.panel', [$object->id, $device['devid']]),
            'page_url' => route('readings.device', [$object->id, $device['devid']]),
        ];
    }

    private function deviceData(AgrObject $object, int $devid): array
    {
        $device = $this->findDevice($object, $devid);
        $history = $this->readings->history((int) $object->id, $device);

        return [
            'object' => $object,
            'device' => $device,
            'history' => $history,
            'problems' => $this->withLeaks(ReadingsService::problems($device, $history), (int) $object->id, $devid),
            'corrections' => $this->readings->corrections((int) $object->id, $devid),
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
