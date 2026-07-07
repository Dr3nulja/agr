<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOE Settings</title>
    <style>
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f5f7fb; color: #0f172a; }
        .page { max-width: 1100px; margin: 0 auto; padding: 24px; }
        .panel { background: #fff; border-radius: 20px; padding: 24px; box-shadow: 0 12px 36px rgba(15, 23, 42, 0.08); }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .field { display: grid; gap: 8px; }
        label { font-weight: 700; color: #475569; }
        input, select {
            width: 100%; border: 1px solid #dbe4ea; border-radius: 14px; padding: 12px 14px; font: inherit; background: #fff;
        }
        .span-2 { grid-column: span 2; }
        .actions { margin-top: 20px; display: flex; gap: 12px; flex-wrap: wrap; }
        .btn {
            border: 0; border-radius: 14px; padding: 12px 16px; background: #0f766e; color: #fff; font: inherit; font-weight: 700; cursor: pointer; text-decoration: none;
        }
        .toggle { display: flex; gap: 18px; flex-wrap: wrap; }
        .toggle label { font-weight: 600; color: #0f172a; }
        .meta { color: #64748b; margin: 8px 0 0; }
        .errors { margin-bottom: 18px; padding: 14px 16px; border-radius: 14px; background: #fef2f2; color: #b91c1c; }
        @media (max-width: 800px) { .grid { grid-template-columns: 1fr; } .span-2 { grid-column: auto; } }
    </style>
</head>
<body>
    <div class="page">
        <div class="panel">
            <h1>SOE settings</h1>
            <p class="meta">{{ $object['address'] }} / {{ $object['city'] }}</p>

            @if ($errors->any())
                <div class="errors">{{ $errors->first() }}</div>
            @endif

            <form method="post" action="{{ route('objects.soe.save', $object['id']) }}">
                @csrf
                <div class="grid">
                    <div class="field span-2">
                        <label>Cost distribution</label>
                        <div class="toggle">
                            <label><input type="radio" name="m2_source" value="50" {{ (int) old('m2_source', $settings['m2_source']) === 50 ? 'checked' : '' }}> 50% m2 / 50% sensor</label>
                            <label><input type="radio" name="m2_source" value="100" {{ (int) old('m2_source', $settings['m2_source']) === 100 ? 'checked' : '' }}> sensor only</label>
                            <label><input type="radio" name="m2_source" value="0" {{ (int) old('m2_source', $settings['m2_source']) === 0 ? 'checked' : '' }}> m2 only</label>
                        </div>
                    </div>

                    <div class="field"><label for="kuludm2">m2 cost</label><input id="kuludm2" name="kuludm2" type="number" min="0" value="{{ old('kuludm2', $settings['kuludm2']) }}"></div>
                    <div class="field"><label for="lisamaks">Extra fee</label><input id="lisamaks" name="lisamaks" type="number" step="0.01" value="{{ old('lisamaks', $settings['lisamaks']) }}"></div>

                    <div class="field span-2">
                        <label>Options</label>
                        <div class="toggle">
                            <label><input type="checkbox" name="lisamaksen" {{ old('lisamaksen', $settings['lisamaksen']) ? 'checked' : '' }}> Add fee</label>
                            <label><input type="checkbox" name="eraldileht" {{ old('eraldileht', $settings['eraldileht']) ? 'checked' : '' }}> Separate sheet</label>
                            <label><input type="checkbox" name="ParamKulu" {{ old('ParamKulu', $settings['ParamKulu']) ? 'checked' : '' }}> Parameter cost</label>
                            <label><input type="checkbox" name="AlgLopp" {{ old('AlgLopp', $settings['AlgLopp']) ? 'checked' : '' }}> Start/end mode</label>
                        </div>
                    </div>

                    <div class="field"><label>Command</label>
                        <select name="command">
                            <option value="0">No command</option>
                            <option value="1">Restart</option>
                            <option value="2">Reload devices list</option>
                            <option value="3">Send data from memory</option>
                        </select>
                    </div>
                </div>

                <div class="actions">
                    <button class="btn" type="submit">Save</button>
                    <a class="btn" href="{{ route('objects.show', $object['id']) }}" style="background:#475569;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>