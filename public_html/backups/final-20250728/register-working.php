<?php
// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Obtener datos del POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validar datos requeridos
if (!$data || !isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

// Preparar datos para enviar
$userData = array(
    "firstName" => $data['firstName'] ?? '',
    "lastName" => $data['lastName'] ?? '',
    "email" => $data['email'],
    "password" => $data['password'],
    "phone" => $data['phone'] ?? '',
    "newsletter" => $data['newsletter'] ?? false
);

// Ajustar teléfono si es necesario
if (!empty($userData['phone'])) {
    // Si no tiene +34 y es un móvil español (6 o 7)
    if (!str_starts_with($userData['phone'], '+') && preg_match('/^[67]\d{8}$/', $userData['phone'])) {
        $userData['phone'] = '+34' . $userData['phone'];
    }
}

// Configurar la petición exactamente como el código que funciona
$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($userData),
        'ignore_errors' => true
    )
);

$context = stream_context_create($options);
$result = file_get_contents('http://localhost:3001/api/auth/register', false, $context);

// Devolver resultado
header('Content-Type: application/json');
echo $result;
?>
