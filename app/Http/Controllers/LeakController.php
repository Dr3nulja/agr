<?php

namespace App\Http\Controllers;

use App\Models\LeakAlert;
use App\Services\LeakDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Вкладка «Lekked»: водомеры с расходом ≥ порога (по умолчанию 5 м³) за сутки
 */
class LeakController extends Controller
{
    public function index(Request $request): View
    {
        $state = $request->input('state', 'open');

        $query = LeakAlert::with('object')->orderByDesc('day')->orderByDesc('consumption');
        if ($state === 'open') {
            $query->whereNull('resolved_at');
        } elseif ($state === 'resolved') {
            $query->whereNotNull('resolved_at');
        }

        return view('leaks.index', [
            'alerts' => $query->paginate(50)->withQueryString(),
            'state' => $state,
            'openCount' => LeakAlert::open()->count(),
            'threshold' => LeakDetector::threshold(),
        ]);
    }

    /**
     * Проверить сейчас: вчера и сегодня (сегодня — по данным на текущий момент)
     */
    public function check(LeakDetector $detector)
    {
        $found = array_merge(
            $detector->scanDay(Carbon::yesterday()),
            $detector->scanDay(Carbon::today()),
        );
        $this->logAction(sprintf('Leak check: %d new', count($found)));

        return back()->with('success', $found ? sprintf('Leitud uusi lekkeid: %d', count($found)) : 'Uusi lekkeid ei leitud');
    }

    public function resolve($leak)
    {
        $leak = LeakAlert::findOrFail($leak);
        $user = session('user');

        if ($leak->resolved_at) {
            $leak->resolved_at = null;
            $leak->resolved_by = null;
        } else {
            $leak->resolved_at = now();
            $leak->resolved_by = (string) ($user->login ?? $user->name ?? '');
        }
        $leak->save();

        $this->logAction(sprintf('Leak #%d (object #%d, device %d) marked %s', $leak->id, $leak->object_id, $leak->devid, $leak->resolved_at ? 'resolved' : 'open'));

        return back()->with('success', $leak->resolved_at ? 'Märgitud lahendatuks' : 'Märgitud avatuks');
    }
}
