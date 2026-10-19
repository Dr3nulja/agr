@extends('layouts.app')

@section('title', 'Paigaldus - AGR')

@section('extra-styles')
    <style>
        .card { padding: 24px 26px; border-radius: 18px; background: var(--surface); box-shadow: 0 2px 10px -2px oklch(0 0 0 / 0.06); margin-bottom: 20px; }
        h2 { margin-top: 0; color: var(--text); font-weight: 500; }
        .hint { color: var(--text-muted); margin-top: -6px; }
        .inst-stats { display: flex; gap: 10px; flex-wrap: wrap; margin: 4px 0 20px; }
        .inst-stat { display: flex; flex-direction: column; gap: 2px; min-width: 130px; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--border); background: var(--bg); }
        .inst-stat-value { font-size: 1.5rem; font-weight: 600; }
        .inst-stat-label { font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.03em; }
        .inst-table-wrap { overflow-x: auto; }
        .inst-table { width: 100%; border-collapse: collapse; }
        .inst-table th, .inst-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); vertical-align: top; }
        .inst-table th { color: var(--text-muted); font-weight: 500; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.03em; }
        .inst-table a { color: var(--primary); text-decoration: none; font-weight: 500; }
        .inst-table a:hover { text-decoration: underline; }
        .inst-muted { color: var(--text-muted); font-size: 0.85rem; }
        .inst-date { font-family: 'IBM Plex Mono', ui-monospace, monospace; font-size: 0.85rem; color: var(--text-muted); white-space: nowrap; }
        .inst-empty { padding: 20px; text-align: center; background: var(--bg); border: 2px dashed var(--border); border-radius: 8px; color: var(--text-muted); }
    </style>
@endsection

@section('content')
    @include('installations._progress-styles')
    <div class="card">
        <h2>🔧 Paigaldus</h2>
        <p class="hint">Kuidas paigaldus objektidel edeneb. Andmed tulevad QRScannerApp'ist; korterite arv — objekti korterite nimekirjast (SOE).</p>

        <div class="inst-stats">
            <div class="inst-stat"><span class="inst-stat-value">{{ $rows->count() }}</span><span class="inst-stat-label">Objekti</span></div>
            <div class="inst-stat"><span class="inst-stat-value">{{ $reportsToday }}</span><span class="inst-stat-label">Aruandeid täna</span></div>
            <div class="inst-stat"><span class="inst-stat-value">{{ $reportsWeek }}</span><span class="inst-stat-label">7 päeva jooksul</span></div>
        </div>

        @if($rows->isEmpty())
            <div class="inst-empty">Paigaldusi pole. Lisa objektile ülesanne vahekaardil „Ülesanded“ — siis näevad paigaldajad seda rakenduses.</div>
        @else
            <div class="inst-table-wrap">
                <table class="inst-table">
                    <thead>
                        <tr>
                            <th>Objekt</th>
                            <th style="min-width: 280px;">Edenemine</th>
                            <th>Aruandeid</th>
                            <th>Viimane</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr>
                                <td>
                                    <a href="{{ route('installations.show', $row->object->id) }}">{{ $row->object->address }}</a>
                                    <div class="inst-muted">{{ $row->object->City }}</div>
                                </td>
                                <td>
                                    @foreach($row->progress as $progress)
                                        @include('installations._progress', ['progress' => $progress])
                                    @endforeach
                                </td>
                                <td>{{ $row->reports }}</td>
                                <td class="inst-date">{{ $row->lastReport ? \Illuminate\Support\Carbon::parse($row->lastReport)->format('d.m.Y H:i') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
