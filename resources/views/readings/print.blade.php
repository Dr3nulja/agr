<!DOCTYPE html>
<html lang="et">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — {{ $object->address }}</title>
    <style>
        @page { size: A4 landscape; margin: 12mm 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; font-size: 9.5pt; color: #1f2430; background: #fff; }
        .page { padding: 16px; }
        .toolbar { display: flex; gap: 10px; align-items: center; justify-content: flex-end; padding: 12px 16px; border-bottom: 1px solid #ddd; background: #f6f7fb; position: sticky; top: 0; }
        .toolbar button { padding: 8px 16px; border-radius: 999px; border: 0; background: #3d6be0; color: #fff; font: inherit; cursor: pointer; }
        .toolbar span { color: #666; font-size: 9.5pt; margin-right: auto; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; }
        .head img { max-height: 48px; max-width: 180px; }
        h1 { font-size: 15pt; margin: 0 0 2px; }
        .muted { color: #666; }
        .charts { display: flex; gap: 20px; margin: 14px 0; }
        .charts > div { flex: 1; break-inside: avoid; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccd; padding: 3px 5px; text-align: left; white-space: nowrap; }
        th { background: #f0f2f8; font-weight: 600; }
        td.num, th.num { text-align: right; }
        tr { break-inside: avoid; }
        @media print {
            .toolbar { display: none; }
            .page { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <span>Salvestamiseks vali printeriks „Salvesta PDF-ina“ / „Save as PDF“.</span>
        <button type="button" onclick="window.print()">🖨 Prindi / salvesta PDF</button>
    </div>

    <div class="page">
        <div class="head">
            <div>
                <h1>{{ $title }}</h1>
                <div><strong>Maja:</strong> {{ $object->address }}, {{ $object->City }}</div>
                @if($filters['location'] !== '')
                    <div><strong>Korter:</strong> {{ $filters['location'] }}</div>
                @endif
                <div class="muted">
                    Periood {{ \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $filters['from'] . '-01')->format('m.Y') }} – {{ \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $filters['to'] . '-01')->format('m.Y') }}
                    · Koostatud {{ now()->format('d.m.Y H:i') }}
                </div>
            </div>
            @if($logo)
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logo)) }}" alt="">
            @else
                <strong style="font-size: 16pt;">{{ \App\Models\AgrObject::COMPANIES[(int) $object->Company] ?? \App\Models\AgrObject::COMPANIES[1] }}</strong>
            @endif
        </div>

        @if($totals)
            <div class="charts">
                @foreach($totals as $kind => $series)
                    <div>
                        <strong>{{ \App\Services\ReadingsService::KINDS[$kind] }}</strong>
                        @include('readings._chart', [
                            'series' => $series,
                            'unit' => $kind === \App\Services\ReadingsService::KIND_WATER ? 'm³' : 'ühikut',
                            'color' => $kind === \App\Services\ReadingsService::KIND_WATER ? '#3b6fe0' : '#e08a2c',
                            'label' => \App\Services\ReadingsService::KINDS[$kind],
                        ])
                    </div>
                @endforeach
            </div>
        @endif

        <table>
            <thead>
                <tr>
                    <th>Korter</th>
                    <th>Seadme nr</th>
                    <th>Tüüp</th>
                    <th class="num">Viimane näit</th>
                    <th>Kuupäev</th>
                    @foreach($periods as $period)
                        <th class="num">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $period . '-01')->format('m.y') }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($devices as $device)
                    <tr>
                        <td>{{ $device['location'] ?: '—' }}</td>
                        <td>{{ $device['devid'] }}</td>
                        <td>{{ $device['type_label'] }}</td>
                        <td class="num">{{ $device['value'] !== null ? number_format($device['value'], 3, '.', ' ') : '—' }}</td>
                        <td>{{ $device['date'] ? date('d.m.Y', strtotime($device['date'])) : '—' }}</td>
                        @foreach($periods as $period)
                            @php $consumption = $monthly[$device['devid']][$period]['consumption'] ?? null; @endphp
                            <td class="num">{{ $consumption !== null ? number_format($consumption, 3, '.', ' ') : '' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ 5 + count($periods) }}" class="muted">Seadmeid pole</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
