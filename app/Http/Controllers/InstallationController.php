<?php

namespace App\Http\Controllers;

use App\Models\AgrObject;
use App\Models\MobileReport;
use App\Models\ObjectTask;
use App\Services\Legacy\LegacyObjectsService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Paigaldus: как идёт монтаж по объектам (отчёты из QRScannerApp), экспорт в PDF/Excel
 */
class InstallationController extends Controller
{
    private const PHOTO_COLUMNS = ['photo_before' => 'Foto enne', 'photo_after' => 'Foto pärast', 'signature' => 'Allkiri'];

    public function __construct(private LegacyObjectsService $legacyObjectsService)
    {
    }

    /**
     * Все объекты с задачами или отчётами: сколько квартир сделано по каждому типу прибора.
     */
    public function index(): View
    {
        $stats = DB::table('mobile_reports')
            ->select('object_id', 'device_type')
            ->selectRaw("count(*) as reports, count(distinct nullif(trim(apartment), '')) as apartments, max(created_at) as last_report")
            ->groupBy('object_id', 'device_type')
            ->get()
            ->groupBy('object_id');

        $objects = AgrObject::query()
            ->where(fn ($query) => $query->whereHas('tasks')->orWhereIn('id', $stats->keys()))
            ->with('tasks')
            ->get(['id', 'address', 'City'])
            ->map(function (AgrObject $object) use ($stats) {
                $objectStats = $stats->get($object->id, collect())->keyBy('device_type');
                $flatCount = count($this->legacyObjectsService->getFlats((int) $object->id));
                $deviceTypes = $object->tasks->pluck('device_type')->merge($objectStats->keys())->unique()->sort()->values();

                return (object) [
                    'object' => $object,
                    'flatCount' => $flatCount,
                    'reports' => $objectStats->sum('reports'),
                    'lastReport' => $objectStats->max('last_report'),
                    'progress' => $deviceTypes->map(fn ($device) => $this->progress(
                        $device,
                        (int) ($objectStats->get($device)->apartments ?? 0),
                        $flatCount,
                        $object->tasks->where('device_type', $device)
                    )),
                ];
            })
            ->sortByDesc(fn ($row) => $row->lastReport ?? '0000')
            ->values();

        return view('installations.index', [
            'rows' => $objects,
            'reportsToday' => MobileReport::whereDate('created_at', today())->count(),
            'reportsWeek' => MobileReport::where('created_at', '>=', now()->subDays(7))->count(),
        ]);
    }

    public function show(Request $request, $object): View
    {
        return view('installations.show', $this->objectData($request, $object));
    }

    /**
     * Страница для печати / «Сохранить как PDF» в браузере (A4, по квартирам, с фото и подписями)
     */
    public function print(Request $request, $object): View
    {
        return view('installations.print', $this->objectData($request, $object));
    }

    /**
     * Excel: строка = отчёт. С ?photos=1 — миниатюры фото и подписи прямо в ячейках.
     */
    public function xlsx(Request $request, $object): StreamedResponse
    {
        $data = $this->objectData($request, $object);
        $withPhotos = $request->boolean('photos');
        $reports = $data['apartments']->flatten(1);

        $fieldColumns = $this->fieldColumns($reports);
        $headers = array_merge(['Korter', 'Seade', 'Toiming', 'QR-kood', 'Kuupäev', 'Paigaldaja'], array_values($fieldColumns));
        if ($withPhotos) {
            $headers = array_merge($headers, array_values(self::PHOTO_COLUMNS));
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Paigaldus');
        $sheet->setCellValue('A1', $data['object']->address . ', ' . $data['object']->City);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('A2', 'Aruandeid: ' . $reports->count() . ' · Koostatud ' . now()->format('d.m.Y H:i'));
        $sheet->fromArray($headers, null, 'A4');
        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle('A4:' . $lastColumn . '4')->getFont()->setBold(true);
        $sheet->freezePane('B5');

        $tempFiles = [];
        $row = 5;

        foreach ($reports as $report) {
            $fields = $report->fields();
            $values = [
                $report->apartment,
                ObjectTask::DEVICE_TYPES[$report->device_type] ?? $report->device_type,
                ObjectTask::ACTION_TYPES[$report->action_type] ?? $report->action_type,
                $report->qr_id,
                $report->created_at?->format('d.m.Y H:i'),
                $report->installer,
            ];
            foreach (array_keys($fieldColumns) as $key) {
                $values[] = $fields[$key][1] ?? '';
            }
            // Строкой, чтобы QR/номера квартир не превращались в числа с потерей нулей
            foreach ($values as $index => $value) {
                $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($index + 1) . $row, (string) $value, DataType::TYPE_STRING);
            }

            if ($withPhotos) {
                $column = count($values) + 1;
                foreach (array_keys(self::PHOTO_COLUMNS) as $file) {
                    $thumb = $this->thumbnail($report->{$file}, $file === 'signature' ? 300 : 160);
                    if ($thumb) {
                        $tempFiles[] = $thumb;
                        $drawing = new Drawing();
                        $drawing->setPath($thumb);
                        $drawing->setHeight(100);
                        if ($drawing->getWidth() > 260) {
                            $drawing->setWidth(260);
                        }
                        $drawing->setCoordinates(Coordinate::stringFromColumnIndex($column) . $row);
                        $drawing->setOffsetX(4);
                        $drawing->setOffsetY(4);
                        $drawing->setWorksheet($sheet);
                    }
                    $column++;
                }
                $sheet->getRowDimension($row)->setRowHeight(82);
            }
            $row++;
        }

        foreach (range(1, count($headers)) as $index) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setAutoSize(true);
        }
        if ($withPhotos) {
            $first = count($headers) - count(self::PHOTO_COLUMNS) + 1;
            foreach ([0 => 12, 1 => 12, 2 => 36] as $offset => $width) {
                $dimension = $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($first + $offset));
                $dimension->setAutoSize(false);
                $dimension->setWidth($width);
            }
        }
        $sheet->getStyle('A5:' . $lastColumn . max(5, $row - 1))->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);

        $fileName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $data['object']->address) . '_paigaldus.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(static function () use ($writer, $tempFiles): void {
            $writer->save('php://output');
            array_map('unlink', array_filter($tempFiles, 'is_file'));
        }, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * Отчёты объекта с фильтрами (?device=&from=&to=), сгруппированные по квартирам,
     * и квартиры из списка объекта, по которым отчётов ещё нет.
     */
    private function objectData(Request $request, $objectId): array
    {
        $object = AgrObject::with('tasks')->findOrFail($objectId);

        $filters = [
            'device' => isset(ObjectTask::DEVICE_TYPES[(string) $request->input('device')]) ? $request->input('device') : null,
            'from' => $this->date($request->input('from')),
            'to' => $this->date($request->input('to')),
        ];

        $reports = $object->mobileReports()
            ->when($filters['device'], fn ($query, $device) => $query->where('device_type', $device))
            ->when($filters['from'], fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'], fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->orderBy('created_at')
            ->get();

        $apartments = $reports
            ->groupBy(fn (MobileReport $report) => MobileReport::normalizeApartment($report->apartment))
            ->sortBy(fn ($group) => MobileReport::apartmentSortKey($group->first()->apartment));

        $flats = collect($this->legacyObjectsService->getFlats((int) $object->id))->pluck('location');
        $missing = $flats
            ->reject(fn ($location) => $apartments->has(MobileReport::normalizeApartment($location)))
            ->sortBy(fn ($location) => MobileReport::apartmentSortKey($location))
            ->values();

        $deviceTypes = $object->tasks->pluck('device_type')->merge($reports->pluck('device_type'))->unique()->sort()->values();

        return [
            'object' => $object,
            'filters' => $filters,
            'query' => array_filter($filters),
            'apartments' => $apartments,
            'reportCount' => $reports->count(),
            'flatCount' => $flats->count(),
            'missing' => $missing,
            'byDay' => $reports->groupBy(fn ($report) => $report->created_at?->format('Y-m-d'))->map->count()->sortKeys(),
            'progress' => $deviceTypes->map(fn ($device) => $this->progress(
                $device,
                $reports->where('device_type', $device)->map(fn ($r) => MobileReport::normalizeApartment($r->apartment))->filter()->unique()->count(),
                $flats->count(),
                $object->tasks->where('device_type', $device)
            )),
        ];
    }

    private function progress(string $device, int $done, int $flatCount, Collection $tasks): object
    {
        return (object) [
            'device' => $device,
            'label' => ObjectTask::DEVICE_TYPES[$device] ?? $device,
            'actions' => $tasks->map(fn ($task) => ObjectTask::ACTION_TYPES[$task->action_type] ?? $task->action_type)->implode(', '),
            'done' => $done,
            'total' => $flatCount,
            'percent' => $flatCount > 0 ? min(100, (int) round($done * 100 / $flatCount)) : null,
        ];
    }

    /**
     * Колонки полей формы для Excel: только встретившиеся, в порядке формы приложения.
     */
    private function fieldColumns(Collection $reports): array
    {
        $order = array_flip(array_keys(MobileReport::FIELDS));
        $columns = [];

        foreach ($reports as $report) {
            foreach ($report->fields() as $key => [$label]) {
                $columns[$key] = $label;
            }
        }

        uksort($columns, function ($a, $b) use ($order) {
            $rank = function ($key) use ($order) {
                $base = preg_replace('/_comment$/', '', $key);
                if (isset($order[$key])) {
                    return $order[$key] * 2;
                }

                return isset($order[$base]) ? $order[$base] * 2 + 1 : 100000;
            };

            return $rank($a) <=> $rank($b);
        });

        return $columns;
    }

    /**
     * Уменьшенная копия фото во временный файл (оригиналы 600×800 раздули бы Excel)
     */
    private function thumbnail(?string $path, int $maxSide): ?string
    {
        // Без расширения GD на сервере Excel выгружается без картинок, а не падает
        if (! $path || ! function_exists('imagecreatefromstring') || ! Storage::exists($path)) {
            return null;
        }

        $image = @imagecreatefromstring(Storage::get($path));
        if (! $image) {
            return null;
        }

        $scale = min(1, $maxSide / max(imagesx($image), imagesy($image)));
        $thumb = imagescale($image, max(1, (int) round(imagesx($image) * $scale)), max(1, (int) round(imagesy($image) * $scale)));
        $base = tempnam(sys_get_temp_dir(), 'agr_thumb_');
        @unlink($base);
        $file = $base . '.jpg';
        imagejpeg($thumb, $file, 80);

        return $file;
    }

    private function date(?string $value): ?string
    {
        return $value && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }
}
