@extends('layouts.app')

@section('title', 'Dashboard - AGR')

@section('extra-styles')
    <style>
        .card {
            padding: 24px;
            border-radius: 16px;
            background: var(--surface);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
            margin-bottom: 24px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .stat-box {
            padding: 20px;
            border-radius: 12px;
            background: var(--bg);
            border: 1px solid var(--border);
            text-align: center;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        .stat-label {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-top: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        th {
            background: var(--bg);
            font-weight: 600;
            color: var(--text-muted);
        }
        tr:hover {
            background: var(--bg);
        }
        h2 {
            margin-top: 0;
            color: var(--text);
        }
    </style>
@endsection

@section('content')
    <div class="card">
        <h2>📊 Welcome back, {{ $user->name }}!</h2>
        <p style="color: var(--text-muted); margin: 0;">
            You are logged in as <strong>{{ $user->login }}</strong>
            @if($user->role === 1)
                <span style="background: var(--primary); color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem; margin-left: 8px;">ADMIN</span>
            @endif
        </p>
    </div>

    <div class="card">
        <h2>📈 Statistics</h2>
        <div class="stats">
            <div class="stat-box">
                <div class="stat-number">{{ $totalObjects }}</div>
                <div class="stat-label">Total Objects</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">{{ $activeObjects }}</div>
                <div class="stat-label">Active</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">{{ $inactiveObjects ?? 0 }}</div>
                <div class="stat-label">Inactive</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">{{ $offlineObjects }}</div>
                <div class="stat-label">Offline</div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>📋 Recent Logs</h2>
        @if($recentLogs->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th>Message</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentLogs as $log)
                        <tr>
                            <td>{{ $log->Content }}</td>
                            <td>{{ $log->created_at->format('M d, H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color: var(--text-muted);">No logs yet</p>
        @endif
    </div>
@endsection