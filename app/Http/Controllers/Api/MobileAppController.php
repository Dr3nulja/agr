<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgrObject;
use App\Models\MobileReport;
use App\Models\ObjectTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Эндпоинты для мобильного приложения QRScannerApp
 */
class MobileAppController extends Controller
{
    // Поля, которые лежат отдельными колонками или файлами, а не в `data`
    private const MAIN_FIELDS = [
        'house_id', 'device_type', 'action_type', 'apartment', 'qr_id', 'last_reading',
        'photo_before', 'photo_after', 'signature',
    ];

    /**
     * Первый экран приложения: объекты, на которых контора выставила задачи.
     *
     * [{"id":1,"address":"Narva mnt 55","City":"Tallinn",
     *   "tasks":[{"device_type":"water_meter","action_type":"replace"}]}]
     */
    public function houseList()
    {
        $houses = AgrObject::query()
            ->whereHas('tasks')
            ->with(['tasks' => fn ($query) => $query->orderBy('id')])
            ->orderBy('City')
            ->orderBy('address')
            ->get(['id', 'address', 'City'])
            ->map(fn (AgrObject $object) => [
                'id' => $object->id,
                'address' => $object->address,
                'City' => $object->City,
                'tasks' => $object->tasks->map(fn ($task) => [
                    'device_type' => $task->device_type,
                    'action_type' => $task->action_type,
                ])->values(),
            ]);

        return response()->json($houses);
    }

    /**
     * Сохранение формы из приложения (application/x-www-form-urlencoded).
     * Фото и подпись приходят base64 JPEG.
     */
    public function insertDevData(Request $request)
    {
        $validator = validator($request->all(), [
            'house_id' => ['required', 'integer', Rule::exists('objects', 'id')],
            'device_type' => ['required', Rule::in(array_keys(ObjectTask::DEVICE_TYPES))],
            'action_type' => ['required', Rule::in(array_keys(ObjectTask::ACTION_TYPES))],
            'apartment' => ['nullable', 'string', 'max:50'],
            'qr_id' => ['nullable', 'string', 'max:100'],
            'last_reading' => ['nullable', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $report = DB::transaction(function () use ($request) {
            $report = MobileReport::create([
                'object_id' => (int) $request->input('house_id'),
                'device_type' => $request->input('device_type'),
                'action_type' => $request->input('action_type'),
                'apartment' => $request->input('apartment'),
                'qr_id' => $request->input('qr_id'),
                'last_reading' => $request->input('last_reading'),
                'data' => collect($request->except(self::MAIN_FIELDS))
                    ->filter(fn ($value) => is_scalar($value))
                    ->all(),
            ]);

            foreach (MobileReport::FILES as $field) {
                $report->{$field} = $this->storeImage($request->input($field), $report->id, $field);
            }
            $report->save();

            return $report;
        });

        return response()->json(['status' => 'ok', 'id' => $report->id]);
    }

    private function storeImage(?string $base64, int $reportId, string $name): ?string
    {
        if (! $base64) {
            return null;
        }

        $binary = base64_decode(preg_replace('/\s+/', '', $base64), true);

        // Только настоящие JPEG (приложение шлёт Bitmap.compress JPEG)
        if ($binary === false || ! str_starts_with($binary, "\xFF\xD8")) {
            return null;
        }

        $path = sprintf('mobile_reports/%d/%s.jpg', $reportId, $name);
        Storage::put($path, $binary);

        return $path;
    }
}
