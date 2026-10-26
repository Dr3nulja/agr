@extends('layouts.app')

@section('title', 'Seade ' . $device['devid'] . ' - AGR')

@section('extra-styles')
    <style>
        .card { padding: 22px 24px; border-radius: 18px; background: var(--surface); box-shadow: 0 2px 10px -2px oklch(0 0 0 / 0.06); margin-bottom: 20px; }
        h2 { margin: 0 0 4px; font-weight: 500; }
        h3 { margin: 0 0 12px; font-weight: 500; }
        .rd-muted { color: var(--text-muted); font-size: 0.9rem; }
        .rd-mono { font-family: 'IBM Plex Mono', ui-monospace, monospace; }
        .rd-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 999px; border: 1px solid var(--border); background: var(--bg); color: var(--text); font: inherit; font-size: 0.85rem; text-decoration: none; cursor: pointer; white-space: nowrap; }
        .rd-btn:hover { border-color: var(--primary); }
        .rd-btn-primary { background: var(--primary-grad); color: white; border-color: transparent; }
        .rd-btn-danger { color: var(--danger); }
        .rd-field { display: flex; flex-direction: column; gap: 4px; }
        .rd-label { font-size: 0.75rem; font-weight: 500; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.03em; }
        .rd-input { padding: 8px 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface); color: var(--text); font: inherit; font-size: 0.9rem; }
        .rd-form { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
        .rd-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 20px; }
        .rd-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        .rd-table th, .rd-table td { padding: 7px 8px; border-bottom: 1px solid var(--border); text-align: left; vertical-align: middle; }
        .rd-table th { font-weight: 500; color: var(--text-muted); }
        .rd-table .rd-input { padding: 5px 8px; }
        .rd-badge { font-size: 0.75rem; padding: 2px 8px; border-radius: 999px; background: oklch(0.95 0.05 65); color: oklch(0.45 0.12 65); }
        @media (max-width: 900px) { .rd-grid { grid-template-columns: 1fr; } }
    </style>
@endsection

@section('content')
    @include('partials.flash')

    @php
        $isAdmin = session('user')->role == 1;
        $backUrl = $back && str_starts_with($back, url('/')) ? $back : route('readings.index', $object->id);
        $actionLabels = ['history_add' => 'Lisatud näit', 'history_edit' => 'Muudetud näit', 'history_delete' => 'Kustutatud näit', 'current_edit' => 'Muudetud praegune'];
    @endphp

    <div class="card">
        <div style="display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
            <div>
                <h2>Seade <span class="rd-mono">{{ $device['devid'] }}</span></h2>
                <div class="rd-muted">{{ $object->address }}, {{ $object->City }} · korter {{ $device['location'] ?: '—' }} · {{ $device['type_label'] }}</div>
            </div>
            <a href="{{ $backUrl }}" class="rd-btn">← Näidud</a>
        </div>
    </div>

    <div class="rd-grid">
        <div class="card">
            <h3>Praegused väärtused</h3>
            <p class="rd-muted" style="margin-top: 0;">Nende järgi arvutatakse aruannetes kuu kulu (lõppnäit − algnäit).</p>
            @if($isAdmin)
                <form method="post" action="{{ route('readings.current.update', [$object->id, $device['devid']]) }}" class="rd-form" onsubmit="return confirm('Salvestada praegused väärtused?');">
                    @csrf
                    @method('PUT')
                    <label class="rd-field"><span class="rd-label">Kuupäev</span><input type="datetime-local" name="date" value="{{ $device['date'] ? date('Y-m-d\TH:i', strtotime($device['date'])) : '' }}" class="rd-input" required></label>
                    <label class="rd-field"><span class="rd-label">Näit</span><input type="number" step="0.001" min="0" name="value" value="{{ $device['value'] }}" class="rd-input" required style="width: 120px;"></label>
                    <label class="rd-field"><span class="rd-label">Kuu lõppnäit</span><input type="number" step="0.001" min="0" name="mvalue" value="{{ $device['mvalue'] }}" class="rd-input" style="width: 120px;"></label>
                    <label class="rd-field"><span class="rd-label">Kuu algnäit</span><input type="number" step="0.001" min="0" name="prev_value" value="{{ $device['prev_value'] }}" class="rd-input" style="width: 120px;"></label>
                    <button type="submit" class="rd-btn rd-btn-primary">Salvesta</button>
                </form>
            @else
                <table class="rd-table">
                    <tr><th>Kuupäev</th><td>{{ $device['date'] ? date('d.m.Y H:i', strtotime($device['date'])) : '—' }}</td></tr>
                    <tr><th>Näit</th><td>{{ $device['value'] ?? '—' }}</td></tr>
                    <tr><th>Kuu lõppnäit</th><td>{{ $device['mvalue'] ?? '—' }}</td></tr>
                    <tr><th>Kuu algnäit</th><td>{{ $device['prev_value'] ?? '—' }}</td></tr>
                </table>
            @endif

            @if($isAdmin)
                <h3 style="margin-top: 24px;">Lisa näit käsitsi</h3>
                <p class="rd-muted" style="margin-top: 0;">Kui seade ei saatnud andmeid. Uuem kui praegune näit uuendab ka praegust.</p>
                <form method="post" action="{{ route('readings.history.store', [$object->id, $device['devid']]) }}" class="rd-form">
                    @csrf
                    <label class="rd-field"><span class="rd-label">Kuupäev</span><input type="datetime-local" name="date" value="{{ old('date', now()->format('Y-m-d\TH:i')) }}" class="rd-input" required></label>
                    <label class="rd-field"><span class="rd-label">Näit</span><input type="number" step="0.001" min="0" name="value" value="{{ old('value') }}" class="rd-input" required style="width: 120px;"></label>
                    <button type="submit" class="rd-btn rd-btn-primary">➕ Lisa</button>
                </form>
            @endif

            @if($corrections->isNotEmpty())
                <h3 style="margin-top: 24px;">Parandused</h3>
                <table class="rd-table">
                    @foreach($corrections as $correction)
                        @php
                            $old = json_decode($correction->old_values ?? 'null', true);
                            $new = json_decode($correction->new_values ?? 'null', true);
                            $describe = function ($values) use ($device) {
                                if (! $values) return '—';
                                $parts = [];
                                if (isset($values['date'])) $parts[] = date('d.m.Y H:i', strtotime($values['date']));
                                foreach (['value' => '', 'mvalue' => 'kuu ', 'prevVal' => 'alg '] as $key => $prefix) {
                                    if (array_key_exists($key, $values) && $values[$key] !== null) $parts[] = $prefix . rtrim(rtrim(number_format($values[$key] / $device['scale'], 3, '.', ''), '0'), '.');
                                }
                                return implode(' · ', $parts);
                            };
                        @endphp
                        <tr>
                            <td class="rd-muted">{{ date('d.m.Y H:i', strtotime($correction->created_at)) }}<br>{{ $correction->user }}</td>
                            <td>{{ $actionLabels[$correction->action] ?? $correction->action }}</td>
                            <td class="rd-mono" style="font-size: 0.8rem;">{{ $describe($old) }} → {{ $describe($new) }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </div>

        <div class="card">
            <h3>Ajalugu ({{ count($history) }}{{ count($history) >= 200 ? ', viimased' : '' }})</h3>
            @if($history === [])
                <div class="rd-muted">Ajalugu puudub.</div>
            @else
                <table class="rd-table">
                    <thead><tr><th>Kuupäev</th><th>Näit</th><th></th></tr></thead>
                    <tbody>
                        @foreach($history as $row)
                            <tr>
                                @if($isAdmin)
                                    <td colspan="2">
                                        <form method="post" action="{{ route('readings.history.update', [$object->id, $device['devid'], $row['id']]) }}" class="rd-form" style="gap: 6px; flex-wrap: nowrap;">
                                            @csrf
                                            @method('PUT')
                                            <input type="datetime-local" name="date" value="{{ date('Y-m-d\TH:i', strtotime($row['date'])) }}" class="rd-input" required>
                                            <input type="number" step="0.001" min="0" name="value" value="{{ $row['value'] }}" class="rd-input" required style="width: 110px;">
                                            <button type="submit" class="rd-btn" title="Salvesta">💾</button>
                                            @if($row['corrected'])<span class="rd-badge">käsitsi</span>@endif
                                        </form>
                                    </td>
                                    <td>
                                        <form method="post" action="{{ route('readings.history.destroy', [$object->id, $device['devid'], $row['id']]) }}" onsubmit="return confirm('Kustutada see näit ajaloost?');" style="margin: 0;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rd-btn rd-btn-danger" title="Kustuta">✕</button>
                                        </form>
                                    </td>
                                @else
                                    <td>{{ date('d.m.Y H:i', strtotime($row['date'])) }}</td>
                                    <td class="rd-mono">{{ number_format($row['value'], 3, '.', ' ') }}</td>
                                    <td>@if($row['corrected'])<span class="rd-badge">käsitsi</span>@endif</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection
