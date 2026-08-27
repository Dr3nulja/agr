@extends('layouts.app')

@section('title', 'SOE Flats - AGR')

@section('extra-styles')
    <style>
        .card { padding: 24px; border-radius: 16px; background: var(--surface); box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06); margin-bottom: 24px; }
        .meta { color: var(--text-muted); margin: 4px 0 0; }
        .actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 12px; }
        .btn { border: 0; border-radius: 10px; padding: 10px 16px; background: var(--primary); color: #fff; font: inherit; font-weight: 600; cursor: pointer; text-decoration: none; transition: background 0.2s; }
        .btn:hover { background: var(--primary-light); }
        .btn-secondary { background: var(--bg); color: var(--text); border: 1px solid var(--border); }
        .btn-secondary:hover { background: var(--border); }
        .btn-danger { background: var(--danger); }
        .btn-danger:hover { background: #b91c1c; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th, td { padding: 12px 10px; border-bottom: 1px solid var(--border); text-align: left; }
        th { color: var(--text-muted); font-weight: 600; font-size: 0.9rem; }
        .upload { margin-top: 16px; display: grid; gap: 10px; }
        input, select {
            width: 100%; padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border); font: inherit; background: var(--surface); color: var(--text); box-sizing: border-box;
        }
        .summary { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-top: 12px; }
        .stat { padding: 14px; border-radius: 12px; background: var(--bg); border: 1px solid var(--border); }
        .stat strong { display: block; font-size: 1.4rem; margin-top: 4px; }
        .mini { display: grid; grid-template-columns: 1fr 160px auto; gap: 10px; margin-top: 12px; align-items: end; }
        .flat-edit { display: grid; grid-template-columns: 1fr 160px auto; gap: 8px; align-items: center; min-width: 520px; }
        @media (max-width: 900px) { .grid-2 { grid-template-columns: 1fr; } }
    </style>
@endsection

@section('content')
    <div class="card">
        <h2>🏢 SOE flats</h2>
        <p class="meta">{{ $object['address'] }} / {{ $object['city'] }}</p>
        <div class="actions">
            <a class="btn" href="{{ route('objects.soe', $object['id']) }}">SOE settings</a>
            <a class="btn btn-secondary" href="{{ route('objects.show', $object['id']) }}">Back</a>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <h3>Upload flat list</h3>
            <form method="post" action="{{ route('objects.soe.flats.upload', $object['id']) }}" enctype="multipart/form-data" class="upload">
                @csrf
                <input type="file" name="fileToUpload" required>
                <button class="btn" type="submit">Upload</button>
            </form>
            <h3 style="margin-top:24px;">Add flat</h3>
            <form method="post" action="{{ route('objects.soe.flats.store', $object['id']) }}" class="mini">
                @csrf
                <div>
                    <label for="location"><strong>Apartment</strong></label>
                    <input id="location" type="text" name="location" required>
                </div>
                <div>
                    <label for="size"><strong>Area</strong></label>
                    <input id="size" type="number" name="size" step="0.01" min="0" required>
                </div>
                <button class="btn" type="submit">Add</button>
            </form>
        </div>

        <div class="card table-wrap">
            <h3>Flats</h3>
            <div class="summary">
                <div class="stat">
                    <span>Flats</span>
                    <strong>{{ $summary['flat_count'] }}</strong>
                </div>
                <div class="stat">
                    <span>Total area</span>
                    <strong>{{ $summary['total_area'] }}</strong>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Apartment</th>
                        <th>Area</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($flats as $flat)
                        <tr>
                            <td>
                                <form method="post" action="{{ route('objects.soe.flats.update', [$object['id'], $flat['id']]) }}" class="flat-edit">
                                    @csrf
                                    <input type="text" name="location" value="{{ $flat['location'] }}" required>
                                    <input type="number" name="size" step="0.01" min="0" value="{{ $flat['size'] }}" required>
                                    <button class="btn" type="submit">Save</button>
                                </form>
                            </td>
                            <td>
                                <form method="post" action="{{ route('objects.soe.flats.delete', [$object['id'], $flat['id']]) }}" style="margin:0;">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3">No flats loaded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card table-wrap">
        <h3>Radiators</h3>
        <form method="post" action="{{ route('objects.soe.radiators.upload', $object['id']) }}" enctype="multipart/form-data" class="upload" style="max-width: 420px; margin-bottom: 20px;">
            @csrf
            <input type="file" name="fileToUpload" required>
            <button class="btn" type="submit">Upload radiator/sensor list</button>
        </form>
        <form method="get" action="{{ route('objects.soe.flats', $object['id']) }}" style="margin-bottom:16px; max-width: 320px;">
            <label for="flat"><strong>Filter by apartment</strong></label>
            <select id="flat" name="flat" onchange="this.form.submit()">
                <option value="">All apartments</option>
                @foreach ($flats as $flat)
                    <option value="{{ $flat['location'] }}" @selected($selectedFlat === (string) $flat['location'])>{{ $flat['location'] }}</option>
                @endforeach
            </select>
        </form>
        <div class="summary" style="margin-bottom: 16px;">
            @forelse ($flatRadiatorSummary as $row)
                <div class="stat">
                    <span>{{ $row['flat_name'] }}</span>
                    <strong>{{ $row['radiator_count'] }} radiators</strong>
                    <small>Power: {{ $row['total_power'] }}, size: {{ $row['total_size'] }}</small>
                </div>
            @empty
                <div class="stat">
                    <span>No radiator summary</span>
                    <strong>0</strong>
                </div>
            @endforelse
        </div>
        <table>
            <thead>
                <tr>
                    <th>Apartment</th>
                    <th>Device</th>
                    <th>Power</th>
                    <th>Coeff</th>
                    <th>Size</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($radiators as $radiator)
                    <tr>
                        <td>{{ $radiator['location'] }}</td>
                        <td>{{ $radiator['devid'] }}</td>
                        <td>{{ $radiator['power'] }}</td>
                        <td>{{ $radiator['cof'] }}</td>
                        <td>{{ $radiator['size'] }}</td>
                        <td>{{ $radiator['description'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No radiators found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
