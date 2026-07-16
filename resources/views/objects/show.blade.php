@extends('layouts.app')

@section('title', 'View Object - AGR')

@section('extra-styles')
    <style>
        .panel {
            background: var(--surface);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
            margin-bottom: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }

        .info-box {
            padding: 12px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .info-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text);
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 16px;
            border-radius: 8px;
            border: 0;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-light);
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
            font-weight: 600;
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
            font-weight: 700;
        }

        .device-table tbody tr:hover,
        .legacy-table tbody tr:hover {
            background: rgba(15, 23, 42, 0.04);
        }

        .row-error {
            background: rgba(248, 113, 113, 0.1);
        }

        .device-id {
            font-weight: 700;
        }
    </style>
@endsection

@section('content')
    <div class="panel" style="padding-bottom: 16px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
            <div>
                <h2 style="margin-bottom: 4px;">{{ $object['address'] }}</h2>
                <div style="color: var(--text-muted);">
                    {{ $object['City'] ?? '—' }} · {{ $object['dtype'] == 1 ? 'külm' : ($object['dtype'] == 2 ? 'soe' : ($object['dtype'] == 3 ? 'electric' : 'type ' . $object['dtype'])) }} · IMEI: {{ $object['IMEI'] ?? '—' }}
                </div>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                @if(session('user')->role == 1)
                    <a href="{{ route('objects.edit', $object['id']) }}" class="btn btn-primary">✏️ Edit</a>
                    <a href="{{ route('objects.export.current', $object['id']) }}" class="btn btn-secondary">📥 Export Current Val XLSX</a>
                    <a href="{{ route('objects.export.month_start', $object['id']) }}" class="btn btn-secondary">📥 Export Month Start</a>
                    <a href="{{ route('objects.export.alokator', $object['id']) }}" class="btn btn-secondary">📥 Export Alokator XLSX</a>
                    <a href="{{ route('objects.export.korto', $object['id']) }}" class="btn btn-secondary">📥 Export Korto CSV</a>
                @endif
                <a href="{{ route('objects.index') }}" class="btn btn-secondary">← Back</a>
            </div>
        </div>

        @if(trim($object['Description'] ?? '') !== '')
            <div style="margin-top: 18px; color: var(--text-muted); line-height: 1.6;">
                {{ $object['Description'] }}
            </div>
        @endif

        <div class="info-grid" style="margin-top: 20px; gap: 14px;">
            <div class="info-box">
                <div class="info-label">Company</div>
                <div class="info-value">{{ $object['Company'] ?? '—' }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Status</div>
                <div class="info-value">{{ $object['status'] == 1 ? 'Active' : ($object['status'] == 2 ? 'Inactive' : 'Unknown') }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Checked</div>
                <div class="info-value">{{ $object['selDate'] ? date('d.m.Y H:i', strtotime($object['selDate'])) : 'No' }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Device Rows</div>
                <div class="info-value">{{ count($devices) }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Install Rows</div>
                <div class="info-value">{{ count($installData) }}</div>
            </div>
        </div>
    </div>

    <div class="panel">
        <h3 class="section-title">🧾 Object Info</h3>
        <div class="info-grid" style="margin-top: 14px; gap: 16px;">
            <div class="info-box">
                <div class="info-label">Object ID</div>
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
            <div class="info-box">
                <div class="info-label">GSMNR</div>
                <div class="info-value">{{ $object['GSMNR'] ?? '—' }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">GSMNR2</div>
                <div class="info-value">{{ $object['GSMNR2'] ?? '—' }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">GSMSERIAL</div>
                <div class="info-value">{{ $object['GSMSERIAL'] ?? '—' }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">GSMSERIAL2</div>
                <div class="info-value">{{ $object['GSMSERIAL2'] ?? '—' }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Contact</div>
                <div class="info-value">{{ $object['Contact'] ?? '—' }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Packet</div>
                <div class="info-value">{{ $object['packet'] ?? '—' }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Traffic</div>
                <div class="info-value">{{ $object['traffic'] ?? '—' }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Call Count</div>
                <div class="info-value">{{ $object['callCnt'] ?? 0 }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Sum</div>
                <div class="info-value">{{ $object['summ'] ?? 0 }}</div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom: 18px;">
            <h3 class="section-title" style="margin:0;">View Records</h3>
            <input id="deviceSearch" type="search" placeholder="Search by ID / location / date" style="padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border); width: 260px;" />
        </div>

        @if(count($devices) > 0)
            <div style="overflow-x:auto;">
                <table class="device-table" style="width:100%; border-collapse: collapse; min-width: 1020px;">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Korter</th>
                            <th>ID</th>
                            <th>Type</th>
                            <th style="text-align:right;">Prev</th>
                            <th style="text-align:right;">Curr</th>
                            <th style="text-align:right;">Delta</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="deviceTableBody">
                        @foreach($devices as $device)
                            @php
                                $prev = $device['prev_mvalue'] ?? 0;
                                $curr = $device['mvalue'] ?? 0;
                                $delta = $curr - $prev;
                            @endphp
                            <tr class="{{ $device['err_class'] ? 'row-error' : '' }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $device['location'] ?? '-' }}</td>
                                <td class="device-id">{{ $device['devid'] }}</td>
                                <td>{{ $device['devtype'] === 1 ? 'külm' : ($device['devtype'] === 2 ? 'soe' : ($device['devtype'] === 3 ? 'electric' : 'type ' . $device['devtype'])) }}</td>
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

            <div class="panel" style="margin-top: 10px;">
                <h3 class="section-title">🧾 Legacy Device Details</h3>
                <div style="overflow-x:auto;">
                    <table class="legacy-table" style="width:100%; border-collapse: collapse; min-width: 1020px;">
                        <thead>
                            <tr>
                                <th>№</th>
                                <th>ID</th>
                                <th>mvalue</th>
                                <th>t1</th>
                                <th>t2</th>
                                <th>error</th>
                                <th>errorDate</th>
                                <th>last insert</th>
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
            <div class="placeholder">No device records available for this object.</div>
        @endif
    </div>

    <div class="panel">
        <h3 class="section-title">📌 Object Summary</h3>
        <div class="info-grid">
            <div class="info-box">
                <div class="info-label">Water Errors</div>
                <div class="info-value">{{ $legacyErrorCounts['water_errors'] ?? 0 }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Heat Errors</div>
                <div class="info-value">{{ $legacyErrorCounts['heat_errors'] ?? 0 }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Legacy Errors</div>
                <div class="info-value">{{ $legacyErrorCounts['errors'] ?? 0 }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Install Rows</div>
                <div class="info-value">{{ count($installData) }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">CSQ Logs</div>
                <div class="info-value">{{ count($csqLogs) }}</div>
            </div>
        </div>
    </div>

    <div class="panel">
        <h3 class="section-title">📦 Install Data</h3>
        @if(count($installData) > 0)
            <div style="overflow-x:auto;">
                <table class="info-table" style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Location</th>
                            <th>Comment</th>
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
            <div class="placeholder">No install data available for this object.</div>
        @endif
    </div>

    <div class="panel">
        <h3 class="section-title">📶 Signal Quality (CSQ) Logs</h3>
        @if(count($csqLogs) > 0)
            <div style="overflow-x:auto;">
                <table class="info-table" style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th>Date</th>
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
            <div class="placeholder">No CSQ logs found for this object.</div>
        @endif
    </div>
@endsection

@section('extra-scripts')
    <script>
        const deviceSearch = document.getElementById('deviceSearch');
        const deviceRows = document.querySelectorAll('#deviceTableBody tr');

        if (deviceSearch) {
            deviceSearch.addEventListener('input', function () {
                const query = this.value.toLowerCase();
                deviceRows.forEach(row => {
                    row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
                });
            });
        }
    </script>
@endsection