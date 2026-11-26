{{-- Страницы ошибок: отдельно от layouts.app, потому что там может не быть сессии и пользователя --}}
<!DOCTYPE html>
<html lang="et">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <title>@yield('code') · @yield('title') - AGR</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; color: oklch(0.24 0.02 270); background: radial-gradient(circle at top left, oklch(0.62 0.19 270 / 0.12), transparent 40%), oklch(0.97 0.006 270); }
        .err { width: 100%; max-width: 440px; padding: 32px 28px; border-radius: 20px; background: white; box-shadow: 0 10px 30px -10px oklch(0 0 0 / 0.15); text-align: center; }
        .err-code { font-size: 3rem; font-weight: 700; line-height: 1; background: linear-gradient(135deg, oklch(0.62 0.19 254), oklch(0.62 0.18 305)); -webkit-background-clip: text; background-clip: text; color: transparent; }
        h1 { font-size: 1.3rem; font-weight: 600; margin: 14px 0 8px; }
        p { margin: 0 0 22px; color: oklch(0.5 0.02 270); line-height: 1.5; }
        .err-actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        a, button { display: inline-flex; align-items: center; padding: 11px 18px; border-radius: 999px; border: 1px solid oklch(0.9 0.01 270); background: white; color: inherit; font: inherit; font-size: 0.95rem; text-decoration: none; cursor: pointer; }
        .primary { background: linear-gradient(135deg, oklch(0.62 0.19 254), oklch(0.62 0.18 305)); color: white; border-color: transparent; }
    </style>
</head>
<body>
    <main class="err">
        <div class="err-code">@yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <div class="err-actions">
            @hasSection('actions')
                @yield('actions')
            @else
                <button type="button" onclick="history.length > 1 ? history.back() : location.assign('/')">← Tagasi</button>
                <a href="/dashboard" class="primary">Avalehele</a>
            @endif
        </div>
    </main>
</body>
</html>
