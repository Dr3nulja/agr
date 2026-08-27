@extends('layouts.app')

@section('title', 'SOE Settings - AGR')

@section('extra-styles')
    <style>
        .card { padding: 24px; border-radius: 18px; background: var(--surface); box-shadow: 0 2px 10px -2px oklch(0 0 0 / 0.06); margin-bottom: 24px; }
        .meta { color: var(--text-muted); margin: 4px 0 0; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-top: 20px; }
        .field { display: grid; gap: 8px; }
        .field label { font-weight: 500; color: var(--text-muted); font-size: 0.9rem; }
        input, select {
            width: 100%; border: 1px solid var(--border); border-radius: 10px; padding: 10px 12px; font: inherit; background: var(--surface); color: var(--text); box-sizing: border-box;
        }
        .span-2 { grid-column: span 2; }
        .actions { margin-top: 20px; display: flex; gap: 12px; flex-wrap: wrap; }
        .btn {
            border: 0; border-radius: 999px; padding: 10px 18px; background: var(--primary-grad); color: #fff; font: inherit; font-weight: 500; cursor: pointer; text-decoration: none; transition: opacity 0.2s;
        }
        .btn:hover { opacity: 0.9; }
        .btn-secondary { background: var(--bg); color: var(--text); border: 1px solid var(--border); }
        .btn-secondary:hover { background: var(--border); }
        .toggle { display: flex; gap: 18px; flex-wrap: wrap; }
        .toggle label { font-weight: 500; color: var(--text); font-size: 0.9rem; }
        .errors { margin-bottom: 18px; padding: 14px 16px; border-radius: 10px; background: #fef2f2; color: var(--danger); }
        @media (max-width: 800px) { .grid { grid-template-columns: 1fr; } .span-2 { grid-column: auto; } }
    </style>
@endsection

@section('content')
    <div class="card">
        <h2>⚙️ SOE settings</h2>
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
                <a class="btn btn-secondary" href="{{ route('objects.show', $object['id']) }}">Cancel</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>🧮 Calculate Costs</h2>
        <p class="meta">Split this month's payment between apartments by area and radiator sensor readings.</p>
        <form method="get" action="{{ route('objects.soe.report.xlsx', $object['id']) }}" id="soeCalcForm" style="display:flex; gap:16px; align-items:end; flex-wrap:wrap; margin-top:16px;">
            <div class="field">
                <label for="maks">Payment amount</label>
                <input id="maks" name="maks" type="number" step="0.01" min="0" required>
            </div>
            <div class="field">
                <label for="kuu">Month</label>
                <select id="kuu" name="kuu" required>
                    <option value="1">January</option>
                    <option value="2">February</option>
                    <option value="3">March</option>
                    <option value="4">April</option>
                    <option value="5">May</option>
                    <option value="6">June</option>
                    <option value="7">July</option>
                    <option value="8">August</option>
                    <option value="9">September</option>
                    <option value="10">October</option>
                    <option value="11">November</option>
                    <option value="12">December</option>
                </select>
            </div>
            <button class="btn" type="submit">Calculate (XLSX)</button>
            <button class="btn btn-secondary" type="submit" formaction="{{ route('objects.soe.report.csv', $object['id']) }}">Calculate (CSV)</button>
        </form>
    </div>
@endsection
