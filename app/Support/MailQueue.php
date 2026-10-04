<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Background email sending. The mailables implement ShouldQueue, so they are sent inside the request while the
 * queue connection is "sync" (the default). With Settings > Email > "Send emails in the background" on, emails go
 * to the database queue instead and a worker sends them: `php artisan schedule:run` every minute starts
 * `queue:work --stop-when-empty` (see routes/console.php and scripts/run-scheduler.bat).
 */
class MailQueue
{
    public const HEARTBEAT_KEY = 'queue.worker.heartbeat';

    /** Background sending is switched on and the queue tables exist. */
    public static function enabled(): bool
    {
        try {
            return (bool) \App\Models\Setting::get('email.queue_enabled', false) && Schema::hasTable('jobs');
        } catch (\Throwable) {
            return false;
        }
    }

    /** Point queued work at the database queue (called at boot). Leaves an explicitly configured queue alone. */
    public static function apply(): void
    {
        if (config('queue.default') === 'sync' && static::enabled()) {
            config(['queue.default' => 'database']);
        }
    }

    public static function beat(): void
    {
        Cache::forever(self::HEARTBEAT_KEY, now()->toIso8601String());
    }

    /** Worker/queue health for the admin Email settings page. */
    public static function status(): array
    {
        $tables = Schema::hasTable('jobs') && Schema::hasTable('failed_jobs');
        $beat = Cache::get(self::HEARTBEAT_KEY);
        $last = $beat ? \Illuminate\Support\Carbon::parse($beat) : null;

        return [
            'ready' => $tables,
            'enabled' => static::enabled(),
            'connection' => config('queue.default'),
            'waiting' => $tables ? DB::table('jobs')->count() : 0,
            'failed' => $tables ? DB::table('failed_jobs')->count() : 0,
            'last_run' => $last?->diffForHumans(),
            'worker_ok' => $last !== null && $last->gt(now()->subMinutes(5)),
        ];
    }
}
