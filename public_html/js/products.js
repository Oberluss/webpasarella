// ==========================================
// SISTEMA DE PRODUCTOS
// ==========================================

class ProductManager {
    constructor() {
        this.products = [];
        this.loadProducts();
    }

    // Cargar productos desde la API
    async loadProducts() {
        try {
            const response = await fetch('http://webpasarella.dnns.es:3001/api/products');
            const result = await response.json();
            
            if (result.success) {
                this.products = result.products;
                this.renderProducts();
            } else {
                this.showError('Error al cargar productos');
            }
        } catch (error) {
            console.error('Error loading products:', error);
            this.showError('Error de conexión al cargar productos');
        }
    }

    // Renderizar productos en el HTML
    renderProducts() {
        const container = document.getElementById('products-container');
        if (!container) return;

        if (this.products.length === 0) {
            container.innerHTML = `
                <div class="col-12 text-center">
                    <h4>No hay productos disponibles</h4>
                </div>
            `;
            return;
        }

        let html = '';
        this.products.forEach(product => {
            html += `
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card product-card h-100 shadow-sm" data-product-id="${product.id}">
                        <div class="position-relative">
                            <img src="${product.image}" alt="${product.name}" 
                                 class="card-img-top product-image" style="height: 250px; object-fit: cover;">
                            ${product.stock < 10 ? '<span class="badge bg-warning position-absolute top-0 end-0 m-2">Pocas unidades</span>' : ''}
                            ${product.stock === 0 ? '<span class="badge bg-danger position-absolute top-0 end-0 m-2">Agotado</span>' : ''}
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="mb-2">
                                <span class="badge bg-secondary">${product.category}</span>
                            </div>
                            <h5 class="card-title product-name">${product.name}</h5>
                            <p class="card-text product-description text-muted flex-grow-1">
                                ${product.description}
                            </p>
                            <div class="product-info mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="text-primary mb-0 product-price">€${parseFloat(product.price).toFixed(2)}</h4>
                                    <small class="text-muted">Stock: ${product.stock}</small>
                                </div>
                                <div class="d-grid">
                                    ${product.stock > 0 ? 
                                        `<button class="btn btn-primary add-to-cart">
                                            <i class="fas fa-shopping-cart me-2"></i>Agregar al Carrito
                                        </button>` :
                                        `<button class="btn btn-outline-secondary" disabled>
                                            <i class="fas fa-times me-2"></i>Sin Stock
                                        </button>`
                                    }
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    // Mostrar error
    showError(message) {
        const container = document.getElementById('products-container');
        if (container) {
            container.innerHTML = `
                <div class="col-12 text-center">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${message}
                    </div>
                    <button class="btn btn-outline-primary" onclick="productManager.loadProducts()">
                        <i class="fas fa-redo me-2"></i>Reintentar
                    </button>
                </div>
            `;
        }
    }

    // Obtener producto por ID
    getProductById(id) {
        return this.products.find(product => product.id === parseInt(id));
    }
}

// Event listener para botones "Agregar al Carrito"
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('add-to-cart') || e.target.closest('.add-to-cart')) {
        e.preventDefault();
        
        const button = e.target.classList.contains('add-to-cart') ? e.target : e.target.closest('.add-to-cart');
        const productCard = button.closest('.product-card');
        
        if (productCard && window.cart) {
            const productId = parseInt(productCard.dataset.productId);
            const productName = productCard.querySelector('.product-name').textContent;
            const priceText = productCard.querySelector('.product-price').textContent;
            const price = parseFloat(priceText.replace(/[€\s]/g, '').replace(',', '.'));
            const image = productCard.querySelector('.product-image').src;
            
            // Verificar stock
            const product = productManager.getProductById(productId);
            if (product && product.stock <= 0) {
                alert('Producto sin stock');
                return;
            }
            
            // Agregar al carrito
            cart.addItem(productId, productName, price, image, 1);
            
            // Feedback visual
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check me-2"></i>¡Agregado!';
            button.classList.add('btn-success');
            button.classList.remove('btn-primary');
            button.disabled = true;
            
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.classList.remove('btn-success');
                button.classList.add('btn-primary');
                button.disabled = false;
            }, 2000);
        }
    }
});

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.productManager = new ProductManager();
});