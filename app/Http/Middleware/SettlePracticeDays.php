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
        } catch (\Throwable $e) {
            report($e);
        }

        return $next($request);
    }
}
