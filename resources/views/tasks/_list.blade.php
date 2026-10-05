{{--
    Список задач.
    $tasks       — коллекция ObjectTask (для $showObject — с загруженным object)
    $showObject  — показывать колонку объекта (главная, вкладка «Задачи»)
--}}
@include('tasks._styles')

@php($showObject = $showObject ?? false)

@if($tasks->isEmpty())
    <div class="task-empty">Задач нет</div>
@else
    <table class="task-table">
        <thead>
            <tr>
                @if($showObject)
                    <th>Объект</th>
                    <th>Город</th>
                @endif
                <th>Задача</th>
                <th>Добавлена</th>
                @if(session('user')->role == 1)
                    <th></th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($tasks as $task)
                <tr class="task-row">
                    @if($showObject)
                        <td>
                            @if($task->object)
                                <a href="{{ route('objects.show', $task->object_id) }}">{{ $task->object->address }}</a>
                            @else
                                #{{ $task->object_id }}
                            @endif
                        </td>
                        <td>{{ $task->object->City ?? '—' }}</td>
                    @endif
                    <td><span class="task-badge task-badge-{{ $task->device_type }}">{{ $task->label() }}</span></td>
                    <td class="task-date">{{ $task->created_at?->format('d.m.Y H:i') }}</td>
                    @if(session('user')->role == 1)
                        <td class="task-actions">
                            <form method="post" action="{{ route('tasks.destroy', $task->id) }}" onsubmit="return confirm('Удалить задачу?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="task-delete" title="Удалить">✕</button>
                            </form>
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
