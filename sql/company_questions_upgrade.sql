-- ============================================================
-- Company Questions Upgrade Migration
-- Run this FIRST before any seed files.
-- Safe to run multiple times (uses IF NOT EXISTS / IF NOT column).
-- Does NOT drop, rename, or delete any existing columns or rows.
-- ============================================================

-- 1. Add new nullable/defaulted columns to company_questions
--    (each wrapped in a procedure so re-runs are safe)

DELIMITER //

DROP PROCEDURE IF EXISTS _cq_upgrade_columns//
CREATE PROCEDURE _cq_upgrade_columns()
BEGIN
    -- difficulty (may already exist from add_difficulty_column.sql)
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'company_questions' AND COLUMN_NAME = 'difficulty') THEN
        ALTER TABLE `company_questions` ADD COLUMN `difficulty` ENUM('Beginner','Intermediate','Advanced','Expert') NOT NULL DEFAULT 'Beginner';
    END IF;

    -- If difficulty exists but uses old enum with 'Pro' instead of 'Expert', update it
    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'company_questions' AND COLUMN_NAME = 'difficulty' AND COLUMN_TYPE LIKE '%Pro%') THEN
        ALTER TABLE `company_questions` MODIFY COLUMN `difficulty` ENUM('Beginner','Intermediate','Advanced','Expert') NOT NULL DEFAULT 'Beginner';
        UPDATE `company_questions` SET `difficulty` = 'Expert' WHERE `difficulty` = '';
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'company_questions' AND COLUMN_NAME = 'question_type') THEN
        ALTER TABLE `company_questions` ADD COLUMN `question_type` VARCHAR(50) DEFAULT NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'company_questions' AND COLUMN_NAME = 'explanation') THEN
        ALTER TABLE `company_questions` ADD COLUMN `explanation` TEXT DEFAULT NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'company_questions' AND COLUMN_NAME = 'programming_language') THEN
        ALTER TABLE `company_questions` ADD COLUMN `programming_language` VARCHAR(100) DEFAULT NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'company_questions' AND COLUMN_NAME = 'time_complexity') THEN
        ALTER TABLE `company_questions` ADD COLUMN `time_complexity` VARCHAR(100) DEFAULT NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'company_questions' AND COLUMN_NAME = 'space_complexity') THEN
        ALTER TABLE `company_questions` ADD COLUMN `space_complexity` VARCHAR(100) DEFAULT NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'company_questions' AND COLUMN_NAME = 'sample_input') THEN
        ALTER TABLE `company_questions` ADD COLUMN `sample_input` TEXT DEFAULT NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'company_questions' AND COLUMN_NAME = 'sample_output') THEN
        ALTER TABLE `company_questions` ADD COLUMN `sample_output` TEXT DEFAULT NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'company_questions' AND COLUMN_NAME = 'source_type') THEN
        ALTER TABLE `company_questions` ADD COLUMN `source_type` VARCHAR(50) NOT NULL DEFAULT 'practice';
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'company_questions' AND COLUMN_NAME = 'category') THEN
        ALTER TABLE `company_questions` ADD COLUMN `category` VARCHAR(100) DEFAULT NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'company_questions' AND COLUMN_NAME = 'created_at') THEN
        ALTER TABLE `company_questions` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
    END IF;
END//

CALL _cq_upgrade_columns()//
DROP PROCEDURE IF EXISTS _cq_upgrade_columns//

DELIMITER ;

-- Add indexes for performance
CREATE INDEX IF NOT EXISTS idx_cq_company ON `company_questions` (`company_name`);
CREATE INDEX IF NOT EXISTS idx_cq_category ON `company_questions` (`category`);
CREATE INDEX IF NOT EXISTS idx_cq_difficulty ON `company_questions` (`difficulty`);


-- 2. Bookmark table
CREATE TABLE IF NOT EXISTS `student_question_bookmarks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `question_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_student_bookmark` (`student_id`, `question_id`),
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`question_id`) REFERENCES `company_questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 3. Progress / solved table
CREATE TABLE IF NOT EXISTS `student_question_progress` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `question_id` INT NOT NULL,
    `status` ENUM('attempted','solved') NOT NULL DEFAULT 'attempted',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_student_progress` (`student_id`, `question_id`),
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`question_id`) REFERENCES `company_questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 4. Update existing rows: set category from topic where category is NULL
UPDATE `company_questions` SET `category` = 'Core CS' WHERE `category` IS NULL AND `topic` IS NOT NULL;
UPDATE `company_questions` SET `source_type` = 'practice' WHERE `source_type` = '' OR `source_type` IS NULL;

