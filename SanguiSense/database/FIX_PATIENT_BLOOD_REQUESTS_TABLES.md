# Fix: Missing `patient_blood_requests` and `request_history` Tables

## Problem
When trying to access the patient dashboard, you get the error:
```
Fatal error: Uncaught PDOException: SQLSTATE[42S02]: Base table or view not found: 1146 Table 'sanguisense.patient_blood_requests' doesn't exist
```

## Root Cause
The `patient_blood_requests` and `request_history` tables are required by the patient portal (`patient/dashboard.php`, `patient/send_request.php`, etc.) but haven't been created in the database yet.

## Solution

### Option 1: Run Migration via PHP Script (Recommended)

1. **Navigate to the migration runner:**
   - Open your browser and go to: `http://localhost/sanguisense/database/run_migration_patient_requests.php`

2. **What happens:**
   - The script reads the migration SQL file
   - Creates both `patient_blood_requests` and `request_history` tables automatically
   - Shows a success message on completion

3. **After successful migration:**
   - Patient dashboard will load without errors
   - Patients can submit blood requests
   - Request history is tracked

### Option 2: Run Migration Manually in phpMyAdmin

1. **Open phpMyAdmin:**
   - Go to `http://localhost/phpmyadmin`

2. **Select the database:**
   - Click on `sanguisense` database

3. **Go to SQL tab:**
   - Click the "SQL" tab at the top

4. **Paste the migration SQL:**
   - Copy the entire content from: `database/2025-11-15_add_patient_requests_tables.sql`
   - Paste it into the SQL editor
   - Click "Go" to execute

### Option 3: Run via MySQL Command Line

```bash
mysql -u root -p sanguisense < database/2025-11-15_add_patient_requests_tables.sql
```

## What These Tables Contain

### `patient_blood_requests` Table
Stores blood requests submitted by patients.

| Column | Type | Purpose |
|--------|------|---------|
| `id` | INT (Primary Key) | Unique request identifier |
| `patient_id` | INT (Foreign Key) | Links to `users.id` (patient) |
| `hospital_id` | INT (Foreign Key, nullable) | Links to `facilities.id` (hospital) |
| `bloodbank_id` | INT (Foreign Key, nullable) | Links to `facilities.id` (blood bank) |
| `blood_type` | ENUM | Requested blood type (A+, A-, B+, etc.) |
| `quantity_units` | INT | Number of units needed |
| `urgency` | ENUM | routine, urgent, emergency, or critical |
| `reason` | VARCHAR(255) | Why blood is needed |
| `notes` | TEXT | Additional notes |
| `required_date` | DATE | Date blood is needed by |
| `status` | ENUM | pending, fulfilled, cancelled, or in_progress |
| `created_at` | TIMESTAMP | When request was created |
| `updated_at` | TIMESTAMP | When request was last updated |

### `request_history` Table
Tracks changes and actions on blood requests.

| Column | Type | Purpose |
|--------|------|---------|
| `id` | INT (Primary Key) | Unique history entry ID |
| `request_id` | INT (Foreign Key) | Links to `patient_blood_requests.id` |
| `action` | VARCHAR(100) | What action was taken (e.g., "created", "fulfilled", "cancelled") |
| `new_status` | VARCHAR(100) | Status after the action |
| `notes` | TEXT | Details about the action |
| `created_at` | TIMESTAMP | When action was recorded |

## How It Works

### Patient Blood Request Flow
1. **Patient submits request** → Record created in `patient_blood_requests`
2. **Request status changes** → Entry added to `request_history`
3. **Blood bank processes** → Status updated from pending to in_progress/fulfilled
4. **Patient views history** → Dashboard queries both tables

### Example Usage
```sql
-- View patient's blood requests
SELECT * FROM patient_blood_requests WHERE patient_id = 5;

-- View request history for a specific request
SELECT * FROM request_history WHERE request_id = 12 ORDER BY created_at DESC;

-- Find urgent requests
SELECT * FROM patient_blood_requests WHERE urgency IN ('urgent', 'emergency', 'critical') AND status = 'pending';
```

## Files Involved

- **Migration SQL:** `database/2025-11-15_add_patient_requests_tables.sql`
- **Migration Runner:** `database/run_migration_patient_requests.php` (newly created)
- **Patient Dashboard:** `patient/dashboard.php` (uses patient_blood_requests)
- **Patient Send Request:** `patient/send_request.php` (creates patient_blood_requests)
- **Request History:** `patient/request_history.php` (views request_history)

## Testing

After running the migration, test the patient portal:

1. Go to `http://localhost/sanguisense/patient/dashboard.php`
2. You should see the patient dashboard without errors
3. You can click "Send Blood Request" to submit a request
4. View your request history on the "Request History" page

## Verification

To verify the tables were created, run this query in phpMyAdmin:

```sql
DESCRIBE patient_blood_requests;
DESCRIBE request_history;
```

You should see all the columns listed above.

## Related Migrations

You may need to run these migrations as well:
- `database/2025-11-15_add_patient_profiles_table.sql` — Creates patient_profiles table
- `database/2025-11-15_add_profile_picture_column.sql` — Adds profile_picture column to users

Run them in order using the corresponding migration runners:
- `database/run_migration_patient_profiles.php`
- `database/run_migration_patient_requests.php`
- `database/run_migration_profile_picture.php` (if needed)

## Additional Notes

- The `patient_blood_requests` table has foreign keys to:
  - `users` (patient submitting request)
  - `facilities` (hospital or blood bank, either one or both can be set)
  - Cascading deletes ensure data consistency

- The `request_history` table tracks all changes to requests, providing a complete audit trail

- Status flow: pending → in_progress → fulfilled (or cancelled at any point)

- Urgency levels allow for prioritization: routine < urgent < emergency < critical
