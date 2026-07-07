@extends('layouts.app')

@section('title', 'View Object - AGR')

@section('extra-styles')
    <style>
        .panel {
            background: var(--surface);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
            margin-bottom: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }

        .info-box {
            padding: 12px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .info-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text);
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 16px;
            border-radius: 8px;
            border: 0;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            transition: all 0.2s;
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

        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-top: 24px;
            margin-bottom: 12px;
            color: var(--text);
        }

        .placeholder {
            padding: 24px;
            text-align: center;
            background: var(--bg);
            border: 2px dashed var(--border);
            border-radius: 8px;
            color: var(--text-muted);
        }
    </style>
@endsection

@section('content')
    <div class="panel">
        <h2>{{ $object['address'] }}</h2>
        <p style="color: var(--text-muted); margin-top: 4px;">Object Details</p>

        <div class="info-grid">
            <div class="info-box">
                <div class="info-label">City</div>
                <div class="info-value">{{ $object['City'] }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Type</div>
                <div class="info-value">
                    @if($object['dtype'] == 1) 💧 Water
                    @elseif($object['dtype'] == 2) 🔥 Gas
                    @elseif($object['dtype'] == 3) ⚡ Electric
                    @else Type {{ $object['dtype'] }} @endif
                </div>
            </div>
            <div class="info-box">
                <div class="info-label">Status</div>
                <div class="info-value" style="color: {{ $object['status'] == 1 ? '#059669' : '#dc2626' }};">
                    {{ $object['status'] == 1 ? '● Active' : '● Inactive' }}
                </div>
            </div>
        </div>

        <div class="action-buttons">
            @if(session('user')->role == 1)
                <a href="{{ route('objects.edit', $object['id']) }}" class="btn btn-primary">✏️ Edit</a>
            @endif
            <a href="{{ route('objects.index') }}" class="btn btn-secondary">← Back to List</a>
        </div>
    </div>

    <div class="panel">
        <h3 class="section-title">⚙️ Device Management</h3>
        <div class="placeholder">
            Device management functionality coming soon...
        </div>
    </div>

    <div class="panel">
        <h3 class="section-title">📊 State of Energy (SOE)</h3>
        <div class="placeholder">
            SOE tracking and visualization coming soon...
        </div>
    </div>

    <div class="panel">
        <h3 class="section-title">💾 Export Data</h3>
        <div class="placeholder">
            Export functionality (CSV, PDF) coming soon...
        </div>
    </div>

    <div class="panel">
        <h3 class="section-title">🎮 Commands</h3>
        <div class="placeholder">
            Device command interface coming soon...
        </div>
    </div>
@endsection