@extends('layouts.app')

@section('title', 'Hooldus - AGR')

@php($isAdmin = session('user')->role == 1)

@section('extra-styles')
    <style>
        .card { padding: 24px 26px; border-radius: 18px; background: var(--surface); box-shadow: 0 2px 10px -2px oklch(0 0 0 / 0.06); margin-bottom: 20px; }
        h2 { margin-top: 0; color: var(--text); font-weight: 500; }
        .hint { color: var(--text-muted); margin-top: -6px; }
        .flash { padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; background: oklch(0.96 0.04 155); color: oklch(0.38 0.1 155); border: 1px solid oklch(0.88 0.07 155); }

        .mt-filters { display: flex; gap: 8px; flex-wrap: wrap; margin: 4px 0 16px; }
        .mt-chip { padding: 7px 14px; border-radius: 999px; border: 1px solid var(--border); background: var(--bg); color: var(--text); text-decoration: none; font-size: 0.9rem; }
        .mt-chip:hover { border-color: var(--primary); }
        .mt-chip.is-active { background: var(--primary); border-color: transparent; color: white; }
        .mt-sep { width: 1px; background: var(--border); margin: 0 4px; }

        .mt-table-wrap { overflow-x: auto; }
        .mt-table { width: 100%; border-collapse: collapse; }
        .mt-table th, .mt-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid var(--border); vertical-align: top; }
        .mt-table th { color: var(--text-muted); font-weight: 500; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.03em; }
        .mt-table tr.is-done td { color: var(--text-muted); }
        .mt-badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 0.82rem; font-weight: 500; white-space: nowrap; }
        .mt-badge-water { background: oklch(0.94 0.04 240); color: oklch(0.42 0.14 240); }
        .mt-badge-heat { background: oklch(0.95 0.06 50); color: oklch(0.48 0.15 40); }
        .mt-comment { white-space: pre-line; max-width: 420px; }
        .mt-muted { color: var(--text-muted); font-size: 0.85rem; }
        .mt-actions { white-space: nowrap; width: 1%; }
        .mt-actions form { display: inline; }
        .mt-btn { padding: 6px 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg); color: var(--text); font: inherit; font-size: 0.85rem; cursor: pointer; }
        .mt-btn:hover { border-color: var(--primary); }
        .mt-btn-danger:hover { border-color: var(--danger); color: var(--danger); }
        .mt-empty { padding: 20px; text-align: center; background: var(--bg); border: 2px dashed var(--border); border-radius: 8px; color: var(--text-muted); }
        .mt-pagination { margin-top: 14px; display: flex; gap: 12px; align-items: center; }
    </style>
@endsection

@section('content')
    <div class="card">
        <h2>🔧 Hooldus</h2>
        <p class="hint">Hoolduse kirjed, mille paigaldajad on rakendusest saatnud. Avatud: {{ $openCount }}</p>

        @if(session('success'))
            <div class="flash">{{ session('success') }}</div>
        @endif

        <div class="mt-filters">
            @foreach(['open' => 'Avatud', 'done' => 'Tehtud', 'all' => 'Kõik'] as $value => $label)
                <a href="{{ route('maintenance.index', array_filter(['state' => $value, 'kind' => $kind])) }}" class="mt-chip {{ $state === $value ? 'is-active' : '' }}">{{ $label }}</a>
            @endforeach
            <span class="mt-sep"></span>
            <a href="{{ route('maintenance.index', ['state' => $state]) }}" class="mt-chip {{ isset(\App\Models\MaintenanceRequest::KINDS[$kind]) ? '' : 'is-active' }}">Vesi ja soojus</a>
            @foreach(\App\Models\MaintenanceRequest::KINDS as $value => $label)
                <a href="{{ route('maintenance.index', ['state' => $state, 'kind' => $value]) }}" class="mt-chip {{ $kind === $value ? 'is-active' : '' }}">{{ $value === 'water' ? '💧' : '🔥' }} {{ $label }}</a>
            @endforeach
        </div>

        @if($requests->isEmpty())
            <div class="mt-empty">Kirjeid pole</div>
        @else
            <div class="mt-table-wrap">
                <table class="mt-table">
                    <thead>
                        <tr>
                            <th>Aeg</th>
                            <th>Liik</th>
                            <th>Aadress</th>
                            <th>Kommentaar</th>
                            <th>Paigaldaja</th>
                            <th>Staatus</th>
                            @if($isAdmin)<th></th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requests as $item)
                            <tr class="{{ $item->done_at ? 'is-done' : '' }}">
                                <td class="mt-muted">{{ $item->created_at?->format('d.m.Y H:i') }}</td>
                                <td><span class="mt-badge mt-badge-{{ $item->kind }}">{{ $item->kind === 'water' ? '💧' : '🔥' }} {{ $item->kindLabel() }}</span></td>
                                <td>{{ $item->address }}</td>
                                <td class="mt-comment">{{ $item->comment ?? '—' }}</td>
                                <td>{{ $item->installer ?? '—' }}</td>
                                <td>
                                    @if($item->done_at)
                                        ✓ Tehtud <span class="mt-muted">{{ $item->done_at->format('d.m.Y') }}</span>
                                    @else
                                        Avatud
                                    @endif
                                </td>
                                @if($isAdmin)
                                    <td class="mt-actions">
                                        <form method="POST" action="{{ route('maintenance.done', $item->id) }}">
                                            @csrf
                                            <button type="submit" class="mt-btn">{{ $item->done_at ? 'Ava uuesti' : '✓ Tehtud' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('maintenance.destroy', $item->id) }}" onsubmit="return confirm('Kas kustutada kirje?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="mt-btn mt-btn-danger">Kustuta</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($requests->hasPages())
                <div class="mt-pagination">
                    @if(! $requests->onFirstPage())
                        <a href="{{ $requests->previousPageUrl() }}" class="mt-chip">&laquo; Eelmine</a>
                    @endif
                    <span class="mt-muted">Leht {{ $requests->currentPage() }} / {{ $requests->lastPage() }}</span>
                    @if($requests->hasMorePages())
                        <a href="{{ $requests->nextPageUrl() }}" class="mt-chip">Järgmine &raquo;</a>
                    @endif
                </div>
            @endif
        @endif
    </div>
@endsection
