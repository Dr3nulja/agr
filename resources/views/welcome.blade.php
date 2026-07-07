<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AGR Laravel</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f4f0e8;
            --panel: #ffffff;
            --text: #1f2933;
            --muted: #62707d;
            --accent: #0f766e;
            --line: #d7d0c4;
        }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(135deg, #f4f0e8 0%, #e8efe9 100%);
            color: var(--text);
            display: grid;
            place-items: center;
        }
        .card {
            width: min(860px, calc(100vw - 32px));
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 24px 80px rgba(15, 23, 42, 0.08);
        }
        h1 {
            margin: 0 0 12px;
            font-size: clamp(2rem, 4vw, 3.5rem);
            line-height: 1.05;
        }
        p {
            margin: 0;
            max-width: 60ch;
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.6;
        }
        .tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(15, 118, 110, 0.1);
            color: var(--accent);
            font-weight: 700;
            letter-spacing: 0.02em;
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="tag">Laravel migration scaffold</div>
        <h1>AGR system is ready for the rewrite.</h1>
        <p>
            This workspace now contains the initial Laravel structure that will be used
            to move the legacy PHP functionality step by step.
        </p>
    </main>
</body>
</html>
