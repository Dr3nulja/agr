<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&display=swap">
    <style>
        :root {
            --panel: #f8fafc;
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
        .shell {
            width: min(960px, calc(100vw - 32px));
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 24px;
            align-items: stretch;
        }
        .hero,
        .form-card {
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.65);
            box-shadow: 0 24px 80px rgba(15, 23, 42, 0.12);
        }
        .hero {
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 520px;
        }
        .badge {
            display: inline-flex;
            width: fit-content;
            padding: 8px 14px;
            border-radius: 999px;
            background: oklch(0.62 0.19 270 / 0.12);
            color: var(--accent);
            font-weight: 500;
        }
        h1 {
            margin: 18px 0 12px;
            font-size: clamp(2rem, 4.4vw, 3.6rem);
            line-height: 1.18;
            letter-spacing: -0.02em;
        }
        .hero p {
            margin: 0;
            max-width: 42ch;
            color: var(--muted);
            font-size: 1.02rem;
            line-height: 1.7;
        }
        .steps {
            display: grid;
            gap: 12px;
            margin-top: 28px;
        }
        .step {
            padding: 16px 18px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.68);
            border: 1px solid var(--line);
            color: var(--text);
        }
        .form-card {
            padding: 28px;
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
        @media (max-width: 860px) {
            .shell { grid-template-columns: 1fr; }
            .hero { min-height: auto; }
        }
    </style>
</head>
<body>
    <main class="shell">
        <section class="hero">
            <div>
                <div class="badge">AGR</div>
                <h1>Metering & billing, in one place.</h1>
                <p>
                    Sign in to manage objects, devices, and meter readings for water,
                    heat, and electricity accounting.
                </p>
            </div>

            <div class="steps">
                <div class="step">💧 Water & heat meter readings</div>
                <div class="step">⚡ Electricity monitoring</div>
                <div class="step">📊 Exports, SOE settings & device management</div>
            </div>
        </section>

        <section class="form-card">
            <form method="post" action="{{ route('login.store') }}">
                @csrf

                @if ($errors->any())
                    <div class="errors">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="field">
                    <label for="login">Login</label>
                    <input id="login" name="login" value="{{ old('login') }}" autocomplete="username" required>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" autocomplete="current-password" required>
                </div>

                <button type="submit">Sign in</button>
            </form>
        </section>
    </main>
</body>
</html>