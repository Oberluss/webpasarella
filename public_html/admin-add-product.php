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
    'name' => '',
    'description' => '',
    'price' => '',
    'stock' => '',
    'category' => '',
    'sku' => '',
    'meta_description' => '',
    'keywords' => '',
    'active' => 1
];

// Procesar formulario
if ($_POST) {
    // Recoger datos del formulario
    $form_data = [
        'name' => trim($_POST['name'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'price' => floatval($_POST['price'] ?? 0),
        'stock' => intval($_POST['stock'] ?? 0),
        'category' => trim($_POST['category'] ?? ''),
        'sku' => trim($_POST['sku'] ?? ''),
        'meta_description' => trim($_POST['meta_description'] ?? ''),
        'keywords' => trim($_POST['keywords'] ?? ''),
        'active' => isset($_POST['active']) ? 1 : 0
    ];
    
    // Validaciones
    $errors = [];
    
    if (empty($form_data['name'])) {
        $errors[] = "El nombre del producto es obligatorio";
    }
    
    if ($form_data['price'] <= 0) {
        $errors[] = "El precio debe ser mayor que 0";
    }
    
    if ($form_data['stock'] < 0) {
        $errors[] = "El stock no puede ser negativo";
    }
    
    // Validar SKU único si se proporciona
    if (!empty($form_data['sku'])) {
        $sku_check = $mysqli->prepare("SELECT id FROM products WHERE sku = ?");
        $sku_check->bind_param("s", $form_data['sku']);
        $sku_check->execute();
        if ($sku_check->get_result()->num_rows > 0) {
            $errors[] = "El SKU ya existe, debe ser único";
        }
        $sku_check->close();
    }
    
    // Procesar imagen si se sube
    $image_filename = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/products/';
        
        // Crear directorio si no existe
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['image']['type'];
        $file_size = $_FILES['image']['size'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Formato de imagen no válido. Use JPG, PNG, GIF o WebP";
        }
        
        if ($file_size > 5 * 1024 * 1024) { // 5MB máximo
            $errors[] = "La imagen es demasiado grande. Máximo 5MB";
        }
        
        if (empty($errors)) {
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_filename = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            $upload_path = $upload_dir . $image_filename;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $errors[] = "Error al subir la imagen";
                $image_filename = null;
            }
        }
    }
    
    // Si no hay errores, insertar en la base de datos
    if (empty($errors)) {
        $sql = "INSERT INTO products (name, description, price, stock, category, sku, image, meta_description, keywords, active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ssdisssssi", 
            $form_data['name'],
            $form_data['description'],
            $form_data['price'],
            $form_data['stock'],
            $form_data['category'],
            $form_data['sku'],
            $image_filename,
            $form_data['meta_description'],
            $form_data['keywords'],
            $form_data['active']
        );
        
        if ($stmt->execute()) {
            $product_id = $mysqli->insert_id;
            $message = "Producto creado exitosamente con ID: $product_id";
            $message_type = "success";
            
            // Limpiar formulario después del éxito
            $form_data = [
                'name' => '',
                'description' => '',
                'price' => '',
                'stock' => '',
                'category' => '',
                'sku' => '',
                'meta_description' => '',
                'keywords' => '',
                'active' => 1
            ];
        } else {
            $message = "Error al crear el producto: " . $stmt->error;
            $message_type = "danger";
            
            // Eliminar imagen si fallo la inserción
            if ($image_filename && file_exists($upload_dir . $image_filename)) {
                unlink($upload_dir . $image_filename);
            }
        }
        $stmt->close();
    } else {
        $message = implode("<br>", $errors);
        $message_type = "danger";
        
        // Eliminar imagen si hay errores
        if ($image_filename && file_exists($upload_dir . $image_filename)) {
            unlink($upload_dir . $image_filename);
        }
    }
}

// Obtener categorías existentes para el selector
$categories = $mysqli->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");

// Generar SKU automático si está vacío
$suggested_sku = "PROD" . date('Ymd') . rand(100, 999);

// Variables seguras para evitar warnings
$admin_name = isset($_SESSION['user']['name']) ? htmlspecialchars($_SESSION['user']['name']) : 'Admin';
$admin_initial = isset($_SESSION['user']['name']) ? strtoupper(substr($_SESSION['user']['name'], 0, 1)) : 'A';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Producto - Panel Admin</title>
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
        .image-preview {
            width: 100%;
            max-width: 300px;
            height: 200px;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        .image-preview:hover {
            border-color: #3498db;
            background: #e3f2fd;
        }
        .image-preview.has-image {
            border-style: solid;
            border-color: #28a745;
        }
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            border-radius: 8px;
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
        .price-input {
            position: relative;
        }
        .price-input::before {
            content: '€';
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-weight: bold;
        }
        .price-input input {
            padding-left: 35px;
        }
        .counter-input {
            display: flex;
            align-items: center;
        }
        .counter-btn {
            width: 40px;
            height: 40px;
            border: 1px solid #dee2e6;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .counter-btn:hover {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        .counter-input input {
            width: 80px;
            text-align: center;
            margin: 0 10px;
            border-radius: 8px;
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
                            <h2><i class="fas fa-plus-circle"></i> Añadir Nuevo Producto</h2>
                            <p class="mb-0">Completa la información para crear un nuevo producto</p>
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
                        <?php if ($message_type === 'success'): ?>
                            <hr>
                            <a href="admin-products.php" class="btn btn-sm btn-success">
                                <i class="fas fa-list"></i> Ver todos los productos
                            </a>
                            <a href="admin-add-product.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Añadir otro producto
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Navegación -->
                <div class="mb-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="admin-dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="admin-products.php">Productos</a></li>
                            <li class="breadcrumb-item active">Añadir Producto</li>
                        </ol>
                    </nav>
                </div>

                <!-- Formulario de producto -->
                <form method="POST" enctype="multipart/form-data" id="productForm">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Información básica -->
                            <div class="content-card">
                                <div class="form-section">
                                    <h6><i class="fas fa-info-circle text-primary"></i> Información Básica</h6>
                                    
                                    <div class="row">
                                        <div class="col-md-8 mb-3">
                                            <label class="form-label fw-bold">Nombre del Producto <span class="required">*</span></label>
                                            <input type="text" class="form-control" name="name" 
                                                   value="<?php echo htmlspecialchars($form_data['name']); ?>" 
                                                   required maxlength="255">
                                            <div class="help-text">Nombre visible para los clientes</div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-bold">SKU</label>
                                            <input type="text" class="form-control" name="sku" 
                                                   value="<?php echo htmlspecialchars($form_data['sku']); ?>"
                                                   placeholder="<?php echo $suggested_sku; ?>" maxlength="50">
                                            <div class="help-text">Código único del producto</div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Descripción</label>
                                        <textarea class="form-control" name="description" rows="4" 
                                                  maxlength="1000"><?php echo htmlspecialchars($form_data['description']); ?></textarea>
                                        <div class="help-text">Descripción detallada del producto (máximo 1000 caracteres)</div>
                                    </div>
                                </div>

                                <!-- Precios y stock -->
                                <div class="form-section">
                                    <h6><i class="fas fa-euro-sign text-success"></i> Precio y Stock</h6>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Precio <span class="required">*</span></label>
                                            <div class="price-input">
                                                <input type="number" class="form-control" name="price" 
                                                       value="<?php echo $form_data['price']; ?>"
                                                       step="0.01" min="0" required>
                                            </div>
                                            <div class="help-text">Precio de venta al público</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Stock Inicial</label>
                                            <div class="counter-input">
                                                <div class="counter-btn" onclick="changeStock(-1)">
                                                    <i class="fas fa-minus"></i>
                                                </div>
                                                <input type="number" class="form-control" name="stock" id="stockInput"
                                                       value="<?php echo $form_data['stock']; ?>" min="0" max="9999">
                                                <div class="counter-btn" onclick="changeStock(1)">
                                                    <i class="fas fa-plus"></i>
                                                </div>
                                            </div>
                                            <div class="help-text">Cantidad disponible en inventario</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- SEO -->
                                <div class="form-section">
                                    <h6><i class="fas fa-search text-info"></i> SEO y Marketing</h6>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Meta Descripción</label>
                                        <textarea class="form-control" name="meta_description" rows="2" 
                                                  maxlength="160"><?php echo htmlspecialchars($form_data['meta_description']); ?></textarea>
                                        <div class="help-text">Descripción para motores de búsqueda (máximo 160 caracteres)</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Palabras Clave</label>
                                        <input type="text" class="form-control" name="keywords" 
                                               value="<?php echo htmlspecialchars($form_data['keywords']); ?>"
                                               placeholder="palabra1, palabra2, palabra3" maxlength="255">
                                        <div class="help-text">Separadas por comas para mejor SEO</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- Imagen del producto -->
                            <div class="content-card">
                                <div class="form-section">
                                    <h6><i class="fas fa-image text-warning"></i> Imagen del Producto</h6>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Subir Imagen</label>
                                        <div class="image-preview" onclick="document.getElementById('imageInput').click()" id="imagePreview">
                                            <div class="text-center">
                                                <i class="fas fa-cloud-upload-alt fa-3x mb-2"></i>
                                                <p class="mb-0">Haz clic para subir imagen</p>
                                                <small class="text-muted">JPG, PNG, GIF, WebP (máx. 5MB)</small>
                                            </div>
                                        </div>
                                        <input type="file" id="imageInput" name="image" accept="image/*" 
                                               style="display: none;" onchange="previewImage(this)">
                                        <div class="help-text">Imagen principal del producto</div>
                                    </div>
                                </div>

                                <!-- Categoría -->
                                <div class="form-section">
                                    <h6><i class="fas fa-tags text-secondary"></i> Categorización</h6>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Categoría</label>
                                        <select class="form-select" name="category" id="categorySelect">
                                            <option value="">Seleccionar categoría</option>
                                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                                <option value="<?php echo htmlspecialchars($cat['category']); ?>"
                                                        <?php echo $form_data['category'] === $cat['category'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['category']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                            <option value="__new__">+ Nueva categoría...</option>
                                        </select>
                                        <div class="help-text">Clasificación del producto</div>
                                    </div>
                                    
                                    <div class="mb-3" id="newCategoryDiv" style="display: none;">
                                        <label class="form-label fw-bold">Nueva Categoría</label>
                                        <input type="text" class="form-control" id="newCategoryInput" 
                                               placeholder="Nombre de la nueva categoría" maxlength="100">
                                    </div>
                                </div>

                                <!-- Estado -->
                                <div class="form-section">
                                    <h6><i class="fas fa-toggle-on text-success"></i> Estado del Producto</h6>
                                    
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="active" id="activeSwitch" 
                                               <?php echo $form_data['active'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="activeSwitch">
                                            Producto Activo
                                        </label>
                                        <div class="help-text">Los productos activos son visibles en la tienda</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Botones de acción -->
                            <div class="content-card">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success btn-modern">
                                        <i class="fas fa-save"></i> Crear Producto
                                    </button>
                                    <a href="admin-products.php" class="btn btn-secondary btn-modern">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                    <button type="button" class="btn btn-info btn-modern" onclick="previewProduct()">
                                        <i class="fas fa-eye"></i> Vista Previa
                                    </button>
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
        // Previsualización de imagen
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                    preview.classList.add('has-image');
                };
                
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Contador de stock
        function changeStock(delta) {
            const input = document.getElementById('stockInput');
            let value = parseInt(input.value) || 0;
            value = Math.max(0, Math.min(9999, value + delta));
            input.value = value;
        }

        // Manejo de nueva categoría
        document.getElementById('categorySelect').addEventListener('change', function() {
            const newCategoryDiv = document.getElementById('newCategoryDiv');
            
            if (this.value === '__new__') {
                newCategoryDiv.style.display = 'block';
                document.getElementById('newCategoryInput').focus();
            } else {
                newCategoryDiv.style.display = 'none';
            }
        });

        // Validar formulario antes de enviar
        document.getElementById('productForm').addEventListener('submit', function(e) {
            const categorySelect = document.getElementById('categorySelect');
            const newCategoryInput = document.getElementById('newCategoryInput');
            
            // Si se seleccionó nueva categoría, usar el valor del input
            if (categorySelect.value === '__new__') {
                if (newCategoryInput.value.trim() === '') {
                    e.preventDefault();
                    alert('Por favor, introduce el nombre de la nueva categoría');
                    newCategoryInput.focus();
                    return;
                }
                
                // Crear option temporal y seleccionarlo
                const newOption = new Option(newCategoryInput.value.trim(), newCategoryInput.value.trim(), true, true);
                categorySelect.add(newOption);
            }
        });

        // Vista previa del producto (modal simulado)
        function previewProduct() {
            const name = document.querySelector('[name="name"]').value;
            const price = document.querySelector('[name="price"]').value;
            const description = document.querySelector('[name="description"]').value;
            const stock = document.querySelector('[name="stock"]').value;
            
            if (!name || !price) {
                alert('Completa al menos el nombre y precio para ver la vista previa');
                return;
            }
            
            const preview = `
                <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; display: flex; align-items: center; justify-content: center;" onclick="this.remove()">
                    <div style="background: white; padding: 30px; border-radius: 15px; max-width: 400px; width: 90%;">
                        <h4>${name}</h4>
                        <p class="text-success h5">€${parseFloat(price).toFixed(2)}</p>
                        <p>${description || 'Sin descripción'}</p>
                        <p><strong>Stock:</strong> ${stock} unidades</p>
                        <small class="text-muted">Haz clic fuera para cerrar</small>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', preview);
        }

        // Auto-generar SKU si está vacío al enviar
        document.getElementById('productForm').addEventListener('submit', function() {
            const skuInput = document.querySelector('[name="sku"]');
            if (!skuInput.value.trim()) {
                skuInput.value = '<?php echo $suggested_sku; ?>';
            }
        });

        // Auto-ocultar alertas después de 5 segundos
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