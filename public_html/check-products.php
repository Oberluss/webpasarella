<?php
// Obtener token de admin
$ch = curl_init("http://localhost:3000/api/auth/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "email" => "admin@webprueba.com",
    "password" => "admin123"
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);
$result = json_decode($response, true);
$token = $result['token'];

// Obtener productos
$ch = curl_init("http://localhost:3000/api/products");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
curl_close($ch);

$products = json_decode($response, true);

echo "<pre>";
echo "=== Productos en la Base de Datos ===\n\n";
echo json_encode($products, JSON_PRETTY_PRINT);
echo "</pre>";
?>
