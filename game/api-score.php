<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['error'=>'Unauthorized']); exit; }

$data     = json_decode(file_get_contents('php://input'), true);
$user     = getCurrentUser();

$uid      = (int)$user['id'];
$gameType = in_array($data['game_type'] ?? '', ['word','puzzle']) ? $data['game_type'] : 'word';
$level    = preg_replace("/[^a-zA-Z0-9\-_]/", '', $data['level'] ?? '');
$score    = max(0, (int)($data['score']    ?? 0));
$duration = max(0, (int)($data['duration'] ?? 0));
$moves    = max(0, (int)($data['moves']    ?? 0)); // untuk puzzle
$attempts = max(0, (int)($data['attempts'] ?? 0)); // untuk word

executeQuery(
    "INSERT INTO scores (user_id, game_type, game_level, score, duration, moves, attempts)
     VALUES ($uid, '$gameType', '$level', $score, $duration, $moves, $attempts)"
);

echo json_encode(['success' => true]);
?>