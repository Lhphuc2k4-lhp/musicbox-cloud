
<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/db_connect.php';

function sendResponse($data, $code = 200) {
    http_response_code($code);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}

try {
    // Kiểm tra kết nối PDO
    if (!isset($conn) || !($conn instanceof PDO)) {
        throw new Exception(
            'Không tìm thấy kết nối PDO $conn'
        );
    }

    // Tự tạo bảng quản lý tác vụ AI nếu chưa có
    // Không thay đổi bảng songs hiện tại
    if (getenv('MUSICBOX_LYRICS_SYNC_ENABLED') !== '0') $conn->exec("
        CREATE TABLE IF NOT EXISTS lyrics_jobs (
            song_id INT NOT NULL PRIMARY KEY,
            status ENUM(
                'pending',
                'processing',
                'ready',
                'failed'
            ) NOT NULL DEFAULT 'pending',
            error_message VARCHAR(255) DEFAULT NULL,
            updated_at TIMESTAMP
                DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
          DEFAULT CHARSET=utf8mb4
    ");

    $songId = filter_input(
        INPUT_GET,
        'song_id',
        FILTER_VALIDATE_INT
    );

    if (!$songId || $songId < 1) {
        sendResponse([
            'status' => 'failed',
            'lyrics_lrc' => '',
            'error' => 'ID bài hát không hợp lệ'
        ], 400);
    }

    // Lấy lời bài hát và trạng thái xử lý
    $sql = "
        SELECT
            s.id,
            s.title,
            s.lyrics_lrc,
            j.status AS job_status,
            j.error_message

        FROM songs AS s

        LEFT JOIN lyrics_jobs AS j
            ON j.song_id = s.id

        WHERE s.id = :song_id

        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ':song_id' => $songId
    ]);

    $song = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$song) {
        sendResponse([
            'status' => 'failed',
            'lyrics_lrc' => '',
            'error' => 'Không tìm thấy bài hát'
        ], 404);
    }

    $lrc = trim(
        (string)($song['lyrics_lrc'] ?? '')
    );

    // Đã có lyrics đồng bộ
    if ($lrc !== '') {
        sendResponse([
            'status' => 'ready',
            'lyrics_lrc' => $lrc,
            'error' => null
        ]);
    }

    // Chưa có LRC: kiểm tra AI đang xử lý hay chưa
    $status = $song['job_status'] ?: 'missing';

    sendResponse([
        'status' => $status,
        'lyrics_lrc' => '',
        'error' => $song['error_message'] ?? null
    ]);

} catch (Throwable $e) {

    error_log(
        'Get Lyrics Status Error: ' .
        $e->getMessage()
    );

    // Thông báo chi tiết chỉ dùng để debug local
    sendResponse([
        'status' => 'failed',
        'lyrics_lrc' => '',
        'error' => 'Chưa tải được lời bài hát. Hãy thử lại.'
    ], 500);
}
