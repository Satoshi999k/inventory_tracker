// Products page functionality with pagination
let lastProductsLoad = 0;
const PRODUCTS_PER_PAGE = 12;
let currentProductsPage = 1;
let allProducts = [];

// Helper function to convert file to Base64
function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = error => reject(error);
        reader.readAsDataURL(file);
    });
}

async function loadProducts() {
    try {
        // Throttle loads
        const now = Date.now();
        if (now - lastProductsLoad < 5000) {
            return;
        }
        lastProductsLoad = now;

        const response = await api.get('/products', true);
        allProducts = response.data || [];
        const grid = document.getElementById('productsGrid');

        if (allProducts.length === 0) {
            grid.innerHTML = '<div class="empty-state"><h3>No products found</h3></div>';
            updateProductStats([]);
            return;
        }

        currentProductsPage = 1;
        renderProductsPage();
        updateProductStats(allProducts);

    } catch (error) {
        console.error('Error loading products:', error);
        showAlert('Failed to load products', 'error');
    }
}

function renderProductsPage() {
    const grid = document.getElementById('productsGrid');
    const start = (currentProductsPage - 1) * PRODUCTS_PER_PAGE;
    const end = start + PRODUCTS_PER_PAGE;
    const pageProducts = allProducts.slice(start, end);

    grid.innerHTML = pageProducts.map(product => {
        const imageHtml = product.image_url 
            ? `<img src="${product.image_url}" alt="${product.name}" class="product-image" onerror="this.src='/images/placeholder.png'">`
            : `<div class="product-image-placeholder"><i class="material-icons">image</i> No Image</div>`;
        
        return `
        <div class="product-card">
            ${imageHtml}
            <div class="product-info">
                <h4>${product.name}</h4>
                <p><strong>SKU:</strong> ${product.sku}</p>
                <p><strong>Category:</strong> ${product.category || 'N/A'}</p>
                <div class="product-price">${formatCurrency(product.price)}</div>
                <p>${product.description || 'No description'}</p>
                <p><small>Threshold: ${product.stock_threshold}</small></p>
                <button class="btn btn-danger" onclick="deleteProduct('${product.sku}', '${product.name.replace(/'/g, "\\'")}')">Delete</button>
            </div>
        </div>
    `}).join('');

    updateProductsPagination();
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
    // Reset image preview when opening form
    resetImagePreview();
}

function closeAddProductForm() {
    document.getElementById('addProductModal').style.display = 'none';
    document.getElementById('addProductForm').reset();
    resetImagePreview();
}

function resetImagePreview() {
    const preview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    if (preview) {
        preview.style.display = 'none';
        if (previewImg) previewImg.src = '';
    }
}

async function submitAddProduct(event) {
    event.preventDefault();

    let imageData = null;
    const imageFile = document.getElementById('image_file').files[0];
    
    // Convert image file to Base64 if selected
    if (imageFile) {
        imageData = await fileToBase64(imageFile);
    }

    const product = {
        sku: document.getElementById('sku').value,
        name: document.getElementById('name').value,
        category: document.getElementById('category').value,
        price: parseFloat(document.getElementById('price').value),
        cost: parseFloat(document.getElementById('cost').value) || null,
        description: document.getElementById('description').value,
        image_url: imageData || null,
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
            // Remove product from allProducts array
            allProducts = allProducts.filter(p => p.sku !== sku);
            
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
            
            // Update stats after removal using the updated allProducts array
            setTimeout(() => {
                const remainingCards = grid.querySelectorAll('.product-card');
                if (remainingCards.length === 0) {
                    grid.innerHTML = '<div class="empty-state"><h3>No products found</h3></div>';
                    updateProductStats([]);
                } else {
                    // Use allProducts array which has all the data
                    updateProductStats(allProducts);
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

function updateProductsPagination() {
    const totalPages = Math.ceil(allProducts.length / PRODUCTS_PER_PAGE);
    const paginationEl = document.getElementById('productsPagination');
    
    if (!paginationEl) return;
    
    if (totalPages <= 1) {
        paginationEl.innerHTML = '';
        return;
    }

    let html = `<div style="text-align: center; margin-top: 20px; padding: 20px;">`;
    
    if (currentProductsPage > 1) {
        html += `<button class="btn btn-secondary" onclick="prevProductsPage()" style="margin-right: 10px;">← Previous</button>`;
    }
    
    html += `<span style="margin: 0 10px; color: #666;">Page ${currentProductsPage} of ${totalPages}</span>`;
    
    if (currentProductsPage < totalPages) {
        html += `<button class="btn btn-secondary" onclick="nextProductsPage()" style="margin-left: 10px;">Next →</button>`;
    }
    
    html += `</div>`;
    paginationEl.innerHTML = html;
}

function nextProductsPage() {
    const totalPages = Math.ceil(allProducts.length / PRODUCTS_PER_PAGE);
    if (currentProductsPage < totalPages) {
        currentProductsPage++;
        renderProductsPage();
        document.querySelector('#productsGrid').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Add image preview event listener
document.addEventListener('DOMContentLoaded', function() {
    const imageFileInput = document.getElementById('image_file');
    if (imageFileInput) {
        imageFileInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            
            if (file && preview && previewImg) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Load products on page load
    loadProducts();
});

function prevProductsPage() {
    if (currentProductsPage > 1) {
        currentProductsPage--;
        renderProductsPage();
        document.querySelector('#productsGrid').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Load products on page load
document.addEventListener('DOMContentLoaded', loadProducts);

