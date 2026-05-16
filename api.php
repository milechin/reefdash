<?php
header('Content-Type: application/json');

$appConfig   = json_decode(file_get_contents(__DIR__ . '/config/reefdash.json'), true) ?: [];
$tankDataPath = __DIR__ . '/' . ($appConfig['tankData'] ?? 'data/tank_data.js');
$tanksPath    = __DIR__ . '/' . ($appConfig['tanks']    ?? 'config/tanks.json');

if (!file_exists($tankDataPath)) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'tank_data.js not found']);
    exit;
}

$raw  = file_get_contents($tankDataPath);
$json = preg_replace('/^const RAW\s*=\s*/', '', trim($raw));
$json = rtrim($json, ';');

$tankData = json_decode($json, true);
$tanks    = file_exists($tanksPath) ? json_decode(file_get_contents($tanksPath), true) : [];

echo json_encode(['ok' => true, 'tankData' => $tankData, 'tanks' => $tanks]);
