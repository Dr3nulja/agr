<?php

namespace Database\Seeders;

use App\Models\AgrObject;
use App\Models\ObjectTask;
use Illuminate\Database\Seeder;

/**
 * Тестовые задачи для мобильного приложения на первых объектах.
 * Re-runnable: удаляет все задачи и ставит заново.
 *
 *   php artisan db:seed --class=ObjectTasksSeeder
 */
class ObjectTasksSeeder extends Seeder
{
    private const TASKS = [
        [['water_meter', 'replace']],
        [['allocator', 'install'], ['water_meter', 'install']],
        [['allocator', 'replace']],
    ];

    public function run(): void
    {
        ObjectTask::query()->delete();

        $objects = AgrObject::orderBy('id')->limit(count(self::TASKS))->get();

        foreach ($objects as $i => $object) {
            foreach (self::TASKS[$i] as [$deviceType, $actionType]) {
                ObjectTask::create([
                    'object_id' => $object->id,
                    'device_type' => $deviceType,
                    'action_type' => $actionType,
                ]);
            }
        }
    }
}
