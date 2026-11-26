<!DOCTYPE html>
<html lang="et">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <title>@yield('title', 'AGR')</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
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
        /* Поле выбора файла не шире экрана (на телефоне «Выбор файла» вылезал за край) */
        input[type="file"] { max-width: 100%; }

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

        .navbar-burger {
            display: none;
            width: 40px;
            height: 40px;
            border: 1px solid var(--border);
            border-radius: 11px;
            background: var(--surface);
            color: var(--text);
            font-size: 1.2rem;
            line-height: 1;
            cursor: pointer;
        }

        /* Адаптивность: меню целиком не помещается — ссылки уходят под кнопку ☰ */
        @media (max-width: 1100px) {
            .navbar-burger {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .navbar-center {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                margin: 0;
                padding: 8px 12px 12px;
                flex-direction: column;
                gap: 2px;
                border-radius: 0;
                background: var(--surface);
                border-bottom: 1px solid var(--border);
                box-shadow: 0 12px 24px -12px oklch(0 0 0 / 0.18);
            }

            .navbar.menu-open .navbar-center {
                display: flex;
            }

            .navbar-link {
                padding: 12px 16px;
                font-size: 0.95rem;
            }

            .navbar-link.active {
                background: var(--bg);
                box-shadow: none;
            }

            .navbar-right {
                gap: 10px;
            }
        }

        @media (max-width: 768px) {
            .navbar-content {
                padding: 0 12px;
                height: 60px;
            }

            .navbar-logo {
                width: 34px;
                height: 34px;
            }

            .navbar-user-info {
                display: none;
            }

            .navbar-user-avatar {
                width: 34px;
                height: 34px;
            }

            .navbar-btn {
                padding: 8px 12px;
            }

            .main-content {
                padding: 12px 10px;
            }

            /* 16px — иначе iOS увеличивает страницу при фокусе на поле */
            input, select, textarea {
                font-size: 16px !important;
            }
        }

    </style>
    {{-- Страницы передают сюда свой <style>; внутри общего <style> первое правило ломалось --}}
    @yield('extra-styles')
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
                    Töölaud
                </a>
                <a href="{{ route('objects.index') }}" class="navbar-link {{ request()->routeIs('objects.*') ? 'active' : '' }}">
                    Objektid
                </a>
                <a href="{{ route('tasks.index') }}" class="navbar-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}">
                    Ülesanded
                </a>
                <a href="{{ route('installations.index') }}" class="navbar-link {{ request()->routeIs('installations.*') ? 'active' : '' }}">
                    Paigaldus
                </a>
                <a href="{{ route('maintenance.index') }}" class="navbar-link {{ request()->routeIs('maintenance.*') ? 'active' : '' }}">
                    Hooldus
                </a>
                @php($openLeaks = \Illuminate\Support\Facades\Schema::hasTable('leak_alerts') ? \App\Models\LeakAlert::open()->count() : 0)
                <a href="{{ route('leaks.index') }}" class="navbar-link {{ request()->routeIs('leaks.*') ? 'active' : '' }}">
                    Lekked
                    @if($openLeaks)
                        <span style="display:inline-block; min-width:18px; padding:1px 6px; border-radius:999px; background:oklch(0.6 0.2 25); color:white; font-size:0.72rem; text-align:center;">{{ $openLeaks }}</span>
                    @endif
                </a>
                <a href="{{ route('sims.index') }}" class="navbar-link {{ request()->routeIs('sims.*') ? 'active' : '' }}">
                    SIM-kaardid
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
                            {{ session('user')->role == 1 ? 'Admin' : 'Kasutaja' }}
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                    @csrf
                    <button type="submit" class="navbar-btn">Logi välja</button>
                </form>

                <button type="button" class="navbar-burger" aria-label="Menüü" aria-expanded="false"
                    onclick="var open = this.closest('.navbar').classList.toggle('menu-open'); this.setAttribute('aria-expanded', open); this.textContent = open ? '✕' : '☰';">☰</button>
            </div>
        </div>
    </nav>

    <!-- Основной контент -->
    <div class="main-content">
        @yield('content')
    </div>

    @yield('extra-scripts')

    {{-- Даты: родной datetime-local показывает формат локали ОС (MM/DD/YYYY, AM/PM).
         flatpickr показывает дд/мм/гггг ЧЧ:мм (24 ч), а на сервер уходит прежнее значение Y-m-d\TH:i --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/et.js"></script>
    <script>
        // initDatePickers(root) — и для блоков, подгруженных позже (например, панель прибора)
        window.initDatePickers = function (root) {
            if (!window.flatpickr) return;
            // Подпись <label for="..."> указывала на скрытое исходное поле — переводим её на видимое
            const linkLabel = (dates, str, fp) => {
                const id = fp.input.id;
                if (!fp.altInput || !id) return;
                fp.altInput.id = id + '-alt';
                document.querySelectorAll('label[for="' + id + '"]').forEach(label => label.htmlFor = fp.altInput.id);
            };
            const common = { locale: 'et', altInput: true, allowInput: true, disableMobile: true, onReady: linkLabel };
            (root || document).querySelectorAll('input[type="datetime-local"]').forEach(el => flatpickr(el, Object.assign({
                enableTime: true, time_24hr: true, dateFormat: 'Y-m-d\\TH:i', altFormat: 'd/m/Y H:i',
            }, common)).altInput?.setAttribute('placeholder', el.placeholder || 'pp/kk/aaaa tt:mm'));
            (root || document).querySelectorAll('input[type="date"]').forEach(el => flatpickr(el, Object.assign({
                dateFormat: 'Y-m-d', altFormat: 'd/m/Y',
            }, common)).altInput?.setAttribute('placeholder', el.placeholder || 'pp/kk/aaaa'));
        };
        window.initDatePickers(document);
    </script>

    <script>
        // Возврат к спискам без прыжка наверх: позиция прокрутки (и состояние списка,
        // если страница задала window.listState) хранится в sessionStorage по адресу страницы.
        // Восстанавливается при «Назад» в браузере, при обновлении и после кнопок «Tagasi» (data-back).
        (function () {
            const here = location.pathname + location.search;
            const key = 'listpos:' + here;
            const read = name => { try { return sessionStorage.getItem(name); } catch (e) { return null; } };
            const write = (name, value) => { try { sessionStorage.setItem(name, value); } catch (e) {} };
            const drop = name => { try { sessionStorage.removeItem(name); } catch (e) {} };

            if ('scrollRestoration' in history) history.scrollRestoration = 'manual';

            function save() {
                const state = { y: window.scrollY };
                if (window.listState && window.listState.save) {
                    try { Object.assign(state, window.listState.save()); } catch (e) {}
                }
                write(key, JSON.stringify(state));
            }
            window.addEventListener('pagehide', save);
            // Перед переходом по ссылке — на случай, если pagehide не сработает
            document.addEventListener('click', e => { if (e.target.closest('a[href]')) save(); }, true);

            // «Tagasi»: если пришли именно с той страницы — обычный шаг назад по истории
            // (сохраняет фильтры и подгруженные строки), иначе — переход с восстановлением позиции
            document.addEventListener('click', e => {
                const link = e.target.closest('a[data-back]');
                if (!link || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
                const target = new URL(link.href, location.href);
                let from = null;
                try { from = document.referrer ? new URL(document.referrer) : null; } catch (err) {}
                if (from && from.origin === location.origin && from.pathname === target.pathname && history.length > 1) {
                    e.preventDefault();
                    history.back();
                    return;
                }
                write('listpos:restore', target.pathname + target.search);
            });

            const nav = performance.getEntriesByType ? performance.getEntriesByType('navigation')[0] : null;
            const navType = nav ? nav.type : (performance.navigation && performance.navigation.type === 2 ? 'back_forward' : 'navigate');
            const viaBackButton = read('listpos:restore') === here;
            drop('listpos:restore');

            const raw = read(key);
            if (!raw || !(navType === 'back_forward' || navType === 'reload' || viaBackButton)) return;

            let state;
            try { state = JSON.parse(raw); } catch (e) { return; }

            Promise.resolve(window.listState && window.listState.restore ? window.listState.restore(state) : null)
                .catch(() => {})
                .then(() => {
                    window.scrollTo(0, state.y || 0);
                    // Картинки / шрифты могут догрузиться позже и сдвинуть страницу
                    if (document.readyState !== 'complete') {
                        window.addEventListener('load', () => window.scrollTo(0, state.y || 0), { once: true });
                    }
                });
        })();
    </script>
</body>
</html>
