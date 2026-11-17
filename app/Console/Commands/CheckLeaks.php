<?php

namespace App\Console\Commands;

use App\Models\LeakAlert;
use App\Services\LeakDetector;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * Утечки воды: водомеры, насчитавшие за сутки ≥ LEAK_DAILY_M3 (по умолчанию 5) м³.
 *
 *   php artisan leaks:check                  — вчерашний день (так запускается по расписанию)
 *   php artisan leaks:check --days=30        — последние 30 дней (первый запуск / проверка истории)
 *   php artisan leaks:check --date=2026-11-01 --object=123
 */
class CheckLeaks extends Command
{
    protected $signature = 'leaks:check
        {--date= : День проверки Y-m-d (по умолчанию вчера)}
        {--days=1 : Сколько дней проверить, заканчивая --date}
        {--object= : Только этот объект (id)}
        {--no-mail : Не отправлять письмо}';

    protected $description = 'Найти утечки воды: суточный расход водомера ≥ порога (config agr.leak_daily_m3)';

    public function handle(LeakDetector $detector): int
    {
        $last = $this->option('date') ? Carbon::createFromFormat('Y-m-d', $this->option('date')) : now()->subDay();
        $days = max(1, (int) $this->option('days'));
        $objectId = $this->option('object') !== null ? (int) $this->option('object') : null;

        $this->info(sprintf('Порог: %s м³ в сутки', LeakDetector::threshold()));

        $new = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = $last->copy()->subDays($i)->startOfDay();
            $found = $detector->scanDay($day, $objectId);
            $new = array_merge($new, $found);
            if ($days > 1 || $found) {
                $this->line(sprintf('%s: новых утечек %d', $day->toDateString(), count($found)));
            }
        }

        if ($new === []) {
            $this->info('Новых утечек нет');

            return self::SUCCESS;
        }

        $this->table(['Maja', 'Korter', 'Seade', 'Päev', 'm³ päevas'], array_map(fn (LeakAlert $alert) => [
            $alert->object?->address,
            $alert->location,
            $alert->devid,
            $alert->day->format('d.m.Y'),
            number_format($alert->consumption, 3, '.', ' '),
        ], $new));

        if (! $this->option('no-mail')) {
            $this->notify($new);
        }

        return self::SUCCESS;
    }

    /**
     * @param  LeakAlert[]  $alerts
     */
    private function notify(array $alerts): void
    {
        $emails = array_values(array_filter(array_map('trim', explode(',', (string) config('agr.leak_alert_emails')))));
        if ($emails === []) {
            return;
        }

        $lines = array_map(fn (LeakAlert $alert) => sprintf(
            '%s, %s — korter %s, seade %d: %s m³ päevas (%s)',
            $alert->object?->address,
            $alert->object?->City,
            $alert->location ?: '—',
            $alert->devid,
            number_format($alert->consumption, 3, '.', ' '),
            $alert->day->format('d.m.Y'),
        ), $alerts);

        $body = "Võimalikud veelekked (kulu ≥ " . LeakDetector::threshold() . " m³ ööpäevas):\n\n"
            . implode("\n", $lines)
            . "\n\n" . route('leaks.index');

        try {
            Mail::raw($body, fn ($message) => $message->to($emails)->subject('Võimalikud veelekked: ' . count($alerts)));
            LeakAlert::whereKey(array_map(fn ($alert) => $alert->id, $alerts))->update(['notified_at' => now()]);
            $this->info('Письмо отправлено: ' . implode(', ', $emails));
        } catch (\Throwable $e) {
            report($e);
            $this->error('Письмо не отправлено: ' . $e->getMessage());
        }
    }
}
