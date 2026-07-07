<?php

namespace App\Http\Controllers;

use App\Models\AgrObject;
use App\Services\Legacy\LegacyObjectsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ObjectController extends Controller
{
    /**
     * Список всех объектов
     */
    public function index(Request $request, LegacyObjectsService $legacyObjectsService)
    {
        $query = AgrObject::query();

        // Фильтрация по компании / установщику
        if ($request->input('company') !== null && $request->company !== '') {
            $query->where('Company', $request->company);
        }

        // Фильтрация по типу системы
        if ($request->input('dtype') !== null && $request->dtype !== '') {
            $query->where('dtype', $request->dtype);
        }

        // Фильтрация по статусу активности
        $query->where('status', '>', 0);
        if ($request->input('status') !== null && $request->status !== '') {
            $query->where('status', (int) $request->status);
        }

        // Фильтрация по проверке / подтверждению
        if ($request->input('checked') !== null && $request->checked !== '') {
            if ($request->checked === 'checked') {
                $query->whereNotNull('selDate');
            } elseif ($request->checked === 'unchecked') {
                $query->whereNull('selDate');
            }
        }

        // Поиск по адресу и городу
        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('address', 'like', '%' . $request->search . '%')
                      ->orWhere('City', 'like', '%' . $request->search . '%');
            });
        }

        $objects = $query->orderBy('address')->paginate(15)->withQueryString();

        foreach ($objects as $object) {
            $object->selected = $object->selDate ? 'table-success' : '';
            $object->errors = 0;
            $object->water_errors = 0;
            $object->heat_errors = 0;
        }

        $companyOptions = AgrObject::query()
            ->select('Company')
            ->distinct()
            ->orderBy('Company')
            ->pluck('Company');

        $summary = [
            'total' => AgrObject::where('status', '>', 0)->count(),
            'active' => AgrObject::where('status', 1)->count(),
            'inactive' => AgrObject::where('status', 2)->count(),
            'checked' => AgrObject::whereNotNull('selDate')->count(),
            'unchecked' => AgrObject::whereNull('selDate')->count(),
        ];

        return view('objects.index', [
            'objects' => $objects,
            'legacyObjectsService' => $legacyObjectsService,
            'companyOptions' => $companyOptions,
            'summary' => $summary,
        ]);
    }

    /**
     * Форма создания объекта
     */
    public function create()
    {
        return view('objects.create');
    }

    /**
     * Сохранение нового объекта
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'address' => 'required|string|max:255',
            'City' => 'required|string|max:255',
            'GSMNR' => 'nullable|string|max:255',
            'IMEI' => 'required|string|max:255|unique:objects',
            'IMEI2' => 'nullable|string|max:255',
            'GSMNR2' => 'nullable|string|max:255',
            'GSMSERIAL' => 'nullable|string|max:255',
            'GSMSERIAL2' => 'nullable|string|max:255',
            'Contact' => 'nullable|string|max:255',
            'Description' => 'nullable|string',
            'Description2' => 'nullable|string',
            'Company' => 'nullable|integer',
            'dtype' => 'nullable|integer',
            'Devqtty' => 'nullable|integer|min:0',
            'RadioDevQty' => 'nullable|integer|min:0',
            'MainRadio' => 'nullable|integer',
            'pin1' => 'nullable|string|max:255',
            'pin2' => 'nullable|string|max:255',
            'puk1' => 'nullable|string|max:255',
            'puk2' => 'nullable|string|max:255',
            'KeyCode' => 'nullable|string|max:255',
            'manager' => 'nullable|integer',
            'packet' => 'nullable|string|max:255',
            'traffic' => 'nullable|string|max:255',
            'callCnt' => 'nullable|integer|min:0',
            'summ' => 'nullable|numeric|min:0',
            'lat' => 'nullable|numeric',
            'lon' => 'nullable|numeric',
            'saveHval' => 'nullable|boolean',
            'status' => 'nullable|integer',
        ]);

        $validated['Company'] = $validated['Company'] ?? 1;
        $validated['dtype'] = $validated['dtype'] ?? 1;
        $validated['Devqtty'] = $validated['Devqtty'] ?? 1;
        $validated['RadioDevQty'] = $validated['RadioDevQty'] ?? 0;
        $validated['manager'] = $validated['manager'] ?? session('user')->id ?? 1;
        $validated['saveHval'] = $request->has('saveHval');
        $validated['status'] = $validated['status'] ?? 1;

        $object = AgrObject::create($validated);
        $this->logAction(sprintf('Created object #%d (IMEI: %s)', $object->id, $object->IMEI));

        return redirect()->route('objects.index')->with('success', 'Объект создан успешно');
    }

    /**
     * Просмотр объекта
     */
    public function show($item)
    {
        $item = AgrObject::findOrFail($item);
        $item->load('installData', 'csqLogs');
        return view('objects.show', [
            'object' => $item->toArray(),
            'devices' => []
        ]);
    }

    /**
     * Форма редактирования
     */
    public function edit($item)
    {
        $item = AgrObject::findOrFail($item);
        return view('objects.edit', ['object' => $item->toArray()]);
    }

    /**
     * Обновление объекта
     */
    public function update(Request $request, $item)
    {
        $item = AgrObject::findOrFail($item);
        
        $validated = $request->validate([
            'address' => 'required|string|max:255',
            'City' => 'required|string|max:255',
            'GSMNR' => 'nullable|string|max:255',
            'IMEI' => [
                'required',
                'string',
                'max:255',
                Rule::unique('objects', 'IMEI')->ignore($item->id),
            ],
            'IMEI2' => 'nullable|string|max:255',
            'GSMNR2' => 'nullable|string|max:255',
            'GSMSERIAL' => 'nullable|string|max:255',
            'GSMSERIAL2' => 'nullable|string|max:255',
            'Contact' => 'nullable|string|max:255',
            'Description' => 'nullable|string',
            'Description2' => 'nullable|string',
            'Company' => 'nullable|integer',
            'dtype' => 'nullable|integer',
            'status' => 'nullable|integer',
            'Devqtty' => 'required|integer|min:0',
            'RadioDevQty' => 'nullable|integer|min:0',
            'MainRadio' => 'nullable|integer',
            'pin1' => 'nullable|string|max:255',
            'pin2' => 'nullable|string|max:255',
            'puk1' => 'nullable|string|max:255',
            'puk2' => 'nullable|string|max:255',
            'KeyCode' => 'nullable|string|max:255',
            'manager' => 'nullable|integer',
            'packet' => 'nullable|string|max:255',
            'traffic' => 'nullable|string|max:255',
            'callCnt' => 'nullable|integer|min:0',
            'summ' => 'nullable|numeric|min:0',
            'lat' => 'nullable|numeric',
            'lon' => 'nullable|numeric',
            'saveHval' => 'nullable|boolean',
        ]);

        $validated['Company'] = $validated['Company'] ?? $item->Company;
        $validated['dtype'] = $validated['dtype'] ?? $item->dtype;
        $validated['RadioDevQty'] = $validated['RadioDevQty'] ?? $item->RadioDevQty;
        $validated['MainRadio'] = $validated['MainRadio'] ?? $item->MainRadio;
        $validated['saveHval'] = $request->has('saveHval');

        $item->update($validated);
        $this->logAction(sprintf('Updated object #%d (IMEI: %s)', $item->id, $item->IMEI));

        return redirect()->route('objects.show', $item)->with('success', 'Объект обновлен');
    }

    /**
     * Отметить объект как checked
     */
    public function check($item)
    {
        $item = AgrObject::findOrFail($item);
        $item->selDate = now();
        $item->save();
        $this->logAction(sprintf('Checked object #%d (IMEI: %s)', $item->id, $item->IMEI));

        return redirect()->route('objects.index')->with('success', 'Объект помечен как checked');
    }

    /**
     * Удаление объекта
     */
    public function destroy($item)
    {
        $item = AgrObject::findOrFail($item);
        $this->logAction(sprintf('Deleted object #%d (IMEI: %s)', $item->id, $item->IMEI));
        $item->delete();

        return redirect()->route('objects.index')->with('success', 'Объект удален');
    }

    /**
     * API - Получить все объекты (JSON)
     */
    public function apiIndex()
    {
        return response()->json(AgrObject::all());
    }

    /**
     * API - Получить объект с приборами
     */
    public function apiShow($item)
    {
        $item = AgrObject::findOrFail($item);
        return response()->json([
            'object' => $item,
            'installData' => $item->installData,
            'csqLogs' => $item->csqLogs,
        ]);
    }
}
