-- =====================================================================
--  MS Football Academy - live database update (24 October 2026)
--
--  Same result as the Laravel migration:
--    2026_10_24_000001_absences_and_closed_practices
--
--  What it does
--    1. new table `absences`: one row per player per practice day missed
--       (counted = 1 when it used one of their paid sessions)
--    2. practice_sessions.closed_at: when a finished practice day was settled.
--       Days already in the past are marked settled ONLY when the column is
--       first added, so no sessions are taken for practices before this update.
--    3. records the migration in `migrations`, so `php artisan migrate`
--       will not try to run it again
--
--  How to run
--    - Run 2026_10_live_update.sql first if you have not yet.
--    - Take a backup: phpMyAdmin > your database > Export > Go.
--    - phpMyAdmin > your database > SQL tab > paste everything > Go.
--    - Safe to run twice: every step checks first and skips what is already done.
--    - Works on MySQL 5.7+ and MariaDB 10.3+.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 1. absences
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `absences` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `player_id`       BIGINT UNSIGNED NOT NULL,
    `age_group`       VARCHAR(10) NULL,
    `attendance_date` DATE NOT NULL,
    `counted`         TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `absences_player_id_attendance_date_unique` (`player_id`, `attendance_date`),
    KEY `absences_attendance_date_index` (`attendance_date`),
    CONSTRAINT `absences_player_id_foreign` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. practice_sessions.closed_at (+ mark past days settled, first run only)
-- ---------------------------------------------------------------------
SET @add_closed := (SELECT COUNT(*) = 0 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'practice_sessions' AND COLUMN_NAME = 'closed_at');

SET @sql := IF(@add_closed,
    'ALTER TABLE `practice_sessions` ADD `closed_at` TIMESTAMP NULL AFTER `note`',
    'SELECT ''practice_sessions.closed_at already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(@add_closed,
    'UPDATE `practice_sessions` SET `closed_at` = NOW() WHERE `session_date` < CURDATE()',
    'SELECT ''past practice days were already marked''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- 3. tell Laravel this migration is done
-- ---------------------------------------------------------------------
SET @batch := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_24_000001_absences_and_closed_practices', @batch FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_10_24_000001_absences_and_closed_practices');

-- Check (optional)
SELECT
  (SELECT COUNT(*) FROM `absences`) AS absences,
  (SELECT COUNT(*) FROM `practice_sessions` WHERE `closed_at` IS NOT NULL) AS settled_practice_days;
