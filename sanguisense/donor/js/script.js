// Donor specific JavaScript with blood theme
document.addEventListener('DOMContentLoaded', function() {
    // Initialize donor dashboard
    initializeDonorDashboard();
    
    // Create floating blood cells
    createFloatingBloodCells();
    
    // Smooth scrolling for anchor links
    initializeSmoothScrolling();
    
    // Form validation
    initializeFormValidation();
    
    // Notification system
    initializeNotifications();
    
    // Fix logout buttons
    initializeLogoutButtons();
});

function initializeDonorDashboard() {
    console.log('Donor dashboard initialized');
    
    // Add hover effects to cards
    const cards = document.querySelectorAll('.dashboard-card, .feature-card, .facility-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}

function createFloatingBloodCells() {
    const container = document.createElement('div');
    container.className = 'floating-cells';
    document.body.appendChild(container);

    // Create multiple blood cells
    for (let i = 0; i < 12; i++) {
        const cell = document.createElement('div');
        cell.className = 'blood-cell';
        
        // Random size between 5px and 15px
        const size = Math.random() * 10 + 5;
        cell.style.width = `${size}px`;
        cell.style.height = `${size}px`;
        
        // Random position
        cell.style.left = `${Math.random() * 100}%`;
        
        // Random animation delay and duration
        const delay = Math.random() * 15;
        const duration = Math.random() * 15 + 15;
        cell.style.animationDelay = `${delay}s`;
        cell.style.animationDuration = `${duration}s`;
        
        // Random color variation
        const redVariation = Math.random() * 40 - 20;
        cell.style.backgroundColor = `rgb(${187 + redVariation}, ${10 + redVariation}, ${30 + redVariation})`;
        
        container.appendChild(cell);
    }
}

function initializeSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

function initializeFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let valid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.style.borderColor = '#dc3545';
                    // Add error message
                    if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('field-error')) {
                        const error = document.createElement('div');
                        error.className = 'field-error';
                        error.style.color = '#dc3545';
                        error.style.fontSize = '0.85rem';
                        error.style.marginTop = '0.3rem';
                        error.textContent = 'This field is required';
                        field.parentNode.appendChild(error);
                    }
                } else {
                    field.style.borderColor = '';
                    // Remove error message
                    const error = field.nextElementSibling;
                    if (error && error.classList.contains('field-error')) {
                        error.remove();
                    }
                }
            });
            
            if (!valid) {
                e.preventDefault();
                // Scroll to first error
                const firstError = this.querySelector('[required]');
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }
        });
    });
}

function initializeNotifications() {
    // Check for new notifications periodically
    setInterval(() => {
        // This would typically make an AJAX call to check for new notifications
        console.log('Checking for new notifications...');
    }, 30000); // Check every 30 seconds
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

// Blood type compatibility helper
function checkCompatibility(donorType, recipientType) {
    const compatibility = {
        'O-': ['O-', 'O+', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'],
        'O+': ['O+', 'A+', 'B+', 'AB+'],
        'A-': ['A-', 'A+', 'AB+', 'AB-'],
        'A+': ['A+', 'AB+'],
        'B-': ['B-', 'B+', 'AB+', 'AB-'],
        'B+': ['B+', 'AB+'],
        'AB-': ['AB-', 'AB+'],
        'AB+': ['AB+']
    };
    
    return compatibility[donorType] && compatibility[donorType].includes(recipientType);
}

// Modal functions
function showDeleteModal() {
    document.getElementById('deleteModal').style.display = 'flex';
}

function hideDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

function cancelDonation(donationId) {
    if (confirm('Are you sure you want to cancel this donation?')) {
        // This would typically make an AJAX call
        window.location.href = 'cancel_donation.php?id=' + donationId;
    }
}

// Eligibility countdown
function updateEligibilityCountdown() {
    const eligibilityElement = document.querySelector('.eligibility-status');
    if (eligibilityElement && eligibilityElement.classList.contains('not-eligible')) {
        const nextDate = eligibilityElement.textContent.match(/([A-Za-z]+ \d{1,2}, \d{4})/);
        if (nextDate) {
            // You could add a countdown timer here
            console.log('Next eligible date:', nextDate[0]);
        }
    }
}