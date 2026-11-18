-- CCDI Image-Based Lost and Found Retrieval System
-- Database: ccdi_lost_found
-- Target: MySQL / MariaDB through XAMPP phpMyAdmin
-- Charset: utf8mb4
--
-- IMPORTANT:
-- 1. Import this file into a NEW/EMPTY database, or back up your database first.
-- 2. This schema is based on the current project ZIP and the stated Chapter 1 scope.
-- 3. The migrations table is populated intentionally so `php artisan migrate` does not try to
--    recreate tables imported from this file. The matching migration files live in
--    database/migrations/ with exactly these names.
-- 4. Matching results are only potential matches; they do not establish ownership.
-- 5. REVISED to match the Laravel 13 framework tables and to avoid a MariaDB quirk:
--    cache / cache_locks.expiration BIGINT + index, jobs.attempts SMALLINT, failed_jobs
--    connection/queue VARCHAR(255) + index, sessions without a foreign key,
--    item_releases.released_at has an explicit DEFAULT CURRENT_TIMESTAMP (older XAMPP MariaDB
--    versions otherwise add ON UPDATE CURRENT_TIMESTAMP, which would rewrite the release time
--    whenever the row is edited).

CREATE DATABASE IF NOT EXISTS `ccdi_lost_found`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `ccdi_lost_found`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('student','teacher','staff','admin') NOT NULL DEFAULT 'student',
  `student_number` VARCHAR(60) NULL DEFAULT NULL,
  `department` VARCHAR(150) NULL DEFAULT NULL,
  `contact_number` VARCHAR(40) NULL DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `remember_token` VARCHAR(100) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_student_number_unique` (`student_number`),
  KEY `users_role_index` (`role`),
  KEY `users_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` VARCHAR(255) NOT NULL,
  `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `ip_address` VARCHAR(45) NULL DEFAULT NULL,
  `user_agent` TEXT NULL,
  `payload` LONGTEXT NOT NULL,
  `last_activity` INT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache` (
  `key` VARCHAR(255) NOT NULL,
  `value` MEDIUMTEXT NOT NULL,
  `expiration` BIGINT NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` VARCHAR(255) NOT NULL,
  `owner` VARCHAR(255) NOT NULL,
  `expiration` BIGINT NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` VARCHAR(255) NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `attempts` SMALLINT UNSIGNED NOT NULL,
  `reserved_at` INT UNSIGNED NULL DEFAULT NULL,
  `available_at` INT UNSIGNED NOT NULL,
  `created_at` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `total_jobs` INT NOT NULL,
  `pending_jobs` INT NOT NULL,
  `failed_jobs` INT NOT NULL,
  `failed_job_ids` LONGTEXT NOT NULL,
  `options` MEDIUMTEXT NULL,
  `cancelled_at` INT NULL DEFAULT NULL,
  `created_at` INT NOT NULL,
  `finished_at` INT NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(255) NOT NULL,
  `connection` VARCHAR(255) NOT NULL,
  `queue` VARCHAR(255) NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `exception` LONGTEXT NOT NULL,
  `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `description` VARCHAR(500) NULL DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_name_unique` (`name`),
  KEY `categories_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `item_reports` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference_code` VARCHAR(30) NOT NULL,
  `submitted_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `category_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `report_type` ENUM('lost','found') NOT NULL,
  `item_name` VARCHAR(180) NOT NULL,
  `description` TEXT NOT NULL,
  `brand` VARCHAR(120) NULL DEFAULT NULL,
  `color` VARCHAR(100) NULL DEFAULT NULL,
  `identifying_features` TEXT NULL,
  `location_description` VARCHAR(255) NOT NULL,
  `incident_date` DATE NOT NULL,
  `incident_time` TIME NULL DEFAULT NULL,
  `report_status` ENUM('pending_review','published','rejected','resolved','archived') NOT NULL DEFAULT 'pending_review',
  `reviewed_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
  `review_notes` TEXT NULL,
  `rejection_reason` TEXT NULL,
  `resolved_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_reports_reference_code_unique` (`reference_code`),
  KEY `item_reports_type_status_index` (`report_type`,`report_status`),
  KEY `item_reports_category_id_index` (`category_id`),
  KEY `item_reports_incident_date_index` (`incident_date`),
  KEY `item_reports_submitted_by_index` (`submitted_by`),
  KEY `item_reports_item_name_index` (`item_name`),
  CONSTRAINT `item_reports_submitted_by_foreign`
    FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `item_reports_category_id_foreign`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `item_reports_reviewed_by_foreign`
    FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `item_images` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_report_id` BIGINT UNSIGNED NOT NULL,
  `uploaded_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `original_filename` VARCHAR(255) NULL DEFAULT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `file_size_bytes` BIGINT UNSIGNED NULL DEFAULT NULL,
  `sha256_hash` CHAR(64) NULL DEFAULT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_images_item_report_id_index` (`item_report_id`),
  KEY `item_images_uploaded_by_index` (`uploaded_by`),
  KEY `item_images_sha256_hash_index` (`sha256_hash`),
  CONSTRAINT `item_images_item_report_id_foreign`
    FOREIGN KEY (`item_report_id`) REFERENCES `item_reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `item_images_uploaded_by_foreign`
    FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `image_features` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_image_id` BIGINT UNSIGNED NOT NULL,
  `algorithm` ENUM('SIFT','ORB') NOT NULL,
  `descriptor_path` VARCHAR(500) NULL DEFAULT NULL,
  `keypoint_count` INT UNSIGNED NULL DEFAULT NULL,
  `image_width` INT UNSIGNED NULL DEFAULT NULL,
  `image_height` INT UNSIGNED NULL DEFAULT NULL,
  `processing_status` ENUM('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `error_message` TEXT NULL,
  `extracted_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `image_features_image_algorithm_unique` (`item_image_id`,`algorithm`),
  KEY `image_features_algorithm_status_index` (`algorithm`,`processing_status`),
  CONSTRAINT `image_features_item_image_id_foreign`
    FOREIGN KEY (`item_image_id`) REFERENCES `item_images` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `image_match_results` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lost_report_id` BIGINT UNSIGNED NOT NULL,
  `found_report_id` BIGINT UNSIGNED NOT NULL,
  `algorithm_used` ENUM('SIFT','ORB') NOT NULL,
  `similarity_score` DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
  `good_matches` INT UNSIGNED NULL DEFAULT NULL,
  `lost_keypoints` INT UNSIGNED NULL DEFAULT NULL,
  `found_keypoints` INT UNSIGNED NULL DEFAULT NULL,
  `matching_details` JSON NULL,
  `match_status` ENUM('pending_review','reviewed','dismissed','confirmed_match') NOT NULL DEFAULT 'pending_review',
  `reviewed_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `image_match_pair_index` (`lost_report_id`,`found_report_id`),
  KEY `image_match_score_index` (`similarity_score`),
  KEY `image_match_status_index` (`match_status`),
  CONSTRAINT `image_match_lost_report_id_foreign`
    FOREIGN KEY (`lost_report_id`) REFERENCES `item_reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `image_match_found_report_id_foreign`
    FOREIGN KEY (`found_report_id`) REFERENCES `item_reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `image_match_reviewed_by_foreign`
    FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `claims` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `found_report_id` BIGINT UNSIGNED NOT NULL,
  `claimant_user_id` BIGINT UNSIGNED NOT NULL,
  `related_lost_report_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `claim_message` TEXT NOT NULL,
  `status` ENUM('pending','under_review','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `reviewed_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `review_notes` TEXT NULL,
  `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `claims_found_report_id_index` (`found_report_id`),
  KEY `claims_claimant_user_id_index` (`claimant_user_id`),
  KEY `claims_related_lost_report_id_index` (`related_lost_report_id`),
  KEY `claims_status_index` (`status`),
  CONSTRAINT `claims_found_report_id_foreign`
    FOREIGN KEY (`found_report_id`) REFERENCES `item_reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `claims_claimant_user_id_foreign`
    FOREIGN KEY (`claimant_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `claims_related_lost_report_id_foreign`
    FOREIGN KEY (`related_lost_report_id`) REFERENCES `item_reports` (`id`) ON DELETE SET NULL,
  CONSTRAINT `claims_reviewed_by_foreign`
    FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `item_releases` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `claim_id` BIGINT UNSIGNED NOT NULL,
  `found_report_id` BIGINT UNSIGNED NOT NULL,
  `released_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `released_to_user_id` BIGINT UNSIGNED NOT NULL,
  `release_reference` VARCHAR(40) NULL DEFAULT NULL,
  `verification_notes` TEXT NULL,
  `released_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_releases_claim_id_unique` (`claim_id`),
  UNIQUE KEY `item_releases_release_reference_unique` (`release_reference`),
  KEY `item_releases_found_report_id_index` (`found_report_id`),
  KEY `item_releases_released_by_index` (`released_by`),
  CONSTRAINT `item_releases_claim_id_foreign`
    FOREIGN KEY (`claim_id`) REFERENCES `claims` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `item_releases_found_report_id_foreign`
    FOREIGN KEY (`found_report_id`) REFERENCES `item_reports` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `item_releases_released_by_foreign`
    FOREIGN KEY (`released_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `item_releases_released_to_user_id_foreign`
    FOREIGN KEY (`released_to_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `action` VARCHAR(120) NOT NULL,
  `subject_type` VARCHAR(120) NULL DEFAULT NULL,
  `subject_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `description` TEXT NULL,
  `details` JSON NULL,
  `ip_address` VARCHAR(45) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_index` (`user_id`),
  KEY `activity_logs_action_index` (`action`),
  KEY `activity_logs_subject_index` (`subject_type`,`subject_id`),
  KEY `activity_logs_created_at_index` (`created_at`),
  CONSTRAINT `activity_logs_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` CHAR(36) NOT NULL,
  `type` VARCHAR(255) NOT NULL,
  `notifiable_type` VARCHAR(255) NOT NULL,
  `notifiable_id` BIGINT UNSIGNED NOT NULL,
  `data` TEXT NOT NULL,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `migrations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` VARCHAR(255) NOT NULL,
  `batch` INT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `migrations_migration_unique` (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- These entries correspond to the schema above. Claude must create migration
-- files with these exact names so the SQL import and Laravel migrations stay aligned.
INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
('0001_01_01_000000_create_users_table', 1),
('0001_01_01_000001_create_cache_table', 1),
('0001_01_01_000002_create_jobs_table', 1),
('2026_10_28_000001_create_categories_table', 2),
('2026_10_28_000002_create_item_reports_table', 2),
('2026_10_28_000003_create_item_images_table', 2),
('2026_10_28_000004_create_image_features_table', 2),
('2026_10_28_000005_create_image_match_results_table', 2),
('2026_10_28_000006_create_claims_table', 2),
('2026_10_28_000007_create_item_releases_table', 2),
('2026_10_28_000008_create_activity_logs_table', 2),
('2026_10_28_000009_create_notifications_table', 2),
('2026_10_28_000010_add_ccdi_fields_to_users_table', 2);

SET FOREIGN_KEY_CHECKS = 1;

-- Starter categories (safe to run repeatedly).
INSERT IGNORE INTO `categories` (`name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
('Electronics', 'Phones, chargers, earphones, laptops, and related devices', 1, NOW(), NOW()),
('ID and Documents', 'School IDs, identification cards, papers, and documents', 1, NOW(), NOW()),
('Bags and Wallets', 'Backpacks, purses, wallets, and bags', 1, NOW(), NOW()),
('Keys', 'Keys and key holders', 1, NOW(), NOW()),
('Clothing and Accessories', 'Clothing, caps, watches, jewelry, and accessories', 1, NOW(), NOW()),
('Books and School Supplies', 'Books, notebooks, pens, and other school supplies', 1, NOW(), NOW()),
('Other', 'Items that do not fit another category', 1, NOW(), NOW());
