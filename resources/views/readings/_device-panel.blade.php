{{--
    Содержимое прибора: график, ошибки, текущие значения, ручной ввод, замена прибора, история.
    На странице readings.device — карточками; в списке readings.index ($inPanel) — подгружается
    при раскрытии строки, формы там отправляются через fetch без перезагрузки списка.
--}}
@php
    $inPanel = $inPanel ?? false;
    $box = $inPanel ? 'rd-box' : 'card';
    $isAdmin = session('user')->role == 1;
    $tone = \App\Services\ReadingsService::tone($device['devtype']);
    $toneColor = ['cold' => '#3b6fe0', 'hot' => '#d9443a', 'heat' => '#e08a2c'][$tone] ?? '#6b7080';
    $unit = $device['kind'] === \App\Services\ReadingsService::KIND_WATER ? 'm³' : 'ühikut';
    $actionLabels = ['history_add' => 'Lisatud näit', 'history_edit' => 'Muudetud näit', 'history_delete' => 'Kustutatud näit', 'current_edit' => 'Muudetud praegune', 'devid_change' => 'Seadme vahetus'];
    $describe = function ($values) use ($device) {
        if (! $values) return '—';
        $parts = [];
        if (isset($values['devid'])) $parts[] = 'nr ' . $values['devid'];
        if (isset($values['date'])) $parts[] = date('d.m.Y H:i', strtotime($values['date']));
        foreach (['value' => '', 'mvalue' => 'kuu ', 'prevVal' => 'alg '] as $key => $prefix) {
            if (array_key_exists($key, $values) && $values[$key] !== null) $parts[] = $prefix . rtrim(rtrim(number_format($values[$key] / $device['scale'], 3, '.', ''), '0'), '.');
        }
        return implode(' · ', $parts);
    };
@endphp

@if($inPanel)
    <div class="rd-panel-bar">
        <div class="rd-panel-msg"></div>
        <a href="{{ route('readings.device', [$object->id, $device['devid'], 'back' => url()->previous()]) }}" class="rd-btn" style="padding: 4px 12px; font-size: 0.8rem;">↗ Ava eraldi lehel</a>
    </div>
@endif

<div class="rd-grid">
    <div class="{{ $box }}">
        <h3>📈 Näitude graafik</h3>
        @include('partials.line-chart', [
            'points' => array_map(fn ($row) => [$row['date'], $row['value']], array_reverse($history)),
            'unit' => $unit,
            'color' => $toneColor,
            'label' => 'Seadme ' . $device['devid'] . ' näidud',
        ])
        @if(count($history) >= 200)
            <p class="rd-muted" style="margin: 8px 0 0;">Viimased 200 näitu.</p>
        @endif
    </div>

    <div class="{{ $box }}">
        <h3>⚠️ Vead ({{ count($problems) }})</h3>
        @if($problems === [])
            <div class="rd-ok">✓ Vigu pole</div>
        @else
            <div class="rd-history" style="max-height: 260px;">
                <table class="rd-dtable">
                    <tbody>
                        @foreach($problems as $problem)
                            <tr>
                                <td class="rd-muted" style="white-space: nowrap;">{{ $problem['date'] ? date('d.m.Y H:i', strtotime($problem['date'])) : '—' }}</td>
                                <td class="rd-problem">{{ $problem['text'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="rd-grid">
    <div class="{{ $box }}">
        <div class="rd-section">
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
                <table class="rd-dtable">
                    <tr><th>Kuupäev</th><td>{{ $device['date'] ? date('d.m.Y H:i', strtotime($device['date'])) : '—' }}</td></tr>
                    <tr><th>Näit</th><td>{{ $device['value'] ?? '—' }}</td></tr>
                    <tr><th>Kuu lõppnäit</th><td>{{ $device['mvalue'] ?? '—' }}</td></tr>
                    <tr><th>Kuu algnäit</th><td>{{ $device['prev_value'] ?? '—' }}</td></tr>
                </table>
            @endif
        </div>

        @if($isAdmin)
            <div class="rd-section">
                <h3>Lisa näit käsitsi</h3>
                <p class="rd-muted" style="margin-top: 0;">Kui seade ei saatnud andmeid. Uuem kui praegune näit uuendab ka praegust.</p>
                <form method="post" action="{{ route('readings.history.store', [$object->id, $device['devid']]) }}" class="rd-form">
                    @csrf
                    <label class="rd-field"><span class="rd-label">Kuupäev</span><input type="datetime-local" name="date" value="{{ $inPanel ? now()->format('Y-m-d\TH:i') : old('date', now()->format('Y-m-d\TH:i')) }}" class="rd-input" required></label>
                    <label class="rd-field"><span class="rd-label">Näit</span><input type="number" step="0.001" min="0" name="value" value="{{ $inPanel ? '' : old('value') }}" class="rd-input" required style="width: 120px;"></label>
                    <button type="submit" class="rd-btn rd-btn-primary">➕ Lisa</button>
                </form>
            </div>

            <div class="rd-section rd-replace">
                <h3>🔁 Seadme vahetus</h3>
                <p class="rd-muted" style="margin-top: 0;">Kui seade läks katki ja paigaldati uus: sama korter ja tüüp, uus number. Vana seadme ajalugu jääb alles numbri {{ $device['devid'] }} all.</p>
                <form method="post" action="{{ route('readings.replace', [$object->id, $device['devid']]) }}" class="rd-form" onsubmit="return confirm('Asendada seade {{ $device['devid'] }} seadmega ' + this.new_devid.value + '?');">
                    @csrf
                    <label class="rd-field"><span class="rd-label">Uus number</span><input type="number" name="new_devid" min="1" step="1" class="rd-input" required style="width: 140px;"></label>
                    <label class="rd-field"><span class="rd-label">Uue seadme algnäit</span><input type="number" step="0.001" min="0" name="start_value" class="rd-input" style="width: 120px;" placeholder="pole kohustuslik"></label>
                    <label class="rd-field"><span class="rd-label">Paigaldatud</span><input type="datetime-local" name="start_date" value="{{ now()->format('Y-m-d\TH:i') }}" class="rd-input"></label>
                    <button type="submit" class="rd-btn rd-btn-primary">🔁 Vaheta</button>
                </form>
            </div>
        @endif

        @if($corrections->isNotEmpty())
            <div class="rd-section">
                <h3>Parandused</h3>
                <table class="rd-dtable">
                    @foreach($corrections as $correction)
                        <tr>
                            <td class="rd-muted">{{ date('d.m.Y H:i', strtotime($correction->created_at)) }}<br>{{ $correction->user }}</td>
                            <td>{{ $actionLabels[$correction->action] ?? $correction->action }}</td>
                            <td class="rd-mono" style="font-size: 0.8rem;">{{ $describe(json_decode($correction->old_values ?? 'null', true)) }} → {{ $describe(json_decode($correction->new_values ?? 'null', true)) }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
    </div>

    <div class="{{ $box }}">
        <h3>🕘 Näitude ajalugu ({{ count($history) }}{{ count($history) >= 200 ? ', viimased' : '' }})</h3>
        @if($history === [])
            <div class="rd-muted">Ajalugu puudub.</div>
        @else
            <div class="rd-history">
                <table class="rd-dtable">
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
            </div>
        @endif
    </div>
</div>
