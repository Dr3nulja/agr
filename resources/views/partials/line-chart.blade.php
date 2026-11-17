{{--
    Линейный график (inline SVG, без скриптов).
    $points   — [[string $date 'Y-m-d H:i:s', float $value], ...] по возрастанию даты
    $unit     — подпись единиц, $color — цвет линии, $label — aria-label
    $min/$max — (необязательно) фиксированная шкала, например CSQ 0..31
    $decimals — (необязательно) знаков в подписях оси
--}}
@php
    $values = array_column($points, 1);
    $count = count($points);
    $width = 760;
    $height = 220;
    $left = 62;
    $right = 12;
    $bottom = 30;
    $top = 16;
    $plotWidth = $width - $left - $right;
    $plotHeight = $height - $bottom - $top;

    $low = $min ?? ($values ? min($values) : 0);
    $high = $max ?? ($values ? max($values) : 1);
    if ($high - $low < 1e-9) {
        // Ровная линия — даём небольшой запас сверху и снизу
        $pad = max(abs($high) * 0.01, 1);
        $low -= $pad;
        $high += $pad;
    } elseif (! isset($min) && ! isset($max)) {
        $pad = ($high - $low) * 0.08;
        $low -= $pad;
        $high += $pad;
    }
    $decimals ??= ($high - $low) >= 100 ? 0 : (($high - $low) >= 10 ? 1 : 3);

    // Ось X — по времени, чтобы пропуски в данных были видны
    $times = array_map(fn ($point) => strtotime($point[0]), $points);
    $tMin = $times ? min($times) : 0;
    $tMax = $times ? max($times) : 1;
    $tSpan = max($tMax - $tMin, 1);

    $xy = [];
    foreach ($points as $i => $point) {
        $x = $count === 1 ? $left + $plotWidth / 2 : $left + ($times[$i] - $tMin) / $tSpan * $plotWidth;
        $y = $top + $plotHeight - ($point[1] - $low) / ($high - $low) * $plotHeight;
        $xy[] = [round($x, 1), round($y, 1)];
    }

    $labelCount = min($count, 6);
    $labelIndexes = $labelCount <= 1 ? [0] : array_unique(array_map(fn ($k) => (int) round($k * ($count - 1) / ($labelCount - 1)), range(0, $labelCount - 1)));
    $dateFormat = $tSpan > 300 * 86400 ? 'm.y' : 'd.m';
@endphp
@if($count === 0)
    <div style="color: var(--text-muted, #6b7080); font-size: 0.9rem;">Andmed puuduvad.</div>
@else
    <svg viewBox="0 0 {{ $width }} {{ $height }}" role="img" aria-label="{{ $label }}" style="width: 100%; height: auto; max-height: 260px; display: block;">
        @foreach([0, 0.5, 1] as $fraction)
            @php $y = $top + $plotHeight - $fraction * $plotHeight; @endphp
            <line x1="{{ $left }}" x2="{{ $width - $right }}" y1="{{ $y }}" y2="{{ $y }}" stroke="#d9dce6" stroke-width="1" @if($fraction > 0) stroke-dasharray="3 4" @endif />
            <text x="{{ $left - 6 }}" y="{{ $y + 4 }}" text-anchor="end" font-size="11" fill="#6b7080">{{ number_format($low + ($high - $low) * $fraction, $decimals, '.', ' ') }}</text>
        @endforeach
        @if($count > 1)
            <polyline points="{{ implode(' ', array_map(fn ($p) => $p[0] . ',' . $p[1], $xy)) }}" fill="none" stroke="{{ $color }}" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />
        @endif
        @foreach($xy as $i => [$x, $y])
            <circle cx="{{ $x }}" cy="{{ $y }}" r="{{ $count > 60 ? 2 : 3.5 }}" fill="{{ $color }}">
                <title>{{ date('d.m.Y H:i', $times[$i]) }}: {{ number_format($points[$i][1], $decimals, '.', ' ') }} {{ $unit }}</title>
            </circle>
        @endforeach
        @foreach($labelIndexes as $i)
            <text x="{{ $xy[$i][0] }}" y="{{ $height - 10 }}" text-anchor="{{ $i === 0 && $count > 1 ? 'start' : ($i === $count - 1 && $count > 1 ? 'end' : 'middle') }}" font-size="11" fill="#6b7080">{{ date($dateFormat, $times[$i]) }}</text>
        @endforeach
        <text x="4" y="{{ $top - 4 }}" font-size="11" fill="#6b7080">{{ $unit }}</text>
    </svg>
@endif
