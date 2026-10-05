{{--
    Форма добавления задачи для мобильного приложения.
    $object  — массив/модель объекта: задача ставится на него (страница объекта)
    $objects — список объектов для выбора с поиском (главная, вкладка «Задачи»)
--}}
@include('tasks._styles')

@if(session('user')->role == 1)
    <form method="post" action="{{ route('tasks.store') }}" class="task-form">
        @csrf

        @isset($object)
            <input type="hidden" name="object_id" value="{{ $object['id'] }}">
        @else
            <div class="task-object-picker">
                <input type="search" class="task-input task-object-search" placeholder="🔍 Поиск объекта: адрес, город, ID" autocomplete="off">
                <select name="object_id" class="task-input task-object-select" required>
                    <option value="">— Выберите объект —</option>
                    @foreach($objects as $item)
                        <option value="{{ $item->id }}" @selected(old('object_id') == $item->id)>
                            {{ $item->address }}, {{ $item->City }} (#{{ $item->id }})
                        </option>
                    @endforeach
                </select>
            </div>
        @endisset

        <select name="device_type" class="task-input" required>
            @foreach(\App\Models\ObjectTask::DEVICE_TYPES as $value => $label)
                <option value="{{ $value }}" @selected(old('device_type') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="action_type" class="task-input" required>
            @foreach(\App\Models\ObjectTask::ACTION_TYPES as $value => $label)
                <option value="{{ $value }}" @selected(old('action_type') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-primary">+ Добавить задачу</button>
    </form>

    @if($errors->any())
        <div class="task-error">{{ $errors->first() }}</div>
    @endif
@endif

@once
    <script>
        // Поиск по объектам: фильтрует выпадающий список и выбирает первое совпадение
        document.addEventListener('input', function (event) {
            if (!event.target.classList.contains('task-object-search')) return;

            var query = event.target.value.trim().toLowerCase();
            var select = event.target.parentElement.querySelector('.task-object-select');
            var firstMatch = null;

            Array.prototype.forEach.call(select.options, function (option) {
                if (!option.value) return;
                var match = option.text.toLowerCase().indexOf(query) !== -1;
                option.hidden = !match;
                if (match && !firstMatch) firstMatch = option;
            });

            if (query === '') {
                select.value = '';
            } else if (firstMatch && (select.value === '' || select.selectedOptions[0].hidden)) {
                select.value = firstMatch.value;
            }
        });
    </script>
@endonce
