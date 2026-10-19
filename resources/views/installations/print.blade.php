<!DOCTYPE html>
<html lang="et">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paigaldus — {{ $object->address }}</title>
    <style>
        @page { size: A4; margin: 14mm 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; font-size: 10.5pt; color: #1f2430; background: #fff; }
        .page { max-width: 186mm; margin: 0 auto; padding: 16px; }
        .toolbar { display: flex; gap: 10px; align-items: center; justify-content: flex-end; padding: 12px 16px; border-bottom: 1px solid #ddd; background: #f6f7fb; position: sticky; top: 0; }
        .toolbar button { padding: 8px 16px; border-radius: 999px; border: 0; background: #3d6be0; color: #fff; font: inherit; cursor: pointer; }
        .toolbar span { color: #666; font-size: 9.5pt; margin-right: auto; }
        h1 { font-size: 16pt; margin: 0 0 2px; }
        .muted { color: #666; }
        .meta { margin: 0 0 14px; }
        table.summary { width: 100%; border-collapse: collapse; margin: 10px 0 18px; }
        table.summary th, table.summary td { border: 1px solid #ccd; padding: 4px 6px; text-align: left; }
        table.summary th { background: #f0f2f8; font-weight: 600; }
        .missing { margin: 0 0 18px; }
        .apartment { margin-top: 14px; }
        .apartment h2 { font-size: 12.5pt; margin: 0 0 6px; padding: 4px 8px; background: #eef1f8; border-left: 4px solid #3d6be0; }
        .report { border: 1px solid #d5d9e4; border-radius: 6px; padding: 8px 10px; margin-bottom: 10px; break-inside: avoid; page-break-inside: avoid; }
        .report-head { display: flex; gap: 12px; flex-wrap: wrap; font-weight: 600; margin-bottom: 6px; }
        .report-head .muted { font-weight: 400; }
        .report-head .date { margin-left: auto; font-weight: 400; color: #666; }
        table.fields { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.fields td { padding: 2px 6px 2px 0; vertical-align: top; }
        table.fields td.label { color: #666; width: 42%; }
        .images { display: flex; gap: 10px; flex-wrap: wrap; }
        .images figure { margin: 0; text-align: center; font-size: 8.5pt; color: #666; }
        .images img { display: block; height: 55mm; max-width: 60mm; object-fit: contain; border: 1px solid #ccd; }
        .images img.signature { height: 25mm; max-width: 80mm; }
        @media print {
            .toolbar { display: none; }
            .page { padding: 0; max-width: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <span>Salvestamiseks vali printeriks „Salvesta PDF-ina“ / „Save as PDF“.</span>
        <button type="button" onclick="window.print()">🖨 Prindi / salvesta PDF</button>
    </div>

    <div class="page">
        <h1>Paigaldusaruanne — {{ $object->address }}</h1>
        <p class="meta muted">
            {{ $object->City }} · Koostatud {{ now()->format('d.m.Y H:i') }}
            @if($filters['device'])· {{ \App\Models\ObjectTask::DEVICE_TYPES[$filters['device']] }}@endif
            @if($filters['from'] || $filters['to'])· periood {{ $filters['from'] ? \Illuminate\Support\Carbon::parse($filters['from'])->format('d.m.Y') : '…' }} – {{ $filters['to'] ? \Illuminate\Support\Carbon::parse($filters['to'])->format('d.m.Y') : '…' }}@endif
        </p>

        <table class="summary">
            <thead>
                <tr><th>Seade</th><th>Toiming</th><th>Kortereid tehtud</th><th>Kokku kortereid</th><th>%</th></tr>
            </thead>
            <tbody>
                @foreach($progress as $item)
                    <tr>
                        <td>{{ $item->label }}</td>
                        <td>{{ $item->actions ?: '—' }}</td>
                        <td>{{ $item->done }}</td>
                        <td>{{ $item->total ?: '—' }}</td>
                        <td>{{ $item->percent !== null ? $item->percent . '%' : '—' }}</td>
                    </tr>
                @endforeach
                <tr><td colspan="2"><strong>Aruandeid kokku</strong></td><td colspan="3"><strong>{{ $reportCount }}</strong></td></tr>
            </tbody>
        </table>

        @if($missing->isNotEmpty())
            <p class="missing"><strong>Aruandeta korterid ({{ $missing->count() }}):</strong> {{ $missing->implode(', ') }}</p>
        @endif

        @forelse($apartments as $reports)
            <section class="apartment">
                <h2>Korter {{ $reports->first()->apartment ?: '—' }}</h2>
                @foreach($reports as $report)
                    <div class="report">
                        <div class="report-head">
                            <span>{{ $report->taskLabel() }}</span>
                            <span class="muted">QR: {{ $report->qr_id ?: '—' }}</span>
                            @if($report->installer)
                                <span class="muted">Paigaldaja: {{ $report->installer }}</span>
                            @endif
                            <span class="date">{{ $report->created_at?->format('d.m.Y H:i') }}</span>
                        </div>

                        @php($fields = $report->fields())
                        @if($fields)
                            <table class="fields">
                                @foreach($fields as [$label, $value])
                                    <tr><td class="label">{{ $label }}</td><td>{{ $value }}</td></tr>
                                @endforeach
                            </table>
                        @endif

                        <div class="images">
                            @foreach(['photo_before' => 'Foto enne', 'photo_after' => 'Foto pärast', 'signature' => 'Omaniku allkiri'] as $file => $label)
                                @if($report->{$file})
                                    <figure>
                                        <img src="{{ route('mobile-reports.file', [$report->id, $file]) }}" alt="{{ $label }}" class="{{ $file === 'signature' ? 'signature' : '' }}">
                                        {{ $label }}
                                    </figure>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </section>
        @empty
            <p class="muted">Aruandeid pole.</p>
        @endforelse
    </div>

    <script>
        // Печать после загрузки всех фото, иначе в PDF попадут пустые рамки
        window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });
    </script>
</body>
</html>
