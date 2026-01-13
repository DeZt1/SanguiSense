## SanguiSense Database Setup Guide

### The Problem

You encountered the error:
```
Error registering patient: SQLSTATE[42S02]: Base table or view not found: 1146 Table 'sanguisense.patient_profiles' doesn't exist
```

This occurred because the patient registration system tries to create a `patient_profiles` table record for each new patient, but the table didn't exist in the database schema.

### What Changed

The database schema has been updated to include all necessary tables:

1. **`patient_profiles`** — Stores extended patient profile data
   - Links to `users` table via `patient_id`
   - Stores: date_of_birth, gender, weight, health_conditions, allergies, emergency contact info

2. **`patient_blood_requests`** — Stores blood requests submitted by patients
   - Separate from the hospital-initiated `blood_requests` table
   - Stores: patient_id, facility references, blood type, quantity, urgency, reason, notes, status, dates

3. **`request_history`** — Audit trail for blood request status changes
   - Links to `patient_blood_requests`
   - Tracks actions and status changes

4. **`users.profile_picture`** — New column for profile pictures (VARCHAR 255, nullable)

### Files Updated

- **`database/sanguisense_schema.sql`** — Complete schema with all tables
- **`database/2025-11-15_add_patient_profiles_table.sql`** — Migration to add patient_profiles
- **`database/2025-11-15_add_patient_requests_tables.sql`** — Migration to add patient request tables
- **`database/2025-11-15_add_profile_picture_column.sql`** — Migration to add profile_picture column
- **`database/MIGRATION_GUIDE.md`** — Comprehensive migration instructions

### How to Fix Your Database

#### Option A: Fresh Start (Recommended)
If you haven't made any important data yet:
1. Delete your current `sanguisense` database
2. Import `database/sanguisense_schema.sql` 
3. Import `database/sanguisense_data.sql` (contains canonical facilities and inventory data)

#### Option B: Add Missing Tables to Existing Installation
If you have existing users and want to keep them:
1. Run `database/2025-11-15_add_patient_profiles_table.sql`
2. Run `database/2025-11-15_add_patient_requests_tables.sql`
3. Run `database/2025-11-15_add_profile_picture_column.sql` (if not already done)

#### How to Run Migrations

**Via phpMyAdmin:**
1. Open phpMyAdmin
2. Select your `sanguisense` database
3. Click the "SQL" tab
4. Copy the entire contents of the migration file
5. Click "Go"

**Via MySQL CLI:**
```bash
mysql -u root -p sanguisense < database/2025-11-15_add_patient_profiles_table.sql
mysql -u root -p sanguisense < database/2025-11-15_add_patient_requests_tables.sql
```

### Verification

After running migrations, verify tables exist:
```sql
USE sanguisense;
SHOW TABLES;

-- Should include: patient_profiles, patient_blood_requests, request_history
```

Verify columns:
```sql
DESCRIBE users;  -- Should include 'profile_picture'
DESCRIBE patient_profiles;  -- Should show all columns
DESCRIBE patient_blood_requests;  -- Should show request columns
DESCRIBE request_history;  -- Should show audit columns
```

### Now You Can...

✅ Register new patients without errors  
✅ Create patient profiles with extended info  
✅ Submit blood requests from patient portal  
✅ Track request history  
✅ Upload patient profile pictures  

### Questions?

- **Data models**: See `database/MIGRATION_GUIDE.md`
- **Schema**: See `database/sanguisense_schema.sql`
- **Foreign keys**: All tables properly reference `users` and `facilities`
- **No user data included**: The data file contains no test users—import users when ready

---

**Next Steps:**
1. Run the appropriate migration(s)
2. Try registering a patient again
3. Verify patient can update profile with extended info
4. Verify patient can submit blood requests

