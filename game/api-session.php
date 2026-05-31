<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
header('Content-Type: application/json');
if (!isLoggedIn()) { echo json_encode(['error'=>'Unauthorized']); exit; }

$user   = getCurrentUser();
$action = $_GET['action'] ?? '';
$data   = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw    = file_get_contents('php://input');
    $data   = json_decode($raw, true) ?? [];
    if (!$action) $action = $data['action'] ?? '';
}


// ── Helper: baca CLOB Oracle → string PHP ─────────────────────────
function readClob($val) {
    if ($val === null) return null;
    if (is_object($val) && method_exists($val, 'load')) return $val->load();
    return (string)$val;
}

switch ($action) {

    // ── CREATE ROOM ────────────────────────────────────────────────
    case 'create':
        $code  = preg_replace('/[^A-Z0-9]/', '', strtoupper($data['code'] ?? ''));
        $level = $data['level'] ?? '';

        // Detect game type and normalise level
        if (preg_match('/^puzzle-(\d+)$/', $level, $m)) {
            $gameType = 'puzzle';
            $level    = 'level-' . $m[1];
        } elseif (preg_match('/^level-\d+$/', $level)) {
            $gameType = 'puzzle';
        } elseif (in_array($level, ['easy','medium','hard'])) {
            $gameType = 'word';
        } else {
            $gameType = 'word';
            $level    = 'easy';
        }

        $sd = json_encode([
            'game_type' => $gameType,
            'level'     => $level,
            'player1'   => $user['username'],
            'player2'   => null,
            'status'    => 'waiting',
            'board'     => null,
            'word'      => null,
            'p1'        => ['moves'=>0,'pct'=>0,'done'=>false,'time'=>null,'won'=>false,'guesses'=>0,'ts'=>time()],
            'p2'        => ['moves'=>0,'pct'=>0,'done'=>false,'time'=>null,'won'=>false,'guesses'=>0,'ts'=>time()],
            'winner'    => null,
        ]);

        executeQuery(
            "INSERT INTO game_sessions (id, game_type, player1_id, sess_status, session_data)
             VALUES (:id, :gt, :p1, 'waiting', TO_CLOB(:sd))",
            [':id'=>$code, ':gt'=>$gameType, ':p1'=>$user['id'], ':sd'=>$sd]
        );
        echo json_encode(['success'=>true, 'code'=>$code]);
        break;

    // ── CHECK IF PLAYER 2 JOINED ───────────────────────────────────
    case 'check':
        $code = preg_replace('/[^A-Z0-9]/', '', strtoupper($_GET['code'] ?? ''));
        $stmt = executeQuery(
            "SELECT session_data FROM game_sessions WHERE id=:id",
            [':id'=>$code]
        );
        $row = fetchOne($stmt);
        if ($row) {
            $sd = json_decode(readClob($row['session_data']), true);
            echo json_encode(['player2' => $sd['player2'] ?? null]);
        } else {
            echo json_encode(['player2' => null]);
        }
        break;

    // ── JOIN ROOM ──────────────────────────────────────────────────
    case 'join':
        $code = preg_replace('/[^A-Z0-9]/', '', strtoupper($_GET['code'] ?? $data['code'] ?? ''));
        $stmt = executeQuery(
            "SELECT id, session_data FROM game_sessions WHERE id=:id AND sess_status='waiting'",
            [':id'=>$code]
        );
        $row = fetchOne($stmt);
        if ($row) {
            $sd = json_decode(readClob($row['session_data']), true);
            if ($sd['player1'] === $user['username']) {
                echo json_encode(['error'=>'Tidak bisa join room sendiri']); break;
            }
            $sd['player2'] = $user['username'];
            // Tetap 'waiting' sampai host tekan Start — supaya polling host bisa deteksi
            executeQuery(
                "UPDATE game_sessions SET player2_id=:p2, session_data=TO_CLOB(:sd) WHERE id=:id",
                [':p2'=>$user['id'], ':sd'=>json_encode($sd), ':id'=>$code]
            );
            echo json_encode(['success'=>true, 'game_type'=>$sd['game_type'], 'level'=>$sd['level']]);
        } else {
            echo json_encode(['error'=>'Room tidak ditemukan atau sudah penuh']);
        }
        break;

    // ── GET FULL SESSION STATE (polling) ───────────────────────────
    case 'get_state':
        $code = preg_replace('/[^A-Z0-9]/', '', strtoupper($_GET['code'] ?? ''));
        $stmt = executeQuery(
            "SELECT session_data FROM game_sessions WHERE id=:id",
            [':id'=>$code]
        );
        $row = fetchOne($stmt);
        if ($row) {
            $rawJson = readClob($row['session_data']);
            $sd = json_decode($rawJson, true);
            if (!is_array($sd)) $sd = [];
            echo json_encode($sd);
        } else {
            // Room tidak ada (sudah didelete host) → sinyal cancelled ke guest
            echo json_encode(['cancelled'=>true]);
        }
        break;

    // ── HOST SAVES PUZZLE BOARD ────────────────────────────────────
    case 'set_board':
        $code = preg_replace('/[^A-Z0-9]/', '', strtoupper($data['code'] ?? ''));
        $stmt = executeQuery(
            "SELECT session_data FROM game_sessions WHERE id=:id",
            [':id'=>$code]
        );
        $row = fetchOne($stmt);
        if (!$row) { echo json_encode(['error'=>'Room not found']); break; }
        $sd = json_decode(readClob($row['session_data']), true);
        if ($sd['player1'] !== $user['username']) {
            echo json_encode(['error'=>'Only host can set board']); break;
        }
        $sd['board'] = $data['board']; // array of tile integers
        $sd['grid']  = (int)($data['grid'] ?? 0);
        executeQuery(
            "UPDATE game_sessions SET session_data=TO_CLOB(:sd) WHERE id=:id",
            [':sd'=>json_encode($sd), ':id'=>$code]
        );
        echo json_encode(['success'=>true]);
        break;

    // ── HOST SAVES WORD ────────────────────────────────────────────
    case 'set_word':
        $code = preg_replace('/[^A-Z0-9]/', '', strtoupper($data['code'] ?? ''));
        $stmt = executeQuery(
            "SELECT session_data FROM game_sessions WHERE id=:id",
            [':id'=>$code]
        );
        $row = fetchOne($stmt);
        if (!$row) { echo json_encode(['error'=>'Room not found']); break; }
        $sd = json_decode(readClob($row['session_data']), true);
        if ($sd['player1'] !== $user['username']) {
            echo json_encode(['error'=>'Only host can set word']); break;
        }
        $sd['word'] = strtoupper(preg_replace('/[^A-Za-z]/', '', $data['word'] ?? ''));
        executeQuery(
            "UPDATE game_sessions SET session_data=TO_CLOB(:sd) WHERE id=:id",
            [':sd'=>json_encode($sd), ':id'=>$code]
        );
        echo json_encode(['success'=>true]);
        break;

    // ── PLAYER POSTS PROGRESS / FINISH ────────────────────────────
    case 'update':
        $code = preg_replace('/[^A-Z0-9]/', '', strtoupper($data['code'] ?? ''));
        $stmt = executeQuery(
            "SELECT session_data FROM game_sessions WHERE id=:id",
            [':id'=>$code]
        );
        $row = fetchOne($stmt);
        if (!$row) { echo json_encode(['error'=>'Room not found']); break; }

        $sd   = json_decode(readClob($row['session_data']), true);
        $pkey = ($sd['player1'] === $user['username']) ? 'p1' : 'p2';

        // Merge allowed fields
        foreach (['moves','pct','done','time','won','guesses'] as $f) {
            if (array_key_exists($f, $data)) $sd[$pkey][$f] = $data[$f];
        }
        $sd[$pkey]['ts'] = time();

        // Auto-determine winner (only if not already set)
        if (!$sd['winner']) {
            $p1done = (bool)($sd['p1']['done'] ?? false);
            $p2done = (bool)($sd['p2']['done'] ?? false);

            if ($sd['game_type'] === 'puzzle') {
                // First to finish wins
                if ($p1done && !$p2done) {
                    $sd['winner'] = $sd['player1'];
                } elseif ($p2done && !$p1done) {
                    $sd['winner'] = $sd['player2'];
                } elseif ($p1done && $p2done) {
                    $t1 = (float)($sd['p1']['time'] ?? 999999);
                    $t2 = (float)($sd['p2']['time'] ?? 999999);
                    $sd['winner'] = ($t1 <= $t2) ? $sd['player1'] : $sd['player2'];
                }
            } else {
                // Word game: first to correctly guess wins
                $w1 = (bool)($sd['p1']['won'] ?? false);
                $w2 = (bool)($sd['p2']['won'] ?? false);
                if ($w1 && !$w2) {
                    $sd['winner'] = $sd['player1'];
                } elseif ($w2 && !$w1) {
                    $sd['winner'] = $sd['player2'];
                } elseif ($p1done && $p2done) {
                    if ($w1 && $w2) {
                        // Both guessed correctly — faster wins
                        $t1 = (float)($sd['p1']['time'] ?? 999999);
                        $t2 = (float)($sd['p2']['time'] ?? 999999);
                        $sd['winner'] = ($t1 <= $t2) ? $sd['player1'] : $sd['player2'];
                    } else {
                        $sd['winner'] = 'draw'; // both lost
                    }
                }
            }
        }

        executeQuery(
            "UPDATE game_sessions SET session_data=TO_CLOB(:sd) WHERE id=:id",
            [':sd'=>json_encode($sd), ':id'=>$code]
        );
        echo json_encode(['success'=>true]);
        break;


    // ── HOST CANCEL / BUBARKAN ROOM ───────────────────────────────
    case 'cancel':
        $code = preg_replace('/[^A-Z0-9]/', '', strtoupper($data['code'] ?? $_GET['code'] ?? ''));
        $stmt = executeQuery(
            "SELECT session_data FROM game_sessions WHERE id=:id",
            [':id'=>$code]
        );
        $row = fetchOne($stmt);
        if (!$row) { echo json_encode(['success'=>true]); break; } // sudah tidak ada, ok
        $sd = json_decode(readClob($row['session_data']), true);
        if ($sd['player1'] === $user['username']) {
            // Host keluar → DELETE row agar guest dapat "Room not found" = cancelled
            executeQuery(
                "DELETE FROM game_sessions WHERE id=:id",
                [':id'=>$code]
            );
        } else {
            // Guest keluar → hapus player2 dari room
            $sd['player2'] = null;
            executeQuery(
                "UPDATE game_sessions SET player2_id=NULL, session_data=TO_CLOB(:sd) WHERE id=:id",
                [':sd'=>json_encode($sd), ':id'=>$code]
            );
        }
        echo json_encode(['success'=>true]);
        break;

    // ── PLAYER FORFEIT (keluar saat game berlangsung) ─────────────
    case 'forfeit':
        $code = preg_replace('/[^A-Z0-9]/', '', strtoupper($data['code'] ?? ''));
        $stmt = executeQuery(
            "SELECT session_data FROM game_sessions WHERE id=:id",
            [':id'=>$code]
        );
        $row = fetchOne($stmt);
        if (!$row) { echo json_encode(['error'=>'Room not found']); break; }
        $sd = json_decode(readClob($row['session_data']), true);
        $isPlayer1 = ($sd['player1'] === $user['username']);
        // Yang kalah adalah yang forfeit, yang menang adalah lawan
        $sd['winner']  = $isPlayer1 ? $sd['player2'] : $sd['player1'];
        $sd['forfeit'] = $user['username']; // siapa yang forfeit
        executeQuery(
            "UPDATE game_sessions SET sess_status='finished', session_data=TO_CLOB(:sd) WHERE id=:id",
            [':sd'=>json_encode($sd), ':id'=>$code]
        );
        echo json_encode(['success'=>true]);
        break;

    // ── HOST STARTS GAME ──────────────────────────────────────────
    case 'start':
        $code = preg_replace('/[^A-Z0-9]/', '', strtoupper($data['code'] ?? ''));
        $stmt = executeQuery(
            "SELECT session_data FROM game_sessions WHERE id=:id",
            [':id'=>$code]
        );
        $row = fetchOne($stmt);
        if (!$row) { echo json_encode(['error'=>'Room not found']); break; }
        $sd = json_decode(readClob($row['session_data']), true);
        if ($sd['player1'] !== $user['username']) {
            echo json_encode(['error'=>'Hanya host yang bisa memulai']); break;
        }
        if (empty($sd['player2'])) {
            echo json_encode(['error'=>'Menunggu pemain 2 bergabung']); break;
        }
        $sd['started'] = true;
        executeQuery(
            "UPDATE game_sessions SET sess_status='playing', session_data=TO_CLOB(:sd) WHERE id=:id",
            [':sd'=>json_encode($sd), ':id'=>$code]
        );
        echo json_encode(['success'=>true]);
        break;

    default:
        echo json_encode(['error'=>'Unknown action']);
}
?>
