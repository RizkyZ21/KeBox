<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
header('Content-Type: application/json');
if (!isLoggedIn()) { echo json_encode(['error'=>'Unauthorized']); exit; }

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $data['action'] ?? $action;

switch ($action) {
    case 'create':
        $code     = preg_replace('/[^A-Z0-9]/', '', strtoupper($data['code'] ?? ''));
        $level    = in_array($data['level'] ?? '', ['easy','medium','hard']) ? $data['level'] : 'easy';
        $user     = getCurrentUser();
        $session  = json_encode(['level'=>$level,'player1'=>$user['username'],'player2'=>null,'status'=>'waiting']);
        executeQuery(
            "INSERT INTO game_sessions (id, game_type, player1_id, status, session_data)
             VALUES (:id, 'word', :p1, 'waiting', :sd)",
            [':id'=>$code, ':p1'=>$user['id'], ':sd'=>$session]
        );
        echo json_encode(['success'=>true,'code'=>$code]);
        break;

    case 'check':
        $code = preg_replace('/[^A-Z0-9]/', '', strtoupper($_GET['code'] ?? ''));
        $stmt = executeQuery("SELECT session_data FROM game_sessions WHERE id=:id", [':id'=>$code]);
        $row  = fetchOne($stmt);
        if ($row) {
            $sd = json_decode($row['session_data'], true);
            echo json_encode(['player2' => $sd['player2'] ?? null]);
        } else {
            echo json_encode(['player2' => null]);
        }
        break;

    case 'join':
        $code = preg_replace('/[^A-Z0-9]/', '', strtoupper($_GET['code'] ?? $data['code'] ?? ''));
        $user = getCurrentUser();
        $stmt = executeQuery("SELECT id, session_data FROM game_sessions WHERE id=:id AND status='waiting'", [':id'=>$code]);
        $row  = fetchOne($stmt);
        if ($row) {
            $sd = json_decode($row['session_data'], true);
            if ($sd['player1'] === $user['username']) { echo json_encode(['error'=>'Tidak bisa join room sendiri']); break; }
            $sd['player2'] = $user['username'];
            $sd['status']  = 'playing';
            $newSd = json_encode($sd);
            executeQuery(
                "UPDATE game_sessions SET player2_id=:p2, status='playing', session_data=:sd WHERE id=:id",
                [':p2'=>$user['id'], ':sd'=>$newSd, ':id'=>$code]
            );
            echo json_encode(['success'=>true]);
        } else {
            echo json_encode(['error'=>'Room tidak ditemukan atau sudah penuh']);
        }
        break;

    default:
        echo json_encode(['error'=>'Unknown action']);
}
?>
