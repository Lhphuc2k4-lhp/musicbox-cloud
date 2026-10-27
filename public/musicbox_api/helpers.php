<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function json_result($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function valid_media_url(?string $value): string {
    $value = trim((string)$value);
    if ($value === '') return '';
    // Trả URL đã có trong CSDL. Với điện thoại thật, cần URL HTTP(S) có thể truy cập từ điện thoại.
    return $value;
}
function format_song(array $s): array {
    $s['id'] = (int)$s['id'];
    $s['duration'] = (int)($s['duration'] ?? 0);
    $s['audio_url'] = valid_media_url($s['audio_url'] ?? '');
    $s['cover_url'] = valid_media_url($s['cover_url'] ?? '');
    return $s;
}
function api_error(Throwable $e): void {
    error_log($e->getMessage());
    json_result(['error' => 'Loi may chu khi truy van du lieu'], 500);
}
