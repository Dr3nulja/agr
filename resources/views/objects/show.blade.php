@extends('layouts.app')

@section('title', 'Objekt - AGR')

@section('extra-styles')
    <style>
        .panel {
            background: var(--surface);
            border-radius: 18px;
            padding: 20px 22px;
            box-shadow: 0 2px 10px -2px oklch(0 0 0 / 0.06);
            margin-bottom: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }

        .info-box {
            padding: 12px 14px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        .info-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 500;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 500;
            color: var(--text);
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 18px;
            border-radius: 999px;
            border: 0;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            transition: opacity 0.2s;
        }

        .btn-primary {
            background: var(--primary-grad);
            color: white;
            box-shadow: 0 6px 16px -4px oklch(0.62 0.19 270 / 0.4);
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        .btn-secondary {
            background: var(--bg);
            color: var(--text);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--border);
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 500;
            margin-top: 24px;
            margin-bottom: 12px;
            color: var(--text);
        }

        .placeholder {
            padding: 24px;
            text-align: center;
            background: var(--bg);
            border: 2px dashed var(--border);
            border-radius: 8px;
            color: var(--text-muted);
        }

        .device-table th,
        .device-table td,
        .info-table th,
        .info-table td,
        .legacy-table th,
        .legacy-table td {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
        }

        .device-table th,
        .legacy-table th {
            background: rgba(15, 23, 42, 0.04);
            text-align: left;
            font-weight: 500;
        }

        .device-table tbody tr:hover,
        .legacy-table tbody tr:hover {
            background: rgba(15, 23, 42, 0.04);
        }

        .row-error {
            background: rgba(248, 113, 113, 0.1);
        }

        .device-id {
            font-family: 'IBM Plex Mono', ui-monospace, monospace;
            font-weight: 500;
        }

        .device-table th.sortable {
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
        }

        .device-table th.sortable:hover {
            color: var(--primary);
        }

        .check-panel {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .check-steps {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .check-step {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 0.9rem;
            font-weight: 500;
            border: 1px solid var(--border);
            color: var(--text-muted);
            background: var(--bg);
        }

        .check-step.is-water {
            background: oklch(0.94 0.05 250);
            border-color: oklch(0.82 0.09 250);
            color: oklch(0.42 0.15 250);
        }

        .check-step.is-all {
            background: oklch(0.94 0.06 155);
            border-color: oklch(0.8 0.1 155);
            color: oklch(0.4 0.12 155);
        }

        .btn-check-water {
            background: oklch(0.58 0.17 250);
            color: white;
        }

        .btn-check-final {
            background: oklch(0.6 0.15 155);
            color: white;
        }

    </style>
@endsection

@section('content')
    @include('partials.flash')

    <div class="panel" style="padding-bottom: 16px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
            <div>
                <h2 style="margin-bottom: 4px;">{{ $object['address'] }}</h2>
                <div style="color: var(--text-muted);">
                    {{ $object['City'] ?? '—' }} · {{ ['1' => 'Apator', '2' => 'Siemens', '3' => 'Elekter', '4' => 'LoRa'][(string) $object['dtype']] ?? 'tüüp ' . $object['dtype'] }} · IMEI: {{ $object['IMEI'] ?? '—' }}
                </div>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                <a href="{{ route('readings.index', $object['id']) }}" class="btn btn-secondary">📈 Näidud</a>
                @if(session('user')->role == 1)
                    <a href="{{ route('objects.edit', $object['id']) }}" class="btn btn-primary">✏️ Muuda</a>
                    <a href="{{ route('objects.devices.upload', $object['id']) }}" class="btn btn-secondary">📤 Laadi seadmete nimekiri</a>
                    <a href="{{ route('objects.import-csv', $object['id']) }}" class="btn btn-secondary">📄 Impordi CSV</a>
                    <a href="{{ route('objects.export.current', $object['id']) }}" class="btn btn-secondary">📥 Praegused näidud XLSX</a>
                    <a href="{{ route('objects.export.month_start', $object['id']) }}" class="btn btn-secondary">📥 Kuu algnäidud XLSX</a>
                    <a href="{{ route('objects.export.alokator', $object['id']) }}" class="btn btn-secondary">📥 Jaoturid XLSX</a>
                    <a href="{{ route('objects.export.korto', $object['id']) }}" class="btn btn-secondary">📥 Korto CSV</a>
                @endif
                <a href="{{ route('objects.index') }}" class="btn btn-secondary">← Tagasi</a>
            </div>
        </div>

        @if(trim($object['Description'] ?? '') !== '')
            <div style="margin-top: 18px; color: var(--text-muted); line-height: 1.6;">
                {{ $object['Description'] }}
            </div>
        @endif

        <div class="info-grid" style="margin-top: 20px; gap: 14px;">
            <div class="info-box">
                <div class="info-label">Firma</div>
                <div class="info-value">{{ \App\Models\AgrObject::companyDisplayName($object['Company']) }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Staatus</div>
                <div class="info-value">{{ $object['status'] == 1 ? 'Aktiivne' : ($object['status'] == 2 ? 'Mitteaktiivne' : 'Teadmata') }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Haldur</div>
                <div class="info-value">{{ \App\Models\AgrObject::managerName($object['manager']) }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Viimane ühendus</div>
                <div class="info-value">{{ !empty($object['lastSession']) ? date('d.m.Y H:i', strtotime($object['lastSession'])) : '—' }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Püsivara versioon</div>
                <div class="info-value">{{ $object['ver'] ?? '—' }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Seadmeid</div>
                <div class="info-value">{{ count($devices) }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Paigaldusridu</div>
                <div class="info-value">{{ count($installData) }}</div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="check-panel">
            <div>
                <h3 class="section-title" style="margin-top: 0;">✅ Andmete esitamine</h3>
                <div class="check-steps">
                    @if($model->selDate)
                        @if($model->water_checked_at && $model->heat_checked_at)
                            <span class="check-step is-water">💧 Vesi {{ $model->water_checked_at->format('d.m.Y H:i') }}</span>
                        @endif
                        <span class="check-step is-all">✓ Kõik esitatud {{ $model->selDate->format('d.m.Y H:i') }}</span>
                    @elseif($model->water_checked_at)
                        <span class="check-step is-water">💧 Vesi esitatud {{ $model->water_checked_at->format('d.m.Y H:i') }}</span>
                        <span class="check-step">🔥 Soojus ootab</span>
                    @else
                        <span class="check-step">Esitamata</span>
                    @endif
                </div>
                <div style="color: var(--text-muted); font-size: 0.85rem; margin-top: 8px;">
                    Aruannete e-post: {{ $model->report_emails ?: '— (lisa objekti muutmise vormis)' }}
                </div>
            </div>
            @if(session('user')->role == 1)
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    @if($nextCheck)
                        @php
                            $stepLabel = $nextCheck === 'water' ? 'vesi' : 'soojus';
                            $checkClass = $nextCheckIsFinal ? 'btn-check-final' : 'btn-check-water';
                        @endphp
                        @if($model->report_emails)
                            <form method="post" action="{{ route('objects.send-report', $object['id']) }}" style="margin: 0;" onsubmit="return confirm('Saata {{ $stepLabel === 'vesi' ? 'veemõõtjate' : 'jaoturite' }} aruanne objekti e-posti aadressidele ja märkida {{ $stepLabel }} esitatuks?');">
                                @csrf
                                <button type="submit" class="btn {{ $checkClass }}">📧 Saada ({{ $stepLabel }}) + märgi esitatuks</button>
                            </form>
                        @endif
                        <form method="post" action="{{ route('objects.check', $object['id']) }}" style="margin: 0;" onsubmit="return confirm('Märkida {{ $stepLabel }} esitatuks ilma saatmata?');">
                            @csrf
                            <button type="submit" class="btn {{ $model->report_emails ? 'btn-secondary' : $checkClass }}">✓ Märgi esitatuks: {{ $stepLabel }}{{ $nextCheckIsFinal ? ' (kõik)' : '' }}</button>
                        </form>
                    @endif
                    @if($model->selDate || $model->water_checked_at)
                        <form method="post" action="{{ route('objects.uncheck', $object['id']) }}" style="margin: 0;" onsubmit="return confirm('Eemaldada märked?');">
                            @csrf
                            <button type="submit" class="btn btn-secondary">↩ Eemalda märked</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>

    @if(session('user')->role == 1)
        <div class="panel">
            <h3 class="section-title">📡 Saada käsk</h3>
            <form method="post" action="{{ route('objects.command', $object['id']) }}" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                @csrf
                <select name="command" required style="padding:10px 12px; border-radius:8px; border:1px solid var(--border); background:var(--bg); color:var(--text);">
                    @foreach($commands as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary">Saada</button>
            </form>
        </div>
    @endif

    <div class="panel">
        <h3 class="section-title">📋 Ülesanded rakendusele</h3>
        <div style="color: var(--text-muted); margin-bottom: 12px;">
            Paigaldajad näevad neid ülesandeid QRScannerApp'i avaekraanil. Ilma ülesanneteta objekti rakenduses ei kuvata.
        </div>

        @include('tasks._list', ['tasks' => $tasks])
        @include('tasks._form')
    </div>

    <div class="panel">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <h3 class="section-title">📱 Aruanded rakendusest ({{ $mobileReports->count() }})</h3>
            <a href="{{ route('installations.show', $object['id']) }}" class="btn btn-secondary">🔧 Paigalduse ülevaade, PDF / Excel →</a>
        </div>
        @include('mobile-reports._list')
    </div>

    <div class="panel">
        <h3 class="section-title">🧾 Objekti andmed</h3>
        <div class="info-grid" style="margin-top: 14px; gap: 16px;">
            <div class="info-box">
                <div class="info-label">Objekti ID</div>
                <div class="info-value">{{ $object['id'] }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">IMEI</div>
                <div class="info-value">{{ $object['IMEI'] ?? '—' }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">IMEI2</div>
                <div class="info-value">{{ $object['IMEI2'] ?? '—' }}</div>
            </div>
            @foreach(\App\Models\SimCard::SLOTS as $slot => $slotLabel)
                <div class="info-box">
                    <div class="info-label">{{ $slotLabel }}</div>
                    <div class="info-value">
                        @if($sim = $simCards->get($slot))
                            <a href="{{ route('sims.index', ['q' => $sim->number ?: $sim->iccid]) }}" style="color: var(--primary); text-decoration: none;">{{ $sim->number ?: '—' }}</a>
                            @if($sim->iccid)
                                <div style="font-size: 0.8rem; color: var(--text-muted); font-family: 'IBM Plex Mono', ui-monospace, monospace;">{{ $sim->iccid }}</div>
                            @endif
                            @if($sim->issued_to)
                                <div style="font-size: 0.8rem; color: var(--text-muted);">paigaldas {{ $sim->issued_to }}</div>
                            @endif
                        @else
                            —
                        @endif
                    </div>
                </div>
            @endforeach
            <div class="info-box">
                <div class="info-label">Kontakt</div>
                <div class="info-value">{{ $object['Contact'] ?? '—' }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Summa</div>
                <div class="info-value">{{ $object['summ'] ?? 0 }}</div>
            </div>
        </div>

        {{-- Редко нужные поля: по умолчанию скрыты --}}
        <div id="fullInfoPanel" hidden>
        <div class="info-grid" style="margin-top: 16px; gap: 16px;">
            <div class="info-box">
                <div class="info-label">Pakett</div>
                <div class="info-value">{{ $object['packet'] ?? '—' }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Liiklus</div>
                <div class="info-value">{{ $object['traffic'] ?? '—' }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Kõnede arv</div>
                <div class="info-value">{{ $object['callCnt'] ?? 0 }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">CSQ kirjeid</div>
                <div class="info-value">{{ count($csqLogs) }}</div>
            </div>
        </div>
        </div>

        <button type="button" id="fullInfoToggle" class="btn btn-secondary" style="margin-top: 16px; padding: 8px 18px; font-size: 0.85rem;" aria-expanded="false" aria-controls="fullInfoPanel">
            ▾ Näita kõiki andmeid
        </button>
    </div>

    <div class="panel">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom: 18px;">
            <h3 class="section-title" style="margin:0;">Seadmete näidud</h3>
            <input id="deviceSearch" type="search" placeholder="Otsi ID / korteri / kuupäeva järgi" style="padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border); width: 260px;" />
        </div>

        @if(count($devices) > 0)
            <div style="overflow-x:auto;">
                <table class="device-table" style="width:100%; border-collapse: collapse; min-width: 1020px;">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th class="sortable" data-sort="location">Korter <span class="sort-mark"></span></th>
                            <th class="sortable" data-sort="devid">ID <span class="sort-mark"></span></th>
                            <th>Tüüp</th>
                            <th style="text-align:right;">Eelmine</th>
                            <th style="text-align:right;">Praegune</th>
                            <th style="text-align:right;">Vahe</th>
                            <th class="sortable" data-sort="date" title="Sorteeri kuupäeva järgi">Kuupäev <span class="sort-mark"></span></th>
                            <th>Staatus</th>
                        </tr>
                    </thead>
                    <tbody id="deviceTableBody">
                        @foreach($devices as $device)
                            @php
                                $prev = $device['prev_mvalue'] ?? 0;
                                $curr = $device['mvalue'] ?? 0;
                                $delta = $curr - $prev;
                            @endphp
                            <tr class="{{ $device['err_class'] ? 'row-error' : '' }} device-row {{ $loop->iteration > 25 ? 'row-limited' : '' }}" data-date="{{ $device['timestamp'] }}" data-devid="{{ $device['devid'] }}" data-location="{{ $device['location'] }}">
                                <td class="row-number">{{ $loop->iteration }}</td>
                                <td>{{ $device['location'] ?? '-' }}</td>
                                <td class="device-id">{{ $device['devid'] }}</td>
                                <td>{{ $device['devtype'] === 1 ? 'külm' : ($device['devtype'] === 2 ? 'soe' : ($device['devtype'] === 3 ? 'jaotur' : 'tüüp ' . $device['devtype'])) }}</td>
                                <td style="text-align:right;">{{ number_format($prev, 3, '.', '') }}</td>
                                <td style="text-align:right;">{{ number_format($curr, 3, '.', '') }}</td>
                                <td style="text-align:right;">{{ number_format($delta, 3, '.', '') }}</td>
                                <td>{{ $device['date'] }}</td>
                                <td style="text-align:center;">{{ $device['err_class'] ? '⚠️' : 'OK' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if(count($devices) > 25)
                <div style="text-align:center; margin-top:14px;">
                    <button type="button" id="deviceShowMoreBtn" class="btn btn-secondary" style="padding: 8px 18px; font-size: 0.85rem;">
                        Näita kõiki {{ count($devices) }} seadet
                    </button>
                </div>
            @endif

            <div class="panel" style="margin-top: 10px;">
                <button type="button" id="legacyDetailsToggle" class="btn btn-secondary" style="padding: 8px 18px; font-size: 0.85rem;">
                    🧾 Seadmete tehnilised andmed ({{ count($devices) }})
                </button>
                <div id="legacyDetailsPanel" style="display:none; margin-top: 16px; overflow-x:auto;">
                    <table class="legacy-table" style="width:100%; border-collapse: collapse; min-width: 1020px;">
                        <thead>
                            <tr>
                                <th>№</th>
                                <th>ID</th>
                                <th>mvalue</th>
                                <th>t1</th>
                                <th>t2</th>
                                <th>viga</th>
                                <th>vea kuupäev</th>
                                <th>viimati lisatud</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($devices as $device)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $device['devid'] }}</td>
                                    <td style="text-align:right;">{{ isset($device['mvalue']) ? number_format($device['mvalue'], 3, '.', '') : '0.000' }}</td>
                                    <td style="text-align:right;">{{ isset($device['t1']) ? number_format($device['t1'], 3, '.', '') : '0.000' }}</td>
                                    <td style="text-align:right;">{{ isset($device['t2']) ? number_format($device['t2'], 3, '.', '') : '0.000' }}</td>
                                    <td style="text-align:center;">{{ $device['ecode'] ?? '—' }}</td>
                                    <td>{{ $device['etime'] ?? '—' }}</td>
                                    <td>{{ $device['rtime'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="placeholder">Objektil pole seadmeid.</div>
        @endif
    </div>

    <div class="panel">
        <h3 class="section-title">📌 Kokkuvõte</h3>
        <div class="info-grid">
            <div class="info-box">
                <div class="info-label">Vee vead</div>
                <div class="info-value">{{ $legacyErrorCounts['water_errors'] ?? 0 }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Soojuse vead</div>
                <div class="info-value">{{ $legacyErrorCounts['heat_errors'] ?? 0 }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Seadmete vead</div>
                <div class="info-value">{{ $legacyErrorCounts['errors'] ?? 0 }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Paigaldusridu</div>
                <div class="info-value">{{ count($installData) }}</div>
            </div>
        </div>
    </div>

    <div class="panel">
        <h3 class="section-title">📦 Paigaldusandmed</h3>
        @if(count($installData) > 0)
            <div style="overflow-x:auto;">
                <table class="info-table" style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Asukoht</th>
                            <th>Kommentaar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($installData as $row)
                            <tr>
                                <td>{{ $row['id'] }}</td>
                                <td>{{ $row['location'] ?? '—' }}</td>
                                <td>{{ $row['comment'] ?? ($row['description'] ?? '—') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="placeholder">Paigaldusandmeid pole.</div>
        @endif
    </div>

    <div class="panel">
        <h3 class="section-title">📶 Signaali tugevus (CSQ)</h3>
        @if(count($csqLogs) > 0)
            <div style="overflow-x:auto;">
                <table class="info-table" style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th>Kuupäev</th>
                            <th style="text-align:center;">CSQ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($csqLogs as $csq)
                            <tr>
                                <td>{{ $csq['date'] ?? '—' }}</td>
                                <td style="text-align:center;">{{ $csq['csq'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="placeholder">CSQ kirjeid pole.</div>
        @endif
    </div>
@endsection

@section('extra-scripts')
    <script>
        const deviceSearch = document.getElementById('deviceSearch');
        const deviceBody = document.getElementById('deviceTableBody');
        let deviceRows = document.querySelectorAll('#deviceTableBody tr');
        const showMoreBtn = document.getElementById('deviceShowMoreBtn');
        let showingAll = false;

        // Сортировка View Records: клик по Korter / ID / Date, повторный клик — обратный порядок.
        // После сортировки первые 25 строк — снова видимые, остальные под «Show all».
        const sortState = { key: null, dir: 1 };
        document.querySelectorAll('.device-table th.sortable').forEach(th => {
            th.addEventListener('click', function () {
                const key = this.dataset.sort;
                sortState.dir = sortState.key === key ? -sortState.dir : (key === 'date' ? -1 : 1);
                sortState.key = key;

                const rows = Array.from(deviceBody.rows);
                rows.sort((a, b) => {
                    const x = a.dataset[key] || '';
                    const y = b.dataset[key] || '';
                    const cmp = key === 'location'
                        ? x.localeCompare(y, undefined, { numeric: true })
                        : (Number(x) - Number(y));
                    return cmp * sortState.dir;
                });
                rows.forEach((row, index) => {
                    deviceBody.appendChild(row);
                    row.querySelector('.row-number').textContent = index + 1;
                    row.classList.toggle('row-limited', index >= 25);
                    row.style.display = (index >= 25 && !showingAll) ? 'none' : '';
                });
                deviceRows = deviceBody.querySelectorAll('tr');
                if (deviceSearch && deviceSearch.value) {
                    deviceSearch.dispatchEvent(new Event('input'));
                }

                document.querySelectorAll('.device-table th.sortable .sort-mark').forEach(mark => mark.textContent = '');
                this.querySelector('.sort-mark').textContent = sortState.dir === 1 ? '▲' : '▼';
            });
        });

        // Hide rows past the initial limit via inline style, so later toggles
        // (which also set inline style) aren't fought by a stylesheet rule.
        deviceRows.forEach(row => {
            if (row.classList.contains('row-limited')) {
                row.style.display = 'none';
            }
        });

        if (deviceSearch) {
            deviceSearch.addEventListener('input', function () {
                const query = this.value.toLowerCase();
                const searching = query.length > 0;
                deviceRows.forEach(row => {
                    const matches = row.textContent.toLowerCase().includes(query);
                    if (!matches) {
                        row.style.display = 'none';
                        return;
                    }
                    // While searching, ignore the row-limited collapse so matches aren't hidden.
                    row.style.display = (!searching && row.classList.contains('row-limited') && !showingAll) ? 'none' : '';
                });
            });
        }

        if (showMoreBtn) {
            showMoreBtn.addEventListener('click', function () {
                showingAll = !showingAll;
                deviceRows.forEach(row => {
                    if (row.classList.contains('row-limited')) {
                        row.style.display = showingAll ? '' : 'none';
                    }
                });
                this.textContent = showingAll ? 'Näita vähem' : 'Näita kõiki ' + deviceRows.length + ' seadet';
            });
        }

        const legacyToggle = document.getElementById('legacyDetailsToggle');
        const legacyPanel = document.getElementById('legacyDetailsPanel');

        if (legacyToggle && legacyPanel) {
            legacyToggle.addEventListener('click', function () {
                const isHidden = legacyPanel.style.display === 'none';
                legacyPanel.style.display = isHidden ? 'block' : 'none';
            });
        }

        const fullInfoToggle = document.getElementById('fullInfoToggle');
        const fullInfoPanel = document.getElementById('fullInfoPanel');

        if (fullInfoToggle && fullInfoPanel) {
            fullInfoToggle.addEventListener('click', function () {
                fullInfoPanel.hidden = !fullInfoPanel.hidden;
                this.setAttribute('aria-expanded', String(!fullInfoPanel.hidden));
                this.textContent = fullInfoPanel.hidden ? '▾ Näita kõiki andmeid' : '▴ Peida';
            });
        }
    </script>
@endsection