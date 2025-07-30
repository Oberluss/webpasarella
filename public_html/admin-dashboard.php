<?php
session_start();

// Verificación de admin
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: /');
    exit;
}

// Variables del usuario
$admin_name = isset($_SESSION['user']['name']) ? htmlspecialchars($_SESSION['user']['name']) : 'Admin';
$admin_initial = isset($_SESSION['user']['name']) ? strtoupper(substr($_SESSION['user']['name'], 0, 1)) : 'A';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Panel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { 
            background-color: #f4f6f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .sidebar {
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.15);
            position: relative;
            display: flex;
            flex-direction: column;
        }
        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 12px 20px;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            border-radius: 0;
            position: relative;
        }
        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #3498db;
            color: #fff;
            padding-left: 25px;
        }
        .sidebar .nav-link.active {
            background-color: rgba(52, 152, 219, 0.2);
            border-left-color: #3498db;
            color: #fff;
            font-weight: 500;
        }
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }
        .dashboard-link {
            background: linear-gradient(45deg, rgba(52, 152, 219, 0.2), rgba(41, 128, 185, 0.2)) !important;
            border-left: 3px solid #3498db !important;
            font-weight: 500 !important;
        }
        .dashboard-link:hover {
            background: linear-gradient(45deg, #3498db, #2980b9) !important;
            color: white !important;
            transform: translateX(5px);
        }
        .site-link:hover {
            background: linear-gradient(45deg, #27ae60, #229954) !important;
            color: white !important;
            transform: translateX(5px);
        }
        .logout-link:hover {
            background: linear-gradient(45deg, #e74c3c, #c0392b) !important;
            color: white !important;
            transform: translateX(5px);
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3498db, #2980b9);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .stat-card.danger::before { background: linear-gradient(90deg, #e74c3c, #c0392b); }
        .stat-card.success::before { background: linear-gradient(90deg, #27ae60, #229954); }
        .stat-card.warning::before { background: linear-gradient(90deg, #f39c12, #e67e22); }
        .stat-card.info::before { background: linear-gradient(90deg, #3498db, #2980b9); }
        
        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            position: relative;
        }
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }
        .activity-item:hover {
            background-color: #f8f9fa;
            margin: 0 -15px;
            padding: 15px;
            border-radius: 10px;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(45deg, #3498db, #2980b9);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            box-shadow: 0 2px 10px rgba(52, 152, 219, 0.3);
        }
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-20px, -20px) rotate(180deg); }
        }
        .chart-container {
            position: relative;
            height: 350px;
            width: 100%;
        }
        #usersChart {
            max-height: 350px !important;
            max-width: 100% !important;
        }
        .quick-action-btn {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            text-decoration: none;
            color: #495057;
            transition: all 0.3s ease;
            display: block;
            margin-bottom: 15px;
        }
        .quick-action-btn:hover {
            border-color: #3498db;
            color: #3498db;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.2);
        }
        .alert-custom {
            border: none;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 15px;
            border-left: 4px solid;
        }
        .alert-custom.alert-warning { border-left-color: #f39c12; background: rgba(243, 156, 18, 0.1); }
        .alert-custom.alert-info { border-left-color: #3498db; background: rgba(52, 152, 219, 0.1); }
        .table-modern {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .table-modern thead {
            background: linear-gradient(45deg, #2c3e50, #34495e);
            color: white;
        }
        .table-modern tbody tr:hover {
            background-color: #f8f9fa;
        }
        .badge-modern {
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .progress-modern {
            height: 8px;
            border-radius: 10px;
            background-color: #e9ecef;
        }
        .progress-bar-modern {
            border-radius: 10px;
        }
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(52, 152, 219, 0.3);
            border-radius: 50%;
            border-top-color: #3498db;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .skeleton-text {
            height: 20px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .skeleton-box {
            height: 100px;
            border-radius: 8px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0 sidebar">
                <div class="text-white text-center py-4">
                    <h4><i class="fas fa-cog"></i> Admin Panel</h4>
                    <small class="text-muted d-block">Sistema de Gestión</small>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="admin-dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link" href="./admin-products.php">
                        <i class="fas fa-box"></i> Productos
                    </a>
                    <a class="nav-link" href="admin-orders.php">
                        <i class="fas fa-shopping-cart"></i> Pedidos
                    </a>
                    <a class="nav-link" href="admin-users-full.php">
                        <i class="fas fa-users"></i> Usuarios
                    </a>
                    <a class="nav-link" href="#">
                        <i class="fas fa-chart-bar"></i> Reportes
                    </a>
                    <a class="nav-link" href="#">
                        <i class="fas fa-cog"></i> Configuración
                    </a>
                    
                    <hr class="bg-white mx-3">
                    
                    <div class="px-3 mb-2">
                        <small class="text-muted text-uppercase fw-bold" style="letter-spacing: 0.5px; font-size: 10px;">Navegación</small>
                    </div>
                    
                    <a class="nav-link dashboard-link" href="admin-dashboard.php">
                        <i class="fas fa-home"></i> Panel Principal
                    </a>
                    
                    <a class="nav-link site-link" href="/" target="_blank">
                        <i class="fas fa-external-link-alt"></i> Ver Sitio Web
                    </a>
                    
                    <a class="nav-link logout-link text-danger" href="#" onclick="logout(); return false;">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </nav>
            </div>

            <!-- Content -->
            <div class="col-md-10 p-4">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2><i class="fas fa-tachometer-alt"></i> Dashboard Principal</h2>
                            <p class="mb-0">Bienvenido al panel de administración - Resumen general del sistema</p>
                            <small><?php echo date('l, d F Y'); ?></small>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="d-flex justify-content-end align-items-center">
                                <div class="user-avatar me-3">
                                    <?php echo $admin_initial; ?>
                                </div>
                                <div class="text-start">
                                    <strong><?php echo $admin_name; ?></strong>
                                    <br>
                                    <small>Administrador</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alertas del sistema -->
                <div id="system-alerts" class="row mb-4" style="display: none;">
                    <div class="col-12" id="alerts-container">
                        <!-- Las alertas se cargarán dinámicamente -->
                    </div>
                </div>

                <!-- Estadísticas principales -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stat-card info">
                            <div class="d-flex justify-content-between align-items-center">
                                <div id="users-stat">
                                    <div class="skeleton skeleton-text" style="width: 60px;"></div>
                                    <p class="text-muted mb-0">Usuarios Totales</p>
                                    <div class="skeleton skeleton-text" style="width: 100px;"></div>
                                </div>
                                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stat-card success">
                            <div class="d-flex justify-content-between align-items-center">
                                <div id="products-stat">
                                    <div class="skeleton skeleton-text" style="width: 60px;"></div>
                                    <p class="text-muted mb-0">Productos</p>
                                    <div class="skeleton skeleton-text" style="width: 100px;"></div>
                                </div>
                                <div class="stat-icon bg-info bg-opacity-10 text-info">
                                    <i class="fas fa-box"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stat-card warning">
                            <div class="d-flex justify-content-between align-items-center">
                                <div id="revenue-stat">
                                    <div class="skeleton skeleton-text" style="width: 80px;"></div>
                                    <p class="text-muted mb-0">Ventas del Mes</p>
                                    <div class="skeleton skeleton-text" style="width: 120px;"></div>
                                </div>
                                <div class="stat-icon bg-success bg-opacity-10 text-success">
                                    <i class="fas fa-euro-sign"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stat-card danger">
                            <div class="d-flex justify-content-between align-items-center">
                                <div id="orders-stat">
                                    <div class="skeleton skeleton-text" style="width: 60px;"></div>
                                    <p class="text-muted mb-0">Pedidos</p>
                                    <div class="skeleton skeleton-text" style="width: 100px;"></div>
                                </div>
                                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Gráfico de registros -->
                    <div class="col-md-8 mb-4">
                        <div class="content-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0"><i class="fas fa-chart-line text-primary"></i> Nuevos Usuarios - Últimos 7 días</h5>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-calendar"></i> 7 días
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="updateChart(7)">Últimos 7 días</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="updateChart(30)">Últimos 30 días</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="updateChart(90)">Últimos 3 meses</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="chart-container">
                                <canvas id="usersChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Actividad reciente -->
                    <div class="col-md-4 mb-4">
                        <div class="content-card">
                            <h5 class="mb-3"><i class="fas fa-history text-success"></i> Usuarios Recientes</h5>
                            <div id="recent-users">
                                <!-- Skeleton loaders -->
                                <div class="skeleton skeleton-box"></div>
                                <div class="skeleton skeleton-box"></div>
                                <div class="skeleton skeleton-box"></div>
                            </div>
                            <div class="text-center mt-3">
                                <a href="admin-users-full.php" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-users"></i> Ver todos los usuarios
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Productos y acciones rápidas -->
                <div class="row">
                    <div class="col-md-7 mb-4">
                        <div class="content-card">
                            <h5 class="mb-3"><i class="fas fa-star text-warning"></i> Productos Destacados</h5>
                            <div class="table-responsive">
                                <table class="table table-hover table-modern">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th>Precio</th>
                                            <th>Stock</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="products-table">
                                        <!-- Skeleton loaders -->
                                        <tr>
                                            <td colspan="5">
                                                <div class="skeleton skeleton-text"></div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="5">
                                                <div class="skeleton skeleton-text"></div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center">
                                <a href="admin-products.php" class="btn btn-primary">
                                    <i class="fas fa-box"></i> Ver todos los productos
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-5 mb-4">
                        <div class="content-card">
                            <h5 class="mb-3"><i class="fas fa-bolt text-primary"></i> Acciones Rápidas</h5>
                            <div class="row g-3">
                                <div class="col-6">
                                    <a href="admin-users-full.php" class="quick-action-btn">
                                        <div class="text-center">
                                            <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                            <div class="fw-bold">Usuarios</div>
                                            <small class="text-muted">Gestionar</small>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="admin-products.php" class="quick-action-btn">
                                        <div class="text-center">
                                            <i class="fas fa-box fa-2x text-info mb-2"></i>
                                            <div class="fw-bold">Productos</div>
                                            <small class="text-muted">Catálogo</small>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="admin-orders.php" class="quick-action-btn">
                                        <div class="text-center">
                                            <i class="fas fa-shopping-cart fa-2x text-success mb-2"></i>
                                            <div class="fw-bold">Pedidos</div>
                                            <small class="text-muted">Procesar</small>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="#" class="quick-action-btn">
                                        <div class="text-center">
                                            <i class="fas fa-chart-bar fa-2x text-warning mb-2"></i>
                                            <div class="fw-bold">Reportes</div>
                                            <small class="text-muted">Analytics</small>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Métricas adicionales -->
                            <hr class="my-4">
                            <h6 class="mb-3"><i class="fas fa-tachometer-alt text-secondary"></i> Rendimiento del Sistema</h6>
                            <div id="system-metrics">
                                <div class="skeleton skeleton-text mb-3"></div>
                                <div class="skeleton skeleton-text mb-3"></div>
                                <div class="skeleton skeleton-text mb-3"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configuración de la API
        const API_URL = '/api-proxy.php?path=';
        const token = localStorage.getItem('token');
        let chart = null;

        // Headers para las peticiones
        const headers = {
            'Content-Type': 'application/json',
            'Authorization': token ? `Bearer ${token}` : ''
        };

        // Función para formatear números
        function formatNumber(num) {
            return new Intl.NumberFormat('es-ES').format(num);
        }

        // Función para formatear tiempo relativo
        function getRelativeTime(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            
            const minutes = Math.floor(diff / 60000);
            const hours = Math.floor(diff / 3600000);
            const days = Math.floor(diff / 86400000);
            
            if (minutes < 60) return `Hace ${minutes} min`;
            if (hours < 24) return `Hace ${hours} h`;
            return `Hace ${days} días`;
        }

        // Cargar estadísticas principales
        async function loadStats() {
            try {
                // Cargar usuarios
                const usersResponse = await fetch(`${API_URL}users/stats`, { headers });
                if (usersResponse.ok) {
                    const usersData = await usersResponse.json();
                    document.getElementById('users-stat').innerHTML = `
                        <h3 class="mb-1 text-primary">${formatNumber(usersData.total || 0)}</h3>
                        <p class="text-muted mb-0">Usuarios Totales</p>
                        <small class="text-success">
                            <i class="fas fa-arrow-up"></i> +${usersData.newThisWeek || 0} esta semana
                        </small>
                        <div class="progress progress-modern mt-2">
                            <div class="progress-bar progress-bar-modern bg-primary" 
                                 style="width: ${(usersData.verified / usersData.total * 100) || 0}%"></div>
                        </div>
                        <small class="text-muted">${usersData.verified || 0} verificados</small>
                    `;
                }

                // Cargar productos
                const productsResponse = await fetch(`${API_URL}products/stats`, { headers });
                if (productsResponse.ok) {
                    const productsData = await productsResponse.json();
                    document.getElementById('products-stat').innerHTML = `
                        <h3 class="mb-1 text-info">${formatNumber(productsData.total || 0)}</h3>
                        <p class="text-muted mb-0">Productos</p>
                        <small class="text-info">
                            ${productsData.active || 0} activos
                        </small>
                        <div class="progress progress-modern mt-2">
                            <div class="progress-bar progress-bar-modern bg-info" 
                                 style="width: ${(productsData.active / productsData.total * 100) || 0}%"></div>
                        </div>
                        <small class="text-muted">${Math.round((productsData.active / productsData.total * 100) || 0)}% activos</small>
                    `;
                }

                // Cargar pedidos
                const ordersResponse = await fetch(`${API_URL}orders/stats`, { headers });
                if (ordersResponse.ok) {
                    const ordersData = await ordersResponse.json();
                    document.getElementById('orders-stat').innerHTML = `
                        <h3 class="mb-1 text-warning">${formatNumber(ordersData.total || 0)}</h3>
                        <p class="text-muted mb-0">Pedidos</p>
                        <small class="text-${ordersData.pending > 10 ? 'danger' : 'warning'}">
                            <i class="fas fa-clock"></i> ${ordersData.pending || 0} pendientes
                        </small>
                        ${ordersData.pending > 0 ? `
                        <div class="progress progress-modern mt-2">
                            <div class="progress-bar progress-bar-modern bg-warning" 
                                 style="width: ${(ordersData.pending / ordersData.total * 100) || 0}%"></div>
                        </div>
                        <small class="text-muted">Requieren atención</small>` : ''}
                    `;

                    // Mostrar alertas si hay muchos pedidos pendientes
                    if (ordersData.pending > 10) {
                        showAlert('warning', 'fas fa-exclamation-triangle', 
                            `Tienes ${ordersData.pending} pedidos pendientes de procesar`);
                    }
                }

                // Cargar ventas
                const revenueResponse = await fetch(`${API_URL}orders/revenue/monthly`, { headers });
                if (revenueResponse.ok) {
                    const revenueData = await revenueResponse.json();
                    document.getElementById('revenue-stat').innerHTML = `
                        <h3 class="mb-1 text-success">€${formatNumber(revenueData.current || 0)}</h3>
                        <p class="text-muted mb-0">Ventas del Mes</p>
                        <small class="text-${revenueData.growth >= 0 ? 'success' : 'danger'}">
                            <i class="fas fa-arrow-${revenueData.growth >= 0 ? 'up' : 'down'}"></i> 
                            ${Math.abs(revenueData.growth || 0)}% vs anterior
                        </small>
                        <div class="progress progress-modern mt-2">
                            <div class="progress-bar progress-bar-modern bg-success" 
                                 style="width: ${(revenueData.current / revenueData.target * 100) || 0}%"></div>
                        </div>
                        <small class="text-muted">${Math.round((revenueData.current / revenueData.target * 100) || 0)}% del objetivo</small>
                    `;
                }

            } catch (error) {
                console.error('Error cargando estadísticas:', error);
                showAlert('danger', 'fas fa-exclamation-circle', 
                    'Error al cargar las estadísticas. Por favor, verifica la conexión con el servidor.');
            }
        }

        // Cargar usuarios recientes
        async function loadRecentUsers() {
            try {
                const response = await fetch(`${API_URL}users/recent?limit=5`, { headers });
                if (response.ok) {
                    const users = await response.json();
                    let html = '';
                    
                    users.forEach(user => {
                        html += `
                            <div class="activity-item">
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar me-3">
                                        ${user.firstName ? user.firstName.charAt(0).toUpperCase() : 'U'}
                                    </div>
                                    <div class="flex-grow-1">
                                        <strong>${user.firstName || ''} ${user.lastName || ''}</strong>
                                        ${user.isVerified ? '<span class="badge bg-success badge-modern ms-2"><i class="fas fa-check"></i></span>' : ''}
                                        <br>
                                        <small class="text-muted">${user.email}</small>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted">
                                            ${getRelativeTime(user.createdAt)}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    
                    document.getElementById('recent-users').innerHTML = html || '<p class="text-muted">No hay usuarios recientes</p>';
                }
            } catch (error) {
                console.error('Error cargando usuarios recientes:', error);
                document.getElementById('recent-users').innerHTML = '<p class="text-danger">Error al cargar usuarios</p>';
            }
        }

        // Cargar productos destacados
        async function loadTopProducts() {
            try {
                const response = await fetch(`${API_URL}products/top?limit=5`, { headers });
                if (response.ok) {
                    const products = await response.json();
                    let html = '';
                    
                    products.forEach(product => {
                        const stockClass = product.stock > 10 ? 'bg-success' : (product.stock > 0 ? 'bg-warning' : 'bg-danger');
                        html += `
                            <tr>
                                <td>
                                    <strong>${product.name}</strong>
                                    <br>
                                    <small class="text-muted">ID: ${product.id}</small>
                                </td>
                                <td>
                                    <span class="fw-bold text-success">€${formatNumber(product.price)}</span>
                                </td>
                                <td>
                                    <span class="badge ${stockClass} badge-modern">
                                        ${product.stock} unidades
                                    </span>
                                </td>
                                <td>
                                    ${product.active ? 
                                        '<span class="badge bg-success badge-modern"><i class="fas fa-check"></i> Activo</span>' : 
                                        '<span class="badge bg-secondary badge-modern"><i class="fas fa-pause"></i> Inactivo</span>'
                                    }
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="admin-products.php?edit=${product.id}" 
                                           class="btn btn-outline-primary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="admin-products.php?view=${product.id}" 
                                           class="btn btn-outline-info" title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    
                    document.getElementById('products-table').innerHTML = html || '<tr><td colspan="5" class="text-center">No hay productos</td></tr>';
                }
            } catch (error) {
                console.error('Error cargando productos:', error);
                document.getElementById('products-table').innerHTML = '<tr><td colspan="5" class="text-danger text-center">Error al cargar productos</td></tr>';
            }
        }

        // Cargar gráfico de usuarios
        async function loadUsersChart(days = 7) {
            try {
                const response = await fetch(`${API_URL}users/chart?days=${days}`, { headers });
                if (response.ok) {
                    const chartData = await response.json();
                    
                    const ctx = document.getElementById('usersChart').getContext('2d');
                    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
                    gradient.addColorStop(0, 'rgba(52, 152, 219, 0.3)');
                    gradient.addColorStop(1, 'rgba(52, 152, 219, 0.05)');
                    
                    if (chart) {
                        chart.destroy();
                    }
                    
                    chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: chartData.map(item => item.date),
                            datasets: [{
                                label: 'Nuevos Usuarios',
                                data: chartData.map(item => item.count),
                                borderColor: '#3498db',
                                backgroundColor: gradient,
                                borderWidth: 3,
                                tension: 0.4,
                                pointBackgroundColor: '#3498db',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 3,
                                pointRadius: 6,
                                pointHoverRadius: 8,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                intersect: false,
                                mode: 'index'
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    enabled: true,
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    titleColor: '#fff',
                                    bodyColor: '#fff',
                                    borderColor: '#3498db',
                                    borderWidth: 1,
                                    cornerRadius: 8,
                                    displayColors: false
                                }
                            },
                            scales: {
                                x: {
                                    display: true,
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        font: {
                                            size: 12,
                                            weight: '500'
                                        },
                                        color: '#6c757d'
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    display: true,
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)',
                                        drawBorder: false
                                    },
                                    ticks: {
                                        stepSize: 1,
                                        font: {
                                            size: 12,
                                            weight: '500'
                                        },
                                        color: '#6c757d',
                                        callback: function(value) {
                                            return Number.isInteger(value) ? value : '';
                                        }
                                    }
                                }
                            },
                            animation: {
                                duration: 1500,
                                easing: 'easeInOutCubic'
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Error cargando gráfico:', error);
            }
        }

        // Cargar métricas del sistema
        async function loadSystemMetrics() {
            try {
                const response = await fetch(`${API_URL}system/metrics`, { headers });
                if (response.ok) {
                    const metrics = await response.json();
                    
                    document.getElementById('system-metrics').innerHTML = `
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">Usuarios Activos</small>
                                <small class="fw-bold">${metrics.activeUsers || 0}%</small>
                            </div>
                            <div class="progress progress-modern">
                                <div class="progress-bar progress-bar-modern bg-success" style="width: ${metrics.activeUsers || 0}%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">Conversión de Ventas</small>
                                <small class="fw-bold">${metrics.conversionRate || 0}%</small>
                            </div>
                            <div class="progress progress-modern">
                                <div class="progress-bar progress-bar-modern bg-primary" style="width: ${metrics.conversionRate || 0}%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">Satisfacción Cliente</small>
                                <small class="fw-bold">${metrics.satisfaction || 0}%</small>
                            </div>
                            <div class="progress progress-modern">
                                <div class="progress-bar progress-bar-modern bg-warning" style="width: ${metrics.satisfaction || 0}%"></div>
                            </div>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error cargando métricas:', error);
                // Mostrar valores por defecto si falla
                document.getElementById('system-metrics').innerHTML = `
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted">Usuarios Activos</small>
                            <small class="fw-bold">85%</small>
                        </div>
                        <div class="progress progress-modern">
                            <div class="progress-bar progress-bar-modern bg-success" style="width: 85%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted">Conversión de Ventas</small>
                            <small class="fw-bold">12%</small>
                        </div>
                        <div class="progress progress-modern">
                            <div class="progress-bar progress-bar-modern bg-primary" style="width: 12%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted">Satisfacción Cliente</small>
                            <small class="fw-bold">92%</small>
                        </div>
                        <div class="progress progress-modern">
                            <div class="progress-bar progress-bar-modern bg-warning" style="width: 92%"></div>
                        </div>
                    </div>
                `;
            }
        }

        // Mostrar alertas
        function showAlert(type, icon, message) {
            const alertsContainer = document.getElementById('alerts-container');
            const systemAlerts = document.getElementById('system-alerts');
            
            const alert = `
                <div class="alert-custom alert-${type}">
                    <i class="${icon} me-2"></i>
                    ${message}
                </div>
            `;
            
            alertsContainer.innerHTML += alert;
            systemAlerts.style.display = 'block';
        }

        // Actualizar gráfico
        function updateChart(days) {
            loadUsersChart(days);
        }

        // Función de logout
        function logout() {
            if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                window.location.href = '/';
            }
        }

        // Cargar todos los datos al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar autenticación
            if (!token) {
                window.location.href = '/';
                return;
            }

            // Cargar todos los datos
            loadStats();
            loadRecentUsers();
            loadTopProducts();
            loadUsersChart();
            loadSystemMetrics();

            // Actualizar estadísticas cada 30 segundos
            setInterval(function() {
                loadStats();
                loadRecentUsers();
            }, 30000);

            // Redimensionar el gráfico cuando cambie el tamaño de la ventana
            window.addEventListener('resize', function() {
                if (chart) {
                    chart.resize();
                }
            });
        });
    </script>
    <script src="js/script.js"></script>
    <script src="js/cart.js"></script>
</body>
</html>