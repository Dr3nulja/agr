{{-- Один отчёт монтажника: шапка, фото + подпись, поля формы. $report — MobileReport --}}
<div class="inst-report">
    <div class="inst-report-head">
        <span class="task-badge task-badge-{{ $report->device_type }}">{{ $report->taskLabel() }}</span>
        <span class="inst-mono">QR: {{ $report->qr_id ?: '—' }}</span>
        @if($report->installer)
            <span class="inst-mono">👷 {{ $report->installer }}</span>
        @endif
        <span class="inst-mono inst-report-date">{{ $report->created_at?->format('d.m.Y H:i') }}</span>
    </div>

    <div class="inst-report-images">
        @foreach(['photo_before' => 'Foto enne', 'photo_after' => 'Foto pärast', 'signature' => 'Allkiri'] as $file => $label)
            @if($report->{$file})
                <figure>
                    <a href="{{ route('mobile-reports.file', [$report->id, $file]) }}" target="_blank">
                        <img src="{{ route('mobile-reports.file', [$report->id, $file]) }}" alt="{{ $label }}" loading="lazy" class="{{ $file === 'signature' ? 'is-signature' : '' }}">
                    </a>
                    <figcaption>{{ $label }}</figcaption>
                </figure>
            @endif
        @endforeach
    </div>

    @php($fields = $report->fields())
    @if($fields)
        <dl class="inst-fields">
            @foreach($fields as [$label, $value])
                <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
            @endforeach
        </dl>
    @endif
</div>
