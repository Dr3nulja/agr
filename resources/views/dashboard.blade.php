@extends('layouts.app')

@section('title', 'Dashboard - AGR')

@section('extra-styles')
    <style>
        .card {
            padding: 24px 26px;
            border-radius: 18px;
            background: var(--surface);
            box-shadow: 0 2px 10px -2px oklch(0 0 0 / 0.06);
            margin-bottom: 20px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 18px;
            margin-top: 20px;
        }
        .stat-box {
            padding: 22px 20px;
            border-radius: 16px;
            background: var(--bg);
            text-align: center;
        }
        .stat-box.tint-good { background: linear-gradient(160deg, oklch(0.95 0.05 155), var(--surface) 60%); }
        .stat-box.tint-warn { background: linear-gradient(160deg, oklch(0.96 0.06 75), var(--surface) 60%); }
        .stat-number {
            font-family: 'IBM Plex Mono', ui-monospace, monospace;
            font-size: 2.1rem;
            font-weight: 500;
            color: var(--text);
        }
        .stat-box.tint-good .stat-number { color: oklch(0.4 0.1 155); }
        .stat-box.tint-warn .stat-number { color: oklch(0.45 0.1 70); }
        .stat-label { font-size: 0.85rem; font-weight: 500; color: var(--text-muted); margin-top: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
        th { color: var(--text-faint); font-weight: 500; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.03em; }
        td:last-child { font-family: 'IBM Plex Mono', ui-monospace, monospace; font-size: 0.85rem; color: var(--text-muted); }
        tr:hover { background: var(--bg); }
        h2 { margin-top: 0; color: var(--text); font-weight: 500; }
        .badge-admin { background: var(--primary-grad); color: white; padding: 3px 10px; border-radius: 999px; font-size: 0.75rem; margin-left: 8px; font-weight: 500; }

        .protocol-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; margin-top: 18px; }
        .protocol-box { padding: 16px 18px; border-radius: 16px; background: var(--bg); }
        .protocol-head { display: flex; align-items: center; justify-content: space-between; }
        .protocol-name { font-size: 0.9rem; font-weight: 500; }
        .protocol-tag { font-family: 'IBM Plex Mono', ui-monospace, monospace; font-size: 0.68rem; color: var(--text-faint); letter-spacing: 0.04em; }
        .protocol-count { font-family: 'IBM Plex Mono', ui-monospace, monospace; font-size: 1.5rem; font-weight: 500; margin-top: 10px; }
        .protocol-bar { height: 5px; border-radius: 3px; background: var(--border); margin-top: 10px; overflow: hidden; }
        .protocol-bar > div { height: 100%; }
    </style>
@endsection

@section('content')
    <div class="card">
        <h2>📊 Welcome back, {{ $user->name }}!</h2>
        <p style="color: var(--text-muted); margin: 0;">
            You are logged in as <strong>{{ $user->login }}</strong>
            @if($user->role === 1)
                <span class="badge-admin">ADMIN</span>
            @endif
        </p>
    </div>

    <div class="card">
        <h2>📈 Statistics</h2>
        <div class="stats">
            <div class="stat-box">
                <div class="stat-number">{{ $totalObjects }}</div>
                <div class="stat-label">Total Objects</div>
            </div>
            <div class="stat-box tint-good">
                <div class="stat-number">{{ $activeObjects }}</div>
                <div class="stat-label">Active</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">{{ $inactiveObjects ?? 0 }}</div>
                <div class="stat-label">Inactive</div>
            </div>
            <div class="stat-box tint-warn">
                <div class="stat-number">{{ $offlineObjects }}</div>
                <div class="stat-label">Offline</div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>🔌 By Protocol</h2>
        @php
            $protocolMeta = [
                1 => ['label' => 'Apator', 'tag' => 'WATER', 'color' => 'oklch(0.62 0.19 254)'],
                2 => ['label' => 'Siemens', 'tag' => 'HEAT', 'color' => 'oklch(0.7 0.15 65)'],
                3 => ['label' => 'Elekter', 'tag' => 'ELECTRIC', 'color' => 'oklch(0.68 0.15 155)'],
                4 => ['label' => 'LoRa', 'tag' => 'ELECTRIC', 'color' => 'oklch(0.62 0.18 305)'],
            ];
            $byType = $objectsByType->pluck('count', 'dtype');
            $maxCount = max(1, $byType->max() ?? 1);
        @endphp
        <div class="protocol-grid">
            @foreach($protocolMeta as $dtype => $meta)
                @php $count = $byType->get($dtype, 0); @endphp
                <div class="protocol-box">
                    <div class="protocol-head">
                        <div class="protocol-name">{{ $meta['label'] }}</div>
                        <div class="protocol-tag">{{ $meta['tag'] }}</div>
                    </div>
                    <div class="protocol-count">{{ $count }}<span style="font-size:0.8rem; color: var(--text-faint); font-weight:400;"> / {{ $totalObjects }}</span></div>
                    <div class="protocol-bar"><div style="width: {{ $totalObjects > 0 ? round($count / $totalObjects * 100) : 0 }}%; background: {{ $meta['color'] }};"></div></div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
            <h2 style="margin-bottom:0;">📋 Задачи для приложения</h2>
            <a href="{{ route('tasks.index') }}" style="color: var(--primary); text-decoration:none; font-weight:500;">Все задачи ({{ $tasksCount }}) →</a>
        </div>
        @include('tasks._form', ['objects' => $taskObjects])
        @include('tasks._list', ['tasks' => $recentTasks, 'showObject' => true])
    </div>
@endsection
