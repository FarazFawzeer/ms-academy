<?php
/**
 * Usage (from the project root): php database/sql/make_demo_sql.php .
 * Generates database/sql/2026_10_demo_data.sql by replaying the app's own rules
 * (sessions -1 per attendance, +8 per payment, reminder at 2 left, age group on the day).
 * Deterministic (fixed random seed). "Today" = Saturday 17 Oct 2026.
 */
$base = $argv[1];
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\AgeGroup;
use Carbon\Carbon;

mt_srand(2026);
$today = Carbon::parse('2026-10-17');
$fee   = (int) config('academy.fee');
$pkg   = (int) config('academy.sessions_per_package');
$q     = fn ($v) => $v === null ? 'NULL' : (is_int($v) || is_float($v) ? (string) $v : "'" . str_replace("'", "''", $v) . "'");
$row   = fn (array $vals) => '(' . implode(', ', array_map($q, $vals)) . ')';

/* ---------------- practice sessions ---------------- */
// [slot, planned, actual, start, end, cancelled, note]
$sessions = [];
for ($d = Carbon::parse('2026-09-04'); $d->lte(Carbon::parse('2026-11-08')); $d->addDay()) {
    $slot = $d->isFriday() ? 'fri' : ($d->isSunday() ? 'sun' : null);
    if ($slot) {
        $sessions[] = ['slot' => $slot, 'planned' => $d->toDateString(), 'date' => $d->toDateString(), 'start' => '16:00:00', 'end' => '17:30:00', 'cancelled' => 0, 'note' => null];
    }
}
$edit = function ($planned, $slot, array $changes) use (&$sessions) {
    foreach ($sessions as &$s) {
        if ($s['planned'] === $planned && $s['slot'] === $slot) {
            $s = array_merge($s, $changes);
        }
    }
};
$edit('2026-09-20', 'sun', ['date' => '2026-09-21', 'note' => 'Ground booked for a match']);           // moved (past)
$edit('2026-10-09', 'fri', ['cancelled' => 1, 'note' => 'Heavy rain']);                                // cancelled (past)
$edit('2026-10-25', 'sun', ['date' => '2026-10-26', 'start' => '16:30:00', 'end' => '18:00:00', 'note' => 'Poya day - moved to Monday']); // moved (future)
$edit('2026-11-06', 'fri', ['cancelled' => 1, 'note' => 'Ground maintenance']);                        // cancelled (future)
$sessions[] = ['slot' => 'fri', 'planned' => '2026-10-10', 'date' => '2026-10-10', 'start' => '09:00:00', 'end' => '10:30:00', 'cancelled' => 0, 'note' => 'Make-up for the rained-out Friday']; // extra day
$sessions[] = ['slot' => 'sun', 'planned' => '2026-11-01', 'date' => '2026-11-01', 'start' => '07:30:00', 'end' => '09:00:00', 'cancelled' => 0, 'note' => 'Morning session (heat)'];
// the last line replaces the regular 1 Nov row (same slot + planned date)
$sessions = array_values(array_reduce($sessions, function ($c, $s) { $c[$s['slot'] . $s['planned']] = $s; return $c; }, []));
usort($sessions, fn ($a, $b) => strcmp($a['date'], $b['date']));

/* ---------------- coaches ---------------- */
$coaches = [
    [1, 'Ruwan Perera', '0771234501', '198512345678', 3000, 1],
    [2, 'Fazal Hameed', '0761234502', '199023456789', 2500, 1],
    [3, 'Dinesh Kumar', '0711234503', '199234567890', 2500, 1],
    [4, 'Shiraz Ismail', '0751234504', '198845678901', 2000, 0],
];

/* ---------------- players ---------------- */
// name, dob, days, joined, guardian, phone, style, active
// style: good = renews when out of sessions, late = stops paying, nopay = joined without paying
$P = [
    ['Ayaan Rizwan', '2019-05-14', 'fri,sun', '2026-09-01', 'Rizwan Hameed', '0771100001', 'good', 1],
    ['Zayd Imran', '2018-11-30', 'sun', '2026-09-01', 'Imran Farook', '0771100002', 'good', 1],
    ['Hana Nizam', '2019-12-02', 'sun', '2026-10-01', 'Nizam Saleem', '0771100003', 'good', 1],
    ['Yusuf Aslam', '2017-03-09', 'fri,sun', '2026-09-01', 'Aslam Nasar', '0771100004', 'late', 1],
    ['Ibrahim Fazlan', '2017-08-21', 'fri', '2026-09-01', 'Fazlan Ahmed', '0771100005', 'good', 1],
    ['Musa Rikas', '2016-10-20', 'fri,sun', '2026-09-01', 'Rikas Mohamed', '0771100006', 'good', 1],  // turns 10 on 20 Oct -> U12
    ['Adam Sajah', '2016-01-17', 'sun', '2026-09-01', 'Sajah Ali', '0771100007', 'good', 1],
    ['Ilyas Nasar', '2015-06-05', 'fri,sun', '2026-09-01', 'Nasar Cader', '0771100008', 'good', 1],
    ['Rayan Fawz', '2015-02-11', 'fri', '2026-09-01', 'Fawz Hussain', '0771100009', 'late', 1],
    ['Hamdan Anush', '2014-10-01', 'fri,sun', '2026-09-01', 'Anush Rahman', '0771100010', 'good', 1],  // turned 12 on 1 Oct -> U12 then U14
    ['Zain Ahmed', '2014-12-24', 'sun', '2026-09-01', 'Ahmed Rauf', '0771100011', 'good', 1],
    ['Aqeel Nimrod', '2014-04-03', 'fri,sun', '2026-09-15', 'Nimrod Shaheed', '0771100012', 'good', 1],
    ['Saif Yoonus', '2013-07-19', 'fri', '2026-09-01', 'Yoonus Haneef', '0771100013', 'good', 1],
    ['Nabeel Salman', '2013-01-28', 'fri,sun', '2026-09-01', 'Salman Jiffry', '0771100014', 'good', 1],
    ['Danish Riyas', '2012-09-09', 'sun', '2026-09-01', 'Riyas Ismail', '0771100015', 'late', 1],
    ['Idrees Faiz', '2012-05-30', 'fri,sun', '2026-09-01', 'Faiz Mubarak', '0771100016', 'good', 1],
    ['Raihaan Hilmy', '2011-11-15', 'fri', '2026-09-01', 'Hilmy Sameem', '0771100017', 'good', 1],
    ['Shazeen Nawaz', '2011-03-22', 'fri,sun', '2026-10-02', 'Nawaz Careem', '0771100018', 'nopay', 1],
    ['Nafees Yahya', '2010-08-08', 'sun', '2026-09-01', 'Yahya Mansoor', '0771100019', 'good', 1],
    ['Umar Farhan', '2010-02-14', 'fri,sun', '2026-09-01', 'Farhan Lafir', '0771100020', 'good', 1],
    ['Bilal Rumy', '2009-06-27', 'fri', '2026-09-01', 'Rumy Badurdeen', '0771100021', 'late', 1],
    ['Hassan Mujeeb', '2009-10-12', 'fri,sun', '2026-09-01', 'Mujeeb Rahim', '0771100022', 'good', 1],
    ['Aathif Rasheed', '2008-04-18', 'sun', '2026-09-01', 'Rasheed Marikar', '0771100023', 'good', 1],
    ['Kasun Perera', '2007-12-05', 'fri,sun', '2026-09-01', 'Sunil Perera', '0771100024', 'good', 1],
    ['Nimal Silva', '2002-09-17', 'fri', '2026-09-01', 'Nimal Silva', '0771100025', 'good', 1],
    ['Tharindu Jayasinghe', '2016-07-07', 'sun', '2026-09-01', 'Lalith Jayasinghe', '0771100026', 'good', 0], // left the academy
    ['Ammar Hafeel', '2013-09-25', 'fri,sun', '2026-09-01', 'Hafeel Wahab', '0771100027', 'good', 0],         // left the academy
];

$players = $payments = $attendance = [];
$admin = '@admin';
foreach ($P as $i => [$name, $dob, $days, $joined, $gName, $gPhone, $style, $active]) {
    $id = $i + 1;
    $players[$id] = ['name' => $name, 'dob' => $dob, 'days' => explode(',', $days), 'joined' => $joined, 'style' => $style, 'active' => $active,
        'gname' => $gName, 'gphone' => $gPhone, 'balance' => 0, 'pending' => 0, 'paid_once' => false, 'left' => $active ? null : '2026-09-27'];
}

$pay = function ($id, $date, $note) use (&$players, &$payments, $fee, $pkg) {
    $methods = ['cash', 'cash', 'cash', 'bank', 'online'];
    $payments[] = ['player_id' => $id, 'amount' => $fee, 'sessions' => $pkg, 'method' => $methods[mt_rand(0, 4)], 'note' => $note,
        'paid_at' => $date . ' ' . sprintf('%02d:%02d:00', mt_rand(15, 16), mt_rand(0, 59))];
    $players[$id]['balance'] += $pkg;
    $players[$id]['pending'] = 0;
    $players[$id]['paid_once'] = true;
};

// first payment on joining (except "nopay")
foreach ($players as $id => $p) {
    if ($p['style'] !== 'nopay') {
        $pay($id, $p['joined'], 'First payment on joining');
    }
}

$remindAt = $pkg - (int) config('academy.remind_at_session');
foreach ($sessions as $s) {
    if ($s['cancelled'] || $s['date'] > $today->toDateString()) continue;
    foreach ($players as $id => &$p) {
        if ($s['date'] < $p['joined'] || ($p['left'] && $s['date'] > $p['left']) || !in_array($s['slot'], $p['days'], true)) continue;
        if (mt_rand(1, 100) > 82) continue;                                  // ~18% absent
        if ($p['style'] === 'good' && $p['balance'] <= 0) {                   // renews at the desk before training
            $pay($id, $s['date'], null);
        }
        $start = Carbon::parse($s['date'] . ' ' . $s['start']);
        $attendance[] = ['player_id' => $id, 'age_group' => AgeGroup::forDob($p['dob'], $s['date']), 'attendance_date' => $s['date'],
            'method' => mt_rand(1, 10) <= 8 ? 'qr' : 'manual', 'is_extra' => 0,
            'created_at' => $start->copy()->subMinutes(mt_rand(0, 15))->addMinutes(mt_rand(0, 20))->format('Y-m-d H:i:s')];
        $p['balance']--;
        if ($p['balance'] === $remindAt) $p['pending'] = 1;
    }
    unset($p);
}

// Admin override: Sunday-only Zain Ahmed (id 11) came on Friday 16 Oct -> extra session
$attendance[] = ['player_id' => 11, 'age_group' => AgeGroup::forDob($players[11]['dob'], '2026-10-16'), 'attendance_date' => '2026-10-16',
    'method' => 'manual', 'is_extra' => 1, 'created_at' => '2026-10-16 16:12:00'];
$players[11]['balance']--;
if ($players[11]['balance'] === $remindAt) $players[11]['pending'] = 1;

// reminders already sent on WhatsApp for two players (pending cleared, date kept)
$sent = [];
foreach ($players as $id => &$p) {
    if ($p['pending'] && count($sent) < 2) {
        $p['pending'] = 0;
        $sent[$id] = '2026-10-12 18:' . sprintf('%02d', 10 + $id) . ':00';
    }
}
unset($p);

/* ---------------- coach workdays ---------------- */
$workdays = [];
foreach ($sessions as $s) {
    if ($s['cancelled'] || $s['date'] > $today->toDateString()) continue;
    foreach ($coaches as [$cid, , , , $rate, $cActive]) {
        if ($cid === 4 && $s['date'] > '2026-09-30') continue;        // coach 4 left end of September
        if ($cid === 3 && mt_rand(1, 100) > 75) continue;             // part-time
        $workdays[] = ['coach_id' => $cid, 'work_date' => $s['date'], 'amount' => $rate,
            'paid_at' => $s['date'] < '2026-10-01' ? '2026-10-01 10:00:00' : null];  // September paid, October not yet
    }
}

/* ---------------- write SQL ---------------- */
$tokens = [];
$tok = function () use (&$tokens) {
    do {
        $t = '';
        for ($i = 0; $i < 12; $i++) $t .= 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'[mt_rand(0, 31)];
    } while (isset($tokens[$t]));
    $tokens[$t] = true;
    return $t;
};

$out = [];
$out[] = <<<SQL
-- =====================================================================
--  MS Football Academy - fresh demo data (for testing all features)
--
--  !! Deletes ALL players, attendance, payments, coaches, coach days,
--  !! practice sessions and every user that is NOT Super Admin / Admin.
--  !! Use only while the system holds test data. Back up first
--  !! (phpMyAdmin > Export).
--
--  Needs the new tables first: run 2026_10_live_update.sql before this.
--  How to run: phpMyAdmin > your database > SQL tab > paste > Go.
--
--  "Today" in this data is Saturday 17 Oct 2026. What you get:
--   * {PLAYERS} players across U8, U10, U12, U14, U16, U18 and Senior,
--     training Friday only, Sunday only or both; 2 inactive players
--   * practice days 4 Sep - 8 Nov: Sunday 20 Sep moved to Monday 21 Sep,
--     Friday 9 Oct cancelled (rain) with a Saturday 10 Oct make-up,
--     Sunday 25 Oct moved to Monday 26 Oct, Friday 6 Nov cancelled,
--     Sunday 1 Nov at a different time
--   * attendance on every past practice (about 82% turn-up), one Admin
--     "extra day" (Zain Ahmed, Sunday only, marked on Friday 16 Oct)
--   * Hamdan Anush turned 12 on 1 Oct: his history shows U12 then U14.
--     Musa Rikas turns 10 on 20 Oct: his profile shows the move to U12
--   * payments with cash / bank / online, players with payment due,
--     renewal reminders pending and two already sent
--   * 4 coaches (one inactive), September salary paid, October unpaid
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM `attendances`;
DELETE FROM `payments`;
DELETE FROM `coach_workdays`;
DELETE FROM `practice_sessions`;
DELETE FROM `players`;
DELETE FROM `coaches`;
DELETE FROM `users` WHERE `type` NOT IN ('Super Admin', 'Admin');

ALTER TABLE `attendances` AUTO_INCREMENT = 1;
ALTER TABLE `payments` AUTO_INCREMENT = 1;
ALTER TABLE `coach_workdays` AUTO_INCREMENT = 1;
ALTER TABLE `practice_sessions` AUTO_INCREMENT = 1;
ALTER TABLE `players` AUTO_INCREMENT = 1;
ALTER TABLE `coaches` AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- the first admin is recorded as "marked by" / "received by"
SET @admin := (SELECT `id` FROM `users` WHERE `type` IN ('Super Admin', 'Admin') ORDER BY `id` LIMIT 1);
SQL;

$out[0] = str_replace('{PLAYERS}', (string) count($players), $out[0]);

$out[] = "\n-- coaches\nINSERT INTO `coaches` (`id`, `name`, `phone`, `nic`, `photo_path`, `daily_rate`, `is_active`, `created_at`, `updated_at`) VALUES\n" .
    implode(",\n", array_map(fn ($c) => $row([$c[0], $c[1], $c[2], $c[3], null, $c[4], $c[5], '2026-09-01 09:00:00', '2026-09-01 09:00:00']), $coaches)) . ";\n";

$out[] = "-- practice schedule (the app adds later weeks by itself)\nINSERT INTO `practice_sessions` (`slot`, `planned_date`, `session_date`, `start_time`, `end_time`, `is_cancelled`, `note`, `created_at`, `updated_at`) VALUES\n" .
    implode(",\n", array_map(fn ($s) => $row([$s['slot'], $s['planned'], $s['date'], $s['start'], $s['end'], $s['cancelled'], $s['note'], '2026-09-01 09:00:00', '2026-09-01 09:00:00']), $sessions)) . ";\n";

$prefix = config('academy.player_prefix', 'MS');
$out[] = "-- players (player number = id, so MS-0001 is id 1)\nINSERT INTO `players` (`id`, `player_no`, `qr_token`, `name`, `dob`, `phone`, `guardian_name`, `guardian_phone`, `training_days`, `photo_path`, `joined_at`, `sessions_balance`, `reminder_pending`, `last_reminded_at`, `is_active`, `created_at`, `updated_at`) VALUES\n" .
    implode(",\n", array_map(fn ($id) => $row([$id, sprintf('%s-%04d', $prefix, $id), $tok(), $players[$id]['name'], $players[$id]['dob'], null,
        $players[$id]['gname'], $players[$id]['gphone'], implode(',', $players[$id]['days']), null, $players[$id]['joined'], $players[$id]['balance'],
        $players[$id]['active'] ? $players[$id]['pending'] : 0, $sent[$id] ?? null, $players[$id]['active'], $players[$id]['joined'] . ' 09:00:00', '2026-10-17 08:00:00']),
        array_keys($players))) . ";\n";

$out[] = "-- payments\nINSERT INTO `payments` (`player_id`, `amount`, `sessions`, `method`, `note`, `paid_at`, `received_by`, `created_at`, `updated_at`) VALUES\n" .
    implode(",\n", array_map(fn ($p) => str_replace("'@admin'", '@admin', $row([$p['player_id'], $p['amount'], $p['sessions'], $p['method'], $p['note'], $p['paid_at'], '@admin', $p['paid_at'], $p['paid_at']])), $payments)) . ";\n";

usort($attendance, fn ($a, $b) => strcmp($a['created_at'], $b['created_at']));
$out[] = "-- attendance\nINSERT INTO `attendances` (`player_id`, `age_group`, `attendance_date`, `method`, `is_extra`, `marked_by`, `created_at`) VALUES\n" .
    implode(",\n", array_map(fn ($a) => str_replace("'@admin'", '@admin', $row([$a['player_id'], $a['age_group'], $a['attendance_date'], $a['method'], $a['is_extra'], '@admin', $a['created_at']])), $attendance)) . ";\n";

$out[] = "-- coach workdays\nINSERT INTO `coach_workdays` (`coach_id`, `work_date`, `amount`, `paid_at`) VALUES\n" .
    implode(",\n", array_map(fn ($w) => $row([$w['coach_id'], $w['work_date'], $w['amount'], $w['paid_at']]), $workdays)) . ";\n";

$out[] = <<<SQL
-- Optional Front Desk login for testing the limited role. Remove the "-- " in front of the
-- INSERT to use it. Login: frontdesk@msfootballacademy.test / password
-- Change the password after the first login, and delete this user before real use.
-- INSERT INTO `users` (`name`, `email`, `email_verified_at`, `password`, `type`, `created_at`, `updated_at`)
-- VALUES ('Front Desk (test)', 'frontdesk@msfootballacademy.test', NOW(), '{HASH}', 'Front Desk', NOW(), NOW());

-- quick check
SELECT
  (SELECT COUNT(*) FROM `players`)           AS players,
  (SELECT COUNT(*) FROM `practice_sessions`) AS practice_days,
  (SELECT COUNT(*) FROM `attendances`)       AS attendances,
  (SELECT COUNT(*) FROM `payments`)          AS payments,
  (SELECT COUNT(*) FROM `coach_workdays`)    AS coach_days;
SQL;
$out[count($out) - 1] = str_replace('{HASH}', password_hash('password', PASSWORD_BCRYPT), $out[count($out) - 1]);

file_put_contents($base . '/database/sql/2026_10_demo_data.sql', implode("\n", $out) . "\n");

// summary for the console
$bal = array_map(fn ($p) => $p['balance'], $players);
printf("players %d, sessions %d, attendance %d, payments %d, workdays %d\n", count($players), count($sessions), count($attendance), count($payments), count($workdays));
printf("due (<0): %d, pending reminders: %d, sent: %d, balances: %s\n",
    count(array_filter($players, fn ($p) => $p['balance'] < 0 && $p['active'])),
    count(array_filter($players, fn ($p) => $p['pending'] && $p['active'])), count($sent), implode(',', $bal));
