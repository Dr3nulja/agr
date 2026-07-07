@extends('layouts.app')

@section('title', 'Create Object - AGR')

@section('extra-styles')
    <style>
        .panel {
            background: var(--surface);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
            max-width: 800px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-top: 20px;
        }

        .field {
            display: grid;
            gap: 8px;
        }

        label {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text);
        }

        input, textarea, select {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 12px;
            font: inherit;
            background: var(--bg);
            color: var(--text);
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--surface);
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .span-2 { grid-column: span 2; }

        .actions {
            margin-top: 24px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 16px;
            border-radius: 8px;
            border: 0;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-light);
        }

        .btn-secondary {
            background: var(--bg);
            color: var(--text);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--border);
        }

        .error-message {
            margin-bottom: 18px;
            padding: 12px 16px;
            border-radius: 8px;
            background: #fef2f2;
            color: var(--danger);
            border: 1px solid #fee2e2;
        }

        @media (max-width: 800px) {
            .grid { grid-template-columns: 1fr; }
            .span-2 { grid-column: auto; }
        }
    </style>
@endsection

@section('content')
    <div class="panel">
        <h2>➕ Create New Object</h2>
        <p style="color: var(--text-muted); margin-top: 4px;">Add a new object to the system</p>

        @if ($errors->any())
            <div class="error-message">{{ $errors->first() }}</div>
        @endif

        <form method="post" action="{{ route('objects.store') }}">
            @csrf
            <div class="grid">
                <div class="field"><label>Address *</label><input name="address" value="{{ old('address') }}" placeholder="Street address" required></div>
                <div class="field"><label>City *</label><input name="City" value="{{ old('City') }}" placeholder="City name" required></div>
                <div class="field"><label>Contact Person</label><input name="Contact" value="{{ old('Contact') }}" placeholder="Name"></div>
                <div class="field"><label>IMEI *</label><input name="IMEI" value="{{ old('IMEI') }}" placeholder="Device IMEI" required></div>
                <div class="field"><label>IMEI 2</label><input name="IMEI2" value="{{ old('IMEI2') }}" placeholder="Second IMEI"></div>
                <div class="field"><label>GSM Number</label><input name="GSMNR" value="{{ old('GSMNR') }}" placeholder="Phone number"></div>
                <div class="field"><label>GSM Number 2</label><input name="GSMNR2" value="{{ old('GSMNR2') }}" placeholder="Secondary phone number"></div>
                <div class="field"><label>GSM Serial</label><input name="GSMSERIAL" value="{{ old('GSMSERIAL') }}" placeholder="GSM serial"></div>
                <div class="field"><label>GSM Serial 2</label><input name="GSMSERIAL2" value="{{ old('GSMSERIAL2') }}" placeholder="Secondary GSM serial"></div>
                <div class="field"><label>PIN 1</label><input name="pin1" value="{{ old('pin1') }}" placeholder="PIN 1"></div>
                <div class="field"><label>PIN 2</label><input name="pin2" value="{{ old('pin2') }}" placeholder="PIN 2"></div>
                <div class="field"><label>PUK 1</label><input name="puk1" value="{{ old('puk1') }}" placeholder="PUK 1"></div>
                <div class="field"><label>PUK 2</label><input name="puk2" value="{{ old('puk2') }}" placeholder="PUK 2"></div>
                <div class="field"><label>Device Quantity</label><input name="Devqtty" type="number" min="0" value="{{ old('Devqtty', 1) }}"></div>
                <div class="field"><label>Radio Device Quantity</label><input name="RadioDevQty" type="number" min="0" value="{{ old('RadioDevQty', 0) }}"></div>
                <div class="field"><label>Main Radio Location</label><input name="MainRadio" type="number" min="0" value="{{ old('MainRadio', 0) }}"></div>
                <div class="field"><label>Key Code</label><input name="KeyCode" value="{{ old('KeyCode') }}"></div>
                <div class="field"><label>Manager</label><input name="manager" type="number" min="0" value="{{ old('manager', 1) }}"></div>
                <div class="field"><label>Package</label><input name="packet" value="{{ old('packet') }}" placeholder="Package"></div>
                <div class="field"><label>Traffic</label><input name="traffic" value="{{ old('traffic') }}" placeholder="Traffic plan"></div>
                <div class="field"><label>Call Count</label><input name="callCnt" type="number" min="0" value="{{ old('callCnt', 0) }}"></div>
                <div class="field"><label>Sum</label><input name="summ" type="number" step="0.01" min="0" value="{{ old('summ', 0) }}"></div>
                <div class="field"><label>Latitude</label><input name="lat" type="number" step="0.000001" value="{{ old('lat') }}"></div>
                <div class="field"><label>Longitude</label><input name="lon" type="number" step="0.000001" value="{{ old('lon') }}"></div>
                <div class="field"><label>Company / Installer</label><input name="Company" type="number" min="1" value="{{ old('Company', 1) }}"></div>
                <div class="field"><label>System</label>
                    <select name="dtype">
                        <option value="1" {{ old('dtype') == '1' ? 'selected' : '' }}>💧 Water</option>
                        <option value="2" {{ old('dtype') == '2' ? 'selected' : '' }}>🔥 Gas</option>
                        <option value="3" {{ old('dtype') == '3' ? 'selected' : '' }}>⚡ Electric</option>
                    </select>
                </div>
                <div class="field"><label>Status</label>
                    <select name="status">
                        <option value="1" {{ old('status', '1') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="2" {{ old('status') == '2' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="field"><label class="checkbox-label"><input type="checkbox" name="saveHval" value="1" {{ old('saveHval') ? 'checked' : '' }}> Save month statistics</label></div>
                <div class="field span-2"><label>Description</label><textarea name="Description" placeholder="Object description...">{{ old('Description') }}</textarea></div>
                <div class="field span-2"><label>Second description</label><textarea name="Description2" placeholder="Additional notes...">{{ old('Description2') }}</textarea></div>
            </div>

            <div class="actions">
                <button class="btn btn-primary" type="submit">✅ Create Object</button>
                <a class="btn btn-secondary" href="{{ route('objects.index') }}">← Cancel</a>
            </div>
        </form>
    </div>
@endsection