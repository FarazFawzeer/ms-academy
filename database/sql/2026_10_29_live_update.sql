-- =====================================================================
--  MS Football Academy - live database update (29 October 2026)
--
--  Same result as the Laravel migration:
--    2026_10_29_000001_manual_schedule_remove_auto_days
--
--  What it does (data clean-up, no table changes)
--    The admin now picks every practice day on the Practice Schedule
--    calendar; nothing is created automatically any more. This removes the
--    FUTURE Fridays / Sundays the old automatic schedule created that nobody
--    touched (same date as planned, regular time 4:00-5:30 PM, no note, not
--    cancelled, nobody marked). Past days, today and every day the admin
--    changed are kept.
--
--  After running it: open Practice Schedule and add the coming practice
--  days on the calendar (Quick select > Fridays + Sundays > Add practice).
--
--  How to run
--    - Needs 2026_10_live_update.sql, 2026_10_24_live_update.sql and
--      2026_10_28_live_update.sql first.
--    - Take a backup: phpMyAdmin > your database > Export > Go.
--    - phpMyAdmin > your database > SQL tab > paste everything > Go.
--    - Safe to run twice. The times below must match 'training_days' in
--      config/academy.php (Friday and Sunday 16:00 - 17:30).
-- =====================================================================

SET NAMES utf8mb4;

-- 1. remove untouched future automatic days (DAYOFWEEK: 1 = Sunday, 6 = Friday)
DELETE s FROM `practice_sessions` s
LEFT JOIN `attendances` a ON a.`attendance_date` = s.`session_date`
WHERE s.`session_date` > CURDATE()
  AND s.`session_date` = s.`planned_date`
  AND s.`is_cancelled` = 0
  AND s.`note` IS NULL
  AND s.`closed_at` IS NULL
  AND a.`id` IS NULL
  AND (   (s.`slot` = 'fri' AND DAYOFWEEK(s.`session_date`) = 6 AND s.`start_time` = '16:00:00' AND s.`end_time` = '17:30:00')
       OR (s.`slot` = 'sun' AND DAYOFWEEK(s.`session_date`) = 1 AND s.`start_time` = '16:00:00' AND s.`end_time` = '17:30:00'));

-- 2. tell Laravel this migration is done
SET @batch := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_29_000001_manual_schedule_remove_auto_days', @batch FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_10_29_000001_manual_schedule_remove_auto_days');

-- Check (optional): practice days from today on (add the coming ones on the calendar)
SELECT `session_date`, `slot`, `start_time`, `is_cancelled`, `note`
FROM `practice_sessions`
WHERE `session_date` >= CURDATE()
ORDER BY `session_date`;
