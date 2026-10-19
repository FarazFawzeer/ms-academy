# MS Football Academy – project handoff

Management system for **MS Football Academy** (Sri Lanka). Freelance project for Faraz. Built on his existing Laravel admin theme (Taplox-based). Live target: **msfootballacademy.com** on Spaceship shared hosting (cPanel).

Rule from the owner: always give **complete replacement files**, not diffs. Non-technical staff use it, so keep the UI simple, clean and mobile friendly.

## Stack
Laravel 11/12, PHP 8.2+, Blade, Bootstrap 5, iconify-icon (solar set), ApexCharts, SweetAlert (Swal), html5-qrcode (camera needs HTTPS), bacon/bacon-qr-code, maatwebsite/excel, GD for images. Assets built with `npm run build` locally, then `public/build` is uploaded.

Timezone is **Asia/Colombo** (`config/app.php` default and `APP_TIMEZONE` in `.env`). Scan times, payment times and "today" (attendance date, undo, coach workdays) all depend on it.

## Business rules
- Fee **LKR 3,500 per 8 sessions** (`config/academy.php`: `fee`, `sessions_per_package`).
- `players.sessions_balance`: +8 per payment, −1 per attendance. Negative = payment due.
- Reminder fires once when the balance drops to exactly 2 (`remind_at_session` = 6), sets `reminder_pending`. A payment clears it.
- One attendance per player per day (UNIQUE `player_id, attendance_date`). Undo is allowed for today only and gives the session back.
- Coach pay = daily wage. A workday snapshots the rate; paid days are locked.
- Player IDs use `config('academy.player_prefix', 'MS')`, e.g. MS-0012.
- Roles in `users.type`: Super Admin, Admin, Front Desk (`UserController::TYPES`). Front Desk can scan, add players, take payments, see reminders, the register and age groups. Admin-only (via `EnsureUserType:Super Admin,Admin`): payment delete, practice schedule, coaches, workdays, salary, reports, users.

## Age groups, training days, practice schedule (client change, Oct 2026)
Replaced manual batches (table, model, controller, page all removed).
- **Age group is never stored on the player.** `App\Support\AgeGroup` works it out from `dob` and today's date, so a player moves up on their birthday. Bands in `config/academy.php` `age_groups` (name => highest age): U8 ≤7, U10 8-9, U12 10-11, U14 12-13, U16 14-15, U18 16-17, then `senior_group` (Senior). `Player::scopeInAgeGroup()` turns a group into a DOB range for SQL. `attendances.age_group` keeps the group *on that day* for reports.
- **Training days**: `players.training_days` = `fri`, `sun` or `fri,sun` (keys of `config('academy.training_days')`, which also holds the default times, currently 4:00-5:30 PM for both; Sunday time not confirmed by the client). Player form has Friday/Sunday toggles, at least one required.
- **Practice schedule** (`practice_sessions`, `PracticeSession` model, one schedule for the whole academy): regular Fri/Sun sessions are created on demand by `PracticeSession::generate($from, $to)` (INSERT OR IGNORE on `slot, planned_date`, never before `schedule_start`). `fillAround()` covers ~5 weeks back to `schedule_weeks_ahead` (8) ahead; `forDate($d)` returns the non-cancelled session on a day. Admin can move a session (`session_date` != `planned_date`), change time/note, cancel/restore, or add an extra one for Friday or Sunday players. One active practice per date. A session with attendance is locked (no move/cancel). Moved/cancelled sessions are never re-created.
- Do not name static model methods `fill()` or `on()` (Eloquent already has them). Dates on `PracticeSession` are stored as plain `Y-m-d` via mutators because exact-date lookups depend on it.
- **Attendance rule** (`AttendanceController@mark`): allowed only when today has a practice AND the player trains on that slot. Otherwise 422 `no_practice` / `wrong_day`. An Admin gets `can_override` and can resend with `force=1`; the row is saved with `is_extra = 1`. Front Desk cannot override. Session counting (−1 per attendance) is unchanged; absences cost nothing.
- **Register** (`attendance/register`, replaces History): month grid of players × practice days grouped by age group, like the client's old Excel. Cells: present, AB (expected, not marked), extra (blue), not their day (·), cancelled (hatched), future (blank). Columns for moved (MOVED), cancelled (OFF) and extra days (EXTRA). Attended/expected and rate per player, present count per day, Excel export with the same grid.
- **Print ID Cards** (`players/print`, sidebar Players > Print ID Cards): pick All players, Age group, ID range (MS-0010 to MS-0025, reversed or "MS-" prefixed input accepted) or Pick players (search + tick list, select all shown). Optional training-day filter, include-inactive switch, order by name or ID; live card and A4 sheet count. Opens `players/cards` in a new tab with `ids`, `from`/`to`, `group`, `days`, `inactive`, `sort` query params. Single-card buttons on the list and profile still link to `players/cards?ids=N` directly. Player numbers equal the id (zero padded), so the range filters on `id`.
- Dashboard shows today's practice (or the next one); scan page shows a "Today: Sunday practice" / "No practice today" banner; manual search tags players "Not today".

## Auth and routes (`routes/web.php`)
- One `LoginController` handles login and the lock screen (both post to `login.post`). Rate limit 5 tries / 60 s per email+IP. Users whose `type` is not in `UserController::TYPES` are refused.
- "Intended URL" is only honoured for `/admin*` or `/index`, otherwise the dashboard (this fixed random 404s after login).
- **No theme catch-all.** The old `/{any}`, `/{first}/{second}`, `/{first}/{second}/{third}` routes and `RoutingController` were removed (they rendered any Blade file by URL and mostly crashed with 500s). Every real page has its own route; anything else is a 404. Theme demo views (`auth/signup`, `auth/password`, `pages/*`, `customer/*`, `layouts-demo/*`, `ui/*` etc.) are still in `resources/views` but have no route.
- Topbar user menu: "Lock screen" uses `route('lock-screen')`; "Logout" is a POST form to `route('logout')` (it used to be a plain link to the sign-in page that never logged out). Before removing a route name, grep views for `route\s*\(` because the theme writes `route ('name' , ...)` with spaces.
- Route names must stay unique so `route:cache` works. `routes/auth.php` (Breeze) is still in the folder but is not loaded anywhere.

## Images
All uploads go through `App\Support\ImageUpload::square()`: GD re-encodes the file as a square JPEG with a random name in `public/uploads/{players,coaches,users}`. Never save an upload with its original name or extension (that let a disguised `.svg`/`.php`-style file land in `public/`). `ImageUpload::delete()` removes the old file on replace/delete.

## Database
- Migrations: Laravel defaults, the two old theme `customers` migrations (now skip themselves when there is no `customers` table), `2026_10_14_000001_create_academy_tables` (coaches, batches, players, attendances, payments, coach_workdays), `2026_10_17_000001_add_type_and_image_to_users_table` (`users.type` default Front Desk, `users.image_path`) and `2026_10_18_000001_age_groups_and_practice_schedule` (creates `practice_sessions`; adds `players.training_days` default `fri,sun`, `attendances.age_group` (back-filled from DOB on the attendance date) and `attendances.is_extra`; drops `batch_id` columns and the `batches` table). It rolls back cleanly.
- **Local dev DB** (`ms-academy` on XAMPP MySQL) was built from the dummy SQL, so the Laravel default migrations (users, cache, jobs, customers) show as Pending although the tables exist. Plain `php artisan migrate` fails there ("users already exists"); run new migrations one by one with `php artisan migrate --path=database/migrations/<file>.php`. The live DB is fresh, so plain `migrate` works there. Both 2026-10-17/18 migrations were run on the local DB on 2026-10-17 and all pages were re-checked against it.
- `DatabaseSeeder` creates the **first Super Admin** and asks for name, email and password in the terminal. Nothing is hard-coded. Re-running with an existing email changes nothing.
- The old dummy data SQL (`academy_dummy_data.sql`, demo login `admin@msacademy.test` / `password`) is **not in this repo** and predates age groups. Never import it into the live database: it deletes data.
- **New demo data: `database/sql/2026_10_demo_data.sql`** (phpMyAdmin > SQL). Deletes all players, attendance, payments, coaches, coach days, practice sessions and every user that is not Super Admin/Admin, then loads 27 players (all age groups, Fri/Sun/both, 2 inactive), practice days 4 Sep-8 Nov with moved / cancelled / extra / re-timed examples, 200 attendances incl. one Admin extra day, payments, 2 dues, 5 pending + 2 sent reminders, 4 coaches with Sept paid / Oct unpaid. "Today" in the data = 17 Oct 2026. Needs `2026_10_live_update.sql` first. Optional Front Desk test login is in the file, commented out. Re-running it is safe (same result). Verified on a scratch MariaDB: balance = paid - attended for every player, age group on each day matches DOB, attendance only on own held training days (or flagged extra), all pages 200. Generated by `php database/sql/make_demo_sql.php .` (replays the app rules, fixed random seed); if the rules change, regenerate rather than hand-editing.

## Done
Dashboard (teal/slate palette, chart 7D/14D/30D) · QR attendance (scan + name search + undo) · player profiles and printable CR80 ID cards (2×5 per A4) · payments with auto session credit · reminders with WhatsApp click-to-chat · automatic age groups · training days · practice schedule · attendance register (month grid + Excel) · coaches, daily attendance, salary · reports with Excel export · single Users page with Add User popup · redesigned login and lock screen · dummy SQL data for all tables · feature-status and QA spreadsheets.

UI pass completed on: dashboard, players, payments, reminders, coaches, users, attendance scan, register, schedule, age groups. Pattern: icon-only action buttons with no background (pay green, view blue, edit orange, print purple, delete red), soft-tint badges with fixed width, auto-applying filters (no Search button), filters right aligned.

`Player::balanceBadge()`, `ageGroupBadge()` and `daysBadge()` are shared by every page (all built on `Player::softBadge()`). They use plain ASCII text ("Due: 1 unpaid") because the middle dot `·` showed up garbled on the server.

## Verified (2026-10-17, local PHP 8.2 + throwaway SQLite)
- `php artisan migrate` runs clean on an empty database; `route:list` showed 49 routes (before the Oct 18 change).
- Every page returns 200 for Super Admin; Front Desk gets 200 on its pages and 403 on admin-only ones.
- Scan (QR token, case-insensitive), duplicate scan, undo, payment (+8), add player with first fee, Excel payments export, seeder.
- Uploads: files named `.php`/`.phtml` are rejected; a GIF+PHP file named `.svg` is saved as a clean random `.jpg`.
- 2026-10-18 (same setup, clock faked to Fri/Sat/Sun/Mon): migration from old batch data, age-group maths and DB filter, every wrong-day / no-practice / override case, schedule move/cancel/restore/lock/clash/add, moved Sunday practice accepted on the Monday only, all 21 pages for Admin + Front Desk, register and report exports. Register, schedule, age groups, scan and player form were also checked in a browser at desktop and phone width.

## Known gaps / not verified
- Not yet run on real MySQL or on the live host. Untested: camera scanning (needs HTTPS), printed ID card QR scanning, the dummy SQL on MySQL.
- Any Admin can create another Super Admin from the Users page (no extra restriction).
- Excel exports write player names as-is (a name starting with `=` would be treated as a formula by Excel).
- If a payment leaves the balance below 2 the reminder never fires for that package (it only fires when the balance hits exactly 2).
- Compiled views in `storage/framework/views` were committed in the first commits; they should be removed from git and ignored.
- WhatsApp is click-to-chat only (`wa.me` link with the message prefilled, `Player::whatsappLink()`, template in `config/academy.php`). No automatic sending. Option if the client wants it: Meta WhatsApp Cloud API (~LKR 0.66 per utility message, check current rates) hooked in at `ReminderController@sent`.
- Coaches status and users role badges are still solid; everything else uses the soft style.
- Coaches are no longer linked to a group (that lived on batches). Add a coach per age group later if the client asks.
- Sunday practice time is a guess (same as Friday, 4:00-5:30 PM). Confirm with the client and change `training_days` in `config/academy.php`. `schedule_start` (env `ACADEMY_SCHEDULE_START`, default 2026-10-01) should be the go-live date.
- Age-group bands are only editable in config, not in the app.
- Optional ideas not built: a "due" filter on the players list, updating the client proposal's WhatsApp cost from ~LKR 4 to ~LKR 0.66 per message.

## Deployment (Spaceship, in progress)
1. Upload the project to `/home/<user>/academy-app` and set the domain's document root to `/academy-app/public` (alternative: public contents in the domain folder with `$app->usePublicPath(__DIR__)` in `index.php`).
2. Run `composer install` **locally** and upload `vendor` (Composer on the host is unconfirmed).
3. cPanel MySQL: names are prefixed (`account_dbname`, `account_user`); add the user to the database with ALL PRIVILEGES. `.env`: `DB_HOST=localhost`, `DB_PORT=3306`, quote the password if it has `@ # $ =` or spaces. Also set `APP_TIMEZONE=Asia/Colombo`, `APP_ENV=production`, `APP_URL=https://msfootballacademy.com`.
4. After editing `.env`: `php artisan config:clear` (or delete `bootstrap/cache/config.php`).
5. Create tables with `php artisan migrate --force`, then the first admin with `php artisan db:seed --force` (it asks for name, email, password).
   - **Live DB that already has the old tables** (no terminal needed): back it up (phpMyAdmin > Export), then paste `database/sql/2026_10_live_update.sql` into phpMyAdmin > SQL. It applies the 2026-10-17 and 2026-10-18 migrations (users.type/image_path, practice_sessions, training_days, attendance age_group back-fill, is_extra, drops batches) and records them in `migrations`. Every step checks first, so it is safe to run twice. Tested on local MariaDB 10.4: same columns, keys and back-filled data as the Laravel migrations. Age bands in the file are hard-coded and must match `config/academy.php`.
6. Enable the PHP extensions (gd, mbstring, zip, etc.) and SSL. `APP_DEBUG=false` before going live.
7. The first login attempt failed with DB error 1045 because the DB username in `.env` held the wrong value (it looked like a password). The DB password was shown in an error page, so it must be reset. Do not write credentials into this file.
8. Delete the test files `index.html` and `check.php` (domain-test) before uploading Laravel. Run `php artisan view:clear` after replacing views and `php artisan route:clear` after replacing `routes/web.php`.

## Views (resources/views)
admin/users · attendance/register, scan · auth/lock-screen, signin, partials/auth-style · coaches/form, index, salary, workdays · dashboard · groups/index · layouts/partials/sidebar, title-meta (page title, favicon, robots noindex, theme colour) · partials/js-helpers · payments/index · players/_payment-modal, cards, form, index, print, show · profile/edit · reminders/index · reports/index · schedule/index

Shared JS helper object `AC` (in `partials/js-helpers`): `AC.form`, `AC.act`, `AC.call`, `AC.toast`, `AC.esc`, `AC.debounce`, `AC.errors`.
