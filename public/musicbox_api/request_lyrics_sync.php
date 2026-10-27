
<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Cloud demo reads the prepared LRC. No Whisper worker is running there.
if (getenv('MUSICBOX_LYRICS_SYNC_ENABLED') === '0') {
    echo json_encode(['status' => 'unsupported', 'lyrics_lrc' => null,
        'error' => 'Máy chủ này chỉ đọc lời đã chuẩn bị sẵn.'], JSON_UNESCAPED_UNICODE);
    exit;
}

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

    // Tự tạo bảng quản lý tác vụ AI
    $conn->exec("
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

    // Chỉ cho phép phương thức POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse([
            'status' => 'failed',
            'error' => 'Chỉ hỗ trợ POST'
        ], 405);
    }

    $songId = filter_input(
        INPUT_POST,
        'song_id',
        FILTER_VALIDATE_INT
    );

    if (!$songId || $songId < 1) {
        sendResponse([
            'status' => 'failed',
            'error' => 'ID bài hát không hợp lệ'
        ], 400);
    }

    // Lấy dữ liệu bài hát từ bảng songs
    $stmt = $conn->prepare("
        SELECT
            id,
            title,
            lyrics,
            lyrics_lrc,
            audio_url

        FROM songs

        WHERE id = :song_id

        LIMIT 1
    ");

    $stmt->execute([
        ':song_id' => $songId
    ]);

    $song = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$song) {
        sendResponse([
            'status' => 'failed',
            'error' => 'Không tìm thấy bài hát'
        ], 404);
    }

    // Nếu đã có LRC thì không xử lý AI nữa
    $existingLrc = trim(
        (string)($song['lyrics_lrc'] ?? '')
    );

    if ($existingLrc !== '') {
        sendResponse([
            'status' => 'ready',
            'error' => null
        ]);
    }

    // Kiểm tra lời bài hát gốc
    $lyrics = trim(
        (string)($song['lyrics'] ?? '')
    );

    if ($lyrics === '') {
        sendResponse([
            'status' => 'failed',
            'error' =>
                'Bài hát chưa có lời trong MySQL'
        ]);
    }

    // Kiểm tra đường dẫn âm thanh
    $audioUrl = trim(
        (string)($song['audio_url'] ?? '')
    );

    if ($audioUrl === '') {
        sendResponse([
            'status' => 'failed',
            'error' =>
                'Bài hát chưa có file âm thanh'
        ]);
    }

    // Thêm yêu cầu vào hàng đợi AI
    // Nếu đang pending/processing thì không tạo trùng
    // Nếu failed thì cho phép gửi lại
    $sql = "
        INSERT INTO lyrics_jobs (
            song_id,
            status,
            error_message
        )
        VALUES (
            :song_id,
            'pending',
            NULL
        )

        ON DUPLICATE KEY UPDATE

            error_message = CASE
                WHEN status = 'failed'
                THEN NULL
                ELSE error_message
            END,

            status = CASE
                WHEN status = 'failed'
                THEN 'pending'
                ELSE status
            END
    ";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ':song_id' => $songId
    ]);

    // Lấy trạng thái hiện tại
    $stmt = $conn->prepare("
        SELECT status
        FROM lyrics_jobs
        WHERE song_id = :song_id
    ");

    $stmt->execute([
        ':song_id' => $songId
    ]);

    $status = $stmt->fetchColumn();

    sendResponse([
        'status' => $status ?: 'pending',
        'error' => null
    ]);

} catch (Throwable $e) {

    error_log(
        'Request Lyrics Sync Error: ' .
        $e->getMessage()
    );

    // Thông báo chi tiết chỉ dùng để debug local
    sendResponse([
        'status' => 'failed',
        'error' => $e->getMessage()
    ], 500);
}
