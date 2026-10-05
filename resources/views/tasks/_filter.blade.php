{{--
    Фильтр списка задач кнопками (по data-* атрибутам строк tasks._list).
    $tasks — та же коллекция, что в списке; на кнопках — число совпадений.

    Маленькие переключатели-«таблетки» по группам — специально не похожи на поля
    формы и кнопку «Lisa ülesanne», чтобы фильтр не путали с добавлением.
--}}
@php
    $groups = [
        'device' => ['Seade', collect(\App\Models\ObjectTask::DEVICE_TYPES)->map(fn ($label, $value) => [$label, $tasks->where('device_type', $value)->count()])],
        'action' => ['Toiming', collect(\App\Models\ObjectTask::ACTION_TYPES)->map(fn ($label, $value) => [$label, $tasks->where('action_type', $value)->count()])],
        'city' => ['Linn', $tasks->map(fn ($task) => $task->object->City ?? null)->filter()->countBy()->sortKeys()->map(fn ($count, $city) => [$city, $count])],
    ];
    // В группе с одним вариантом фильтровать нечего
    $groups = array_filter($groups, fn ($group) => $group[1]->count() > 1);
@endphp

@if($groups !== [])
<div class="task-filter" role="group" aria-label="Filtreeri ülesandeid">
    <div class="task-filter-head">
        <span class="task-filter-title">Filtreeri</span>
        <span class="task-filter-shown"></span>
        <button type="button" class="task-filter-reset" hidden>Lähtesta</button>
    </div>

    @foreach($groups as $key => [$title, $options])
        <div class="task-filter-group" data-filter="{{ $key }}">
            <span class="task-filter-label">{{ $title }}</span>
            <button type="button" class="task-chip is-active" data-value="" aria-pressed="true">Kõik</button>
            @foreach($options as $value => [$label, $count])
                <button type="button" class="task-chip" data-value="{{ $value }}" aria-pressed="false" @disabled($count === 0)>
                    {{ $label }} <span class="task-chip-count">{{ $count }}</span>
                </button>
            @endforeach
        </div>
    @endforeach
</div>
@endif

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () { document.querySelectorAll('.task-filter').forEach(function (filter) {
            var card = filter.parentElement;
            var rows = card.querySelectorAll('.task-row');
            var empty = card.querySelector('.task-filter-empty');
            var shown = filter.querySelector('.task-filter-shown');
            var reset = filter.querySelector('.task-filter-reset');

            function selected() {
                var values = {};
                filter.querySelectorAll('.task-filter-group').forEach(function (group) {
                    values[group.dataset.filter] = group.querySelector('.task-chip.is-active').dataset.value;
                });
                return values;
            }

            function apply() {
                var values = selected();
                var visible = 0;

                rows.forEach(function (row) {
                    var match = Object.keys(values).every(function (key) {
                        return values[key] === '' || row.dataset[key] === values[key];
                    });
                    row.hidden = !match;
                    if (match) visible++;
                });

                var filtered = Object.keys(values).some(function (key) { return values[key] !== ''; });
                shown.textContent = filtered ? visible + ' / ' + rows.length : '';
                reset.hidden = !filtered;
                if (empty) empty.hidden = visible > 0;
            }

            function activate(chip) {
                chip.parentElement.querySelectorAll('.task-chip').forEach(function (other) {
                    other.classList.toggle('is-active', other === chip);
                    other.setAttribute('aria-pressed', other === chip ? 'true' : 'false');
                });
            }

            filter.addEventListener('click', function (event) {
                var chip = event.target.closest('.task-chip');
                if (chip) {
                    activate(chip);
                    apply();
                } else if (event.target === reset) {
                    filter.querySelectorAll('.task-chip[data-value=""]').forEach(activate);
                    apply();
                }
            });
        }); });
    </script>
@endonce
