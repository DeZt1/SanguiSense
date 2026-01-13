# Database Migrations Guide

## Overview
This document lists all database migrations needed for SanguiSense and how to run them.

## Quick Start

Run all migrations in order using the provided PHP scripts:

```
1. http://localhost/sanguisense/database/run_migration_patient_profiles.php
2. http://localhost/sanguisense/database/run_migration_patient_requests.php
3. http://localhost/sanguisense/database/run_migration_profile_picture.php (if needed)
```

## Migrations List

### 1. Patient Profiles Table
**File:** `database/2025-11-15_add_patient_profiles_table.sql`
**Runner:** `database/run_migration_patient_profiles.php`

**Purpose:** Stores extended patient profile information
- Date of birth, gender, weight
- Health conditions, allergies
- Emergency contact information

**What it creates:**
- `patient_profiles` table
- Foreign key to `users` table

**Required by:**
- Patient registration
- Patient profile page
- Patient dashboard

---

### 2. Patient Blood Requests Tables
**File:** `database/2025-11-15_add_patient_requests_tables.sql`
**Runner:** `database/run_migration_patient_requests.php`

**Purpose:** Allows patients to submit blood requests and track them
- Store patient blood requests
- Track request status changes over time

**What it creates:**
- `patient_blood_requests` table
- `request_history` table

**Required by:**
- Patient dashboard (shows requests)
- Patient send request page (submits requests)
- Patient request history page

---

### 3. Profile Picture Column
**File:** `database/2025-11-15_add_profile_picture_column.sql`
**Runner:** `database/run_migration_profile_picture.php`

**Purpose:** Adds profile picture support to all user types
- Stores profile picture path in users table

**What it creates:**
- `profile_picture` column in `users` table

**Required by:**
- Profile pages (all portals)
- Avatar display in sidebars
- User profile pictures

---

## Manual Migration Steps

If you prefer to run migrations manually in phpMyAdmin:

### Method 1: Via phpMyAdmin Interface
1. Open http://localhost/phpmyadmin
2. Click on `sanguisense` database
3. Click the **SQL** tab
4. Copy content from migration file (e.g., `2025-11-15_add_patient_profiles_table.sql`)
5. Paste into the SQL editor
6. Click **Go** to execute
7. Look for success message

### Method 2: Via MySQL Command Line
Open terminal/PowerShell and run:

```bash
# Patient profiles
mysql -u root -p sanguisense < database/2025-11-15_add_patient_profiles_table.sql

# Patient requests
mysql -u root -p sanguisense < database/2025-11-15_add_patient_requests_tables.sql

# Profile pictures (if needed)
mysql -u root -p sanguisense < database/2025-11-15_add_profile_picture_column.sql
```

You'll be prompted to enter your MySQL password.

---

## Migration Status Check

### Check which tables exist
In phpMyAdmin SQL tab, run:

```sql
-- List all tables in database
SHOW TABLES;

-- Or check specific tables
DESCRIBE patient_profiles;
DESCRIBE patient_blood_requests;
DESCRIBE request_history;
```

### Expected output after all migrations:
```
Tables_in_sanguisense
users
facilities
inventory
donations
notifications
demand_forecasts
distributions
patient_profiles          ← From migration 1
patients
doctors
blood_requests
patient_blood_requests    ← From migration 2
request_history           ← From migration 2
```

---

## Table Dependencies

```
users (base table)
├── patient_profiles (references users.id)
├── patient_blood_requests (references users.id)
│   └── request_history (references patient_blood_requests.id)
├── facilities
├── donations
└── notifications
```

---

## Common Errors & Solutions

### Error: Table 'sanguisense.patient_profiles' doesn't exist
**Solution:** Run migration 1: `run_migration_patient_profiles.php`

### Error: Table 'sanguisense.patient_blood_requests' doesn't exist
**Solution:** Run migration 2: `run_migration_patient_requests.php`

### Error: Unknown column 'profile_picture' in 'field list'
**Solution:** Run migration 3: `run_migration_profile_picture.php`

### Error: Access denied
**Solution:** Check that your database user (root) has proper permissions. You may need to re-import the schema with proper credentials.

### Error: Duplicate key name 'fk_patient_profiles_user'
**Solution:** The migration has already been run. This is safe to ignore.

---

## Verification Checklist

After running all migrations, verify everything is set up:

- [ ] Patient profiles table exists
  ```sql
  SELECT COUNT(*) as count FROM patient_profiles;
  ```

- [ ] Patient blood requests table exists
  ```sql
  SELECT COUNT(*) as count FROM patient_blood_requests;
  ```

- [ ] Request history table exists
  ```sql
  SELECT COUNT(*) as count FROM request_history;
  ```

- [ ] Can access patient dashboard without errors
  ```
  http://localhost/sanguisense/patient/dashboard.php
  ```

- [ ] Can view patient profile without errors
  ```
  http://localhost/sanguisense/patient/profile.php
  ```

- [ ] Can submit blood request without errors
  ```
  http://localhost/sanguisense/patient/send_request.php
  ```

---

## Running All Migrations at Once

You can create a batch migration runner. Here's what you need:

1. Visit `run_migration_patient_profiles.php`
2. After success, visit `run_migration_patient_requests.php`
3. After success, visit `run_migration_profile_picture.php` (if using)

All three will complete in about 10 seconds total.

---

## Need Help?

If you encounter issues:

1. **Check the error message** — Note the exact table name or column
2. **Run the corresponding migration** — Use the migration runner for that table
3. **Verify in phpMyAdmin** — Run DESCRIBE to check the table structure
4. **Check browser console** — Look for JavaScript errors that might help

For detailed information about each migration, see:
- `FIX_PATIENT_PROFILES_TABLE.md`
- `FIX_PATIENT_BLOOD_REQUESTS_TABLES.md`
- README_DATABASE_SETUP.md
