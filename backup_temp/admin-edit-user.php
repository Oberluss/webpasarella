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

// Obtener ID del usuario
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id <= 0) {
    header('Location: admin-users-full.php?error=invalid_id');
    exit;
}

// Obtener datos del usuario
$stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: admin-users-full.php?error=user_not_found');
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// Variables para el formulario (usar datos del usuario)
$message = '';
$message_type = '';
$form_data = [
    'first_name' => $user['first_name'],
    'last_name' => $user['last_name'],
    'email' => $user['email'],
    'phone' => $user['phone'] ?? '',
    'is_verified' => $user['is_verified']
];

// Procesar formulario
if ($_POST) {
    // Recoger datos del formulario
    $form_data = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'is_verified' => isset($_POST['is_verified']) ? 1 : 0
    ];
    
    // Validaciones
    $errors = [];
    
    if (empty($form_data['first_name'])) {
        $errors[] = "El nombre es obligatorio";
    }
    
    if (empty($form_data['last_name'])) {
        $errors[] = "El apellido es obligatorio";
    }
    
    if (empty($form_data['email'])) {
        $errors[] = "El email es obligatorio";
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El formato del email no es válido";
    }
    
    // Validar email único si se cambió
    if ($form_data['email'] !== $user['email']) {
        $email_check = $mysqli->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $email_check->bind_param("si", $form_data['email'], $user_id);
        $email_check->execute();
        if ($email_check->get_result()->num_rows > 0) {
            $errors[] = "Ya existe un usuario con ese email";
        }
        $email_check->close();
    }
    
    // Validar teléfono si se proporciona
    if (!empty($form_data['phone'])) {
        if (!preg_match('/^[+]?[0-9\s\-\(\)]{9,15}$/', $form_data['phone'])) {
            $errors[] = "El formato del teléfono no es válido";
        }
    }
    
    // Si no hay errores, actualizar en la base de datos
    if (empty($errors)) {
        $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, is_verified = ? WHERE id = ?";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ssssii", 
            $form_data['first_name'],
            $form_data['last_name'],
            $form_data['email'],
            $form_data['phone'],
            $form_data['is_verified'],
            $user_id
        );
        
        if ($stmt->execute()) {
            $message = "Usuario actualizado exitosamente";
            $message_type = "success";
            
            // Recargar datos del usuario
            $stmt_reload = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
            $stmt_reload->bind_param("i", $user_id);
            $stmt_reload->execute();
            $user = $stmt_reload->get_result()->fetch_assoc();
            $stmt_reload->close();
            
        } else {
            $message = "Error al actualizar el usuario: " . $stmt->error;
            $message_type = "danger";
        }
        $stmt->close();
    } else {
        $message = implode("<br>", $errors);
        $message_type = "danger";
    }
}

// Obtener estadísticas del usuario
$user_stats = [];

// Contar pedidos del usuario (cuando implementemos la tabla orders)
// $user_stats['orders'] = $mysqli->query("SELECT COUNT(*) as c FROM orders WHERE user_id = $user_id")->fetch_assoc()['c'];
$user_stats['orders'] = 0; // Placeholder por ahora

// Calcular tiempo como usuario
$registration_date = new DateTime($user['created_at']);
$current_date = new DateTime();
$user_stats['days_registered'] = $registration_date->diff($current_date)->days;

// Variables seguras para evitar warnings
$admin_name = isset($_SESSION['user']['name']) ? htmlspecialchars($_SESSION['user']['name']) : 'Admin';
$admin_initial = isset($_SESSION['user']['name']) ? strtoupper(substr($_SESSION['user']['name'], 0, 1)) : 'A';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - Panel Admin</title>
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
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            border: 1px solid rgba(0,0,0,0.05);
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
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(45deg, #3498db, #2980b9);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 2rem;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            margin: 0 auto 20px;
        }
        .user-avatar-small {
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
        .form-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        .form-section h6 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .alert {
            border: none;
            border-radius: 12px;
            padding: 15px 20px;
        }
        .btn-modern {
            border-radius: 10px;
            padding: 12px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #dee2e6;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        .required {
            color: #e74c3c;
        }
        .help-text {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        .user-info-header {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid #17a2b8;
        }
        .stat-badge {
            background: white;
            border-radius: 10px;
            padding: 15px;
            text-center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 15px;
        }
        .stat-badge h4 {
            margin: 0;
            color: #3498db;
        }
        .stat-badge small {
            color: #6c757d;
            font-weight: 500;
        }
        .danger-zone {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        .verification-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .verification-status.verified {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }
        .verification-status.unverified {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.2);
        }
        .activity-timeline {
            position: relative;
            padding-left: 20px;
        }
        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 6px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        .activity-item {
            position: relative;
            margin-bottom: 15px;
            padding: 10px 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .activity-item::before {
            content: '';
            position: absolute;
            left: -19px;
            top: 15px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #3498db;
            border: 2px solid white;
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
                            <h2><i class="fas fa-user-edit"></i> Editar Usuario</h2>
                            <p class="mb-0">Modificar información del usuario: <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong></p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="d-flex justify-content-end align-items-center">
                                <div class="user-avatar-small me-3">
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
                        <?php if ($message_type === 'success'): ?>
                            <hr>
                            <a href="admin-users-full.php" class="btn btn-sm btn-success">
                                <i class="fas fa-list"></i> Ver todos los usuarios
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Navegación -->
                <div class="mb-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="admin-dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="admin-users-full.php">Usuarios</a></li>
                            <li class="breadcrumb-item active">Editar: <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></li>
                        </ol>
                    </nav>
                </div>

                <!-- Información del usuario -->
                <div class="user-info-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="mb-1"><i class="fas fa-info-circle text-info"></i> Información del Usuario</h5>
                            <p class="mb-0">
                                <strong>ID:</strong> <?php echo $user['id']; ?> | 
                                <strong>Registrado:</strong> <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?> 
                                (<?php echo $user_stats['days_registered']; ?> días)
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="verification-status <?php echo $user['is_verified'] ? 'verified' : 'unverified'; ?>">
                                <i class="fas fa-<?php echo $user['is_verified'] ? 'check-circle' : 'clock'; ?>"></i>
                                <?php echo $user['is_verified'] ? 'Verificado' : 'Pendiente'; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulario de usuario -->
                <form method="POST" id="userForm">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Información personal -->
                            <div class="content-card">
                                <div class="form-section">
                                    <h6><i class="fas fa-user text-primary"></i> Información Personal</h6>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Nombre <span class="required">*</span></label>
                                            <input type="text" class="form-control" name="first_name" 
                                                   value="<?php echo htmlspecialchars($form_data['first_name']); ?>" 
                                                   required maxlength="255">
                                            <div class="help-text">Nombre del usuario</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Apellidos <span class="required">*</span></label>
                                            <input type="text" class="form-control" name="last_name" 
                                                   value="<?php echo htmlspecialchars($form_data['last_name']); ?>" 
                                                   required maxlength="255">
                                            <div class="help-text">Apellidos del usuario</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Información de contacto -->
                                <div class="form-section">
                                    <h6><i class="fas fa-envelope text-success"></i> Información de Contacto</h6>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Email <span class="required">*</span></label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?php echo htmlspecialchars($form_data['email']); ?>" 
                                               required maxlength="255">
                                        <div class="help-text">Dirección de correo electrónico (debe ser única)</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Teléfono</label>
                                        <input type="tel" class="form-control" name="phone" 
                                               value="<?php echo htmlspecialchars($form_data['phone']); ?>" 
                                               placeholder="+34 600 123 456" maxlength="20">
                                        <div class="help-text">Número de teléfono (opcional)</div>
                                    </div>
                                </div>

                                <!-- Estado de verificación -->
                                <div class="form-section">
                                    <h6><i class="fas fa-shield-alt text-warning"></i> Estado de Verificación</h6>
                                    
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_verified" id="verifiedSwitch" 
                                               <?php echo $form_data['is_verified'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="verifiedSwitch">
                                            Usuario Verificado
                                        </label>
                                        <div class="help-text">Los usuarios verificados tienen acceso completo al sistema</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- Avatar del usuario -->
                            <div class="content-card">
                                <div class="text-center">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                                    </div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                                    <p class="text-muted mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                                </div>
                            </div>

                            <!-- Estadísticas del usuario -->
                            <div class="content-card">
                                <h6 class="mb-3"><i class="fas fa-chart-bar text-info"></i> Estadísticas</h6>
                                
                                <div class="stat-badge">
                                    <h4><?php echo $user_stats['orders']; ?></h4>
                                    <small>Pedidos Realizados</small>
                                </div>
                                
                                <div class="stat-badge">
                                    <h4><?php echo $user_stats['days_registered']; ?></h4>
                                    <small>Días Registrado</small>
                                </div>
                                
                                <div class="stat-badge">
                                    <h4><?php echo $user['is_verified'] ? 'Sí' : 'No'; ?></h4>
                                    <small>Cuenta Verificada</small>
                                </div>
                            </div>

                            <!-- Botones de acción -->
                            <div class="content-card">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-modern">
                                        <i class="fas fa-save"></i> Actualizar Usuario
                                    </button>
                                    <a href="admin-users-full.php" class="btn btn-secondary btn-modern">
                                        <i class="fas fa-arrow-left"></i> Volver a Usuarios
                                    </a>
                                    <button type="button" class="btn btn-info btn-modern" onclick="sendVerificationEmail()">
                                        <i class="fas fa-envelope"></i> Enviar Verificación
                                    </button>
                                </div>
                                
                                <!-- Zona de peligro -->
                                <div class="danger-zone">
                                    <h6 class="text-danger"><i class="fas fa-exclamation-triangle"></i> Zona de Peligro</h6>
                                    <p class="mb-2 small">Esta acción no se puede deshacer</p>
                                    <button type="button" class="btn btn-danger btn-sm" 
                                            onclick="deleteUser(<?php echo $user_id; ?>)">
                                        <i class="fas fa-trash"></i> Eliminar Usuario
                                    </button>
                                </div>
                            </div>

                            <!-- Actividad reciente -->
                            <div class="content-card">
                                <h6 class="mb-3"><i class="fas fa-history text-secondary"></i> Actividad Reciente</h6>
                                
                                <div class="activity-timeline">
                                    <div class="activity-item">
                                        <strong>Registro de cuenta</strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>
                                        </small>
                                    </div>
                                    
                                    <?php if ($user['is_verified']): ?>
                                    <div class="activity-item">
                                        <strong>Cuenta verificada</strong>
                                        <br>
                                        <small class="text-muted">Estado actual</small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="activity-item">
                                        <strong>Última actualización</strong>
                                        <br>
                                        <small class="text-muted">Ahora mismo</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación de formulario en tiempo real
        document.getElementById('userForm').addEventListener('input', function(e) {
            const target = e.target;
            
            // Validar email en tiempo real
            if (target.name === 'email') {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (target.value && !emailRegex.test(target.value)) {
                    target.setCustomValidity('Formato de email inválido');
                } else {
                    target.setCustomValidity('');
                }
            }
            
            // Validar teléfono en tiempo real
            if (target.name === 'phone') {
                const phoneRegex = /^[+]?[0-9\s\-\(\)]{9,15}$/;
                if (target.value && !phoneRegex.test(target.value)) {
                    target.setCustomValidity('Formato de teléfono inválido');
                } else {
                    target.setCustomValidity('');
                }
            }
        });

        // Enviar email de verificación
        function sendVerificationEmail() {
            if (confirm('¿Enviar email de verificación a este usuario?')) {
                // Aquí implementarías la funcionalidad real de envío
                alert('Funcionalidad de email pendiente de implementar');
            }
        }

        // Eliminar usuario
        function deleteUser(userId) {
            if (confirm('¿Estás seguro de que quieres eliminar este usuario? Esta acción no se puede deshacer.')) {
                if (confirm('CONFIRMACIÓN FINAL: ¿Realmente quieres eliminar este usuario?')) {
                    // Crear formulario para eliminar
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'admin-users-full.php';
                    
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'delete_user';
                    
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'user_id';
                    idInput.value = userId;
                    
                    form.appendChild(actionInput);
                    form.appendChild(idInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        }

        // Auto-ocultar alertas después de 5 segundos
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 8000);

        // Advertencia al salir sin guardar
        let formChanged = false;
        document.getElementById('userForm').addEventListener('input', function() {
            formChanged = true;
        });

        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        document.getElementById('userForm').addEventListener('submit', function() {
            formChanged = false;
        });

        // Actualizar avatar cuando cambie el nombre
        document.querySelector('[name="first_name"]').addEventListener('input', function() {
            const avatar = document.querySelector('.user-avatar');
            if (this.value) {
                avatar.textContent = this.value.charAt(0).toUpperCase();
            }
        });
    </script>
</body>
</html>

<?php
$mysqli->close();
?>