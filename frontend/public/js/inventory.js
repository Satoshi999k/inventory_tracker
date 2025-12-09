// Inventory page functionality with pagination
let lastInventoryLoad = 0;
const INVENTORY_REFRESH_INTERVAL = 20000; // 20 seconds minimum
const ITEMS_PER_PAGE = 15;
let currentInventoryPage = 1;
let allInventoryItems = [];

async function loadInventory() {
    try {
        // Check if enough time has passed since last load
        const now = Date.now();
        if (now - lastInventoryLoad < 5000) {
            return;
        }
        lastInventoryLoad = now;

        const response = await api.get('/inventory', true);
        allInventoryItems = response.data || [];
        
        if (allInventoryItems.length === 0) {
            const table = document.getElementById('inventoryTable');
            table.innerHTML = '<tr><td colspan="6" style="text-align: center;">No inventory items found</td></tr>';
            updateInventoryStats([]);
            return;
        }

        currentInventoryPage = 1;
        renderInventoryPage();
        updateInventoryStats(allInventoryItems);

        // Populate restock dropdown
        const select = document.getElementById('restockSku');
        select.innerHTML = '<option value="">Select Product...</option>' + 
            allInventoryItems.map(item => `<option value="${item.sku}">${item.name} (${item.sku})</option>`).join('');

    } catch (error) {
        console.error('Error loading inventory:', error);
        showAlert('Failed to load inventory', 'error');
    }
}

function renderInventoryPage() {
    const table = document.getElementById('inventoryTable');
    const start = (currentInventoryPage - 1) * ITEMS_PER_PAGE;
    const end = start + ITEMS_PER_PAGE;
    const pageItems = allInventoryItems.slice(start, end);

    const html = pageItems.map(item => {
        let status = '';
        if (item.quantity < item.stock_threshold) {
            status = `<span class="status-low"><i class="material-icons">warning</i>LOW</span>`;
        } else if (item.quantity < item.stock_threshold * 1.5) {
            status = `<span class="status-medium"><i class="material-icons">info</i>MEDIUM</span>`;
        } else {
            status = `<span class="status-high"><i class="material-icons">check_circle</i>OK</span>`;
        }

        return `
            <tr>
                <td>${item.sku}</td>
                <td>${item.name || 'N/A'}</td>
                <td>${item.quantity}</td>
                <td>${item.stock_threshold}</td>
                <td>${status}</td>
                <td>
                    <button class="btn btn-primary" onclick="editStock('${item.sku}')">Adjust</button>
                </td>
            </tr>
        `;
    }).join('');
    
    table.innerHTML = html;
    updateInventoryPagination();
}

function updateInventoryStats(inventory) {
    // Total Items (Sum of all quantities)
    const totalItems = inventory.reduce((sum, item) => sum + (item.quantity || 0), 0);
    const totalEl = document.getElementById('totalStockCount');
    if (totalEl) totalEl.textContent = totalItems;

    // Low Stock Count
    const lowStock = inventory.filter(item => item.quantity < item.stock_threshold).length;
    const lowEl = document.getElementById('lowStockCount');
    if (lowEl) lowEl.textContent = lowStock;

    // Medium Stock Count (between threshold and 1.5x threshold)
    const mediumStock = inventory.filter(item => 
        item.quantity >= item.stock_threshold && 
        item.quantity < item.stock_threshold * 1.5
    ).length;
    const mediumEl = document.getElementById('mediumStockCount');
    if (mediumEl) mediumEl.textContent = mediumStock;

    // Good Stock Count (OK Status)
    const goodStock = inventory.filter(item => item.quantity >= item.stock_threshold * 1.5).length;
    const goodEl = document.getElementById('goodStockCount');
    if (goodEl) goodEl.textContent = goodStock;
}

function openRestockForm() {
    document.getElementById('restockModal').style.display = 'block';
    // Add event listeners for auto-calculation
    document.getElementById('restockQuantity').addEventListener('input', calculateTotalCost);
    document.getElementById('restockCostPerUnit').addEventListener('input', calculateTotalCost);
}

function closeRestockForm() {
    document.getElementById('restockModal').style.display = 'none';
    document.getElementById('restockForm').reset();
}

function calculateTotalCost() {
    const quantity = parseFloat(document.getElementById('restockQuantity').value) || 0;
    const costPerUnit = parseFloat(document.getElementById('restockCostPerUnit').value) || 0;
    const totalCost = quantity * costPerUnit;
    
    // Only update if both values are present
    if (quantity > 0 && costPerUnit > 0) {
        document.getElementById('restockTotalCost').value = totalCost.toFixed(2);
    } else {
        document.getElementById('restockTotalCost').value = '';
    }
}

async function submitRestock(event) {
    event.preventDefault();

    const sku = document.getElementById('restockSku').value;
    const quantity = parseInt(document.getElementById('restockQuantity').value);
    const supplier = document.getElementById('restockSupplier').value || null;
    const costPerUnit = parseFloat(document.getElementById('restockCostPerUnit').value) || null;
    const totalCost = parseFloat(document.getElementById('restockTotalCost').value) || null;

    try {
        const response = await api.post('/restock', { 
            sku, 
            quantity,
            supplier,
            cost_per_unit: costPerUnit,
            total_cost: totalCost
        });
        if (response.success) {
            showAlert('Inventory restocked successfully', 'success');
            closeRestockForm();
            loadInventory();
        } else {
            showAlert('Failed to restock: ' + (response.error || 'Unknown error'), 'error');
        }
    } catch (error) {
        console.error('Error restocking:', error);
        showAlert('Error restocking inventory', 'error');
    }
}

function editStock(sku) {
    // Find the product name
    const rows = document.querySelectorAll('#inventoryTable tr');
    let productName = sku;
    rows.forEach(row => {
        if (row.cells[0]?.textContent === sku) {
            productName = row.cells[1]?.textContent || sku;
        }
    });

    // Create and show modern modal
    const modal = document.createElement('div');
    modal.id = 'adjustStockModal';
    modal.className = 'modal';
    modal.style.display = 'block';
    
    const closeModal = () => {
        if (modal && modal.parentNode) {
            modal.remove();
        }
    };
    
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="material-icons modal-header-icon">edit_note</i> Adjust Stock</h3>
                <span class="modal-close" style="cursor: pointer;">&times;</span>
            </div>
            <div class="modal-body">
                <p style="color: #666; margin-bottom: 20px;"><strong>Product:</strong> ${productName} (${sku})</p>
                <div class="form-group">
                    <label for="adjustQuantityInput">Quantity to Adjust:</label>
                    <input type="number" id="adjustQuantityInput" placeholder="Enter quantity (use negative to reduce)" class="form-input" autofocus>
                    <small style="color: #999; display: block; margin-top: 8px;">
                        Positive numbers increase stock, negative numbers reduce it
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button">Cancel</button>
                <button class="btn btn-primary" type="button">Adjust Stock</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Add event listeners
    const closeBtn = modal.querySelector('.modal-close');
    const cancelBtn = modal.querySelectorAll('.btn-secondary')[0];
    const submitBtn = modal.querySelectorAll('.btn-primary')[0];
    
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    submitBtn.addEventListener('click', async () => {
        const input = modal.querySelector('#adjustQuantityInput');
        const quantity = parseInt(input.value);
        
        if (isNaN(quantity)) {
            showAlert('Please enter a valid number', 'error');
            return;
        }
        
        if (quantity === 0) {
            showAlert('Please enter a quantity (cannot be 0)', 'error');
            return;
        }
        
        closeModal();
        await submitRestockQuantity(sku, quantity);
    });
    
    // Close modal when clicking outside
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });
    
    // Focus on input
    setTimeout(() => {
        const input = modal.querySelector('#adjustQuantityInput');
        if (input) input.focus();
    }, 100);
}

async function submitRestockQuantity(sku, quantity) {
    try {
        const response = await api.post('/restock', { sku, quantity });
        if (response.success) {
            showAlert('Stock adjusted successfully', 'success');
            loadInventory();
        } else {
            showAlert('Failed to adjust stock', 'error');
        }
    } catch (error) {
        console.error('Error adjusting stock:', error);
        showAlert('Error adjusting stock', 'error');
    }
}

function updateInventoryPagination() {
    const totalPages = Math.ceil(allInventoryItems.length / ITEMS_PER_PAGE);
    const paginationEl = document.getElementById('inventoryPagination');
    
    if (!paginationEl) return;
    
    if (totalPages <= 1) {
        paginationEl.innerHTML = '';
        return;
    }

    let html = `<div style="text-align: center; margin-top: 20px; padding: 20px;">`;
    
    if (currentInventoryPage > 1) {
        html += `<button class="btn btn-secondary" onclick="prevInventoryPage()" style="margin-right: 10px;">← Previous</button>`;
    }
    
    html += `<span style="margin: 0 10px; color: #666;">Page ${currentInventoryPage} of ${totalPages}</span>`;
    
    if (currentInventoryPage < totalPages) {
        html += `<button class="btn btn-secondary" onclick="nextInventoryPage()" style="margin-left: 10px;">Next →</button>`;
    }
    
    html += `</div>`;
    paginationEl.innerHTML = html;
}

function nextInventoryPage() {
    const totalPages = Math.ceil(allInventoryItems.length / ITEMS_PER_PAGE);
    if (currentInventoryPage < totalPages) {
        currentInventoryPage++;
        renderInventoryPage();
        document.querySelector('#inventoryTable').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function prevInventoryPage() {
    if (currentInventoryPage > 1) {
        currentInventoryPage--;
        renderInventoryPage();
        document.querySelector('#inventoryTable').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}
// Load inventory on page load
document.addEventListener('DOMContentLoaded', loadInventory);

// Throttle refresh to 20 seconds minimum
let inventoryRefreshInterval = setInterval(loadInventory, 30000);

// Stop refreshing when user leaves the page
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        clearInterval(inventoryRefreshInterval);
    } else {
        inventoryRefreshInterval = setInterval(loadInventory, 30000);
    }
});
