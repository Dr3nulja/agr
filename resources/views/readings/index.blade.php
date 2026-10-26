@extends('layouts.app')

@section('title', 'Näidud: ' . $object->address . ' - AGR')

@section('extra-styles')
    <style>
        .card { padding: 22px 24px; border-radius: 18px; background: var(--surface); box-shadow: 0 2px 10px -2px oklch(0 0 0 / 0.06); margin-bottom: 20px; }
        h2 { margin: 0 0 4px; font-weight: 500; }
        h3 { margin: 0 0 14px; font-weight: 500; }
        .rd-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; }
        .rd-muted { color: var(--text-muted); font-size: 0.9rem; }
        .rd-mono { font-family: 'IBM Plex Mono', ui-monospace, monospace; }
        .rd-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .rd-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 999px; border: 1px solid var(--border); background: var(--bg); color: var(--text); font: inherit; font-size: 0.9rem; text-decoration: none; cursor: pointer; white-space: nowrap; }
        .rd-btn:hover { border-color: var(--primary); }
        .rd-btn-primary { background: var(--primary-grad); color: white; border-color: transparent; }
        .rd-filter { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--border); }
        .rd-field { display: flex; flex-direction: column; gap: 4px; }
        .rd-label { font-size: 0.75rem; font-weight: 500; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.03em; }
        .rd-input { padding: 9px 12px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface); color: var(--text); font: inherit; }
        .rd-charts { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 20px; }
        .rd-table-wrap { overflow-x: auto; }
        .rd-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        .rd-table th, .rd-table td { padding: 8px 10px; border-bottom: 1px solid var(--border); white-space: nowrap; }
        .rd-table th { background: oklch(0.97 0.006 270); text-align: left; font-weight: 500; position: sticky; top: 0; }
        .rd-table td.num, .rd-table th.num { text-align: right; font-family: 'IBM Plex Mono', ui-monospace, monospace; }
        .rd-table tbody tr:hover { background: oklch(0.97 0.006 270); }
        .rd-type-water { color: oklch(0.5 0.17 250); }
        .rd-type-hot { color: oklch(0.55 0.19 25); }
        .rd-type-heat { color: oklch(0.55 0.15 60); }
        .rd-empty { padding: 20px; text-align: center; background: var(--bg); border: 2px dashed var(--border); border-radius: 8px; color: var(--text-muted); }
        .rd-snapshot { padding: 10px 14px; border-radius: 10px; background: oklch(0.95 0.04 254); color: oklch(0.4 0.12 254); margin-bottom: 16px; }
        .rd-fixed { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
        .rd-fixed a { padding: 5px 12px; border-radius: 999px; border: 1px solid var(--border); text-decoration: none; color: var(--text); font-size: 0.85rem; }
        .rd-fixed a.active { border-color: var(--primary); color: var(--primary); }
    </style>
@endsection

@section('content')
    @include('partials.flash')

    @php
        $isAdmin = session('user')->role == 1;
        $query = array_filter($filters, fn ($value) => $value !== null && $value !== '');
        $units = [\App\Services\ReadingsService::KIND_WATER => 'm³', \App\Services\ReadingsService::KIND_HEAT => 'ühikut'];
        $colors = [\App\Services\ReadingsService::KIND_WATER => '#3b6fe0', \App\Services\ReadingsService::KIND_HEAT => '#e08a2c'];
    @endphp

    <div class="card">
        <div class="rd-head">
            <div>
                <h2>📈 Näidud — {{ $object->address }}</h2>
                <div class="rd-muted">{{ $object->City }} · veemõõtjate ja jaoturite näidud ning kulu kuude kaupa</div>
            </div>
            <div class="rd-actions">
                <a href="{{ route('readings.xlsx', [$object->id] + $query) }}" class="rd-btn">📥 Excel</a>
                <a href="{{ route('readings.print', [$object->id] + $query) }}" class="rd-btn" target="_blank">🖨 PDF</a>
                <a href="{{ route('objects.show', $object->id) }}" class="rd-btn">← Objekt</a>
            </div>
        </div>

        <form method="get" action="{{ route('readings.index', $object->id) }}" class="rd-filter">
            <label class="rd-field">
                <span class="rd-label">Seadmed</span>
                <select name="kind" class="rd-input">
                    <option value="">Kõik</option>
                    @foreach(\App\Services\ReadingsService::KINDS as $kind => $label)
                        <option value="{{ $kind }}" @selected($filters['kind'] === $kind)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="rd-field">
                <span class="rd-label">Korter</span>
                <select name="location" class="rd-input">
                    <option value="">Kõik korterid</option>
                    @foreach($locations as $location)
                        <option value="{{ $location }}" @selected($filters['location'] === (string) $location)>{{ $location }}</option>
                    @endforeach
                </select>
            </label>
            <label class="rd-field">
                <span class="rd-label">Alates</span>
                <input type="month" name="from" value="{{ $filters['from'] }}" class="rd-input">
            </label>
            <label class="rd-field">
                <span class="rd-label">Kuni</span>
                <input type="month" name="to" value="{{ $filters['to'] }}" class="rd-input">
            </label>
            @if($fixedPeriods)
                <label class="rd-field">
                    <span class="rd-label">Näidud</span>
                    <select name="snapshot" class="rd-input">
                        <option value="">Praegused</option>
                        @foreach($fixedPeriods as $fixed)
                            <option value="{{ $fixed['period'] }}" @selected($filters['snapshot'] === $fixed['period'])>Fikseeritud {{ $fixed['period'] }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            <button type="submit" class="rd-btn rd-btn-primary">Näita</button>
        </form>
    </div>

    @if(! $hasTables)
        <div class="card"><div class="rd-empty">Objektil pole seadmete tabeleid (object_{{ $object->id }}, lastdata_{{ $object->id }}, mlog_{{ $object->id }}).</div></div>
    @else
        <div class="card">
            <h3>Kulu kuude kaupa</h3>
            @if($totals === [])
                <div class="rd-empty">Valitud filtritega seadmeid pole.</div>
            @else
                <div class="rd-charts">
                    @foreach($totals as $kind => $series)
                        <div>
                            <div class="rd-muted" style="margin-bottom: 6px;">{{ \App\Services\ReadingsService::KINDS[$kind] }}{{ $filters['location'] !== '' ? ' · korter ' . $filters['location'] : '' }}</div>
                            @include('readings._chart', ['series' => $series, 'unit' => $units[$kind], 'color' => $colors[$kind], 'label' => \App\Services\ReadingsService::KINDS[$kind]])
                        </div>
                    @endforeach
                </div>
                <p class="rd-muted" style="margin: 10px 0 0;">Kuu kulu = kuu viimane näit − eelmise kuu viimane näit (ajaloost mlog_{{ $object->id }}).</p>
            @endif
        </div>

        <div class="card">
            @if($filters['snapshot'])
                <div class="rd-snapshot">Näidatakse {{ $filters['snapshot'] }} fikseeritud näite. Kulu kuude kaupa arvutatakse ikka ajaloost.</div>
            @endif
            <div class="rd-head" style="margin-bottom: 12px;">
                <h3 style="margin: 0;">Seadmed ({{ count($devices) }})</h3>
                <input id="rdSearch" type="search" class="rd-input" placeholder="🔍 Korter / seadme nr" style="width: 220px;">
            </div>

            @if($devices === [])
                <div class="rd-empty">Valitud filtritega seadmeid pole.</div>
            @else
                <div class="rd-table-wrap">
                    <table class="rd-table">
                        <thead>
                            <tr>
                                <th>Korter</th>
                                <th>Seadme nr</th>
                                <th>Tüüp</th>
                                <th class="num">Viimane näit</th>
                                <th>Kuupäev</th>
                                @foreach($periods as $period)
                                    <th class="num">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $period . '-01')->format('m.Y') }}</th>
                                @endforeach
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="rdBody">
                            @foreach($devices as $device)
                                @php
                                    $typeClass = $device['kind'] === 'heat' ? 'rd-type-heat' : (str_starts_with($device['type_label'], 'soe') ? 'rd-type-hot' : 'rd-type-water');
                                @endphp
                                <tr>
                                    <td>{{ $device['location'] ?: '—' }}</td>
                                    <td class="rd-mono">{{ $device['devid'] }}</td>
                                    <td class="{{ $typeClass }}">{{ $device['type_label'] }}</td>
                                    <td class="num">{{ $device['value'] !== null ? number_format($device['value'], 3, '.', ' ') : '—' }}</td>
                                    <td>{{ $device['date'] ? date('d.m.Y', strtotime($device['date'])) : '—' }}</td>
                                    @foreach($periods as $period)
                                        @php $consumption = $monthly[$device['devid']][$period]['consumption'] ?? null; @endphp
                                        <td class="num">{{ $consumption !== null ? number_format($consumption, 3, '.', ' ') : '·' }}</td>
                                    @endforeach
                                    <td><a href="{{ route('readings.device', [$object->id, $device['devid'], 'back' => request()->fullUrl()]) }}" class="rd-btn" style="padding: 4px 12px; font-size: 0.8rem;">{{ $isAdmin ? '✏️ Ajalugu' : 'Ajalugu' }}</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if($isAdmin)
            <div class="card">
                <h3>📌 Kuu näitude fikseerimine</h3>
                <p class="rd-muted" style="margin-top: 0;">Salvestab kõigi seadmete praegused näidud eraldi tabelisse. Hilisemad impordid ja parandused fikseeritud kuud ei muuda. Sama kuu uuesti fikseerimine kirjutab selle üle.</p>
                <form method="post" action="{{ route('readings.fix-month', $object->id) }}" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;" onsubmit="return confirm('Fikseerida ' + this.period.value + ' näidud?' + ({{ json_encode(array_column($fixedPeriods, 'period')) }}.includes(this.period.value) ? ' See kuu on juba fikseeritud ja kirjutatakse üle.' : ''));">
                    @csrf
                    <label class="rd-field">
                        <span class="rd-label">Kuu</span>
                        <input type="month" name="period" value="{{ now()->subMonthNoOverflow()->format('Y-m') }}" class="rd-input" required>
                    </label>
                    <button type="submit" class="rd-btn rd-btn-primary">📌 Fikseeri</button>
                </form>

                @if($fixedPeriods)
                    <div class="rd-fixed">
                        @foreach($fixedPeriods as $fixed)
                            <a href="{{ route('readings.index', [$object->id, 'snapshot' => $fixed['period']] + \Illuminate\Support\Arr::except($query, 'snapshot')) }}" class="{{ $filters['snapshot'] === $fixed['period'] ? 'active' : '' }}" title="{{ $fixed['count'] }} seadet · {{ $fixed['fixed_at'] ? date('d.m.Y H:i', strtotime($fixed['fixed_at'])) : '' }} {{ $fixed['fixed_by'] }}">
                                {{ $fixed['period'] }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    @endif
@endsection

@section('extra-scripts')
    <script>
        var rdSearch = document.getElementById('rdSearch');
        if (rdSearch) {
            rdSearch.addEventListener('input', function () {
                var query = this.value.trim().toLowerCase();
                document.querySelectorAll('#rdBody tr').forEach(function (row) {
                    var text = row.cells[0].textContent + ' ' + row.cells[1].textContent;
                    row.style.display = text.toLowerCase().indexOf(query) === -1 ? 'none' : '';
                });
            });
        }
    </script>
@endsection
