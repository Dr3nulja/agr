<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOE Flats</title>
    <style>
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f5f7fb; color: #0f172a; }
        .page { max-width: 1400px; margin: 0 auto; padding: 24px; }
        .panel { background: #fff; border-radius: 20px; padding: 24px; box-shadow: 0 12px 36px rgba(15, 23, 42, 0.08); margin-bottom: 18px; }
        .meta { color: #64748b; }
        .actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 12px; }
        .btn { border: 0; border-radius: 14px; padding: 12px 16px; background: #0f766e; color: #fff; font: inherit; font-weight: 700; cursor: pointer; text-decoration: none; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th, td { padding: 12px 10px; border-bottom: 1px solid #e5ebf0; text-align: left; }
        th { color: #475569; }
        .upload { margin-top: 16px; display: grid; gap: 10px; }
        input[type="file"] { padding: 12px; border: 1px solid #dbe4ea; border-radius: 14px; background: #fff; }
        select { width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid #dbe4ea; font: inherit; background: #fff; }
        input[type="text"], input[type="number"] { width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid #dbe4ea; font: inherit; background: #fff; box-sizing: border-box; }
        .summary { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-top: 12px; }
        .stat { padding: 14px; border-radius: 16px; background: #f8fafc; border: 1px solid #e5ebf0; }
        .stat strong { display: block; font-size: 1.4rem; margin-top: 4px; }
        .mini { display: grid; grid-template-columns: 1fr 160px auto; gap: 10px; margin-top: 12px; align-items: end; }
        .ghost { background: #475569; }
        .danger { background: #b91c1c; }
        .row-actions { display: flex; gap: 8px; align-items: center; }
        .flat-edit {
            display: grid;
            grid-template-columns: 1fr 160px auto;
            gap: 8px;
            align-items: center;
            min-width: 520px;
        }
        .flat-edit input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #dbe4ea;
            border-radius: 12px;
            font: inherit;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="panel">
            <h1>SOE flats</h1>
            <p class="meta">{{ $object['address'] }} / {{ $object['city'] }}</p>
            <div class="actions">
                <a class="btn" href="{{ route('objects.soe', $object['id']) }}">SOE settings</a>
                <a class="btn" href="{{ route('objects.show', $object['id']) }}" style="background:#475569;">Back</a>
            </div>
        </div>

        <div class="grid">
            <div class="panel">
                <h2>Upload flat list</h2>
                <form method="post" action="{{ route('objects.soe.flats.upload', $object['id']) }}" enctype="multipart/form-data" class="upload">
                    @csrf
                    <input type="file" name="fileToUpload" required>
                    <button class="btn" type="submit">Upload</button>
                </form>
                <h3 style="margin-top:24px;">Add flat</h3>
                <form method="post" action="{{ route('objects.soe.flats.store', $object['id']) }}" class="mini">
                    @csrf
                    <div>
                        <label for="location"><strong>Apartment</strong></label><br>
                        <input id="location" type="text" name="location" required>
                    </div>
                    <div>
                        <label for="size"><strong>Area</strong></label><br>
                        <input id="size" type="number" name="size" step="0.01" min="0" required>
                    </div>
                    <button class="btn" type="submit">Add</button>
                </form>
            </div>

            <div class="panel table-wrap">
                <h2>Flats</h2>
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
                                        <button class="btn danger" type="submit">Delete</button>
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

        <div class="panel table-wrap">
            <h2>Radiators</h2>
            <form method="get" action="{{ route('objects.soe.flats', $object['id']) }}" style="margin-bottom:16px;">
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
    </div>
</body>
</html>