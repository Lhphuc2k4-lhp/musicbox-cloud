<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db_connect.php';
try {
    $stmt = $conn->query('SELECT id, title, artist, composer, album, duration, lyrics, audio_url, cover_url FROM songs ORDER BY id ASC');
    json_result(array_map('format_song', $stmt->fetchAll()));
} catch (Throwable $e) { api_error($e); }
