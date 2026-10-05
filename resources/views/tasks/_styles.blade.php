@once
    <style>
        .task-form { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin: 14px 0; }
        .task-object-picker { display: flex; gap: 8px; flex: 1 1 360px; flex-wrap: wrap; }
        .task-object-picker .task-input { flex: 1 1 200px; min-width: 0; }
        .task-input { padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg); color: var(--text); font: inherit; }
        .task-error { color: var(--danger); margin: -4px 0 12px; }
        .task-empty { padding: 20px; text-align: center; background: var(--bg); border: 2px dashed var(--border); border-radius: 8px; color: var(--text-muted); }
        .task-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .task-table th, .task-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid var(--border); }
        .task-table th { color: var(--text-muted); font-weight: 500; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.03em; }
        .task-table td.task-date { font-family: 'IBM Plex Mono', ui-monospace, monospace; font-size: 0.85rem; color: var(--text-muted); }
        .task-table a { color: var(--primary); text-decoration: none; font-weight: 500; }
        .task-table a:hover { text-decoration: underline; }
        .task-actions { width: 1%; white-space: nowrap; }
        .task-badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 0.85rem; font-weight: 500; }
        .task-badge-water_meter { background: oklch(0.94 0.04 240); color: oklch(0.4 0.12 250); }
        .task-badge-allocator { background: oklch(0.95 0.05 60); color: oklch(0.45 0.12 50); }
        .task-delete { border: 1px solid var(--border); background: var(--bg); color: var(--text-muted); border-radius: 999px; width: 32px; height: 32px; cursor: pointer; }
        .task-delete:hover { color: var(--danger); border-color: var(--danger); }
    </style>
@endonce
