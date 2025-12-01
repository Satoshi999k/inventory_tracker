// Login functionality
document.addEventListener('DOMContentLoaded', function() {
    // Check if already logged in
    if (localStorage.getItem('authToken')) {
        window.location.href = '/';
    }

    // Check if all services are ready
    checkServicesReady();

    const form = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const errorMessage = document.getElementById('errorMessage');
    const loading = document.getElementById('loading');
    const btnText = document.getElementById('btnText');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        errorMessage.classList.remove('show');

        const email = emailInput.value.trim();
        const password = passwordInput.value;

        if (!email || !password) {
            showError('Please enter email and password');
            return;
        }

        // Show loading state
        loading.style.display = 'block';
        btnText.style.display = 'none';

        try {
            // For demo: simple client-side validation
            // In production, this would call a real authentication API
            if (email === 'admin@inventory.com' && password === 'admin123') {
                // Simulate successful login
                const authToken = 'token_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                const user = {
                    email: email,
                    name: 'Admin User',
                    role: 'Administrator',
                    loginTime: new Date().toISOString()
                };

                // Store auth data
                localStorage.setItem('authToken', authToken);
                localStorage.setItem('user', JSON.stringify(user));

                // Simulate network delay
                await new Promise(resolve => setTimeout(resolve, 1000));

                showAlert('Login successful! Redirecting...', 'success');
                setTimeout(() => {
                    window.location.href = '/';
                }, 1500);
            } else {
                showError('Invalid email or password');
            }
        } catch (error) {
            showError('Login failed: ' + error.message);
        } finally {
            loading.style.display = 'none';
            btnText.style.display = 'inline';
        }
    });

    function showError(message) {
        errorMessage.textContent = message;
        errorMessage.classList.add('show');
    }

    // Clear error on input
    emailInput.addEventListener('input', () => {
        errorMessage.classList.remove('show');
    });

    passwordInput.addEventListener('input', () => {
        errorMessage.classList.remove('show');
    });

    // Pre-fill demo credentials
    emailInput.value = 'admin@inventory.com';
    passwordInput.value = 'admin123';
});

// Check if services are ready
async function checkServicesReady() {
    const services = [
        { name: 'API Gateway', port: 8000 },
        { name: 'Product Catalog', port: 8001 },
        { name: 'Inventory', port: 8002 },
        { name: 'Sales', port: 8003 }
    ];

    for (const service of services) {
        try {
            const response = await fetch(`http://localhost:${service.port}/health`, {
                method: 'GET',
                mode: 'no-cors'
            });
        } catch (error) {
            // Service not ready - this is okay, just continue
        }
    }
}

// Simple alert function for demo
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    
    // Define colors and icons for different alert types
    const alertConfig = {
        success: {
            bgColor: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
            icon: 'check_circle',
            borderColor: '#059669'
        },
        error: {
            bgColor: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
            icon: 'error',
            borderColor: '#dc2626'
        },
        info: {
            bgColor: 'linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%)',
            icon: 'info',
            borderColor: '#0369a1'
        },
        warning: {
            bgColor: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
            icon: 'warning',
            borderColor: '#d97706'
        }
    };
    
    const config = alertConfig[type] || alertConfig.info;
    
    alertDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 20px;
        background: ${config.bgColor};
        background-size: 300% 300%;
        animation: waveGradient 8s ease infinite, slideIn 0.4s ease-out;
        color: white;
        border-radius: 12px;
        z-index: 2000;
        font-weight: 500;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        border-left: 4px solid ${config.borderColor};
        display: flex;
        align-items: center;
        gap: 12px;
        max-width: 400px;
        min-height: 50px;
    `;
    
    // Create icon element
    const icon = document.createElement('i');
    icon.className = 'material-icons';
    icon.textContent = config.icon;
    icon.style.cssText = `
        font-size: 22px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
    `;
    
    // Create message element
    const msgSpan = document.createElement('span');
    msgSpan.textContent = message;
    msgSpan.style.flex = '1';
    
    alertDiv.appendChild(icon);
    alertDiv.appendChild(msgSpan);
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.style.animation = 'slideOut 0.4s ease-out forwards';
        setTimeout(() => alertDiv.remove(), 400);
    }, 3000);
}
