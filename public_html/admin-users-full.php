<?php
session_start();

// Verificación de admin (temporal)
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    $_SESSION['user'] = ['id' => 1, 'role' => 'admin', 'name' => 'Admin'];
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
            case 'delete_user':
                $user_id = intval($_POST['user_id']);
                if ($user_id > 0) {
                    $stmt = $mysqli->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    if ($stmt->execute()) {
                        $message = "Usuario eliminado correctamente";
                        $message_type = "success";
                    } else {
                        $message = "Error al eliminar usuario";
                        $message_type = "danger";
                    }
                    $stmt->close();
                }
                break;
                
            case 'toggle_verification':
                $user_id = intval($_POST['user_id']);
                $current_status = intval($_POST['current_status']);
                $new_status = $current_status ? 0 : 1;
                
                if ($user_id > 0) {
                    $stmt = $mysqli->prepare("UPDATE users SET is_verified = ? WHERE id = ?");
                    $stmt->bind_param("ii", $new_status, $user_id);
                    if ($stmt->execute()) {
                        $action_text = $new_status ? "verificado" : "desverificado";
                        $message = "Usuario $action_text correctamente";
                        $message_type = "success";
                    } else {
                        $message = "Error al cambiar estado de verificación";
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
$filter_verified = isset($_GET['verified']) ? $_GET['verified'] : 'all';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Construir consulta con filtros
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

if ($filter_verified !== 'all') {
    $where_conditions[] = "is_verified = ?";
    $params[] = ($filter_verified === 'verified') ? 1 : 0;
    $param_types .= 'i';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Consulta principal
$sql = "SELECT * FROM users $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
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
$count_sql = "SELECT COUNT(*) as total FROM users $where_clause";
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
$stats['total'] = $mysqli->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$stats['verified'] = $mysqli->query("SELECT COUNT(*) as c FROM users WHERE is_verified = 1")->fetch_assoc()['c'];
$stats['unverified'] = $mysqli->query("SELECT COUNT(*) as c FROM users WHERE is_verified = 0")->fetch_assoc()['c'];
$stats['today'] = $mysqli->query("SELECT COUNT(*) as c FROM users WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['c'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Panel Admin</title>
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
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #3498db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
        }
        .table-actions {
            white-space: nowrap;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .search-box {
            position: relative;
        }
        .search-box .form-control {
            padding-left: 40px;
        }
        .search-box .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .filter-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
        }
        .pagination {
            justify-content: center;
        }
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
        }
        .alert {
            border: none;
            border-radius: 10px;
            padding: 15px 20px;
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
                    <a class="nav-link" href="admin-products.php">
                        <i class="fas fa-box"></i> Productos
                    </a>
                    <a class="nav-link" href="admin-orders.php">
                        <i class="fas fa-shopping-cart"></i> Pedidos
                    </a>
                    <a class="nav-link active" href="admin-users-full.php">
                        <i class="fas fa-users"></i> Usuarios
                    </a>
                    <a class="nav-link" href="#">
                        <i class="fas fa-chart-bar"></i> Reportes
                    </a>
                    <a class="nav-link" href="#">
                        <i class="fas fa-cog"></i> Configuración
                    </a>
                    
                    <hr class="bg-white mx-3">
                    
                    <!-- NAVEGACIÓN MEJORADA -->
                    <div class="px-3 mb-2">
                        <small class="text-muted text-uppercase fw-bold" style="letter-spacing: 0.5px; font-size: 10px;">Navegación</small>
                    </div>
                    
                    <!-- Botón destacado para volver al Dashboard -->
                    <a class="nav-link dashboard-link" href="admin-dashboard.php">
                        <i class="fas fa-home"></i> Panel Principal
                    </a>
                    
                    <!-- Botón para ver el sitio web -->
                    <a class="nav-link site-link" href="/" target="_blank">
                        <i class="fas fa-external-link-alt"></i> Ver Sitio Web
                    </a>
                    
                    <!-- Botón de logout -->
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
                    <h2><i class="fas fa-users"></i> Gestión de Usuarios</h2>
                    <p class="mb-0">Administra todos los usuarios registrados en el sistema</p>
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
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1 text-primary"><?php echo $stats['total']; ?></h3>
                                    <p class="text-muted mb-0">Total Usuarios</p>
                                </div>
                                <div class="text-primary" style="font-size: 2rem;">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1 text-success"><?php echo $stats['verified']; ?></h3>
                                    <p class="text-muted mb-0">Verificados</p>
                                </div>
                                <div class="text-success" style="font-size: 2rem;">
                                    <i class="fas fa-user-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1 text-warning"><?php echo $stats['unverified']; ?></h3>
                                    <p class="text-muted mb-0">Sin Verificar</p>
                                </div>
                                <div class="text-warning" style="font-size: 2rem;">
                                    <i class="fas fa-user-clock"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1 text-info"><?php echo $stats['today']; ?></h3>
                                    <p class="text-muted mb-0">Hoy</p>
                                </div>
                                <div class="text-info" style="font-size: 2rem;">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros y búsqueda -->
                <div class="content-card">
                    <form method="GET" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Buscar Usuario</label>
                            <div class="search-box">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Buscar por nombre o email..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                                <i class="fas fa-search search-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Estado de Verificación</label>
                            <select class="form-select" name="verified">
                                <option value="all" <?php echo $filter_verified === 'all' ? 'selected' : ''; ?>>Todos</option>
                                <option value="verified" <?php echo $filter_verified === 'verified' ? 'selected' : ''; ?>>Verificados</option>
                                <option value="unverified" <?php echo $filter_verified === 'unverified' ? 'selected' : ''; ?>>Sin verificar</option>
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
                </div>

                <!-- Tabla de usuarios -->
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Lista de Usuarios (<?php echo $total_records; ?> total)</h5>
                        <a href="admin-add-user.php" class="btn btn-success">
                            <i class="fas fa-plus"></i> Nuevo Usuario
                        </a>
                    </div>
                    
                    <?php if ($result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Usuario</th>
                                        <th>Email</th>
                                        <th>Teléfono</th>
                                        <th>Estado</th>
                                        <th>Fecha Registro</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($user = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="fw-bold"><?php echo $user['id']; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar me-3">
                                                        <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            ID: <?php echo $user['id']; ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone'] ?? 'No especificado'); ?></td>
                                            <td>
                                                <?php if ($user['is_verified']): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check-circle"></i> Verificado
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-clock"></i> Pendiente
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>
                                                </small>
                                            </td>
                                            <td class="table-actions">
                                                <div class="btn-group" role="group">
                                                    <!-- Toggle verificación -->
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="toggle_verification">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="current_status" value="<?php echo $user['is_verified']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-<?php echo $user['is_verified'] ? 'warning' : 'success'; ?>" 
                                                                title="<?php echo $user['is_verified'] ? 'Desverificar' : 'Verificar'; ?>">
                                                            <i class="fas fa-<?php echo $user['is_verified'] ? 'times' : 'check'; ?>"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    <!-- Editar -->
                                                    <a href="admin-edit-user.php?id=<?php echo $user['id']; ?>" 
                                                       class="btn btn-sm btn-primary" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <!-- Eliminar -->
                                                    <form method="POST" style="display: inline;" 
                                                          onsubmit="return confirm('¿Estás seguro de eliminar este usuario?')">
                                                        <input type="hidden" name="action" value="delete_user">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Paginación de usuarios" class="mt-4">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&verified=<?php echo $filter_verified; ?>">
                                                <i class="fas fa-chevron-left"></i> Anterior
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&verified=<?php echo $filter_verified; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&verified=<?php echo $filter_verified; ?>">
                                                Siguiente <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users text-muted" style="font-size: 4rem;"></i>
                            <h4 class="mt-3 text-muted">No se encontraron usuarios</h4>
                            <p class="text-muted">Intenta cambiar los filtros de búsqueda</p>
                            <a href="admin-users-full.php" class="btn btn-primary">
                                <i class="fas fa-refresh"></i> Ver todos los usuarios
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
    </script>
</body>
</html>

<?php
$mysqli->close();
?>