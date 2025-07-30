<?php
// Procesar el formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Preparar datos
    $data = array(
        "firstName" => $_POST['firstName'] ?? '',
        "lastName" => $_POST['lastName'] ?? '',
        "email" => $_POST['email'] ?? '',
        "password" => $_POST['password'] ?? '',
        "phone" => $_POST['phone'] ?? '',
        "newsletter" => isset($_POST['newsletter']) ? true : false
    );
    
    // Ajustar teléfono
    if (!empty($data['phone']) && !str_starts_with($data['phone'], '+')) {
        if (preg_match('/^[67]\d{8}$/', $data['phone'])) {
            $data['phone'] = '+34' . $data['phone'];
        }
    }
    
    // Enviar al backend
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
            'ignore_errors' => true
        )
    );
    
    $context = stream_context_create($options);
    $result = file_get_contents('http://localhost:3001/api/auth/register', false, $context);
    $response = json_decode($result, true);
    
    if ($response && $response['success']) {
        $message = "¡Registro exitoso! Por favor verifica tu email.";
        $success = true;
    } else {
        $message = $response['message'] ?? "Error al registrar";
        $success = false;
        // Mostrar errores específicos
        if (isset($response['errors'])) {
            $errors = $response['errors'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - WebPasarella</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h2>Crear Cuenta</h2>
                
                <?php if (isset($message)): ?>
                    <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                        
                        <?php if (isset($errors)): ?>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error['param'] . ': ' . $error['msg']); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Debug info -->
                <?php if (isset($data)): ?>
                    <div class="alert alert-info">
                        <small>Datos enviados: <?php echo json_encode($data); ?></small>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label>Nombre *</label>
                        <input type="text" name="firstName" class="form-control" required 
                               value="<?php echo $_POST['firstName'] ?? ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label>Apellidos *</label>
                        <input type="text" name="lastName" class="form-control" required
                               value="<?php echo $_POST['lastName'] ?? ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label>Email *</label>
                        <input type="email" name="email" class="form-control" required
                               value="<?php echo $_POST['email'] ?? ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label>Teléfono (móvil español: 6xxxxxxxx)</label>
                        <input type="text" name="phone" class="form-control" placeholder="600000000"
                               value="<?php echo $_POST['phone'] ?? ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label>Contraseña * (mínimo 8 caracteres, mayúsculas, minúsculas y números)</label>
                        <input type="password" name="password" class="form-control" required minlength="8">
                    </div>
                    
                    <div class="mb-3">
                        <input type="checkbox" name="newsletter" id="newsletter" 
                               <?php echo isset($_POST['newsletter']) ? 'checked' : ''; ?>>
                        <label for="newsletter">Deseo recibir ofertas y novedades</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Crear Cuenta</button>
                    <a href="/" class="btn btn-secondary">Volver</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
