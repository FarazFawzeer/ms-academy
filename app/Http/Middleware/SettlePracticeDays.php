<?php

namespace App\Http\Middleware;

use App\Models\PracticeSession;
use Closure;
use Illuminate\Http\Request;

/**
 * Before any logged-in page: settle finished practice days (absences use a session).
 * Never blocks the page - a problem is logged and the request goes on.
 */
class SettlePracticeDays
{
    public function handle(Request $request, Closure $next)
    {
        try {
            PracticeSession::closePending();
            self::fillOnce();
        } catch (\Throwable $e) {
            report($e);
        }

        return $next($request);
    }

    /**
     * Once after the 20 Nov 2026 update (no terminal on the host): players added earlier with an old
     * joining date get their missed practices as absences. Recorded in `migrations` like a migration,
     * so it never runs twice (also when `php artisan migrate` ran the same step).
     */
    public const FILL_STEP = '2026_11_20_000001_fill_missed_absences';

    public static function fillOnce(): void
    {
        if (\Illuminate\Support\Facades\Cache::get('fill-missed-absences-done')) {
            return;
        }
        $db = \Illuminate\Support\Facades\DB::table('migrations');
        if (!$db->where('migration', self::FILL_STEP)->exists()) {
            // record it first, then fill (filling twice would change nothing anyway)
            $claimed = \Illuminate\Support\Facades\DB::table('migrations')->insertOrIgnore([
                'migration' => self::FILL_STEP,
                'batch'     => (int) \Illuminate\Support\Facades\DB::table('migrations')->max('batch') + 1,
            ]);
            if ($claimed) {
                foreach (\App\Models\Player::where('is_active', true)->get() as $p) {
                    \Illuminate\Support\Facades\DB::transaction(fn () => \App\Support\PlayerSessions::fillPastAbsences($p));
                }
            }
        }
        \Illuminate\Support\Facades\Cache::forever('fill-missed-absences-done', true);
    }
}
