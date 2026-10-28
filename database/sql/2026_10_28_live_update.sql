-- =====================================================================
--  MS Football Academy - live database update (28 October 2026)
--
--  Same result as the Laravel migration:
--    2026_10_28_000001_remove_absences_on_non_training_days
--
--  What it does (data clean-up, no table changes)
--    A Sunday-only player could show as "absent" on a Friday (and the other
--    way round): players started as Friday + Sunday, absences were recorded,
--    then staff changed them to one day. This removes absences on days that
--    are not the player's training days (or before their joining date) and
--    gives back the sessions those absences used.
--
--  How to run
--    - Needs 2026_10_live_update.sql and 2026_10_24_live_update.sql first.
--    - Take a backup: phpMyAdmin > your database > Export > Go.
--    - phpMyAdmin > your database > SQL tab > paste everything > Go.
--    - Safe to run twice: the second time there is nothing left to change.
-- =====================================================================

SET NAMES utf8mb4;

-- 1. give back the sessions used by the wrong absences
UPDATE `players` p
JOIN (
    SELECT a.`player_id`, SUM(a.`counted` = 1) AS refund
    FROM `absences` a
    JOIN `players` pl ON pl.`id` = a.`player_id`
    JOIN `practice_sessions` s ON s.`session_date` = a.`attendance_date` AND s.`is_cancelled` = 0
    WHERE FIND_IN_SET(s.`slot`, pl.`training_days`) = 0
       OR (pl.`joined_at` IS NOT NULL AND a.`attendance_date` < pl.`joined_at`)
    GROUP BY a.`player_id`
) x ON x.`player_id` = p.`id`
SET p.`sessions_balance` = p.`sessions_balance` + x.refund;

-- 2. remove those absences
DELETE a FROM `absences` a
JOIN `players` pl ON pl.`id` = a.`player_id`
JOIN `practice_sessions` s ON s.`session_date` = a.`attendance_date` AND s.`is_cancelled` = 0
WHERE FIND_IN_SET(s.`slot`, pl.`training_days`) = 0
   OR (pl.`joined_at` IS NOT NULL AND a.`attendance_date` < pl.`joined_at`);

-- 3. renewal reminder off where more than one week of practice is left again
--    (one week = number of training days: 2 for Fri+Sun, 1 for one day)
UPDATE `players`
SET `reminder_pending` = 0
WHERE `reminder_pending` = 1
  AND `sessions_balance` > (LENGTH(`training_days`) - LENGTH(REPLACE(`training_days`, ',', '')) + 1);

-- 4. tell Laravel this migration is done
SET @batch := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_28_000001_remove_absences_on_non_training_days', @batch FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_10_28_000001_remove_absences_on_non_training_days');

-- Check (optional): absences left on a day that is not the player's training day (should be 0)
SELECT COUNT(*) AS wrong_day_absences_left
FROM `absences` a
JOIN `players` pl ON pl.`id` = a.`player_id`
JOIN `practice_sessions` s ON s.`session_date` = a.`attendance_date` AND s.`is_cancelled` = 0
WHERE FIND_IN_SET(s.`slot`, pl.`training_days`) = 0;
