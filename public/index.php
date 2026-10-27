<?php
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['service' => 'MusicBox', 'catalog_api' => '/musicbox_api/',
    'zing_api' => '/zing/', 'health' => '/health.php'], JSON_UNESCAPED_SLASHES);
