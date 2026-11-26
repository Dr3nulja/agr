{{-- Стили содержимого прибора: страница readings.device и раскрывающаяся строка в readings.index --}}
<style>
    .rd-field { display: flex; flex-direction: column; gap: 4px; }
    .rd-label { font-size: 0.75rem; font-weight: 500; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.03em; }
    .rd-form { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
    .rd-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 20px; }
    .rd-dtable { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
    .rd-dtable th, .rd-dtable td { padding: 7px 8px; border-bottom: 1px solid var(--border); text-align: left; vertical-align: middle; white-space: normal; }
    .rd-dtable th { font-weight: 500; color: var(--text-muted); background: none; position: static; }
    .rd-dtable .rd-input { padding: 5px 8px; font-size: 0.88rem; }
    .rd-badge { font-size: 0.75rem; padding: 2px 8px; border-radius: 999px; background: oklch(0.95 0.05 65); color: oklch(0.45 0.12 65); }
    .rd-btn-danger { color: var(--danger); }
    .rd-ok { color: oklch(0.45 0.12 155); }
    .rd-problem { color: oklch(0.5 0.17 30); }
    .rd-history { max-height: 640px; overflow-y: auto; }
    .rd-section + .rd-section { margin-top: 24px; }
    .rd-replace { padding: 14px 16px; border-radius: 12px; border: 1px dashed oklch(0.75 0.1 65); background: oklch(0.98 0.02 75); }
    /* В раскрывающейся строке — без теней карточек, компактнее */
    .rd-panel .rd-grid { gap: 14px; margin-bottom: 14px; }
    .rd-panel .rd-grid:last-child { margin-bottom: 0; }
    .rd-box { padding: 16px 18px; border-radius: 14px; background: var(--surface); border: 1px solid var(--border); }
    .rd-box h3 { font-size: 1rem; margin: 0 0 10px; }
    .rd-panel .rd-history { max-height: 380px; }
    @media (max-width: 900px) { .rd-grid { grid-template-columns: 1fr; } }

    /* Раскрывающаяся строка прибора в таблице (стрелка ▶ и подгружаемая панель) */
    .rd-dev-row[data-panel-url] { cursor: pointer; }
    .rd-toggle { width: 26px; height: 26px; padding: 0; border: 1px solid var(--border); border-radius: 8px; background: var(--bg); color: var(--text-muted); cursor: pointer; font-size: 0.7rem; line-height: 1; transition: transform 0.15s, border-color 0.15s; }
    .rd-toggle:hover { border-color: var(--primary); color: var(--primary); }
    .rd-dev-row.open .rd-toggle { transform: rotate(90deg); border-color: var(--primary); color: var(--primary); }
    .rd-dev-row.open > td { background: oklch(0.96 0.02 254); }
    table tr.rd-panel-row > td { padding: 0; background: oklch(0.975 0.006 270); white-space: normal; }
    table tbody tr.rd-panel-row:hover { background: none; }
    .rd-panel { position: sticky; left: 0; box-sizing: border-box; padding: 14px; text-align: left; }
    .rd-panel-bar { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 12px; }
    .rd-panel-msg { font-size: 0.88rem; }
    .rd-panel-msg.ok { color: oklch(0.42 0.12 155); }
    .rd-panel-msg.err { color: var(--danger); }
    .rd-panel-loading { padding: 18px; color: var(--text-muted); }
    .rd-panel.busy { opacity: 0.6; pointer-events: none; }
    /* Базовые элементы внутри панели — чтобы она выглядела одинаково на любой странице */
    .rd-panel h3 { font-weight: 500; }
    .rd-panel .rd-muted { color: var(--text-muted); font-size: 0.9rem; }
    .rd-panel .rd-mono { font-family: 'IBM Plex Mono', ui-monospace, monospace; }
    .rd-panel .rd-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 999px; border: 1px solid var(--border); background: var(--bg); color: var(--text); font: inherit; font-size: 0.85rem; text-decoration: none; cursor: pointer; white-space: nowrap; }
    .rd-panel .rd-btn:hover { border-color: var(--primary); }
    .rd-panel .rd-btn-primary { background: var(--primary-grad); color: white; border-color: transparent; }
    .rd-panel .rd-input { padding: 8px 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface); color: var(--text); font: inherit; font-size: 0.9rem; }
    .rd-panel .rd-dtable th, .rd-panel .rd-dtable td { padding: 7px 8px; border-bottom: 1px solid var(--border); background: none; }
    .rd-panel .rd-dtable tbody tr:hover { background: none; }
</style>
