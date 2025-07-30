// Sistema de productos seguro
console.log('Products-safe.js cargado');

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, inicializando productos...');
    loadProductsFromAPI();
});

async function loadProductsFromAPI() {
    try {
        console.log('Cargando productos desde API...');
        const response = await fetch('http://webpasarella.dnns.es:3001/api/products');
        const result = await response.json();
        
        console.log('Respuesta de API:', result);
        
        if (result.success && result.products) {
            window.availableProducts = result.products;
            displayProducts(result.products);
        } else {
            console.error('Error en respuesta de API');
            showTestProducts();
        }
    } catch (error) {
        console.error('Error cargando productos:', error);
        showTestProducts();
    }
}

function displayProducts(products) {
    const container = document.getElementById('products-container');
    if (!container) return;
    
    if (products.length === 0) {
        container.innerHTML = '<div class="col-12 text-center"><h4>No hay productos disponibles</h4></div>';
        return;
    }
    
    let html = '';
    products.forEach(product => {
        html += `
            <div class="col-md-4 mb-4">
                <div class="card product-card h-100" data-product-id="${product.id}">
                    <img src="${product.image}" alt="${product.name}" 
                         class="card-img-top product-image" style="height: 250px; object-fit: cover;">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title product-name">${product.name}</h5>
                        <p class="card-text product-description">${product.description}</p>
                        <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="text-primary product-price">€${parseFloat(product.price).toFixed(2)}</h4>
                                <small class="text-muted">Stock: ${product.stock}</small>
                            </div>
                            <button class="btn btn-primary w-100" onclick="addToCart(${product.id})">
                                <i class="fas fa-shopping-cart me-2"></i>Agregar al Carrito
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function showTestProducts() {
    console.log('Mostrando productos de prueba...');
    const testProducts = [
        {
            id: 1,
            name: 'Laptop Gaming',
            description: 'Potente laptop para gaming y trabajo',
            price: 899.99,
            image: 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=400',
            stock: 10
        },
        {
            id: 2,
            name: 'Smartphone Pro',
            description: 'Smartphone de última generación',
            price: 699.99,
            image: 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400',
            stock: 25
        },
        {
            id: 3,
            name: 'Tablet Ultra',
            description: 'Tablet de alta resolución',
            price: 499.99,
            image: 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=400',
            stock: 15
        }
    ];
    
    window.availableProducts = testProducts;
    displayProducts(testProducts);
}

function addToCart(productId) {
    console.log('Agregando al carrito:', productId);
    
    if (!window.availableProducts) {
        alert('Productos no disponibles');
        return;
    }
    
    const product = window.availableProducts.find(p => p.id == productId);
    if (!product) {
        alert('Producto no encontrado');
        return;
    }
    
    if (window.cart) {
        cart.addItem(productId, product.name, product.price, product.image, 1);
        console.log('Producto agregado al carrito');
    } else {
        console.error('Sistema de carrito no disponible');
        alert('Sistema de carrito no disponible');
    }
}