{{--
    Список задач.
    $tasks       — коллекция ObjectTask (для $showObject — с загруженным object)
    $showObject  — показывать колонку объекта (главная, вкладка «Задачи»)
--}}
@include('tasks._styles')

@php($showObject = $showObject ?? false)

@if($tasks->isEmpty())
    <div class="task-empty">Ülesandeid pole</div>
@else
    <div class="task-table-wrap">
    <table class="task-table">
        <thead>
            <tr>
                @if($showObject)
                    <th>Objekt</th>
                    <th>Linn</th>
                @endif
                <th>Ülesanne</th>
                <th>Lisatud</th>
                @if(session('user')->role == 1)
                    <th></th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($tasks as $task)
                <tr class="task-row" data-device="{{ $task->device_type }}" data-action="{{ $task->action_type }}" data-city="{{ $task->object->City ?? '' }}">
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
                            <form method="post" action="{{ route('tasks.destroy', $task->id) }}" onsubmit="return confirm('Kas kustutada ülesanne?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="task-delete" title="Kustuta">✕</button>
                            </form>
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    <div class="task-empty task-filter-empty" hidden>Filtrile ei vasta ühtegi ülesannet</div>
@endif
