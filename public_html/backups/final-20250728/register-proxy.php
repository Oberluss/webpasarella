<?php
// Proxy especial para registro que convierte GET a POST
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Obtener datos del body o de GET
$input = file_get_contents('php://input');
if (empty($input)) {
    // Si no hay body, intentar obtener de parámetros GET
    $data = array(
        'firstName' => $_GET['firstName'] ?? '',
        'lastName' => $_GET['lastName'] ?? '',
        'email' => $_GET['email'] ?? '',
        'password' => $_GET['password'] ?? '',
        'phone' => $_GET['phone'] ?? '',
        'newsletter' => isset($_GET['newsletter']) ? true : false
    );
} else {
    $data = json_decode($input, true);
}

// Agregar +34 al teléfono si no lo tiene
if (!empty($data['phone']) && !str_starts_with($data['phone'], '+')) {
    // Si empieza con 6 o 7, es móvil español
    if (preg_match('/^[67]\d{8}$/', $data['phone'])) {
        $data['phone'] = '+34' . $data['phone'];
    } else {
        // Si no es móvil, dejarlo vacío para evitar error
        $data['phone'] = '';
    }
}

// Hacer la petición al backend
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

// Devolver resultado
header('Content-Type: application/json');
echo $result;
?>
