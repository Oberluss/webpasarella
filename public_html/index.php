<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webprueba.com - Tu tienda online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-shop me-2"></i>Webprueba.com
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#products">Productos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contacto/index.php">Contacto</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#aboutModal">Acerca de</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <!-- Cart Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle position-relative" href="#" id="cartDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-cart3"></i>
                            <span class="badge bg-danger" id="cartCount" style="display: none;">0</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end cart-dropdown p-3">
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
                    
                    <!-- User Menu -->
                    <li class="nav-item dropdown" id="userMenu" style="display: none;">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <span id="userName"></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" onclick="viewProfile()">
                                <i class="bi bi-person me-2"></i>Mi Perfil
                            </a></li>
                            <li><a class="dropdown-item" href="my-orders.php">
                                <i class="bi bi-bag-check me-2"></i>Mis Pedidos
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="logout()">
                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                            </a></li>
                        </ul>
                    </li>
                    
                    <!-- Login Button -->
                    <li class="nav-item" id="loginBtn">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#authModal">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Iniciar Sesión
                        </a>
                    </li>
                    
                    <!-- Admin Panel Link -->
                    <li class="nav-item" id="adminLink" style="display: none;">
                        <a class="nav-link" href="admin-dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i>Panel Admin
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section" style="margin-top: 56px;">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Bienvenido a Webprueba</h1>
            <p class="lead mb-4">Tu solución integral de comercio electrónico</p>
            <a href="#products" class="btn btn-light btn-lg">
                <i class="bi bi-shop me-2"></i>Explorar Productos
            </a>
        </div>
    </section>

    <!-- Products Section -->
    <section id="products" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Nuestros Productos</h2>
            <div class="row g-4" id="productList">
                <!-- Products will be loaded here -->
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Webprueba.com</h5>
                    <p>Tu tienda online de confianza</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>&copy; 2025 Webprueba.com. Todos los derechos reservados.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Auth Modal -->
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
                                <input type="password" class="form-control" id="registerPassword" required minlength="8">
                                <small class="text-muted">Mínimo 8 caracteres, debe incluir mayúsculas, minúsculas y números</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirmar Contraseña</label>
                                <input type="password" class="form-control" id="registerPasswordConfirm" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Teléfono (opcional)</label>
                                <input type="tel" class="form-control" id="registerPhone">
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="registerNewsletter">
                                <label class="form-check-label" for="registerNewsletter">
                                    Quiero recibir ofertas y novedades por email
                                </label>
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

    <!-- About Modal -->
    <div class="modal fade" id="aboutModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acerca de Webprueba.com</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Webprueba.com es una plataforma de comercio electrónico diseñada para ofrecer la mejor experiencia de compra online.</p>
                    <h6>Características:</h6>
                    <ul>
                        <li>Catálogo de productos actualizado</li>
                        <li>Proceso de compra seguro</li>
                        <li>Múltiples métodos de pago</li>
                        <li>Envío a toda España</li>
                        <li>Atención al cliente personalizada</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container"></div>

    <!-- Loading Spinner -->
    <div class="loading-spinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/cart.js"></script>
    <script src="js/auth.js"></script>
    <script src="js/products.js"></script>
    <script src="js/main.js"></script>
</body>
</html>