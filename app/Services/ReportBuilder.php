<?php

namespace App\Services;

use App\Models\AgrObject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * XLSX-отчёты по показаниям: и для кнопок экспорта, и для отправки на почту.
 * Логотип — по настоящей компании объекта (AGR / Prem).
 */
class ReportBuilder
{
    /**
     * Водомеры: начальное / конечное показание и потребление за месяц.
     * null — у объекта нет таблиц приборов.
     */
    public function water(AgrObject $object): ?Spreadsheet
    {
        $objectId = (int) $object->id;

        if (! $this->hasTables($objectId)) {
            return null;
        }

        [$spreadsheet, $sheet] = $this->newSheet();

        $sheet->setCellValue('A3', 'krt. nr.');
        $sheet->setCellValue('B3', 'veearvesti nr.');
        $sheet->setCellValue('C3', 'Soe/külm');
        $sheet->setCellValue('D3', 'Veemõõtja algnäit');
        $sheet->setCellValue('E3', 'Veemõtja lõppnäit');
        $sheet->setCellValue('F3', 'Kulu kuus, m3');
        $this->styleHeader($sheet, 'A3:F3');
        $this->addAddress($sheet, $object);
        $this->addLogo($sheet, $object, 'E1');

        $data = DB::table('object_' . $objectId . ' as O')
            ->leftJoin('lastdata_' . $objectId . ' as L', 'O.devid', '=', 'L.devid')
            ->select(['O.devid', 'O.id as object_row_id', 'O.location', 'O.devtype', 'L.date', 'L.value', 'L.mvalue', 'L.prevVal'])
            ->orderBy('O.id')
            ->get();

        $i = 5;
        foreach ($data as $Record) {
            $sheet->setCellValue('A' . $i, $Record->location);
            $sheet->setCellValue('B' . $i, $Record->devid);

            if ((int) $Record->devtype === 1) {
                $sheet->getStyle('B' . $i)->getFont()->getColor()->setRGB('0000FF');
                $sheet->setCellValue('C' . $i, 'külm');
            } elseif ((int) $Record->devtype === 2) {
                $sheet->getStyle('B' . $i)->getFont()->getColor()->setRGB('FF0000');
                $sheet->setCellValue('C' . $i, 'soe');
            } else {
                $sheet->setCellValue('C' . $i, '');
            }

            $date = $Record->date ?? null;
            if ($date !== null && date('m', strtotime($date)) == 12 && date('m') == 1) {
                $sheet->setCellValue('D' . $i, ($Record->mvalue ?? 0) / 1000);
                $sheet->setCellValue('E' . $i, ($Record->value ?? 0) / 1000);
            } else {
                $sheet->setCellValue('D' . $i, ($Record->prevVal ?? 0) / 1000);
                $sheet->setCellValue('E' . $i, ($Record->mvalue ?? 0) / 1000);
            }

            $sheet->setCellValue('F' . $i, '=E' . $i . '-D' . $i);
            $i++;
        }

        $sheet->getStyle('A5:B' . ($i - 1))->getFont()->setBold(true);
        $sheet->getStyle('A5:B' . ($i - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B5:B' . ($i - 1))->getNumberFormat()->setFormatCode('# ### ##0');
        $sheet->getStyle('A4:F' . ($i - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        return $spreadsheet;
    }

    /**
     * Аллокаторы тепла: последнее показание и месячное значение
     */
    public function allocator(AgrObject $object): ?Spreadsheet
    {
        $objectId = (int) $object->id;

        if (! $this->hasTables($objectId)) {
            return null;
        }

        [$spreadsheet, $sheet] = $this->newSheet();

        $sheet->setCellValue('A3', 'krt. nr.');
        $sheet->setCellValue('B3', 'soe nr.');
        $sheet->setCellValue('C3', 'Soemõõtja lõppnäit');
        $sheet->setCellValue('D3', 'Kulu kuus');
        $this->styleHeader($sheet, 'A3:D3');
        $this->addAddress($sheet, $object);
        $this->addLogo($sheet, $object, 'D1');

        $data = DB::table('object_' . $objectId . ' as O')
            ->leftJoin('lastdata_' . $objectId . ' as L', 'O.devid', '=', 'L.devid')
            ->where(function ($query): void {
                $query->where('O.devtype', 3)
                    ->orWhereBetween('O.devtype', [30, 39]);
            })
            ->select(['O.devid', 'O.id as object_row_id', 'O.location', 'O.devtype', 'L.date', 'L.value', 'L.mvalue'])
            ->orderBy('O.id')
            ->get();

        $i = 5;
        foreach ($data as $record) {
            $sheet->setCellValue('A' . $i, $record->location);
            $sheet->setCellValue('B' . $i, $record->devid);
            $sheet->setCellValue('C' . $i, $record->value ?? 0);
            $sheet->setCellValue('D' . $i, $record->mvalue ?? 0);
            $i++;
        }

        $sheet->getStyle('A5:B' . ($i - 1))->getFont()->setBold(true);
        $sheet->getStyle('A5:B' . ($i - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A5:D' . ($i - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        return $spreadsheet;
    }

    /**
     * Показания и потребление по месяцам (страница «Näidud»): дом, квартира, прибор
     */
    public function readings(AgrObject $object, array $devices, array $monthly, array $periods, string $title): Spreadsheet
    {
        [$spreadsheet, $sheet] = $this->newSheet();

        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $this->addAddress($sheet, $object);
        $this->addLogo($sheet, $object, 'F1');

        $headers = ['Maja', 'Korter', 'Seadme nr', 'Tüüp', 'Viimane näit', 'Näidu kuupäev'];
        foreach ($periods as $period) {
            $headers[] = 'Kulu ' . $period;
        }

        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, 3], $header);
        }
        $lastColumn = $sheet->getHighestColumn(3);
        $this->styleHeader($sheet, 'A3:' . $lastColumn . '3');

        $address = trim(($object->address ?? '') . ', ' . ($object->City ?? ''), ', ');
        $row = 4;
        foreach ($devices as $device) {
            $values = [
                $address,
                $device['location'],
                $device['devid'],
                $device['type_label'],
                $device['value'],
                $device['date'] ? date('d.m.Y', strtotime($device['date'])) : '',
            ];
            foreach ($periods as $period) {
                $values[] = $monthly[$device['devid']][$period]['consumption'] ?? null;
            }
            foreach ($values as $index => $value) {
                $sheet->setCellValue([$index + 1, $row], $value);
            }
            $row++;
        }

        if ($row > 4) {
            $sheet->getStyle('A4:' . $lastColumn . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle('G4:' . $lastColumn . ($row - 1))->getNumberFormat()->setFormatCode('0.000');
        }
        foreach (range(1, count($headers)) as $column) {
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /**
     * Файл во временную папку (для вложения в письмо)
     */
    public function saveTemp(Spreadsheet $spreadsheet): string
    {
        $path = tempnam(sys_get_temp_dir(), 'agr') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    public function download(Spreadsheet $spreadsheet, string $fileName)
    {
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, $fileName . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public static function fileSafe(string $value): string
    {
        return trim(preg_replace('/[^A-Za-z0-9_\-]+/', '_', $value), '_');
    }

    private function hasTables(int $objectId): bool
    {
        return Schema::hasTable('object_' . $objectId) && Schema::hasTable('lastdata_' . $objectId);
    }

    private function newSheet(): array
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Report');
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10);

        return [$spreadsheet, $sheet];
    }

    private function styleHeader(Worksheet $sheet, string $range): void
    {
        $sheet->getRowDimension('3')->setRowHeight(41);
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getAlignment()->setWrapText(true);
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
    }

    private function addAddress(Worksheet $sheet, AgrObject $object): void
    {
        $sheet->setCellValue('A2', ($object->address ?? '') . ' ' . ($object->City ?? ''));
        $sheet->getStyle('A2')->getFont()->setBold(true);
    }

    private function addLogo(Worksheet $sheet, AgrObject $object, string $cell): void
    {
        $path = $object->reportLogoPath();

        if ($path === null) {
            return;
        }

        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setPath($path);
        $drawing->setHeight(36);
        $drawing->setCoordinates($cell);
        $drawing->setWorksheet($sheet);
        $sheet->getRowDimension('1')->setRowHeight(30);
    }
}
