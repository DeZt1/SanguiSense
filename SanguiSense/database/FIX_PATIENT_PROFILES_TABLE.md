# Fix: Missing `patient_profiles` Table Error

## Problem
When trying to register a new patient, you get the error:
```
Error registering patient: SQLSTATE[42S02]: Base table or view not found: 1146 Table 'sanguisense.patient_profiles' doesn't exist
```

## Root Cause
The `patient_profiles` table is required by the patient registration system (`patient/register.php`) but hasn't been created in the database yet.

## Solution

### Option 1: Run Migration via PHP Script (Recommended)

1. **Navigate to the migration runner:**
   - Open your browser and go to: `http://localhost/sanguisense/database/run_migration_patient_profiles.php`

2. **What happens:**
   - The script reads the migration SQL file
   - Creates the `patient_profiles` table automatically
   - Shows a success message on completion

3. **After successful migration:**
   - You can immediately register new patients
   - The `patient_profiles` entry will be created for each new patient

### Option 2: Run Migration Manually in phpMyAdmin

1. **Open phpMyAdmin:**
   - Go to `http://localhost/phpmyadmin`

2. **Select the database:**
   - Click on `sanguisense` database

3. **Go to SQL tab:**
   - Click the "SQL" tab at the top

4. **Paste the migration SQL:**
   - Copy the entire content from: `database/2025-11-15_add_patient_profiles_table.sql`
   - Paste it into the SQL editor
   - Click "Go" to execute

### Option 3: Run via MySQL Command Line

```bash
mysql -u root -p sanguisense < database/2025-11-15_add_patient_profiles_table.sql
```

## What the `patient_profiles` Table Contains

The table stores extended patient profile information:

| Column | Type | Purpose |
|--------|------|---------|
| `id` | INT (Primary Key) | Unique identifier |
| `patient_id` | INT (Unique, Foreign Key) | Links to `users.id` |
| `date_of_birth` | DATE | Patient's date of birth |
| `gender` | VARCHAR(20) | Gender information |
| `weight_kg` | DECIMAL(5,2) | Patient weight in kg |
| `health_conditions` | TEXT | Medical conditions |
| `allergies` | TEXT | Known allergies |
| `emergency_contact_name` | VARCHAR(255) | Emergency contact name |
| `emergency_contact_phone` | VARCHAR(20) | Emergency contact phone |
| `created_at` | TIMESTAMP | When record was created |
| `updated_at` | TIMESTAMP | When record was last updated |

## How It Works

When a patient registers:

1. **User record created** in `users` table with `user_type = 'patient'`
2. **Patient profile record created** in `patient_profiles` table with:
   - `patient_id` = the new user's ID
   - Other fields left NULL initially (filled in later on profile page)

## Testing

After running the migration, test the patient registration:

1. Go to `http://localhost/sanguisense/patient/register.php`
2. Fill in the registration form
3. Submit the form
4. You should see success message (no more table not found error)

## Verification

To verify the table was created, run this query in phpMyAdmin:

```sql
DESCRIBE patient_profiles;
```

You should see all the columns listed above.

## Files Involved

- **Migration SQL:** `database/2025-11-15_add_patient_profiles_table.sql`
- **Migration Runner:** `database/run_migration_patient_profiles.php` (newly created)
- **Patient Registration:** `patient/register.php` (uses this table)
- **Patient Profile:** `patient/profile.php` (uses this table)
- **Patient Dashboard:** `patient/dashboard.php` (uses this table)

## Additional Notes

- The `patient_profiles` table has a foreign key constraint to `users(id)`, so patient records will be automatically deleted if the associated user is deleted
- The `patient_id` column is UNIQUE to ensure only one profile per patient
- All columns except `patient_id`, `created_at`, and `updated_at` are optional (NULL allowed)
