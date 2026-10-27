
<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/artist_helpers.php';

try {
    // Giả định db_connect.php tạo kết nối PDO $conn
    $stmt = $conn->query(
        "SELECT artist FROM songs
         WHERE artist IS NOT NULL AND TRIM(artist) <> ''"
    );

    $artists = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        foreach (splitArtists($row['artist']) as $name) {
            $key = mb_strtolower($name, 'UTF-8');

            if (!isset($artists[$key])) {
                $artists[$key] = [
                    'artist' => $name,
                    'song_count' => 0
                ];
            }

            $artists[$key]['song_count']++;
        }
    }

    $result = array_values($artists);

    usort($result, function ($a, $b) {
        return $b['song_count'] <=> $a['song_count']
            ?: strcmp($a['artist'], $b['artist']);
    });

    echo json_encode(
        $result,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);

    echo json_encode(
        ['error' => 'Không thể tải nghệ sĩ'],
        JSON_UNESCAPED_UNICODE
    );
}
