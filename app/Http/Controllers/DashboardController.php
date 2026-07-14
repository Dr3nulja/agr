<?php

namespace App\Http\Controllers;

use App\Models\AgrObject;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Главная панель управления
     */
    public function index(Request $request): View
    {
        $user = session('user');
        
        $totalObjects = AgrObject::count();
        $activeObjects = AgrObject::where('status', 1)->count();
        $inactiveObjects = AgrObject::where('status', 2)->count();
        
        // Объекты по типам
        $objectsByType = AgrObject::selectRaw('dtype, COUNT(*) as count')
                              ->groupBy('dtype')
                              ->get();

        // Последние логи
        $recentLogs = Log::orderBy('ins_date', 'desc')->limit(10)->get();

        // Объекты без связи (последняя сессия больше суток назад)
        $offlineObjects = AgrObject::where('lastSession', '<', now()->subDay())->count();

        return view('dashboard', [
            'user' => $user,
            'totalObjects' => $totalObjects,
            'activeObjects' => $activeObjects,
            'inactiveObjects' => $inactiveObjects,
            'objectsByType' => $objectsByType,
            'recentLogs' => $recentLogs,
            'offlineObjects' => $offlineObjects,
        ]);
    }

    /**
     * Статистика объектов (JSON API)
     */
    public function stats()
    {
        return response()->json([
            'total' => AgrObject::count(),
            'active' => AgrObject::where('status', 1)->count(),
            'inactive' => AgrObject::where('status', 2)->count(),
            'offline' => AgrObject::where('lastSession', '<', now()->subDay())->count(),
            'byType' => AgrObject::selectRaw('dtype, COUNT(*) as count')
                             ->groupBy('dtype')
                             ->pluck('count', 'dtype'),
        ]);
    }
}