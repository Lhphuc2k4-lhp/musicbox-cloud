<?php
$tlsMode = strtolower(getenv('MUSICBOX_DB_TLS_MODE') ?: 'disabled');
try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 12,
    ];
    if (!in_array($tlsMode, ['disabled', 'required', 'verified'], true)) {
        throw new RuntimeException('Invalid database TLS mode');
    }
    if ($tlsMode !== 'disabled') {
        // Setting the cipher list enables mysqlnd's TLS handshake. Filess uses
        // a self-signed certificate; REQUIRED encrypts without CA validation.
        $options[PDO::MYSQL_ATTR_SSL_CIPHER] = 'DEFAULT';
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = $tlsMode === 'verified';
        $ca = getenv('MUSICBOX_DB_SSL_CA');
        if ($ca) $options[PDO::MYSQL_ATTR_SSL_CA] = $ca;
    }
    $conn = new PDO(
        'mysql:host=' . (getenv('MUSICBOX_DB_HOST') ?: '127.0.0.1') .
        ';port=' . (getenv('MUSICBOX_DB_PORT') ?: '3306') .
        ';dbname=' . (getenv('MUSICBOX_DB_NAME') ?: 'musicbox') . ';charset=utf8mb4',
        getenv('MUSICBOX_DB_USER') ?: 'root',
        getenv('MUSICBOX_DB_PASSWORD') ?: '',
        $options
    );
    if ($tlsMode !== 'disabled') {
        $cipher = $conn->query("SHOW STATUS LIKE 'Ssl_cipher'")->fetch(PDO::FETCH_NUM);
        if (!$cipher || $cipher[1] === '') throw new RuntimeException('Database TLS negotiation failed');
    }
} catch (Throwable $e) {
    // Keep connection details out of public API responses and application logs.
    error_log('MusicBox database connection failed (' . get_class($e) . ')');
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Chua ket noi duoc CSDL. Kiem tra cau hinh may chu.']);
    exit;
}
