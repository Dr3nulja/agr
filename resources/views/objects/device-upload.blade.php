@extends('layouts.app')

@section('title', 'Upload Device List - AGR')

@section('extra-styles')
    <style>
        .card { padding: 24px; border-radius: 16px; background: var(--surface); box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06); max-width: 600px; }
        .meta { color: var(--text-muted); margin: 4px 0 0; }
        .field { margin-top: 18px; display: grid; gap: 8px; }
        input[type="file"] { padding: 10px; border: 1px solid var(--border); border-radius: 10px; background: var(--surface); }
        .actions { margin-top: 20px; display: flex; gap: 12px; }
        .btn { border: 0; border-radius: 10px; padding: 10px 16px; background: var(--primary); color: #fff; font: inherit; font-weight: 600; cursor: pointer; text-decoration: none; transition: background 0.2s; }
        .btn:hover { background: var(--primary-light); }
        .btn-secondary { background: var(--bg); color: var(--text); border: 1px solid var(--border); }
        .btn-secondary:hover { background: var(--border); }
        .hint { margin-top: 14px; color: var(--text-muted); font-size: 0.9rem; line-height: 1.5; }
    </style>
@endsection

@section('content')
    <div class="card">
        <h2>📤 Upload device list</h2>
        <p class="meta">{{ $object['address'] }} / {{ $object['city'] }}</p>

        <form method="post" action="{{ route('objects.devices.upload.store', $object['id']) }}" enctype="multipart/form-data">
            @csrf
            <div class="field">
                <label for="fileToUpload">Choose semicolon-separated file</label>
                <input type="file" id="fileToUpload" name="fileToUpload" required>
            </div>

            <div class="actions">
                <button class="btn" type="submit">Upload</button>
                <a class="btn btn-secondary" href="{{ route('objects.show', $object['id']) }}">Cancel</a>
            </div>

            <p class="hint">Replaces the full device list for this object. Format: id;devid;location;type (one per line).</p>
        </form>
    </div>
@endsection
