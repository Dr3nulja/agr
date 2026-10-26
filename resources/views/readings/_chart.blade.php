{{--
    Столбики потребления по месяцам (inline SVG — печатается и в PDF без скриптов).
    $series — [period => float], $unit — подпись единиц, $color — цвет столбиков
--}}
@php
    $max = max(array_merge([0], array_values($series)));
    $count = max(count($series), 1);
    $width = 760;
    $height = 220;
    $left = 54;
    $bottom = 34;
    $top = 16;
    $plotWidth = $width - $left - 10;
    $plotHeight = $height - $bottom - $top;
    $step = $plotWidth / $count;
    $barWidth = max(min($step * 0.62, 46), 4);
    $scaleMax = $max > 0 ? $max * 1.1 : 1;
    $decimals = $max >= 100 ? 0 : ($max >= 10 ? 1 : 2);
@endphp
<svg viewBox="0 0 {{ $width }} {{ $height }}" role="img" aria-label="{{ $label }}" style="width: 100%; height: auto; max-height: 260px; display: block;">
    @foreach([0, 0.5, 1] as $fraction)
        @php $y = $top + $plotHeight - $fraction * $plotHeight; @endphp
        <line x1="{{ $left }}" x2="{{ $width - 10 }}" y1="{{ $y }}" y2="{{ $y }}" stroke="#d9dce6" stroke-width="1" @if($fraction > 0) stroke-dasharray="3 4" @endif />
        <text x="{{ $left - 6 }}" y="{{ $y + 4 }}" text-anchor="end" font-size="11" fill="#6b7080">{{ number_format($scaleMax * $fraction, $decimals, '.', ' ') }}</text>
    @endforeach
    @foreach($series as $period => $value)
        @php
            $barHeight = $value > 0 ? max($value / $scaleMax * $plotHeight, 1) : 0;
            $x = $left + $loop->index * $step + ($step - $barWidth) / 2;
        @endphp
        <rect x="{{ $x }}" y="{{ $top + $plotHeight - $barHeight }}" width="{{ $barWidth }}" height="{{ $barHeight }}" rx="3" fill="{{ $color }}">
            <title>{{ $period }}: {{ number_format($value, 3, '.', ' ') }} {{ $unit }}</title>
        </rect>
        @if($count <= 14 || $loop->index % 2 === 0)
            <text x="{{ $left + $loop->index * $step + $step / 2 }}" y="{{ $height - 12 }}" text-anchor="middle" font-size="11" fill="#6b7080">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $period . '-01')->format('m.y') }}</text>
        @endif
    @endforeach
    <text x="4" y="{{ $top - 4 }}" font-size="11" fill="#6b7080">{{ $unit }}</text>
</svg>
