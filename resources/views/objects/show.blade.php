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

        /* Номер прибора окрашен по типу: külm vesi — синий, soe vesi — красный, jaotur — оранжевый */
        .device-id.type-cold { color: oklch(0.5 0.17 250); }
        .device-id.type-hot { color: oklch(0.55 0.2 25); }
        .device-id.type-heat { color: oklch(0.6 0.15 60); }

        .device-more {
            text-align: center;
            margin-top: 14px;
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

        /* Сворачиваемые блоки: стрелка справа сверху, состояние запоминается */
        .fold-head {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .fold-title {
            margin: 0;
            cursor: pointer;
            user-select: none;
        }

        .fold-extra {
            margin-left: auto;
            font-size: 0.9rem;
        }

        .fold-btn {
            margin-left: auto;
            width: 34px;
            height: 34px;
            flex: none;
            border-radius: 50%;
            border: 1px solid var(--border);
            background: var(--bg);
            color: var(--text);
            font-size: 1.1rem;
            line-height: 1;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .fold-extra + .fold-btn {
            margin-left: 0;
        }

        .fold-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .fold-body {
            margin-top: 16px;
        }

        .fold.is-closed .fold-body {
            display: none;
        }

        .fold.is-closed .fold-btn {
            transform: rotate(-90deg);
        }

        .device-id a {
            color: inherit;
            text-decoration: none;
        }

        .device-id a:hover {
            text-decoration: underline;
        }

        /* Телефон */
        @media (max-width: 640px) {
            .panel {
                padding: 14px 12px;
                border-radius: 14px;
                margin-bottom: 12px;
            }

            .section-title {
                font-size: 1.1rem;
            }

            .obj-head h2 {
                font-size: 1.3rem;
                margin-top: 0;
            }

            .obj-head-sub {
                font-size: 0.85rem;
                word-break: break-word;
            }

            /* Кнопки объекта — сеткой по две, а не столбиком на весь экран */
            .obj-actions {
                display: grid !important;
                grid-template-columns: 1fr 1fr;
                gap: 6px !important;
                width: 100%;
            }

            .obj-actions .btn {
                justify-content: center;
                padding: 9px 8px;
                font-size: 0.82rem;
                text-align: center;
                white-space: normal;
                line-height: 1.2;
            }

            .info-grid {
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) !important;
                gap: 8px !important;
            }

            .info-box {
                padding: 8px 10px;
                min-width: 0;
            }

            .info-label {
                font-size: 0.7rem;
            }

            .info-value {
                font-size: 0.95rem;
                overflow-wrap: anywhere;
            }

            .fold-extra {
                order: 3;
                flex: 1 1 100%;
                margin-left: 0;
            }

            .fold-extra input[type="search"] {
                width: 100% !important;
            }

            .fold-extra + .fold-btn {
                margin-left: auto;
            }

            /* Таблица приборов: без №, Eelmine и Vahe — остальное влезает в экран */
            .device-table {
                min-width: 0 !important;
                font-size: 0.85rem;
            }

            .device-table > thead > tr > th,
            .device-table > tbody > tr.device-row > td {
                padding: 9px 6px;
            }

            .device-table > thead > tr > :nth-child(2),
            .device-table > thead > tr > :nth-child(6),
            .device-table > thead > tr > :nth-child(7),
            .device-table > tbody > tr.device-row > :nth-child(2),
            .device-table > tbody > tr.device-row > :nth-child(6),
            .device-table > tbody > tr.device-row > :nth-child(7) {
                display: none;
            }

            .device-table > thead > tr > :nth-child(9),
            .device-table > tbody > tr.device-row > :nth-child(9) {
                display: none;
            }

            .device-table .dv-date {
                font-size: 0.75rem;
                color: var(--text-muted);
            }

            /* Вместо колонки «Staatus»: старые данные — дата красным */
            .device-table .dv-date.dv-stale {
                color: var(--danger);
            }

            .device-table .dv-date.dv-stale::before {
                content: '⚠️ ';
            }
        }

    </style>
    @include('readings._device-styles')
@endsection

@section('content')
    @include('partials.flash')

    <div class="panel" style="padding-bottom: 16px;">
        <div class="obj-head" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
            <div>
                <h2 style="margin-bottom: 4px;">{{ $object['address'] }}</h2>
                <div class="obj-head-sub" style="color: var(--text-muted);">
                    {{ $object['City'] ?? '—' }} · {{ ['1' => 'Apator', '2' => 'Siemens', '3' => 'Elekter', '4' => 'LoRa'][(string) $object['dtype']] ?? 'tüüp ' . $object['dtype'] }} · IMEI: {{ $object['IMEI'] ?? '—' }}
                </div>
            </div>
            <div class="obj-actions" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
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
                <button type="button" id="foldAllBtn" class="btn btn-secondary" title="Ava / sulge kõik plokid">⇕ Ava kõik</button>
                <a href="{{ route('objects.index') }}" class="btn btn-secondary" data-back>← Tagasi</a>
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

    <section class="panel fold" data-fold="check" data-default-open>
        <div class="fold-head">
            <h3 class="section-title fold-title">✅ Andmete esitamine</h3>
            <div class="fold-extra check-steps">
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
            <button type="button" class="fold-btn" aria-expanded="true" title="Ava / sulge">▾</button>
        </div>
        <div class="fold-body">
        <div class="check-panel">
            <div>
                <div style="color: var(--text-muted); font-size: 0.85rem;">
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
    </section>

    @if(session('user')->role == 1)
        <section class="panel fold" data-fold="command">
            <div class="fold-head">
                <h3 class="section-title fold-title">📡 Saada käsk</h3>
                <button type="button" class="fold-btn" aria-expanded="true" title="Ava / sulge">▾</button>
            </div>
            <div class="fold-body">
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
        </section>
    @endif

    <section class="panel fold" data-fold="tasks">
        <div class="fold-head">
            <h3 class="section-title fold-title">📋 Ülesanded rakendusele ({{ $tasks->count() }})</h3>
            <button type="button" class="fold-btn" aria-expanded="true" title="Ava / sulge">▾</button>
        </div>
        <div class="fold-body">
        <div style="color: var(--text-muted); margin-bottom: 12px;">
            Paigaldajad näevad neid ülesandeid QRScannerApp'i avaekraanil. Ilma ülesanneteta objekti rakenduses ei kuvata.
        </div>

        @include('tasks._list', ['tasks' => $tasks])
        @include('tasks._form')
        </div>
    </section>

    <section class="panel fold" data-fold="reports">
        <div class="fold-head">
            <h3 class="section-title fold-title">📱 Aruanded rakendusest ({{ $mobileReports->count() }})</h3>
            <div class="fold-extra">
                <a href="{{ route('installations.show', $object['id']) }}" class="btn btn-secondary">🔧 Paigalduse ülevaade, PDF / Excel →</a>
            </div>
            <button type="button" class="fold-btn" aria-expanded="true" title="Ava / sulge">▾</button>
        </div>
        <div class="fold-body">
        @include('mobile-reports._list')
        </div>
    </section>

    <section class="panel fold" data-fold="info">
        <div class="fold-head">
            <h3 class="section-title fold-title">🧾 Objekti andmed</h3>
            <button type="button" class="fold-btn" aria-expanded="true" title="Ava / sulge">▾</button>
        </div>
        <div class="fold-body">
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
    </section>

    <section class="panel fold" data-fold="devices" data-default-open>
        <div class="fold-head">
            <h3 class="section-title fold-title">📟 Seadmete näidud ({{ count($devices) }})</h3>
            <div class="fold-extra">
                <input id="deviceSearch" type="search" placeholder="Otsi ID / korteri / kuupäeva järgi" style="padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border); width: 260px;" />
            </div>
            <button type="button" class="fold-btn" aria-expanded="true" title="Ava / sulge">▾</button>
        </div>
        <div class="fold-body">

        @if(count($devices) > 0)
            @php
                // Тип прибора → подпись и цвет (как на странице «Näidud»)
                $typeInfo = function (int $devtype): array {
                    $kind = \App\Services\ReadingsService::kindOf($devtype);
                    $label = \App\Services\ReadingsService::typeLabel($devtype);
                    $class = $kind === 'heat' ? 'type-heat' : ($kind === 'water' ? (str_starts_with($label, 'soe') ? 'type-hot' : 'type-cold') : 'type-other');

                    return [$label, $class];
                };
            @endphp
            <div class="device-table-wrap" style="overflow-x:auto;">
                <table class="device-table" style="width:100%; border-collapse: collapse; min-width: 1020px;">
                    <thead>
                        <tr>
                            <th style="width: 34px;"></th>
                            <th class="sortable" data-sort="num">№ <span class="sort-mark"></span></th>
                            <th class="sortable" data-sort="location">Korter <span class="sort-mark"></span></th>
                            <th class="sortable" data-sort="devid">ID <span class="sort-mark"></span></th>
                            <th style="text-align:right;">Praegune</th>
                            <th style="text-align:right;">Eelmine</th>
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
                                [$typeLabel, $typeClass] = $typeInfo((int) $device['devtype']);
                            @endphp
                            @php $hasPanel = (bool) \App\Services\ReadingsService::kindOf((int) $device['devtype']); @endphp
                            <tr class="device-row rd-dev-row" data-num="{{ $loop->iteration }}" data-type="{{ $typeLabel }}" data-date="{{ $device['timestamp'] }}" data-devid="{{ $device['devid'] }}" data-location="{{ $device['location'] }}" @if($hasPanel) data-panel-url="{{ route('readings.panel', [$object['id'], $device['devid']]) }}" @endif>
                                <td>@if($hasPanel)<button type="button" class="rd-toggle" title="Näita seadme andmeid" aria-expanded="false">▶</button>@endif</td>
                                <td class="row-number">{{ $loop->iteration }}</td>
                                <td>{{ $device['location'] ?? '-' }}</td>
                                <td class="device-id {{ $typeClass }}" title="{{ $typeLabel }}">
                                    @if($hasPanel)
                                        <a href="{{ route('readings.device', [$object['id'], $device['devid'], 'back' => request()->fullUrl()]) }}" title="{{ $typeLabel }} — graafik, ajalugu, vead">{{ $device['devid'] }}</a>
                                    @else
                                        {{ $device['devid'] }}
                                    @endif
                                </td>
                                <td class="dv-curr" style="text-align:right;">{{ number_format($curr, 3, '.', '') }}</td>
                                <td class="dv-prev" style="text-align:right;">{{ number_format($prev, 3, '.', '') }}</td>
                                <td class="dv-delta" style="text-align:right;">{{ number_format($delta, 3, '.', '') }}</td>
                                <td class="dv-date {{ $device['err_class'] ? 'dv-stale' : '' }}">{{ $device['date'] }}</td>
                                <td style="text-align:center;" @if($device['err_class']) title="Andmed vanemad kui 3 päeva" @endif>{{ $device['err_class'] ? '⚠️' : 'OK' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Как в списке объектов: следующие строки показываются при прокрутке до конца таблицы --}}
            <div class="device-more" id="deviceMore" @if(count($devices) <= 25) hidden @endif>
                <button type="button" id="deviceShowMoreBtn" class="btn btn-secondary" style="padding: 8px 18px; font-size: 0.85rem;">Näita veel</button>
            </div>

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
    </section>

    <section class="panel fold" data-fold="summary">
        <div class="fold-head">
            <h3 class="section-title fold-title">📌 Kokkuvõte</h3>
            <button type="button" class="fold-btn" aria-expanded="true" title="Ava / sulge">▾</button>
        </div>
        <div class="fold-body">
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
    </section>

    <section class="panel fold" data-fold="install">
        <div class="fold-head">
            <h3 class="section-title fold-title">📦 Paigaldusandmed ({{ count($installData) }})</h3>
            <button type="button" class="fold-btn" aria-expanded="true" title="Ava / sulge">▾</button>
        </div>
        <div class="fold-body">
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
    </section>

    <section class="panel fold" data-fold="csq">
        <div class="fold-head">
            <h3 class="section-title fold-title">📶 Signaali tugevus (CSQ)</h3>
            @if(count($csqLogs) > 0)
                <div class="fold-extra" style="color: var(--text-muted);">viimane {{ $csqLogs[0]['csq'] }} · {{ $csqLogs[0]['date'] ?? '' }}</div>
            @endif
            <button type="button" class="fold-btn" aria-expanded="true" title="Ava / sulge">▾</button>
        </div>
        <div class="fold-body">
        @if(count($csqLogs) > 0)
            {{-- CSQ 0..31: < 10 — плохо, 10–14 — удовлетворительно, 15+ — хорошо --}}
            @include('partials.line-chart', [
                'points' => array_reverse(array_map(fn ($row) => [$row['date'], (float) $row['csq']], array_slice(array_filter($csqLogs, fn ($row) => ! empty($row['date']) && (int) $row['csq'] !== 99), 0, 300))),
                'unit' => 'CSQ',
                'color' => '#3d6be0',
                'label' => 'Signaali tugevus',
                'min' => 0,
                'max' => 31,
                'decimals' => 0,
            ])
            <button type="button" id="csqTableToggle" class="btn btn-secondary" style="margin-top: 14px; padding: 8px 18px; font-size: 0.85rem;" aria-expanded="false" aria-controls="csqTablePanel">▾ Näita tabelit ({{ count($csqLogs) }})</button>
            <div id="csqTablePanel" hidden style="overflow-x:auto; max-height: 420px; overflow-y: auto; margin-top: 12px;">
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
    </section>
@endsection

@section('extra-scripts')
    @include('readings._panel-script')
    <script>
        // Таблица приборов: сортировка по №, Korter, ID, Kuupäev; тип прибора — цветом номера (повторный клик — обратный порядок),
        // поиск и показ порциями по 25 строк — следующая порция при прокрутке до конца, как в списке объектов.
        const deviceSearch = document.getElementById('deviceSearch');
        const deviceBody = document.getElementById('deviceTableBody');
        const deviceMore = document.getElementById('deviceMore');
        const showMoreBtn = document.getElementById('deviceShowMoreBtn');
        const DEVICE_STEP = 25;
        let deviceLimit = DEVICE_STEP;
        const sortState = { key: 'num', dir: 1 };

        // Стрелка ▶ у водомеров / jaoturite: данные прибора подгружаются только при раскрытии строки
        const fmt = value => Number(value).toFixed(3);
        const devicePanels = deviceBody ? window.initDevicePanels({
            body: deviceBody,
            wrap: deviceBody.closest('.device-table-wrap'),
            rowSelector: 'tr.device-row',
            onUpdate: (row, device, changed) => {
                row.querySelector('.dv-curr').textContent = fmt(device.mvalue_raw);
                row.querySelector('.dv-prev').textContent = fmt(device.prev_value_raw);
                row.querySelector('.dv-delta').textContent = fmt(device.mvalue_raw - device.prev_value_raw);
                row.querySelector('.dv-date').textContent = device.date_full;
                row.dataset.date = device.timestamp;
                if (changed) {
                    const link = row.querySelector('.device-id a');
                    if (link) {
                        link.textContent = device.devid;
                        const url = new URL(device.page_url, location.href);
                        url.searchParams.set('back', location.href);
                        link.href = url.toString();
                    }
                }
            },
        }) : null;

        function renderDevices() {
            if (!deviceBody) return;
            const query = deviceSearch ? deviceSearch.value.trim().toLowerCase() : '';
            const rows = Array.from(deviceBody.querySelectorAll('tr.device-row'));
            let visible = 0;
            let matched = 0;
            rows.forEach(row => {
                const matches = !query || row.textContent.toLowerCase().includes(query);
                if (matches) matched++;
                // При поиске показываем все совпадения
                const show = matches && (query || visible < deviceLimit);
                if (show) visible++;
                row.style.display = show ? '' : 'none';
                const panelRow = devicePanels && devicePanels.panelRowOf(row);
                if (panelRow) panelRow.style.display = row.style.display;
            });
            if (deviceMore) {
                deviceMore.hidden = !!query || visible >= matched;
                showMoreBtn.textContent = 'Näita veel (' + visible + ' / ' + matched + ')';
            }
        }

        function sortDevices(key, dir) {
            sortState.key = key;
            sortState.dir = dir;
            const rows = Array.from(deviceBody.querySelectorAll('tr.device-row'));
            rows.sort((a, b) => {
                const x = a.dataset[key] || '';
                const y = b.dataset[key] || '';
                const cmp = (key === 'location' || key === 'type')
                    ? x.localeCompare(y, undefined, { numeric: true })
                    : (Number(x) - Number(y));
                // При равенстве — исходный порядок
                return (cmp || (Number(a.dataset.num) - Number(b.dataset.num))) * (cmp ? dir : 1);
            });
            rows.forEach(row => {
                // Раскрытая панель прибора переезжает вместе со своей строкой
                const panelRow = devicePanels && devicePanels.panelRowOf(row);
                deviceBody.appendChild(row);
                if (panelRow) deviceBody.appendChild(panelRow);
            });
            document.querySelectorAll('.device-table th.sortable').forEach(th => {
                th.querySelector('.sort-mark').textContent = th.dataset.sort === key ? (dir === 1 ? '▲' : '▼') : '';
            });
            renderDevices();
        }

        if (deviceBody) {
            document.querySelectorAll('.device-table th.sortable').forEach(th => {
                th.addEventListener('click', function () {
                    const key = this.dataset.sort;
                    const dir = sortState.key === key ? -sortState.dir : (key === 'date' ? -1 : 1);
                    sortDevices(key, dir);
                });
            });

            if (deviceSearch) deviceSearch.addEventListener('input', renderDevices);

            const showMore = () => {
                if (deviceMore.hidden) return;
                deviceLimit += DEVICE_STEP;
                renderDevices();
            };
            if (showMoreBtn) {
                showMoreBtn.addEventListener('click', showMore);
                if ('IntersectionObserver' in window) {
                    new IntersectionObserver(entries => {
                        if (entries.some(entry => entry.isIntersecting)) {
                            showMore();
                            // Высокий экран: низ всё ещё виден — показываем дальше
                            requestAnimationFrame(function again() {
                                if (!deviceMore.hidden && deviceMore.getBoundingClientRect().top < window.innerHeight + 200) {
                                    showMore();
                                    requestAnimationFrame(again);
                                }
                            });
                        }
                    }, { rootMargin: '200px' }).observe(deviceMore);
                }
            }

            // При возврате на страницу — та же сортировка и столько же показанных строк
            window.listState = {
                save: () => ({ deviceLimit, sortKey: sortState.key, sortDir: sortState.dir }),
                restore: state => {
                    if (state.deviceLimit) deviceLimit = state.deviceLimit;
                    if (state.sortKey) sortDevices(state.sortKey, state.sortDir || 1);
                },
            };

            sortDevices('num', 1);
        }

        const legacyToggle = document.getElementById('legacyDetailsToggle');
        const legacyPanel = document.getElementById('legacyDetailsPanel');

        if (legacyToggle && legacyPanel) {
            legacyToggle.addEventListener('click', function () {
                const isHidden = legacyPanel.style.display === 'none';
                legacyPanel.style.display = isHidden ? 'block' : 'none';
            });
        }

        // Сворачиваемые блоки. По умолчанию открыты только «Andmete esitamine» и «Seadmete näidud»;
        // выбор пользователя запоминается в браузере (общий для всех объектов).
        const folds = Array.from(document.querySelectorAll('.fold'));
        const foldAllBtn = document.getElementById('foldAllBtn');
        const foldKey = name => 'objectFold:' + name;

        function setFold(fold, open, remember) {
            fold.classList.toggle('is-closed', !open);
            fold.querySelector('.fold-btn').setAttribute('aria-expanded', String(open));
            if (remember) {
                try { localStorage.setItem(foldKey(fold.dataset.fold), open ? '1' : '0'); } catch (e) {}
            }
        }

        function updateFoldAllBtn() {
            if (!foldAllBtn) return;
            const allOpen = folds.every(fold => !fold.classList.contains('is-closed'));
            foldAllBtn.textContent = allOpen ? '⇕ Sulge kõik' : '⇕ Ava kõik';
        }

        folds.forEach(fold => {
            let saved = null;
            try { saved = localStorage.getItem(foldKey(fold.dataset.fold)); } catch (e) {}
            setFold(fold, saved === null ? fold.hasAttribute('data-default-open') : saved === '1', false);

            const toggle = () => {
                setFold(fold, fold.classList.contains('is-closed'), true);
                updateFoldAllBtn();
            };
            fold.querySelector('.fold-btn').addEventListener('click', toggle);
            fold.querySelector('.fold-title').addEventListener('click', toggle);
        });
        updateFoldAllBtn();

        if (foldAllBtn) {
            foldAllBtn.addEventListener('click', () => {
                const open = folds.some(fold => fold.classList.contains('is-closed'));
                folds.forEach(fold => setFold(fold, open, true));
                updateFoldAllBtn();
            });
        }

        const csqTableToggle = document.getElementById('csqTableToggle');
        const csqTablePanel = document.getElementById('csqTablePanel');
        if (csqTableToggle && csqTablePanel) {
            const csqLabel = csqTableToggle.textContent.trim();
            csqTableToggle.addEventListener('click', function () {
                csqTablePanel.hidden = !csqTablePanel.hidden;
                this.setAttribute('aria-expanded', String(!csqTablePanel.hidden));
                this.textContent = csqTablePanel.hidden ? csqLabel : '▴ Peida tabel';
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