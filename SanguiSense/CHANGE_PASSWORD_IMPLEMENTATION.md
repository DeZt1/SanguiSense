## Change Password Feature - Implementation Summary

### Overview
Added complete "Change Password" functionality to all four portals (Donor, Hospital, Blood Bank, Patient) with working password updates in the database.

### Changes Made

#### 1. Fixed PHP Warnings in `includes/config.php`
**Problem:**
- Constants (DB_HOST, DB_NAME, DB_USER, DB_PASS, BASE_URL) defined multiple times causing warnings
- `session_start()` called multiple times causing session already active notice

**Solution:**
- Wrapped all constants in `if (!defined(...))` checks
- Wrapped PDO connection in `if (!isset($pdo))` check
- Wrapped `session_start()` in `if (session_status() === PHP_SESSION_NONE)` check
- **Result:** No more duplicate definition or session warnings

#### 2. Created Change Password Pages

**Files Created:**
- `hospital/change_password.php` — Hospital admin change password
- `bloodbank/change_password.php` — Blood bank admin change password
- `patient/change_password.php` — Patient change password
- (Donor already had one: `donor/change_password.php`)

**Features of Change Password Pages:**
✅ Requires old password verification (password_verify)
✅ New password must be ≥ 8 characters
✅ Password confirmation matching validation
✅ Updates password in users table with bcrypt hashing
✅ Portal-specific styling (colors match each portal)
✅ Success/error messaging
✅ Back link to profile page

#### 3. Updated Profile Pages

**Hospital Profile (`hospital/profile.php`):**
- Fixed malformed HTML (was severely corrupted)
- Recreated with clean structure matching donor layout
- Added "Change Password" button in action menu

**Bloodbank Profile (`bloodbank/profile.php`):**
- Added "Change Password" button before "Manage Facility"

**Patient Profile (`patient/profile.php`):**
- Added "Change Password" button in action menu
- Added action buttons container for consistency

**Donor Profile (`donor/profile.php`):**
- Already had "Change Password" button (no changes needed)

#### 4. Password Update Flow

When user changes password:
1. Old password validated against bcrypt hash in database
2. New password hashed with `password_hash($newPassword, PASSWORD_BCRYPT)`
3. Update query: `UPDATE users SET password = ? WHERE id = ?`
4. Database immediately stores new hashed password
5. Success message displayed
6. Next login uses new password

#### 5. Database Security
- Passwords stored as bcrypt hashes (PASSWORD_BCRYPT)
- Old password verified using `password_verify()`
- No plaintext passwords stored or transmitted
- Uses PDO prepared statements (SQL injection safe)
- Column: `users.password`

---

### How to Test

#### Test Change Password:
1. Log in to any portal (donor, hospital, bloodbank, patient)
2. Go to Profile page
3. Click "Change Password" button
4. Enter current password, new password (min 8 chars), confirm
5. Click "Change Password"
6. Should see success message
7. Log out and try logging in with new password
8. Should work!

#### Test Failed Cases:
- Enter wrong old password → "Old password is incorrect"
- New password < 8 chars → "New password must be at least 8 characters long"
- Passwords don't match → "Passwords do not match"
- Empty fields → "All fields are required"

---

### Files Modified
1. `includes/config.php` — Added conditional checks
2. `hospital/profile.php` — Recreated (was corrupted)
3. `bloodbank/profile.php` — Added change password button
4. `patient/profile.php` — Added change password button & actions
5. Created 3 new change_password.php files

### Database Interactions
- **Table:** `users`
- **Column:** `password` (VARCHAR, bcrypt hashed)
- **Operation:** SELECT and UPDATE
- **Security:** Bcrypt hashing, prepared statements, password_verify()

---

### Portal-Specific Colors
- **Donor:** var(--yellow, gold)
- **Hospital:** var(--hospital-blue, #1e88e5)
- **Blood Bank:** var(--bloodbank-purple, #8e44ad)
- **Patient:** var(--patient-teal, #00bcd4)

Each change password page uses the respective portal's color scheme.

---

### Next Steps (Optional)
- Add "Forgot Password" recovery feature
- Add password strength meter in UI
- Add password change history logging
- Email notification on password change

---

**Status:** ✅ Complete and tested
