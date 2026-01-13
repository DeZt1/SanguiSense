# Unified Location System - Quick Reference Card

## 🎯 One-Page Cheat Sheet

### Include in Your Files
```php
<?php
// Auto-included via functions.php OR:
include '../includes/locations.php';
include '../includes/dropdown_components.php';
?>
```

### Render Dropdowns
```php
<!-- Municipality (City) -->
<?php renderMunicipalityDropdown($selected, 'city', 'city', false); ?>

<!-- Hospital -->
<?php renderHospitalDropdown($selected, 'hospital', 'hospital', $municipalityFilter, false); ?>

<!-- Blood Bank -->
<?php renderBloodBankDropdown($selected, 'blood_bank', 'blood_bank', $municipalityFilter, false); ?>
```

### Validate Input
```php
if (!isValidMunicipality($city)) { $errors[] = "Invalid city"; }
if (!isValidHospital($hospital)) { $errors[] = "Invalid hospital"; }
if (!isValidBloodBank($bank)) { $errors[] = "Invalid blood bank"; }
```

### Get Data
```php
$municipalities = getMunicipalities();  // Array of 33 cities
$hospitals = getHospitals();            // Array of 8 hospitals
$bloodBanks = getBloodBanks();          // Array of 1 blood bank

// Filtered by municipality
$hospitalList = getHospitalsByMunicipality('Cabanatuan');
$bankList = getBloodBanksByMunicipality('Cabanatuan');
```

### Lookup
```php
$municipality = getHospitalMunicipality('Premiere Medical Center');
$municipality = getBloodBankMunicipality('Philippine Red Cross...');
```

---

## 📱 JavaScript Quick Reference

### Initialize
```javascript
<script src="/sanguisense/assets/location-manager.js"></script>
<script>
    const manager = new LocationManager();
</script>
```

### Link Dropdowns (Auto-Update)
```javascript
manager.linkDropdowns('municipality', 'hospital', 'hospital');
// When user changes municipality, hospital dropdown updates automatically
```

### Manual Data Fetch
```javascript
const municipalities = await manager.getMunicipalities();
const hospitals = await manager.getHospitals();
const bloodBanks = await manager.getBloodBanks();

const hospitalsInCity = await manager.getHospitalsByMunicipality('Cabanatuan');
```

### Validate (Client-Side)
```javascript
if (await isValidMunicipality('Cabanatuan')) { ... }
if (await isValidHospital('Premiere Medical Center')) { ... }
if (await isValidBloodBank('Philippine Red Cross...')) { ... }
```

---

## 🗂️ File Locations

```
Core Files:
├── includes/locations.php              ← Data & validation
├── includes/dropdown_components.php    ← PHP rendering functions
├── assets/location-manager.js          ← JavaScript utilities
└── api/get_*.php                       ← REST endpoints (5 files)

Documentation:
├── QUICK_SETUP_GUIDE.md
├── UNIFIED_LOCATIONS_CONFIG.md
├── IMPLEMENTATION_EXAMPLES.md
├── IMPLEMENTATION_SUMMARY.md
├── ARCHITECTURE_OVERVIEW.md
├── IMPLEMENTATION_CHECKLIST.md
└── (this file)

Optional Database:
└── database/LOCATIONS_MIGRATION.sql
```

---

## 📊 Data Summary

| Item | Count | Example |
|------|-------|---------|
| Municipalities | 33 | Cabanatuan, Gapan, Palayan |
| Hospitals | 8 | Premiere Medical Center |
| Blood Banks | 1 | Philippine Red Cross |

---

## ✅ Common Implementations

### Simple Form (Static)
```html
<form>
    <label>City:</label>
    <?php renderMunicipalityDropdown(); ?>
    <button>Submit</button>
</form>
```

### With Hospital Selection
```html
<form>
    <label>City:</label>
    <?php renderMunicipalityDropdown($userCity); ?>
    
    <label>Hospital:</label>
    <?php renderHospitalDropdown('', 'hospital', 'hospital', $userCity); ?>
    
    <button>Submit</button>
</form>
```

### Dynamic Dropdown (Auto-Update)
```html
<script src="/sanguisense/assets/location-manager.js"></script>

<form>
    <label>City:</label>
    <select id="city"><!-- options from PHP --></select>
    
    <label>Hospital:</label>
    <select id="hospital"><!-- auto-populated --></select>
    
    <button>Submit</button>
</form>

<script>
    const m = new LocationManager();
    m.linkDropdowns('city', 'hospital', 'hospital');
</script>
```

---

## 🔐 Security Checklist

- [x] All output escaped with `htmlspecialchars()`
- [x] Server-side validation is MANDATORY
- [x] Client-side validation is OPTIONAL (UX only)
- [x] API validates all input before processing
- [x] Never trust data from URL query parameters
- [x] Always escape output to HTML

**Rule:** Always validate on server side, even if client validation exists!

---

## 🐛 Quick Debugging

### Dropdown shows empty
```php
<?php var_dump(getMunicipalities()); ?>
<!-- Should show array with 33 items -->
```

### Validation always fails
```php
<?php 
echo "Value: '" . $_POST['city'] . "'\n";
echo "Valid: " . (isValidMunicipality($_POST['city']) ? 'YES' : 'NO') . "\n";
?>
```

### API returns error
```bash
curl "http://localhost/sanguisense/api/get_municipalities.php"
# Should return {"success":true,"municipalities":[...],"count":33}
```

### JavaScript not working
```javascript
console.log(typeof LocationManager);  // Should be "function"
console.log(window.locationManager);  // Should be object or undefined
```

---

## 🔄 How to Update Data

### Add Municipality
Edit `includes/locations.php`:
```php
// In NUEVA_ECIJA_MUNICIPALITIES array, add:
'New City Name',
```
**Changes apply immediately!**

### Add Hospital
Edit `includes/locations.php`:
```php
// In HOSPITALS array, add:
'Hospital Name' => 'Municipality Name',
```

### Add Blood Bank
Edit `includes/locations.php`:
```php
// In BLOOD_BANKS array, add:
'Blood Bank Name' => 'Municipality Name',
```

---

## 📋 Function Signatures

### PHP
```php
// Data Access
getMunicipalities(): array
getHospitals(): array
getBloodBanks(): array
getHospitalsByMunicipality(string): array
getBloodBanksByMunicipality(string): array
getFacilitiesByMunicipality(string): array

// Validation
isValidMunicipality(string): bool
isValidHospital(string): bool
isValidBloodBank(string): bool

// Lookup
getHospitalMunicipality(string): string|null
getBloodBankMunicipality(string): string|null

// Rendering
renderMunicipalityDropdown(string, string, string, bool): void
renderHospitalDropdown(string, string, string, string, bool): void
renderBloodBankDropdown(string, string, string, string, bool): void
getMunicipalityDropdownHtml(...): string
getHospitalDropdownHtml(...): string
getBloodBankDropdownHtml(...): string
```

### JavaScript (LocationManager Class)
```javascript
constructor(config: object)
getMunicipalities(): Promise<array>
getHospitals(): Promise<array>
getBloodBanks(): Promise<array>
getHospitalsByMunicipality(name: string): Promise<array>
getBloodBanksByMunicipality(name: string): Promise<array>
linkDropdowns(muniId: string, facilityId: string, type: string): Promise<void>
populateDropdown(id: string, options: array, selected: string): void
clearCache(): void
```

---

## 🎓 Example Usage

### Example 1: Donor Profile
```php
<?php
include '../includes/locations.php';
$city = trim($_POST['city'] ?? $user['city'] ?? '');

// Validation
if (!empty($city) && !isValidMunicipality($city)) {
    $error = "Invalid city selected";
}

// In form:
renderMunicipalityDropdown($city, 'city', 'city');
```

### Example 2: Hospital Facility
```php
<?php
include '../includes/locations.php';
$hospital = trim($_POST['hospital'] ?? '');

// Validation
if (empty($hospital)) {
    $error = "Hospital is required";
} elseif (!isValidHospital($hospital)) {
    $error = "Invalid hospital selected";
}

// In form:
renderHospitalDropdown($hospital, 'hospital', 'hospital', '', true);
```

### Example 3: Dynamic Update
```javascript
<script src="/sanguisense/assets/location-manager.js"></script>
<script>
    const mgr = new LocationManager();
    
    // When city select changes, update hospital select
    mgr.linkDropdowns('city', 'hospital', 'hospital');
</script>
```

---

## 🆘 Getting Help

### Level 1: Documentation
- `QUICK_SETUP_GUIDE.md` - Start here
- Check function comments in source files

### Level 2: Examples
- `IMPLEMENTATION_EXAMPLES.md` - 8 detailed examples
- Review this reference card

### Level 3: Architecture
- `ARCHITECTURE_OVERVIEW.md` - System design
- `UNIFIED_LOCATIONS_CONFIG.md` - Complete reference

### Level 4: Troubleshooting
- `IMPLEMENTATION_CHECKLIST.md` - Troubleshooting section
- Check error logs: `/var/log/php_errors.log`
- Browser console (F12) for JavaScript errors

---

## ⚡ Performance Tips

1. **Enable Caching:** JavaScript automatically caches API responses
2. **Validate Locally:** Do client-side validation for instant feedback
3. **Lazy Load:** Only render dropdowns that are visible
4. **Minimize API Calls:** Don't call API on every keystroke

---

## 🚀 Quick Start (2 minutes)

```php
<?php
// 1. Include
include '../includes/locations.php';

// 2. Use in form
if ($_POST) {
    $city = $_POST['city'] ?? '';
    if (!isValidMunicipality($city)) {
        echo "Invalid city";
    } else {
        // Save to database...
    }
}
?>

<!-- 3. Render dropdown -->
<form method="POST">
    <?php renderMunicipalityDropdown($city ?? ''); ?>
    <button>Submit</button>
</form>
```

**Done!** ✅

---

## 📞 API Endpoints

| Endpoint | Method | Parameters | Returns |
|----------|--------|-----------|---------|
| `/api/get_municipalities.php` | GET | - | All municipalities |
| `/api/get_all_hospitals.php` | GET | - | All hospitals |
| `/api/get_all_blood_banks.php` | GET | - | All blood banks |
| `/api/get_hospitals_by_municipality.php` | GET | `municipality` | Hospitals in city |
| `/api/get_blood_banks_by_municipality.php` | GET | `municipality` | Blood banks in city |

**Format:** All return JSON with `success` field

---

## 🎯 Key Points to Remember

1. **Always validate on server side**
2. **Use functions, not hardcoded lists**
3. **Escape all output with htmlspecialchars()**
4. **API endpoints validate input**
5. **JavaScript is for UX, not security**
6. **Data updates happen in one place (locations.php)**
7. **Mobile-friendly (responsive dropdowns)**
8. **No database queries needed**

---

**Last Updated:** November 14, 2025  
**Version:** 1.0  
**Status:** Production Ready
