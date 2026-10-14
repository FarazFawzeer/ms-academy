<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\CoachController;
use App\Http\Controllers\CoachWorkdayController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoutingController;
use App\Http\Middleware\EnsureUserType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth (kept before the catch-all routes below so /login is never swallowed)
|--------------------------------------------------------------------------
*/
Route::get('/login', function () {
    // already logged in? go to the dashboard instead of showing the form again
    return Auth::check() ? redirect()->route('dashboard') : view('auth.signin');
})->name('login');

Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Lock screen (needs a logged-in user; posts to login.post to unlock)
Route::get('/auth/lock-screen', fn () => view('auth.lock-screen'))->middleware('auth')->name('lock-screen');

/*
|--------------------------------------------------------------------------
| Dashboard  (/ and /index both open it; the logo link uses /index)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('root');
    Route::get('/index', [DashboardController::class, 'index'])->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Academy - everything below needs a login (the old admin group had none)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {

    $manager = EnsureUserType::class . ':Super Admin,Admin';   // Front Desk is blocked

    /* ---- Front Desk + Admin ------------------------------------------------ */

    // Attendance
    Route::get('attendance/scan', [AttendanceController::class, 'scan'])->name('attendance.scan');
    Route::post('attendance/mark', [AttendanceController::class, 'mark'])->name('attendance.mark');
    Route::get('attendance/search', [AttendanceController::class, 'search'])->name('attendance.search');
    Route::get('attendance/history', [AttendanceController::class, 'history'])->name('attendance.history');
    Route::delete('attendance/{attendance}', [AttendanceController::class, 'destroy'])->whereNumber('attendance')->name('attendance.destroy');

    // Players (static paths first, then {player})
    Route::get('players', [PlayerController::class, 'index'])->name('players.index');
    Route::get('players/create', [PlayerController::class, 'create'])->name('players.create');
    Route::get('players/cards', [PlayerController::class, 'cards'])->name('players.cards');
    Route::post('players', [PlayerController::class, 'store'])->name('players.store');
    Route::get('players/{player}', [PlayerController::class, 'show'])->whereNumber('player')->name('players.show');
    Route::get('players/{player}/edit', [PlayerController::class, 'edit'])->whereNumber('player')->name('players.edit');
    Route::post('players/{player}', [PlayerController::class, 'update'])->whereNumber('player')->name('players.update');
    Route::post('players/{player}/toggle', [PlayerController::class, 'toggle'])->whereNumber('player')->name('players.toggle');
    Route::post('players/{player}/payments', [PaymentController::class, 'store'])->whereNumber('player')->name('players.payments.store');

    // Payments list
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');

    // Reminders (WhatsApp)
    Route::get('reminders', [ReminderController::class, 'index'])->name('reminders.index');
    Route::post('reminders/{player}/sent', [ReminderController::class, 'sent'])->whereNumber('player')->name('reminders.sent');
    Route::post('reminders/{player}/dismiss', [ReminderController::class, 'dismiss'])->whereNumber('player')->name('reminders.dismiss');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    /* ---- Admin only -------------------------------------------------------- */

    Route::middleware($manager)->group(function () {

        Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->whereNumber('payment')->name('payments.destroy');

        // Batches
        Route::get('batches', [BatchController::class, 'index'])->name('batches.index');
        Route::post('batches', [BatchController::class, 'store'])->name('batches.store');
        Route::post('batches/{batch}', [BatchController::class, 'update'])->whereNumber('batch')->name('batches.update');
        Route::delete('batches/{batch}', [BatchController::class, 'destroy'])->whereNumber('batch')->name('batches.destroy');

        // Coaches
        Route::get('coaches', [CoachController::class, 'index'])->name('coaches.index');
        Route::get('coaches/create', [CoachController::class, 'create'])->name('coaches.create');
        Route::post('coaches', [CoachController::class, 'store'])->name('coaches.store');
        Route::get('coaches/workdays', [CoachWorkdayController::class, 'daily'])->name('coaches.workdays');
        Route::get('coaches/salary', [CoachWorkdayController::class, 'salary'])->name('coaches.salary');
        Route::post('coaches/salary/pay', [CoachWorkdayController::class, 'pay'])->name('coaches.salary.pay');
        Route::get('coaches/{coach}/edit', [CoachController::class, 'edit'])->whereNumber('coach')->name('coaches.edit');
        Route::post('coaches/{coach}', [CoachController::class, 'update'])->whereNumber('coach')->name('coaches.update');
        Route::post('coaches/{coach}/toggle', [CoachController::class, 'toggle'])->whereNumber('coach')->name('coaches.toggle');
        Route::post('coaches/{coach}/workday', [CoachWorkdayController::class, 'toggle'])->whereNumber('coach')->name('coaches.workday.toggle');
        Route::get('coaches/{coach}/days', [CoachWorkdayController::class, 'days'])->whereNumber('coach')->name('coaches.days');

        // Reports
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export/{type}', [ReportController::class, 'export'])->where('type', 'attendance|payments|salary')->name('reports.export');

        // System users (the old "Admin" menu)
        Route::resource('users', UserController::class)->only(['index', 'store', 'destroy']);
    });
});

/*
|--------------------------------------------------------------------------
| Theme catch-all routes - MUST stay last
|--------------------------------------------------------------------------
*/
// Segments may only contain letters, digits, _ and -. Anything with a dot (favicon.ico, .well-known/...,
// missing images, *.map files) therefore never reaches this group: it is a plain 404 and can no longer be
// remembered as the "page you wanted" after login.
Route::group(['prefix' => '/', 'middleware' => 'auth'], function () {
    $seg = '[A-Za-z0-9_-]+';
    Route::get('{first}/{second}/{third}', [RoutingController::class, 'thirdLevel'])->where(['first' => $seg, 'second' => $seg, 'third' => $seg])->name('third');
    Route::get('{first}/{second}', [RoutingController::class, 'secondLevel'])->where(['first' => $seg, 'second' => $seg])->name('second');
    Route::get('{any}', [RoutingController::class, 'root'])->where('any', $seg)->name('any');
});