@extends('layouts.app')

@section('title', 'SIM-kaardid - AGR')

@php($isAdmin = session('user')->role == 1)

@section('extra-styles')
    <style>
        .card { padding: 24px 26px; border-radius: 18px; background: var(--surface); box-shadow: 0 2px 10px -2px oklch(0 0 0 / 0.06); margin-bottom: 20px; }
        h2 { margin-top: 0; color: var(--text); font-weight: 500; }
        .hint { color: var(--text-muted); margin-top: -6px; }
        .flash { padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; }
        .flash-ok { background: oklch(0.96 0.04 155); color: oklch(0.38 0.1 155); border: 1px solid oklch(0.88 0.07 155); }
        .flash-error { background: #fef2f2; color: var(--danger); border: 1px solid #fee2e2; }

        .sim-stats { display: flex; gap: 10px; flex-wrap: wrap; margin: 4px 0 18px; }
        .sim-stat { display: flex; flex-direction: column; gap: 2px; min-width: 120px; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--border); background: var(--bg); color: var(--text); text-decoration: none; }
        .sim-stat:hover { border-color: var(--primary); }
        .sim-stat.is-active { border-color: var(--text); box-shadow: inset 0 0 0 1px var(--text); }
        .sim-stat-value { font-size: 1.5rem; font-weight: 600; }
        .sim-stat-label { font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.03em; }

        .sim-search { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 14px; }
        .sim-search input { flex: 1 1 260px; }
        .sim-input { padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface); color: var(--text); font: inherit; min-width: 0; }
        .sim-btn { white-space: nowrap; padding: 9px 16px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg); color: var(--text); font: inherit; font-size: 0.9rem; cursor: pointer; }
        .sim-btn:hover { border-color: var(--primary); }
        .sim-btn-primary { background: var(--primary); border-color: transparent; color: white; font-weight: 600; }
        .sim-btn-primary:hover { filter: brightness(1.08); }
        .sim-btn-danger:hover { border-color: var(--danger); color: var(--danger); }

        .sim-add { margin-bottom: 18px; border: 1px solid oklch(0.88 0.05 254); border-left: 4px solid var(--primary); border-radius: 12px; background: oklch(0.97 0.02 254); }
        .sim-add > summary { cursor: pointer; padding: 14px 18px; font-weight: 600; }
        .sim-add-body { padding: 0 18px 18px; display: grid; gap: 18px; }
        .sim-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; align-items: end; }
        .sim-field { display: flex; flex-direction: column; gap: 4px; }
        .sim-field-wide { grid-column: span 2; }
        .sim-label { font-size: 0.75rem; font-weight: 500; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.03em; }
        .sim-add textarea { min-height: 110px; font-family: 'IBM Plex Mono', ui-monospace, monospace; font-size: 0.85rem; width: 100%; }

        .sim-table-wrap { overflow-x: auto; }
        .sim-table { width: 100%; border-collapse: collapse; }
        .sim-table th, .sim-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid var(--border); vertical-align: top; }
        .sim-table th { color: var(--text-muted); font-weight: 500; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.03em; }
        .sim-mono { font-family: 'IBM Plex Mono', ui-monospace, monospace; font-size: 0.88rem; }
        .sim-muted { color: var(--text-muted); font-size: 0.85rem; }
        .sim-table a { color: var(--primary); text-decoration: none; font-weight: 500; }
        .sim-table a:hover { text-decoration: underline; }
        .sim-badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 0.82rem; font-weight: 500; white-space: nowrap; }
        .sim-badge-free { background: oklch(0.95 0.05 155); color: oklch(0.4 0.11 155); }
        .sim-badge-issued { background: oklch(0.95 0.06 75); color: oklch(0.45 0.12 60); }
        .sim-badge-assigned { background: oklch(0.94 0.04 254); color: oklch(0.42 0.14 254); }
        .sim-actions { white-space: nowrap; width: 1%; }
        .sim-manage-row[hidden] { display: none; }
        .sim-manage-row > td { background: var(--bg); }
        .sim-manage { display: grid; gap: 14px; padding: 6px 0; }
        .sim-manage form { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
        .sim-manage-title { font-size: 0.8rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.03em; width: 100%; margin-bottom: -4px; }
        .sim-empty { padding: 20px; text-align: center; background: var(--bg); border: 2px dashed var(--border); border-radius: 8px; color: var(--text-muted); }
        .sim-pagination { margin-top: 14px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
    </style>
@endsection

@section('content')
    <div class="card">
        <h2>📶 SIM-kaardid</h2>
        <p class="hint">Kõik SIM-id ühes registris. Objektil olevat SIM-i saab ainult vabastada — teisele objektile otse ei saa.</p>

        @if(session('success'))
            <div class="flash flash-ok">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="flash flash-error">{{ $errors->first() }}</div>
        @endif

        <div class="sim-stats">
            <a href="{{ route('sims.index', array_filter(['q' => $search])) }}" class="sim-stat {{ $status ? '' : 'is-active' }}">
                <span class="sim-stat-value">{{ $counts->sum() }}</span>
                <span class="sim-stat-label">Kõik</span>
            </a>
            @foreach(\App\Models\SimCard::STATUSES as $value => $label)
                <a href="{{ route('sims.index', array_filter(['status' => $value, 'q' => $search])) }}" class="sim-stat {{ $status === $value ? 'is-active' : '' }}">
                    <span class="sim-stat-value">{{ $counts[$value] ?? 0 }}</span>
                    <span class="sim-stat-label">{{ $label }}</span>
                </a>
            @endforeach
        </div>

        <form method="get" action="{{ route('sims.index') }}" class="sim-search">
            @if($status)
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <input type="search" name="q" value="{{ $search }}" class="sim-input" placeholder="🔍 Number, ICCID, tehnik, objekti aadress">
            <button type="submit" class="sim-btn">Otsi</button>
        </form>

        @if($isAdmin)
            <details class="sim-add" @if($errors->hasAny(['number', 'iccid', 'lines'])) open @endif>
                <summary>➕ Lisa SIM</summary>
                <div class="sim-add-body">
                    <form method="post" action="{{ route('sims.store') }}" class="sim-grid">
                        @csrf
                        <label class="sim-field"><span class="sim-label">Number</span><input name="number" value="{{ old('number') }}" class="sim-input" placeholder="+3725…"></label>
                        <label class="sim-field sim-field-wide"><span class="sim-label">ICCID</span><input name="iccid" value="{{ old('iccid') }}" class="sim-input"></label>
                        <label class="sim-field"><span class="sim-label">PIN1</span><input name="pin1" value="{{ old('pin1') }}" class="sim-input"></label>
                        <label class="sim-field"><span class="sim-label">PUK1</span><input name="puk1" value="{{ old('puk1') }}" class="sim-input"></label>
                        <label class="sim-field"><span class="sim-label">PIN2</span><input name="pin2" value="{{ old('pin2') }}" class="sim-input"></label>
                        <label class="sim-field"><span class="sim-label">PUK2</span><input name="puk2" value="{{ old('puk2') }}" class="sim-input"></label>
                        <label class="sim-field sim-field-wide"><span class="sim-label">Märkus</span><input name="comment" value="{{ old('comment') }}" class="sim-input"></label>
                        <button type="submit" class="sim-btn sim-btn-primary">Lisa</button>
                    </form>

                    <form method="post" action="{{ route('sims.store-bulk') }}">
                        @csrf
                        <label class="sim-field">
                            <span class="sim-label">Mitu korraga — üks rida = number;ICCID;PIN1;PUK1;PIN2;PUK2</span>
                            <textarea name="lines" class="sim-input" placeholder="+37259000001;8937201000000000001;1234;12345678">{{ old('lines') }}</textarea>
                        </label>
                        <button type="submit" class="sim-btn sim-btn-primary" style="margin-top:10px;">Lisa kõik</button>
                    </form>
                </div>
            </details>
        @endif

        @if($sims->isEmpty())
            <div class="sim-empty">SIM-e ei leitud</div>
        @else
            <datalist id="sim-technicians">
                @foreach($technicians as $technician)
                    <option value="{{ $technician }}">
                @endforeach
            </datalist>

            {{-- Один список объектов на страницу: клонируется в строку при «Halda» --}}
            <template id="sim-object-picker">
                <input type="search" class="sim-input task-object-search" placeholder="🔍 Otsi objekti" autocomplete="off" style="flex:1 1 160px;">
                <select name="object_id" class="sim-input task-object-select" required style="flex:2 1 220px;">
                    <option value="">— Vali objekt —</option>
                    @foreach($objects as $item)
                        <option value="{{ $item->id }}">{{ $item->address }}, {{ $item->City }} (#{{ $item->id }})</option>
                    @endforeach
                </select>
            </template>

            <div class="sim-table-wrap">
                <table class="sim-table">
                    <thead>
                        <tr>
                            <th>Number</th>
                            <th>ICCID</th>
                            @if($isAdmin)
                                <th>PIN / PUK</th>
                            @endif
                            <th>Staatus</th>
                            <th>Kus / kellel</th>
                            <th>Märkus</th>
                            @if($isAdmin)
                                <th></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sims as $sim)
                            <tr>
                                <td class="sim-mono">{{ $sim->number ?: '—' }}</td>
                                <td class="sim-mono">{{ $sim->iccid ?: '—' }}</td>
                                @if($isAdmin)
                                    <td class="sim-mono sim-muted">
                                        {{ $sim->pin1 ?: '—' }} / {{ $sim->puk1 ?: '—' }}
                                        @if($sim->pin2 || $sim->puk2)
                                            <br>{{ $sim->pin2 ?: '—' }} / {{ $sim->puk2 ?: '—' }}
                                        @endif
                                    </td>
                                @endif
                                <td><span class="sim-badge sim-badge-{{ $sim->status }}">{{ $sim->statusLabel() }}</span></td>
                                <td>
                                    @if($sim->isAssigned())
                                        @if($sim->object)
                                            <a href="{{ route('objects.show', $sim->object_id) }}">{{ $sim->object->address }}</a>
                                            <span class="sim-muted">· {{ $sim->object->City }} · {{ \App\Models\SimCard::SLOTS[$sim->slot] ?? '' }}</span>
                                        @else
                                            #{{ $sim->object_id }}
                                        @endif
                                        <div class="sim-muted">{{ $sim->assigned_at?->format('d.m.Y') }}{{ $sim->issued_to ? ' · paigaldas ' . $sim->issued_to : '' }}</div>
                                    @elseif($sim->status === \App\Models\SimCard::STATUS_ISSUED)
                                        {{ $sim->issued_to }}
                                        <div class="sim-muted">{{ $sim->issued_at?->format('d.m.Y H:i') }}</div>
                                    @else
                                        <span class="sim-muted">Laos</span>
                                    @endif
                                </td>
                                <td class="sim-muted">{{ $sim->comment }}</td>
                                @if($isAdmin)
                                    <td class="sim-actions">
                                        <button type="button" class="sim-btn sim-manage-toggle" aria-expanded="false" aria-controls="sim-manage-{{ $sim->id }}">Halda ▾</button>
                                    </td>
                                @endif
                            </tr>
                            @if($isAdmin)
                                <tr class="sim-manage-row" id="sim-manage-{{ $sim->id }}" hidden>
                                    <td colspan="7">
                                        <div class="sim-manage">
                                            @if($sim->isAssigned())
                                                <form method="post" action="{{ route('sims.release', $sim->id) }}" onsubmit="return confirm('Kas võtta SIM objektilt maha ja märkida vabaks?');">
                                                    @csrf
                                                    <span class="sim-manage-title">Objektil olevat SIM-i saab ainult vabastada</span>
                                                    <button type="submit" class="sim-btn">↩ Vabasta (märgi vabaks)</button>
                                                </form>
                                            @else
                                                <form method="post" action="{{ route('sims.issue', $sim->id) }}">
                                                    @csrf
                                                    <span class="sim-manage-title">Väljasta tehnikule</span>
                                                    <input name="issued_to" list="sim-technicians" class="sim-input" placeholder="Tehniku nimi" value="{{ $sim->issued_to }}" required>
                                                    <button type="submit" class="sim-btn">Väljasta</button>
                                                </form>

                                                <form method="post" action="{{ route('sims.assign', $sim->id) }}">
                                                    @csrf
                                                    <span class="sim-manage-title">Määra objektile</span>
                                                    <span class="sim-object-slot" style="display:flex; gap:8px; flex:1 1 360px; flex-wrap:wrap;"></span>
                                                    <select name="slot" class="sim-input">
                                                        @foreach(\App\Models\SimCard::SLOTS as $slot => $label)
                                                            <option value="{{ $slot }}">{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="sim-btn">Määra</button>
                                                </form>

                                                @if($sim->status === \App\Models\SimCard::STATUS_ISSUED)
                                                    <form method="post" action="{{ route('sims.release', $sim->id) }}">
                                                        @csrf
                                                        <span class="sim-manage-title">Tehnik tagastas</span>
                                                        <button type="submit" class="sim-btn">↩ Tagasi lattu (vaba)</button>
                                                    </form>
                                                @endif
                                            @endif

                                            <form method="post" action="{{ route('sims.update', $sim->id) }}" class="sim-grid" style="display:grid;">
                                                @csrf
                                                @method('PUT')
                                                <span class="sim-manage-title" style="grid-column:1/-1;">Muuda andmeid</span>
                                                <label class="sim-field"><span class="sim-label">Number</span><input name="number" value="{{ $sim->number }}" class="sim-input"></label>
                                                <label class="sim-field sim-field-wide"><span class="sim-label">ICCID</span><input name="iccid" value="{{ $sim->iccid }}" class="sim-input"></label>
                                                <label class="sim-field"><span class="sim-label">PIN1</span><input name="pin1" value="{{ $sim->pin1 }}" class="sim-input"></label>
                                                <label class="sim-field"><span class="sim-label">PUK1</span><input name="puk1" value="{{ $sim->puk1 }}" class="sim-input"></label>
                                                <label class="sim-field"><span class="sim-label">PIN2</span><input name="pin2" value="{{ $sim->pin2 }}" class="sim-input"></label>
                                                <label class="sim-field"><span class="sim-label">PUK2</span><input name="puk2" value="{{ $sim->puk2 }}" class="sim-input"></label>
                                                <label class="sim-field sim-field-wide"><span class="sim-label">Märkus</span><input name="comment" value="{{ $sim->comment }}" class="sim-input"></label>
                                                <button type="submit" class="sim-btn sim-btn-primary">Salvesta</button>
                                            </form>

                                            @unless($sim->isAssigned())
                                                <form method="post" action="{{ route('sims.destroy', $sim->id) }}" onsubmit="return confirm('Kas kustutada SIM registrist?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="sim-btn sim-btn-danger">✕ Kustuta registrist</button>
                                                </form>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($sims->hasPages())
                <div class="sim-pagination">
                    @if($sims->onFirstPage())
                        <span class="sim-btn" style="opacity:.45;">← Eelmine</span>
                    @else
                        <a class="sim-btn" href="{{ $sims->previousPageUrl() }}" style="text-decoration:none;">← Eelmine</a>
                    @endif
                    <span class="sim-muted">Lk {{ $sims->currentPage() }} / {{ $sims->lastPage() }} · {{ $sims->total() }} SIM-i</span>
                    @if($sims->hasMorePages())
                        <a class="sim-btn" href="{{ $sims->nextPageUrl() }}" style="text-decoration:none;">Järgmine →</a>
                    @else
                        <span class="sim-btn" style="opacity:.45;">Järgmine →</span>
                    @endif
                </div>
            @endif
        @endif
    </div>
@endsection

@section('extra-scripts')
    <script>
        document.addEventListener('click', function (event) {
            var toggle = event.target.closest('.sim-manage-toggle');
            if (!toggle) return;
            var row = document.getElementById(toggle.getAttribute('aria-controls'));
            var open = row.hidden;
            row.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.textContent = open ? 'Halda ▴' : 'Halda ▾';
            var slot = row.querySelector('.sim-object-slot');
            if (open && slot && !slot.childElementCount) {
                slot.appendChild(document.getElementById('sim-object-picker').content.cloneNode(true));
            }
        });

        // Поиск объекта в форме «Määra objektile» (та же логика, что в tasks._form)
        document.addEventListener('input', function (event) {
            if (!event.target.classList.contains('task-object-search')) return;
            var query = event.target.value.trim().toLowerCase();
            var select = event.target.parentElement.querySelector('.task-object-select');
            var firstMatch = null;
            Array.prototype.forEach.call(select.options, function (option) {
                if (!option.value) return;
                var match = option.text.toLowerCase().indexOf(query) !== -1;
                option.hidden = !match;
                if (match && !firstMatch) firstMatch = option;
            });
            if (query === '') {
                select.value = '';
            } else if (firstMatch && (select.value === '' || select.selectedOptions[0].hidden)) {
                select.value = firstMatch.value;
            }
        });
    </script>
@endsection
