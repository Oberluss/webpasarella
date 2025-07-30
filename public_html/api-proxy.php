<?php
// Include configuration
require_once 'config.php';

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get the path from query string
$path = $_GET['path'] ?? '';
unset($_GET['path']);

// Build query string from remaining GET parameters
$queryString = '';
if (!empty($_GET)) {
    $queryString = '?' . http_build_query($_GET);
}

// Build the full URL - CAMBIADO A PUERTO 3001
$url = "http://localhost:3001/api/" . $path . $queryString;

// Set up headers
$headers = [];
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $headers[] = 'Authorization: ' . $_SERVER['HTTP_AUTHORIZATION'];
}
$headers[] = 'Content-Type: application/json';

// Initialize cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Añadir timeout

// Handle request body for POST/PUT/PATCH
if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH'])) {
    $body = file_get_contents('php://input');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
}

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Handle errors
if ($response === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al conectar con el servidor: ' . $error]);
    exit();
}

// Return response
http_response_code($httpCode);
header('Content-Type: application/json');
echo $response;
?>