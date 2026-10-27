<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db_connect.php';
try {
    $mood = isset($_GET['mood']) ? trim((string)$_GET['mood']) : null;
    if ($mood !== null && !in_array($mood, ['relax', 'focus'], true)) {
        json_result(['error' => 'mood phai la relax hoac focus'], 400);
    }
    $sql = 'SELECT p.id, p.title, p.description, p.mood, p.cover_url, COUNT(ps.song_id) AS song_count
            FROM playlists p LEFT JOIN playlist_songs ps ON ps.playlist_id = p.id';
    if ($mood !== null) $sql .= ' WHERE p.mood = :mood';
    $sql .= ' GROUP BY p.id, p.title, p.description, p.mood, p.cover_url ORDER BY FIELD(p.id, \'peaceful\', \'city_night\', \'sunshine\', \'focus\', \'own_space\', \'work_rhythm\'), p.id';
    $stmt = $conn->prepare($sql);
    $stmt->execute($mood === null ? [] : ['mood' => $mood]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) $r['song_count'] = (int)$r['song_count'];
    unset($r);
    json_result($rows);
} catch (Throwable $e) { api_error($e); }
