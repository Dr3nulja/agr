@include('tasks._styles')
<style>
    .inst-progress { margin-bottom: 10px; }
    .inst-progress:last-child { margin-bottom: 0; }
    .inst-progress-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 6px; }
    .inst-progress-count { font-family: 'IBM Plex Mono', ui-monospace, monospace; font-size: 0.85rem; color: var(--text-muted); white-space: nowrap; }
    .inst-bar { height: 8px; border-radius: 999px; background: var(--border); overflow: hidden; }
    .inst-bar-fill { height: 100%; border-radius: 999px; background: var(--primary); }
    .inst-bar-water_meter { background: oklch(0.6 0.13 250); }
    .inst-bar-allocator { background: oklch(0.7 0.14 60); }
</style>
