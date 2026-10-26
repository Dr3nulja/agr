<!DOCTYPE html>
<html lang="et">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sisselogimine - AGR</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&display=swap">
    <style>
        :root {
            --text: oklch(0.24 0.02 270);
            --muted: oklch(0.5 0.02 270);
            --accent: oklch(0.62 0.19 254);
            --accent-2: oklch(0.62 0.18 305);
            --line: #dbe4ea;
            --danger: #b91c1c;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: 'Sora', system-ui, sans-serif;
            background:
                radial-gradient(circle at top left, oklch(0.62 0.19 270 / 0.14), transparent 30%),
                linear-gradient(135deg, #e2e8f0 0%, #f8fafc 100%);
            color: var(--text);
        }
        .form-card {
            width: min(380px, calc(100vw - 32px));
            padding: 32px 28px 28px;
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.65);
            box-shadow: 0 24px 80px rgba(15, 23, 42, 0.12);
        }
        .badge {
            display: block;
            width: fit-content;
            margin: 0 auto 24px;
            padding: 8px 18px;
            border-radius: 999px;
            background: oklch(0.62 0.19 270 / 0.12);
            color: var(--accent);
            font-weight: 600;
            font-size: 1.1rem;
        }
        .field {
            display: grid;
            gap: 8px;
            margin-bottom: 16px;
        }
        label {
            font-size: 0.92rem;
            color: var(--muted);
            font-weight: 500;
        }
        input {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 14px 16px;
            font: inherit;
            color: var(--text);
            background: #fff;
        }
        button {
            width: 100%;
            margin-top: 4px;
            border: 0;
            border-radius: 16px;
            padding: 14px 16px;
            font: inherit;
            font-weight: 500;
            color: #fff;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            box-shadow: 0 10px 24px -6px oklch(0.62 0.19 270 / 0.45);
            cursor: pointer;
            transition: opacity 0.2s;
        }
        button:hover {
            opacity: 0.92;
        }
        .errors {
            margin: 0 0 16px;
            padding: 14px 16px;
            border-radius: 16px;
            background: rgba(185, 28, 28, 0.08);
            color: var(--danger);
        }
    </style>
</head>
<body>
    <main class="form-card">
        <div class="badge">AGR</div>

        <form method="post" action="{{ route('login.store') }}">
            @csrf

            @if ($errors->any())
                <div class="errors">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="field">
                <label for="login">Kasutajanimi</label>
                <input id="login" name="login" value="{{ old('login') }}" autocomplete="username" required autofocus>
            </div>

            <div class="field">
                <label for="password">Parool</label>
                <input id="password" type="password" name="password" autocomplete="current-password" required>
            </div>

            <button type="submit">Logi sisse</button>
        </form>
    </main>
</body>
</html>
