<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/artist_helpers.php';

try {
    $artist = trim($_GET['artist'] ?? '');

    if ($artist === '') {
        http_response_code(400);
        echo json_encode(
            ['error' => 'Thiếu tên nghệ sĩ'],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    $stmt = $conn->query(
        "SELECT id, title, artist, composer, album,
                duration, lyrics, audio_url, cover_url
         FROM songs
         ORDER BY id"
    );

    $result = [];

    while ($song = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (artistMatches($song['artist'] ?? '', $artist)) {
            $song['id'] = (int) $song['id'];
            $song['duration'] = (int) $song['duration'];
            $result[] = $song;
        }
    }

    echo json_encode(
        $result,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);

    echo json_encode(
        ['error' => 'Không thể tải bài hát nghệ sĩ'],
        JSON_UNESCAPED_UNICODE
    );
}
