<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'AGR Dashboard')</title>
    <style>
        :root {
            --primary: #0f766e;
            --primary-light: #14b8a6;
            --bg: #f5f7fb;
            --surface: #fff;
            --text: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --danger: #dc2626;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        /* Навигация */
        .navbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
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
            height: 70px;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .navbar-logo {
            width: 32px;
            height: 32px;
            background: var(--primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .navbar-center {
            display: flex;
            gap: 32px;
            flex: 1;
            margin-left: 48px;
        }

        .navbar-link {
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            padding: 8px 0;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }

        .navbar-link:hover,
        .navbar-link.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .navbar-user {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .navbar-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .navbar-user-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .navbar-user-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text);
        }

        .navbar-user-role {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .navbar-btn {
            padding: 8px 16px;
            border-radius: 8px;
            background: var(--bg);
            border: none;
            color: var(--danger);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9rem;
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
                margin-left: 24px;
                gap: 16px;
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
                <div class="navbar-logo">⚙️</div>
                <span>AGR</span>
            </a>

            <div class="navbar-center">
                <a href="{{ route('dashboard') }}" class="navbar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    📊 Dashboard
                </a>
                <a href="{{ route('objects.index') }}" class="navbar-link {{ request()->routeIs('objects.*') ? 'active' : '' }}">
                    🏠 Objects
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
