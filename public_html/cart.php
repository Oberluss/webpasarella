<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras - WebPasarella</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        .cart-container {
            min-height: 400px;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        .quantity-input {
            width: 80px;
        }
        .summary-card {
            position: sticky;
            top: 20px;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            display: flex;
            align-items: center;
            color: #6c757d;
        }
        .step.active {
            color: #007bff;
        }
        .step.completed {
            color: #28a745;
        }
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 2px solid currentColor;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }
        .step-line {
            width: 100px;
            height: 2px;
            background-color: #ddd;
            margin: 0 20px;
        }
        .cart-item-row:hover {
            background-color: #f8f9fa;
        }
        .btn-continue-shopping {
            background-color: #6c757d;
            color: white;
        }
        .btn-continue-shopping:hover {
            background-color: #5a6268;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-shop me-2"></i>WebPasarella
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#products">Productos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contacto/index.php">Contacto</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="cartDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-cart3"></i>
                            <span class="badge bg-danger" id="cartCount" style="display: none;">0</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 350px;">
                            <h6 class="dropdown-header">Carrito de Compras</h6>
                            <div id="cartItems"></div>
                            <div id="emptyCartMessage" class="text-center py-3" style="display: none;">
                                <p class="text-muted mb-0">Tu carrito está vacío</p>
                            </div>
                            <div class="dropdown-divider"></div>
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>Total:</strong>
                                <strong id="cartTotal">€0.00</strong>
                            </div>
                            <div class="mt-3">
                                <a href="cart.php" class="btn btn-primary btn-sm w-100">Ver Carrito</a>
                            </div>
                        </div>
                    </li>
                    <li class="nav-item dropdown" id="userMenu" style="display: none;">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <span id="userName"></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" onclick="viewProfile()">Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="#" onclick="viewOrders()">Mis Pedidos</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="logout()">Cerrar Sesión</a></li>
                        </ul>
                    </li>
                    <li class="nav-item" id="loginBtn">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#authModal">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Iniciar Sesión
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-5">
        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step active">
                <div class="step-number">1</div>
                <span>Carrito</span>
            </div>
            <div class="step-line"></div>
            <div class="step">
                <div class="step-number">2</div>
                <span>Checkout</span>
            </div>
            <div class="step-line"></div>
            <div class="step">
                <div class="step-number">3</div>
                <span>Confirmación</span>
            </div>
        </div>

        <h2 class="mb-4">
            <i class="bi bi-cart3 me-2"></i>
            Mi Carrito de Compras
        </h2>

        <div class="row">
            <!-- Cart Items -->
            <div class="col-lg-8">
                <div class="card cart-container">
                    <div class="card-body" id="cartContainer">
                        <!-- Cart items will be loaded here by JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <div id="cartSummary">
                    <!-- Summary will be loaded here by JavaScript -->
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <div class="mt-5">
            <h4 class="mb-4">También te puede interesar</h4>
            <div class="row" id="relatedProducts">
                <!-- Related products will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>WebPasarella</h5>
                    <p>Tu tienda online de confianza</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>&copy; 2025 WebPasarella. Todos los derechos reservados.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Auth Modal (same as index.php) -->
    <div class="modal fade" id="authModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="authModalTitle">Iniciar Sesión</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Login Form -->
                    <div id="loginForm">
                        <form onsubmit="handleLogin(event)">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" id="loginEmail" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="loginPassword" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
                        </form>
                        <p class="text-center mt-3">
                            ¿No tienes cuenta? 
                            <a href="#" onclick="showRegisterForm()">Regístrate aquí</a>
                        </p>
                    </div>

                    <!-- Register Form -->
                    <div id="registerForm" style="display: none;">
                        <form onsubmit="handleRegister(event)">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" class="form-control" id="registerFirstName" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Apellidos</label>
                                    <input type="text" class="form-control" id="registerLastName" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" id="registerEmail" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="registerPassword" required>
                                <small class="text-muted">Mínimo 8 caracteres</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirmar Contraseña</label>
                                <input type="password" class="form-control" id="registerPasswordConfirm" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Teléfono (opcional)</label>
                                <input type="tel" class="form-control" id="registerPhone">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Registrarse</button>
                        </form>
                        <p class="text-center mt-3">
                            ¿Ya tienes cuenta? 
                            <a href="#" onclick="showLoginForm()">Inicia sesión aquí</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/cart.js"></script>
    <script>
        // Check authentication on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkAuth();
            loadRelatedProducts();
        });

        // Check if user is authenticated
        function checkAuth() {
            const token = localStorage.getItem('token');
            if (token) {
                // Get user profile
                fetch('api-proxy.php?path=auth/profile', {
                    headers: {
                        'Authorization': 'Bearer ' + token
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('loginBtn').style.display = 'none';
                        document.getElementById('userMenu').style.display = 'block';
                        document.getElementById('userName').textContent = data.user.first_name;
                    } else {
                        localStorage.removeItem('token');
                    }
                });
            }
        }

        // Load related products
        function loadRelatedProducts() {
            fetch('api-proxy.php?path=products')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.products) {
                        // Get 4 random products
                        const shuffled = data.products.sort(() => 0.5 - Math.random());
                        const selected = shuffled.slice(0, 4);
                        
                        let html = '';
                        selected.forEach(product => {
                            html += `
                                <div class="col-md-3 mb-4">
                                    <div class="card h-100">
                                        <img src="${product.image}" class="card-img-top" alt="${product.name}">
                                        <div class="card-body">
                                            <h6 class="card-title">${product.name}</h6>
                                            <p class="card-text">€${parseFloat(product.price).toFixed(2)}</p>
                                            <button class="btn btn-primary btn-sm w-100" 
                                                onclick="addToCart(${product.id}, '${product.name}', ${product.price}, '${product.image}')">
                                                <i class="bi bi-cart-plus me-1"></i>Añadir
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        
                        document.getElementById('relatedProducts').innerHTML = html;
                    }
                });
        }

        // Auth functions (same as index.php)
        function showLoginForm() {
            document.getElementById('loginForm').style.display = 'block';
            document.getElementById('registerForm').style.display = 'none';
            document.getElementById('authModalTitle').textContent = 'Iniciar Sesión';
        }

        function showRegisterForm() {
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('registerForm').style.display = 'block';
            document.getElementById('authModalTitle').textContent = 'Registrarse';
        }

        function handleLogin(event) {
            event.preventDefault();
            
            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;
            
            fetch('api-proxy.php?path=auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, password })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    localStorage.setItem('token', data.token);
                    bootstrap.Modal.getInstance(document.getElementById('authModal')).hide();
                    checkAuth();
                    
                    // Check if we should redirect to checkout
                    const redirect = localStorage.getItem('redirectAfterLogin');
                    if (redirect) {
                        localStorage.removeItem('redirectAfterLogin');
                        window.location.href = redirect;
                    }
                } else {
                    alert(data.message || 'Error al iniciar sesión');
                }
            })
            .catch(error => {
                alert('Error al conectar con el servidor');
            });
        }

        function handleRegister(event) {
            event.preventDefault();
            
            const password = document.getElementById('registerPassword').value;
            const passwordConfirm = document.getElementById('registerPasswordConfirm').value;
            
            if (password !== passwordConfirm) {
                alert('Las contraseñas no coinciden');
                return;
            }
            
            const data = {
                firstName: document.getElementById('registerFirstName').value,
                lastName: document.getElementById('registerLastName').value,
                email: document.getElementById('registerEmail').value,
                password: password,
                phone: document.getElementById('registerPhone').value
            };
            
            fetch('api-proxy.php?path=auth/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Registro exitoso! Por favor verifica tu email.');
                    showLoginForm();
                } else {
                    alert(data.message || 'Error al registrar');
                }
            })
            .catch(error => {
                alert('Error al conectar con el servidor');
            });
        }

        function logout() {
            localStorage.removeItem('token');
            window.location.reload();
        }

        function viewProfile() {
            alert('Perfil - Por implementar');
        }

        function viewOrders() {
            window.location.href = 'my-orders.php';
        }
    </script>
</body>
</html>