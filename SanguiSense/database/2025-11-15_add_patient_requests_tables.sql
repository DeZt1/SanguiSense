-- Migration: Create patient_blood_requests and request_history tables
-- Date: 2025-11-15
-- Description: Add tables to support patient blood request submission and tracking
-- 
-- How to run this manually in phpMyAdmin:
-- 1. Go to phpMyAdmin
-- 2. Select database 'sanguisense'
-- 3. Go to SQL tab
-- 4. Copy and paste this entire file
-- 5. Click "Go" to execute

USE `sanguisense`;

-- Create patient_blood_requests table
CREATE TABLE IF NOT EXISTS `patient_blood_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,
  `hospital_id` INT NULL,
  `bloodbank_id` INT NULL,
  `blood_type` ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
  `quantity_units` INT NOT NULL,
  `urgency` ENUM('routine', 'urgent', 'emergency', 'critical') DEFAULT 'routine',
  `reason` VARCHAR(255) NULL,
  `notes` TEXT NULL,
  `required_date` DATE NOT NULL,
  `status` ENUM('pending', 'fulfilled', 'cancelled', 'in_progress') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pbr_patient` FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pbr_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `facilities`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pbr_bloodbank` FOREIGN KEY (`bloodbank_id`) REFERENCES `facilities`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create request_history table
CREATE TABLE IF NOT EXISTS `request_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `request_id` INT NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `new_status` VARCHAR(100) NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_rh_request` FOREIGN KEY (`request_id`) REFERENCES `patient_blood_requests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- End of migration
