<?php
/**
 * IMPLEMENTATION EXAMPLES
 * 
 * This file shows practical examples of how to implement the unified 
 * municipality and facility dropdowns in different forms across the SanguiSense application.
 */

// ============================================================================
// EXAMPLE 1: DONOR PROFILE FORM - City Municipality Dropdown
// ============================================================================
/*
Location: donor/profile.php

BEFORE (Old Code):
```php
<div class="form-group">
    <label for="city">City / Municipality</label>
    <select id="city" name="city">
        <option value="">-- Select City --</option>
        <?php
        $cities = [
            'Cabanatuan','Gapan','Muñoz','Palayan','San Jose City',...
        ];
        foreach ($cities as $c) {
            $sel = ($user['city'] ?? '') === $c ? 'selected' : '';
            echo "<option value=\"" . htmlspecialchars($c) . "\" $sel>" . htmlspecialchars($c) . "</option>\n";
        }
        ?>
    </select>
</div>
```

AFTER (New Code):
```php
<div class="form-group">
    <label for="city">City / Municipality</label>
    <?php renderMunicipalityDropdown($user['city'] ?? '', 'city', 'city'); ?>
</div>
```

SERVER-SIDE VALIDATION (in your POST handler):
```php
// Validate city (if provided, must be in Nueva Ecija list)
if (!empty($city) && !isValidMunicipality($city)) {
    $validation_errors[] = "Please select a valid city/municipality in Nueva Ecija.";
}
```
*/


// ============================================================================
// EXAMPLE 2: HOSPITAL FACILITY SETUP - Hospital Selection
// ============================================================================
/*
Location: hospital/facility_setup.php

BEFORE (Old Code):
```html
<form method="POST">
    <select name="hospital" required>
        <option value="">-- Select Hospital --</option>
        <option value="Premiere Medical Center">Premiere Medical Center</option>
        <option value="GoodSam Medical Center">GoodSam Medical Center</option>
        ...
    </select>
</form>
```

AFTER (New Code):
```php
<form method="POST">
    <div class="form-group">
        <label for="hospital">Select Hospital <span class="required">*</span></label>
        <?php 
        $selectedHospital = $_POST['hospital'] ?? '';
        renderHospitalDropdown($selectedHospital, 'hospital', 'hospital', '', true);
        ?>
    </div>
</form>
```

SERVER-SIDE VALIDATION:
```php
$hospital = trim($_POST['hospital'] ?? '');

if (empty($hospital)) {
    $errors[] = "Hospital selection is required.";
} elseif (!isValidHospital($hospital)) {
    $errors[] = "Invalid hospital selected.";
}
```
*/


// ============================================================================
// EXAMPLE 3: BLOOD BANK FACILITY SETUP - Blood Bank Selection
// ============================================================================
/*
Location: bloodbank/facility_setup.php

BEFORE (Old Code):
```html
<form method="POST">
    <select name="blood_bank" required>
        <option value="">-- Select Blood Bank --</option>
        <option value="Philippine Red Cross-Nueva Ecija">Philippine Red Cross-Nueva Ecija</option>
        ...
    </select>
</form>
```

AFTER (New Code):
```php
<form method="POST">
    <div class="form-group">
        <label for="blood_bank">Select Blood Bank <span class="required">*</span></label>
        <?php 
        $selectedBank = $_POST['blood_bank'] ?? '';
        renderBloodBankDropdown($selectedBank, 'blood_bank', 'blood_bank', '', true);
        ?>
    </div>
</form>
```

SERVER-SIDE VALIDATION:
```php
$bloodBank = trim($_POST['blood_bank'] ?? '');

if (empty($bloodBank)) {
    $errors[] = "Blood bank selection is required.";
} elseif (!isValidBloodBank($bloodBank)) {
    $errors[] = "Invalid blood bank selected.";
}
```
*/


// ============================================================================
// EXAMPLE 4: PATIENT PROFILE - City and Health Facility
// ============================================================================
/*
Location: patient/profile.php

HTML:
```php
<div class="form-row">
    <div class="form-group">
        <label for="city">City / Municipality</label>
        <?php renderMunicipalityDropdown($user['city'] ?? '', 'city', 'city'); ?>
    </div>
    
    <div class="form-group">
        <label for="health_facility">Preferred Health Facility</label>
        <?php 
        $selectedCity = $user['city'] ?? '';
        renderHospitalDropdown($user['health_facility'] ?? '', 'health_facility', 'health_facility', $selectedCity);
        ?>
    </div>
</div>
```

PHP VALIDATION:
```php
$city = trim($_POST['city'] ?? '');
$healthFacility = trim($_POST['health_facility'] ?? '');

// Validate city
if (!empty($city) && !isValidMunicipality($city)) {
    $validation_errors[] = "Invalid municipality selected.";
}

// Validate health facility (if provided)
if (!empty($healthFacility) && !isValidHospital($healthFacility)) {
    $validation_errors[] = "Invalid health facility selected.";
}
```
*/


// ============================================================================
// EXAMPLE 5: DYNAMIC MUNICIPALITY-DEPENDENT DROPDOWNS (Using JavaScript)
// ============================================================================
/*
This example shows how to dynamically update facility dropdowns when municipality changes.

HTML:
```html
<div class="form-row">
    <div class="form-group">
        <label for="municipality">City / Municipality</label>
        <select id="municipality" name="municipality">
            <option value="">-- Select --</option>
            <?php
            foreach (getMunicipalities() as $mun) {
                echo '<option value="' . htmlspecialchars($mun) . '">' . htmlspecialchars($mun) . '</option>';
            }
            ?>
        </select>
    </div>
    
    <div class="form-group">
        <label for="hospital">Hospital</label>
        <select id="hospital" name="hospital">
            <option value="">-- Select Hospital --</option>
        </select>
    </div>
</div>

<script>
document.getElementById('municipality').addEventListener('change', function() {
    const municipality = this.value;
    const hospitalSelect = document.getElementById('hospital');
    
    // Clear existing options
    hospitalSelect.innerHTML = '<option value="">-- Select Hospital --</option>';
    
    if (!municipality) return;
    
    // Get hospitals data via AJAX
    fetch('/sanguisense/api/get_hospitals.php?municipality=' + encodeURIComponent(municipality))
        .then(response => response.json())
        .then(data => {
            if (data.hospitals && data.hospitals.length > 0) {
                data.hospitals.forEach(hospital => {
                    const option = document.createElement('option');
                    option.value = hospital.name;
                    option.textContent = hospital.name + ' (' + hospital.municipality + ')';
                    hospitalSelect.appendChild(option);
                });
            }
        });
});
</script>
```

Backend API (api/get_hospitals.php):
```php
<?php
include '../includes/locations.php';

$municipality = $_GET['municipality'] ?? '';

if (!isValidMunicipality($municipality)) {
    echo json_encode(['error' => 'Invalid municipality']);
    exit;
}

$hospitals = getHospitalsByMunicipality($municipality);
$result = [];

foreach ($hospitals as $hospitalName) {
    $result[] = [
        'name' => $hospitalName,
        'municipality' => $municipality
    ];
}

echo json_encode(['hospitals' => $result]);
?>
```
*/


// ============================================================================
// EXAMPLE 6: FILTERING & LISTING - Get All Facilities by Municipality
// ============================================================================
/*
Display all hospitals and blood banks for a selected municipality.

PHP CODE:
```php
<?php
include '../includes/locations.php';

$municipality = 'Cabanatuan';
$facilities = getFacilitiesByMunicipality($municipality);
?>

<h3>Facilities in <?php echo htmlspecialchars($municipality); ?></h3>

<h4>Hospitals</h4>
<ul>
    <?php foreach ($facilities['hospitals'] as $hospital): ?>
        <li><?php echo htmlspecialchars($hospital); ?></li>
    <?php endforeach; ?>
</ul>

<h4>Blood Banks</h4>
<ul>
    <?php foreach ($facilities['blood_banks'] as $bank): ?>
        <li><?php echo htmlspecialchars($bank); ?></li>
    <?php endforeach; ?>
</ul>
```

OUTPUT:
Facilities in Cabanatuan
Hospitals
- Premiere Medical Center
- GoodSam Medical Center - Cabanatuan
- Nueva Ecija Doctors Hospital

Blood Banks
- Philippine Red Cross-Nueva Ecija Blood Services
*/


// ============================================================================
// EXAMPLE 7: QUICK REFERENCE - Common Validation Functions
// ============================================================================
/*
VALIDATION FUNCTIONS AVAILABLE:

1. isValidMunicipality($municipality)
   - Checks if a municipality is in the Nueva Ecija list
   - Usage: if (!isValidMunicipality($_POST['city'])) { error }

2. isValidHospital($hospitalName)
   - Checks if a hospital name exists
   - Usage: if (!isValidHospital($_POST['hospital'])) { error }

3. isValidBloodBank($bankName)
   - Checks if a blood bank name exists
   - Usage: if (!isValidBloodBank($_POST['blood_bank'])) { error }

4. getHospitalMunicipality($hospitalName)
   - Returns the municipality for a hospital
   - Usage: $city = getHospitalMunicipality('Premiere Medical Center');
   - Returns: 'Cabanatuan'

5. getBloodBankMunicipality($bankName)
   - Returns the municipality for a blood bank
   - Usage: $city = getBloodBankMunicipality('Philippine Red Cross-Nueva Ecija Blood Services');
   - Returns: 'Cabanatuan'
*/


// ============================================================================
// EXAMPLE 8: COMPLETE FORM EXAMPLE - Hospital Registration
// ============================================================================
/*
This is a complete example of a form using all the unified components.

FILE: hospital/register.php

<?php
include '../includes/auth.php';
include '../includes/locations.php';
include '../includes/dropdown_components.php';

$errors = [];
$formData = [
    'name' => '',
    'email' => '',
    'city' => '',
    'hospital' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['name'] = trim($_POST['name'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['city'] = trim($_POST['city'] ?? '');
    $formData['hospital'] = trim($_POST['hospital'] ?? '');
    
    // Validate name
    if (empty($formData['name'])) {
        $errors[] = "Name is required";
    }
    
    // Validate email
    if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    // Validate city
    if (empty($formData['city'])) {
        $errors[] = "Please select a city";
    } elseif (!isValidMunicipality($formData['city'])) {
        $errors[] = "Invalid city selected";
    }
    
    // Validate hospital
    if (empty($formData['hospital'])) {
        $errors[] = "Please select a hospital";
    } elseif (!isValidHospital($formData['hospital'])) {
        $errors[] = "Invalid hospital selected";
    } else {
        // Verify hospital belongs to selected city
        $hospitalCity = getHospitalMunicipality($formData['hospital']);
        if ($hospitalCity !== $formData['city']) {
            $errors[] = "Selected hospital does not belong to selected city";
        }
    }
    
    // If no errors, save to database
    if (empty($errors)) {
        // Save hospital admin...
        // header('Location: dashboard.php');
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Hospital Registration</title>
</head>
<body>
    <h1>Hospital Admin Registration</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="name">Full Name *</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($formData['name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="city">City / Municipality *</label>
            <?php renderMunicipalityDropdown($formData['city'], 'city', 'city', true); ?>
        </div>
        
        <div class="form-group">
            <label for="hospital">Hospital *</label>
            <?php renderHospitalDropdown($formData['hospital'], 'hospital', 'hospital', $formData['city'], true); ?>
        </div>
        
        <button type="submit">Register</button>
    </form>
</body>
</html>
*/

?>
