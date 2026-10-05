{{--
    Форма добавления задачи для мобильного приложения.
    $object  — массив/модель объекта: задача ставится на него (страница объекта)
    $objects — список объектов для выбора с поиском (главная, вкладка «Задачи»)

    Форма — в отдельной тонированной панели с подписями полей, чтобы её не путали
    с кнопками фильтра над списком (tasks._filter).
--}}
@include('tasks._styles')

@if(session('user')->role == 1)
    <form method="post" action="{{ route('tasks.store') }}" class="task-form-panel">
        @csrf
        <div class="task-form-title">➕ Uus ülesanne</div>

        <div class="task-form">
            @isset($object)
                <input type="hidden" name="object_id" value="{{ $object['id'] }}">
            @else
                <label class="task-field task-field-object">
                    <span class="task-field-label">Objekt</span>
                    <span class="task-object-picker">
                        <input type="search" class="task-input task-object-search" placeholder="🔍 Otsi: aadress, linn, ID" autocomplete="off">
                        <select name="object_id" class="task-input task-object-select" required>
                            <option value="">— Vali objekt —</option>
                            @foreach($objects as $item)
                                <option value="{{ $item->id }}" @selected(old('object_id') == $item->id)>
                                    {{ $item->address }}, {{ $item->City }} (#{{ $item->id }})
                                </option>
                            @endforeach
                        </select>
                    </span>
                </label>
            @endisset

            <label class="task-field">
                <span class="task-field-label">Seade</span>
                <select name="device_type" class="task-input" required>
                    @foreach(\App\Models\ObjectTask::DEVICE_TYPES as $value => $label)
                        <option value="{{ $value }}" @selected(old('device_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="task-field">
                <span class="task-field-label">Toiming</span>
                <select name="action_type" class="task-input" required>
                    @foreach(\App\Models\ObjectTask::ACTION_TYPES as $value => $label)
                        <option value="{{ $value }}" @selected(old('action_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <button type="submit" class="task-submit">Lisa ülesanne</button>
        </div>

        @if($errors->any())
            <div class="task-error">{{ $errors->first() }}</div>
        @endif
    </form>
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
