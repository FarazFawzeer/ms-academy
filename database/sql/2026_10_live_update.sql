-- =====================================================================
--  MS Football Academy - live database update (October 2026)
--
--  Same result as these two Laravel migrations:
--    2026_10_17_000001_add_type_and_image_to_users_table
--    2026_10_18_000001_age_groups_and_practice_schedule
--
--  What it does
--    1. users: adds `type` and `image_path` (only if missing)
--    2. new table `practice_sessions` (Friday / Sunday schedule)
--    3. players: adds `training_days` (every existing player = Friday + Sunday)
--    4. attendances: adds `age_group` (filled from date of birth on the day
--       of each attendance) and `is_extra`
--    5. removes manual batches: players.batch_id, attendances.batch_id,
--       table `batches`  (THIS DELETES THE BATCH LIST - export it first if needed)
--    6. records both migrations in `migrations`, so `php artisan migrate`
--       will not try to run them again
--
--  How to run
--    - Take a backup first: phpMyAdmin > your database > Export > Go.
--    - phpMyAdmin > your database > SQL tab > paste everything > Go.
--    - Safe to run twice: every step checks first and skips what is already done.
--    - Works on MySQL 5.7+ and MariaDB 10.3+.
--  Age groups below must match config/academy.php 'age_groups'.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 1. users.type, users.image_path
-- ---------------------------------------------------------------------
SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'type') = 0,
    'ALTER TABLE `users` ADD `type` VARCHAR(30) NOT NULL DEFAULT ''Front Desk'' AFTER `password`',
    'SELECT ''users.type already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'image_path') = 0,
    'ALTER TABLE `users` ADD `image_path` VARCHAR(255) NULL AFTER `type`',
    'SELECT ''users.image_path already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- 2. practice_sessions
--    Rows are created by the app itself (every Friday and Sunday);
--    nothing needs to be inserted here.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `practice_sessions` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slot`         VARCHAR(10)  NOT NULL,
    `planned_date` DATE         NOT NULL,
    `session_date` DATE         NOT NULL,
    `start_time`   TIME         NULL,
    `end_time`     TIME         NULL,
    `is_cancelled` TINYINT(1)   NOT NULL DEFAULT 0,
    `note`         VARCHAR(150) NULL,
    `created_at`   TIMESTAMP    NULL,
    `updated_at`   TIMESTAMP    NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `practice_sessions_slot_planned_date_unique` (`slot`, `planned_date`),
    KEY `practice_sessions_session_date_index` (`session_date`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. players.training_days  ('fri', 'sun' or 'fri,sun')
-- ---------------------------------------------------------------------
SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'players' AND COLUMN_NAME = 'training_days') = 0,
    'ALTER TABLE `players` ADD `training_days` VARCHAR(20) NOT NULL DEFAULT ''fri,sun'' AFTER `guardian_phone`',
    'SELECT ''players.training_days already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- 4. attendances.age_group, attendances.is_extra
-- ---------------------------------------------------------------------
SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attendances' AND COLUMN_NAME = 'age_group') = 0,
    'ALTER TABLE `attendances` ADD `age_group` VARCHAR(10) NULL AFTER `player_id`',
    'SELECT ''attendances.age_group already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attendances' AND COLUMN_NAME = 'is_extra') = 0,
    'ALTER TABLE `attendances` ADD `is_extra` TINYINT(1) NOT NULL DEFAULT 0 AFTER `method`',
    'SELECT ''attendances.is_extra already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- age group on the day of each attendance (U8 = age 7 or below ... U18 = 16-17, then Senior)
UPDATE `attendances` a
JOIN `players` p ON p.`id` = a.`player_id`
SET a.`age_group` = CASE
        WHEN TIMESTAMPDIFF(YEAR, p.`dob`, a.`attendance_date`) <= 7  THEN 'U8'
        WHEN TIMESTAMPDIFF(YEAR, p.`dob`, a.`attendance_date`) <= 9  THEN 'U10'
        WHEN TIMESTAMPDIFF(YEAR, p.`dob`, a.`attendance_date`) <= 11 THEN 'U12'
        WHEN TIMESTAMPDIFF(YEAR, p.`dob`, a.`attendance_date`) <= 13 THEN 'U14'
        WHEN TIMESTAMPDIFF(YEAR, p.`dob`, a.`attendance_date`) <= 15 THEN 'U16'
        WHEN TIMESTAMPDIFF(YEAR, p.`dob`, a.`attendance_date`) <= 17 THEN 'U18'
        ELSE 'Senior'
    END
WHERE a.`age_group` IS NULL;

-- ---------------------------------------------------------------------
-- 5. remove manual batches
-- ---------------------------------------------------------------------
-- attendances.batch_id: foreign key, index, column
SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attendances'
        AND CONSTRAINT_NAME = 'attendances_batch_id_foreign' AND CONSTRAINT_TYPE = 'FOREIGN KEY') > 0,
    'ALTER TABLE `attendances` DROP FOREIGN KEY `attendances_batch_id_foreign`',
    'SELECT ''attendances FK already removed''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attendances'
        AND INDEX_NAME = 'attendances_attendance_date_batch_id_index') > 0,
    'ALTER TABLE `attendances` DROP INDEX `attendances_attendance_date_batch_id_index`',
    'SELECT ''attendances date/batch index already removed''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attendances' AND COLUMN_NAME = 'batch_id') > 0,
    'ALTER TABLE `attendances` DROP COLUMN `batch_id`',
    'SELECT ''attendances.batch_id already removed''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- players.batch_id: foreign key, column
SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'players'
        AND CONSTRAINT_NAME = 'players_batch_id_foreign' AND CONSTRAINT_TYPE = 'FOREIGN KEY') > 0,
    'ALTER TABLE `players` DROP FOREIGN KEY `players_batch_id_foreign`',
    'SELECT ''players FK already removed''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'players' AND COLUMN_NAME = 'batch_id') > 0,
    'ALTER TABLE `players` DROP COLUMN `batch_id`',
    'SELECT ''players.batch_id already removed''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

DROP TABLE IF EXISTS `batches`;

-- index used by the register and reports
SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attendances'
        AND INDEX_NAME = 'attendances_attendance_date_age_group_index') = 0,
    'ALTER TABLE `attendances` ADD INDEX `attendances_attendance_date_age_group_index` (`attendance_date`, `age_group`)',
    'SELECT ''attendances date/age_group index already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- 6. tell Laravel these migrations are done
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `migrations` (
    `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `migration` VARCHAR(255) NOT NULL,
    `batch`     INT NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

SET @batch := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_17_000001_add_type_and_image_to_users_table', @batch FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_10_17_000001_add_type_and_image_to_users_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_18_000001_age_groups_and_practice_schedule', @batch FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_10_18_000001_age_groups_and_practice_schedule');

-- ---------------------------------------------------------------------
-- Check (optional): players per training day and attendances per group
-- ---------------------------------------------------------------------
SELECT `training_days`, COUNT(*) AS players FROM `players` GROUP BY `training_days`;
SELECT `age_group`, COUNT(*) AS attendances FROM `attendances` GROUP BY `age_group`;
