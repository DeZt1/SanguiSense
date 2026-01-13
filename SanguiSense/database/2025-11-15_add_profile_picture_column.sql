-- 2025-11-15_add_profile_picture_column.sql
-- Add `profile_picture` column to `users` table if your installation doesn't have it.
-- Run this in phpMyAdmin (SQL tab) or via the mysql CLI as a user with ALTER privileges.
-- Example (PowerShell / CLI):
-- & 'C:\xampp\mysql\bin\mysql.exe' -u root -p -e "USE sanguisense; ALTER TABLE `users` ADD COLUMN `profile_picture` VARCHAR(255) NULL AFTER `created_at`;"

ALTER TABLE `users`
  ADD COLUMN `profile_picture` VARCHAR(255) NULL AFTER `created_at`;

-- Notes:
-- - Some older MySQL versions do not support "IF NOT EXISTS" for ADD COLUMN; running this while the column exists will cause an error.
-- - If you prefer a guard to avoid an error, first check INFORMATION_SCHEMA for the column and only run the ALTER if it is not present.
--   Example SELECT to check presence:
--   SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
--    WHERE TABLE_SCHEMA = 'sanguisense' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'profile_picture';
-- - If the SELECT above returns 0, run the ALTER TABLE statement.
