-- sanguisense_schema.sql
-- SCHEMA-ONLY dump for SanguiSense
-- This file creates the database and all required tables (no INSERTs / no data).
-- Import this first (for example via phpMyAdmin -> SQL tab), then you may import your data-only SQL.

CREATE DATABASE IF NOT EXISTS `sanguisense` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sanguisense`;

-- --------------------------------------------------
-- Table structure for `users`
-- --------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `user_type` ENUM('donor', 'patient', 'hospital_admin', 'bloodbank_admin') NOT NULL,
  `blood_type` ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NULL,
  `phone` VARCHAR(20) NULL,
  `address` TEXT NULL,
  `city` VARCHAR(100) NULL,
  `last_donation_date` DATE NULL,
  `health_conditions` TEXT NULL,
  `is_eligible` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Table structure for `facilities`
-- --------------------------------------------------
CREATE TABLE IF NOT EXISTS `facilities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `type` ENUM('hospital', 'blood_bank') NOT NULL,
  `address` TEXT NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `admin_id` INT NULL,
  CONSTRAINT `fk_facilities_admin` FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Table structure for `inventory`
-- --------------------------------------------------
CREATE TABLE IF NOT EXISTS `inventory` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `facility_id` INT NOT NULL,
  `blood_type` ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
  `quantity` INT NOT NULL,
  `expiration_date` DATE NOT NULL,
  `status` ENUM('available', 'expired', 'used') DEFAULT 'available',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_inventory_facility` FOREIGN KEY (`facility_id`) REFERENCES `facilities`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Table structure for `donations`
-- --------------------------------------------------
CREATE TABLE IF NOT EXISTS `donations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `donor_id` INT NOT NULL,
  `facility_id` INT NOT NULL,
  `blood_type` ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
  `donation_date` DATE NOT NULL,
  `quantity` INT DEFAULT 1,
  `status` ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_donations_donor` FOREIGN KEY (`donor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_donations_facility` FOREIGN KEY (`facility_id`) REFERENCES `facilities`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Table structure for `notifications`
-- --------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('alert', 'info', 'reminder') NOT NULL,
  `is_read` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Table structure for `demand_forecasts`
-- --------------------------------------------------
CREATE TABLE IF NOT EXISTS `demand_forecasts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `facility_id` INT NOT NULL,
  `blood_type` ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
  `predicted_demand` INT NOT NULL,
  `forecast_date` DATE NOT NULL,
  `confidence_level` DECIMAL(3,2) DEFAULT 0.80,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_forecasts_facility` FOREIGN KEY (`facility_id`) REFERENCES `facilities`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Table structure for `distributions`
-- --------------------------------------------------
CREATE TABLE IF NOT EXISTS `distributions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `from_facility_id` INT NOT NULL,
  `to_facility_id` INT NOT NULL,
  `blood_type` ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
  `quantity` INT NOT NULL,
  `distribution_date` DATE NOT NULL,
  `purpose` ENUM('routine_supply', 'emergency', 'scheduled_surgery', 'critical_care', 'other') NOT NULL,
  `status` ENUM('pending', 'completed', 'cancelled') DEFAULT 'completed',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_distributions_from` FOREIGN KEY (`from_facility_id`) REFERENCES `facilities`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_distributions_to` FOREIGN KEY (`to_facility_id`) REFERENCES `facilities`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Table structure for `patient_profiles`
-- --------------------------------------------------
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

-- --------------------------------------------------
-- Table structure for `patients`
-- --------------------------------------------------
CREATE TABLE IF NOT EXISTS `patients` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `medical_record_number` VARCHAR(100) UNIQUE NOT NULL,
  `facility_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_patients_facility` FOREIGN KEY (`facility_id`) REFERENCES `facilities`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Table structure for `doctors`
-- --------------------------------------------------
CREATE TABLE IF NOT EXISTS `doctors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `facility_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_doctors_facility` FOREIGN KEY (`facility_id`) REFERENCES `facilities`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Table structure for `blood_requests`
-- --------------------------------------------------
CREATE TABLE IF NOT EXISTS `blood_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `facility_id` INT NOT NULL,
  `patient_id` INT NOT NULL,
  `doctor_id` INT NOT NULL,
  `blood_type` ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
  `quantity` INT NOT NULL,
  `urgency` ENUM('routine', 'urgent', 'emergency', 'critical') DEFAULT 'routine',
  `purpose` ENUM('surgery', 'trauma', 'chronic_anemia', 'cancer_treatment', 'childbirth', 'other') NOT NULL,
  `required_date` DATE NOT NULL,
  `status` ENUM('pending', 'fulfilled', 'cancelled') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `fulfilled_at` TIMESTAMP NULL,
  CONSTRAINT `fk_requests_facility` FOREIGN KEY (`facility_id`) REFERENCES `facilities`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_requests_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_requests_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Table structure for `patient_blood_requests`
-- --------------------------------------------------
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

-- --------------------------------------------------
-- Table structure for `request_history`
-- --------------------------------------------------
CREATE TABLE IF NOT EXISTS `request_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `request_id` INT NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `new_status` VARCHAR(100) NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_rh_request` FOREIGN KEY (`request_id`) REFERENCES `patient_blood_requests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- End of schema-only dump
