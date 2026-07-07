@extends('layouts.app')

@section('title', 'Profile - AGR')

@section('extra-styles')
    <style>
        .panel {
            background: var(--surface);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
            max-width: 600px;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--border);
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 2rem;
            flex-shrink: 0;
        }

        .profile-info h2 {
            margin: 0;
            color: var(--text);
        }

        .profile-info p {
            margin: 4px 0 0;
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .info-group {
            margin-bottom: 20px;
        }

        .info-group-title {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 12px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .info-value {
            color: var(--text);
            font-weight: 600;
            font-size: 0.95rem;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-admin {
            background: var(--primary);
            color: white;
        }

        .badge-user {
            background: var(--bg);
            color: var(--text);
        }

        .actions {
            margin-top: 24px;
            display: flex;
            gap: 12px;
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
    </style>
@endsection

@section('content')
    <div class="panel">
        <div class="profile-header">
            <div class="profile-avatar">{{ substr($user->name, 0, 1) }}</div>
            <div class="profile-info">
                <h2>{{ $user->name }}</h2>
                <p>{{ $user->login }}</p>
            </div>
        </div>

        <div class="info-group">
            <div class="info-group-title">Account Information</div>
            <div class="info-row">
                <span class="info-label">Full Name</span>
                <span class="info-value">{{ $user->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Login</span>
                <span class="info-value">{{ $user->login }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Role</span>
                <span class="info-value">
                    @if($user->role === 1)
                        <span class="badge badge-admin">Administrator</span>
                    @else
                        <span class="badge badge-user">User</span>
                    @endif
                </span>
            </div>
        </div>

        <div class="info-group">
            <div class="info-group-title">System Information</div>
            <div class="info-row">
                <span class="info-label">User ID</span>
                <span class="info-value">#{{ $user->id }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Session Status</span>
                <span class="info-value" style="color: #059669;">✓ Active</span>
            </div>
        </div>

        <div class="actions">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
    </div>
@endsection
