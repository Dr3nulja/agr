{{-- Прогресс по одному типу прибора. $progress — объект из InstallationController::progress() --}}
<div class="inst-progress">
    <div class="inst-progress-head">
        <span class="task-badge task-badge-{{ $progress->device }}">{{ $progress->label }}{{ $progress->actions ? ' — ' . mb_strtolower($progress->actions) : '' }}</span>
        <span class="inst-progress-count">
            @if($progress->total > 0)
                {{ $progress->done }} / {{ $progress->total }} krt · {{ $progress->percent }}%
            @else
                {{ $progress->done }} krt
            @endif
        </span>
    </div>
    @if($progress->total > 0)
        <div class="inst-bar" role="progressbar" aria-valuenow="{{ $progress->percent }}" aria-valuemin="0" aria-valuemax="100">
            <div class="inst-bar-fill inst-bar-{{ $progress->device }}" style="width: {{ $progress->percent }}%"></div>
        </div>
    @endif
</div>
