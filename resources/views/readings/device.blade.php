@extends('layouts.app')

@section('title', 'Seade ' . $device['devid'] . ' - AGR')

@section('extra-styles')
    <style>
        .card { padding: 22px 24px; border-radius: 18px; background: var(--surface); box-shadow: 0 2px 10px -2px oklch(0 0 0 / 0.06); margin-bottom: 20px; }
        h2 { margin: 0 0 4px; font-weight: 500; }
        h3 { margin: 0 0 12px; font-weight: 500; }
        .rd-muted { color: var(--text-muted); font-size: 0.9rem; }
        .rd-mono { font-family: 'IBM Plex Mono', ui-monospace, monospace; }
        .rd-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 999px; border: 1px solid var(--border); background: var(--bg); color: var(--text); font: inherit; font-size: 0.85rem; text-decoration: none; cursor: pointer; white-space: nowrap; }
        .rd-btn:hover { border-color: var(--primary); }
        .rd-btn-primary { background: var(--primary-grad); color: white; border-color: transparent; }
        .rd-input { padding: 8px 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface); color: var(--text); font: inherit; font-size: 0.9rem; }
        .rd-tone-cold { color: oklch(0.5 0.17 250); }
        .rd-tone-hot { color: oklch(0.55 0.19 25); }
        .rd-tone-heat { color: oklch(0.58 0.15 60); }
    </style>
    @include('readings._device-styles')
@endsection

@section('content')
    @include('partials.flash')

    @php
        $backUrl = $back && str_starts_with($back, url('/')) ? $back : route('readings.index', $object->id);
        $tone = \App\Services\ReadingsService::tone($device['devtype']);
    @endphp

    <div class="card">
        <div style="display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
            <div>
                <h2>Seade <span class="rd-mono rd-tone-{{ $tone }}">{{ $device['devid'] }}</span></h2>
                <div class="rd-muted">{{ $object->address }}, {{ $object->City }} · korter {{ $device['location'] ?: '—' }} · <span class="rd-tone-{{ $tone }}">{{ $device['type_label'] }}</span></div>
            </div>
            <a href="{{ $backUrl }}" class="rd-btn" data-back>← Näidud</a>
        </div>
    </div>

    @include('readings._device-panel')
@endsection
