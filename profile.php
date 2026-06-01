<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();
$user   = getCurrentUser();
$userId = (int)$user['id'];

// ── Detail user (email + created_at) ──────────────────────────────
$stmtUser = executeQuery(
    "SELECT username, email, user_role, created_at FROM users WHERE id = :id",
    [':id' => $userId]
);
$userDetail = fetchOne($stmtUser);

// ── Statistik Word per difficulty ─────────────────────────────────
// game_level: 'easy' | 'medium' | 'hard'  ;  score > 0 = menang
$stmtWord = executeQuery(
    "SELECT game_level,
            COUNT(*)                                        AS total,
            SUM(CASE WHEN score > 0 THEN 1 ELSE 0 END)    AS wins,
            MAX(score)                                      AS best_score,
            MIN(CASE WHEN score > 0 THEN duration END)     AS best_time
     FROM scores
     WHERE user_id = :id AND game_type = 'word'
     GROUP BY game_level
     ORDER BY CASE game_level WHEN 'easy' THEN 1 WHEN 'medium' THEN 2 WHEN 'hard' THEN 3 ELSE 4 END",
    [':id' => $userId]
);
$wordRows = fetchAll($stmtWord);

$wordTotal = 0; $wordBest = 0;
$wordDiff  = [];    // ['easy'=>[total,wins,best_score,best_time], …]
foreach ($wordRows as $r) {
    $wordDiff[$r['game_level']] = $r;
    $wordTotal += $r['total'];
    if ($r['best_score'] > $wordBest) $wordBest = $r['best_score'];
}

// ── Statistik Puzzle ──────────────────────────────────────────────
// game_level: 'level-1' … 'level-10'
// Level tertinggi dicapai = MAX numeriknya
// Level paling sering     = MODE (ambil via subquery rank)
$stmtPuzz = executeQuery(
    "SELECT game_level,
            COUNT(*)                                     AS total,
            SUM(CASE WHEN score > 0 THEN 1 ELSE 0 END)  AS wins,
            MAX(score)                                   AS best_score,
            MIN(CASE WHEN score > 0 THEN duration END)  AS best_time,
            TO_NUMBER(REGEXP_SUBSTR(game_level,'[0-9]+')) AS lvl_num
     FROM scores
     WHERE user_id = :id AND game_type = 'puzzle'
     GROUP BY game_level",
    [':id' => $userId]
);
$puzzRows = fetchAll($stmtPuzz);

$puzzTotal    = 0;
$puzzBest     = 0;
$puzzMaxLvl   = 0;   // level tertinggi yang pernah dimainkan
$puzzMostLvl  = '-'; // level paling sering
$puzzMostCnt  = 0;

foreach ($puzzRows as $r) {
    $puzzTotal += $r['total'];
    if ($r['best_score'] > $puzzBest) $puzzBest = $r['best_score'];
    if ((int)$r['lvl_num'] > $puzzMaxLvl) $puzzMaxLvl = (int)$r['lvl_num'];
    if ((int)$r['total'] > $puzzMostCnt) {
        $puzzMostCnt = (int)$r['total'];
        $puzzMostLvl = 'Level ' . (int)$r['lvl_num'];
    }
}
$puzzMaxDisplay = $puzzMaxLvl > 0 ? 'Level ' . $puzzMaxLvl : '-';

// ── Riwayat 10 game terakhir ──────────────────────────────────────
$stmtHist = executeQuery(
    "SELECT game_type, game_level, score, duration, created_at
     FROM scores
     WHERE user_id = :id
     ORDER BY created_at DESC
     FETCH FIRST 10 ROWS ONLY",
    [':id' => $userId]
);
$history = fetchAll($stmtHist);

// ── Helpers ───────────────────────────────────────────────────────
$initials = strtoupper(substr($userDetail['username'] ?? $user['username'], 0, 2));

$joinDate = '';
if (!empty($userDetail['created_at'])) {
    $ts = is_string($userDetail['created_at']) ? strtotime($userDetail['created_at']) : 0;
    if ($ts) $joinDate = date('d M Y', $ts);
    else     $joinDate = $userDetail['created_at'];
}

function fmtDur($sec) {
    if ($sec === null || $sec === '') return '-';
    $sec = (int)$sec;
    if ($sec <= 0) return '-';
    return $sec < 60 ? $sec . 'd' : floor($sec/60) . 'm ' . ($sec % 60) . 'd';
}

function winratePct($wins, $total) {
    if (!$total) return '0%';
    return round($wins / $total * 100) . '%';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Profil — KeBox</title>
<link rel="stylesheet" href="css/style.css">
<style>
/* ── PROFILE HERO ─────────────────────────── */
.profile-hero {
    display:flex; align-items:center; gap:2rem; flex-wrap:wrap;
    padding:2rem; background:var(--bg-card);
    border:1px solid var(--border); border-radius:20px;
    margin-bottom:1.5rem; box-shadow:var(--shadow-purple);
}
.avatar {
    width:88px; height:88px; border-radius:50%; flex-shrink:0;
    background:linear-gradient(135deg,var(--purple-main),var(--purple-mid));
    display:flex; align-items:center; justify-content:center;
    font-family:'Orbitron',monospace; font-size:2rem; font-weight:900; color:#fff;
    box-shadow:0 0 30px rgba(124,77,255,.5); border:2px solid var(--purple-bright);
}
.profile-info { flex:1; min-width:200px; }
.profile-info h2 { font-family:'Orbitron',monospace; font-size:1.5rem; color:var(--text-main); margin-bottom:.3rem; }
.profile-meta { display:flex; flex-wrap:wrap; gap:.5rem; margin-top:.6rem; }
.meta-chip {
    display:inline-flex; align-items:center; gap:.35rem;
    font-size:.8rem; color:var(--text-dim); background:var(--bg-card2);
    border:1px solid var(--border); border-radius:20px; padding:.25rem .75rem;
}
.badge-role-user  { background:rgba(124,77,255,.15); border-color:var(--purple-mid); color:var(--purple-bright); }
.badge-role-admin { background:rgba(255,215,64,.12); border-color:rgba(255,215,64,.4); color:var(--accent-gold); }

/* ── SECTION LABEL ────────────────────────── */
.section-label {
    font-family:'Orbitron',monospace; font-size:.72rem;
    letter-spacing:2px; color:var(--text-muted); text-transform:uppercase;
    margin-bottom:.85rem;
}

/* ── GAME DETAIL CARD ─────────────────────── */
.game-detail-card {
    background:var(--bg-card); border:1px solid var(--border);
    border-radius:16px; padding:1.5rem; margin-bottom:1.5rem;
    transition:border-color .25s;
}
.game-detail-card.word   { border-top:3px solid var(--accent-cyan); }
.game-detail-card.puzzle { border-top:3px solid var(--accent-gold); }
.game-detail-card:hover  { border-color:var(--purple-mid); box-shadow:var(--shadow-glow); }

.game-detail-card h4 {
    font-family:'Orbitron',monospace; font-size:.95rem; margin-bottom:1.2rem;
    display:flex; align-items:center; gap:.5rem;
}

/* total + best per card */
.card-summary {
    display:flex; gap:1.5rem; flex-wrap:wrap;
    padding-bottom:1rem; margin-bottom:1.2rem;
    border-bottom:1px solid var(--border);
}
.card-summary-item { text-align:center; }
.card-summary-item .num {
    font-family:'Orbitron',monospace; font-size:1.8rem; font-weight:900;
    color:var(--purple-bright); line-height:1;
}
.card-summary-item .num.cyan  { color:var(--accent-cyan); }
.card-summary-item .num.gold  { color:var(--accent-gold); }
.card-summary-item .lbl { color:var(--text-dim); font-size:.72rem; text-transform:uppercase; letter-spacing:1px; margin-top:.2rem; }

/* difficulty breakdown table */
.diff-table { width:100%; border-collapse:collapse; font-size:.85rem; }
.diff-table th {
    color:var(--text-muted); font-size:.7rem; text-transform:uppercase;
    letter-spacing:1px; padding:.4rem .6rem; text-align:left;
    font-family:'Orbitron',monospace; font-weight:400;
    border-bottom:1px solid var(--border);
}
.diff-table td { padding:.55rem .6rem; border-bottom:1px solid rgba(45,31,94,.5); vertical-align:middle; }
.diff-table tr:last-child td { border-bottom:none; }
.diff-table tr:hover td { background:rgba(124,77,255,.04); }

.winrate-bar-wrap { display:flex; align-items:center; gap:.5rem; }
.winrate-bar-bg { flex:1; height:6px; background:var(--bg-card2); border-radius:3px; min-width:60px; }
.winrate-bar-fill { height:100%; border-radius:3px; background:linear-gradient(90deg,var(--purple-main),var(--purple-bright)); }
.winrate-pct { font-family:'Orbitron',monospace; font-size:.75rem; color:var(--purple-bright); min-width:34px; text-align:right; }

/* puzzle info rows */
.puzz-info-row { display:flex; justify-content:space-between; align-items:center; padding:.55rem 0; border-bottom:1px solid rgba(45,31,94,.5); font-size:.88rem; }
.puzz-info-row:last-child { border-bottom:none; }
.puzz-info-row .key { color:var(--text-dim); }
.puzz-info-row .val { font-family:'Orbitron',monospace; font-size:.82rem; font-weight:700; color:var(--text-main); }

/* ── HISTORY TABLE ────────────────────────── */
.history-table { width:100%; border-collapse:collapse; font-size:.88rem; }
.history-table th {
    background:var(--bg-card2); color:var(--text-muted);
    font-size:.7rem; letter-spacing:1px; text-transform:uppercase;
    padding:.6rem .9rem; text-align:left;
    font-family:'Orbitron',monospace; font-weight:400;
}
.history-table td { padding:.65rem .9rem; border-bottom:1px solid var(--border); color:var(--text-main); vertical-align:middle; }
.history-table tr:last-child td { border-bottom:none; }
.history-table tr:hover td { background:rgba(124,77,255,.05); }
.badge-word   { background:rgba(0,229,255,.12); color:var(--accent-cyan);  border:1px solid rgba(0,229,255,.3);  padding:.15rem .55rem; border-radius:20px; font-size:.72rem; font-weight:700; }
.badge-puzzle { background:rgba(255,215,64,.1);  color:var(--accent-gold); border:1px solid rgba(255,215,64,.3); padding:.15rem .55rem; border-radius:20px; font-size:.72rem; font-weight:700; }
.score-val { font-family:'Orbitron',monospace; font-size:.82rem; color:var(--purple-bright); font-weight:700; }
.empty-state { text-align:center; color:var(--text-muted); padding:2.5rem 1rem; }

/* difficulty label colors */
.diff-easy   { color:var(--accent-green); font-weight:700; }
.diff-medium { color:var(--accent-gold);  font-weight:700; }
.diff-hard   { color:var(--accent-red);   font-weight:700; }
</style>
</head>
<body>

<?php
$navActive = 'profile';
$navBase   = '';
require_once 'includes/navbar.php';
?>

<div class="container" style="padding-top:2rem;padding-bottom:3rem;max-width:860px">

    <!-- ── PROFILE HERO ── -->
    <div class="profile-hero">
        <div class="avatar"><?= htmlspecialchars($initials) ?></div>
        <div class="profile-info">
            <h2><?= htmlspecialchars($userDetail['username'] ?? $user['username']) ?></h2>
            <div class="profile-meta">
                <span class="meta-chip">Email: <?= htmlspecialchars($userDetail['email'] ?? '—') ?></span>
                <?php if ($joinDate): ?>
                <span class="meta-chip">Bergabung pada <?= htmlspecialchars($joinDate) ?></span>
                <?php endif; ?>
                <span class="meta-chip badge-role-<?= $user['role'] === 'admin' ? 'admin' : 'user' ?>">
                    <?= $user['role'] === 'admin' ? '⭐ Admin' : '🎮 Player' ?>
                </span>
            </div>
        </div>
    </div>

    <!-- ── WORD GAME DETAIL ── -->
    <div class="section-label">Word Game</div>
    <div class="game-detail-card word">
        <h4 style="color:var(--accent-cyan)">💬 Word Game</h4>

        <?php if ($wordTotal > 0): ?>

        <!-- ringkasan singkat -->
        <div class="card-summary">
            <div class="card-summary-item">
                <div class="num cyan"><?= $wordTotal ?></div>
                <div class="lbl">Total Game</div>
            </div>
            <div class="card-summary-item">
                <div class="num"><?= $wordBest ?></div>
                <div class="lbl">Best Score</div>
            </div>
        </div>

        <!-- breakdown per difficulty -->
        <table class="diff-table">
            <thead>
                <tr>
                    <th>Kesulitan</th>
                    <th style="text-align:center">Dimainkan</th>
                    <th style="text-align:center">Menang</th>
                    <th>Winrate</th>
                    <th style="text-align:right">Best Score</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $diffLabels = ['easy'=>'Easy','medium'=>'Medium','hard'=>'Hard'];
            foreach ($diffLabels as $key => $label):
                if (empty($wordDiff[$key])) continue;
                $r = $wordDiff[$key];
                $pct = $r['total'] > 0 ? round($r['wins'] / $r['total'] * 100) : 0;
            ?>
                <tr>
                    <td><span class="diff-<?= $key ?>"><?= $label ?></span></td>
                    <td style="text-align:center;color:var(--text-dim)"><?= $r['total'] ?></td>
                    <td style="text-align:center;color:var(--accent-green)"><?= $r['wins'] ?></td>
                    <td>
                        <div class="winrate-bar-wrap">
                            <div class="winrate-bar-bg">
                                <div class="winrate-bar-fill" style="width:<?= $pct ?>%"></div>
                            </div>
                            <span class="winrate-pct"><?= $pct ?>%</span>
                        </div>
                    </td>
                    <td style="text-align:right">
                        <span class="score-val"><?= (int)$r['best_score'] ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php else: ?>
        <p class="empty-state">Belum pernah main Word Game. <a href="game/word-select.php" style="color:var(--purple-bright)">Main sekarang →</a></p>
        <?php endif; ?>
    </div>

    <!-- ── SLIDE PUZZLE DETAIL ── -->
    <div class="section-label">Slide Puzzle</div>
    <div class="game-detail-card puzzle">
        <h4 style="color:var(--accent-gold)">🧩 Slide Puzzle</h4>

        <?php if ($puzzTotal > 0): ?>

        <!-- ringkasan singkat -->
        <div class="card-summary">
            <div class="card-summary-item">
                <div class="num gold"><?= $puzzTotal ?></div>
                <div class="lbl">Total Game</div>
            </div>
            <div class="card-summary-item">
                <div class="num"><?= $puzzBest ?></div>
                <div class="lbl">Best Score</div>
            </div>
        </div>

        <!-- info rows -->
        <div class="puzz-info-row">
            <span class="key">🏆 Level Tertinggi Dicapai</span>
            <span class="val" style="color:var(--accent-gold)"><?= htmlspecialchars($puzzMaxDisplay) ?></span>
        </div>
        <div class="puzz-info-row">
            <span class="key">🔁 Level Paling Sering Dimainkan</span>
            <span class="val"><?= htmlspecialchars($puzzMostLvl) ?> <span style="color:var(--text-muted);font-family:'Rajdhani',sans-serif;font-weight:400;font-size:.8rem">(<?= $puzzMostCnt ?>×)</span></span>
        </div>

        <?php else: ?>
        <p class="empty-state">Belum pernah main Slide Puzzle. <a href="game/puzzle-select.php" style="color:var(--purple-bright)">Main sekarang →</a></p>
        <?php endif; ?>
    </div>

    <!-- ── RIWAYAT 10 GAME TERAKHIR ── -->
    <div class="section-label">Riwayat 10 Permainan Terakhir</div>
    <div class="card" style="padding:0;overflow:hidden">
        <?php if (!empty($history)): ?>
        <div class="table-wrapper" style="border:none;border-radius:16px">
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Game</th>
                        <th>Level</th>
                        <th>Skor</th>
                        <th>Durasi</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($history as $h):
                    $ts  = is_string($h['created_at']) ? strtotime($h['created_at']) : 0;
                    $tgl = $ts ? date('d/m/Y', $ts) : $h['created_at'];
                    $gt  = $h['game_type'];
                ?>
                    <tr>
                        <td><span class="badge-<?= htmlspecialchars($gt) ?>"><?= strtoupper($gt) ?></span></td>
                        <td style="color:var(--text-dim)"><?= htmlspecialchars($h['game_level'] ?? '—') ?></td>
                        <td><span class="score-val"><?= (int)$h['score'] ?></span></td>
                        <td style="color:var(--text-dim);font-size:.8rem"><?= fmtDur($h['duration']) ?></td>
                        <td style="color:var(--text-muted);font-size:.82rem"><?= htmlspecialchars($tgl) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <p style="font-size:2rem;margin-bottom:.5rem">🎮</p>
            <p style="color:var(--text-muted)">Belum ada riwayat permainan.<br>Yuk mulai main!</p>
            <a href="dashboard.php" class="btn btn-primary btn-sm mt-2">Pilih Game</a>
        </div>
        <?php endif; ?>
    </div>

</div>

<script src="music.js"></script>
</body>
</html>
