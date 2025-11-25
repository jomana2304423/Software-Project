// Pharmacy Management System - Enhanced Interactive JavaScript

// Global variables
let isLoading = false;
let notificationCount = 0;

document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Initialize all components
    initializeAlerts();
    initializeFormValidation();
    initializeTooltips();
    initializeSearch();
    initializeAnimations();
    initializeNotifications();
    initializeCharts();
    initializeModals();
    initializeTables();
    initializeButtons();
    
    // Start periodic updates
    startPeriodicUpdates();
}

// Enhanced Alert Management
function initializeAlerts() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert, index) {
        // Stagger animation
        setTimeout(() => {
            alert.classList.add('fade-in');
        }, index * 100);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            if (alert.classList.contains('show')) {
                hideAlert(alert);
            }
        }, 5000);
    });
}

function hideAlert(alert) {
    alert.style.transition = 'all 0.3s ease';
    alert.style.transform = 'translateX(100%)';
    alert.style.opacity = '0';
    
    setTimeout(() => {
        if (alert.parentNode) {
            alert.parentNode.removeChild(alert);
        }
    }, 300);
}

function showAlert(message, type = 'info', duration = 5000) {
    const alertContainer = document.querySelector('.container-fluid') || document.body;
    const alertId = 'alert-' + Date.now();
    
    const alertHtml = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="bi bi-${getAlertIcon(type)}"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    alertContainer.insertAdjacentHTML('afterbegin', alertHtml);
    
    const alert = document.getElementById(alertId);
    setTimeout(() => {
        hideAlert(alert);
    }, duration);
}

function getAlertIcon(type) {
    const icons = {
        'success': 'check-circle',
        'danger': 'exclamation-triangle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// Enhanced Form Validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                showAlert('Please fill in all required fields correctly.', 'warning');
            }
            form.classList.add('was-validated');
        });
        
        // Real-time validation
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', validateField);
            input.addEventListener('input', clearFieldError);
        });
    });
}

function validateField(e) {
    const field = e.target;
    const value = field.value.trim();
    
    // Clear previous error
    clearFieldError(e);
    
    // Required field validation
    if (field.hasAttribute('required') && !value) {
        showFieldError(field, 'This field is required.');
        return false;
    }
    
    // Email validation
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            showFieldError(field, 'Please enter a valid email address.');
            return false;
        }
    }
    
    // Phone validation
    if (field.type === 'tel' && value) {
        const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
        if (!phoneRegex.test(value.replace(/\s/g, ''))) {
            showFieldError(field, 'Please enter a valid phone number.');
            return false;
        }
    }
    
    return true;
}

function showFieldError(field, message) {
    field.classList.add('is-invalid');
    
    let feedback = field.parentNode.querySelector('.invalid-feedback');
    if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        field.parentNode.appendChild(feedback);
    }
    feedback.textContent = message;
}

function clearFieldError(e) {
    const field = e.target;
    field.classList.remove('is-invalid');
    const feedback = field.parentNode.querySelector('.invalid-feedback');
    if (feedback) {
        feedback.textContent = '';
    }
}

// Enhanced Tooltips
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            delay: { show: 500, hide: 100 }
        });
    });
}

// Enhanced Search with Debouncing
function initializeSearch() {
    const searchInputs = document.querySelectorAll('input[type="search"], input[placeholder*="search" i]');
    searchInputs.forEach(input => {
        let timeout;
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                performSearch(this);
            }, 300);
        });
    });
}

function performSearch(input) {
    const searchTerm = input.value.toLowerCase();
    const tableId = input.getAttribute('data-table') || 'dataTable';
    const table = document.getElementById(tableId);
    
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    let visibleCount = 0;
    
    rows.forEach(function(row) {
        const text = row.textContent.toLowerCase();
        const isVisible = text.includes(searchTerm);
        row.style.display = isVisible ? '' : 'none';
        if (isVisible) visibleCount++;
        
        // Add highlight effect
        if (isVisible && searchTerm) {
            highlightText(row, searchTerm);
        }
    });
    
    // Show "no results" message if needed
    showSearchResults(visibleCount, searchTerm);
}

function highlightText(element, searchTerm) {
    const walker = document.createTreeWalker(
        element,
        NodeFilter.SHOW_TEXT,
        null,
        false
    );
    
    const textNodes = [];
    let node;
    while (node = walker.nextNode()) {
        textNodes.push(node);
    }
    
    textNodes.forEach(textNode => {
        const text = textNode.textContent;
        const regex = new RegExp(`(${searchTerm})`, 'gi');
        if (regex.test(text)) {
            const highlightedText = text.replace(regex, '<mark>$1</mark>');
            const span = document.createElement('span');
            span.innerHTML = highlightedText;
            textNode.parentNode.replaceChild(span, textNode);
        }
    });
}

function showSearchResults(count, searchTerm) {
    let resultsDiv = document.getElementById('search-results');
    if (!resultsDiv) {
        resultsDiv = document.createElement('div');
        resultsDiv.id = 'search-results';
        resultsDiv.className = 'alert alert-info mt-3';
        document.querySelector('.card-body').appendChild(resultsDiv);
    }
    
    if (searchTerm && count === 0) {
        resultsDiv.innerHTML = `<i class="bi bi-search"></i> No results found for "${searchTerm}"`;
        resultsDiv.style.display = 'block';
    } else if (searchTerm) {
        resultsDiv.innerHTML = `<i class="bi bi-check-circle"></i> Found ${count} result(s) for "${searchTerm}"`;
        resultsDiv.style.display = 'block';
    } else {
        resultsDiv.style.display = 'none';
    }
}

// Enhanced Animations
function initializeAnimations() {
    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);
    
    // Observe cards and other elements
    document.querySelectorAll('.card, .btn, .table').forEach(el => {
        observer.observe(el);
    });
}

// Enhanced Notifications
function initializeNotifications() {
    // Check for new notifications periodically
    setInterval(checkNotifications, 30000); // Every 30 seconds
}

function checkNotifications() {
    // This would typically make an AJAX call to check for new notifications
    // For now, we'll simulate it
    if (Math.random() < 0.1) { // 10% chance of new notification
        notificationCount++;
        updateNotificationBadge();
    }
}

function updateNotificationBadge() {
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        badge.textContent = notificationCount;
        badge.classList.add('pulse');
    }
}

// Enhanced Charts
function initializeCharts() {
    // Initialize any charts that might be present
    const chartElements = document.querySelectorAll('canvas');
    chartElements.forEach(canvas => {
        if (typeof Chart !== 'undefined') {
            // Chart initialization would go here
        }
    });
}

// Enhanced Modals
function initializeModals() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('show.bs.modal', function() {
            this.classList.add('fade-in');
        });
        
        modal.addEventListener('hidden.bs.modal', function() {
            this.classList.remove('fade-in');
        });
    });
}

// Enhanced Tables
function initializeTables() {
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        // Add hover effects
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.01)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });
    });
}

// Enhanced Buttons
function initializeButtons() {
    // Add loading states to buttons
    const buttons = document.querySelectorAll('button[type="submit"], .btn-primary');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            if (this.type === 'submit') {
                showButtonLoading(this);
            }
        });
    });
    
    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
}

function showButtonLoading(button) {
    const originalText = button.innerHTML;
    button.innerHTML = '<span class="loading"></span> Processing...';
    button.disabled = true;
    
    // Re-enable after 3 seconds (or when form submission completes)
    setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    }, 3000);
}

// Sales Cart Calculations
function calculateLineTotal() {
    const row = this.closest('tr');
    const quantity = parseFloat(row.querySelector('.quantity-input')?.value) || 0;
    const price = parseFloat(row.querySelector('.price-input')?.value) || 0;
    const total = quantity * price;
    
    const totalCell = row.querySelector('.line-total');
    if (totalCell) {
        totalCell.textContent = '₹' + total.toFixed(2);
        totalCell.classList.add('text-success', 'fw-bold');
    }
    
    calculateGrandTotal();
}

function calculateGrandTotal() {
    let grandTotal = 0;
    const lineTotals = document.querySelectorAll('.line-total');
    
    lineTotals.forEach(function(cell) {
        const value = parseFloat(cell.textContent.replace('₹', '')) || 0;
        grandTotal += value;
    });
    
    const discountElement = document.getElementById('discount');
    const discount = discountElement ? parseFloat(discountElement.value) || 0 : 0;
    const finalTotal = grandTotal - discount;
    
    const subtotalElement = document.getElementById('subtotal');
    const totalElement = document.getElementById('total');
    
    if (subtotalElement) {
        subtotalElement.textContent = '₹' + grandTotal.toFixed(2);
    }
    if (totalElement) {
        totalElement.textContent = '₹' + finalTotal.toFixed(2);
        totalElement.classList.add('text-success', 'fw-bold');
    }
}

// Utility Functions
function addMedicineToCart(medicineId, medicineName, price) {
    showAlert(`Added ${medicineName} to cart`, 'success');
    console.log('Adding medicine to cart:', medicineId, medicineName, price);
}

function printInvoice() {
    window.print();
}

function exportToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(function(row) {
        const cells = row.querySelectorAll('td, th');
        const rowData = [];
        cells.forEach(function(cell) {
            rowData.push('"' + cell.textContent.replace(/"/g, '""') + '"');
        });
        csv.push(rowData.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    
    const a = document.createElement('a');
    a.href = url;
    a.download = filename + '.csv';
    a.click();
    
    window.URL.revokeObjectURL(url);
    
    showAlert('CSV file downloaded successfully', 'success');
}

// Periodic Updates
function startPeriodicUpdates() {
    // Update dashboard statistics every 5 minutes
    setInterval(updateDashboardStats, 300000);
    
    // Check for system alerts every 2 minutes
    setInterval(checkSystemAlerts, 120000);
}

function updateDashboardStats() {
    // This would typically make AJAX calls to update dashboard statistics
    console.log('Updating dashboard statistics...');
}

function checkSystemAlerts() {
    // This would check for low stock, expiry alerts, etc.
    console.log('Checking system alerts...');
}

// Keyboard Shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl + S for save
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        const saveButton = document.querySelector('button[type="submit"]');
        if (saveButton) {
            saveButton.click();
        }
    }
    
    // Ctrl + F for search
    if (e.ctrlKey && e.key === 'f') {
        e.preventDefault();
        const searchInput = document.querySelector('input[type="search"], input[placeholder*="search" i]');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // Escape to close modals
    if (e.key === 'Escape') {
        const openModal = document.querySelector('.modal.show');
        if (openModal) {
            const modal = bootstrap.Modal.getInstance(openModal);
            if (modal) {
                modal.hide();
            }
        }
    }
});

// Error Handling
window.addEventListener('error', function(e) {
    console.error('JavaScript Error:', e.error);
    showAlert('An unexpected error occurred. Please refresh the page.', 'danger');
});

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    .animate-in {
        animation: slideInUp 0.6s ease-out;
    }
    
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    mark {
        background-color: #ffeb3b;
        padding: 0.1em 0.2em;
        border-radius: 3px;
    }
    
    .loading {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: #fff;
        animation: spin 1s ease-in-out infinite;
        margin-right: 8px;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
