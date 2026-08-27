@extends('layouts.app')

@section('title', 'Import CSV - AGR')

@section('extra-styles')
    <style>
        .card { padding: 24px; border-radius: 16px; background: var(--surface); box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06); max-width: 600px; margin-bottom: 20px; }
        .meta { color: var(--text-muted); margin: 4px 0 0; }
        .field { margin-top: 16px; display: grid; gap: 8px; }
        input { padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--surface); color: var(--text); font: inherit; }
        input[type="file"] { padding: 8px; }
        .actions { margin-top: 20px; display: flex; gap: 12px; }
        .btn { border: 0; border-radius: 8px; padding: 10px 16px; background: var(--primary); color: #fff; font: inherit; font-weight: 600; cursor: pointer; text-decoration: none; transition: background 0.2s; }
        .btn:hover { background: var(--primary-light); }
        .btn-secondary { background: var(--bg); color: var(--text); border: 1px solid var(--border); }
        .btn-secondary:hover { background: var(--border); }
    </style>
@endsection

@section('content')
    <p class="meta" style="margin-bottom: 16px;">{{ $object['address'] }} / {{ $object['city'] }}</p>

    <div class="card">
        <h3>💧 Import Apator Water CSV</h3>
        <form method="post" action="{{ route('objects.import-csv.apator-water', $object['id']) }}" enctype="multipart/form-data">
            @csrf
            <div class="field">
                <label for="dtime">Date and time</label>
                <input type="datetime-local" id="dtime" name="dtime" required>
            </div>
            <div class="field">
                <label for="apatorWaterFile">Apator WATER CSV file</label>
                <input type="file" id="apatorWaterFile" name="fileToUpload" required>
            </div>
            <div class="actions"><button class="btn" type="submit">Upload</button></div>
        </form>
    </div>

    <div class="card">
        <h3>🔥 Import Apator Allocator CSV</h3>
        <form method="post" action="{{ route('objects.import-csv.apator-heater', $object['id']) }}" enctype="multipart/form-data">
            @csrf
            <div class="field">
                <label for="apatorHeaterFile">Apator ALLOCATOR CSV file</label>
                <input type="file" id="apatorHeaterFile" name="fileToUpload" required>
            </div>
            <div class="actions"><button class="btn" type="submit">Upload</button></div>
        </form>
    </div>

    <div class="card">
        <h3>⚙️ Import Siemens CSV</h3>
        <form method="post" action="{{ route('objects.import-csv.siemens', $object['id']) }}" enctype="multipart/form-data">
            @csrf
            <div class="field">
                <label for="siemensFile">SIEMENS CSV file</label>
                <input type="file" id="siemensFile" name="fileToUpload" required>
            </div>
            <div class="actions"><button class="btn" type="submit">Upload</button></div>
        </form>
    </div>

    <a class="btn btn-secondary" href="{{ route('objects.show', $object['id']) }}">← Back</a>
@endsection
