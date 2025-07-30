<?php
// Cargar configuración
$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    // Si no existe config.php, intentar con config.example.php para desarrollo
    $configFile = __DIR__ . '/config.example.php';
    if (!file_exists($configFile)) {
        http_response_code(500);
        die(json_encode(['error' => 'Archivo de configuración no encontrado']));
    }
}
require_once $configFile;

// Headers CORS
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGINS);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Construir URL
$path = $_GET['path'] ?? '';
unset($_GET['path']);

$queryString = !empty($_GET) ? '?' . http_build_query($_GET) : '';
$url = API_URL . $path . $queryString;

// Headers para cURL
$headers = ['Content-Type: application/json'];
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $headers[] = 'Authorization: ' . $_SERVER['HTTP_AUTHORIZATION'];
}

// Configurar cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

// Body para POST/PUT
if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH'])) {
    $body = file_get_contents('php://input');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
}

// Ejecutar y obtener respuesta
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Manejar errores
if ($response === false) {
    http_response_code(500);
    $errorMsg = defined('DEBUG_MODE') && DEBUG_MODE ? $error : 'Error de conexión con el servidor';
    echo json_encode(['error' => $errorMsg]);
    exit();
}

// Devolver respuesta
http_response_code($httpCode);
header('Content-Type: application/json');
echo $response;
?>