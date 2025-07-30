// ==========================================
// SCRIPT COMPLETO CON PANEL ADMIN FUNCIONAL
// ==========================================

console.log('Script.js cargado');

// Variables globales
let currentSlide = 0;
let currentView = 'home';

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, inicializando...');
    initializeApp();
});

function initializeApp() {
    // Inicializar slider
    startSlider();
    
    // Verificar usuario logueado
    checkUserSession();
    
    // Cargar productos
    loadProducts();
    
    // Configurar validación de teléfono
    setupPhoneValidation();
    
    console.log('Aplicación inicializada correctamente');
}

// ==========================================
// NAVEGACIÓN MEJORADA - MANEJO DE VISTAS
// ==========================================

function showView(viewName) {
    console.log('Cambiando a vista:', viewName);
    
    // Manejar modales especiales
    if (viewName === 'login') {
        const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
        loginModal.show();
        return;
    }
    
    if (viewName === 'register') {
        const registerModal = new bootstrap.Modal(document.getElementById('registerModal'));
        registerModal.show();
        setupPhoneValidation(); // Asegurar que la validación esté activa
        return;
    }
    
    // Ocultar todas las vistas principales
    document.getElementById('homeView').style.display = 'none';
    document.getElementById('cartView').style.display = 'none';
    document.getElementById('adminView').style.display = 'none';
    
    // Mostrar la vista solicitada
    switch(viewName) {
        case 'home':
            document.getElementById('homeView').style.display = 'block';
            currentView = 'home';
            break;
            
        case 'cart':
            document.getElementById('cartView').style.display = 'block';
            currentView = 'cart';
            updateCartDisplay();
            break;
            
        case 'admin':
            // Verificar si es admin
            const userData = localStorage.getItem('user');
            if (userData) {
                const user = JSON.parse(userData);
                if (user.role === 'admin') {
                    document.getElementById('adminView').style.display = 'block';
                    currentView = 'admin';
                    loadAdminDashboard();
                } else {
                    alert('No tienes permisos de administrador');
                    showView('home');
                }
            } else {
                alert('Debes iniciar sesión');
                showView('login');
            }
            break;
            
        default:
            document.getElementById('homeView').style.display = 'block';
            currentView = 'home';
    }
}

function scrollToSection(sectionId) {
    console.log('Scrolling to:', sectionId);
    
    // Si no estamos en home, cambiar a home primero
    if (currentView !== 'home') {
        showView('home');
        // Esperar un momento para que se cargue la vista
        setTimeout(() => {
            const element = document.getElementById(sectionId);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth' });
            }
        }, 100);
    } else {
        const element = document.getElementById(sectionId);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth' });
        }
    }
}

// ==========================================
// AUTENTICACIÓN MEJORADA
// ==========================================

async function handleLogin(event) {
    event.preventDefault();
    console.log('Procesando login...');
    
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;
    
    if (!email || !password) {
        alert('Por favor completa todos los campos');
        return;
    }
    
    try {
        const response = await fetch(window.location.pathname, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'login',
                email: email,
                password: password
            })
        });
        
        const result = await response.json();
        console.log('Respuesta login:', result);
        
        if (result.success) {
            // Guardar datos
            localStorage.setItem('token', result.token);
            localStorage.setItem('user', JSON.stringify(result.user));
            
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('loginModal'));
            if (modal) modal.hide();
            
            // Actualizar UI
            updateAuthUI(result.user);
            
            // Si es admin, redirigir al panel
            if (result.user.role === 'admin') {
                setTimeout(() => {
                    showView('admin');
                }, 500);
                alert('¡Bienvenido Administrador!');
            } else {
                alert('¡Bienvenido ' + result.user.firstName + '!');
            }
            
        } else {
            alert('Error: ' + (result.message || 'Credenciales incorrectas'));
        }
    } catch (error) {
        console.error('Error en login:', error);
        alert('Error de conexión');
    }
}

async function handleRegister(event) {
    event.preventDefault();
    console.log('Procesando registro...');
    
    const formData = {
        action: 'register',
        firstName: document.getElementById('registerName').value,
        lastName: document.getElementById('registerLastName').value,
        email: document.getElementById('registerEmail').value,
        password: document.getElementById('registerPassword').value,
        phone: document.getElementById('registerPhone').value,
        newsletter: document.getElementById('registerNewsletter').checked
    };
    
    // Validar teléfono si se proporciona
    if (formData.phone) {
        // Limpiar espacios y caracteres no numéricos
        formData.phone = formData.phone.replace(/\D/g, '');
        
        // Validar que tenga 9 dígitos y empiece por 6, 7, 8 o 9
        if (!/^[6789]\d{8}$/.test(formData.phone)) {
            alert('El teléfono debe tener 9 dígitos y comenzar por 6, 7, 8 o 9');
            return;
        }
        
        // Añadir prefijo español
        formData.phone = '+34' + formData.phone;
    }
    
    // Validar contraseñas
    const confirmPassword = document.getElementById('registerPasswordConfirm').value;
    if (formData.password !== confirmPassword) {
        alert('Las contraseñas no coinciden');
        return;
    }
    
    // Validar términos
    if (!document.getElementById('registerTerms').checked) {
        alert('Debes aceptar los términos y condiciones');
        return;
    }
    
    try {
        const response = await fetch(window.location.pathname, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        console.log('Respuesta registro:', result);
        
        if (result.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
            if (modal) modal.hide();
            
            alert('¡Cuenta creada exitosamente! Ya puedes iniciar sesión.');
            
            setTimeout(() => showView('login'), 500);
        } else {
            alert('Error: ' + (result.message || 'Error en registro'));
        }
    } catch (error) {
        console.error('Error en registro:', error);
        alert('Error de conexión');
    }
}

// ==========================================
// VALIDACIÓN DE TELÉFONO EN TIEMPO REAL
// ==========================================

function setupPhoneValidation() {
    const phoneInput = document.getElementById('registerPhone');
    if (!phoneInput) return;
    
    // Cambiar el placeholder
    phoneInput.placeholder = '600 123 456';
    
    // Validación mientras escribe
    phoneInput.addEventListener('input', function(e) {
        let value = e.target.value;
        
        // Eliminar todo lo que no sea número
        value = value.replace(/\D/g, '');
        
        // Limitar a 9 dígitos
        if (value.length > 9) {
            value = value.substr(0, 9);
        }
        
        // Formatear con espacios (opcional, para mejor visualización)
        if (value.length >= 6) {
            value = value.substr(0, 3) + ' ' + value.substr(3, 3) + ' ' + value.substr(6);
        } else if (value.length >= 3) {
            value = value.substr(0, 3) + ' ' + value.substr(3);
        }
        
        e.target.value = value;
        
        // Validación visual
        if (value.replace(/\s/g, '').length === 9) {
            const cleanNumber = value.replace(/\s/g, '');
            if (/^[6789]\d{8}$/.test(cleanNumber)) {
                phoneInput.classList.remove('is-invalid');
                phoneInput.classList.add('is-valid');
            } else {
                phoneInput.classList.remove('is-valid');
                phoneInput.classList.add('is-invalid');
            }
        } else {
            phoneInput.classList.remove('is-valid', 'is-invalid');
        }
    });
    
    // Prevenir caracteres no numéricos
    phoneInput.addEventListener('keypress', function(e) {
        const char = String.fromCharCode(e.which);
        if (!/[0-9]/.test(char) && e.which !== 8 && e.which !== 32) {
            e.preventDefault();
        }
    });
}

function updateAuthUI(user) {
    const authButton = document.getElementById('authButton');
    if (authButton && user) {
        authButton.innerHTML = `
            <div class="dropdown">
                <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user"></i> ${user.firstName}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    ${user.role === 'admin' ? '<li><a class="dropdown-item" href="#" onclick="showView(\'admin\')"><i class="fas fa-tachometer-alt me-2"></i>Panel Admin</a></li><li><hr class="dropdown-divider"></li>' : ''}
                    <li><a class="dropdown-item" href="#" onclick="showView(\'home\')"><i class="fas fa-home me-2"></i>Inicio</a></li>
                    <li><a class="dropdown-item" href="#" onclick="showProfile()"><i class="fas fa-user-circle me-2"></i>Mi Perfil</a></li>
                    <li><a class="dropdown-item" href="#" onclick="showOrders()"><i class="fas fa-shopping-bag me-2"></i>Mis Pedidos</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" onclick="logout()"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                </ul>
            </div>
        `;
        console.log('UI actualizada para usuario:', user.firstName, 'Role:', user.role);
    }
}

function logout() {
    if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        localStorage.removeItem('cart');
        
        const authButton = document.getElementById('authButton');
        if (authButton) {
            authButton.innerHTML = `
                <button class="btn btn-outline-light" onclick="showView('login')">
                    <i class="fas fa-user"></i> Iniciar Sesión
                </button>
            `;
        }
        
        // Volver a home
        showView('home');
        
        alert('Sesión cerrada exitosamente');
        console.log('Usuario deslogueado');
    }
}

function checkUserSession() {
    const token = localStorage.getItem('token');
    const userData = localStorage.getItem('user');
    
    if (token && userData) {
        try {
            const user = JSON.parse(userData);
            updateAuthUI(user);
            console.log('Sesión restaurada para:', user.firstName, 'Role:', user.role);
        } catch (e) {
            console.error('Error parsing user data:', e);
            localStorage.removeItem('token');
            localStorage.removeItem('user');
        }
    }
}

// ==========================================
// FUNCIONES DEL PANEL ADMIN
// ==========================================

function showAdminSection(section) {
    console.log('Mostrando sección admin:', section);
    
    // Por ahora solo mostrar alertas
    switch(section) {
        case 'dashboard':
           // alert('Dashboard - En desarrollo');
            break;
        case 'products':
            window.location.href = 'admin-products.php';
            break;
        case 'orders':
            window.location.href = 'admin-orders.php';
            break;
        case 'users':
            window.location.href = 'admin-users-full.php';
            break;
        case 'settings':
            alert('Configuración - En desarrollo');
            break;
    }
}

function loadAdminDashboard() {
    console.log('Cargando dashboard del administrador...');
    // Aquí puedes agregar lógica para cargar datos del dashboard
}

// ==========================================
// FUNCIONES DE PRODUCTOS
// ==========================================

async function loadProducts() {
    console.log('Cargando productos...');
    
    try {
        // Simular productos para prueba
        const products = [
            {
                id: 1,
                name: 'Laptop Profesional',
                price: 899.99,
                image: 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=400',
                description: 'Potente laptop para profesionales'
            },
            {
                id: 2,
                name: 'Smartphone 5G',
                price: 699.99,
                image: 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400',
                description: 'Último modelo con tecnología 5G'
            },
            {
                id: 3,
                name: 'Tablet Ultra',
                price: 499.99,
                image: 'https://images.unsplash.com/photo-1561154464-82e9adf32764?w=400',
                description: 'Tablet de alta resolución'
            },
            {
                id: 4,
                name: 'Smartwatch Pro',
                price: 299.99,
                image: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400',
                description: 'Reloj inteligente avanzado'
            },
            {
                id: 5,
                name: 'Auriculares Premium',
                price: 199.99,
                image: 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400',
                description: 'Sonido de alta calidad'
            },
            {
                id: 6,
                name: 'Cámara 4K',
                price: 1299.99,
                image: 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=400',
                description: 'Cámara profesional 4K'
            }
        ];
        
        displayProducts(products);
        
    } catch (error) {
        console.error('Error cargando productos:', error);
    }
}

function displayProducts(products) {
    const container = document.getElementById('products-container');
    if (!container) return;
    
    container.innerHTML = products.map(product => `
        <div class="col-md-4 mb-4">
            <div class="product-card card h-100">
                <img src="${product.image}" class="card-img-top" alt="${product.name}">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">${product.name}</h5>
                    <p class="card-text flex-grow-1">${product.description}</p>
                    <div class="d-flex justify-content-between align-items-center mt-auto">
                        <span class="h5 mb-0 text-primary">€${product.price}</span>
                        <button class="btn btn-primary" onclick="addToCart(${product.id}, '${product.name}', ${product.price})">
                            <i class="fas fa-cart-plus"></i> Añadir
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

// ==========================================
// FUNCIONES DEL CARRITO
// ==========================================

function addToCart(productId, productName, productPrice) {
    let cart = JSON.parse(localStorage.getItem('cart') || '[]');
    
    const existingItem = cart.find(item => item.id === productId);
    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.push({
            id: productId,
            name: productName,
            price: productPrice,
            quantity: 1
        });
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCounter();
    alert('Producto añadido al carrito');
}

function updateCartCounter() {
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    
    const cartCount = document.getElementById('cartCount');
    if (cartCount) {
        cartCount.textContent = totalItems;
        cartCount.style.display = totalItems > 0 ? 'inline-block' : 'none';
    }
}

function updateCartDisplay() {
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    const cartItems = document.getElementById('cartItems');
    
    if (!cartItems) return;
    
    if (cart.length === 0) {
        cartItems.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <p>Tu carrito está vacío</p>
                <button class="btn btn-primary" onclick="showView('home')">Continuar Comprando</button>
            </div>
        `;
        updateCartTotals(0, 0, 0);
        return;
    }
    
    let subtotal = 0;
    cartItems.innerHTML = cart.map(item => {
        const itemTotal = item.price * item.quantity;
        subtotal += itemTotal;
        return `
            <div class="cart-item d-flex justify-content-between align-items-center p-3 border-bottom">
                <div>
                    <h6>${item.name}</h6>
                    <small class="text-muted">€${item.price} x ${item.quantity}</small>
                </div>
                <div class="d-flex align-items-center">
                    <span class="me-3">€${itemTotal.toFixed(2)}</span>
                    <button class="btn btn-sm btn-danger" onclick="removeFromCart(${item.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    }).join('');
    
    const tax = subtotal * 0.21;
    const total = subtotal + tax;
    updateCartTotals(subtotal, tax, total);
}

function updateCartTotals(subtotal, tax, total) {
    const elements = {
        cartSubtotal: document.getElementById('cartSubtotal'),
        cartTax: document.getElementById('cartTax'),
        cartTotal: document.getElementById('cartTotal')
    };
    
    if (elements.cartSubtotal) elements.cartSubtotal.textContent = `€${subtotal.toFixed(2)}`;
    if (elements.cartTax) elements.cartTax.textContent = `€${tax.toFixed(2)}`;
    if (elements.cartTotal) elements.cartTotal.textContent = `€${total.toFixed(2)}`;
}

function removeFromCart(productId) {
    let cart = JSON.parse(localStorage.getItem('cart') || '[]');
    cart = cart.filter(item => item.id !== productId);
    localStorage.setItem('cart', JSON.stringify(cart));
    
    updateCartCounter();
    updateCartDisplay();
}

function proceedToCheckout() {
    const user = localStorage.getItem('user');
    if (!user) {
        alert('Debes iniciar sesión para continuar');
        showView('login');
        return;
    }
    
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    if (cart.length === 0) {
        alert('Tu carrito está vacío');
        return;
    }
    
    // Mostrar modal de pago
    const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
    paymentModal.show();
    
    // Actualizar total en modal
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const total = subtotal * 1.21;
    const paymentTotal = document.getElementById('paymentTotal');
    if (paymentTotal) {
        paymentTotal.textContent = `€${total.toFixed(2)}`;
    }
}

// ==========================================
// FUNCIONES DE MODALES
// ==========================================

function switchToRegister() {
    const loginModal = bootstrap.Modal.getInstance(document.getElementById('loginModal'));
    if (loginModal) loginModal.hide();
    
    setTimeout(() => {
        const registerModal = new bootstrap.Modal(document.getElementById('registerModal'));
        registerModal.show();
        setupPhoneValidation(); // Asegurar validación activa
    }, 300);
}

function switchToLogin() {
    const registerModal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
    if (registerModal) registerModal.hide();
    
    setTimeout(() => {
        const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
        loginModal.show();
    }, 300);
}

function togglePassword() {
    const passwordInput = document.getElementById('loginPassword');
    const passwordIcon = document.getElementById('passwordIcon');
    
    if (passwordInput && passwordIcon) {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            passwordIcon.classList.remove('fa-eye');
            passwordIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            passwordIcon.classList.remove('fa-eye-slash');
            passwordIcon.classList.add('fa-eye');
        }
    }
}

function toggleRegisterPassword(inputId) {
    const passwordInput = document.getElementById(inputId);
    const icon = passwordInput.nextElementSibling.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// ==========================================
// FUNCIONES DEL SLIDER
// ==========================================

function startSlider() {
    const slides = document.querySelectorAll('.slider-item');
    if (slides.length > 0) {
        setInterval(() => {
            currentSlide = (currentSlide + 1) % slides.length;
            changeSlide(currentSlide);
        }, 5000);
    }
}

function changeSlide(index) {
    const slides = document.querySelectorAll('.slider-item');
    const dots = document.querySelectorAll('.slider-dot');
    
    slides.forEach(slide => slide.classList.remove('active'));
    dots.forEach(dot => dot.classList.remove('active'));
    
    if (slides[index]) slides[index].classList.add('active');
    if (dots[index]) dots[index].classList.add('active');
    
    currentSlide = index;
}

// ==========================================
// FUNCIONES PLACEHOLDER
// ==========================================

function showProfile() {
    alert('Perfil de usuario - En desarrollo');
}

function showOrders() {
    alert('Mis pedidos - En desarrollo');
}

function processPayment(event) {
    event.preventDefault();
    alert('¡Gracias por tu compra! (Simulación - En desarrollo)');
    
    // Limpiar carrito
    localStorage.removeItem('cart');
    updateCartCounter();
    
    // Cerrar modal y volver a home
    const modal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
    if (modal) modal.hide();
    
    showView('home');
}

// Inicializar contador del carrito al cargar
document.addEventListener('DOMContentLoaded', function() {
    updateCartCounter();
});

console.log('Script completo cargado correctamente');
