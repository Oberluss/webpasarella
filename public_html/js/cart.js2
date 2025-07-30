// ==========================================
// SISTEMA DE CARRITO DE COMPRAS
// ==========================================

class ShoppingCart {
    constructor() {
        this.items = JSON.parse(localStorage.getItem('webpasarella_cart')) || [];
        this.updateCartUI();
        this.initEventListeners();
    }

    // Agregar producto al carrito
    addItem(productId, productName, price, image, quantity = 1) {
        const existingItem = this.items.find(item => item.product_id === productId);
        
        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            this.items.push({
                product_id: productId,
                product_name: productName,
                price: parseFloat(price),
                image: image,
                quantity: quantity
            });
        }
        
        this.saveCart();
        this.updateCartUI();
        this.showNotification(`${productName} agregado al carrito`);
    }

    // Eliminar producto del carrito
    removeItem(productId) {
        this.items = this.items.filter(item => item.product_id !== productId);
        this.saveCart();
        this.updateCartUI();
    }

    // Actualizar cantidad de un producto
    updateQuantity(productId, quantity) {
        const item = this.items.find(item => item.product_id === productId);
        if (item) {
            if (quantity <= 0) {
                this.removeItem(productId);
            } else {
                item.quantity = quantity;
                this.saveCart();
                this.updateCartUI();
            }
        }
    }

    // Obtener total del carrito
    getTotal() {
        return this.items.reduce((total, item) => total + (item.price * item.quantity), 0);
    }

    // Obtener cantidad total de items
    getTotalItems() {
        return this.items.reduce((total, item) => total + item.quantity, 0);
    }

    // Vaciar carrito
    clear() {
        this.items = [];
        this.saveCart();
        this.updateCartUI();
    }

    // Guardar carrito en localStorage
    saveCart() {
        localStorage.setItem('webpasarella_cart', JSON.stringify(this.items));
    }

    // Actualizar interfaz del carrito
    updateCartUI() {
        this.updateCartCounter();
        this.updateCartDropdown();
        this.updateCartPage();
    }

    // Actualizar contador del carrito
    updateCartCounter() {
        const counter = document.querySelector('.cart-counter');
        const totalItems = this.getTotalItems();
        
        if (counter) {
            counter.textContent = totalItems;
            counter.style.display = totalItems > 0 ? 'inline' : 'none';
        }
    }

    // Actualizar dropdown del carrito
    updateCartDropdown() {
        const dropdown = document.querySelector('.cart-dropdown');
        if (!dropdown) return;

        if (this.items.length === 0) {
            dropdown.innerHTML = '<p class="empty-cart">Tu carrito está vacío</p>';
            return;
        }

        let html = '<div class="cart-items">';
        this.items.forEach(item => {
            html += `
                <div class="cart-item" data-product-id="${item.product_id}">
                    <img src="${item.image}" alt="${item.product_name}" class="cart-item-image">
                    <div class="cart-item-details">
                        <h4>${item.product_name}</h4>
                        <p class="cart-item-price">€${item.price.toFixed(2)} x ${item.quantity}</p>
                        <p class="cart-item-total">€${(item.price * item.quantity).toFixed(2)}</p>
                    </div>
                    <button class="remove-item" onclick="cart.removeItem(${item.product_id})">×</button>
                </div>
            `;
        });
        
        html += `
            </div>
            <div class="cart-footer">
                <div class="cart-total">
                    <strong>Total: €${this.getTotal().toFixed(2)}</strong>
                </div>
                <div class="cart-actions">
                    <button class="btn btn-outline" onclick="cart.clear()">Vaciar</button>
                    <button class="btn btn-primary" onclick="goToCheckout()">Finalizar Compra</button>
                </div>
            </div>
        `;

        dropdown.innerHTML = html;
    }

    // Actualizar página del carrito
    updateCartPage() {
        const cartPage = document.querySelector('#cart-page');
        if (!cartPage) return;

        if (this.items.length === 0) {
            cartPage.innerHTML = `
                <div class="empty-cart-page">
                    <h2>Tu carrito está vacío</h2>
                    <p>¡Descubre nuestros productos y comienza a comprar!</p>
                    <a href="#products" class="btn btn-primary">Ver Productos</a>
                </div>
            `;
            return;
        }

        let html = `
            <div class="cart-header">
                <h2>Tu Carrito (${this.getTotalItems()} productos)</h2>
            </div>
            <div class="cart-items-list">
        `;

        this.items.forEach(item => {
            html += `
                <div class="cart-item-row" data-product-id="${item.product_id}">
                    <div class="item-image">
                        <img src="${item.image}" alt="${item.product_name}">
                    </div>
                    <div class="item-details">
                        <h3>${item.product_name}</h3>
                        <p class="item-price">€${item.price.toFixed(2)}</p>
                    </div>
                    <div class="item-quantity">
                        <button class="qty-btn" onclick="cart.updateQuantity(${item.product_id}, ${item.quantity - 1})">-</button>
                        <input type="number" value="${item.quantity}" min="1" 
                               onchange="cart.updateQuantity(${item.product_id}, parseInt(this.value))">
                        <button class="qty-btn" onclick="cart.updateQuantity(${item.product_id}, ${item.quantity + 1})">+</button>
                    </div>
                    <div class="item-total">
                        <strong>€${(item.price * item.quantity).toFixed(2)}</strong>
                    </div>
                    <div class="item-actions">
                        <button class="remove-btn" onclick="cart.removeItem(${item.product_id})">
                            🗑️ Eliminar
                        </button>
                    </div>
                </div>
            `;
        });

        html += `
            </div>
            <div class="cart-summary">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <strong>€${this.getTotal().toFixed(2)}</strong>
                </div>
                <div class="summary-row">
                    <span>Envío:</span>
                    <span>Gratis</span>
                </div>
                <div class="summary-row total">
                    <span>Total:</span>
                    <strong>€${this.getTotal().toFixed(2)}</strong>
                </div>
                <div class="cart-actions">
                    <button class="btn btn-outline" onclick="cart.clear()">Vaciar Carrito</button>
                    <button class="btn btn-primary" onclick="goToCheckout()">Proceder al Pago</button>
                </div>
            </div>
        `;

        cartPage.innerHTML = html;
    }

    // Mostrar notificación
    showNotification(message) {
        // Crear notificación
        const notification = document.createElement('div');
        notification.className = 'cart-notification';
        notification.textContent = message;
        
        // Agregar al DOM
        document.body.appendChild(notification);
        
        // Mostrar con animación
        setTimeout(() => notification.classList.add('show'), 100);
        
        // Ocultar después de 3 segundos
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => document.body.removeChild(notification), 300);
        }, 3000);
    }

    // Inicializar event listeners
    initEventListeners() {
        // Event listeners para botones "Agregar al carrito"
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('add-to-cart')) {
                e.preventDefault();
                const productCard = e.target.closest('.product-card');
                if (productCard) {
                    const productId = parseInt(productCard.dataset.productId);
                    const productName = productCard.querySelector('.product-name').textContent;
                    const price = parseFloat(productCard.querySelector('.product-price').textContent.replace('€', ''));
                    const image = productCard.querySelector('.product-image').src;
                    
                    this.addItem(productId, productName, price, image, 1);
                }
            }
        });

        // Toggle del dropdown del carrito
        const cartToggle = document.querySelector('.cart-toggle');
        if (cartToggle) {
            cartToggle.addEventListener('click', (e) => {
                e.preventDefault();
                const dropdown = document.querySelector('.cart-dropdown');
                if (dropdown) {
                    dropdown.classList.toggle('show');
                }
            });
        }

        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', (e) => {
            const cartContainer = e.target.closest('.cart-container');
            if (!cartContainer) {
                const dropdown = document.querySelector('.cart-dropdown');
                if (dropdown) {
                    dropdown.classList.remove('show');
                }
            }
        });
    }
}

// ==========================================
// FUNCIONES GLOBALES
// ==========================================

// Ir al checkout
function goToCheckout() {
    if (cart.items.length === 0) {
        alert('Tu carrito está vacío');
        return;
    }
    
    // Redirigir a la página de checkout
    window.location.hash = 'checkout';
}

// Inicializar carrito cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.cart = new ShoppingCart();
});

// Export para uso en otros archivos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ShoppingCart;
}
