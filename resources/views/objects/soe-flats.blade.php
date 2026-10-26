@extends('layouts.app')

@section('title', 'SOE korterid - AGR')

@section('extra-styles')
    <style>
        .card { padding: 24px; border-radius: 18px; background: var(--surface); box-shadow: 0 2px 10px -2px oklch(0 0 0 / 0.06); margin-bottom: 24px; }
        .meta { color: var(--text-muted); margin: 4px 0 0; }
        .actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 12px; }
        .btn { border: 0; border-radius: 999px; padding: 10px 18px; background: var(--primary-grad); color: #fff; font: inherit; font-weight: 500; cursor: pointer; text-decoration: none; transition: opacity 0.2s; }
        .btn:hover { opacity: 0.9; }
        .btn-secondary { background: var(--bg); color: var(--text); border: 1px solid var(--border); }
        .btn-secondary:hover { background: var(--border); }
        .btn-danger { background: var(--danger); }
        .btn-danger:hover { background: #b91c1c; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th, td { padding: 12px 10px; border-bottom: 1px solid var(--border); text-align: left; }
        th { color: var(--text-muted); font-weight: 500; font-size: 0.9rem; }
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
        <h2>🏢 SOE korterid</h2>
        <p class="meta">{{ $object['address'] }} / {{ $object['city'] }}</p>
        <div class="actions">
            <a class="btn" href="{{ route('objects.soe', $object['id']) }}">SOE seaded</a>
            <a class="btn btn-secondary" href="{{ route('objects.show', $object['id']) }}">Tagasi</a>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <h3>Laadi korterite nimekiri</h3>
            <form method="post" action="{{ route('objects.soe.flats.upload', $object['id']) }}" enctype="multipart/form-data" class="upload">
                @csrf
                <input type="file" name="fileToUpload" required>
                <button class="btn" type="submit">Laadi üles</button>
            </form>
            <h3 style="margin-top:24px;">Lisa korter</h3>
            <form method="post" action="{{ route('objects.soe.flats.store', $object['id']) }}" class="mini">
                @csrf
                <div>
                    <label for="location"><strong>Korter</strong></label>
                    <input id="location" type="text" name="location" required>
                </div>
                <div>
                    <label for="size"><strong>Pindala</strong></label>
                    <input id="size" type="number" name="size" step="0.01" min="0" required>
                </div>
                <button class="btn" type="submit">Lisa</button>
            </form>
        </div>

        <div class="card table-wrap">
            <h3>Korterid</h3>
            <div class="summary">
                <div class="stat">
                    <span>Korterid</span>
                    <strong>{{ $summary['flat_count'] }}</strong>
                </div>
                <div class="stat">
                    <span>Pindala kokku</span>
                    <strong>{{ $summary['total_area'] }}</strong>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Korter</th>
                        <th>Pindala</th>
                        <th>Toimingud</th>
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
                                    <button class="btn" type="submit">Salvesta</button>
                                </form>
                            </td>
                            <td>
                                <form method="post" action="{{ route('objects.soe.flats.delete', [$object['id'], $flat['id']]) }}" style="margin:0;">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-danger" type="submit">Kustuta</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3">Kortereid pole lisatud.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card table-wrap">
        <h3>Radiaatorid</h3>
        <form method="post" action="{{ route('objects.soe.radiators.upload', $object['id']) }}" enctype="multipart/form-data" class="upload" style="max-width: 420px; margin-bottom: 20px;">
            @csrf
            <input type="file" name="fileToUpload" required>
            <button class="btn" type="submit">Laadi radiaatorite/jaoturite nimekiri</button>
        </form>
        <form method="get" action="{{ route('objects.soe.flats', $object['id']) }}" style="margin-bottom:16px; max-width: 320px;">
            <label for="flat"><strong>Filtreeri korteri järgi</strong></label>
            <select id="flat" name="flat" onchange="this.form.submit()">
                <option value="">Kõik korterid</option>
                @foreach ($flats as $flat)
                    <option value="{{ $flat['location'] }}" @selected($selectedFlat === (string) $flat['location'])>{{ $flat['location'] }}</option>
                @endforeach
            </select>
        </form>
        <div class="summary" style="margin-bottom: 16px;">
            @forelse ($flatRadiatorSummary as $row)
                <div class="stat">
                    <span>{{ $row['flat_name'] }}</span>
                    <strong>{{ $row['radiator_count'] }} radiaatorit</strong>
                    <small>Võimsus: {{ $row['total_power'] }}, suurus: {{ $row['total_size'] }}</small>
                </div>
            @empty
                <div class="stat">
                    <span>Radiaatoreid pole</span>
                    <strong>0</strong>
                </div>
            @endforelse
        </div>
        <table>
            <thead>
                <tr>
                    <th>Korter</th>
                    <th>Seade</th>
                    <th>Võimsus</th>
                    <th>Koef.</th>
                    <th>Suurus</th>
                    <th>Kirjeldus</th>
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
                    <tr><td colspan="6">Radiaatoreid ei leitud.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
