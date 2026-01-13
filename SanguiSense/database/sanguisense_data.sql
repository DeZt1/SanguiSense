-- sanguisense_data.sql
-- DATA-ONLY dump for SanguiSense
-- Import this after the schema (sanguisense_schema.sql). Contains INSERT statements only.

USE `sanguisense`;

-- Users (sample) -- REMOVED
-- Per request, user/sample user INSERTs have been omitted from this data-only file.
-- Import users separately when you're ready. Leaving users out prevents FK conflicts
-- and avoids creating admin accounts in your database automatically.

-- Facilities
-- Note: admin_id set to NULL because users are not included in this data file.
INSERT INTO facilities (name, type, address, city, phone, email, admin_id) VALUES
('Premiere Medical Center', 'hospital', '123 Premiere Ave', 'Cabanatuan', '09170000001', 'info@premieremedical.com', NULL),
('GoodSam Medical Center', 'hospital', '45 GoodSam St', 'Cabanatuan', '09170000002', 'contact@goodsam-cabanatuan.com', NULL),
('Nueva Ecija Doctors Hospital', 'hospital', '78 Doctors Road', 'Cabanatuan', '09170000003', 'info@nedh.com', NULL),
('GoodSam Medical Center (Gapan)', 'hospital', '9 Gapan Highway', 'Gapan', '09170000004', 'contact@goodsam-gapan.com', NULL),
('Palayan City Emergency Hospital', 'hospital', '1 Palayan Blvd', 'Palayan', '09170000005', 'info@palayanhospital.com', NULL),
('San Jose City General Hospital', 'hospital', '12 SJCGH Rd', 'San Jose', '09170000006', 'contact@sanjosegh.com', NULL),
('San Antonio District Hospital', 'hospital', 'San Antonio Road', 'San Antonio', '09170000007', 'contact@sanantonio-dh.com', NULL),
('Guimba District Hospital', 'hospital', 'Guimba Rd', 'Guimba', '09170000008', 'contact@guimba-dh.com', NULL),
('Philippine Red Cross - Nueva Ecija Blood Services', 'blood_bank', 'PRC Compound, Cabanatuan', 'Cabanatuan', '09170000009', 'bloodservices@prc.org.ph', NULL);

-- Inventory
INSERT INTO inventory (facility_id, blood_type, quantity, expiration_date, status) VALUES
(1, 'O+', 25, '2024-12-31', 'available'),
(1, 'A+', 15, '2024-12-25', 'available'),
(4, 'O+', 50, '2024-12-30', 'available'),
(4, 'B-', 5, '2024-12-20', 'available'),
(9, 'AB+', 8, '2024-12-28', 'available');

-- Donations (omitted)
-- Donation rows reference users (donor_id). Since users are omitted from this data file,
-- the donations INSERTs are intentionally left out to avoid foreign-key violations.
-- Re-enable these inserts after you import users into the database.

-- Notifications (omitted)
-- Notifications reference user rows. They are omitted here to avoid FK conflicts.
-- Re-enable after importing users.

-- Demand forecasts
INSERT INTO demand_forecasts (facility_id, blood_type, predicted_demand, forecast_date, confidence_level) VALUES
(1, 'O+', 30, '2024-12-01', 0.85),
(1, 'A+', 20, '2024-12-01', 0.78),
(4, 'O+', 45, '2024-12-01', 0.82),
(4, 'B-', 12, '2024-12-01', 0.75);
