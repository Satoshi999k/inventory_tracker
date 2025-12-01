/**
 * Authentication Module - Shared across all pages
 * Handles login checks, user menu setup, and logout functionality
 */

// Check authentication on page load
document.addEventListener('DOMContentLoaded', function() {
    checkAuthentication();
    setupUserMenu();
});

/**
 * Check if user is authenticated
 * Redirects to login if no valid token exists
 */
function checkAuthentication() {
    const authToken = localStorage.getItem('authToken');
    
    if (!authToken) {
        // Only redirect if not already on login page
        if (!window.location.pathname.includes('login')) {
            window.location.href = '/login.html';
        }
        return false;
    }
    
    return true;
}

/**
 * Setup user menu with user information and logout handler
 */
function setupUserMenu() {
    // Get user info from localStorage
    const userJSON = localStorage.getItem('user');
    const user = userJSON ? JSON.parse(userJSON) : { name: 'Admin' };
    
    // Update user name display
    const userNameElements = document.querySelectorAll('#userName');
    userNameElements.forEach(el => {
        el.textContent = user.name || 'Admin';
    });
    
    // Setup user info click toggle
    const userInfoElements = document.querySelectorAll('.user-info');
    userInfoElements.forEach(userInfo => {
        userInfo.addEventListener('click', toggleUserMenu);
    });
    
    // Setup logout button
    const logoutButtons = document.querySelectorAll('#logoutBtn');
    logoutButtons.forEach(btn => {
        btn.addEventListener('click', handleLogout);
    });
    
    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        const userMenus = document.querySelectorAll('.user-menu');
        userMenus.forEach(menu => {
            if (!menu.contains(event.target)) {
                const dropdown = menu.querySelector('.dropdown-menu');
                if (dropdown) {
                    dropdown.classList.remove('active');
                }
            }
        });
    });
}

/**
 * Toggle user menu dropdown
 */
function toggleUserMenu(e) {
    e.stopPropagation();
    const userMenu = e.target.closest('.user-menu');
    const dropdown = userMenu.querySelector('.dropdown-menu');
    dropdown.classList.toggle('active');
}

/**
 * Handle logout - clear session and redirect to login
 */
function handleLogout(e) {
    e.preventDefault();
    
    // Clear localStorage
    localStorage.removeItem('authToken');
    localStorage.removeItem('user');
    
    // Redirect to login page
    window.location.href = '/login.html';
}

/**
 * Get authentication token
 */
function getAuthToken() {
    return localStorage.getItem('authToken');
}

/**
 * Get current user information
 */
function getCurrentUser() {
    const userJSON = localStorage.getItem('user');
    return userJSON ? JSON.parse(userJSON) : null;
}

/**
 * Check if user is authenticated (returns boolean)
 */
function isAuthenticated() {
    return !!localStorage.getItem('authToken');
}
