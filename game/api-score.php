<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['error'=>'Unauthorized']); exit; }

$data = json_decode(file_get_contents('php://input'), true);
$user = getCurrentUser();
$gameType = in_array($data['game_type'] ?? '', ['word','puzzle']) ? $data['game_type'] : 'word';
$level    = htmlspecialchars($data['level'] ?? '');
$score    = (int)($data['score'] ?? 0);
$duration = (int)($data['duration'] ?? 0);

executeQuery(
    "INSERT INTO scores (user_id, game_type, game_level, score, duration) VALUES (:uid, :gt, :lv, :sc, :du)",
    [':uid'=>$user['id'], ':gt'=>$gameType, ':lv'=>$level, ':sc'=>$score, ':du'=>$duration]
);

echo json_encode(['success' => true]);
?>
