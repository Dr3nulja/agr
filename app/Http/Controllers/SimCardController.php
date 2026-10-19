<?php

namespace App\Http\Controllers;

use App\Models\AgrObject;
use App\Models\SimCard;
use App\Services\SimCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Регистр SIM-карт (вкладка «SIM-kaardid»)
 */
class SimCardController extends Controller
{
    public function __construct(private SimCardService $sims)
    {
    }

    public function index(Request $request): View
    {
        $status = $request->input('status');
        $search = trim((string) $request->input('q'));

        $query = SimCard::with('object');

        if (isset(SimCard::STATUSES[$status])) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('number', 'like', '%' . $search . '%')
                    ->orWhere('iccid', 'like', '%' . $search . '%')
                    ->orWhere('issued_to', 'like', '%' . $search . '%')
                    ->orWhere('comment', 'like', '%' . $search . '%')
                    ->orWhereHas('object', fn ($object) => $object->where('address', 'like', '%' . $search . '%'));
            });
        }

        return view('sims.index', [
            'sims' => $query->orderBy('number')->paginate(50)->withQueryString(),
            'counts' => SimCard::query()->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status'),
            'status' => $status,
            'search' => $search,
            'objects' => ObjectTaskController::objectOptions(),
            'technicians' => SimCard::whereNotNull('issued_to')->distinct()->orderBy('issued_to')->pluck('issued_to'),
        ]);
    }

    public function store(Request $request)
    {
        $sim = new SimCard($this->validateCard($request));
        $sim->status = SimCard::STATUS_FREE;
        $sim->save();

        $this->logAction(sprintf('Added SIM %s', $sim->label()));

        return back()->with('success', 'SIM lisatud');
    }

    /**
     * Несколько карт за раз: строка = «номер;ICCID;PIN1;PUK1;PIN2;PUK2», лишние поля можно опустить.
     */
    public function storeBulk(Request $request)
    {
        $request->validate(['lines' => 'required|string']);

        $rows = collect(preg_split('/\R/', $request->input('lines')))
            ->map(fn ($line) => array_map('trim', preg_split('/[;\t,]/', $line)))
            ->filter(fn ($fields) => ($fields[0] ?? '') !== '' || ($fields[1] ?? '') !== '')
            ->values();

        $added = 0;
        $skipped = [];

        foreach ($rows as $fields) {
            [$number, $iccid, $pin1, $puk1, $pin2, $puk2] = array_pad(array_map(fn ($v) => $v === '' ? null : $v, $fields), 6, null);

            $exists = SimCard::query()
                ->when($number, fn ($q) => $q->orWhere('number', $number))
                ->when($iccid, fn ($q) => $q->orWhere('iccid', $iccid))
                ->exists();

            if ($exists) {
                $skipped[] = $number ?? $iccid;
                continue;
            }

            $sim = new SimCard(compact('number', 'iccid', 'pin1', 'pin2', 'puk1', 'puk2'));
            $sim->status = SimCard::STATUS_FREE;
            $sim->save();
            $added++;
        }

        $this->logAction(sprintf('Added %d SIM cards in bulk', $added));

        $message = sprintf('Lisatud %d SIM-i', $added);
        if ($skipped !== []) {
            $message .= '. Juba registris: ' . implode(', ', $skipped);
        }

        return back()->with('success', $message);
    }

    public function update(Request $request, $sim)
    {
        $sim = SimCard::findOrFail($sim);
        $sim->update($this->validateCard($request, $sim));

        if ($sim->object_id) {
            $this->sims->syncObject($sim->object_id);
        }

        $this->logAction(sprintf('Updated SIM %s', $sim->label()));

        return back()->with('success', 'SIM salvestatud');
    }

    public function issue(Request $request, $sim)
    {
        $sim = SimCard::findOrFail($sim);
        $validated = $request->validate(
            ['issued_to' => 'required|string|max:100'],
            ['issued_to.required' => 'Kellele SIM väljastati?']
        );

        $this->sims->issue($sim, trim($validated['issued_to']));
        $this->logAction(sprintf('Issued SIM %s to %s', $sim->label(), $sim->issued_to));

        return back()->with('success', sprintf('SIM väljastatud: %s', $sim->issued_to));
    }

    public function release($sim)
    {
        $sim = SimCard::findOrFail($sim);
        $previous = $sim->isAssigned() ? 'object #' . $sim->object_id : ($sim->issued_to ?? 'free');

        $this->sims->release($sim);
        $this->logAction(sprintf('Released SIM %s (was: %s)', $sim->label(), $previous));

        return back()->with('success', 'SIM on nüüd vaba');
    }

    public function assign(Request $request, $sim)
    {
        $sim = SimCard::findOrFail($sim);
        $validated = $request->validate([
            'object_id' => ['required', 'integer', Rule::exists('objects', 'id')],
            'slot' => ['required', Rule::in(array_keys(SimCard::SLOTS))],
        ], [
            'object_id.required' => 'Vali objekt',
            'object_id.exists' => 'Objekti ei leitud',
        ]);

        $object = AgrObject::findOrFail($validated['object_id']);
        $this->sims->assign($sim, $object, (int) $validated['slot']);
        $this->logAction(sprintf('Assigned SIM %s to object #%d slot %d', $sim->label(), $object->id, $sim->slot));

        return back()->with('success', sprintf('SIM määratud: %s', $object->address));
    }

    public function destroy($sim)
    {
        $sim = SimCard::findOrFail($sim);

        if ($sim->isAssigned()) {
            throw ValidationException::withMessages(['sim' => 'Objektil olevat SIM-i ei saa kustutada — vabasta see enne']);
        }

        $sim->delete();
        $this->logAction(sprintf('Deleted SIM %s', $sim->label()));

        return back()->with('success', 'SIM kustutatud');
    }

    private function validateCard(Request $request, ?SimCard $sim = null): array
    {
        $validated = $request->validate([
            'number' => ['nullable', 'string', 'max:50', 'required_without:iccid', Rule::unique('sim_cards', 'number')->ignore($sim?->id)],
            'iccid' => ['nullable', 'string', 'max:50', Rule::unique('sim_cards', 'iccid')->ignore($sim?->id)],
            'pin1' => 'nullable|string|max:50',
            'pin2' => 'nullable|string|max:50',
            'puk1' => 'nullable|string|max:50',
            'puk2' => 'nullable|string|max:50',
            'comment' => 'nullable|string|max:1000',
        ], [
            'number.required_without' => 'Sisesta number või ICCID',
            'number.unique' => 'Selle numbriga SIM on juba registris',
            'iccid.unique' => 'Selle ICCID-ga SIM on juba registris',
        ]);

        return array_map(fn ($value) => is_string($value) ? (trim($value) === '' ? null : trim($value)) : $value, $validated);
    }
}
