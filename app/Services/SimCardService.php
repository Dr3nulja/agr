<?php

namespace App\Services;

use App\Models\AgrObject;
use App\Models\SimCard;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Все смены статуса SIM-карт. Держит в синхроне колонки objects
 * (GSMNR/GSMSERIAL/pin/puk и GSMNR2/GSMSERIAL2) с назначенными картами.
 */
class SimCardService
{
    public function issue(SimCard $sim, string $technician): void
    {
        if ($sim->isAssigned()) {
            throw ValidationException::withMessages(['sim' => 'Objektil olev SIM tuleb enne vabastada']);
        }

        $sim->status = SimCard::STATUS_ISSUED;
        $sim->issued_to = $technician;
        $sim->issued_at = now();
        $sim->save();
    }

    /**
     * Вернуть карту в свободные — единственный выход из «назначена».
     */
    public function release(SimCard $sim): void
    {
        DB::transaction(function () use ($sim) {
            $objectId = $sim->object_id;

            $sim->status = SimCard::STATUS_FREE;
            $sim->issued_to = null;
            $sim->issued_at = null;
            $sim->object_id = null;
            $sim->slot = null;
            $sim->assigned_at = null;
            $sim->save();

            if ($objectId) {
                $this->syncObject($objectId);
            }
        });
    }

    /**
     * Поставить свободную/выданную карту в слот объекта. Слот должен быть пуст:
     * замену делает setObjectSlot (старая карта уходит в свободные).
     * $installer — кто ставит карту; без него остаётся техник, которому карта была выдана.
     */
    public function assign(SimCard $sim, AgrObject $object, int $slot, ?string $installer = null): void
    {
        if ($sim->isAssigned()) {
            if ((int) $sim->object_id === (int) $object->id && (int) $sim->slot === $slot) {
                return;
            }
            throw ValidationException::withMessages(['sim' => 'SIM on juba objektil — vabasta see enne']);
        }

        $occupied = SimCard::where('object_id', $object->id)->where('slot', $slot)->first();
        if ($occupied) {
            throw ValidationException::withMessages([
                'sim' => sprintf('Objekti pesas %s on juba SIM %s', SimCard::SLOTS[$slot], $occupied->label()),
            ]);
        }

        DB::transaction(function () use ($sim, $object, $slot, $installer) {
            if ($installer !== null) {
                $sim->issued_to = $installer;
            }
            $sim->status = SimCard::STATUS_ASSIGNED;
            $sim->object_id = $object->id;
            $sim->slot = $slot;
            $sim->assigned_at = now();
            $sim->save();

            $this->syncObject($object->id);
        });
    }

    /**
     * Выбор карты в форме объекта: null — снять карту со слота,
     * другая карта — старая уходит в свободные, новая назначается.
     */
    public function setObjectSlot(AgrObject $object, int $slot, ?int $simId): void
    {
        $current = SimCard::where('object_id', $object->id)->where('slot', $slot)->first();

        if ($current && (int) $current->id === $simId) {
            return;
        }

        DB::transaction(function () use ($object, $slot, $simId, $current) {
            if ($current) {
                $this->release($current);
            }
            if ($simId) {
                $sim = SimCard::findOrFail($simId);
                // Перестановка между слотами одного объекта
                if ($sim->isAssigned() && (int) $sim->object_id === (int) $object->id) {
                    $this->release($sim);
                    $sim->refresh();
                }
                $this->assign($sim, $object, $slot);
            }
        });
    }

    /**
     * Объект удаляют — его карты становятся свободными.
     */
    public function releaseObject(AgrObject $object): void
    {
        SimCard::where('object_id', $object->id)->get()->each(fn (SimCard $sim) => $this->release($sim));
    }

    /**
     * Карты, которые можно выбрать в слот объекта: свободные, выданные
     * и уже стоящие на этом объекте.
     */
    public function selectableFor(?AgrObject $object = null)
    {
        return SimCard::query()
            ->where(function ($query) use ($object) {
                $query->whereIn('status', [SimCard::STATUS_FREE, SimCard::STATUS_ISSUED]);
                if ($object) {
                    $query->orWhere('object_id', $object->id);
                }
            })
            ->orderBy('number')
            ->get();
    }

    /**
     * Копируем назначенные карты в колонки objects (их читает легаси-код).
     */
    public function syncObject(int $objectId): void
    {
        $sims = SimCard::where('object_id', $objectId)->get()->keyBy('slot');
        $first = $sims->get(1);
        $second = $sims->get(2);

        DB::table('objects')->where('id', $objectId)->update([
            'GSMNR' => $first->number ?? '',
            'GSMSERIAL' => $first->iccid ?? '',
            'pin1' => $first->pin1 ?? '',
            'pin2' => $first->pin2 ?? '',
            'puk1' => $first->puk1 ?? '',
            'puk2' => $first->puk2 ?? '',
            'GSMNR2' => $second->number ?? '',
            'GSMSERIAL2' => $second->iccid ?? '',
        ]);
    }
}
