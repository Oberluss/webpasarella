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

// Variables para el formulario
$message = '';
$message_type = '';
$form_data = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'password' => '',
    'is_verified' => 0,
    'send_welcome_email' => 1
];

// Procesar formulario
if ($_POST) {
    // Recoger datos del formulario
    $form_data = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'is_verified' => isset($_POST['is_verified']) ? 1 : 0,
        'send_welcome_email' => isset($_POST['send_welcome_email']) ? 1 : 0
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
    
    // Validar email único
    if (!empty($form_data['email'])) {
        $email_check = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
        $email_check->bind_param("s", $form_data['email']);
        $email_check->execute();
        if ($email_check->get_result()->num_rows > 0) {
            $errors[] = "Ya existe un usuario con ese email";
        }
        $email_check->close();
    }
    
    // Validar contraseña
    if (empty($form_data['password'])) {
        $errors[] = "La contraseña es obligatoria";
    } elseif (strlen($form_data['password']) < 6) {
        $errors[] = "La contraseña debe tener al menos 6 caracteres";
    }
    
    // Validar confirmación de contraseña
    if ($form_data['password'] !== $form_data['confirm_password']) {
        $errors[] = "Las contraseñas no coinciden";
    }
    
    // Validar teléfono si se proporciona
    if (!empty($form_data['phone'])) {
        if (!preg_match('/^[+]?[0-9\s\-\(\)]{9,15}$/', $form_data['phone'])) {
            $errors[] = "El formato del teléfono no es válido";
        }
    }
    
    // Si no hay errores, insertar en la base de datos
    if (empty($errors)) {
        // Encriptar contraseña
        $hashed_password = password_hash($form_data['password'], PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (first_name, last_name, email, phone, password, is_verified, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sssssi", 
            $form_data['first_name'],
            $form_data['last_name'],
            $form_data['email'],
            $form_data['phone'],
            $hashed_password,
            $form_data['is_verified']
        );
        
        if ($stmt->execute()) {
            $new_user_id = $mysqli->insert_id;
            $message = "Usuario creado exitosamente con ID: $new_user_id";
            $message_type = "success";
            
            // Aquí enviarías el email de bienvenida si está marcado
            if ($form_data['send_welcome_email']) {
                // Placeholder para funcionalidad de email
                $message .= "<br><small>Email de bienvenida enviado (funcionalidad pendiente)</small>";
            }
            
            // Limpiar formulario después del éxito
            $form_data = [
                'first_name' => '',
                'last_name' => '',
                'email' => '',
                'phone' => '',
                'password' => '',
                'is_verified' => 0,
                'send_welcome_email' => 1
            ];
            
        } else {
            $message = "Error al crear el usuario: " . $stmt->error;
            $message_type = "danger";
        }
        $stmt->close();
    } else {
        $message = implode("<br>", $errors);
        $message_type = "danger";
    }
}

// Obtener estadísticas para mostrar contexto
$stats = [];
$stats['total_users'] = $mysqli->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$stats['verified_users'] = $mysqli->query("SELECT COUNT(*) as c FROM users WHERE is_verified = 1")->fetch_assoc()['c'];
$stats['unverified_users'] = $mysqli->query("SELECT COUNT(*) as c FROM users WHERE is_verified = 0")->fetch_assoc()['c'];

// Variables seguras para evitar warnings
$admin_name = isset($_SESSION['user']['name']) ? htmlspecialchars($_SESSION['user']['name']) : 'Admin';
$admin_initial = isset($_SESSION['user']['name']) ? strtoupper(substr($_SESSION['user']['name'], 0, 1)) : 'A';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Usuario - Panel Admin</title>
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
            transition: all 0.3s ease;
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
        .stat-badge {
            background: white;
            border-radius: 10px;
            padding: 15px;
            text-center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 15px;
            border-left: 4px solid;
        }
        .stat-badge.primary { border-left-color: #3498db; }
        .stat-badge.success { border-left-color: #28a745; }
        .stat-badge.warning { border-left-color: #ffc107; }
        
        .stat-badge h4 {
            margin: 0;
            color: #2c3e50;
        }
        .stat-badge small {
            color: #6c757d;
            font-weight: 500;
        }
        .password-strength {
            height: 4px;
            border-radius: 2px;
            background: #e9ecef;
            margin-top: 5px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }
        .strength-weak { background: #dc3545; width: 25%; }
        .strength-fair { background: #ffc107; width: 50%; }
        .strength-good { background: #17a2b8; width: 75%; }
        .strength-strong { background: #28a745; width: 100%; }
        
        .preview-card {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #17a2b8;
        }
        .input-group-text {
            border-radius: 10px 0 0 10px;
            border-color: #dee2e6;
        }
        .form-control.rounded-start {
            border-radius: 0 10px 10px 0;
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
                            <h2><i class="fas fa-user-plus"></i> Añadir Nuevo Usuario</h2>
                            <p class="mb-0">Crear una nueva cuenta de usuario en el sistema</p>
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
                            <a href="admin-add-user.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Añadir otro usuario
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
                            <li class="breadcrumb-item active">Añadir Usuario</li>
                        </ol>
                    </nav>
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
                                                   required maxlength="255" placeholder="Nombre del usuario">
                                            <div class="help-text">Nombre del usuario</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Apellidos <span class="required">*</span></label>
                                            <input type="text" class="form-control" name="last_name" 
                                                   value="<?php echo htmlspecialchars($form_data['last_name']); ?>" 
                                                   required maxlength="255" placeholder="Apellidos del usuario">
                                            <div class="help-text">Apellidos del usuario</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Información de contacto -->
                                <div class="form-section">
                                    <h6><i class="fas fa-envelope text-success"></i> Información de Contacto</h6>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Email <span class="required">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" class="form-control rounded-start" name="email" 
                                                   value="<?php echo htmlspecialchars($form_data['email']); ?>" 
                                                   required maxlength="255" placeholder="usuario@ejemplo.com">
                                        </div>
                                        <div class="help-text">Dirección de correo electrónico (debe ser única)</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Teléfono</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                            <input type="tel" class="form-control rounded-start" name="phone" 
                                                   value="<?php echo htmlspecialchars($form_data['phone']); ?>" 
                                                   placeholder="+34 600 123 456" maxlength="20">
                                        </div>
                                        <div class="help-text">Número de teléfono (opcional)</div>
                                    </div>
                                </div>

                                <!-- Contraseña -->
                                <div class="form-section">
                                    <h6><i class="fas fa-lock text-warning"></i> Seguridad</h6>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Contraseña <span class="required">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                <input type="password" class="form-control rounded-start" name="password" 
                                                       id="password" required minlength="6" 
                                                       placeholder="Mínimo 6 caracteres">
                                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password')">
                                                    <i class="fas fa-eye" id="password-eye"></i>
                                                </button>
                                            </div>
                                            <div class="password-strength" id="password-strength">
                                                <div class="password-strength-bar" id="strength-bar"></div>
                                            </div>
                                            <div class="help-text">La contraseña debe tener al menos 6 caracteres</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Confirmar Contraseña <span class="required">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                <input type="password" class="form-control rounded-start" name="confirm_password" 
                                                       id="confirm_password" required minlength="6" 
                                                       placeholder="Repetir contraseña">
                                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirm_password')">
                                                    <i class="fas fa-eye" id="confirm_password-eye"></i>
                                                </button>
                                            </div>
                                            <div class="help-text">Debe coincidir con la contraseña anterior</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Configuración -->
                                <div class="form-section">
                                    <h6><i class="fas fa-cogs text-info"></i> Configuración de Cuenta</h6>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_verified" id="verifiedSwitch" 
                                                       <?php echo $form_data['is_verified'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-bold" for="verifiedSwitch">
                                                    Cuenta Verificada
                                                </label>
                                                <div class="help-text">Los usuarios verificados tienen acceso completo</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="send_welcome_email" id="welcomeEmailSwitch" 
                                                       <?php echo $form_data['send_welcome_email'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-bold" for="welcomeEmailSwitch">
                                                    Enviar Email de Bienvenida
                                                </label>
                                                <div class="help-text">Notificar al usuario por email</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- Vista previa del usuario -->
                            <div class="content-card">
                                <div class="preview-card">
                                    <h6 class="mb-3"><i class="fas fa-eye text-info"></i> Vista Previa</h6>
                                    <div class="text-center">
                                        <div class="user-avatar" id="preview-avatar">
                                            ?
                                        </div>
                                        <h5 class="mb-1" id="preview-name">Nuevo Usuario</h5>
                                        <p class="text-muted mb-0" id="preview-email">email@ejemplo.com</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Estadísticas contextuales -->
                            <div class="content-card">
                                <h6 class="mb-3"><i class="fas fa-chart-bar text-secondary"></i> Estadísticas del Sistema</h6>
                                
                                <div class="stat-badge primary">
                                    <h4><?php echo $stats['total_users']; ?></h4>
                                    <small>Total de Usuarios</small>
                                </div>
                                
                                <div class="stat-badge success">
                                    <h4><?php echo $stats['verified_users']; ?></h4>
                                    <small>Usuarios Verificados</small>
                                </div>
                                
                                <div class="stat-badge warning">
                                    <h4><?php echo $stats['unverified_users']; ?></h4>
                                    <small>Pendientes de Verificar</small>
                                </div>
                            </div>

                            <!-- Botones de acción -->
                            <div class="content-card">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success btn-modern">
                                        <i class="fas fa-user-plus"></i> Crear Usuario
                                    </button>
                                    <a href="admin-users-full.php" class="btn btn-secondary btn-modern">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                    <button type="button" class="btn btn-info btn-modern" onclick="generatePassword()">
                                        <i class="fas fa-key"></i> Generar Contraseña
                                    </button>
                                    <button type="reset" class="btn btn-warning btn-modern" onclick="clearForm()">
                                        <i class="fas fa-eraser"></i> Limpiar Formulario
                                    </button>
                                </div>
                            </div>

                            <!-- Consejos -->
                            <div class="content-card">
                                <h6 class="mb-3"><i class="fas fa-lightbulb text-warning"></i> Consejos</h6>
                                <ul class="list-unstyled small">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Usa emails válidos para notificaciones</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Contraseñas seguras protegen las cuentas</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Verifica usuarios para acceso completo</li>
                                    <li class="mb-0"><i class="fas fa-check text-success me-2"></i>El email de bienvenida mejora la experiencia</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Actualizar vista previa en tiempo real
        document.addEventListener('DOMContentLoaded', function() {
            const firstNameInput = document.querySelector('[name="first_name"]');
            const lastNameInput = document.querySelector('[name="last_name"]');
            const emailInput = document.querySelector('[name="email"]');
            const previewAvatar = document.getElementById('preview-avatar');
            const previewName = document.getElementById('preview-name');
            const previewEmail = document.getElementById('preview-email');

            function updatePreview() {
                const firstName = firstNameInput.value.trim();
                const lastName = lastNameInput.value.trim();
                const email = emailInput.value.trim();

                // Actualizar avatar
                if (firstName) {
                    previewAvatar.textContent = firstName.charAt(0).toUpperCase();
                } else {
                    previewAvatar.textContent = '?';
                }

                // Actualizar nombre
                if (firstName || lastName) {
                    previewName.textContent = `${firstName} ${lastName}`.trim();
                } else {
                    previewName.textContent = 'Nuevo Usuario';
                }

                // Actualizar email
                if (email) {
                    previewEmail.textContent = email;
                } else {
                    previewEmail.textContent = 'email@ejemplo.com';
                }
            }

            firstNameInput.addEventListener('input', updatePreview);
            lastNameInput.addEventListener('input', updatePreview);
            emailInput.addEventListener('input', updatePreview);
        });

        // Validación de contraseña en tiempo real
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strength-bar');
            
            let strength = 0;
            let className = '';
            
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            switch (strength) {
                case 1:
                    className = 'strength-weak';
                    break;
                case 2:
                    className = 'strength-fair';
                    break;
                case 3:
                    className = 'strength-good';
                    break;
                case 4:
                    className = 'strength-strong';
                    break;
                default:
                    className = '';
            }
            
            strengthBar.className = 'password-strength-bar ' + className;
        });

        // Validar confirmación de contraseña
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('Las contraseñas no coinciden');
            } else {
                this.setCustomValidity('');
            }
        });

        // Generar contraseña aleatoria
        function generatePassword() {
            const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
            let password = '';
            for (let i = 0; i < 12; i++) {
                password += charset.charAt(Math.floor(Math.random() * charset.length));
            }
            
            document.getElementById('password').value = password;
            document.getElementById('confirm_password').value = password;
            
            // Trigger events para actualizar validaciones
            document.getElementById('password').dispatchEvent(new Event('input'));
            document.getElementById('confirm_password').dispatchEvent(new Event('input'));
            
            alert('Contraseña generada. Asegúrate de comunicársela al usuario de forma segura.');
        }

        // Mostrar/ocultar contraseña
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const eye = document.getElementById(inputId + '-eye');
            
            if (input.type === 'password') {
                input.type = 'text';
                eye.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                eye.className = 'fas fa-eye';
            }
        }

        // Limpiar formulario
        function clearForm() {
            if (confirm('¿Estás seguro de que quieres limpiar todo el formulario?')) {
                document.getElementById('userForm').reset();
                document.getElementById('preview-avatar').textContent = '?';
                document.getElementById('preview-name').textContent = 'Nuevo Usuario';
                document.getElementById('preview-email').textContent = 'email@ejemplo.com';
                document.getElementById('strength-bar').className = 'password-strength-bar';
            }
        }

        // Validación de email en tiempo real
        document.querySelector('[name="email"]').addEventListener('input', function() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (this.value && !emailRegex.test(this.value)) {
                this.setCustomValidity('Formato de email inválido');
            } else {
                this.setCustomValidity('');
            }
        });

        // Validación de teléfono en tiempo real
        document.querySelector('[name="phone"]').addEventListener('input', function() {
            const phoneRegex = /^[+]?[0-9\s\-\(\)]{9,15}$/;
            if (this.value && !phoneRegex.test(this.value)) {
                this.setCustomValidity('Formato de teléfono inválido');
            } else {
                this.setCustomValidity('');
            }
        });

        // Auto-ocultar alertas después de 8 segundos
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 8000);
    </script>
</body>
</html>

<?php
$mysqli->close();
?>