// Products page functionality with pagination
let lastProductsLoad = 0;
const PRODUCTS_PER_PAGE = 12;
let currentProductsPage = 1;
let allProducts = [];

// Helper function to convert file to Base64 with compression
async function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        // Limit image size to 500KB max, compress if needed
        const maxSize = 500 * 1024; // 500KB
        
        if (file.size > maxSize) {
            // Compress image on client side
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    let { width, height } = img;
                    
                    // Scale down if too large
                    const maxDim = 800;
                    if (width > maxDim || height > maxDim) {
                        const ratio = Math.min(maxDim / width, maxDim / height);
                        width *= ratio;
                        height *= ratio;
                    }
                    
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);
                    
                    // Use JPEG format with quality reduction for compression
                    resolve(canvas.toDataURL('image/jpeg', 0.7));
                };
                img.onerror = () => reject(new Error('Failed to compress image'));
                img.src = e.target.result;
            };
            reader.onerror = error => reject(error);
            reader.readAsDataURL(file);
        } else {
            // File is small enough, use as is
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = error => reject(error);
            reader.readAsDataURL(file);
        }
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
                <div class="product-actions">
                    <button class="btn btn-warning edit-btn" data-product-id="${product.id}" title="Edit"><i class="material-icons">edit</i></button>
                    <button class="btn btn-danger" onclick="deleteProduct('${product.sku}', '${product.name.replace(/'/g, "\\'")}')">Delete</button>
                </div>
            </div>
        </div>
    `}).join('');

    updateProductsPagination();
    
    // Add click listeners to edit buttons
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const productId = btn.getAttribute('data-product-id');
            const product = allProducts.find(p => p.id == productId);
            if (product) {
                openEditProductForm(product);
            }
        });
    });
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
    document.getElementById('editProductModal').style.display = 'none';
    document.getElementById('addProductModal').style.display = 'block';
    document.getElementById('addProductForm').reset();
    // Reset image preview when opening form
    resetImagePreview();
}

function closeAddProductForm() {
    document.getElementById('addProductModal').style.display = 'none';
    document.getElementById('addProductForm').reset();
    resetImagePreview();
}

function openEditProductForm(product) {
    // Populate edit form with product data
    document.getElementById('editSku').value = product.sku;
    document.getElementById('editName').value = product.name;
    document.getElementById('editCategory').value = product.category || '';
    document.getElementById('editPrice').value = product.price;
    document.getElementById('editCost').value = product.cost || '';
    document.getElementById('editDescription').value = product.description || '';
    document.getElementById('editStockThreshold').value = product.stock_threshold || 10;
    
    // Show current image if exists
    const editImagePreview = document.getElementById('editImagePreview');
    const editPreviewImg = document.getElementById('editPreviewImg');
    if (product.image_url && editImagePreview && editPreviewImg) {
        editPreviewImg.src = product.image_url;
        editImagePreview.style.display = 'block';
    }
    
    // Show edit modal
    document.getElementById('editProductModal').style.display = 'block';
}

function closeEditProductForm() {
    document.getElementById('editProductModal').style.display = 'none';
    document.getElementById('editProductForm').reset();
    resetEditImagePreview();
}

function resetEditImagePreview() {
    const preview = document.getElementById('editImagePreview');
    const previewImg = document.getElementById('editPreviewImg');
    if (preview) {
        preview.style.display = 'none';
        if (previewImg) previewImg.src = '';
    }
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

    // Disable form and show loading state
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="material-icons btn-icon">hourglass_empty</i> Adding...';
    
    // Disable all form inputs
    const formInputs = event.target.querySelectorAll('input, textarea, button');
    const wasDisabled = [];
    formInputs.forEach(input => {
        wasDisabled.push(input.disabled);
        input.disabled = true;
    });

    let imageData = null;
    const imageFile = document.getElementById('image_file').files[0];
    
    try {
        // Convert image file to Base64 if selected (with compression)
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

        const response = await api.post('/products', product);
        if (response.success) {
            showAlert('Product added successfully', 'success');
            closeAddProductForm();
            
            // Optimized: Add product to array instead of reloading all
            const newProduct = response.data || {
                ...product,
                id: response.id,
                created_at: new Date().toISOString()
            };
            allProducts.unshift(newProduct);
            currentProductsPage = 1;
            renderProductsPage();
            updateProductStats(allProducts);
        } else {
            showAlert('Failed to add product: ' + (response.error || 'Unknown error'), 'error');
        }
    } catch (error) {
        console.error('Error adding product:', error);
        showAlert('Error adding product', 'error');
    } finally {
        // Re-enable form
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
        formInputs.forEach((input, index) => {
            input.disabled = wasDisabled[index];
        });
    }
}

async function submitEditProduct(event) {
    event.preventDefault();

    // Disable form and show loading state
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="material-icons btn-icon">hourglass_empty</i> Updating...';
    
    // Disable all form inputs
    const formInputs = event.target.querySelectorAll('input, textarea, button');
    const wasDisabled = [];
    formInputs.forEach(input => {
        wasDisabled.push(input.disabled);
        input.disabled = true;
    });

    let imageData = null;
    const imageFile = document.getElementById('editImageFile').files[0];
    
    try {
        // Convert image file to Base64 if selected (with compression)
        if (imageFile) {
            imageData = await fileToBase64(imageFile);
        }

        const product = {
            sku: document.getElementById('editSku').value,
            name: document.getElementById('editName').value,
            category: document.getElementById('editCategory').value,
            price: parseFloat(document.getElementById('editPrice').value),
            cost: parseFloat(document.getElementById('editCost').value) || null,
            description: document.getElementById('editDescription').value,
            image_url: imageData || null,
            stock_threshold: parseInt(document.getElementById('editStockThreshold').value)
        };

        const response = await api.put('/products', product);
        if (response.success) {
            showAlert('Product updated successfully', 'success');
            closeEditProductForm();
            
            // Update product in array
            const productIndex = allProducts.findIndex(p => p.sku === product.sku);
            if (productIndex !== -1) {
                allProducts[productIndex] = { ...allProducts[productIndex], ...product };
            }
            renderProductsPage();
            updateProductStats(allProducts);
        } else {
            showAlert('Failed to update product: ' + (response.error || 'Unknown error'), 'error');
        }
    } catch (error) {
        console.error('Error updating product:', error);
        showAlert('Error updating product', 'error');
    } finally {
        // Re-enable form
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
        formInputs.forEach((input, index) => {
            input.disabled = wasDisabled[index];
        });
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

// Add image preview event listener with debouncing
document.addEventListener('DOMContentLoaded', function() {
    const imageFileInput = document.getElementById('image_file');
    if (imageFileInput) {
        imageFileInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            
            if (file && preview && previewImg) {
                // Check file size
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (file.size > maxSize) {
                    showAlert('Image file is too large (max 5MB). It will be automatically compressed during upload.', 'warning');
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Show preview with compressed size estimate
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                    
                    // Show file info
                    const fileSize = (file.size / 1024).toFixed(2);
                    let infoText = `${file.name} (${fileSize} KB)`;
                    if (file.size > 500 * 1024) {
                        infoText += ' - Will be compressed to reduce upload time';
                    }
                    const infoEl = preview.querySelector('small') || document.createElement('small');
                    infoEl.textContent = infoText;
                    infoEl.style.display = 'block';
                    infoEl.style.marginTop = '8px';
                    infoEl.style.color = '#666';
                    if (!preview.querySelector('small')) {
                        preview.appendChild(infoEl);
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Add edit image preview listener
    const editImageFileInput = document.getElementById('editImageFile');
    if (editImageFileInput) {
        editImageFileInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('editImagePreview');
            const previewImg = document.getElementById('editPreviewImg');
            
            if (file && preview && previewImg) {
                // Check file size
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (file.size > maxSize) {
                    showAlert('Image file is too large (max 5MB). It will be automatically compressed during upload.', 'warning');
                }
                
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
    
    // Add click-outside close for modals
    const addProductModal = document.getElementById('addProductModal');
    if (addProductModal) {
        addProductModal.addEventListener('click', (e) => {
            if (e.target === addProductModal) {
                closeAddProductForm();
            }
        });
    }
    
    const editProductModal = document.getElementById('editProductModal');
    if (editProductModal) {
        editProductModal.addEventListener('click', (e) => {
            if (e.target === editProductModal) {
                closeEditProductForm();
            }
        });
    }
});

function prevProductsPage() {
    if (currentProductsPage > 1) {
        currentProductsPage--;
        renderProductsPage();
        document.querySelector('#productsGrid').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}