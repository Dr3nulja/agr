{{-- Отчёты монтажников из приложения. $mobileReports — коллекция MobileReport --}}
@once
    <style>
        .report { border: 1px solid var(--border); border-radius: 12px; background: var(--bg); margin-bottom: 10px; }
        .report > summary { list-style: none; cursor: pointer; padding: 12px 14px; display: flex; gap: 14px; align-items: center; flex-wrap: wrap; }
        .report > summary::-webkit-details-marker { display: none; }
        .report > summary::before { content: '▸'; color: var(--text-muted); }
        .report[open] > summary::before { content: '▾'; }
        .report-apartment { font-weight: 600; min-width: 90px; }
        .report-meta { color: var(--text-muted); font-family: 'IBM Plex Mono', ui-monospace, monospace; font-size: 0.85rem; }
        .report-body { padding: 0 14px 14px; }
        .report-images { display: flex; gap: 12px; flex-wrap: wrap; margin: 4px 0 12px; }
        .report-images figure { margin: 0; text-align: center; font-size: 0.8rem; color: var(--text-muted); }
        .report-images img { display: block; height: 140px; border-radius: 8px; border: 1px solid var(--border); background: white; margin-bottom: 4px; }
        .report-data { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 6px 16px; font-size: 0.9rem; }
        .report-data dt { color: var(--text-muted); font-size: 0.78rem; }
        .report-data dd { margin: 0 0 6px; word-break: break-word; }
    </style>
@endonce

@forelse($mobileReports as $report)
    <details class="report">
        <summary>
            <span class="report-apartment">Krt {{ $report->apartment ?: '—' }}</span>
            <span class="task-badge task-badge-{{ $report->device_type }}">{{ $report->taskLabel() }}</span>
            <span class="report-meta">QR: {{ $report->qr_id ?: '—' }}</span>
            <span class="report-meta" style="margin-left:auto;">{{ $report->created_at?->format('d.m.Y H:i') }}</span>
        </summary>
        <div class="report-body">
            <div class="report-images">
                @foreach(['photo_before' => 'Foto enne', 'photo_after' => 'Foto pärast', 'signature' => 'Allkiri'] as $file => $label)
                    @if($report->{$file})
                        <figure>
                            <a href="{{ route('mobile-reports.file', [$report->id, $file]) }}" target="_blank">
                                <img src="{{ route('mobile-reports.file', [$report->id, $file]) }}" alt="{{ $label }}" loading="lazy">
                            </a>
                            {{ $label }}
                        </figure>
                    @endif
                @endforeach
            </div>

            <dl class="report-data">
                @if($report->last_reading)
                    <div><dt>last_reading</dt><dd>{{ $report->last_reading }}</dd></div>
                @endif
                @foreach($report->filledData() as $key => $value)
                    <div><dt>{{ $key }}</dt><dd>{{ $value }}</dd></div>
                @endforeach
            </dl>

            @if(session('user')->role == 1)
                <form method="post" action="{{ route('mobile-reports.destroy', $report->id) }}" onsubmit="return confirm('Kas kustutada aruanne?');" style="margin-top:12px;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="task-delete" style="width:auto; padding:0 14px;">✕ Kustuta aruanne</button>
                </form>
            @endif
        </div>
    </details>
@empty
    <div class="task-empty">Aruandeid veel pole</div>
@endforelse
