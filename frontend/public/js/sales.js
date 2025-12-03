// Sales page functionality
let lastSalesLoad = 0;

async function loadSales() {
    try {
        // Throttle loads
        const now = Date.now();
        if (now - lastSalesLoad < 5000) {
            return;
        }
        lastSalesLoad = now;

        const [salesRes, productsRes] = await Promise.all([
            api.get('/sales', true),
            api.get('/products', true)
        ]);

        const sales = salesRes.data || [];
        const products = productsRes.data || [];
        const table = document.getElementById('salesTable');

        if (sales.length === 0) {
            table.innerHTML = '<tr><td colspan="6" style="text-align: center;">No sales recorded yet</td></tr>';
            updateSalesStats([]);
        } else {
            table.innerHTML = sales.map(sale => `
                <tr>
                    <td>${sale.transaction_id}</td>
                    <td>${sale.items || 'Unknown'}</td>
                    <td>${sale.payment_method || 'N/A'}</td>
                    <td>${formatCurrency(sale.total_amount)}</td>
                    <td><span class="status-badge ${sale.status}">${sale.status || 'completed'}</span></td>
                    <td>${formatDate(sale.created_at)}</td>
                </tr>
            `).join('');

            updateSalesStats(sales);
        }

        // Populate product dropdown
        const select = document.getElementById('saleSku');
        select.innerHTML = '<option value="">Select Product...</option>' + 
            products.map(p => `<option value="${p.sku}">${p.name} (${p.sku})</option>`).join('');

    } catch (error) {
        console.error('Error loading sales:', error);
        showAlert('Failed to load sales', 'error');
    }
}

function updateSalesStats(sales) {
    // Total Sales Transactions
    const totalTransactions = sales.length;
    const totalEl = document.getElementById('totalSalesCount');
    if (totalEl) totalEl.textContent = totalTransactions;

    // Total Revenue
    const totalRevenue = sales.reduce((sum, sale) => sum + (parseFloat(sale.total_amount) || 0), 0);
    const revenueEl = document.getElementById('totalRevenueCount');
    if (revenueEl) revenueEl.textContent = formatCurrency(totalRevenue);

    // Average Order Value
    const avgOrderValue = sales.length > 0 ? totalRevenue / sales.length : 0;
    const avgEl = document.getElementById('avgOrderCount');
    if (avgEl) avgEl.textContent = formatCurrency(avgOrderValue);
}

function openNewSaleForm() {
    document.getElementById('newSaleModal').style.display = 'block';
}

function closeNewSaleForm() {
    document.getElementById('newSaleModal').style.display = 'none';
    document.getElementById('newSaleForm').reset();
}

async function submitNewSale(event) {
    event.preventDefault();

    const sku = document.getElementById('saleSku').value;
    const quantity = parseInt(document.getElementById('saleQuantity').value);

    if (!sku || quantity <= 0) {
        showAlert('Please select a product and enter a valid quantity', 'error');
        return;
    }

    try {
        const response = await api.post('/sales', { sku, quantity });
        
        if (response.success) {
            showAlert(`Sale recorded! Transaction ID: ${response.transaction_id}`, 'success');
            closeNewSaleForm();
            // Reload sales data
            lastSalesLoad = 0;
            loadSales();
        } else if (response.error) {
            // Handle various error types
            let errorMsg = response.error;
            if (response.error.includes('HTTP 503')) {
                errorMsg = 'Sale recorded but inventory service is temporarily unavailable. Please refresh to see the sale.';
            }
            showAlert('Failed to record sale: ' + errorMsg, 'error');
            // Still reload sales in case it was partially recorded
            lastSalesLoad = 0;
            loadSales();
        } else {
            showAlert('Failed to record sale: Unknown error', 'error');
        }
    } catch (error) {
        console.error('Error recording sale:', error);
        showAlert('Error recording sale: ' + error.message, 'error');
    }
}

// Load sales on page load
document.addEventListener('DOMContentLoaded', loadSales);

// Throttle refresh to 30 seconds
let salesRefreshInterval = setInterval(loadSales, 30000);

// Stop refreshing when user leaves the page
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        clearInterval(salesRefreshInterval);
    } else {
        salesRefreshInterval = setInterval(loadSales, 30000);
    }
});
