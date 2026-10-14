<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Usage in routes: ->middleware(EnsureUserType::class . ':Super Admin,Admin')
 * Super Admin always passes.
 */
class EnsureUserType
{
    public function handle(Request $request, Closure $next, string ...$types)
    {
        $user = $request->user();

        $allowed = $user && ($user->type === 'Super Admin' || in_array($user->type, $types, true));

        if (!$allowed) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to do this.'], 403);
            }
            abort(403, 'You do not have permission to open this page.');
        }

        return $next($request);
    }
}
