// Cart functionality
let cart = [];

// Initialize cart from localStorage
function initCart() {
    const savedCart = localStorage.getItem('cart');
    if (savedCart) {
        cart = JSON.parse(savedCart);
    }
    updateCartUI();
}

// Add item to cart
function addToCart(productId, productName, productPrice, productImage) {
    // Check if item already exists in cart
    const existingItem = cart.find(item => item.id === productId);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            id: productId,
            name: productName,
            price: parseFloat(productPrice),
            image: productImage,
            quantity: 1
        });
    }
    
    saveCart();
    updateCartUI();
    showCartNotification(productName);
}

// Remove item from cart
function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    saveCart();
    updateCartUI();
}

// Update item quantity
function updateQuantity(productId, quantity) {
    const item = cart.find(item => item.id === productId);
    if (item) {
        item.quantity = parseInt(quantity);
        if (item.quantity <= 0) {
            removeFromCart(productId);
        } else {
            saveCart();
            updateCartUI();
        }
    }
}

// Clear entire cart
function clearCart() {
    if (confirm('¿Estás seguro de que quieres vaciar el carrito?')) {
        cart = [];
        saveCart();
        updateCartUI();
    }
}

// Save cart to localStorage
function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

// Update cart UI
function updateCartUI() {
    // Update cart count in navbar
    const cartCount = document.getElementById('cartCount');
    if (cartCount) {
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        cartCount.textContent = totalItems;
        cartCount.style.display = totalItems > 0 ? 'inline' : 'none';
    }
    
    // Update cart dropdown
    const cartItems = document.getElementById('cartItems');
    const cartTotal = document.getElementById('cartTotal');
    const emptyCartMessage = document.getElementById('emptyCartMessage');
    const cartFooter = document.querySelector('.dropdown-menu .dropdown-divider')?.parentElement;
    
    if (cartItems) {
        if (cart.length === 0) {
            cartItems.innerHTML = '';
            if (emptyCartMessage) emptyCartMessage.style.display = 'block';
            if (cartFooter) cartFooter.style.display = 'none';
        } else {
            if (emptyCartMessage) emptyCartMessage.style.display = 'none';
            if (cartFooter) cartFooter.style.display = 'block';
            
            let total = 0;
            let cartHTML = '';
            
            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                
                cartHTML += `
                    <div class="dropdown-item">
                        <div class="d-flex align-items-center">
                            <img src="${item.image}" alt="${item.name}" 
                                 style="width: 50px; height: 50px; object-fit: cover; margin-right: 10px;">
                            <div class="flex-grow-1">
                                <h6 class="mb-0">${item.name}</h6>
                                <small class="text-muted">
                                    €${item.price.toFixed(2)} x ${item.quantity} = €${itemTotal.toFixed(2)}
                                </small>
                            </div>
                            <button class="btn btn-sm btn-danger ms-2" 
                                    onclick="removeFromCart(${item.id})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            cartItems.innerHTML = cartHTML;
            if (cartTotal) cartTotal.textContent = `€${total.toFixed(2)}`;
        }
    }
    
    // Update cart page if we're on it
    if (window.location.pathname.includes('cart.php')) {
        updateCartPage();
    }
}

// Update cart page
function updateCartPage() {
    const cartContainer = document.getElementById('cartContainer');
    const cartSummary = document.getElementById('cartSummary');
    
    if (!cartContainer || !cartSummary) return;
    
    if (cart.length === 0) {
        cartContainer.innerHTML = `
            <div class="text-center py-5">
                <i class="bi bi-cart-x" style="font-size: 4rem; color: #6c757d;"></i>
                <h3 class="mt-3">Tu carrito está vacío</h3>
                <p class="text-muted">¡Añade algunos productos para empezar!</p>
                <a href="index.php" class="btn btn-primary">
                    <i class="bi bi-shop me-2"></i>Continuar Comprando
                </a>
            </div>
        `;
        cartSummary.innerHTML = '';
        return;
    }
    
    let subtotal = 0;
    let cartHTML = `
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Precio</th>
                        <th>Cantidad</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        subtotal += itemTotal;
        
        cartHTML += `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <img src="${item.image}" alt="${item.name}" 
                             style="width: 60px; height: 60px; object-fit: cover; margin-right: 15px;">
                        <div>
                            <h6 class="mb-0">${item.name}</h6>
                        </div>
                    </div>
                </td>
                <td>€${item.price.toFixed(2)}</td>
                <td>
                    <input type="number" class="form-control" style="width: 80px;" 
                           value="${item.quantity}" min="1" 
                           onchange="updateQuantity(${item.id}, this.value)">
                </td>
                <td>€${itemTotal.toFixed(2)}</td>
                <td>
                    <button class="btn btn-sm btn-danger" onclick="removeFromCart(${item.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    cartHTML += `
                </tbody>
            </table>
        </div>
        <div class="text-end mt-3">
            <button class="btn btn-outline-danger" onclick="clearCart()">
                <i class="bi bi-trash me-2"></i>Vaciar Carrito
            </button>
        </div>
    `;
    
    cartContainer.innerHTML = cartHTML;
    
    // Update summary
    const shipping = 5.00;
    const tax = subtotal * 0.21;
    const total = subtotal + shipping + tax;
    
    cartSummary.innerHTML = `
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Resumen del Pedido</h5>
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <span>€${subtotal.toFixed(2)}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Envío:</span>
                    <span>€${shipping.toFixed(2)}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>IVA (21%):</span>
                    <span>€${tax.toFixed(2)}</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-3">
                    <strong>Total:</strong>
                    <strong>€${total.toFixed(2)}</strong>
                </div>
                <button class="btn btn-primary w-100" onclick="proceedToCheckout()">
                    <i class="bi bi-credit-card me-2"></i>Proceder al Pago
                </button>
                <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">
                    <i class="bi bi-arrow-left me-2"></i>Continuar Comprando
                </a>
            </div>
        </div>
    `;
}

// Show cart notification
function showCartNotification(productName) {
    // Create toast notification
    const toastHTML = `
        <div class="toast align-items-center text-white bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-check-circle me-2"></i>
                    ${productName} añadido al carrito
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    const toastContainer = document.querySelector('.toast-container');
    if (toastContainer) {
        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        const toastElement = toastContainer.lastElementChild;
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
        
        // Animate cart icon
        const cartIcon = document.querySelector('#cartDropdown i');
        if (cartIcon) {
            cartIcon.style.animation = 'cartBounce 0.5s ease';
            setTimeout(() => {
                cartIcon.style.animation = '';
            }, 500);
        }
        
        // Remove toast after hide
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    } else {
        // Fallback notification if toast container doesn't exist
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            z-index: 9999;
            animation: slideIn 0.3s ease;
        `;
        notification.innerHTML = `
            <i class="bi bi-check-circle me-2"></i>
            ${productName} añadido al carrito
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
}

// Proceed to checkout
function proceedToCheckout() {
    // Check if user is logged in
    const token = localStorage.getItem('token');
    if (!token) {
        alert('Debes iniciar sesión para continuar con la compra');
        // Save current URL to redirect back after login
        localStorage.setItem('redirectAfterLogin', 'checkout.php');
        window.location.href = 'index.php#login';
        return;
    }
    
    // Verify cart is not empty
    if (cart.length === 0) {
        alert('Tu carrito está vacío');
        return;
    }
    
    // Redirect to checkout
    window.location.href = 'checkout.php';
}

// Quick buy function
function quickBuy(productId, productName, productPrice, productImage) {
    // Add to cart and go directly to checkout
    addToCart(productId, productName, productPrice, productImage);
    proceedToCheckout();
}

// Initialize cart when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initCart();
    
    // Add cart dropdown functionality
    const cartDropdown = document.getElementById('cartDropdown');
    if (cartDropdown) {
        cartDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            // Bootstrap will handle the dropdown toggle
        });
    }
});

// Export functions for use in other scripts
window.cartFunctions = {
    addToCart,
    removeFromCart,
    updateQuantity,
    clearCart,
    proceedToCheckout,
    quickBuy
};