@once
    <style>
        /* Форма добавления: тонированная панель с подписями полей */
        .task-form-panel { margin: 14px 0 18px; padding: 16px 18px; border-radius: 12px; background: oklch(0.97 0.02 254); border: 1px solid oklch(0.88 0.05 254); border-left: 4px solid var(--primary); }
        .task-form-title { font-weight: 600; color: var(--text); margin-bottom: 10px; }
        .task-form { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
        .task-field { display: flex; flex-direction: column; gap: 4px; }
        .task-field-object { flex: 1 1 360px; }
        .task-field-label { font-size: 0.78rem; font-weight: 500; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.03em; }
        .task-object-picker { display: flex; gap: 8px; flex-wrap: wrap; }
        .task-object-picker .task-input { flex: 1 1 200px; min-width: 0; }
        .task-input { padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface); color: var(--text); font: inherit; }
        .task-submit { white-space: nowrap; padding: 10px 18px; border: 1px solid transparent; border-radius: 8px; background: var(--primary); color: white; font: inherit; font-weight: 600; cursor: pointer; }
        .task-submit:hover { filter: brightness(1.08); }
        .task-error { color: var(--danger); margin-top: 10px; }

        /* Фильтр списка: маленькие переключатели, привязанные к таблице */
        .task-filter { display: flex; flex-direction: column; gap: 8px; padding: 12px 0 14px; border-bottom: 1px solid var(--border); }
        .task-filter-head { display: flex; align-items: center; gap: 10px; }
        .task-filter-title { font-size: 0.78rem; font-weight: 500; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.03em; }
        .task-filter-shown { font-size: 0.85rem; color: var(--text-muted); font-family: 'IBM Plex Mono', ui-monospace, monospace; }
        .task-filter-reset { margin-left: auto; border: none; background: none; color: var(--primary); font: inherit; font-size: 0.85rem; cursor: pointer; padding: 0; }
        .task-filter-reset:hover { text-decoration: underline; }
        .task-filter-group { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .task-filter-label { min-width: 64px; font-size: 0.85rem; color: var(--text-muted); }
        .task-chip { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 999px; border: 1px solid var(--border); background: var(--surface); color: var(--text); font: inherit; font-size: 0.85rem; cursor: pointer; }
        .task-chip:hover:not(:disabled) { border-color: var(--primary); }
        .task-chip:disabled { opacity: 0.45; cursor: default; }
        .task-chip.is-active { background: var(--text); border-color: var(--text); color: var(--surface); }
        .task-chip-count { font-size: 0.75rem; opacity: 0.7; }

        .task-empty { padding: 20px; text-align: center; background: var(--bg); border: 2px dashed var(--border); border-radius: 8px; color: var(--text-muted); }
        .task-filter-empty { margin-top: 12px; }
        .task-table-wrap { overflow-x: auto; }
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
