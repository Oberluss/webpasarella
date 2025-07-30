// Products functionality
function loadProducts() {
    fetch('api-proxy.php?path=products')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.products) {
                displayProducts(data.products);
            }
        })
        .catch(error => {
            console.error('Error loading products:', error);
            showToast('Error al cargar productos', 'danger');
        });
}

function displayProducts(products) {
    const productList = document.getElementById('productList');
    if (!productList) return;
    
    let html = '';
    
    products.forEach(product => {
        const price = parseFloat(product.price).toFixed(2);
        const inStock = product.stock > 0;
        
        html += `
            <div class="col-md-6 col-lg-4">
                <div class="card product-card h-100">
                    <img src="${product.image || 'https://via.placeholder.com/300x200'}" 
                         class="card-img-top product-image" 
                         alt="${product.name}"
                         onerror="this.src='https://via.placeholder.com/300x200?text=Sin+Imagen'">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">${product.name}</h5>
                        <p class="card-text flex-grow-1">${product.description || ''}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="h5 mb-0 text-primary">€${price}</span>
                            <span class="badge ${inStock ? 'bg-success' : 'bg-danger'}">
                                ${inStock ? `${product.stock} en stock` : 'Agotado'}
                            </span>
                        </div>
                        <div class="mt-3 d-grid gap-2">
                            ${inStock ? `
                                <button class="btn btn-primary btn-add-cart" 
                                    onclick="addToCart(${product.id}, '${product.name.replace(/'/g, "\\'")}', ${product.price}, '${product.image}')">
                                    <i class="bi bi-cart-plus me-2"></i>Añadir al Carrito
                                </button>
                                <button class="btn btn-outline-primary" 
                                    onclick="quickBuy(${product.id}, '${product.name.replace(/'/g, "\\'")}', ${product.price}, '${product.image}')">
                                    <i class="bi bi-lightning-fill me-2"></i>Compra Rápida
                                </button>
                            ` : `
                                <button class="btn btn-secondary" disabled>
                                    <i class="bi bi-x-circle me-2"></i>No Disponible
                                </button>
                            `}
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    productList.innerHTML = html;
}

// Search products
function searchProducts(query) {
    fetch('api-proxy.php?path=products')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.products) {
                const filtered = data.products.filter(product => 
                    product.name.toLowerCase().includes(query.toLowerCase()) ||
                    (product.description && product.description.toLowerCase().includes(query.toLowerCase()))
                );
                displayProducts(filtered);
            }
        });
}

// Filter products by category
function filterByCategory(category) {
    fetch('api-proxy.php?path=products')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.products) {
                const filtered = category === 'all' 
                    ? data.products 
                    : data.products.filter(product => product.category === category);
                displayProducts(filtered);
            }
        });
}

// Sort products
function sortProducts(sortBy) {
    fetch('api-proxy.php?path=products')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.products) {
                let sorted = [...data.products];
                
                switch(sortBy) {
                    case 'price-asc':
                        sorted.sort((a, b) => parseFloat(a.price) - parseFloat(b.price));
                        break;
                    case 'price-desc':
                        sorted.sort((a, b) => parseFloat(b.price) - parseFloat(a.price));
                        break;
                    case 'name':
                        sorted.sort((a, b) => a.name.localeCompare(b.name));
                        break;
                    case 'stock':
                        sorted.sort((a, b) => b.stock - a.stock);
                        break;
                }
                
                displayProducts(sorted);
            }
        });
}