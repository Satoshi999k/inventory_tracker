// Inventory page functionality
let lastInventoryLoad = 0;
const INVENTORY_REFRESH_INTERVAL = 20000; // 20 seconds minimum

async function loadInventory() {
    try {
        // Check if enough time has passed since last load
        const now = Date.now();
        if (now - lastInventoryLoad < 5000) {
            return; // Skip if less than 5 seconds
        }
        lastInventoryLoad = now;

        const response = await api.get('/inventory', true);
        const inventory = response.data || [];
        const table = document.getElementById('inventoryTable');

        if (inventory.length === 0) {
            table.innerHTML = '<tr><td colspan="6" style="text-align: center;">No inventory items found</td></tr>';
            updateInventoryStats([]);
            return;
        }

        table.innerHTML = inventory.map(item => {
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

        updateInventoryStats(inventory);

        // Populate restock dropdown
        const select = document.getElementById('restockSku');
        select.innerHTML = '<option value="">Select Product...</option>' + 
            inventory.map(item => `<option value="${item.sku}">${item.name} (${item.sku})</option>`).join('');

    } catch (error) {
        console.error('Error loading inventory:', error);
        showAlert('Failed to load inventory', 'error');
    }
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
}

function closeRestockForm() {
    document.getElementById('restockModal').style.display = 'none';
    document.getElementById('restockForm').reset();
}

async function submitRestock(event) {
    event.preventDefault();

    const sku = document.getElementById('restockSku').value;
    const quantity = parseInt(document.getElementById('restockQuantity').value);

    try {
        const response = await api.post('/restock', { sku, quantity });
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
