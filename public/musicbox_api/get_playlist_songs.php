<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db_connect.php';
try {
    $id = trim((string)($_GET['playlist_id'] ?? ''));
    if ($id === '') json_result(['error' => 'Thieu playlist_id'], 400);
    $stmt = $conn->prepare('SELECT s.id, s.title, s.artist, s.composer, s.album, s.duration, s.lyrics, s.audio_url, s.cover_url
        FROM playlist_songs ps JOIN songs s ON s.id = ps.song_id
        WHERE ps.playlist_id = :id ORDER BY ps.position ASC');
    $stmt->execute(['id' => $id]);
    json_result(array_map('format_song', $stmt->fetchAll()));
} catch (Throwable $e) { api_error($e); }
