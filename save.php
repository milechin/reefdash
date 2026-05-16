<?php
/**
 * Reef Monitor — save endpoint
 * Receives updated tank_data.js content and writes it to disk.
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $body = file_get_contents('php://input');
    if (!$body) throw new Exception('Empty request body');

    $data = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON parse error: ' . json_last_error_msg());
    }
    if (!$data || !isset($data['content'])) {
        throw new Exception('Missing content field');
    }

    $content = $data['content'];

    if (strpos(ltrim($content), 'const RAW') !== 0) {
        throw new Exception('Content does not look like tank_data.js');
    }

    $filePath = __DIR__ . '/data/tank_data.js';

    // Check the data directory is writable
    if (!is_writable(__DIR__ . '/data')) {
        throw new Exception('Directory not writable: ' . __DIR__ . '/data');
    }

    // Write new file
    $bytes = file_put_contents($filePath, $content);
    if ($bytes === false) {
        throw new Exception('file_put_contents failed — check permissions on ' . $filePath);
    }

    echo json_encode(['ok' => true, 'bytes' => $bytes]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
