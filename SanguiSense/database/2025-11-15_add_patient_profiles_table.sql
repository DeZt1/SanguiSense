-- Migration: Create patient_profiles table
-- Date: 2025-11-15
-- Description: Add patient_profiles table to store patient-specific extended profile data
-- 
-- How to run this manually in phpMyAdmin:
-- 1. Go to phpMyAdmin
-- 2. Select database 'sanguisense'
-- 3. Go to SQL tab
-- 4. Copy and paste this entire file
-- 5. Click "Go" to execute

USE `sanguisense`;

-- Create patient_profiles table if it doesn't exist
CREATE TABLE IF NOT EXISTS `patient_profiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL UNIQUE,
  `date_of_birth` DATE NULL,
  `gender` VARCHAR(20) NULL,
  `weight_kg` DECIMAL(5,2) NULL,
  `health_conditions` TEXT NULL,
  `allergies` TEXT NULL,
  `emergency_contact_name` VARCHAR(255) NULL,
  `emergency_contact_phone` VARCHAR(20) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_patient_profiles_user` FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- End of migration
