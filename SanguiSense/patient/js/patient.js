// Patient Portal JavaScript - Map, Filters, and Request Handling

// Initialize Leaflet Map
let map;
let markersGroup = new L.FeatureGroup();
let donorMarkers = {};
let searchPerformed = false; // Track whether user has performed a search

function initializeMap() {
    // Initialize Leaflet map centered on Nueva Ecija, Philippines (Region 3)
    // Nueva Ecija coordinates: 14.7995° N, 121.4936° E
    // Center on central Nueva Ecija and constrain view tightly to the province bounds
    map = L.map('donor-map', {
        center: [14.81, 121.45],
        zoom: 10,
        minZoom: 9,
        maxZoom: 18,
        zoomControl: true,
        maxBounds: [[14.408, 120.884], [15.218, 122.025]],
        maxBoundsViscosity: 1.0  // Prevents panning outside bounds completely
    });

    // Modern-styled basemap (CartoDB Positron) - clean, minimal, modern look
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a> | Region 3 - Nueva Ecija',
        subdomains: 'abcd',
        maxZoom: 19
    }).addTo(map);

    // Nueva Ecija Province bounds (Region 3, Philippines)
    // SW corner: 14.408° N, 120.884° E
    // NE corner: 15.218° N, 122.025° E
    const nueeaBounds = [
        [14.408, 120.884],  // Southwest
        [15.218, 122.025]   // Northeast
    ];
    
    // Fit map tightly to Nueva Ecija bounds on initial load
    map.fitBounds(nueeaBounds, { padding: [30, 30] });
    
    // Draw a subtle rectangle to show Nueva Ecija boundaries
    L.rectangle(nueeaBounds, {
        color: '#00bcd4',
        weight: 2,
        opacity: 0.3,
        fill: true,
        fillColor: '#00bcd4',
        fillOpacity: 0.05,
        dashArray: '5, 5'
    }).addTo(map).bindPopup('<strong>Nueva Ecija</strong><br/>Region 3, Philippines');
    
    // Add a legend to show map region
    const legend = L.control({ position: 'topleft' });
    legend.onAdd = function() {
        const div = L.DomUtil.create('div', 'leaflet-control leaflet-bar' );
        div.innerHTML = `
            <div style="background: white; padding: 1rem; border-radius: 5px; box-shadow: 0 2px 8px rgba(0,0,0,0.2); font-size: 0.9rem; max-width: 200px;">
                <strong style="display: block; margin-bottom: 0.5rem; color: #00bcd4;">📍 Map Region</strong>
                <p style="margin: 0.3rem 0; color: #333;">Province: Nueva Ecija</p>
                <p style="margin: 0.3rem 0; color: #333;">Region: 3 (CALABARZON)</p>
                <p style="margin: 0.3rem 0; color: #333;">Country: Philippines</p>
            </div>
        `;
        L.DomEvent.disableClickPropagation(div);
        return div;
    };
    legend.addTo(map);
    
    // Add markers layer group
    markersGroup.addTo(map);
    
    // Load facility locations first
    loadFacilitiesOnMap();
    
    // Load donor locations
    loadDonorsOnMap();
}

function loadFacilitiesOnMap() {
    // Load facilities from the API
    fetch('api/get_facilities.php', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.facilities.length > 0) {
            data.facilities.forEach(facility => {
                const lat = parseFloat(facility.latitude);
                const lng = parseFloat(facility.longitude);
                
                // Create facility marker icon based on type
                const facilityColor = facility.type === 'hospital' ? '#e74c3c' : '#3498db';
                const facilityIcon = L.divIcon({
                    html: `<div style="background-color: ${facilityColor}; width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.4); font-size: 20px;">
                        ${facility.type === 'hospital' ? '🏥' : '🩸'}
                    </div>`,
                    iconSize: [45, 45],
                    className: 'facility-marker'
                });
                
                const facilityMarker = L.marker([lat, lng], { icon: facilityIcon });
                
                // Create popup content for facility
                const facilityPopup = `
                    <div style="width: 300px; font-family: Arial, sans-serif;">
                        <h4 style="margin: 0 0 8px 0; color: ${facilityColor};">${facility.name}</h4>
                        <p style="margin: 4px 0;"><strong>Type:</strong> ${facility.type === 'hospital' ? 'Hospital' : 'Blood Bank'}</p>
                        <p style="margin: 4px 0;"><strong>City:</strong> ${facility.city}</p>
                        <p style="margin: 4px 0;"><strong>Address:</strong> ${facility.address}</p>
                        <p style="margin: 4px 0;"><strong>Phone:</strong> ${facility.phone}</p>
                        <p style="margin: 4px 0;"><strong>Email:</strong> <a href="mailto:${facility.email}">${facility.email}</a></p>
                        ${facility.recent_donations > 0 ? `<p style="margin: 4px 0; color: #27ae60;"><strong>✓ Recent Donations:</strong> ${facility.recent_donations} in last 7 days</p>` : ''}
                        ${facility.available_blood_types && facility.available_blood_types.length > 0 ? `<p style="margin: 4px 0;"><strong>Available Types:</strong> ${facility.available_blood_types.join(', ')}</p>` : ''}
                        <button class="btn-contact-facility" data-facility-id="${facility.facility_id}" style="margin-top: 10px; padding: 8px 16px; background: ${facilityColor}; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; width: 100%;">
                            ${facility.type === 'hospital' ? 'Request Blood' : 'Donate Blood'}
                        </button>
                    </div>
                `;
                
                facilityMarker.bindPopup(facilityPopup);
                markersGroup.addLayer(facilityMarker);
            });
            
            console.log('Loaded ' + data.facilities.length + ' facilities from Nueva Ecija');
        }
    })
    .catch(error => {
        console.error('Error loading facilities:', error);
    });
}

function loadDonorsOnMap() {
    const bloodType = document.getElementById('filterBloodType')?.value || '';
    const city = document.getElementById('filterCity')?.value || '';
    
    fetch('api/get_donors.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `blood_type=${encodeURIComponent(bloodType)}&city=${encodeURIComponent(city)}`
    })
    .then(response => response.json())
    .then(data => {
        // Clear existing markers
        markersGroup.clearLayers();
        donorMarkers = {};
        
        if (data.success && data.donors.length > 0) {
            data.donors.forEach(donor => {
                const lat = parseFloat(donor.latitude);
                const lng = parseFloat(donor.longitude);
                
                // Create custom marker icon based on blood type
                const markerColor = getBloodTypeColor(donor.blood_type);
                const markerIcon = L.divIcon({
                    html: `<div style="background-color: ${markerColor}; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">${donor.blood_type}</div>`,
                    iconSize: [40, 40],
                    className: 'donor-marker'
                });
                
                const marker = L.marker([lat, lng], { icon: markerIcon });
                
                // Create popup content
                const popupContent = `
                    <div style="width: 280px; font-family: Arial, sans-serif;">
                        <h4 style="margin: 0 0 8px 0; color: #00bcd4;">${donor.name}</h4>
                        <p style="margin: 4px 0;"><strong>Blood Type:</strong> <span style="color: #c8102e; font-weight: bold;">${donor.blood_type}</span></p>
                        <p style="margin: 4px 0;"><strong>City:</strong> ${donor.city}</p>
                        ${donor.phone ? `<p style="margin: 4px 0;"><strong>Phone:</strong> ${donor.phone}</p>` : ''}
                        <p style="margin: 4px 0;"><strong>Status:</strong> <span style="color: ${donor.is_eligible ? '#27ae60' : '#dc3545'};">${donor.is_eligible ? 'Eligible' : 'Not Eligible'}</span></p>
                        ${donor.distance_km ? `<p style="margin: 4px 0;"><strong>Distance:</strong> ${parseFloat(donor.distance_km).toFixed(2)} km</p>` : ''}
                        <button class="btn-contact-donor" data-donor-id="${donor.donor_id}" style="margin-top: 10px; padding: 8px 16px; background: #00bcd4; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">Contact Donor</button>
                    </div>
                `;
                
                marker.bindPopup(popupContent);
                markersGroup.addLayer(marker);
                donorMarkers[donor.donor_id] = { marker, data: donor };
            });
            
            // Fit map to bounds of all markers
            if (markersGroup.getLayers().length > 0) {
                map.fitBounds(markersGroup.getBounds().pad(0.1));
            }
        } else {
            if (searchPerformed) {
                showAlert('No donors found matching your criteria.', 'info');
            }
        }
    })
    .catch(error => {
        console.error('Error loading donors:', error);
        showAlert('Error loading donors. Please try again.', 'error');
    });
}

function getBloodTypeColor(bloodType) {
    const colors = {
        'O+': '#e74c3c',
        'O-': '#c0392b',
        'A+': '#e67e22',
        'A-': '#d35400',
        'B+': '#3498db',
        'B-': '#2980b9',
        'AB+': '#9b59b6',
        'AB-': '#8e44ad'
    };
    return colors[bloodType] || '#95a5a6';
}

// Filter Donors
function filterDonors() {
    searchPerformed = true; // Mark that a search has been performed
    loadDonorsOnMap();
    loadDonorsInList();
}

function loadDonorsInList() {
    const bloodType = document.getElementById('filterBloodType')?.value || '';
    const city = document.getElementById('filterCity')?.value || '';
    
    fetch('api/get_donors.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `blood_type=${encodeURIComponent(bloodType)}&city=${encodeURIComponent(city)}`
    })
    .then(response => response.json())
    .then(data => {
        const listContainer = document.getElementById('donorsList');
        if (!listContainer) return;
        
        listContainer.innerHTML = '';
        
        if (data.success && data.donors.length > 0) {
            data.donors.forEach(donor => {
                const distance = donor.distance_km ? parseFloat(donor.distance_km).toFixed(2) : 'N/A';
                const eligibilityClass = donor.is_eligible ? 'eligible' : 'ineligible';
                const eligibilityText = donor.is_eligible ? 'Eligible' : 'Not Eligible';
                
                const card = document.createElement('div');
                card.className = 'donor-card';
                card.innerHTML = `
                    <div class="distance-badge">${distance} km</div>
                    <h4>${donor.name}</h4>
                    <div class="donor-info">
                        <div class="blood-type-badge">${donor.blood_type}</div>
                        <div class="eligibility-badge ${eligibilityClass}">${eligibilityText}</div>
                        <p><strong>City:</strong> ${donor.city}</p>
                        ${donor.phone ? `<p><strong>Phone:</strong> ${donor.phone}</p>` : ''}
                        ${donor.last_donation_date ? `<p><strong>Last Donation:</strong> ${donor.last_donation_date}</p>` : ''}
                    </div>
                    <button class="btn btn-primary btn-small contact-donor-btn" data-donor-id="${donor.donor_id}">
                        Contact Donor
                    </button>
                `;
                listContainer.appendChild(card);
            });
        } else {
            if (searchPerformed) {
                const noData = document.createElement('div');
                noData.className = 'data-table-empty';
                noData.innerHTML = '<p>No donors found matching your criteria.</p>';
                listContainer.appendChild(noData);
            }
        }
    })
    .catch(error => {
        console.error('Error loading donors list:', error);
        showAlert('Error loading donors. Please try again.', 'error');
    });
}

// Contact Donor Modal
function openContactDonorModal(donorId) {
    const modal = document.getElementById('contactDonorModal');
    if (modal) {
        document.getElementById('selectedDonorId').value = donorId;
        modal.classList.add('active');
    }
}

function closeContactDonorModal() {
    const modal = document.getElementById('contactDonorModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('contact-donor-btn') || e.target.classList.contains('btn-contact-donor')) {
        const donorId = e.target.getAttribute('data-donor-id');
        openContactDonorModal(donorId);
    }
});

// Close modal when clicking close button
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('close-modal')) {
        e.target.closest('.modal').classList.remove('active');
    }
});

// Robust: also attach explicit listeners to any close buttons (handles delegated elements)
function bindCloseModalButtons() {
    document.querySelectorAll('.close-modal').forEach(btn => {
        if (!btn._closeBound) {
            btn.addEventListener('click', function(ev) {
                const modal = btn.closest('.modal');
                if (modal) modal.classList.remove('active');
            });
            btn._closeBound = true;
        }
    });
}
// Run initial bind and re-bind after dynamic content loads
document.addEventListener('DOMContentLoaded', bindCloseModalButtons);
document.addEventListener('click', function(e) {
    // If a details view loads dynamic close buttons, ensure they are bound
    if (e.target && e.target.closest && e.target.closest('.modal')) {
        bindCloseModalButtons();
    }
});

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});

// Robust modal close handlers: support clicks on inner elements and Escape key
document.addEventListener('click', function(e) {
    const closeBtn = e.target.closest && e.target.closest('.close-modal');
    if (closeBtn) {
        const modal = closeBtn.closest('.modal');
        if (modal) modal.classList.remove('active');
    }
});

// Close modal on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' || e.key === 'Esc') {
        document.querySelectorAll('.modal.active').forEach(modal => modal.classList.remove('active'));
    }
});

// Send Blood Request
function submitBloodRequest(event) {
    if (event) {
        event.preventDefault();
    }
    
    const form = event?.target || document.getElementById('bloodRequestForm');
    if (!form) return;
    
    const formData = new FormData(form);
    
    // Validate required fields
    const bloodType = formData.get('blood_type');
    const facilityType = formData.get('facility_type');
    const quantity = formData.get('quantity_units');
    const requiredDate = formData.get('required_date');
    
    if (!bloodType || !facilityType || !quantity || !requiredDate) {
        showAlert('Please fill in all required fields.', 'error');
        return;
    }
    
    // Validate quantity
    if (isNaN(quantity) || quantity < 1) {
        showAlert('Quantity must be a valid number greater than 0.', 'error');
        return;
    }
    
    // Validate date
    const selectedDate = new Date(requiredDate);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (selectedDate < today) {
        showAlert('Required date must be today or in the future.', 'error');
        return;
    }
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn?.textContent;
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner"></span> Submitting...';
    }
    
    // Submit request
    fetch('api/submit_blood_request.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Blood request submitted successfully!', 'success');
            form.reset();
            // Reload requests list if it exists
            setTimeout(() => {
                if (window.loadRequestHistory) {
                    window.loadRequestHistory();
                }
            }, 1000);
        } else {
            showAlert(data.message || 'Error submitting request.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error submitting request. Please try again.', 'error');
    })
    .finally(() => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
}

// Load Patient's Blood Request History
function loadRequestHistory() {
    const container = document.getElementById('requestHistoryTable');
    if (!container) return;
    
    fetch('api/get_patient_requests.php')
    .then(response => response.json())
    .then(data => {
        if (data.success && data.requests.length > 0) {
            let html = '<div class="data-table"><h3>Your Blood Requests</h3><table><thead><tr><th>ID</th><th>Blood Type</th><th>Quantity</th><th>Required Date</th><th>Urgency</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead><tbody>';
            
            data.requests.forEach(request => {
                const statusClass = `status-${request.status}`;
                html += `
                    <tr>
                        <td>#${request.id}</td>
                        <td><span class="blood-type-badge">${request.blood_type}</span></td>
                        <td>${request.quantity_units} unit(s)</td>
                        <td>${request.required_date}</td>
                        <td>${capitalizeFirst(request.urgency)}</td>
                        <td><span class="status-badge ${statusClass}">${capitalizeFirst(request.status)}</span></td>
                        <td>${formatDate(request.created_at)}</td>
                        <td>
                            <button class="btn btn-secondary btn-small" onclick="viewRequestDetails(${request.id})">View</button>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table></div>';
            container.innerHTML = html;
        } else {
            container.innerHTML = '<p style="text-align: center; padding: 2rem; color: #e0e0e0;">No blood requests yet.</p>';
        }
    })
    .catch(error => {
        console.error('Error loading requests:', error);
        container.innerHTML = '<p style="color: red;">Error loading requests.</p>';
    });
}

// View Request Details
function viewRequestDetails(requestId) {
    fetch(`api/get_request_details.php?id=${requestId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const request = data.request;
            const modal = document.getElementById('requestDetailsModal');
            if (modal) {
                document.getElementById('modalRequestContent').innerHTML = `
                    <div class="modal-header">
                        <h3>Blood Request #${request.id}</h3>
                    </div>
                    <div>
                        <p><strong>Blood Type:</strong> <span class="blood-type-badge">${request.blood_type}</span></p>
                        <p><strong>Quantity:</strong> ${request.quantity_units} unit(s)</p>
                        <p><strong>Required Date:</strong> ${request.required_date}</p>
                        <p><strong>Urgency:</strong> ${capitalizeFirst(request.urgency)}</p>
                        <p><strong>Status:</strong> <span class="status-badge status-${request.status}">${capitalizeFirst(request.status)}</span></p>
                        <p><strong>Reason:</strong> ${request.reason || 'N/A'}</p>
                        <p><strong>Notes:</strong> ${request.notes || 'N/A'}</p>
                        <p><strong>Created:</strong> ${formatDate(request.created_at)}</p>
                        <p><strong>Last Updated:</strong> ${formatDate(request.updated_at)}</p>
                    </div>
                `;
                modal.classList.add('active');
            }
        }
    })
    .catch(error => console.error('Error loading request details:', error));
}

// Utility Functions
function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alertContainer');
    if (!alertContainer) {
        const container = document.createElement('div');
        container.id = 'alertContainer';
        container.style.position = 'fixed';
        container.style.top = '80px';
        container.style.right = '20px';
        container.style.zIndex = '9999';
        container.style.maxWidth = '400px';
        document.body.appendChild(container);
    }
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    alert.style.marginBottom = '10px';
    alert.style.animation = 'slideUp 0.3s ease';
    
    document.getElementById('alertContainer').appendChild(alert);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.3s ease';
        setTimeout(() => alert.remove(), 300);
    }, 5000);
}

function capitalizeFirst(string) {
    if (!string) return '';
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

// Geolocation support
function getUserLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                map.setView([lat, lng], 12);
                L.circleMarker([lat, lng], {
                    radius: 8,
                    fillColor: '#00bcd4',
                    color: '#0097a7',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.8
                }).addTo(map).bindPopup('Your Location');
            },
            function() {
                console.log('Geolocation permission denied');
            }
        );
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const mapContainer = document.getElementById('donor-map');
    if (mapContainer) {
        initializeMap();
        // Don't call filterDonors() here - wait for user to perform a search
        // Only load facilities on map, not donors
    }
    
    // Load request history if on history page
    if (document.getElementById('requestHistoryTable')) {
        loadRequestHistory();
    }
    
    // Set minimum date to today for date inputs
    const requiredDateInput = document.querySelector('input[name="required_date"]');
    if (requiredDateInput) {
        const today = new Date().toISOString().split('T')[0];
        requiredDateInput.setAttribute('min', today);
    }
});

// Add event listeners for filter buttons
document.addEventListener('DOMContentLoaded', function() {
    const filterBtn = document.getElementById('filterBtn');
    if (filterBtn) {
        filterBtn.addEventListener('click', filterDonors);
    }
    
    // Add change event listeners to filter dropdowns for real-time filtering
    const filterBloodType = document.getElementById('filterBloodType');
    const filterCity = document.getElementById('filterCity');
    
    if (filterBloodType) {
        filterBloodType.addEventListener('change', filterDonors);
    }
    if (filterCity) {
        filterCity.addEventListener('change', filterDonors);
    }
    
    // Enter key to submit form
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
            }
        });
    });
});
