@extends('layouts.app')

@section('title', 'Задачи - AGR')

@section('extra-styles')
    <style>
        .card {
            padding: 24px 26px;
            border-radius: 18px;
            background: var(--surface);
            box-shadow: 0 2px 10px -2px oklch(0 0 0 / 0.06);
            margin-bottom: 20px;
        }
        h2 { margin-top: 0; color: var(--text); font-weight: 500; }
        .card-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
        .hint { color: var(--text-muted); margin-top: -6px; }
    </style>
@endsection

@section('content')
    <div class="card">
        <h2>📋 Задачи для приложения</h2>
        <p class="hint">Монтажники видят эти задачи на первом экране QRScannerApp. Объект без задач в приложении не показывается.</p>
        @include('tasks._form', ['objects' => $objects])
    </div>

    <div class="card">
        <div class="card-head">
            <h2>Текущие задачи ({{ $tasks->count() }})</h2>
            @if($tasks->isNotEmpty())
                <input type="search" id="taskFilter" class="task-input" placeholder="🔍 Фильтр" autocomplete="off">
            @endif
        </div>
        @include('tasks._list', ['tasks' => $tasks, 'showObject' => true])
    </div>
@endsection

@section('extra-scripts')
    <script>
        var taskFilter = document.getElementById('taskFilter');
        if (taskFilter) {
            taskFilter.addEventListener('input', function () {
                var query = taskFilter.value.trim().toLowerCase();
                document.querySelectorAll('.task-row').forEach(function (row) {
                    row.hidden = row.textContent.toLowerCase().indexOf(query) === -1;
                });
            });
        }
    </script>
@endsection
