<?php

namespace App\Http\Controllers;

use App\Models\AgrObject;
use App\Models\ObjectTask;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Задачи объектов для мобильного приложения (вкладка «Задачи», главная, страница объекта)
 */
class ObjectTaskController extends Controller
{
    public function index(): View
    {
        return view('tasks.index', [
            'objects' => self::objectOptions(),
            'tasks' => ObjectTask::with('object')->latest('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'object_id' => ['required', 'integer', Rule::exists('objects', 'id')],
            'device_type' => ['required', Rule::in(array_keys(ObjectTask::DEVICE_TYPES))],
            'action_type' => ['required', Rule::in(array_keys(ObjectTask::ACTION_TYPES))],
        ], [
            'object_id.required' => 'Выберите объект',
            'object_id.exists' => 'Объект не найден',
        ]);

        $task = ObjectTask::firstOrCreate($validated);

        if ($task->wasRecentlyCreated) {
            $this->logAction(sprintf('Added task "%s" to object #%d', $task->label(), $task->object_id));
        }

        return back()->with('success', 'Задача добавлена');
    }

    public function destroy($task)
    {
        $task = ObjectTask::findOrFail($task);
        $task->delete();
        $this->logAction(sprintf('Removed task "%s" from object #%d', $task->label(), $task->object_id));

        return back()->with('success', 'Задача удалена');
    }

    /**
     * Объекты для выпадающего списка с поиском
     */
    public static function objectOptions()
    {
        return AgrObject::orderBy('City')->orderBy('address')->get(['id', 'address', 'City']);
    }
}
