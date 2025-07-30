<?php
// Versión mínima del proxy
$path = $_GET['path'] ?? '';
$url = 'http://localhost:3001/api/' . $path;
$data = file_get_contents('php://input');

$opts = array('http' =>
    array(
        'method'  => $_SERVER['REQUEST_METHOD'],
        'header'  => 'Content-Type: application/json',
        'content' => $data
    )
);

$context = stream_context_create($opts);
$result = @file_get_contents($url, false, $context);

if ($result === FALSE) {
    http_response_code(500);
    echo json_encode(['error' => 'Proxy error']);
} else {
    header('Content-Type: application/json');
    echo $result;
}
?>
