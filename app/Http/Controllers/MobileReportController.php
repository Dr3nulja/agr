<?php

namespace App\Http\Controllers;

use App\Models\MobileReport;
use Illuminate\Support\Facades\Storage;

/**
 * Просмотр отчётов из приложения в дашборде
 */
class MobileReportController extends Controller
{
    /**
     * Фото/подпись отчёта. Файлы не публичные: подписи владельцев — личные данные.
     */
    public function file($report, string $file)
    {
        abort_unless(in_array($file, MobileReport::FILES, true), 404);

        $report = MobileReport::findOrFail($report);
        $path = $report->{$file};

        abort_unless($path && Storage::exists($path), 404);

        return Storage::response($path);
    }

    public function destroy($report)
    {
        $report = MobileReport::findOrFail($report);

        Storage::deleteDirectory('mobile_reports/'.$report->id);

        $report->delete();
        $this->logAction(sprintf('Deleted mobile report #%d (object #%d, apartment %s)', $report->id, $report->object_id, $report->apartment));

        return back()->with('success', 'Aruanne kustutatud');
    }
}
