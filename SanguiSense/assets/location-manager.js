/**
 * SanguiSense Unified Location & Facility Helpers
 * 
 * This file provides JavaScript utilities for managing location and facility
 * dropdowns across all portals with dynamic filtering and AJAX support.
 */

class LocationManager {
    /**
     * Constructor
     * @param {Object} config - Configuration object
     */
    constructor(config = {}) {
        this.config = {
            baseUrl: config.baseUrl || '/sanguisense',
            cacheData: config.cacheData !== false, // Cache API responses
            ...config
        };
        this.cache = {};
    }

    /**
     * Get municipalities from API
     * @returns {Promise<Array>} Array of municipality names
     */
    async getMunicipalities() {
        if (this.cache.municipalities) {
            return this.cache.municipalities;
        }

        try {
            const response = await fetch(`${this.config.baseUrl}/api/get_municipalities.php`);
            const data = await response.json();
            
            if (data.success) {
                if (this.config.cacheData) {
                    this.cache.municipalities = data.municipalities;
                }
                return data.municipalities;
            } else {
                console.error('Error fetching municipalities:', data.error);
                return [];
            }
        } catch (error) {
            console.error('API Error:', error);
            return [];
        }
    }

    /**
     * Get hospitals from API
     * @returns {Promise<Array>} Array of hospital objects
     */
    async getHospitals() {
        if (this.cache.hospitals) {
            return this.cache.hospitals;
        }

        try {
            const response = await fetch(`${this.config.baseUrl}/api/get_all_hospitals.php`);
            const data = await response.json();
            
            if (data.success) {
                if (this.config.cacheData) {
                    this.cache.hospitals = data.hospitals;
                }
                return data.hospitals;
            } else {
                console.error('Error fetching hospitals:', data.error);
                return [];
            }
        } catch (error) {
            console.error('API Error:', error);
            return [];
        }
    }

    /**
     * Get blood banks from API
     * @returns {Promise<Array>} Array of blood bank objects
     */
    async getBloodBanks() {
        if (this.cache.bloodBanks) {
            return this.cache.bloodBanks;
        }

        try {
            const response = await fetch(`${this.config.baseUrl}/api/get_all_blood_banks.php`);
            const data = await response.json();
            
            if (data.success) {
                if (this.config.cacheData) {
                    this.cache.bloodBanks = data.bloodBanks;
                }
                return data.bloodBanks;
            } else {
                console.error('Error fetching blood banks:', data.error);
                return [];
            }
        } catch (error) {
            console.error('API Error:', error);
            return [];
        }
    }

    /**
     * Get hospitals filtered by municipality
     * @param {string} municipality - Municipality name
     * @returns {Promise<Array>} Array of hospital objects
     */
    async getHospitalsByMunicipality(municipality) {
        const hospitals = await this.getHospitals();
        return hospitals.filter(h => h.municipality === municipality);
    }

    /**
     * Get blood banks filtered by municipality
     * @param {string} municipality - Municipality name
     * @returns {Promise<Array>} Array of blood bank objects
     */
    async getBloodBanksByMunicipality(municipality) {
        const bloodBanks = await this.getBloodBanks();
        return bloodBanks.filter(b => b.municipality === municipality);
    }

    /**
     * Link municipality dropdown to facility dropdown
     * When municipality changes, update available facilities
     * 
     * @param {string} municipalitySelectId - ID of municipality select element
     * @param {string} facilitySelectId - ID of facility select element
     * @param {string} facilityType - Type of facilities: 'hospital' or 'blood_bank'
     */
    async linkDropdowns(municipalitySelectId, facilitySelectId, facilityType = 'hospital') {
        const municipalitySelect = document.getElementById(municipalitySelectId);
        const facilitySelect = document.getElementById(facilitySelectId);

        if (!municipalitySelect || !facilitySelect) {
            console.error('One or both select elements not found');
            return;
        }

        // Handle change event
        municipalitySelect.addEventListener('change', async (e) => {
            const municipality = e.target.value;
            
            // Reset facility dropdown
            facilitySelect.innerHTML = `<option value="">-- Select ${facilityType === 'hospital' ? 'Hospital' : 'Blood Bank'} --</option>`;
            
            if (!municipality) return;

            let facilities = [];
            if (facilityType === 'hospital') {
                facilities = await this.getHospitalsByMunicipality(municipality);
            } else if (facilityType === 'blood_bank') {
                facilities = await this.getBloodBanksByMunicipality(municipality);
            }

            // Populate dropdown
            facilities.forEach(facility => {
                const option = document.createElement('option');
                option.value = facility.name;
                option.textContent = facility.name;
                facilitySelect.appendChild(option);
            });
        });

        // Trigger change on page load if municipality has pre-selected value
        if (municipalitySelect.value) {
            municipalitySelect.dispatchEvent(new Event('change'));
        }
    }

    /**
     * Populate a select dropdown with options
     * @param {string} selectId - ID of select element
     * @param {Array} options - Array of option objects {value, text}
     * @param {string} selectedValue - Currently selected value (optional)
     */
    populateDropdown(selectId, options, selectedValue = '') {
        const select = document.getElementById(selectId);
        if (!select) return;

        select.innerHTML = '<option value="">-- Select --</option>';
        
        options.forEach(option => {
            const opt = document.createElement('option');
            opt.value = option.value;
            opt.textContent = option.text;
            if (option.value === selectedValue) {
                opt.selected = true;
            }
            select.appendChild(opt);
        });
    }

    /**
     * Clear the cache
     */
    clearCache() {
        this.cache = {};
    }
}

/**
 * Helper function: Initialize location manager globally
 * Usage: initializeLocationManager() or initializeLocationManager({baseUrl: '/custom/path'})
 */
function initializeLocationManager(config = {}) {
    window.locationManager = new LocationManager(config);
    return window.locationManager;
}

/**
 * Helper function: Setup linked dropdowns on page load
 * Usage: setupLinkedDropdowns('city', 'hospital', 'hospital')
 */
async function setupLinkedDropdowns(municipalityId, facilityId, facilityType = 'hospital') {
    const manager = window.locationManager || new LocationManager();
    await manager.linkDropdowns(municipalityId, facilityId, facilityType);
}

/**
 * Helper function: Validate municipality
 * Usage: if (await isValidMunicipality('Cabanatuan')) { ... }
 */
async function isValidMunicipality(municipality) {
    const municipalities = await (window.locationManager || new LocationManager()).getMunicipalities();
    return municipalities.includes(municipality);
}

/**
 * Helper function: Validate hospital
 * Usage: if (await isValidHospital('Premiere Medical Center')) { ... }
 */
async function isValidHospital(hospitalName) {
    const hospitals = await (window.locationManager || new LocationManager()).getHospitals();
    return hospitals.some(h => h.name === hospitalName);
}

/**
 * Helper function: Validate blood bank
 * Usage: if (await isValidBloodBank('Philippine Red Cross')) { ... }
 */
async function isValidBloodBank(bankName) {
    const bloodBanks = await (window.locationManager || new LocationManager()).getBloodBanks();
    return bloodBanks.some(b => b.name === bankName);
}
