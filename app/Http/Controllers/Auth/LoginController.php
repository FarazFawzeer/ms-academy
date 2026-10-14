<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * One login controller for the login page AND the lock screen (both post here).
 * Replaces the Breeze AuthenticatedSessionController so there is a single, known path.
 */
class LoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // 5 wrong tries per email + IP, then a 1 minute pause (stops password guessing)
        $key = Str::lower($credentials['email']) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many attempts. Please try again in ' . RateLimiter::availableIn($key) . ' seconds.',
            ]);
        }

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        // only academy staff may enter (old "Customer" style accounts are refused)
        if (!in_array(Auth::user()->type, UserController::TYPES, true)) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'This account does not have access to the system.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect($this->landingUrl($request));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Where to go after login. We only honour the "page you wanted" when it is a real
     * app page. Browsers also ask for favicon.ico, .well-known/..., missing images and
     * source maps; if one of those was saved as the "intended" URL the user landed on a
     * 404 right after logging in. Everything else goes to the dashboard.
     */
    private function landingUrl(Request $request): string
    {
        $intended = $request->session()->pull('url.intended');
        $path     = $intended ? (string) parse_url($intended, PHP_URL_PATH) : '';

        if ($path !== '' && preg_match('#^/(admin(/|$)|index$)#', $path)) {
            return $intended;
        }

        return route('dashboard');
    }
}