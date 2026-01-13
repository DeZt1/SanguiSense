-- Migration: create donor_communications table
-- Run this SQL in your database (e.g., via phpMyAdmin or mysql CLI)

CREATE TABLE IF NOT EXISTS `donor_communications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `donor_id` INT UNSIGNED NOT NULL,
  `subject` VARCHAR(255) DEFAULT NULL,
  `message` TEXT,
  `contact_method` VARCHAR(32) DEFAULT 'email',
  `sent_by` INT UNSIGNED DEFAULT NULL,
  `sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`donor_id`),
  INDEX (`sent_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
