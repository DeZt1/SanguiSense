## Database Migrations Summary

This document outlines all database schema updates needed for the SanguiSense system. Choose your migration approach based on your current state.

### Option 1: Fresh Installation (Recommended)
If you haven't imported any schema yet:
1. Import `database/sanguisense_schema.sql` (contains all tables)
2. Import `database/sanguisense_data.sql` (contains canonical data)

### Option 2: Existing Installation with Missing Tables
If you already have `users` and `facilities` tables but are missing newer tables:
1. Run all migration files in order:
   - `database/2025-11-15_add_profile_picture_column.sql` — adds profile_picture to users table
   - `database/2025-11-15_add_patient_profiles_table.sql` — creates patient_profiles table
   - `database/2025-11-15_add_patient_requests_tables.sql` — creates patient_blood_requests and request_history tables

---

## Table Inventory

### Core Tables
- `users` — system users (donors, patients, admins)
- `facilities` — hospitals and blood banks
- `facilities.admin_id` — links to users (admin account for facility)

### Patient Portal Tables
- `patient_profiles` — extended patient profile data (date_of_birth, gender, weight, health_conditions, etc.)
  - Foreign key: `patient_id` → `users.id`
- `patient_blood_requests` — blood requests submitted by patients
  - Foreign keys: `patient_id` → `users.id`, `hospital_id` → `facilities.id`, `bloodbank_id` → `facilities.id`
- `request_history` — audit trail for blood request status changes
  - Foreign key: `request_id` → `patient_blood_requests.id`

### Blood Bank / Hospital Tables
- `inventory` — blood product inventory per facility
- `donations` — donor blood donations (references users as donor_id)
- `notifications` — user notifications
- `demand_forecasts` — predicted blood demand per facility
- `distributions` — blood distribution between facilities
- `blood_requests` — hospital/doctor-initiated blood requests
- `patients` — hospital patient records (distinct from registered users)
- `doctors` — hospital doctor records

### User Column Additions
- `users.profile_picture` — path to user's profile picture (VARCHAR 255, nullable)

---

## How to Apply Migrations

### Via phpMyAdmin (Easy)
1. Open phpMyAdmin
2. Select your `sanguisense` database
3. Click the "SQL" tab
4. Copy and paste the entire contents of the migration file
5. Click "Go"

### Via Command Line (Terminal)
```bash
# Example for fresh installation:
mysql -u root -p sanguisense < database/sanguisense_schema.sql
mysql -u root -p sanguisense < database/sanguisense_data.sql

# Example for adding migrations:
mysql -u root -p sanguisense < database/2025-11-15_add_patient_profiles_table.sql
```

### Via PHP Web Script (Programmatic)
Use the PHP `configure_python_environment`, `install_python_packages` or similar approach to execute SQL files through a web migration runner (optional).

---

## Important Notes

- **No User Data Included**: The `sanguisense_data.sql` file contains only facilities, inventory, and forecasts—no user accounts. This prevents accidental admin account creation.
- **Foreign Key References**: If importing data before schema, you may encounter foreign key constraint errors. Always import `sanguisense_schema.sql` first.
- **Existing Tables**: Migration files use `CREATE TABLE IF NOT EXISTS` to avoid errors if tables already exist.
- **NULL admin_id**: Facilities in the data file have `admin_id = NULL`. Assign admins manually via UPDATE query or admin UI after importing.

---

## Verification Checklist

After importing, verify with:
```sql
-- Check tables exist:
SHOW TABLES IN sanguisense;

-- Check users table has profile_picture:
DESCRIBE users;

-- Check patient_profiles exists:
DESCRIBE patient_profiles;

-- Check patient_blood_requests exists:
DESCRIBE patient_blood_requests;

-- Check request_history exists:
DESCRIBE request_history;
```

---

## Support

If you encounter errors during migration:
1. Check the MySQL error message for table/column names mentioned
2. Verify the table doesn't already exist (look for "already exists" errors)
3. Ensure the database is created: `CREATE DATABASE IF NOT EXISTS sanguisense;`
4. Re-run the schema first if foreign key constraint errors occur
