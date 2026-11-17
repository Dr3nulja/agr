@extends('layouts.app')

@section('title', 'Lekked - AGR')

@php($isAdmin = session('user')->role == 1)

@section('extra-styles')
    <style>
        .card { padding: 24px 26px; border-radius: 18px; background: var(--surface); box-shadow: 0 2px 10px -2px oklch(0 0 0 / 0.06); margin-bottom: 20px; }
        h2 { margin-top: 0; color: var(--text); font-weight: 500; }
        .hint { color: var(--text-muted); margin-top: -6px; }
        .lk-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; }
        .lk-filters { display: flex; gap: 8px; flex-wrap: wrap; margin: 4px 0 16px; }
        .lk-chip { padding: 7px 14px; border-radius: 999px; border: 1px solid var(--border); background: var(--bg); color: var(--text); text-decoration: none; font-size: 0.9rem; }
        .lk-chip:hover { border-color: var(--primary); }
        .lk-chip.is-active { background: var(--primary); border-color: transparent; color: white; }
        .lk-table-wrap { overflow-x: auto; }
        .lk-table { width: 100%; border-collapse: collapse; }
        .lk-table th, .lk-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid var(--border); white-space: nowrap; }
        .lk-table th { color: var(--text-muted); font-weight: 500; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.03em; }
        .lk-table td.num { text-align: right; font-family: 'IBM Plex Mono', ui-monospace, monospace; }
        .lk-table tr.is-resolved td { color: var(--text-muted); }
        .lk-amount { font-weight: 600; color: oklch(0.5 0.19 25); }
        .lk-devid { font-family: 'IBM Plex Mono', ui-monospace, monospace; font-weight: 600; text-decoration: none; }
        .lk-devid.tone-cold { color: oklch(0.5 0.17 250); }
        .lk-devid.tone-hot { color: oklch(0.55 0.19 25); }
        .lk-muted { color: var(--text-muted); font-size: 0.85rem; }
        .lk-btn { padding: 7px 14px; border: 1px solid var(--border); border-radius: 999px; background: var(--bg); color: var(--text); font: inherit; font-size: 0.85rem; cursor: pointer; text-decoration: none; }
        .lk-btn:hover { border-color: var(--primary); }
        .lk-btn-primary { background: var(--primary-grad); color: white; border-color: transparent; }
        .lk-empty { padding: 20px; text-align: center; background: var(--bg); border: 2px dashed var(--border); border-radius: 8px; color: var(--text-muted); }
        .lk-pagination { margin-top: 14px; display: flex; gap: 12px; align-items: center; }
    </style>
@endsection

@section('content')
    @include('partials.flash')

    <div class="card">
        <div class="lk-head">
            <div>
                <h2>💧 Võimalikud lekked</h2>
                <p class="hint">Veemõõtjad, mille kulu oli {{ rtrim(rtrim(number_format($threshold, 3, '.', ''), '0'), '.') }} m³ ööpäevas või rohkem. Kontroll käib iga hommikul eelmise päeva kohta. Avatud: {{ $openCount }}</p>
            </div>
            @if($isAdmin)
                <form method="POST" action="{{ route('leaks.check') }}" style="margin: 0;">
                    @csrf
                    <button type="submit" class="lk-btn lk-btn-primary">🔍 Kontrolli kohe</button>
                </form>
            @endif
        </div>

        <div class="lk-filters">
            @foreach(['open' => 'Avatud', 'resolved' => 'Lahendatud', 'all' => 'Kõik'] as $value => $label)
                <a href="{{ route('leaks.index', ['state' => $value]) }}" class="lk-chip {{ $state === $value ? 'is-active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>

        @if($alerts->isEmpty())
            <div class="lk-empty">Lekkeid pole</div>
        @else
            <div class="lk-table-wrap">
                <table class="lk-table">
                    <thead>
                        <tr>
                            <th>Päev</th>
                            <th>Maja</th>
                            <th>Korter</th>
                            <th>Seade</th>
                            <th style="text-align: right;">m³ ööpäevas</th>
                            <th style="text-align: right;">Näit</th>
                            <th>Staatus</th>
                            @if($isAdmin)<th></th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alerts as $alert)
                            <tr class="{{ $alert->resolved_at ? 'is-resolved' : '' }}">
                                <td>{{ $alert->day->format('d.m.Y') }}</td>
                                <td>
                                    @if($alert->object)
                                        <a href="{{ route('objects.show', $alert->object_id) }}" style="color: inherit;">{{ $alert->object->address }}</a>
                                        <div class="lk-muted">{{ $alert->object->City }}</div>
                                    @else
                                        #{{ $alert->object_id }}
                                    @endif
                                </td>
                                <td>{{ $alert->location ?: '—' }}</td>
                                <td>
                                    <a href="{{ route('readings.device', [$alert->object_id, $alert->devid, 'back' => request()->fullUrl()]) }}" class="lk-devid tone-{{ \App\Services\ReadingsService::tone($alert->devtype) }}" title="{{ \App\Services\ReadingsService::typeLabel($alert->devtype) }}">{{ $alert->devid }}</a>
                                </td>
                                <td class="num lk-amount">{{ number_format($alert->consumption, 3, '.', ' ') }}</td>
                                <td class="num lk-muted" title="{{ $alert->from_date->format('d.m.Y H:i') }} → {{ $alert->to_date->format('d.m.Y H:i') }}">{{ number_format($alert->from_value, 3, '.', ' ') }} → {{ number_format($alert->to_value, 3, '.', ' ') }}</td>
                                <td>
                                    @if($alert->resolved_at)
                                        ✓ Lahendatud <span class="lk-muted">{{ $alert->resolved_at->format('d.m.Y') }} {{ $alert->resolved_by }}</span>
                                    @else
                                        ⚠️ Avatud
                                    @endif
                                </td>
                                @if($isAdmin)
                                    <td>
                                        <form method="POST" action="{{ route('leaks.resolve', $alert->id) }}" style="margin: 0;">
                                            @csrf
                                            <button type="submit" class="lk-btn">{{ $alert->resolved_at ? 'Ava uuesti' : '✓ Lahendatud' }}</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($alerts->hasPages())
                <div class="lk-pagination">
                    @if(! $alerts->onFirstPage())
                        <a href="{{ $alerts->previousPageUrl() }}" class="lk-chip">&laquo; Eelmine</a>
                    @endif
                    <span class="lk-muted">Leht {{ $alerts->currentPage() }} / {{ $alerts->lastPage() }}</span>
                    @if($alerts->hasMorePages())
                        <a href="{{ $alerts->nextPageUrl() }}" class="lk-chip">Järgmine &raquo;</a>
                    @endif
                </div>
            @endif
        @endif
    </div>
@endsection
