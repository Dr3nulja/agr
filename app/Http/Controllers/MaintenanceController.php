<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Вкладка «Hooldus»: заявки на обслуживание из мобильного приложения
 */
class MaintenanceController extends Controller
{
    public function index(Request $request): View
    {
        $kind = $request->input('kind');
        $state = $request->input('state', 'open');

        $query = MaintenanceRequest::query()->latest('id');

        if (isset(MaintenanceRequest::KINDS[$kind])) {
            $query->where('kind', $kind);
        }
        if ($state === 'open') {
            $query->whereNull('done_at');
        } elseif ($state === 'done') {
            $query->whereNotNull('done_at');
        }

        return view('maintenance.index', [
            'requests' => $query->paginate(50)->withQueryString(),
            'kind' => $kind,
            'state' => $state,
            'openCount' => MaintenanceRequest::whereNull('done_at')->count(),
        ]);
    }

    public function toggleDone($maintenance)
    {
        $maintenance = MaintenanceRequest::findOrFail($maintenance);
        $maintenance->done_at = $maintenance->done_at ? null : now();
        $maintenance->save();

        $this->logAction(sprintf('Maintenance #%d marked %s', $maintenance->id, $maintenance->done_at ? 'done' : 'open'));

        return back()->with('success', $maintenance->done_at ? 'Märgitud tehtuks' : 'Märgitud avatuks');
    }

    public function destroy($maintenance)
    {
        $maintenance = MaintenanceRequest::findOrFail($maintenance);
        $maintenance->delete();
        $this->logAction(sprintf('Deleted maintenance #%d (%s)', $maintenance->id, $maintenance->address));

        return back()->with('success', 'Hoolduse kirje kustutatud');
    }
}
