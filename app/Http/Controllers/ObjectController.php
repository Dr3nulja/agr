<?php

namespace App\Http\Controllers;

use App\Models\AgrObject;
use App\Models\SimCard;
use App\Services\Legacy\LegacyObjectsService;
use App\Services\Legacy\ObjectTablesProvisioner;
use App\Mail\ObjectReportMail;
use App\Services\ObjectCheckService;
use App\Services\ReportBuilder;
use App\Services\SimCardService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ObjectController extends Controller
{
    /**
     * Список всех объектов
     */
    public function index(Request $request, LegacyObjectsService $legacyObjectsService)
    {
        $query = AgrObject::query();

        // Фильтрация по компании / установщику (AGR и Prem — одна группа)
        if ($request->input('company') !== null && $request->company !== '') {
            if ($request->company === 'agr') {
                $query->whereIn('Company', AgrObject::COMPANY_GROUP);
            } else {
                $query->where('Company', $request->company);
            }
        }

        // Фильтрация по менеджеру: IRA / KAT / REG / NAT, можно несколько
        $managers = array_values(array_intersect(
            array_map('intval', (array) $request->input('manager', [])),
            array_keys(AgrObject::MANAGERS)
        ));
        if ($managers !== []) {
            $query->whereIn('manager', $managers);
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
            } elseif ($request->checked === 'water') {
                $query->whereNull('selDate')->whereNotNull('water_checked_at');
            } elseif ($request->checked === 'unchecked') {
                $query->whereNull('selDate')->whereNull('water_checked_at');
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
            $errorCounts = $legacyObjectsService->getObjectErrorCounts((int) $object->id);
            $object->errors = $errorCounts['errors'] ?? 0;
            $object->water_errors = $errorCounts['water_errors'] ?? 0;
            $object->heat_errors = $errorCounts['heat_errors'] ?? 0;
        }

        $companyOptions = AgrObject::query()
            ->select('Company')
            ->distinct()
            ->orderBy('Company')
            ->pluck('Company')
            ->filter()
            ->mapWithKeys(fn ($company) => in_array((int) $company, AgrObject::COMPANY_GROUP, true)
                ? ['agr' => AgrObject::companyDisplayName($company)]
                : [(string) $company => AgrObject::companyDisplayName($company)]);

        $summary = [
            'total' => AgrObject::where('status', '>', 0)->count(),
            'active' => AgrObject::where('status', 1)->count(),
            'inactive' => AgrObject::where('status', 2)->count(),
            'checked' => AgrObject::whereNotNull('selDate')->count(),
            'water' => AgrObject::whereNull('selDate')->whereNotNull('water_checked_at')->count(),
            'unchecked' => AgrObject::whereNull('selDate')->whereNull('water_checked_at')->count(),
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
    public function create(SimCardService $simCardService)
    {
        return view('objects.create', [
            'simOptions' => $simCardService->selectableFor(),
            'simSlots' => [],
        ]);
    }

    /**
     * Сохранение нового объекта
     */
    public function store(Request $request, SimCardService $simCardService)
    {
        $validated = $request->validate([
            'address' => 'required|string|max:255',
            'City' => 'required|string|max:255',
            'IMEI' => 'required|string|max:255|unique:objects',
            'IMEI2' => 'nullable|string|max:255',
            'Contact' => 'nullable|string|max:255',
            'Description' => 'nullable|string',
            'Description2' => 'nullable|string',
            'Company' => 'nullable|integer',
            'dtype' => 'nullable|integer',
            'Devqtty' => 'nullable|integer|min:0',
            'RadioDevQty' => 'nullable|integer|min:0',
            'MainRadio' => 'nullable|integer',
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
            'report_emails' => self::reportEmailsRules(),
        ] + self::simSlotRules());

        $validated['Company'] = $validated['Company'] ?? 1;
        $validated['dtype'] = $validated['dtype'] ?? 1;
        $validated['Devqtty'] = $validated['Devqtty'] ?? 1;
        $validated['RadioDevQty'] = $validated['RadioDevQty'] ?? 0;
        $validated['manager'] = $validated['manager'] ?? session('user')->id ?? 1;
        $validated['saveHval'] = $request->has('saveHval');
        $validated['status'] = $validated['status'] ?? 1;
        $validated['report_emails'] = self::normalizeEmails($validated['report_emails'] ?? null);

        $object = DB::transaction(function () use ($validated, $simCardService) {
            $object = AgrObject::create(collect($validated)->except(['sim1_id', 'sim2_id'])->all());
            $this->applySimSlots($object, $validated, $simCardService);

            return $object;
        });
        $this->provisionObjectTables($object->id, (int) $object->dtype);
        $this->logAction(sprintf('Created object #%d (IMEI: %s)', $object->id, $object->IMEI));

        return redirect()->route('objects.index')->with('success', 'Objekt lisatud');
    }

    /**
     * SIM в слотах 1/2 выбираются из регистра: свободные, выданные технику
     * или уже стоящие на этом объекте. Чужую назначенную карту выбрать нельзя.
     */
    private static function simSlotRules(?AgrObject $object = null): array
    {
        $selectable = Rule::exists('sim_cards', 'id')->where(function ($query) use ($object) {
            $query->whereIn('status', [SimCard::STATUS_FREE, SimCard::STATUS_ISSUED]);
            if ($object) {
                $query->orWhere('object_id', $object->id);
            }
        });

        return [
            'sim1_id' => ['nullable', 'integer', 'different:sim2_id', $selectable],
            'sim2_id' => ['nullable', 'integer', $selectable],
        ];
    }

    /**
     * Email для отчётов: можно несколько через запятую, каждый проверяем
     */
    private static function reportEmailsRules(): array
    {
        return ['nullable', 'string', 'max:500', function ($attribute, $value, $fail) {
            foreach (AgrObject::parseEmails($value) as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                    $fail('Vigane e-posti aadress: ' . $email);

                    return;
                }
            }
        }];
    }

    private static function normalizeEmails(?string $emails): ?string
    {
        return implode(', ', AgrObject::parseEmails($emails)) ?: null;
    }

    private function applySimSlots(AgrObject $object, array $validated, SimCardService $simCardService): void
    {
        foreach (array_keys(SimCard::SLOTS) as $slot) {
            // Поля нет в запросе — слот не трогаем (а не снимаем карту)
            if (! array_key_exists('sim' . $slot . '_id', $validated)) {
                continue;
            }
            $simId = $validated['sim' . $slot . '_id'];
            $simCardService->setObjectSlot($object, $slot, $simId ? (int) $simId : null);
        }
    }

    /**
     * Таблицы объекта: процедурой NewObject / NewElekterObject, если она есть в базе,
     * иначе сами (ObjectTablesProvisioner). Ошибка не должна ломать создание объекта.
     */
    private function provisionObjectTables(int $objectId, int $dtype): void
    {
        try {
            app(ObjectTablesProvisioner::class)->provision($objectId, $dtype);
        } catch (QueryException $e) {
            report($e);
        }
    }

    /**
     * Просмотр объекта
     */
    public function show($item, LegacyObjectsService $legacyObjectsService, ObjectCheckService $checks)
    {
        $item = AgrObject::with('installData', 'csqLogs', 'tasks', 'simCards')->findOrFail($item);
        $devices = $legacyObjectsService->getDevices((int) $item->id, (int) $item->dtype);
        $errorCounts = $legacyObjectsService->getObjectErrorCounts((int) $item->id);

        return view('objects.show', [
            'object' => $item->toArray(),
            'devices' => $devices,
            'installData' => $item->installData->toArray(),
            'csqLogs' => $item->csqLogs->toArray(),
            'tasks' => $item->tasks->sortBy('id')->values(),
            'simCards' => $item->simCards->keyBy('slot'),
            'mobileReports' => $item->mobileReports()->latest('id')->get(),
            'legacyErrorCounts' => $errorCounts,
            'commands' => $legacyObjectsService->getCommandOptions((int) $item->dtype),
            'model' => $item,
            'nextCheck' => $checks->nextStep($item),
            'nextCheckIsFinal' => $checks->nextIsFinal($item),
        ]);
    }

    /**
     * Форма редактирования
     */
    public function edit($item)
    {
        $item = AgrObject::findOrFail($item);

        return view('objects.edit', [
            'object' => $item->toArray(),
            'simOptions' => app(SimCardService::class)->selectableFor($item),
            'simSlots' => $item->simCards->pluck('id', 'slot')->all(),
        ]);
    }

    /**
     * Обновление объекта
     */
    public function update(Request $request, $item, SimCardService $simCardService)
    {
        $item = AgrObject::findOrFail($item);
        
        $validated = $request->validate([
            'address' => 'required|string|max:255',
            'City' => 'required|string|max:255',
            'IMEI' => [
                'required',
                'string',
                'max:255',
                Rule::unique('objects', 'IMEI')->ignore($item->id),
            ],
            'IMEI2' => 'nullable|string|max:255',
            'Contact' => 'nullable|string|max:255',
            'Description' => 'nullable|string',
            'Description2' => 'nullable|string',
            'Company' => 'nullable|integer',
            'dtype' => 'nullable|integer',
            'status' => 'nullable|integer',
            'Devqtty' => 'required|integer|min:0',
            'RadioDevQty' => 'nullable|integer|min:0',
            'MainRadio' => 'nullable|integer',
            'KeyCode' => 'nullable|string|max:255',
            'manager' => 'nullable|integer',
            'packet' => 'nullable|string|max:255',
            'traffic' => 'nullable|string|max:255',
            'callCnt' => 'nullable|integer|min:0',
            'summ' => 'nullable|numeric|min:0',
            'lat' => 'nullable|numeric',
            'lon' => 'nullable|numeric',
            'saveHval' => 'nullable|boolean',
            'report_emails' => self::reportEmailsRules(),
        ] + self::simSlotRules($item));

        $validated['Company'] = $validated['Company'] ?? $item->Company;
        $validated['dtype'] = $validated['dtype'] ?? $item->dtype;
        $validated['RadioDevQty'] = $validated['RadioDevQty'] ?? $item->RadioDevQty;
        $validated['MainRadio'] = $validated['MainRadio'] ?? $item->MainRadio;
        $validated['saveHval'] = $request->has('saveHval');
        $validated['report_emails'] = self::normalizeEmails($validated['report_emails'] ?? null);

        DB::transaction(function () use ($item, $validated, $simCardService) {
            $item->update(collect($validated)->except(['sim1_id', 'sim2_id'])->all());
            $this->applySimSlots($item, $validated, $simCardService);
        });
        $this->logAction(sprintf('Updated object #%d (IMEI: %s)', $item->id, $item->IMEI));

        return redirect()->route('objects.show', $item)->with('success', 'Objekt salvestatud');
    }

    /**
     * Поставить следующий чек: вода → тепло (или сразу «сдано всё»)
     */
    public function check($item, ObjectCheckService $checks)
    {
        $item = AgrObject::findOrFail($item);
        $step = $checks->check($item);

        if ($step === null) {
            return back()->with('error', 'Kõik andmed on juba esitatud');
        }

        $this->logAction(sprintf('Checked object #%d (IMEI: %s): %s', $item->id, $item->IMEI, $step));

        return redirect()->route('objects.show', $item)->with('success', self::checkMessage($item));
    }

    /**
     * Снять чеки
     */
    public function uncheck($item, ObjectCheckService $checks)
    {
        $item = AgrObject::findOrFail($item);
        $checks->reset($item);
        $this->logAction(sprintf('Unchecked object #%d (IMEI: %s)', $item->id, $item->IMEI));

        return redirect()->route('objects.show', $item)->with('success', 'Märked eemaldatud');
    }

    /**
     * Отправить отчёт следующего шага на report_emails объекта и поставить чек
     */
    public function sendReport($item, ObjectCheckService $checks, ReportBuilder $reports)
    {
        $item = AgrObject::findOrFail($item);
        $emails = $item->reportEmails();

        if ($emails === []) {
            return back()->with('error', 'Objektil pole aruannete e-posti aadressi — lisa see objekti muutmise vormis');
        }

        $step = $checks->nextStep($item);
        if ($step === null) {
            return back()->with('error', 'Kõik andmed on juba esitatud');
        }

        [$spreadsheet, $title, $fileName] = $step === ObjectCheckService::STEP_HEAT
            ? [$reports->allocator($item), 'Jaoturite näidud', 'Report_Alokator_']
            : [$reports->water($item), 'Veemõõtjate näidud', 'Report_Water_'];

        if ($spreadsheet === null) {
            return back()->with('error', 'Objekti seadmete tabelid puuduvad — aruannet ei saa koostada');
        }

        $path = $reports->saveTemp($spreadsheet);
        try {
            Mail::to($emails)->send(new ObjectReportMail(
                $item,
                $title,
                $path,
                $fileName . ReportBuilder::fileSafe($item->address . ' ' . $item->City) . '.xlsx'
            ));
        } catch (TransportExceptionInterface $e) {
            report($e);

            return back()->with('error', 'Kirja saatmine ebaõnnestus: ' . $e->getMessage());
        } finally {
            @unlink($path);
        }

        $checks->check($item);
        $this->logAction(sprintf('Sent %s report of object #%d to %s', $step, $item->id, implode(', ', $emails)));

        return redirect()->route('objects.show', $item)
            ->with('success', sprintf('Aruanne saadetud: %s. %s', implode(', ', $emails), self::checkMessage($item)));
    }

    private static function checkMessage(AgrObject $item): string
    {
        return $item->checkState() === AgrObject::CHECK_ALL
            ? 'Kõik andmed esitatud ✓'
            : 'Vesi esitatud, soojus veel ootab';
    }

    /**
     * Удаление объекта
     */
    public function destroy($item, SimCardService $simCardService)
    {
        $item = AgrObject::findOrFail($item);
        $this->logAction(sprintf('Deleted object #%d (IMEI: %s)', $item->id, $item->IMEI));
        $simCardService->releaseObject($item);
        $item->tasks()->delete();
        $item->delete();

        return redirect()->route('objects.index')->with('success', 'Objekt kustutatud');
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
