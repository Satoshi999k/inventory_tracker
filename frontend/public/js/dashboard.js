// Dashboard functionality
// Authentication is checked by auth.js which is loaded first
let charts = {
    inventory: null,
    sales: null,
    category: null,
    stockStatus: null
};

let lastDashboardUpdate = 0;
let dashboardRefreshTimer = null;
const MIN_REFRESH_INTERVAL = 10000; // Min 10 seconds between refreshes

// Debounce function
function debounce(func, delay) {
    let timeoutId;
    return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func.apply(this, args), delay);
    };
}

// Throttle function
function throttle(func, limit) {
    let inThrottle;
    return function (...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

async function loadDashboard() {
    try {
        const now = Date.now();
        if (now - lastDashboardUpdate < MIN_REFRESH_INTERVAL) {
            return; // Skip if too soon
        }
        lastDashboardUpdate = now;

        // Load products with cache
        const productsRes = await api.get('/products', true);
        const products = productsRes.data || [];
        document.getElementById('totalProducts').textContent = products.length;

        // Load inventory with cache
        const inventoryRes = await api.get('/inventory', true);
        const inventory = inventoryRes.data || [];
        
        // Calculate low stock items and inventory value
        let lowStockCount = 0;
        let totalValue = 0;

        inventory.forEach(item => {
            if (item.quantity < item.stock_threshold) {
                lowStockCount++;
            }
            totalValue += item.quantity * (item.price || 0);
        });

        document.getElementById('lowStockItems').textContent = lowStockCount;
        document.getElementById('inventoryValue').textContent = formatCurrency(totalValue);

        // Load alerts
        await loadAlerts();

        // Load recent sales
        await loadRecentSales();

        // Load charts with requestAnimationFrame for smooth rendering
        requestAnimationFrame(() => loadCharts(products, inventory));

    } catch (error) {
        console.error('Error loading dashboard:', error);
        showAlert('Failed to load dashboard data', 'error');
    }
}

async function loadCharts(products, inventory) {
    try {
        // Inventory Distribution Chart
        const inventoryCtx = document.getElementById('inventoryChart')?.getContext('2d');
        if (inventoryCtx) {
            if (charts.inventory) charts.inventory.destroy();
            charts.inventory = new Chart(inventoryCtx, {
                type: 'doughnut',
                data: {
                    labels: inventory.slice(0, 5).map(item => item.name),
                    datasets: [{
                        data: inventory.slice(0, 5).map(item => item.quantity),
                        backgroundColor: [
                            'rgba(102, 126, 234, 0.8)',
                            'rgba(240, 147, 251, 0.8)',
                            'rgba(76, 175, 80, 0.8)',
                            'rgba(255, 193, 7, 0.8)',
                            'rgba(244, 67, 54, 0.8)'
                        ],
                        borderColor: [
                            'rgba(102, 126, 234, 1)',
                            'rgba(240, 147, 251, 1)',
                            'rgba(76, 175, 80, 1)',
                            'rgba(255, 193, 7, 1)',
                            'rgba(244, 67, 54, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    animation: { duration: 300 },
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }

        // Sales Trend Chart
        const salesCtx = document.getElementById('salesChart')?.getContext('2d');
        if (salesCtx) {
            if (charts.sales) charts.sales.destroy();
            charts.sales = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Sales (₱)',
                        data: [12000, 19000, 15000, 25000, 22000, 30000, 28000],
                        borderColor: 'rgba(102, 126, 234, 1)',
                        backgroundColor: 'rgba(102, 126, 234, 0.05)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                        pointBackgroundColor: 'rgba(240, 147, 251, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    animation: { duration: 300 },
                    plugins: { legend: { display: true } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { callback: function(value) { return '₱' + value.toLocaleString(); } }
                        }
                    }
                }
            });
        }

        // Category Distribution Chart
        const categoryCtx = document.getElementById('categoryChart')?.getContext('2d');
        if (categoryCtx) {
            if (charts.category) charts.category.destroy();
            const categories = {};
            products.forEach(p => {
                categories[p.category || 'Other'] = (categories[p.category || 'Other'] || 0) + 1;
            });
            charts.category = new Chart(categoryCtx, {
                type: 'bar',
                data: {
                    labels: Object.keys(categories),
                    datasets: [{
                        label: 'Products per Category',
                        data: Object.values(categories),
                        backgroundColor: 'rgba(102, 126, 234, 0.8)',
                        borderColor: 'rgba(102, 126, 234, 1)',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    animation: { duration: 300 },
                    indexAxis: 'y',
                    scales: { x: { beginAtZero: true } }
                }
            });
        }

        // Stock Status Chart
        const stockStatusCtx = document.getElementById('stockStatusChart')?.getContext('2d');
        if (stockStatusCtx) {
            if (charts.stockStatus) charts.stockStatus.destroy();
            let lowCount = 0, mediumCount = 0, highCount = 0;
            inventory.forEach(item => {
                if (item.quantity < item.stock_threshold) lowCount++;
                else if (item.quantity < item.stock_threshold * 1.5) mediumCount++;
                else highCount++;
            });
            charts.stockStatus = new Chart(stockStatusCtx, {
                type: 'pie',
                data: {
                    labels: ['Low Stock', 'Medium Stock', 'High Stock'],
                    datasets: [{
                        data: [lowCount, mediumCount, highCount],
                        backgroundColor: [
                            'rgba(244, 67, 54, 0.8)',
                            'rgba(255, 193, 7, 0.8)',
                            'rgba(76, 175, 80, 0.8)'
                        ],
                        borderColor: [
                            'rgba(244, 67, 54, 1)',
                            'rgba(255, 193, 7, 1)',
                            'rgba(76, 175, 80, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    animation: { duration: 300 },
                    plugins: { legend: { position: 'right' } }
                }
            });
        }
    } catch (error) {
        console.error('Error loading charts:', error);
    }
}

async function loadAlerts() {
    try {
        const response = await api.get('/alerts', true);
        const alerts = response.alerts || [];
        const alertsList = document.getElementById('alertsList');

        if (alerts.length === 0) {
            alertsList.innerHTML = '<p><i class="material-icons inline-icon">check_circle</i> No low stock items</p>';
            return;
        }

        alertsList.innerHTML = alerts.map(alert => `
            <div class="alert-item">
                <strong>${alert.name}</strong> (${alert.sku})<br>
                Current: ${alert.quantity} | Threshold: ${alert.stock_threshold} | Deficit: ${alert.deficit}
            </div>
        `).join('');

    } catch (error) {
        console.error('Error loading alerts:', error);
    }
}

async function loadRecentSales() {
    try {
        const response = await api.get('/sales', true);
        const sales = (response.data || []).slice(0, 10);
        const table = document.getElementById('recentSalesTable');

        if (sales.length === 0) {
            table.innerHTML = '<tr><td colspan="5">No sales recorded yet</td></tr>';
            return;
        }

        table.innerHTML = sales.map(sale => `
            <tr>
                <td>${sale.transaction_id}</td>
                <td>${sale.name || 'Unknown'}</td>
                <td>${sale.quantity}</td>
                <td>${formatCurrency(sale.total)}</td>
                <td>${formatDate(sale.sale_date)}</td>
            </tr>
        `).join('');

        // Calculate today's sales
        const today = new Date().toDateString();
        const todaysSales = sales.filter(s => new Date(s.sale_date).toDateString() === today);
        const salesToday = todaysSales.reduce((sum, s) => sum + (parseFloat(s.total) || 0), 0);
        document.getElementById('salesToday').textContent = formatCurrency(salesToday);

    } catch (error) {
        console.error('Error loading recent sales:', error);
    }
}

// Load dashboard on page load
document.addEventListener('DOMContentLoaded', loadDashboard);

// Use throttled refresh for better performance
const throttledRefresh = throttle(loadDashboard, 15000); // Max once every 15 seconds
setInterval(throttledRefresh, 30000);
