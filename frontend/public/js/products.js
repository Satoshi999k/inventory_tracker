// Products page functionality
let lastProductsLoad = 0;

async function loadProducts() {
    try {
        // Throttle loads
        const now = Date.now();
        if (now - lastProductsLoad < 5000) {
            return;
        }
        lastProductsLoad = now;

        const response = await api.get('/products', true);
        const products = response.data || [];
        const grid = document.getElementById('productsGrid');

        if (products.length === 0) {
            grid.innerHTML = '<div class="empty-state"><h3>No products found</h3></div>';
            updateProductStats([]);
            return;
        }

        grid.innerHTML = products.map(product => `
            <div class="product-card">
                <h4>${product.name}</h4>
                <p><strong>SKU:</strong> ${product.sku}</p>
                <p><strong>Category:</strong> ${product.category || 'N/A'}</p>
                <div class="product-price">${formatCurrency(product.price)}</div>
                <p>${product.description || 'No description'}</p>
                <p><small>Threshold: ${product.stock_threshold}</small></p>
                <button class="btn btn-danger" onclick="deleteProduct('${product.sku}', '${product.name.replace(/'/g, "\\'")}')">Delete</button>
            </div>
        `).join('');

        updateProductStats(products);

    } catch (error) {
        console.error('Error loading products:', error);
        showAlert('Failed to load products', 'error');
    }
}

function updateProductStats(products) {
    // Total Products
    const totalCount = products.length;
    const totalEl = document.getElementById('totalProductsCount');
    if (totalEl) totalEl.textContent = totalCount;

    // Unique Categories
    const categories = new Set(products.map(p => p.category).filter(Boolean));
    const categoriesEl = document.getElementById('totalCategoriesCount');
    if (categoriesEl) categoriesEl.textContent = categories.size;

    // Average Price
    const avgPrice = products.length > 0 
        ? products.reduce((sum, p) => sum + (parseFloat(p.price) || 0), 0) / products.length 
        : 0;
    const avgEl = document.getElementById('avgPriceCount');
    if (avgEl) avgEl.textContent = formatCurrency(avgPrice);
}

function openAddProductForm() {
    document.getElementById('addProductModal').style.display = 'block';
}

function closeAddProductForm() {
    document.getElementById('addProductModal').style.display = 'none';
    document.getElementById('addProductForm').reset();
}

async function submitAddProduct(event) {
    event.preventDefault();

    const product = {
        sku: document.getElementById('sku').value,
        name: document.getElementById('name').value,
        category: document.getElementById('category').value,
        price: parseFloat(document.getElementById('price').value),
        description: document.getElementById('description').value,
        stock_threshold: parseInt(document.getElementById('stock_threshold').value)
    };

    try {
        const response = await api.post('/products', product);
        if (response.success) {
            showAlert('Product added successfully', 'success');
            closeAddProductForm();
            loadProducts();
        } else {
            showAlert('Failed to add product: ' + (response.error || 'Unknown error'), 'error');
        }
    } catch (error) {
        console.error('Error adding product:', error);
        showAlert('Error adding product', 'error');
    }
}

async function deleteProduct(sku, productName = '') {
    // Create enhanced confirmation dialog
    const modalId = 'deleteConfirmModal_' + Date.now();
    const modal = document.createElement('div');
    modal.id = modalId;
    modal.className = 'delete-confirm-modal';
    modal.innerHTML = `
        <div class="delete-confirm-content">
            <div class="delete-confirm-header">
                <i class="material-icons delete-icon">warning</i>
                <h2>Delete Product?</h2>
            </div>
            <div class="delete-confirm-body">
                <p class="warning-text">Are you sure you want to delete this product?</p>
                <div class="product-info">
                    <p><strong>SKU:</strong> ${sku}</p>
                    ${productName ? `<p><strong>Name:</strong> ${productName}</p>` : ''}
                </div>
                <p class="deletion-warning"><i class="material-icons">info</i> This action cannot be undone. All related data will be permanently deleted.</p>
            </div>
            <div class="delete-confirm-footer">
                <button class="btn btn-secondary" onclick="closeDeleteConfirm('${modalId}')"><i class="material-icons">close</i> Cancel</button>
                <button class="btn btn-danger" onclick="confirmDeleteProduct('${sku}', '${modalId}')">
                    <i class="material-icons">delete</i> Delete Product
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    modal.setAttribute('data-sku', sku);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeDeleteConfirm(modalId);
    });
}

function closeDeleteConfirm(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.animation = 'fadeOut 0.3s ease-out';
        setTimeout(() => modal.remove(), 300);
    }
}

async function confirmDeleteProduct(sku, modalId) {
    closeDeleteConfirm(modalId);
    
    try {
        const response = await api.delete('/products', { sku });
        if (response.success) {
            // Remove product card from DOM immediately (AJAX)
            const grid = document.getElementById('productsGrid');
            const productCards = grid.querySelectorAll('.product-card');
            let productRemoved = false;
            
            productCards.forEach(card => {
                const skuElement = card.querySelector('p strong');
                if (skuElement && skuElement.textContent === 'SKU:' && 
                    skuElement.nextSibling.textContent.trim() === sku) {
                    card.style.animation = 'fadeOut 0.3s ease-out forwards';
                    setTimeout(() => card.remove(), 300);
                    productRemoved = true;
                }
            });
            
            showAlert('Product deleted successfully', 'success');
            
            // Update stats after removal
            setTimeout(() => {
                const remainingCards = grid.querySelectorAll('.product-card');
                if (remainingCards.length === 0) {
                    grid.innerHTML = '<div class="empty-state"><h3>No products found</h3></div>';
                } else {
                    // Get all remaining products data and update stats
                    const products = Array.from(remainingCards).map(card => ({
                        sku: card.querySelector('p').nextSibling.textContent.trim()
                    }));
                    updateProductStats(products);
                }
            }, 300);
        } else {
            showAlert('Failed to delete product', 'error');
        }
    } catch (error) {
        console.error('Error deleting product:', error);
        showAlert('Error deleting product', 'error');
    }
}

// Load products on page load
document.addEventListener('DOMContentLoaded', loadProducts);
