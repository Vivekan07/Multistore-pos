/**
 * Advanced Point Of Sale
 * Main JavaScript File
 */

// Toggle password visibility
function togglePassword(inputId) {
    try {
        const input = document.getElementById(inputId);
        if (!input) {
            console.error('Password input not found:', inputId);
            return false;
        }
        
        // Find the icon - try multiple methods
        let icon = document.getElementById(inputId + '-toggle-icon');
        if (!icon) {
            // Try to find icon in the same wrapper
            const wrapper = input.closest('.password-wrapper');
            if (wrapper) {
                icon = wrapper.querySelector('.password-toggle i');
            }
        }
        
        if (!icon) {
            console.error('Password toggle icon not found for:', inputId);
            return false;
        }
        
        // Toggle password visibility
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
            if (icon.parentElement) {
                icon.parentElement.setAttribute('title', 'Hide password');
            }
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
            if (icon.parentElement) {
                icon.parentElement.setAttribute('title', 'Show password');
            }
        }
        
        return true;
    } catch (error) {
        console.error('Error toggling password:', error);
        return false;
    }
}

// Initialize password toggles on page load
document.addEventListener('DOMContentLoaded', function() {
    // Add click event listeners to all password toggle buttons as backup
    const passwordToggles = document.querySelectorAll('.password-toggle');
    passwordToggles.forEach(function(toggle) {
        // Remove existing listeners to avoid duplicates
        const newToggle = toggle.cloneNode(true);
        toggle.parentNode.replaceChild(newToggle, toggle);
        
        // Add click listener
        newToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Get input ID from onclick attribute or data attribute
            const onclickAttr = this.getAttribute('onclick');
            let inputId = null;
            
            if (onclickAttr) {
                const match = onclickAttr.match(/togglePassword\('([^']+)'\)/);
                if (match && match[1]) {
                    inputId = match[1];
                }
            }
            
            if (!inputId) {
                // Try data attribute
                inputId = this.getAttribute('data-input-id');
            }
            
            if (!inputId) {
                // Try to find input in wrapper
                const wrapper = this.closest('.password-wrapper');
                if (wrapper) {
                    const input = wrapper.querySelector('input[type="password"], input[type="text"]');
                    if (input && input.id) {
                        inputId = input.id;
                    }
                }
            }
            
            if (inputId) {
                togglePassword(inputId);
            }
        });
    });
});

// Session timeout check
let lastActivity = Date.now();
const SESSION_TIMEOUT = 3600000; // 1 hour in milliseconds

document.addEventListener('mousemove', () => {
    lastActivity = Date.now();
});

document.addEventListener('keypress', () => {
    lastActivity = Date.now();
});

setInterval(() => {
    const timeSinceLastActivity = Date.now() - lastActivity;
    if (timeSinceLastActivity > SESSION_TIMEOUT) {
        // Use SweetAlert2 if available, otherwise fallback to alert
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Session Expired',
                text: 'Your session has expired due to inactivity. You will be redirected to the login page.',
                confirmButtonColor: '#2563eb',
                confirmButtonText: 'OK',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(() => {
                window.location.href = 'index.php';
            });
        } else {
            alert('Your session has expired due to inactivity. You will be redirected to the login page.');
            window.location.href = 'index.php';
        }
    }
}, 60000); // Check every minute

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', () => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });
});

// Confirm delete actions with SweetAlert2
async function confirmDelete(message = 'Are you sure you want to delete this item?') {
    // Use SweetAlert2 if available, otherwise fallback to confirm
    if (typeof Swal !== 'undefined') {
        const result = await Swal.fire({
            title: 'Confirm Delete',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fas fa-trash"></i> Yes, Delete',
            cancelButtonText: '<i class="fas fa-times"></i> Cancel',
            reverseButtons: true
        });
        return result.isConfirmed;
    } else {
        return confirm(message);
    }
}

// Format currency
function formatCurrency(amount) {
    return 'LKR ' + new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
}

// Format number
function formatNumber(number) {
    return new Intl.NumberFormat('en-US').format(number);
}

// Show loading spinner
function showLoading(element) {
    element.innerHTML = '<div class="spinner"></div>';
}

// Validate form
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = '#ef4444';
            isValid = false;
        } else {
            field.style.borderColor = '';
        }
    });
    
    return isValid;
}

// Search functionality
function setupSearch(inputId, tableId) {
    const searchInput = document.getElementById(inputId);
    if (!searchInput) return;
    
    searchInput.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
}

// Initialize search on page load
document.addEventListener('DOMContentLoaded', () => {
    // Auto-setup search if elements exist
    const searchInputs = document.querySelectorAll('[data-search]');
    searchInputs.forEach(input => {
        const tableId = input.getAttribute('data-search');
        setupSearch(input.id || `search-${tableId}`, tableId);
    });
});

