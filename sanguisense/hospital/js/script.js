// Hospital specific JavaScript with blood theme
document.addEventListener('DOMContentLoaded', function() {
    // Initialize hospital dashboard
    initializeHospitalDashboard();
    
    // Create floating blood cells
    createFloatingBloodCells();
    
    // Chart initialization for analytics
    initializeCharts();
    
    // Real-time updates
    startRealTimeUpdates();
    
    // Fix logout buttons
    initializeLogoutButtons();
});

function initializeHospitalDashboard() {
    console.log('Hospital dashboard initialized');
    
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