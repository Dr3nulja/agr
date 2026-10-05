@extends('layouts.app')

@section('title', 'Ülesanded - AGR')

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
        .hint { color: var(--text-muted); margin-top: -6px; }
    </style>
@endsection

@section('content')
    <div class="card">
        <h2>📋 Ülesanded rakendusele</h2>
        <p class="hint">Paigaldajad näevad neid ülesandeid QRScannerApp'i avaekraanil. Ilma ülesanneteta objekti rakenduses ei kuvata.</p>
        @include('tasks._form', ['objects' => $objects])
    </div>

    <div class="card">
        <h2>Aktiivsed ülesanded ({{ $tasks->count() }})</h2>
        @include('tasks._filter', ['tasks' => $tasks])
        @include('tasks._list', ['tasks' => $tasks, 'showObject' => true])
    </div>
@endsection
