<?php
/**
 * HTML Components for Standardized Dropdowns
 * Provides reusable dropdown components for municipalities and facilities
 */

/**
 * Render municipality dropdown
 * @param string $selectedValue - Currently selected municipality
 * @param string $fieldName - HTML name attribute (default: 'city')
 * @param string $fieldId - HTML id attribute (default: 'city')
 * @param string $required - Make field required (default: false)
 */
function renderMunicipalityDropdown($selectedValue = '', $fieldName = 'city', $fieldId = 'city', $required = false) {
    $municipalities = get_municipalities();
    $requiredAttr = $required ? 'required' : '';
    
    echo '<select id="' . htmlspecialchars($fieldId) . '" name="' . htmlspecialchars($fieldName) . '" ' . $requiredAttr . '>';
    echo '<option value="">-- Select City/Municipality --</option>';
    
    foreach ($municipalities as $municipality) {
        $selected = ($selectedValue === $municipality) ? 'selected' : '';
        echo '<option value="' . htmlspecialchars($municipality) . '" ' . $selected . '>' . htmlspecialchars($municipality) . '</option>';
    }
    
    echo '</select>';
}

/**
 * Render hospital dropdown
 * @param string $selectedValue - Currently selected hospital
 * @param string $fieldName - HTML name attribute (default: 'hospital')
 * @param string $fieldId - HTML id attribute (default: 'hospital')
 * @param string $municipalityFilter - Filter hospitals by municipality (optional)
 * @param string $required - Make field required (default: false)
 */
function renderHospitalDropdown($selectedValue = '', $fieldName = 'hospital', $fieldId = 'hospital', $municipalityFilter = '', $required = false) {
    $hospitals = get_facilities(['type' => 'hospital']);
    $requiredAttr = $required ? 'required' : '';
    
    echo '<select id="' . htmlspecialchars($fieldId) . '" name="' . htmlspecialchars($fieldName) . '" ' . $requiredAttr . '>';
    echo '<option value="">-- Select Hospital --</option>';
    
    foreach ($hospitals as $h) {
        $name = $h['name'];
        $municipality = $h['city'];
        // Filter by municipality if specified
        if (!empty($municipalityFilter) && $municipality !== $municipalityFilter) {
            continue;
        }
        $selected = ($selectedValue === $name) ? 'selected' : '';
        $displayText = $name . ' (' . $municipality . ')';
        echo '<option value="' . htmlspecialchars($name) . '" ' . $selected . '>' . htmlspecialchars($displayText) . '</option>';
    }
    
    echo '</select>';
}

/**
 * Render blood bank dropdown
 * @param string $selectedValue - Currently selected blood bank
 * @param string $fieldName - HTML name attribute (default: 'blood_bank')
 * @param string $fieldId - HTML id attribute (default: 'blood_bank')
 * @param string $municipalityFilter - Filter blood banks by municipality (optional)
 * @param string $required - Make field required (default: false)
 */
function renderBloodBankDropdown($selectedValue = '', $fieldName = 'blood_bank', $fieldId = 'blood_bank', $municipalityFilter = '', $required = false) {
    $bloodBanks = get_facilities(['type' => 'bloodbank']);
    $requiredAttr = $required ? 'required' : '';
    
    echo '<select id="' . htmlspecialchars($fieldId) . '" name="' . htmlspecialchars($fieldName) . '" ' . $requiredAttr . '>';
    echo '<option value="">-- Select Blood Bank --</option>';
    
    foreach ($bloodBanks as $b) {
        $name = $b['name'];
        $municipality = $b['city'];
        if (!empty($municipalityFilter) && $municipality !== $municipalityFilter) {
            continue;
        }
        $selected = ($selectedValue === $name) ? 'selected' : '';
        $displayText = $name . ' (' . $municipality . ')';
        echo '<option value="' . htmlspecialchars($name) . '" ' . $selected . '>' . htmlspecialchars($displayText) . '</option>';
    }
    
    echo '</select>';
}

/**
 * Get HTML for municipality dropdown as string (without echo)
 * @param string $selectedValue - Currently selected municipality
 * @param string $fieldName - HTML name attribute (default: 'city')
 * @param string $fieldId - HTML id attribute (default: 'city')
 * @param string $required - Make field required (default: false)
 * @return string - HTML dropdown string
 */
function getMunicipalityDropdownHtml($selectedValue = '', $fieldName = 'city', $fieldId = 'city', $required = false) {
    $municipalities = get_municipalities();
    $requiredAttr = $required ? 'required' : '';
    
    $html = '<select id="' . htmlspecialchars($fieldId) . '" name="' . htmlspecialchars($fieldName) . '" ' . $requiredAttr . '>';
    $html .= '<option value="">-- Select City/Municipality --</option>';
    
    foreach ($municipalities as $municipality) {
        $selected = ($selectedValue === $municipality) ? 'selected' : '';
        $html .= '<option value="' . htmlspecialchars($municipality) . '" ' . $selected . '>' . htmlspecialchars($municipality) . '</option>';
    }
    
    $html .= '</select>';
    
    return $html;
}

/**
 * Get HTML for hospital dropdown as string (without echo)
 * @param string $selectedValue - Currently selected hospital
 * @param string $fieldName - HTML name attribute (default: 'hospital')
 * @param string $fieldId - HTML id attribute (default: 'hospital')
 * @param string $municipalityFilter - Filter hospitals by municipality (optional)
 * @param string $required - Make field required (default: false)
 * @return string - HTML dropdown string
 */
function getHospitalDropdownHtml($selectedValue = '', $fieldName = 'hospital', $fieldId = 'hospital', $municipalityFilter = '', $required = false) {
    $hospitals = get_facilities(['type' => 'hospital']);
    $requiredAttr = $required ? 'required' : '';
    
    $html = '<select id="' . htmlspecialchars($fieldId) . '" name="' . htmlspecialchars($fieldName) . '" ' . $requiredAttr . '>';
    $html .= '<option value="">-- Select Hospital --</option>';
    
    foreach ($hospitals as $h) {
        $name = $h['name'];
        $municipality = $h['city'];
        if (!empty($municipalityFilter) && $municipality !== $municipalityFilter) {
            continue;
        }
        $selected = ($selectedValue === $name) ? 'selected' : '';
        $displayText = $name . ' (' . $municipality . ')';
        $html .= '<option value="' . htmlspecialchars($name) . '" ' . $selected . '>' . htmlspecialchars($displayText) . '</option>';
    }
    
    $html .= '</select>';
    
    return $html;
}

/**
 * Get HTML for blood bank dropdown as string (without echo)
 * @param string $selectedValue - Currently selected blood bank
 * @param string $fieldName - HTML name attribute (default: 'blood_bank')
 * @param string $fieldId - HTML id attribute (default: 'blood_bank')
 * @param string $municipalityFilter - Filter blood banks by municipality (optional)
 * @param string $required - Make field required (default: false)
 * @return string - HTML dropdown string
 */
function getBloodBankDropdownHtml($selectedValue = '', $fieldName = 'blood_bank', $fieldId = 'blood_bank', $municipalityFilter = '', $required = false) {
    $bloodBanks = get_facilities(['type' => 'bloodbank']);
    $requiredAttr = $required ? 'required' : '';
    
    $html = '<select id="' . htmlspecialchars($fieldId) . '" name="' . htmlspecialchars($fieldName) . '" ' . $requiredAttr . '>';
    $html .= '<option value="">-- Select Blood Bank --</option>';
    
    foreach ($bloodBanks as $b) {
        $name = $b['name'];
        $municipality = $b['city'];
        if (!empty($municipalityFilter) && $municipality !== $municipalityFilter) {
            continue;
        }
        $selected = ($selectedValue === $name) ? 'selected' : '';
        $displayText = $name . ' (' . $municipality . ')';
        $html .= '<option value="' . htmlspecialchars($name) . '" ' . $selected . '>' . htmlspecialchars($displayText) . '</option>';
    }
    
    $html .= '</select>';
    
    return $html;
}
?>
