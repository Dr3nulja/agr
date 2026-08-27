<?php

namespace App\Http\Controllers;

use App\Services\Legacy\LegacyObjectsService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SoeReportController extends Controller
{
    public function csv(int $objectId, Request $request, LegacyObjectsService $legacyObjectsService): StreamedResponse
    {
        $data = $this->validated($request);
        $report = $legacyObjectsService->calculateSoeCosts($objectId, $data['kuu'], $data['maks']);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Korteri nr.');
        $sheet->setCellValue('B1', 'Anduri number');
        $sheet->setCellValue('C1', 'Radiaatori voimsus');
        $sheet->setCellValue('D1', 'Koefitsient');
        $sheet->setCellValue('E1', 'Algnait');
        $sheet->setCellValue('F1', 'Loppnait');
        $sheet->setCellValue('G1', 'Anduri lugem');
        $sheet->setCellValue('H1', 'Korrutis (C x D x G)');

        $row = 2;

        foreach ($report['flats'] as $flat) {
            foreach ($flat['devices'] as $device) {
                $sheet->setCellValue('A' . $row, $flat['location']);
                $sheet->setCellValue('B' . $row, $device['devid']);
                $sheet->setCellValue('C' . $row, $device['power']);
                $sheet->setCellValue('D' . $row, $device['cof']);
                $sheet->setCellValue('E' . $row, $device['min']);
                $sheet->setCellValue('F' . $row, $device['max']);
                $sheet->setCellValue('G' . $row, $device['diff']);
                $sheet->setCellValue('H' . $row, round($device['consumption'], 2));
                $row++;
            }
        }

        $fileName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $report['address']) . '_soe';
        $writer = new Csv($spreadsheet);
        $writer->setDelimiter(';');
        $writer->setEnclosure('');
        $writer->setLineEnding("\r\n");

        return response()->streamDownload(static function () use ($writer): void {
            $writer->save('php://output');
        }, $fileName . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function xlsx(int $objectId, Request $request, LegacyObjectsService $legacyObjectsService): StreamedResponse
    {
        $data = $this->validated($request);
        $report = $legacyObjectsService->calculateSoeCosts($objectId, $data['kuu'], $data['maks']);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('SOE Report');
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        $sheet->setCellValue('A1', $report['address'] . ' ' . $report['city']);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->setCellValue('A2', 'Month: ' . $report['month'] . '   Payment: ' . number_format($report['payment'], 2));
        $sheet->setCellValue('A3', 'm2 cost budget: ' . number_format($report['m2_cost'], 2) . '   sensor cost budget: ' . number_format($report['andur_cost'], 2));

        $headerRow = 5;
        $sheet->setCellValue('A' . $headerRow, 'Apartment');
        $sheet->setCellValue('B' . $headerRow, 'Size m2');
        $sheet->setCellValue('C' . $headerRow, 'm2 share');
        $sheet->setCellValue('D' . $headerRow, 'Sensor share');
        $sheet->setCellValue('E' . $headerRow, 'Total');
        $sheet->getStyle('A' . $headerRow . ':E' . $headerRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $headerRow . ':E' . $headerRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);

        $row = $headerRow + 1;

        foreach ($report['flats'] as $flat) {
            $sheet->setCellValue('A' . $row, $flat['location']);
            $sheet->setCellValue('B' . $row, $flat['size']);
            $sheet->setCellValue('C' . $row, round($flat['m2_value'], 2));
            $sheet->setCellValue('D' . $row, round($flat['sensor_value'], 2));
            $sheet->setCellValue('E' . $row, round($flat['total'], 2));
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'Total');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->setCellValue('B' . $row, $report['grand']['m2']);
        $sheet->setCellValue('C' . $row, round($report['grand']['m2_cost'], 2));
        $sheet->setCellValue('D' . $row, round($report['grand']['sensor_cost'], 2));
        $sheet->setCellValue('E' . $row, round($report['grand']['total'], 2));
        $sheet->getStyle('A' . ($headerRow + 1) . ':E' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $fileName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $report['address']) . '_soe_' . $report['month'];
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(static function () use ($writer): void {
            $writer->save('php://output');
        }, $fileName . '.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'maks' => ['required', 'numeric', 'min:0.01'],
            'kuu' => ['required', 'integer', 'between:1,12'],
        ]);
    }
}
