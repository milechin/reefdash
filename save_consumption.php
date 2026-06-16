<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

$appConfig = json_decode(file_get_contents(__DIR__ . '/config/reefdash.json'), true) ?: [];
$filePath  = __DIR__ . '/' . ($appConfig['consumption'] ?? 'config/consumption.json');

if (!is_writable(dirname($filePath))) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Directory not writable']);
    exit;
}

$bytes = file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
echo json_encode($bytes !== false ? ['ok' => true] : ['ok' => false, 'error' => 'Write failed']);
