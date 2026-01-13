# Centralized Location & Facility Configuration

## Overview

This document explains the unified municipality and facility configuration system implemented in SanguiSense. This system ensures that all portals (Donor, Patient, Hospital, Blood Bank) use the same standardized lists for municipalities and facilities.

## Files Included

### 1. `includes/locations.php`
Contains all standardized data and validation functions:
- **NUEVA_ECIJA_MUNICIPALITIES** - Constant array of all 33 municipalities
- **HOSPITALS** - Constant array of hospitals mapped to municipalities
- **BLOOD_BANKS** - Constant array of blood banks mapped to municipalities
- Helper functions for validation and filtering

### 2. `includes/dropdown_components.php`
Contains reusable functions to render dropdown components:
- `renderMunicipalityDropdown()` - Outputs municipality dropdown HTML
- `renderHospitalDropdown()` - Outputs hospital dropdown HTML
- `renderBloodBankDropdown()` - Outputs blood bank dropdown HTML
- HTML string return versions (without `echo`)

## Municipalities

Total: 33 municipalities

```
Aliaga, Bongabon, Cabiao, Cabanatuan, Carranglan, Cuyapo, Gapan, Gabaldon, 
General Mamerto Natividad, General Tinio, Guimba, Jaen, Laur, Licab, Llanera, 
Lupao, Muñoz, Nampicuan, Palayan, Pantabangan, Peñaranda, Quezon, Rizal, 
San Antonio, San Isidro, San Jose, San Jose City, San Leonardo, Santa Rosa, 
Santo Domingo, Talavera, Talugtug, Zaragoza
```

## Hospitals by Municipality

| Municipality | Hospital | Count |
|---|---|---|
| **Cabanatuan** | Premiere Medical Center | 3 |
| | GoodSam Medical Center - Cabanatuan | |
| | Nueva Ecija Doctors Hospital | |
| **Gapan** | GoodSam Medical Center - Gapan | 1 |
| **Palayan** | Palayan City Emergency Hospital | 1 |
| **San Jose City** | San Jose City General Hospital | 1 |
| **San Antonio** | San Antonio District Hospital | 1 |
| **Guimba** | Guimba District Hospital | 1 |

**Total: 8 Hospitals**

## Blood Banks by Municipality

| Municipality | Blood Bank | Count |
|---|---|---|
| **Cabanatuan** | Philippine Red Cross-Nueva Ecija Blood Services | 1 |

**Total: 1 Blood Bank**

## Implementation Guide

### Step 1: Include the files in your PHP script

```php
<?php
include '../includes/locations.php';
include '../includes/dropdown_components.php';
?>
```

### Step 2: Use in Forms

#### Option A: Using render functions (outputs HTML directly)

```html
<div class="form-group">
    <label for="city">City / Municipality</label>
    <?php renderMunicipalityDropdown($user['city'] ?? '', 'city', 'city'); ?>
</div>
```

#### Option B: Using HTML string return functions

```html
<div class="form-group">
    <label for="city">City / Municipality</label>
    <?php echo getMunicipalityDropdownHtml($user['city'] ?? '', 'city', 'city'); ?>
</div>
```

#### Option C: Hospital dropdown with municipality filter

```html
<div class="form-group">
    <label for="hospital">Hospital</label>
    <?php 
    $selectedMunicipality = $user['city'] ?? '';
    renderHospitalDropdown($user['hospital'] ?? '', 'hospital', 'hospital', $selectedMunicipality);
    ?>
</div>
```

### Step 3: Server-side Validation

```php
<?php
// Validate municipality
if (!empty($city) && !isValidMunicipality($city)) {
    $validation_errors[] = "Please select a valid city/municipality in Nueva Ecija.";
}

// Validate hospital
if (!empty($hospital) && !isValidHospital($hospital)) {
    $validation_errors[] = "Please select a valid hospital.";
}

// Validate blood bank
if (!empty($bloodBank) && !isValidBloodBank($bloodBank)) {
    $validation_errors[] = "Please select a valid blood bank.";
}
?>
```

## Available Functions

### From `locations.php`

#### Data Access Functions
- `getMunicipalities()` - Returns array of all municipalities
- `getHospitals()` - Returns array of all hospitals
- `getBloodBanks()` - Returns array of all blood banks
- `getHospitalsByMunicipality($municipality)` - Returns hospitals in a municipality
- `getBloodBanksByMunicipality($municipality)` - Returns blood banks in a municipality
- `getFacilitiesByMunicipality($municipality)` - Returns both hospitals and blood banks in a municipality

#### Validation Functions
- `isValidMunicipality($municipality)` - Check if municipality is valid
- `isValidHospital($hospitalName)` - Check if hospital is valid
- `isValidBloodBank($bankName)` - Check if blood bank is valid

#### Lookup Functions
- `getHospitalMunicipality($hospitalName)` - Get municipality for a hospital
- `getBloodBankMunicipality($bankName)` - Get municipality for a blood bank

### From `dropdown_components.php`

#### Rendering Functions (echo output)
- `renderMunicipalityDropdown($selected, $name, $id, $required)`
- `renderHospitalDropdown($selected, $name, $id, $municipalityFilter, $required)`
- `renderBloodBankDropdown($selected, $name, $id, $municipalityFilter, $required)`

#### String Return Functions
- `getMunicipalityDropdownHtml($selected, $name, $id, $required)`
- `getHospitalDropdownHtml($selected, $name, $id, $municipalityFilter, $required)`
- `getBloodBankDropdownHtml($selected, $name, $id, $municipalityFilter, $required)`

## Usage Examples

### Example 1: Simple Municipality Dropdown

```php
<?php
include '../includes/locations.php';
include '../includes/dropdown_components.php';

$userCity = 'Cabanatuan';
?>

<label>Select City:</label>
<?php renderMunicipalityDropdown($userCity); ?>
```

**Output:**
```html
<select id="city" name="city">
    <option value="">-- Select City/Municipality --</option>
    <option value="Aliaga">Aliaga</option>
    ...
    <option value="Cabanatuan" selected>Cabanatuan</option>
    ...
</select>
```

### Example 2: Hospital Dropdown with Municipality Filter

```php
<?php
include '../includes/locations.php';
include '../includes/dropdown_components.php';

$userCity = 'Cabanatuan';
$userHospital = 'Premiere Medical Center';
?>

<label>Select Hospital:</label>
<?php renderHospitalDropdown($userHospital, 'hospital', 'hospital', $userCity); ?>
```

**Output:**
```html
<select id="hospital" name="hospital">
    <option value="">-- Select Hospital --</option>
    <option value="Premiere Medical Center" selected>Premiere Medical Center (Cabanatuan)</option>
    <option value="GoodSam Medical Center - Cabanatuan">GoodSam Medical Center - Cabanatuan (Cabanatuan)</option>
    <option value="Nueva Ecija Doctors Hospital">Nueva Ecija Doctors Hospital (Cabanatuan)</option>
</select>
```

### Example 3: Getting Hospitals by Municipality

```php
<?php
include '../includes/locations.php';

$hospitals = getHospitalsByMunicipality('Cabanatuan');
// Returns: ['Premiere Medical Center', 'GoodSam Medical Center - Cabanatuan', 'Nueva Ecija Doctors Hospital']

foreach ($hospitals as $hospital) {
    echo $hospital . "\n";
}
?>
```

### Example 4: Validation

```php
<?php
include '../includes/locations.php';

$userCity = $_POST['city'] ?? '';
$userHospital = $_POST['hospital'] ?? '';

$errors = [];

if (!empty($userCity) && !isValidMunicipality($userCity)) {
    $errors[] = "Invalid municipality selected";
}

if (!empty($userHospital) && !isValidHospital($userHospital)) {
    $errors[] = "Invalid hospital selected";
}

if (!empty($errors)) {
    echo "Validation errors: " . implode(", ", $errors);
}
?>
```

## How to Update

### Adding a New Municipality

1. Open `includes/locations.php`
2. Add the municipality name to the `NUEVA_ECIJA_MUNICIPALITIES` array in alphabetical order
3. Save the file

### Adding a New Hospital

1. Open `includes/locations.php`
2. Add an entry to the `HOSPITALS` array:
   ```php
   'Hospital Name' => 'Municipality Name',
   ```
3. Save the file

### Adding a New Blood Bank

1. Open `includes/locations.php`
2. Add an entry to the `BLOOD_BANKS` array:
   ```php
   'Blood Bank Name' => 'Municipality Name',
   ```
3. Save the file

## Migration Guide for Existing Code

If you have hardcoded city lists in your forms, follow these steps to migrate:

### Before (Old Code)
```php
<select id="city" name="city">
    <option value="">-- Select City --</option>
    <?php
    $cities = ['Cabanatuan','Gapan','Muñoz','Palayan']; // hardcoded
    foreach ($cities as $c) {
        echo "<option value=\"$c\">$c</option>";
    }
    ?>
</select>
```

### After (New Code)
```php
<?php
include '../includes/locations.php';
include '../includes/dropdown_components.php';
?>

<select id="city" name="city">
    <?php renderMunicipalityDropdown(); ?>
</select>
```

## Benefits

✅ **Consistency** - All portals use the same data  
✅ **Maintainability** - Update once, changes reflect everywhere  
✅ **Validation** - Built-in validation functions prevent invalid entries  
✅ **Flexibility** - Filter facilities by municipality or get all  
✅ **Reusability** - Use components across multiple pages  
✅ **Type-safe** - Defined constants prevent typos  

## Support

For questions or issues regarding the centralized configuration, refer to:
- `includes/locations.php` - For data and validation functions
- `includes/dropdown_components.php` - For UI component functions
