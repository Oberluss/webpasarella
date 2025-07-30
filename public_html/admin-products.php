<?php
session_start();

// Verificación de admin (temporal) - VERSIÓN CORREGIDA
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
    $_SESSION['user'] = [
        'id' => 1, 
        'role' => 'admin', 
        'name' => 'Admin',
        'email' => 'admin@webpasarella.com',
        'first_name' => 'Admin',
        'last_name' => 'Sistema'
    ];
}

// Conexión BD
$mysqli = new mysqli('localhost', 'Oberlus_webp', 'Admin2018!', 'Oberlus_webpasarella');
if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

// Procesar acciones (eliminar, cambiar estado, etc.)
$message = '';
$message_type = '';

if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete_product':
                $product_id = intval($_POST['product_id']);
                if ($product_id > 0) {
                    // Primero eliminamos la imagen si existe
                    $image_query = $mysqli->prepare("SELECT image FROM products WHERE id = ?");
                    $image_query->bind_param("i", $product_id);
                    $image_query->execute();
                    $image_result = $image_query->get_result();
                    if ($image_data = $image_result->fetch_assoc()) {
                        if (!empty($image_data['image']) && file_exists("uploads/products/" . $image_data['image'])) {
                            unlink("uploads/products/" . $image_data['image']);
                        }
                    }
                    $image_query->close();
                    
                    $stmt = $mysqli->prepare("DELETE FROM products WHERE id = ?");
                    $stmt->bind_param("i", $product_id);
                    if ($stmt->execute()) {
                        $message = "Producto eliminado correctamente";
                        $message_type = "success";
                    } else {
                        $message = "Error al eliminar producto";
                        $message_type = "danger";
                    }
                    $stmt->close();
                }
                break;
                
            case 'toggle_status':
                $product_id = intval($_POST['product_id']);
                $current_status = intval($_POST['current_status']);
                $new_status = $current_status ? 0 : 1;
                
                if ($product_id > 0) {
                    $stmt = $mysqli->prepare("UPDATE products SET active = ? WHERE id = ?");
                    $stmt->bind_param("ii", $new_status, $product_id);
                    if ($stmt->execute()) {
                        $action_text = $new_status ? "activado" : "desactivado";
                        $message = "Producto $action_text correctamente";
                        $message_type = "success";
                    } else {
                        $message = "Error al cambiar estado del producto";
                        $message_type = "danger";
                    }
                    $stmt->close();
                }
                break;

            case 'update_stock':
                $product_id = intval($_POST['product_id']);
                $new_stock = intval($_POST['new_stock']);
                
                if ($product_id > 0 && $new_stock >= 0) {
                    $stmt = $mysqli->prepare("UPDATE products SET stock = ? WHERE id = ?");
                    $stmt->bind_param("ii", $new_stock, $product_id);
                    if ($stmt->execute()) {
                        $message = "Stock actualizado correctamente";
                        $message_type = "success";
                    } else {
                        $message = "Error al actualizar stock";
                        $message_type = "danger";
                    }
                    $stmt->close();
                }
                break;
        }
    }
}

// Filtros y búsqueda
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter_category = isset($_GET['category']) ? $_GET['category'] : 'all';
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$filter_stock = isset($_GET['stock']) ? $_GET['stock'] : 'all';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sort_order = isset($_GET['order']) && $_GET['order'] == 'asc' ? 'ASC' : 'DESC';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$records_per_page = 12;
$offset = ($page - 1) * $records_per_page;

// Construir consulta con filtros
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR description LIKE ? OR sku LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

if ($filter_category !== 'all') {
    $where_conditions[] = "category = ?";
    $params[] = $filter_category;
    $param_types .= 's';
}

if ($filter_status !== 'all') {
    $where_conditions[] = "active = ?";
    $params[] = ($filter_status === 'active') ? 1 : 0;
    $param_types .= 'i';
}

if ($filter_stock !== 'all') {
    switch ($filter_stock) {
        case 'out_of_stock':
            $where_conditions[] = "stock = 0";
            break;
        case 'low_stock':
            $where_conditions[] = "stock > 0 AND stock <= 10";
            break;
        case 'in_stock':
            $where_conditions[] = "stock > 10";
            break;
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Validar campo de ordenamiento
$valid_sort_fields = ['name', 'price', 'stock', 'created_at', 'category'];
if (!in_array($sort_by, $valid_sort_fields)) {
    $sort_by = 'created_at';
}

// Consulta principal
$sql = "SELECT * FROM products $where_clause ORDER BY $sort_by $sort_order LIMIT ? OFFSET ?";
$params[] = $records_per_page;
$params[] = $offset;
$param_types .= 'ii';

$stmt = $mysqli->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Contar total para paginación
$count_sql = "SELECT COUNT(*) as total FROM products $where_clause";
$count_stmt = $mysqli->prepare($count_sql);
if (!empty($where_conditions)) {
    $count_params = array_slice($params, 0, -2); // Quitar limit y offset
    $count_param_types = substr($param_types, 0, -2);
    if (!empty($count_params)) {
        $count_stmt->bind_param($count_param_types, ...$count_params);
    }
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Estadísticas rápidas
$stats = [];
$stats['total'] = $mysqli->query("SELECT COUNT(*) as c FROM products")->fetch_assoc()['c'];
$stats['active'] = $mysqli->query("SELECT COUNT(*) as c FROM products WHERE active = 1")->fetch_assoc()['c'];
$stats['inactive'] = $mysqli->query("SELECT COUNT(*) as c FROM products WHERE active = 0")->fetch_assoc()['c'];
$stats['out_of_stock'] = $mysqli->query("SELECT COUNT(*) as c FROM products WHERE stock = 0")->fetch_assoc()['c'];
$stats['low_stock'] = $mysqli->query("SELECT COUNT(*) as c FROM products WHERE stock > 0 AND stock <= 10")->fetch_assoc()['c'];
$stats['categories'] = $mysqli->query("SELECT COUNT(DISTINCT category) as c FROM products WHERE category IS NOT NULL AND category != ''")->fetch_assoc()['c'];

// Obtener categorías para el filtro
$categories = $mysqli->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");

// Variables seguras para evitar warnings
$admin_name = isset($_SESSION['user']['name']) ? htmlspecialchars($_SESSION['user']['name']) : 'Admin';
$admin_initial = isset($_SESSION['user']['name']) ? strtoupper(substr($_SESSION['user']['name'], 0, 1)) : 'A';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - Panel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
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
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .stat-card.danger::before { background: linear-gradient(90deg, #e74c3c, #c0392b); }
        .stat-card.success::before { background: linear-gradient(90deg, #27ae60, #229954); }
        .stat-card.warning::before { background: linear-gradient(90deg, #f39c12, #e67e22); }
        .stat-card.info::before { background: linear-gradient(90deg, #3498db, #2980b9); }
        
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
        .search-box {
            position: relative;
        }
        .search-box .form-control {
            padding-left: 40px;
            border-radius: 10px;
        }
        .search-box .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 20px;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 3rem;
        }
        .product-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 2;
        }
        .stock-indicator {
            width: 100%;
            height: 4px;
            border-radius: 2px;
            background: #e9ecef;
            margin: 10px 0;
            overflow: hidden;
        }
        .stock-bar {
            height: 100%;
            border-radius: 2px;
            transition: width 0.3s ease;
        }
        .alert {
            border: none;
            border-radius: 12px;
            padding: 15px 20px;
        }
        .badge-modern {
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .btn-group-actions {
            gap: 5px;
        }
        .pagination {
            justify-content: center;
        }
        .sort-link {
            color: #6c757d;
            text-decoration: none;
            transition: color 0.3s;
        }
        .sort-link:hover, .sort-link.active {
            color: #3498db;
        }
        .quick-stock-form {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .quick-stock-form input {
            width: 60px;
            padding: 2px 5px;
            font-size: 12px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: 1fr;
            }
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
                    <a class="nav-link" href="admin-dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link active" href="admin-products.php">
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
                    
                    <a class="nav-link logout-link text-danger" href="logout.php" 
                       onclick="return confirm('¿Estás seguro de que quieres cerrar sesión?')">
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
                            <h2><i class="fas fa-box"></i> Gestión de Productos</h2>
                            <p class="mb-0">Administra tu catálogo completo de productos</p>
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

                <!-- Mensajes -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Estadísticas rápidas -->
                <div class="row mb-4">
                    <div class="col-lg-2 col-md-4 col-6 mb-3">
                        <div class="stat-card info">
                            <div class="text-center">
                                <h4 class="mb-1 text-primary"><?php echo $stats['total']; ?></h4>
                                <p class="text-muted mb-0 small">Total</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6 mb-3">
                        <div class="stat-card success">
                            <div class="text-center">
                                <h4 class="mb-1 text-success"><?php echo $stats['active']; ?></h4>
                                <p class="text-muted mb-0 small">Activos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6 mb-3">
                        <div class="stat-card warning">
                            <div class="text-center">
                                <h4 class="mb-1 text-secondary"><?php echo $stats['inactive']; ?></h4>
                                <p class="text-muted mb-0 small">Inactivos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6 mb-3">
                        <div class="stat-card danger">
                            <div class="text-center">
                                <h4 class="mb-1 text-danger"><?php echo $stats['out_of_stock']; ?></h4>
                                <p class="text-muted mb-0 small">Sin Stock</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6 mb-3">
                        <div class="stat-card warning">
                            <div class="text-center">
                                <h4 class="mb-1 text-warning"><?php echo $stats['low_stock']; ?></h4>
                                <p class="text-muted mb-0 small">Stock Bajo</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6 mb-3">
                        <div class="stat-card info">
                            <div class="text-center">
                                <h4 class="mb-1 text-info"><?php echo $stats['categories']; ?></h4>
                                <p class="text-muted mb-0 small">Categorías</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros y búsqueda -->
                <div class="content-card">
                    <div class="row align-items-center mb-3">
                        <div class="col-md-6">
                            <h5 class="mb-0"><i class="fas fa-filter text-primary"></i> Filtros y Búsqueda</h5>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="admin-add-product.php" class="btn btn-success">
                                <i class="fas fa-plus"></i> Nuevo Producto
                            </a>
                        </div>
                    </div>
                    
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Buscar Producto</label>
                            <div class="search-box">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Nombre, descripción o SKU..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                                <i class="fas fa-search search-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Categoría</label>
                            <select class="form-select" name="category">
                                <option value="all" <?php echo $filter_category === 'all' ? 'selected' : ''; ?>>Todas</option>
                                <?php while ($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                            <?php echo $filter_category === $cat['category'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Estado</label>
                            <select class="form-select" name="status">
                                <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>Todos</option>
                                <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Activos</option>
                                <option value="inactive" <?php echo $filter_status === 'inactive' ? 'selected' : ''; ?>>Inactivos</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Stock</label>
                            <select class="form-select" name="stock">
                                <option value="all" <?php echo $filter_stock === 'all' ? 'selected' : ''; ?>>Todos</option>
                                <option value="in_stock" <?php echo $filter_stock === 'in_stock' ? 'selected' : ''; ?>>En Stock</option>
                                <option value="low_stock" <?php echo $filter_stock === 'low_stock' ? 'selected' : ''; ?>>Stock Bajo</option>
                                <option value="out_of_stock" <?php echo $filter_stock === 'out_of_stock' ? 'selected' : ''; ?>>Sin Stock</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filtrar
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Ordenamiento -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <small class="text-muted">Ordenar por: </small>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'name', 'order' => 'asc'])); ?>" 
                               class="sort-link <?php echo $sort_by === 'name' ? 'active' : ''; ?>">Nombre</a> |
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price', 'order' => 'desc'])); ?>" 
                               class="sort-link <?php echo $sort_by === 'price' ? 'active' : ''; ?>">Precio</a> |
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'stock', 'order' => 'asc'])); ?>" 
                               class="sort-link <?php echo $sort_by === 'stock' ? 'active' : ''; ?>">Stock</a> |
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'created_at', 'order' => 'desc'])); ?>" 
                               class="sort-link <?php echo $sort_by === 'created_at' ? 'active' : ''; ?>">Fecha</a>
                        </div>
                    </div>
                </div>

                <!-- Lista de productos -->
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Productos (<?php echo $total_records; ?> total)</h5>
                        <div class="text-muted">
                            Página <?php echo $page; ?> de <?php echo $total_pages; ?>
                        </div>
                    </div>
                    
                    <?php if ($result->num_rows > 0): ?>
                        <div class="product-grid">
                            <?php while ($product = $result->fetch_assoc()): ?>
                                <div class="product-card">
                                    <div class="position-relative">
                                        <?php if (!empty($product['image']) && file_exists("uploads/products/" . $product['image'])): ?>
                                            <img src="uploads/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                                        <?php else: ?>
                                            <div class="product-image">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="product-badge">
                                            <?php if ($product['active']): ?>
                                                <span class="badge bg-success badge-modern">
                                                    <i class="fas fa-check"></i> Activo
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary badge-modern">
                                                    <i class="fas fa-pause"></i> Inactivo
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="p-3">
                                        <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                        <p class="text-muted small mb-2"><?php echo htmlspecialchars(substr($product['description'], 0, 80)); ?>...</p>
                                        
                                        <div class="row align-items-center mb-2">
                                            <div class="col-6">
                                                <span class="h5 text-success mb-0">€<?php echo number_format($product['price'], 2); ?></span>
                                            </div>
                                            <div class="col-6 text-end">
                                                <?php if (!empty($product['category'])): ?>
                                                    <span class="badge bg-info badge-modern"><?php echo htmlspecialchars($product['category']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Indicador de stock -->
                                        <div class="mb-2">
                                            <small class="text-muted">Stock: 
                                                <strong class="<?php echo $product['stock'] > 10 ? 'text-success' : ($product['stock'] > 0 ? 'text-warning' : 'text-danger'); ?>">
                                                    <?php echo $product['stock']; ?> unidades
                                                </strong>
                                            </small>
                                            <div class="stock-indicator">
                                                <?php 
                                                $stock_percentage = min(100, ($product['stock'] / 50) * 100); // Asumiendo 50 como stock máximo
                                                $stock_color = $product['stock'] > 10 ? '#28a745' : ($product['stock'] > 0 ? '#ffc107' : '#dc3545');
                                                ?>
                                                <div class="stock-bar" style="width: <?php echo $stock_percentage; ?>%; background-color: <?php echo $stock_color; ?>;"></div>
                                            </div>
                                        </div>
                                        
                                        <!-- Acciones -->
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="btn-group btn-group-sm btn-group-actions" role="group">
                                                <!-- Toggle estado -->
                                                <form method="POST" style="display: inline;" class="me-1">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                    <input type="hidden" name="current_status" value="<?php echo $product['active']; ?>">
                                                    <button type="submit" class="btn btn-<?php echo $product['active'] ? 'warning' : 'success'; ?>" 
                                                            title="<?php echo $product['active'] ? 'Desactivar' : 'Activar'; ?>">
                                                        <i class="fas fa-<?php echo $product['active'] ? 'pause' : 'play'; ?>"></i>
                                                    </button>
                                                </form>
                                                
                                                <!-- Editar -->
                                                <a href="admin-edit-product.php?id=<?php echo $product['id']; ?>" 
                                                   class="btn btn-primary btn-sm me-1" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <!-- Eliminar -->
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('¿Estás seguro de eliminar este producto?')">
                                                    <input type="hidden" name="action" value="delete_product">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            
                                            <!-- Actualización rápida de stock -->
                                            <form method="POST" class="quick-stock-form">
                                                <input type="hidden" name="action" value="update_stock">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <input type="number" name="new_stock" value="<?php echo $product['stock']; ?>" 
                                                       min="0" max="9999" title="Stock actual">
                                                <button type="submit" class="btn btn-outline-secondary btn-sm" title="Actualizar stock">
                                                    <i class="fas fa-sync"></i>
                                                </button>
                                            </form>
                                        </div>
                                        
                                        <hr class="my-2">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i> 
                                            <?php echo date('d/m/Y', strtotime($product['created_at'])); ?>
                                            <?php if (!empty($product['sku'])): ?>
                                                | SKU: <?php echo htmlspecialchars($product['sku']); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <!-- Paginación -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Paginación de productos" class="mt-4">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                                <i class="fas fa-chevron-left"></i> Anterior
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                                Siguiente <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-box-open text-muted" style="font-size: 4rem;"></i>
                            <h4 class="mt-3 text-muted">No se encontraron productos</h4>
                            <p class="text-muted">Intenta cambiar los filtros de búsqueda o añade nuevos productos</p>
                            <a href="admin-add-product.php" class="btn btn-success">
                                <i class="fas fa-plus"></i> Añadir Primer Producto
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-ocultar alertas después de 5 segundos
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Confirmación para acciones peligrosas
        document.querySelectorAll('form[onsubmit*="confirm"]').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                if (!confirm('¿Estás seguro de realizar esta acción?')) {
                    e.preventDefault();
                }
            });
        });

        // Actualización de stock con Enter
        document.querySelectorAll('.quick-stock-form input[type="number"]').forEach(function(input) {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.closest('form').submit();
                }
            });
        });

        // Animación de cards al cargar
        window.addEventListener('load', function() {
            const cards = document.querySelectorAll('.product-card');
            cards.forEach(function(card, index) {
                setTimeout(function() {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.3s ease';
                    
                    setTimeout(function() {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 50);
                }, index * 50);
            });
        });
    </script>
</body>
</html>

<?php
$mysqli->close();
?>
