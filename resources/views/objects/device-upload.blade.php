@extends('layouts.app')

@section('title', 'Seadmete nimekiri - AGR')

@section('extra-styles')
    <style>
        .card { padding: 24px; border-radius: 18px; background: var(--surface); box-shadow: 0 2px 10px -2px oklch(0 0 0 / 0.06); max-width: 600px; }
        .meta { color: var(--text-muted); margin: 4px 0 0; }
        .field { margin-top: 18px; display: grid; gap: 8px; }
        input[type="file"] { padding: 10px; border: 1px solid var(--border); border-radius: 10px; background: var(--surface); }
        .actions { margin-top: 20px; display: flex; gap: 12px; }
        .btn { border: 0; border-radius: 999px; padding: 10px 18px; background: var(--primary-grad); color: #fff; font: inherit; font-weight: 500; cursor: pointer; text-decoration: none; transition: opacity 0.2s; }
        .btn:hover { opacity: 0.9; }
        .btn-secondary { background: var(--bg); color: var(--text); border: 1px solid var(--border); }
        .btn-secondary:hover { background: var(--border); }
        .hint { margin-top: 14px; color: var(--text-muted); font-size: 0.9rem; line-height: 1.5; }
    </style>
@endsection

@section('content')
    <div class="card">
        <h2>📤 Seadmete nimekirja üleslaadimine</h2>
        <p class="meta">{{ $object['address'] }} / {{ $object['city'] }}</p>

        <form method="post" action="{{ route('objects.devices.upload.store', $object['id']) }}" enctype="multipart/form-data">
            @csrf
            <div class="field">
                <label for="fileToUpload">Vali semikooloniga eraldatud fail</label>
                <input type="file" id="fileToUpload" name="fileToUpload" required>
            </div>

            <div class="actions">
                <button class="btn" type="submit">Laadi üles</button>
                <a class="btn btn-secondary" href="{{ route('objects.show', $object['id']) }}">Tühista</a>
            </div>

            <p class="hint">Asendab objekti kogu seadmete nimekirja. Vorming: id;devid;asukoht;tüüp (üks rida seadme kohta).</p>
        </form>
    </div>
@endsection
