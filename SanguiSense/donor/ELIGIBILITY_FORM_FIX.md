# Eligibility Form Visibility Fix

## Problem
The eligibility check form questions were not visible (camouflaging/blending into the background) because the form was being hidden with `display: none;` when there were validation errors.

## Root Cause
The original PHP logic on the form tag had:
```php
<?php echo ($form_locked || !empty($eligibility_errors) || $eligibility_passed) ? 'style="display: none;"' : ''; ?>
```

This would hide the form when:
1. Form was locked (user already completed eligibility check)
2. There were eligibility errors
3. User passed eligibility

The issue is that when a user submitted the form with errors, both the error messages AND the form would be hidden, making it impossible to see what was wrong.

## Solution
Restructured the form display logic to:

1. **If form is locked** (user already completed check):
   - Show the previous result (eligible, ineligible, or neutral message)
   - Hide the form completely

2. **If form was submitted with errors**:
   - Show error messages clearly
   - Show explanatory text about what happens next
   - Keep the form visible so user can correct answers

3. **If no submission yet or after successful submission**:
   - Show the eligibility form
   - User can complete it

## Changes Made
File: `donor/eligibility_check.php`

### Before
```php
<?php if (!empty($eligibility_errors)): ?>
    <?php if ($form_locked): ?>
        <!-- Show result -->
    <?php elseif (!empty($eligibility_errors)): ?>
        <!-- Show errors -->
    <?php else: ?>
        <!-- Show info -->
    <?php endif; ?>
<?php endif; ?>

<form method="POST" class="eligibility-form" <?php echo ($form_locked || !empty($eligibility_errors) || $eligibility_passed) ? 'style="display: none;"' : ''; ?> >
```

### After
```php
<?php if ($form_locked && ($existing_status === 'ineligible' || $existing_status === 'passed' || $existing_status === 'eligible')): ?>
    <!-- FORM LOCKED - SHOW PREVIOUS RESULT -->
    <!-- Show appropriate result based on status -->
<?php elseif (!empty($eligibility_errors) && $_SERVER['REQUEST_METHOD'] == 'POST'): ?>
    <!-- FORM SUBMITTED WITH ERRORS -->
    <!-- Show error messages and explanation -->
<?php endif; ?>

<!-- ELIGIBILITY FORM - SHOW UNLESS FORM IS LOCKED -->
<?php if (!$form_locked): ?>
    <form method="POST" class="eligibility-form">
        <!-- All form fields here -->
    </form>
<?php endif; ?>
```

## Result
✅ Form questions are now **always visible** when needed
✅ Error messages display clearly when form is submitted with issues
✅ Previous results show when form is locked
✅ Clean, logical flow with no hidden elements causing confusion

## Testing
To verify the fix:

1. Go to `http://localhost/sanguisense/donor/eligibility_check.php`
2. You should see all form questions clearly
3. Try submitting with invalid answers (e.g., age 16)
4. You should see error messages AND be able to see the form to correct them
5. Fill out correctly and submit - you should be redirected to schedule page

## CSS Note
The form styling uses a glassmorphic design with:
- Semi-transparent white background: `rgba(255,255,255,0.1)`
- Blur effect: `backdrop-filter: blur(10px)`
- Light border: `rgba(255, 255, 255, 0.1)`

This creates a modern frosted glass effect against the animated gradient background.
