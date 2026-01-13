# Unified Municipalities & Facilities Implementation - Quick Setup Guide

## 📋 What Was Created

A complete centralized location and facility management system for the SanguiSense application with the following components:

### Core Files Created
1. **`includes/locations.php`** - Central data storage and validation functions
2. **`includes/dropdown_components.php`** - Reusable PHP dropdown rendering functions
3. **`assets/location-manager.js`** - JavaScript utility class for client-side operations
4. **`api/get_municipalities.php`** - REST API endpoint for municipalities
5. **`api/get_hospitals_by_municipality.php`** - REST API for hospitals by municipality
6. **`api/get_blood_banks_by_municipality.php`** - REST API for blood banks by municipality
7. **`api/get_all_hospitals.php`** - REST API for all hospitals
8. **`api/get_all_blood_banks.php`** - REST API for all blood banks

### Documentation Files
- **`UNIFIED_LOCATIONS_CONFIG.md`** - Complete reference documentation
- **`IMPLEMENTATION_EXAMPLES.md`** - Practical code examples
- **`QUICK_SETUP_GUIDE.md`** - This file

## 🚀 Quick Start

### Step 1: Include Required Files in Your PHP Files

```php
<?php
include '../includes/locations.php';
include '../includes/dropdown_components.php';
?>
```

**Note:** If your file already includes `../includes/functions.php`, the files are already included automatically since `functions.php` now includes them.

### Step 2: Use Dropdowns in HTML Forms

#### Municipality Dropdown
```html
<div class="form-group">
    <label for="city">City / Municipality</label>
    <?php renderMunicipalityDropdown($user['city'] ?? '', 'city', 'city'); ?>
</div>
```

#### Hospital Dropdown
```html
<div class="form-group">
    <label for="hospital">Hospital</label>
    <?php renderHospitalDropdown($user['hospital'] ?? '', 'hospital', 'hospital', $user['city'] ?? ''); ?>
</div>
```

#### Blood Bank Dropdown
```html
<div class="form-group">
    <label for="blood_bank">Blood Bank</label>
    <?php renderBloodBankDropdown($user['blood_bank'] ?? '', 'blood_bank', 'blood_bank', $user['city'] ?? ''); ?>
</div>
```

### Step 3: Validate on Server Side

```php
<?php
// Validate municipality
if (!empty($city) && !isValidMunicipality($city)) {
    $errors[] = "Invalid municipality selected";
}

// Validate hospital
if (!empty($hospital) && !isValidHospital($hospital)) {
    $errors[] = "Invalid hospital selected";
}

// Validate blood bank
if (!empty($bloodBank) && !isValidBloodBank($bloodBank)) {
    $errors[] = "Invalid blood bank selected";
}
?>
```

## 📱 Using Dynamic Dropdowns (JavaScript)

Include the location manager in your page:

```html
<!-- Include the location manager library -->
<script src="/sanguisense/assets/location-manager.js"></script>

<script>
// Initialize the location manager
const manager = new LocationManager();

// Setup linked dropdowns - when municipality changes, update hospitals
manager.linkDropdowns('municipality', 'hospital', 'hospital');
</script>
```

**HTML:**
```html
<select id="municipality" name="municipality">
    <option value="">-- Select Municipality --</option>
    <!-- Options will be populated by JavaScript -->
</select>

<select id="hospital" name="hospital">
    <option value="">-- Select Hospital --</option>
    <!-- Options will be auto-populated based on municipality selection -->
</select>
```

## 🗂️ Data Structure

### 33 Municipalities
Aliaga, Bongabon, Cabiao, Cabanatuan, Carranglan, Cuyapo, Gapan, Gabaldon, General Mamerto Natividad, General Tinio, Guimba, Jaen, Laur, Licab, Llanera, Lupao, Muñoz, Nampicuan, Palayan, Pantabangan, Peñaranda, Quezon, Rizal, San Antonio, San Isidro, San Jose, San Jose City, San Leonardo, Santa Rosa, Santo Domingo, Talavera, Talugtug, Zaragoza

### 8 Hospitals
| Hospital | Municipality |
|---|---|
| Premiere Medical Center | Cabanatuan |
| GoodSam Medical Center - Cabanatuan | Cabanatuan |
| Nueva Ecija Doctors Hospital | Cabanatuan |
| GoodSam Medical Center - Gapan | Gapan |
| Palayan City Emergency Hospital | Palayan |
| San Jose City General Hospital | San Jose City |
| San Antonio District Hospital | San Antonio |
| Guimba District Hospital | Guimba |

### 1 Blood Bank
| Blood Bank | Municipality |
|---|---|
| Philippine Red Cross-Nueva Ecija Blood Services | Cabanatuan |

## ✅ Available Functions

### PHP Functions (from `locations.php`)

**Data Access:**
- `getMunicipalities()` - Get array of all municipalities
- `getHospitals()` - Get array of all hospitals
- `getBloodBanks()` - Get array of all blood banks
- `getHospitalsByMunicipality($municipality)` - Get hospitals in a municipality
- `getBloodBanksByMunicipality($municipality)` - Get blood banks in a municipality

**Validation:**
- `isValidMunicipality($municipality)` - Check if municipality is valid
- `isValidHospital($hospitalName)` - Check if hospital is valid
- `isValidBloodBank($bankName)` - Check if blood bank is valid

**Lookup:**
- `getHospitalMunicipality($hospitalName)` - Get municipality for a hospital
- `getBloodBankMunicipality($bankName)` - Get municipality for a blood bank

### PHP Functions (from `dropdown_components.php`)

**Render Functions (output HTML):**
- `renderMunicipalityDropdown($selected, $name, $id, $required)`
- `renderHospitalDropdown($selected, $name, $id, $filter, $required)`
- `renderBloodBankDropdown($selected, $name, $id, $filter, $required)`

**String Return Functions:**
- `getMunicipalityDropdownHtml(...)`
- `getHospitalDropdownHtml(...)`
- `getBloodBankDropdownHtml(...)`

### JavaScript Functions (from `location-manager.js`)

**Class Methods:**
- `getMunicipalities()` - Fetch municipalities via API
- `getHospitals()` - Fetch all hospitals via API
- `getBloodBanks()` - Fetch all blood banks via API
- `getHospitalsByMunicipality(municipality)` - Get filtered hospitals
- `getBloodBanksByMunicipality(municipality)` - Get filtered blood banks
- `linkDropdowns(muniId, facilityId, type)` - Link two dropdowns for dynamic updates

**Global Helper Functions:**
- `initializeLocationManager(config)` - Initialize the manager
- `setupLinkedDropdowns(muniId, facilityId, type)` - Setup linked dropdowns
- `isValidMunicipality(name)` - Validate municipality via API
- `isValidHospital(name)` - Validate hospital via API
- `isValidBloodBank(name)` - Validate blood bank via API

## 🔄 Migration from Old Code

### Before (Hard-coded lists)
```php
$cities = [
    'Cabanatuan','Gapan','Muñoz','Palayan',...
];
foreach ($cities as $c) {
    echo "<option value=\"$c\">$c</option>";
}
```

### After (Centralized)
```php
<?php renderMunicipalityDropdown($selected); ?>
```

## 📡 API Endpoints

All API endpoints return JSON and are located in `/sanguisense/api/`:

### GET `/municipalities.php`
Returns all municipalities
```json
{
    "success": true,
    "municipalities": ["Aliaga", "Bongabon", ...],
    "count": 33
}
```

### GET `/get_hospitals_by_municipality.php?municipality=Cabanatuan`
Returns hospitals in a municipality
```json
{
    "success": true,
    "municipality": "Cabanatuan",
    "hospitals": [
        {"name": "Premiere Medical Center", "municipality": "Cabanatuan"}
    ]
}
```

### GET `/get_blood_banks_by_municipality.php?municipality=Cabanatuan`
Returns blood banks in a municipality

### GET `/get_all_hospitals.php`
Returns all hospitals

### GET `/get_all_blood_banks.php`
Returns all blood banks

## 🔐 Server-Side Validation

Always validate on the server side! Never trust client-side validation alone.

```php
<?php
// Example complete validation
$city = trim($_POST['city'] ?? '');
$hospital = trim($_POST['hospital'] ?? '');

$errors = [];

// City validation
if (!empty($city) && !isValidMunicipality($city)) {
    $errors[] = "Invalid municipality selected";
}

// Hospital validation
if (!empty($hospital)) {
    if (!isValidHospital($hospital)) {
        $errors[] = "Invalid hospital selected";
    } else {
        // Additional check: hospital must be in selected city
        $hospitalCity = getHospitalMunicipality($hospital);
        if ($hospitalCity !== $city) {
            $errors[] = "Selected hospital is not in the selected municipality";
        }
    }
}

if (!empty($errors)) {
    // Handle errors
    $errorMessage = implode("; ", $errors);
}
?>
```

## 🎯 Implementation Checklist

- [ ] Include `locations.php` and `dropdown_components.php` in all relevant forms
- [ ] Replace hard-coded city lists with `renderMunicipalityDropdown()`
- [ ] Replace hard-coded facility lists with `renderHospitalDropdown()` or `renderBloodBankDropdown()`
- [ ] Update server-side validation to use validation functions
- [ ] Test forms with various municipalities and facilities
- [ ] If using dynamic dropdowns, include `location-manager.js` and link dropdowns
- [ ] Verify API endpoints are accessible
- [ ] Test with both valid and invalid inputs

## 🐛 Troubleshooting

### Dropdowns not showing options
- Check that `locations.php` is included
- Verify file paths are correct relative to your script
- Check browser console for JavaScript errors

### API returning 404
- Verify API files exist in `/sanguisense/api/`
- Check that paths in JavaScript match your base URL
- Ensure `locations.php` is included in API files

### Options not filtering correctly
- Verify municipality spelling matches exactly
- Check that the municipality parameter is being passed correctly
- Clear browser cache if using JavaScript

### Validation always failing
- Ensure you're using the exact names from the constants
- Check spelling and case sensitivity
- Verify functions are called after including `locations.php`

## 📞 Support

For detailed information:
- See `UNIFIED_LOCATIONS_CONFIG.md` for comprehensive documentation
- See `IMPLEMENTATION_EXAMPLES.md` for code examples
- Check function comments in source files

## 🔄 Updating Data

To add new municipalities, hospitals, or blood banks:

1. Edit `includes/locations.php`
2. Add to the appropriate constant array
3. Changes apply immediately to all forms
4. No database changes needed (unless storing facility name in DB)

Example - Adding a new hospital:
```php
// In HOSPITALS constant
'New Hospital Name' => 'Municipality Name',
```

---

**Last Updated:** November 14, 2025
