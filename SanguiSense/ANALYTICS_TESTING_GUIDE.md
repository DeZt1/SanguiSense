# Analytics Testing & Data Population Guide

## Quick Start

### Access Analytics Pages
1. **Blood Bank Portal:** `/bloodbank/analytics.php`
2. **Hospital Portal:** `/hospital/analytics.php`
3. **Donor Portal:** `/donor/analytics.php`

## Testing Data Setup

To test analytics with actual data, you need to populate the following tables:

### 1. Create Test Facilities
```sql
INSERT INTO facilities (name, type, address, city, email, phone, admin_id) VALUES
('GoodSam Medical Center', 'hospital', '123 Hospital Way', 'Cabanatuan City', 'contact@goodsam.com', '0966-1234567', 2),
('RedCross Blood Bank', 'blood_bank', '456 Blood Bank Road', 'Cabanatuan City', 'contact@redcross.com', '0966-7654321', 1);
```

### 2. Create Test Donors
```sql
INSERT INTO users (name, email, password, user_type, blood_type, phone) VALUES
('John Donor', 'john@donor.com', '$2y$10$...hash...', 'donor', 'O+', '0966-1111111'),
('Jane Donor', 'jane@donor.com', '$2y$10$...hash...', 'donor', 'A+', '0966-2222222'),
('Bob Helper', 'bob@donor.com', '$2y$10$...hash...', 'donor', 'B+', '0966-3333333');
```

### 3. Create Test Inventory
```sql
INSERT INTO inventory (facility_id, blood_type, quantity, status, expiration_date) VALUES
(1, 'O+', 50, 'available', DATE_ADD(CURDATE(), INTERVAL 30 DAY)),
(1, 'A+', 35, 'available', DATE_ADD(CURDATE(), INTERVAL 45 DAY)),
(1, 'B+', 25, 'available', DATE_ADD(CURDATE(), INTERVAL 20 DAY)),
(1, 'AB+', 15, 'available', DATE_ADD(CURDATE(), INTERVAL 60 DAY)),
(2, 'O+', 100, 'available', DATE_ADD(CURDATE(), INTERVAL 40 DAY)),
(2, 'A+', 75, 'available', DATE_ADD(CURDATE(), INTERVAL 35 DAY));
```

### 4. Create Test Donations
```sql
INSERT INTO donations (facility_id, donor_id, blood_type, quantity, status, donation_date) VALUES
(1, 1, 'O+', 450, 'completed', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1, 2, 'A+', 450, 'completed', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1, 3, 'B+', 450, 'completed', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 1, 'O+', 450, 'completed', DATE_SUB(NOW(), INTERVAL 10 DAY)),
(2, 2, 'A+', 450, 'completed', DATE_SUB(NOW(), INTERVAL 15 DAY));
```

### 5. Create Test Blood Requests
```sql
INSERT INTO blood_requests (facility_id, blood_type, quantity, urgency, status) VALUES
(1, 'O+', 500, 'urgent', 'pending'),
(1, 'A+', 300, 'normal', 'fulfilled'),
(1, 'B+', 200, 'emergency', 'pending'),
(2, 'AB+', 100, 'normal', 'fulfilled');
```

### 6. Create Test Distributions
```sql
INSERT INTO distributions (from_facility_id, to_facility_id, blood_type, quantity, status) VALUES
(2, 1, 'O+', 100, 'completed'),
(2, 1, 'A+', 75, 'completed'),
(2, 1, 'B+', 50, 'completed');
```

## Expected Results After Data Population

### Blood Bank Analytics Should Show:
✅ **Key Metrics:**
- Total Blood Units: 215 (50+35+25+15+100+75... based on your data)
- Total Donations: 5 (from donations table)
- Distributed Units: 225 (sum of all distributions)
- Top Donors Listed: 3 (John, Jane, Bob)

✅ **Charts:**
- Inventory Distribution: 8-type doughnut showing O+, A+, B+, AB+ (others empty)
- Donation Trends: Line chart showing donations per month
- Distribution Analysis: Bar chart showing blood type distribution
- Donor Demographics: Pie chart showing all system donors

✅ **Tables:**
- Top Donors: John, Jane, Bob with donation counts and totals
- Expiration Alerts: Items expiring within 14 days (only if dates are set)

### Hospital Analytics Should Show:
✅ **Key Metrics:**
- Total Blood Units: [Based on inventory]
- Pending Requests: 2 (O+ and B+ marked as pending)
- Urgent Cases: 2 (1 urgent + 1 emergency)
- Recent Donations: Last 8 donations with facility info

✅ **Charts:**
- Similar inventory and donation trends
- Blood Request Analysis: Showing pending/urgent/fulfilled breakdown

### Donor Analytics Should Show:
✅ **For John Donor:**
- Total Donations: 2 (to facilities 1 and 2)
- Total Units: 900 (450 + 450)
- Donation Trends: 2 months with data
- Blood Type Distribution: O+ (100% of his donations)
- Status: Completed (2), Pending (0), Cancelled (0)

## Verification Checklist

After setting up test data, verify:

- [ ] Blood Bank Analytics page loads without errors
- [ ] Hospital Analytics page loads without errors
- [ ] Donor Analytics page loads without errors
- [ ] All charts render correctly with Chart.js
- [ ] Key metrics display correct numbers
- [ ] Tables show appropriate data
- [ ] Error messages display (if any)
- [ ] Color schemes match portals
- [ ] Responsive design works on mobile

## Data Validation Queries

Run these to verify your test data:

```sql
-- Check donations
SELECT facility_id, COUNT(*) as count, SUM(quantity) as total
FROM donations WHERE status = 'completed'
GROUP BY facility_id;

-- Check inventory
SELECT facility_id, SUM(quantity) as total, COUNT(*) as types
FROM inventory WHERE status = 'available'
GROUP BY facility_id;

-- Check blood types
SELECT blood_type, COUNT(*) as count
FROM users WHERE user_type = 'donor'
GROUP BY blood_type;

-- Check requests
SELECT urgency, status, COUNT(*) as count
FROM blood_requests
GROUP BY urgency, status;
```

## Real Data Integration

For production use:
1. Populate tables through application forms (not manual SQL)
2. Ensure all required fields are filled
3. Verify facility assignments for users
4. Check data consistency and accuracy
5. Monitor database performance with large datasets

## Notes

- Analytics are READ-ONLY (no data modifications)
- All queries filter by facility_id or user_id
- Error messages help identify data issues
- Charts use Chart.js library (must be available)
- Responsive design tested on common screen sizes

---
**Last Updated:** November 16, 2025
