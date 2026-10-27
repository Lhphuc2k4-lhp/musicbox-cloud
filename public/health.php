<?php
require_once __DIR__ . '/musicbox_api/db_connect.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    $count = (int)$conn->query('SELECT COUNT(*) FROM songs')->fetchColumn();
    $context = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
    $zingPort = (int)(getenv('MUSICBOX_ZING_PORT') ?: 8877);
    $zing = json_decode((string)@file_get_contents('http://127.0.0.1:' . $zingPort . '/health', false, $context), true);
    if (($zing['status'] ?? '') !== 'ok' || ($zing['source'] ?? '') !== 'zingmp3') {
        throw new RuntimeException('Zing bridge is not ready');
    }
    echo json_encode(['status' => 'ok', 'database' => 'connected', 'songs' => $count, 'zing' => 'ok']);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['status' => 'unavailable', 'message' => 'Service is starting or not configured.']);
}
