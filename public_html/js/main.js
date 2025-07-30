// Main application initialization
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    
    // Check authentication
    checkAuth();
    
    // Initialize cart
    if (typeof initCart === 'function') {
        initCart();
    }
    
    // Load products if on home page
    if (document.getElementById('productList')) {
        loadProducts();
    }
    
    // Smooth scrolling for anchor links
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
    
    // Handle search form if exists
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const query = document.getElementById('searchInput').value;
            searchProducts(query);
        });
    }
    
    // Handle category filter if exists
    const categoryFilter = document.getElementById('categoryFilter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            filterByCategory(this.value);
        });
    }
    
    // Handle sort dropdown if exists
    const sortDropdown = document.getElementById('sortDropdown');
    if (sortDropdown) {
        sortDropdown.addEventListener('change', function() {
            sortProducts(this.value);
        });
    }
});

// Show loading spinner
function showLoading() {
    document.querySelector('.loading-spinner').style.display = 'flex';
}

// Hide loading spinner
function hideLoading() {
    document.querySelector('.loading-spinner').style.display = 'none';
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
}

// Format date
function formatDate(dateString) {
    const options = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return new Date(dateString).toLocaleDateString('es-ES', options);
}

// Debounce function for search
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Handle network errors
function handleNetworkError(error) {
    console.error('Network error:', error);
    showToast('Error de conexión. Por favor, intenta de nuevo.', 'danger');
}

// Update page title
function updatePageTitle(title) {
    document.title = title + ' - Webprueba.com';
}

// Handle session expiration
function handleSessionExpired() {
    localStorage.removeItem('token');
    localStorage.removeItem('adminToken');
    showToast('Tu sesión ha expirado. Por favor, inicia sesión nuevamente.', 'warning');
    setTimeout(() => {
        window.location.href = 'index.php';
    }, 2000);
}

// Check if user is authenticated
function isAuthenticated() {
    return localStorage.getItem('token') !== null;
}

// Check if user is admin
function isAdmin() {
    return localStorage.getItem('adminToken') !== null;
}

// Redirect if not authenticated
function requireAuth() {
    if (!isAuthenticated()) {
        localStorage.setItem('redirectAfterLogin', window.location.pathname);
        window.location.href = 'index.php#login';
        return false;
    }
    return true;
}

// Global error handler
window.addEventListener('error', function(e) {
    console.error('Global error:', e.error);
    if (e.error.message.includes('401')) {
        handleSessionExpired();
    }
});

// Handle back button for modals
window.addEventListener('popstate', function(e) {
    const modals = document.querySelectorAll('.modal.show');
    modals.forEach(modal => {
        const modalInstance = bootstrap.Modal.getInstance(modal);
        if (modalInstance) {
            modalInstance.hide();
        }
    });
});