<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['error'=>'Unauthorized']); exit; }

$data     = json_decode(file_get_contents('php://input'), true);
$user     = getCurrentUser();

// Validasi & sanitasi — hindari bind variable untuk kompatibilitas OCI8/Oracle
$uid      = (int)$user['id'];
$gameType = in_array($data['game_type'] ?? '', ['word','puzzle']) ? $data['game_type'] : 'word';
$level    = preg_replace("/[^a-zA-Z0-9\-_]/", '', $data['level'] ?? ''); // hanya alfanumerik + - _
$score    = max(0, (int)($data['score']    ?? 0));
$duration = max(0, (int)($data['duration'] ?? 0));

// Embed nilai langsung (aman: uid & score/duration = int, gameType = whitelist, level = regex-cleaned)
executeQuery(
    "INSERT INTO scores (user_id, game_type, game_level, score, duration)
     VALUES ($uid, '$gameType', '$level', $score, $duration)"
);

echo json_encode(['success' => true]);
?>