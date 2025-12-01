// API Configuration
const API_BASE_URL = 'http://localhost:8000';
const CACHE_DURATION = 5000; // 5 seconds cache
const REQUEST_TIMEOUT = 8000; // 8 second timeout

// Cache management
const apiCache = new Map();
const pendingRequests = new Map();

// API Helper Functions
const api = {
    // Helper to set request timeout
    fetchWithTimeout(url, options = {}) {
        return Promise.race([
            fetch(url, options),
            new Promise((_, reject) =>
                setTimeout(() => reject(new Error('Request timeout')), REQUEST_TIMEOUT)
            )
        ]);
    },

    // Cache manager
    getFromCache(key) {
        const cached = apiCache.get(key);
        if (cached && Date.now() - cached.timestamp < CACHE_DURATION) {
            return cached.data;
        }
        apiCache.delete(key);
        return null;
    },

    setCache(key, data) {
        apiCache.set(key, { data, timestamp: Date.now() });
    },

    clearCache() {
        apiCache.clear();
    },

    async get(endpoint, useCache = true) {
        try {
            // Check cache first
            if (useCache) {
                const cached = this.getFromCache(endpoint);
                if (cached) return cached;
            }

            // Check if request already pending
            if (pendingRequests.has(endpoint)) {
                return pendingRequests.get(endpoint);
            }

            // Create promise for this request
            const requestPromise = this.fetchWithTimeout(`${API_BASE_URL}${endpoint}`)
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    if (useCache) this.setCache(endpoint, data);
                    pendingRequests.delete(endpoint);
                    return data;
                })
                .catch(error => {
                    console.error('GET request failed:', error);
                    pendingRequests.delete(endpoint);
                    return { error: error.message };
                });

            // Store pending request
            pendingRequests.set(endpoint, requestPromise);
            return requestPromise;
        } catch (error) {
            console.error('GET request failed:', error);
            return { error: error.message };
        }
    },

    async post(endpoint, data) {
        try {
            const response = await this.fetchWithTimeout(`${API_BASE_URL}${endpoint}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const result = await response.json();
            
            // Invalidate related caches on POST
            this.invalidateCache(['/products', '/inventory', '/sales']);
            return result;
        } catch (error) {
            console.error('POST request failed:', error);
            return { error: error.message };
        }
    },

    async put(endpoint, data) {
        try {
            const response = await this.fetchWithTimeout(`${API_BASE_URL}${endpoint}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const result = await response.json();
            
            // Invalidate related caches on PUT
            this.invalidateCache(['/products', '/inventory', '/sales']);
            return result;
        } catch (error) {
            console.error('PUT request failed:', error);
            return { error: error.message };
        }
    },

    async delete(endpoint, data) {
        try {
            const response = await this.fetchWithTimeout(`${API_BASE_URL}${endpoint}`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const result = await response.json();
            
            // Invalidate related caches on DELETE
            this.invalidateCache(['/products', '/inventory', '/sales']);
            return result;
        } catch (error) {
            console.error('DELETE request failed:', error);
            return { error: error.message };
        }
    },

    invalidateCache(endpoints) {
        endpoints.forEach(endpoint => apiCache.delete(endpoint));
    }
};

// Utility Functions
function formatCurrency(value) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(value);
}

function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

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

// Animation keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);

// Scroll Performance Optimization
let ticking = false;
document.addEventListener('scroll', () => {
    if (!ticking) {
        window.requestAnimationFrame(() => {
            // Handle scroll events with RAF for smooth performance
            ticking = false;
        });
        ticking = true;
    }
}, { passive: true });

// Optimize event listeners with passive flag
if (document.addEventListener) {
    document.addEventListener('wheel', () => {}, { passive: true });
    document.addEventListener('touchmove', () => {}, { passive: true });
}
