@extends('layouts.app')

@section('title', 'Paigaldus: ' . $object->address . ' - AGR')

@section('extra-styles')
    <style>
        .card { padding: 24px 26px; border-radius: 18px; background: var(--surface); box-shadow: 0 2px 10px -2px oklch(0 0 0 / 0.06); margin-bottom: 20px; }
        h2 { margin: 0 0 4px; color: var(--text); font-weight: 500; }
        h3 { margin: 0 0 14px; font-weight: 500; }
        .inst-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; }
        .inst-muted { color: var(--text-muted); font-size: 0.9rem; }
        .inst-mono { font-family: 'IBM Plex Mono', ui-monospace, monospace; font-size: 0.85rem; color: var(--text-muted); }
        .inst-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .inst-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 999px; border: 1px solid var(--border); background: var(--bg); color: var(--text); font: inherit; font-size: 0.9rem; text-decoration: none; cursor: pointer; white-space: nowrap; }
        .inst-btn:hover { border-color: var(--primary); }
        .inst-btn-primary { background: var(--primary-grad); color: white; border-color: transparent; }

        .inst-filter { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--border); }
        .inst-field { display: flex; flex-direction: column; gap: 4px; }
        .inst-label { font-size: 0.75rem; font-weight: 500; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.03em; }
        .inst-input { padding: 9px 12px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface); color: var(--text); font: inherit; }

        .inst-overview { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 24px; }
        .inst-days { display: grid; gap: 4px; max-height: 220px; overflow-y: auto; }
        .inst-day { display: grid; grid-template-columns: 86px 1fr 32px; align-items: center; gap: 8px; font-size: 0.85rem; }
        .inst-day-bar { height: 8px; border-radius: 999px; background: var(--primary); opacity: 0.75; }
        .inst-day-count { text-align: right; font-family: 'IBM Plex Mono', ui-monospace, monospace; color: var(--text-muted); }

        .inst-missing { display: flex; gap: 6px; flex-wrap: wrap; }
        .inst-missing span { padding: 3px 10px; border-radius: 999px; background: oklch(0.96 0.03 20); color: oklch(0.45 0.13 20); font-family: 'IBM Plex Mono', ui-monospace, monospace; font-size: 0.85rem; }

        .inst-apartment { border: 1px solid var(--border); border-radius: 12px; background: var(--bg); margin-bottom: 10px; }
        .inst-apartment > summary { list-style: none; cursor: pointer; padding: 12px 14px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .inst-apartment > summary::-webkit-details-marker { display: none; }
        .inst-apartment > summary::before { content: '▸'; color: var(--text-muted); }
        .inst-apartment[open] > summary::before { content: '▾'; }
        .inst-apartment-name { font-weight: 600; min-width: 80px; }
        .inst-apartment-body { padding: 0 14px 6px; }
        .inst-report { padding: 14px 0; border-top: 1px solid var(--border); }
        .inst-report-head { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-bottom: 10px; }
        .inst-report-date { margin-left: auto; }
        .inst-report-images { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 12px; }
        .inst-report-images figure { margin: 0; text-align: center; font-size: 0.8rem; color: var(--text-muted); }
        .inst-report-images img { display: block; height: 160px; border-radius: 8px; border: 1px solid var(--border); background: white; margin-bottom: 4px; }
        .inst-report-images img.is-signature { height: 110px; }
        .inst-fields { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 6px 16px; font-size: 0.9rem; margin: 0; }
        .inst-fields dt { color: var(--text-muted); font-size: 0.78rem; }
        .inst-fields dd { margin: 0 0 6px; word-break: break-word; }
        .inst-empty { padding: 20px; text-align: center; background: var(--bg); border: 2px dashed var(--border); border-radius: 8px; color: var(--text-muted); }

        @media (max-width: 800px) {
            .inst-overview { grid-template-columns: 1fr; }
        }
    </style>
@endsection

@section('content')
    @include('installations._progress-styles')

    <div class="card">
        <div class="inst-head">
            <div>
                <h2>🔧 {{ $object->address }}</h2>
                <div class="inst-muted">{{ $object->City }} · {{ $reportCount }} aruannet · {{ $apartments->count() }} korterit</div>
            </div>
            <div class="inst-actions">
                <a href="{{ route('installations.print', ['object' => $object->id] + $query) }}" target="_blank" class="inst-btn inst-btn-primary">📄 PDF</a>
                <a href="{{ route('installations.xlsx', ['object' => $object->id] + $query) }}" class="inst-btn">📥 Excel</a>
                <a href="{{ route('installations.xlsx', ['object' => $object->id, 'photos' => 1] + $query) }}" class="inst-btn">📥 Excel fotodega</a>
                <a href="{{ route('objects.show', $object->id) }}" class="inst-btn">Objekti leht</a>
                <a href="{{ route('installations.index') }}" class="inst-btn">← Tagasi</a>
            </div>
        </div>

        <form method="get" action="{{ route('installations.show', $object->id) }}" class="inst-filter">
            <label class="inst-field">
                <span class="inst-label">Seade</span>
                <select name="device" class="inst-input">
                    <option value="">Kõik</option>
                    @foreach(\App\Models\ObjectTask::DEVICE_TYPES as $value => $label)
                        <option value="{{ $value }}" @selected($filters['device'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="inst-field">
                <span class="inst-label">Alates</span>
                <input type="date" name="from" value="{{ $filters['from'] }}" class="inst-input">
            </label>
            <label class="inst-field">
                <span class="inst-label">Kuni</span>
                <input type="date" name="to" value="{{ $filters['to'] }}" class="inst-input">
            </label>
            <button type="submit" class="inst-btn">Filtreeri</button>
            @if($query)
                <a href="{{ route('installations.show', $object->id) }}" class="inst-btn">Lähtesta</a>
            @endif
        </form>
    </div>

    <div class="card">
        <div class="inst-overview">
            <div>
                <h3>Edenemine</h3>
                @forelse($progress as $item)
                    @include('installations._progress', ['progress' => $item])
                @empty
                    <div class="inst-muted">Ülesandeid ega aruandeid pole.</div>
                @endforelse
                @if($flatCount === 0)
                    <p class="inst-muted" style="margin-bottom:0;">Objektil pole korterite nimekirja, seega protsenti ei arvutata. Nimekirja saab lisada SOE lehel.</p>
                @endif
            </div>
            <div>
                <h3>Päevade kaupa</h3>
                @php($maxDay = max(1, $byDay->max() ?? 1))
                <div class="inst-days">
                    @forelse($byDay as $day => $count)
                        <div class="inst-day">
                            <span class="inst-mono">{{ \Illuminate\Support\Carbon::parse($day)->format('d.m.Y') }}</span>
                            <div class="inst-day-bar" style="width: {{ max(4, round($count * 100 / $maxDay)) }}%"></div>
                            <span class="inst-day-count">{{ $count }}</span>
                        </div>
                    @empty
                        <div class="inst-muted">—</div>
                    @endforelse
                </div>
            </div>
        </div>

        @if($missing->isNotEmpty())
            <h3 style="margin-top: 22px;">Aruandeta korterid ({{ $missing->count() }})</h3>
            <div class="inst-missing">
                @foreach($missing as $location)
                    <span>{{ $location }}</span>
                @endforeach
            </div>
        @endif
    </div>

    <div class="card">
        <div class="inst-head" style="margin-bottom: 14px;">
            <h3 style="margin:0;">Korterid</h3>
            @if($apartments->isNotEmpty())
                <button type="button" class="inst-btn" id="instToggleAll">Ava kõik</button>
            @endif
        </div>

        @forelse($apartments as $reports)
            <details class="inst-apartment">
                <summary>
                    <span class="inst-apartment-name">Krt {{ $reports->first()->apartment ?: '—' }}</span>
                    @foreach($reports->groupBy('device_type') as $device => $deviceReports)
                        <span class="task-badge task-badge-{{ $device }}">{{ \App\Models\ObjectTask::DEVICE_TYPES[$device] ?? $device }} × {{ $deviceReports->count() }}</span>
                    @endforeach
                    <span class="inst-mono" style="margin-left:auto;">{{ $reports->last()->created_at?->format('d.m.Y H:i') }}</span>
                </summary>
                <div class="inst-apartment-body">
                    @foreach($reports as $report)
                        @include('installations._report', ['report' => $report])
                    @endforeach
                </div>
            </details>
        @empty
            <div class="inst-empty">Aruandeid pole{{ $query ? ' (filtriga)' : '' }}</div>
        @endforelse
    </div>
@endsection

@section('extra-scripts')
    <script>
        (function () {
            var button = document.getElementById('instToggleAll');
            if (!button) return;
            button.addEventListener('click', function () {
                var items = document.querySelectorAll('.inst-apartment');
                var open = Array.prototype.some.call(items, function (item) { return !item.open; });
                items.forEach(function (item) { item.open = open; });
                button.textContent = open ? 'Sulge kõik' : 'Ava kõik';
            });
        })();
    </script>
@endsection
