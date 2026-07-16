<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ExportController extends Controller
{
    public function export(int $objectId, Request $request)
    {
        $fileName = 'Report_object_' . $objectId;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Report');
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10);

        $sheet->setCellValue('A3', 'krt. nr.');
        $sheet->setCellValue('B3', 'veearvesti nr.');
        $sheet->setCellValue('C3', 'Soe/külm');
        $sheet->setCellValue('D3', 'Veemõõtja algnäit');
        $sheet->setCellValue('E3', 'Veemõtja lõppnäit');
        $sheet->setCellValue('F3', 'Kulu kuus, m3');

        $sheet->getRowDimension('3')->setRowHeight(41);
        $sheet->getStyle('A3:F3')->getFont()->setBold(true);
        $sheet->getStyle('A3:F3')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A3:F3')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);

        // Pull object name/address
        $object = DB::table('objects')->where('id', $objectId)->first();
        if ($object) {
            $address = ($object->address ?? '') . ' ' . ($object->City ?? '');
            $sheet->setCellValue('A2', $address);
            $sheet->getStyle('A2')->getFont()->setBold(true);
        }

        if (! Schema::hasTable('object_' . $objectId) || ! Schema::hasTable('lastdata_' . $objectId)) {
            return redirect()->back()->with('error', 'Legacy tables for this object are missing.');
        }

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

        // Output
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportAlokator(int $objectId)
    {
        $fileName = 'Report_Alokator_' . $objectId;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Report');
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10);

        $sheet->setCellValue('A3', 'krt. nr.');
        $sheet->setCellValue('B3', 'soe nr.');
        $sheet->setCellValue('C3', 'Soemõõtja lõppnäit');
        $sheet->setCellValue('D3', 'Kulu kuus');

        $sheet->getRowDimension('3')->setRowHeight(41);
        $sheet->getStyle('A3:D3')->getFont()->setBold(true);
        $sheet->getStyle('A3:D3')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A3:D3')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);

        $object = DB::table('objects')->where('id', $objectId)->first();
        if ($object) {
            $address = ($object->address ?? '') . ' ' . ($object->City ?? '');
            $sheet->setCellValue('A2', $address);
            $sheet->getStyle('A2')->getFont()->setBold(true);
            $fileName = 'Report_Alokator_' . preg_replace('/[^A-Za-z0-9_\-]+/', '_', $address);
        }

        if (! Schema::hasTable('object_' . $objectId) || ! Schema::hasTable('lastdata_' . $objectId)) {
            return redirect()->back()->with('error', 'Legacy tables for this object are missing.');
        }

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

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, $fileName . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
