// Admin specific JavaScript with blood theme
document.addEventListener('DOMContentLoaded', function() {
    // Initialize admin dashboard
    initializeAdminDashboard();
    
    // Create floating blood cells
    createFloatingBloodCells();
    
    // Chart initialization for analytics
    initializeCharts();
    
    // Real-time updates
    startRealTimeUpdates();
    
    // Fix logout buttons
    initializeLogoutButtons();
});

function initializeAdminDashboard() {
    console.log('Admin dashboard initialized');
    
    // Add hover effects to cards
    const cards = document.querySelectorAll('.stat-card, .content-card, .action-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Initialize data tables
    initializeDataTables();
}

function createFloatingBloodCells() {
    const container = document.createElement('div');
    container.className = 'floating-cells';
    document.body.appendChild(container);

    // Create multiple blood cells
    for (let i = 0; i < 15; i++) {
        const cell = document.createElement('div');
        cell.className = 'blood-cell';
        
        // Random size between 5px and 20px
        const size = Math.random() * 15 + 5;
        cell.style.width = `${size}px`;
        cell.style.height = `${size}px`;
        
        // Random position
        cell.style.left = `${Math.random() * 100}%`;
        
        // Random animation delay and duration
        const delay = Math.random() * 20;
        const duration = Math.random() * 10 + 20;
        cell.style.animationDelay = `${delay}s`;
        cell.style.animationDuration = `${duration}s`;
        
        // Random color variation
        const redVariation = Math.random() * 50 - 25;
        cell.style.backgroundColor = `rgb(${187 + redVariation}, ${10 + redVariation}, ${30 + redVariation})`;
        
        container.appendChild(cell);
    }
}

function initializeLogoutButtons() {
    // Ensure logout buttons work properly
    const logoutButtons = document.querySelectorAll('.logout-btn');
    logoutButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to logout?')) {
                e.preventDefault();
            }
        });
    });
}

function initializeDataTables() {
    // Simple table sorting functionality
    const tables = document.querySelectorAll('.data-table table');
    tables.forEach(table => {
        const headers = table.querySelectorAll('th[data-sortable="true"]');
        headers.forEach((header, index) => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                sortTable(table, index);
            });
        });
    });
}

function sortTable(table, columnIndex) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    const isNumeric = !isNaN(parseFloat(rows[0].cells[columnIndex].textContent));
    
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent;
        const bValue = b.cells[columnIndex].textContent;
        
        if (isNumeric) {
            return parseFloat(aValue) - parseFloat(bValue);
        } else {
            return aValue.localeCompare(bValue);
        }
    });
    
    // Reverse if already sorted
    if (tbody.querySelector('tr') === rows[0]) {
        rows.reverse();
    }
    
    // Reappend rows
    rows.forEach(row => tbody.appendChild(row));
}

function initializeCharts() {
    // Chart.js integration would go here
    console.log('Charts initialized');
}

function startRealTimeUpdates() {
    setInterval(() => {
        updateDashboardStats();
    }, 30000);
}

function updateDashboardStats() {
    // AJAX calls to update stats would go here
    console.log('Updating dashboard statistics...');
}

// Form handling functions
function showAddForm() {
    document.getElementById('addInventoryForm').style.display = 'block';
}

function hideAddForm() {
    document.getElementById('addInventoryForm').style.display = 'none';
}

function generateReport(type) {
    alert('Generating ' + type + ' report... This would typically download a PDF or Excel file.');
    // In a real implementation, this would make an AJAX call to generate and download the report
}

// Inventory management
function editInventory(id) {
    alert('Edit inventory item ' + id + ' - This would open an edit form');
    // Implementation for editing
}

function deleteInventory(id) {
    if (confirm('Are you sure you want to delete this inventory item?')) {
        window.location.href = 'delete_inventory.php?id=' + id;
    }
}

// Donor management
function viewDonor(id) {
    alert('View donor details ' + id);
    // window.location.href = 'donor_details.php?id=' + id;
}

function contactDonor(id) {
    alert('Contact donor ' + id);
    // window.location.href = 'contact_donor.php?id=' + id;
}

// Blood bank specific functions
function restockItem(id) {
    alert('Restock item ' + id + ' - This would open a restock form');
    // Implementation for restocking
}

function distributeBlood(id) {
    window.location.href = 'distribution.php?id=' + id;
}

// Restock and inventory functions
function restockItem(id) {
    window.location.href = 'inventory.php?action=restock&id=' + id;
}

// Profile page functions
function validateProfileForm() {
    let isValid = true;
    const name = document.getElementById('name');
    const email = document.getElementById('email');
    const phone = document.getElementById('phone');
    const address = document.getElementById('address');
    
    // Clear previous errors
    document.querySelectorAll('.form-error').forEach(el => el.textContent = '');
    
    // Name validation (3-100 chars)
    if (name && (name.value.trim().length < 2 || name.value.trim().length > 100)) {
        document.getElementById('name_error').textContent = 'Name must be 2-100 characters';
        isValid = false;
    }
    
    // Email validation
    if (email && email.value.trim()) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email.value.trim())) {
            document.getElementById('email_error').textContent = 'Please enter a valid email address';
            isValid = false;
        }
    }
    
    // Phone validation (10-15 digits if provided)
    if (phone && phone.value.trim()) {
        const phoneRegex = /^\+?[\d\s\-()]+$/;
        const digitsOnly = phone.value.replace(/\D/g, '');
        if (!phoneRegex.test(phone.value) || digitsOnly.length < 10 || digitsOnly.length > 15) {
            document.getElementById('phone_error').textContent = 'Phone must be 10-15 digits';
            isValid = false;
        }
    }
    
    // Address validation (if provided, 5-255 chars)
    if (address && address.value.trim() && (address.value.trim().length < 5 || address.value.trim().length > 255)) {
        document.getElementById('address_error').textContent = 'Address must be 5-255 characters';
        isValid = false;
    }
    
    return isValid;
}

// Profile picture upload handling
document.addEventListener('DOMContentLoaded', function() {
    const avatarUploader = document.getElementById('avatar-uploader');
    const profilePictureInput = document.getElementById('profile_picture');
    const pictureForm = document.getElementById('pictureForm');
    
    if (avatarUploader && profilePictureInput) {
        // Click to upload
        avatarUploader.addEventListener('click', () => {
            profilePictureInput.click();
        });
        
        // File selection handler
        profilePictureInput.addEventListener('change', handleProfilePictureChange);
        
        // Drag and drop
        avatarUploader.addEventListener('dragover', (e) => {
            e.preventDefault();
            avatarUploader.style.borderColor = 'var(--bloodbank-purple)';
            avatarUploader.style.backgroundColor = 'rgba(142, 68, 173, 0.15)';
        });
        
        avatarUploader.addEventListener('dragleave', () => {
            avatarUploader.style.borderColor = 'rgba(142, 68, 173, 0.3)';
            avatarUploader.style.backgroundColor = 'rgba(142, 68, 173, 0.05)';
        });
        
        avatarUploader.addEventListener('drop', (e) => {
            e.preventDefault();
            avatarUploader.style.borderColor = 'rgba(142, 68, 173, 0.3)';
            avatarUploader.style.backgroundColor = 'rgba(142, 68, 173, 0.05)';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                profilePictureInput.files = files;
                handleProfilePictureChange({ target: profilePictureInput });
            }
        });
    }
});

function handleProfilePictureChange(e) {
    const file = e.target.files[0];
    const pictureError = document.getElementById('picture_error');
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    
    // Clear previous error
    if (pictureError) pictureError.textContent = '';
    
    if (!file) return;
    
    // Validate file type
    if (!allowedTypes.includes(file.type)) {
        if (pictureError) pictureError.textContent = 'Only JPG, PNG, and GIF images are allowed';
        e.target.value = '';
        return;
    }
    
    // Validate file size
    if (file.size > maxSize) {
        if (pictureError) pictureError.textContent = 'File size must not exceed 5MB';
        e.target.value = '';
        return;
    }
    
    // Show preview
    const reader = new FileReader();
    reader.onload = (event) => {
        const previewImage = document.getElementById('preview-image');
        const previewInitials = document.getElementById('preview-initials');
        
        if (previewImage) {
            previewImage.src = event.target.result;
            previewImage.style.display = 'block';
        }
        if (previewInitials) {
            previewInitials.style.display = 'none';
        }
    };
    reader.readAsDataURL(file);
    
    // Auto-submit the form
    const pictureForm = document.getElementById('pictureForm');
    if (pictureForm) {
        pictureForm.submit();
    }
}