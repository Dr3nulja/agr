<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'AGR Dashboard')</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600&display=swap">
    <style>
        :root {
            --primary: oklch(0.62 0.19 254);
            --primary-light: oklch(0.62 0.18 305);
            --primary-grad: linear-gradient(135deg, oklch(0.62 0.19 254), oklch(0.62 0.18 305));
            --bg: oklch(0.975 0.008 270);
            --surface: oklch(1 0 0);
            --text: oklch(0.24 0.02 270);
            --text-muted: oklch(0.5 0.02 270);
            --text-faint: oklch(0.62 0.02 270);
            --border: oklch(0.9 0.01 270);
            --danger: oklch(0.6 0.21 20);
            --good: oklch(0.68 0.15 155);
            --warn: oklch(0.72 0.15 65);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Sora', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        /* Навигация */
        .navbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 76px;
        }

        .navbar-brand {
            font-size: 1.2rem;
            font-weight: 500;
            letter-spacing: -0.01em;
            color: var(--text);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .navbar-logo {
            width: 38px;
            height: 38px;
            background: var(--primary-grad);
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 6px 16px -4px oklch(0.62 0.19 270 / 0.4);
        }

        .navbar-center {
            display: flex;
            gap: 6px;
            background: var(--bg);
            border-radius: 999px;
            padding: 4px;
            margin-left: 32px;
        }

        .navbar-link {
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.88rem;
            padding: 9px 20px;
            border-radius: 999px;
            transition: all 0.15s;
        }

        .navbar-link:hover {
            color: var(--text);
        }

        .navbar-link.active {
            color: var(--text);
            background: var(--surface);
            box-shadow: 0 1px 4px oklch(0 0 0 / 0.08);
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .navbar-user {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .navbar-user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--primary-grad);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            font-size: 1rem;
        }

        .navbar-user-info {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .navbar-user-name {
            font-weight: 500;
            font-size: 0.85rem;
            color: var(--text);
        }

        .navbar-user-role {
            font-size: 0.75rem;
            color: var(--text-faint);
        }

        .navbar-btn {
            padding: 8px 16px;
            border-radius: 999px;
            background: var(--bg);
            border: none;
            color: var(--danger);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s;
            font-size: 0.85rem;
            font-family: inherit;
        }

        .navbar-btn:hover {
            background: var(--danger);
            color: white;
        }

        /* Основной контент */
        .main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px 24px;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .navbar-content {
                padding: 0 16px;
            }

            .navbar-center {
                margin-left: 16px;
                gap: 2px;
            }

            .navbar-link {
                padding: 9px 12px;
            }

            .navbar-right {
                gap: 12px;
            }

            .navbar-user-info {
                display: none;
            }

            .main-content {
                padding: 16px 12px;
            }
        }

        @yield('extra-styles')
    </style>
</head>
<body>
    <!-- Навигация -->
    <nav class="navbar">
        <div class="navbar-content">
            <a href="{{ route('dashboard') }}" class="navbar-brand">
                <div class="navbar-logo">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 17V9l8-5 8 5v8"/><path d="M9 21v-6h6v6"/></svg>
                </div>
                <span>AGR</span>
            </a>

            <div class="navbar-center">
                <a href="{{ route('dashboard') }}" class="navbar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    Dashboard
                </a>
                <a href="{{ route('objects.index') }}" class="navbar-link {{ request()->routeIs('objects.*') ? 'active' : '' }}">
                    Objects
                </a>
                <a href="{{ route('tasks.index') }}" class="navbar-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}">
                    Задачи
                </a>
            </div>

            <div class="navbar-right">
                <div class="navbar-user">
                    <div class="navbar-user-avatar">
                        {{ substr(session('user')->name, 0, 1) }}
                    </div>
                    <div class="navbar-user-info">
                        <div class="navbar-user-name">{{ session('user')->name }}</div>
                        <div class="navbar-user-role">
                            {{ session('user')->role == 1 ? 'Admin' : 'User' }}
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                    @csrf
                    <button type="submit" class="navbar-btn">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Основной контент -->
    <div class="main-content">
        @yield('content')
    </div>

    @yield('extra-scripts')
</body>
</html>
