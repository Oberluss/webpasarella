<?php
// logout.php - Cerrar sesión y limpiar todo
session_start();

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerrando sesión...</title>
    <script>
        // Limpiar localStorage y sessionStorage
        localStorage.clear();
        sessionStorage.clear();
        
        // Redirigir inmediatamente a index.php
        window.location.href = 'index.php';
    </script>
</head>
<body>
    <p>Cerrando sesión...</p>
    <!-- Redirección de respaldo si JavaScript está deshabilitado -->
    <meta http-equiv="refresh" content="0;url=index.php">
</body>
</html>