-- sanguisense.sql
CREATE DATABASE IF NOT EXISTS sanguisense;
USE sanguisense;

-- Users table (for both donors and admins)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    user_type ENUM('donor', 'hospital_admin', 'bloodbank_admin') NOT NULL,
    blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    city VARCHAR(100) NULL,
    last_donation_date DATE NULL,
    health_conditions TEXT NULL,
    is_eligible BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Hospitals/Blood Banks table
CREATE TABLE facilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('hospital', 'blood_bank') NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    admin_id INT,
    FOREIGN KEY (admin_id) REFERENCES users(id)
);

-- Blood inventory table
CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    facility_id INT NOT NULL,
    blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    quantity INT NOT NULL,
    expiration_date DATE NOT NULL,
    status ENUM('available', 'expired', 'used') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (facility_id) REFERENCES facilities(id)
);

-- Donations table
CREATE TABLE donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    donor_id INT NOT NULL,
    facility_id INT NOT NULL,
    blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    donation_date DATE NOT NULL,
    quantity INT DEFAULT 1,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id) REFERENCES users(id),
    FOREIGN KEY (facility_id) REFERENCES facilities(id)
);

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('alert', 'info', 'reminder') NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Demand forecasting table
CREATE TABLE demand_forecasts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    facility_id INT NOT NULL,
    blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    predicted_demand INT NOT NULL,
    forecast_date DATE NOT NULL,
    confidence_level DECIMAL(3,2) DEFAULT 0.8,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (facility_id) REFERENCES facilities(id)
);

CREATE TABLE IF NOT EXISTS distributions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_facility_id INT NOT NULL,
    to_facility_id INT NOT NULL,
    blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    quantity INT NOT NULL,
    distribution_date DATE NOT NULL,
    purpose ENUM('routine_supply', 'emergency', 'scheduled_surgery', 'critical_care', 'other') NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_facility_id) REFERENCES facilities(id),
    FOREIGN KEY (to_facility_id) REFERENCES facilities(id)
);

-- Patients table
CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    medical_record_number VARCHAR(100) UNIQUE NOT NULL,
    facility_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (facility_id) REFERENCES facilities(id)
);

-- Doctors table
CREATE TABLE IF NOT EXISTS doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    facility_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (facility_id) REFERENCES facilities(id)
);

-- Blood requests table
CREATE TABLE IF NOT EXISTS blood_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    facility_id INT NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    quantity INT NOT NULL,
    urgency ENUM('routine', 'urgent', 'emergency', 'critical') DEFAULT 'routine',
    purpose ENUM('surgery', 'trauma', 'chronic_anemia', 'cancer_treatment', 'childbirth', 'other') NOT NULL,
    required_date DATE NOT NULL,
    status ENUM('pending', 'fulfilled', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fulfilled_at TIMESTAMP NULL,
    FOREIGN KEY (facility_id) REFERENCES facilities(id),
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id)
);

-- Insert sample data with proper password hashes (password = 'password')
INSERT INTO users (email, password, name, user_type, blood_type, phone, address, city) VALUES
('admin@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Hospital Admin', 'hospital_admin', NULL, '1234567890', '123 Hospital St', 'Cityville'),
('admin@bloodbank.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Blood Bank Admin', 'bloodbank_admin', NULL, '1234567891', '456 Blood Ave', 'Cityville'),
('donor@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Donor', 'donor', 'O+', '1234567892', '789 Donor Lane', 'Cityville'),
('donor2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Smith', 'donor', 'A-', '1234567893', '456 Donor Ave', 'Townsville');

INSERT INTO facilities (name, type, address, city, phone, email, admin_id) VALUES
('City General Hospital', 'hospital', '123 Hospital St', 'Cityville', '1234567890', 'contact@cityhospital.com', 1),
('Central Blood Bank', 'blood_bank', '456 Blood Ave', 'Cityville', '1234567891', 'contact@centralbloodbank.com', 2),
('Town Medical Center', 'hospital', '789 Health Blvd', 'Townsville', '1234567894', 'contact@townmedical.com', NULL);

INSERT INTO inventory (facility_id, blood_type, quantity, expiration_date, status) VALUES
(1, 'O+', 25, '2024-12-31', 'available'),
(1, 'A+', 15, '2024-12-25', 'available'),
(2, 'O+', 50, '2024-12-30', 'available'),
(2, 'B-', 5, '2024-12-20', 'available'),
(2, 'AB+', 8, '2024-12-28', 'available');

INSERT INTO donations (donor_id, facility_id, blood_type, donation_date, quantity, status) VALUES
(3, 1, 'O+', '2024-01-15', 1, 'completed'),
(3, 2, 'O+', '2024-03-20', 1, 'completed'),
(4, 1, 'A-', '2024-02-10', 1, 'completed');

INSERT INTO notifications (user_id, title, message, type) VALUES
(3, 'Blood Donation Appreciation', 'Thank you for your recent donation! You helped save lives.', 'info'),
(3, 'Urgent Need for O+ Blood', 'There is an urgent need for O+ blood in your area. Please consider donating.', 'alert'),
(4, 'Donation Eligibility', 'You are now eligible to donate blood again. Schedule your next appointment!', 'reminder');

INSERT INTO demand_forecasts (facility_id, blood_type, predicted_demand, forecast_date, confidence_level) VALUES
(1, 'O+', 30, '2024-12-01', 0.85),
(1, 'A+', 20, '2024-12-01', 0.78),
(2, 'O+', 45, '2024-12-01', 0.82),
(2, 'B-', 12, '2024-12-01', 0.75);