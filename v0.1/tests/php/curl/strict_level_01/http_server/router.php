<?php

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$body = file_get_contents('php://input');
if (!is_string($body)) {
    $body = '';
}

if ($path === '/echo-post' && $method === 'POST') {
    http_response_code(200);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $body;
    return true;
}

if ($path === '/echo-json' && $method === 'POST') {
    $decoded = json_decode($body, true);
    if (!is_array($decoded)) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'invalid_json']);
        return true;
    }

    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode([
        'ok' => true,
        'kind' => 'json',
        'payload' => $decoded,
    ]);
    return true;
}

return false;
