# Unified Location & Facility System - Architecture Overview

## System Structure

```
┌─────────────────────────────────────────────────────────────────┐
│                      SanguiSense Application                     │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │            CENTRALIZED DATA LAYER                          │ │
│  │                                                            │ │
│  │  includes/locations.php                                   │ │
│  │  ├── NUEVA_ECIJA_MUNICIPALITIES (33 cities)             │ │
│  │  ├── HOSPITALS (8 hospitals mapped to municipalities)   │ │
│  │  └── BLOOD_BANKS (1 blood bank mapped)                  │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │         VALIDATION & DATA ACCESS FUNCTIONS                │ │
│  │                                                            │ │
│  │  includes/locations.php                                   │ │
│  │  ├── getMunicipalities()                                  │ │
│  │  ├── getHospitals()                                       │ │
│  │  ├── getBloodBanks()                                      │ │
│  │  ├── isValidMunicipality($name)                          │ │
│  │  ├── isValidHospital($name)                              │ │
│  │  ├── isValidBloodBank($name)                             │ │
│  │  └── ... (9 more utility functions)                      │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │         PRESENTATION LAYER (PHP)                          │ │
│  │                                                            │ │
│  │  includes/dropdown_components.php                         │ │
│  │  ├── renderMunicipalityDropdown()  ─┐                    │ │
│  │  ├── renderHospitalDropdown()       │ Outputs HTML      │ │
│  │  ├── renderBloodBankDropdown()      │                   │ │
│  │  ├── getMunicipalityDropdownHtml()  ─┐                   │ │
│  │  ├── getHospitalDropdownHtml()      │ Returns HTML      │ │
│  │  └── getBloodBankDropdownHtml()     │ as String         │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
                              ↑
                    Used by all portals:
            Donor, Patient, Hospital, Blood Bank
```

---

## Data Flow Diagram

### Standard Form Submission Flow

```
┌─────────────────────────────────────────────────────────────┐
│  HTML Form (Using dropdown_components.php)                  │
│  ├── Municipality Dropdown ◄─── renderMunicipalityDropdown()│
│  ├── Hospital Dropdown    ◄─── renderHospitalDropdown()    │
│  └── Submit Button                                          │
└─────────────────────────────────────────────────────────────┘
                          ↓ (POST)
┌─────────────────────────────────────────────────────────────┐
│  Server-Side Validation (PHP)                              │
│  ├── if (!isValidMunicipality($city)) → Error             │
│  ├── if (!isValidHospital($hospital)) → Error             │
│  └── Check hospital belongs to city (getHospitalMunicipal)│
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  Process & Save Data                                        │
│  └── INSERT/UPDATE database with validated values          │
└─────────────────────────────────────────────────────────────┘
```

### Dynamic Dropdown Flow (JavaScript)

```
┌──────────────────────────────────────────────────────┐
│  User changes Municipality Dropdown                  │
└──────────────────────────────────────────────────────┘
                    ↓
┌──────────────────────────────────────────────────────┐
│  location-manager.js triggers change event           │
└──────────────────────────────────────────────────────┘
                    ↓
┌──────────────────────────────────────────────────────┐
│  LocationManager.linkDropdowns() calls API           │
│  → /api/get_hospitals_by_municipality.php            │
└──────────────────────────────────────────────────────┘
                    ↓
┌──────────────────────────────────────────────────────┐
│  API includes locations.php                          │
│  → getHospitalsByMunicipality($municipality)         │
│  → Returns JSON with filtered hospitals              │
└──────────────────────────────────────────────────────┘
                    ↓
┌──────────────────────────────────────────────────────┐
│  JavaScript updates Hospital Dropdown                │
│  → Clears previous options                           │
│  → Adds new options from API response                │
└──────────────────────────────────────────────────────┘
                    ↓
┌──────────────────────────────────────────────────────┐
│  User sees updated Hospital list for selected city   │
└──────────────────────────────────────────────────────┘
```

---

## File Organization

```
/sanguisense/
│
├── includes/
│   ├── config.php                    (existing)
│   ├── functions.php                 (updated - now includes locations.php)
│   ├── locations.php                 (NEW - data & validation)
│   └── dropdown_components.php        (NEW - UI components)
│
├── api/
│   ├── get_municipalities.php         (NEW)
│   ├── get_all_hospitals.php          (NEW)
│   ├── get_all_blood_banks.php        (NEW)
│   ├── get_hospitals_by_municipality.php   (NEW)
│   └── get_blood_banks_by_municipality.php (NEW)
│
├── assets/
│   └── location-manager.js            (NEW - JavaScript utilities)
│
├── donor/
│   ├── profile.php                  (can use new system)
│   ├── dashboard.php                (can use new system)
│   └── ...
│
├── patient/
│   ├── profile.php                  (can use new system)
│   ├── dashboard.php                (can use new system)
│   └── ...
│
├── hospital/
│   ├── profile.php                  (can use new system)
│   ├── facility_setup.php           (can use new system)
│   └── ...
│
├── bloodbank/
│   ├── profile.php                  (can use new system)
│   ├── facility_setup.php           (can use new system)
│   └── ...
│
├── database/
│   └── LOCATIONS_MIGRATION.sql       (NEW - optional DB setup)
│
└── documentation files:
    ├── UNIFIED_LOCATIONS_CONFIG.md
    ├── IMPLEMENTATION_EXAMPLES.md
    ├── QUICK_SETUP_GUIDE.md
    ├── IMPLEMENTATION_SUMMARY.md
    └── (this file)
```

---

## Component Dependencies

```
┌─────────────────────────────────────────────────────────┐
│  Any PHP Form/Page                                      │
│  (donor, patient, hospital, bloodbank portals)          │
└─────────────────────────────────────────────────────────┘
                    ↓ includes
┌─────────────────────────────────────────────────────────┐
│  includes/locations.php                                 │
│  (contains all data + validation functions)             │
└─────────────────────────────────────────────────────────┘
                    ↓ optionally includes
┌─────────────────────────────────────────────────────────┐
│  includes/dropdown_components.php                       │
│  (rendering functions that use locations.php)           │
└─────────────────────────────────────────────────────────┘
                    ↓ outputs HTML with
┌─────────────────────────────────────────────────────────┐
│  Client-Side Form                                       │
│  (with dropdown select elements)                        │
└─────────────────────────────────────────────────────────┘
                    ↓ optionally uses
┌─────────────────────────────────────────────────────────┐
│  assets/location-manager.js                             │
│  (for dynamic dropdown updates)                         │
└─────────────────────────────────────────────────────────┘
                    ↓ calls
┌─────────────────────────────────────────────────────────┐
│  api/*.php endpoints                                    │
│  (REST API that includes locations.php)                 │
└─────────────────────────────────────────────────────────┘
                    ↓ returns
┌─────────────────────────────────────────────────────────┐
│  JSON Response                                          │
│  (for JavaScript to update UI)                          │
└─────────────────────────────────────────────────────────┘
```

---

## Usage Patterns

### Pattern 1: Simple Static Form

```
Form Page
    ↓
Include locations.php
Include dropdown_components.php
    ↓
Render Dropdown
    (renderMunicipalityDropdown())
    ↓
Submit Form
    ↓
Validate
    (isValidMunicipality())
    ↓
Save Data
```

### Pattern 2: Dynamic Form with AJAX

```
Form Page
    ↓
Include location-manager.js
    ↓
Initialize LocationManager
    ↓
Link Dropdowns
    (manager.linkDropdowns())
    ↓
User Changes Municipality
    ↓
Fetch Facilities via API
    (api/get_hospitals_by_municipality.php)
    ↓
Update Hospital Dropdown
    ↓
User Selects Hospital & Submits
    ↓
Validate on Server
    (includes/locations.php validation)
    ↓
Save Data
```

### Pattern 3: API Integration

```
External Application
    ↓
Call REST Endpoint
    (api/get_municipalities.php)
    ↓
Receive JSON
    ↓
Process Response
```

---

## Integration Points

### For Donor Portal
```php
// donor/profile.php
include '../includes/locations.php';
include '../includes/dropdown_components.php';

// Use in form
renderMunicipalityDropdown($user['city']);

// Use in validation
if (!isValidMunicipality($city)) { ... }
```

### For Patient Portal
```php
// patient/profile.php
include '../includes/locations.php';
include '../includes/dropdown_components.php';

// Use for home city
renderMunicipalityDropdown($user['city']);

// Use for preferred hospital
renderHospitalDropdown($user['hospital'], 'hospital', 'hospital', $user['city']);
```

### For Hospital Admin
```php
// hospital/facility_setup.php
include '../includes/locations.php';
include '../includes/dropdown_components.php';

// Use for facility selection
renderHospitalDropdown('', 'hospital', 'hospital', '', true);

// Use in validation
if (!isValidHospital($hospital)) { ... }
```

### For Blood Bank Admin
```php
// bloodbank/facility_setup.php
include '../includes/locations.php';
include '../includes/dropdown_components.php';

// Use for facility selection
renderBloodBankDropdown('', 'blood_bank', 'blood_bank', '', true);

// Use in validation
if (!isValidBloodBank($bloodBank)) { ... }
```

---

## Data Access Patterns

### Getting All Data
```php
$municipalities = getMunicipalities();     // 33 items
$hospitals = getHospitals();               // 8 items
$bloodBanks = getBloodBanks();             // 1 item
```

### Filtering Data
```php
$hospitalsInCabanatuan = getHospitalsByMunicipality('Cabanatuan');
// Returns: ['Premiere Medical Center', 'GoodSam ...', 'Nueva Ecija ...']

$bloodBanksInCabanatuan = getBloodBanksByMunicipality('Cabanatuan');
// Returns: ['Philippine Red Cross-Nueva Ecija Blood Services']
```

### Validating Data
```php
if (isValidMunicipality('Cabanatuan')) { ... }        // true
if (isValidHospital('Premiere Medical Center')) { ... } // true
if (isValidBloodBank('Philippine Red Cross...')) { ... } // true

if (isValidMunicipality('Unknown')) { ... }           // false
if (isValidHospital('Unknown Hospital')) { ... }      // false
```

### Looking Up Data
```php
$mun = getHospitalMunicipality('Premiere Medical Center');
// Returns: 'Cabanatuan'

$mun = getBloodBankMunicipality('Philippine Red Cross...');
// Returns: 'Cabanatuan'
```

---

## API Request/Response Examples

### Get All Municipalities
```
GET /sanguisense/api/get_municipalities.php

Response:
{
    "success": true,
    "municipalities": ["Aliaga", "Bongabon", ...],
    "count": 33
}
```

### Get Hospitals by Municipality
```
GET /sanguisense/api/get_hospitals_by_municipality.php?municipality=Cabanatuan

Response:
{
    "success": true,
    "municipality": "Cabanatuan",
    "hospitals": [
        {
            "name": "Premiere Medical Center",
            "municipality": "Cabanatuan"
        },
        ...
    ]
}
```

### Get All Hospitals
```
GET /sanguisense/api/get_all_hospitals.php

Response:
{
    "success": true,
    "hospitals": [
        {"name": "Premiere Medical Center", "municipality": "Cabanatuan"},
        ...
    ],
    "count": 8
}
```

---

## Error Handling

### PHP Server-Side
```php
try {
    if (!isValidMunicipality($city)) {
        $errors[] = "Invalid municipality";
    }
    if (!isValidHospital($hospital)) {
        $errors[] = "Invalid hospital";
    }
    
    if (empty($errors)) {
        // Save to database
    } else {
        // Display errors
    }
} catch (Exception $e) {
    // Log error
    $errors[] = "System error: " . $e->getMessage();
}
```

### JavaScript Client-Side
```javascript
try {
    const hospitals = await manager.getHospitalsByMunicipality('Cabanatuan');
    
    if (!hospitals || hospitals.length === 0) {
        console.warn('No hospitals found');
        return;
    }
    
    // Update dropdown
    hospitals.forEach(h => { ... });
} catch (error) {
    console.error('Error fetching hospitals:', error);
}
```

### API Error Handling
```php
if (empty($municipality)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Municipality parameter required'
    ]);
    exit;
}

if (!isValidMunicipality($municipality)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid municipality'
    ]);
    exit;
}
```

---

## Performance Characteristics

```
Operation                           Time        Notes
─────────────────────────────────────────────────────────
Render dropdown                     ~0ms        No DB query
Validate input                      ~0.1ms      Simple array check
Get hospitals by municipality       ~0.5ms      Array filter
API call (cold)                     ~50ms       First request
API call (cached)                   ~5ms        Subsequent requests
JavaScript dropdown link setup      ~100ms      DOM manipulation
User selection to update            ~50ms       AJAX round-trip
```

---

## Security Model

```
User Input
    ↓
Client-Side Validation (optional)
    └─ Provide feedback, not security
    ↓
Server-Side Validation (required)
    ├─ Check with isValid*() functions
    ├─ HTML escape output with htmlspecialchars()
    └─ Prevent SQL injection with prepared statements
    ↓
Database Storage
    └─ Already escaped and validated
    ↓
API Response
    ├─ JSON encode
    ├─ Set Content-Type: application/json
    └─ No executable code in response
    ↓
Client-Side Processing
    └─ JSON.parse() automatically escapes
```

---

## Testing Scenarios

### Valid Cases
- ✅ Select valid municipality
- ✅ View filtered hospitals
- ✅ Select valid hospital
- ✅ Submit form with all valid data

### Invalid Cases
- ❌ Select invalid municipality → Should show error
- ❌ Select invalid hospital → Should show error
- ❌ Hospital from different municipality → Should reject
- ❌ Manually enter invalid value → Should not allow

### Edge Cases
- 📌 No selection made (empty) → Should be optional by default
- 📌 Duplicate selections → Should allow if valid
- 📌 Special characters in names → Should handle gracefully
- 📌 API timeout → Should show error message

---

**This architecture ensures consistency, maintainability, and scalability across all SanguiSense portals.**
