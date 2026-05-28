<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
$user = getCurrentUser();

// ── Mapping grid_size ke game_level label ──────────────────────────
// puzzle_levels: level_num 8=6x6, 9=7x7, 10=8x8, 11=9x9, 12=10x10
// Scores disimpan sebagai game_level = 'level-{level_num}'
$puzzleTabs = [
    ['label' => '6×6', 'game_level' => 'level-8',  'grid' => 6],
    ['label' => '7×7', 'game_level' => 'level-9',  'grid' => 7],
    ['label' => '8×8', 'game_level' => 'level-10', 'grid' => 8],
    ['label' => '9×9', 'game_level' => 'level-11', 'grid' => 9],
    ['label' => '10×10','game_level'=> 'level-12', 'grid' => 10],
];

// ── Tab aktif ──────────────────────────────────────────────────────
$section     = in_array($_GET['section'] ?? '', ['word','puzzle']) ? $_GET['section'] : 'word';
$activeGrid  = in_array($_GET['grid'] ?? '', ['6','7','8','9','10']) ? (int)$_GET['grid'] : 6;

// ── Query Word Game Hard – top 50, best score per player ───────────
$stmtWord = executeQuery(
    "SELECT rn, username, best_score, best_time_sec
     FROM (
         SELECT
             ROW_NUMBER() OVER (ORDER BY MAX(s.score) DESC, MIN(CASE WHEN s.score > 0 THEN s.duration END) ASC NULLS LAST) AS rn,
             u.username,
             MAX(s.score)                                       AS best_score,
             MIN(CASE WHEN s.score > 0 THEN s.duration END)    AS best_time_sec
         FROM scores s
         JOIN users u ON s.user_id = u.id
         WHERE s.game_type = 'word'
           AND s.game_level = 'hard'
           AND s.score > 0
         GROUP BY u.id, u.username
     )
     WHERE rn <= 50
     ORDER BY rn"
);
$wordRows = fetchAll($stmtWord);

// ── Query Puzzle per grid – top 50, best score per player ──────────
$puzzleData = [];
foreach ($puzzleTabs as $tab) {
    $gl = $tab['game_level'];
    $stmt = executeQuery(
        "SELECT rn, username, best_score, best_time_sec
         FROM (
             SELECT
                 ROW_NUMBER() OVER (ORDER BY MAX(s.score) DESC, MIN(CASE WHEN s.score > 0 THEN s.duration END) ASC NULLS LAST) AS rn,
                 u.username,
                 MAX(s.score)                                       AS best_score,
                 MIN(CASE WHEN s.score > 0 THEN s.duration END)    AS best_time_sec
             FROM scores s
             JOIN users u ON s.user_id = u.id
             WHERE s.game_type = 'puzzle'
               AND s.game_level = '$gl'
             GROUP BY u.id, u.username
         )
         WHERE rn <= 50
         ORDER BY rn"
    );
    $puzzleData[$tab['grid']] = fetchAll($stmt);
}

// ── Helper format durasi ───────────────────────────────────────────
function fmtTime($sec) {
    if ($sec === null || $sec === '') return '—';
    $s = (int)$sec;
    if ($s < 60) return $s . 's';
    return floor($s / 60) . 'm ' . ($s % 60) . 's';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Leaderboard — KeBox</title>
<link rel="stylesheet" href="css/style.css">
<style>
/* ── PAGE HEADER ── */
.lb-hero {
    text-align: center;
    padding: 3rem 0 2rem;
}
.lb-hero-title {
    font-family: 'Orbitron', monospace;
    font-size: 2.2rem;
    font-weight: 900;
    color: var(--text-main);
    letter-spacing: 3px;
    text-shadow: 0 0 30px rgba(124,77,255,.5);
}
.lb-hero-title span { color: var(--accent-gold); }
.lb-hero-sub { color: var(--text-dim); margin-top: .5rem; font-size: 1rem; }

/* ── SECTION TABS (Word / Puzzle) ── */
.lb-section-tabs {
    display: flex;
    justify-content: center;
    gap: .75rem;
    margin-bottom: 2rem;
}
.lb-section-tab {
    font-family: 'Orbitron', monospace;
    font-size: .8rem;
    letter-spacing: 2px;
    padding: .6rem 2rem;
    border-radius: 12px;
    border: 1px solid var(--border);
    background: var(--bg-card);
    color: var(--text-dim);
    cursor: pointer;
    text-decoration: none;
    transition: all .2s;
    display: inline-flex;
    align-items: center;
    gap: .5rem;
}
.lb-section-tab:hover {
    border-color: var(--purple-mid);
    color: var(--text-main);
    background: rgba(124,77,255,.1);
}
.lb-section-tab.active {
    background: linear-gradient(135deg, rgba(124,77,255,.25), rgba(0,229,255,.1));
    border-color: var(--purple-main);
    color: var(--text-main);
    box-shadow: 0 0 20px rgba(124,77,255,.2);
}

/* ── GRID TABS (Puzzle size) ── */
.lb-grid-tabs {
    display: flex;
    gap: .5rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}
.lb-grid-tab {
    font-family: 'Orbitron', monospace;
    font-size: .75rem;
    letter-spacing: 1px;
    padding: .4rem 1.2rem;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: var(--bg-card);
    color: var(--text-dim);
    text-decoration: none;
    transition: all .2s;
}
.lb-grid-tab:hover {
    border-color: var(--accent-cyan);
    color: var(--accent-cyan);
}
.lb-grid-tab.active {
    background: rgba(0,229,255,.1);
    border-color: var(--accent-cyan);
    color: var(--accent-cyan);
    box-shadow: 0 0 12px rgba(0,229,255,.15);
}

/* ── TABLE ── */
.lb-table-wrap {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: var(--shadow-purple);
}
.lb-table {
    width: 100%;
    border-collapse: collapse;
}
.lb-table thead tr {
    background: rgba(124,77,255,.12);
    border-bottom: 1px solid var(--border);
}
.lb-table th {
    font-family: 'Orbitron', monospace;
    font-size: .7rem;
    letter-spacing: 2px;
    color: var(--text-muted);
    padding: .9rem 1.2rem;
    text-align: left;
    text-transform: uppercase;
}
.lb-table th.num { text-align: center; }
.lb-table td {
    padding: .75rem 1.2rem;
    color: var(--text-dim);
    font-size: .95rem;
    border-bottom: 1px solid rgba(45,31,94,.5);
    vertical-align: middle;
}
.lb-table tbody tr:last-child td { border-bottom: none; }
.lb-table tbody tr:hover td { background: rgba(124,77,255,.06); }

/* Rank badges */
.rank-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    font-family: 'Orbitron', monospace;
    font-size: .75rem;
    font-weight: 700;
}
.rank-1 { background: rgba(255,215,64,.2);  border: 1px solid var(--accent-gold); color: var(--accent-gold);  box-shadow: 0 0 12px rgba(255,215,64,.3); }
.rank-2 { background: rgba(200,200,220,.1); border: 1px solid #a0a0c0; color: #c0c0d8; }
.rank-3 { background: rgba(205,127,50,.15); border: 1px solid #cd7f32; color: #e09a50; }
.rank-n { background: rgba(45,31,94,.5);    border: 1px solid var(--border); color: var(--text-muted); }

.td-rank { text-align: center; width: 60px; }
.td-score {
    font-family: 'Orbitron', monospace;
    font-size: 1rem;
    color: var(--purple-bright);
    font-weight: 700;
}
.td-time { color: var(--accent-cyan); font-size: .88rem; }
.td-name { color: var(--text-main); font-weight: 600; font-size: 1rem; }
.td-name.me { color: var(--accent-gold); }

/* Top-3 row highlight */
.lb-table tbody tr.top1 td { background: rgba(255,215,64,.04); }
.lb-table tbody tr.top2 td { background: rgba(192,192,210,.03); }
.lb-table tbody tr.top3 td { background: rgba(205,127,50,.04); }

/* Empty state */
.lb-empty {
    padding: 4rem 2rem;
    text-align: center;
    color: var(--text-muted);
}
.lb-empty .icon { font-size: 3rem; display: block; margin-bottom: 1rem; }
.lb-empty p { font-size: .95rem; }

/* Podium mini for top 3 */
.podium {
    display: flex;
    justify-content: center;
    align-items: flex-end;
    gap: 1rem;
    margin-bottom: 2rem;
}
.podium-slot {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .5rem;
}
.podium-avatar {
    width: 52px; height: 52px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Orbitron', monospace;
    font-size: 1.1rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 1px;
    border: 2px solid;
}
.podium-slot-1 .podium-avatar { background: rgba(255,215,64,.15); border-color: var(--accent-gold); color: var(--accent-gold); box-shadow: 0 0 20px rgba(255,215,64,.3); }
.podium-slot-2 .podium-avatar { background: rgba(180,180,210,.08); border-color: #a0a0c0; color: #c0c0d8; }
.podium-slot-3 .podium-avatar { background: rgba(205,127,50,.1);  border-color: #cd7f32; color: #e09a50; }
.podium-name   { font-size: .85rem; color: var(--text-main); font-weight: 600; }
.podium-score  { font-family: 'Orbitron', monospace; font-size: .8rem; color: var(--purple-bright); }
.podium-crown  { font-size: 1.4rem; }
.podium-bar {
    display: flex; align-items: center; justify-content: center;
    border-radius: 8px 8px 0 0;
    font-family: 'Orbitron', monospace;
    font-size: .9rem; font-weight: 900;
    border: 1px solid;
    width: 80px;
}
.podium-bar-1 { height: 70px; background: rgba(255,215,64,.12); border-color: rgba(255,215,64,.4); color: var(--accent-gold); }
.podium-bar-2 { height: 52px; background: rgba(180,180,210,.08); border-color: rgba(180,180,210,.3); color: #a0a0c0; }
.podium-bar-3 { height: 38px; background: rgba(205,127,50,.1);   border-color: rgba(205,127,50,.3); color: #cd7f32; }

.lb-info-badge {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .35rem .9rem;
    border-radius: 20px;
    font-size: .8rem;
    background: rgba(124,77,255,.1);
    border: 1px solid rgba(124,77,255,.25);
    color: var(--purple-bright);
    margin-bottom: 1.5rem;
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a href="index.php" class="navbar-brand">Ke<span>Box</span></a>
    <ul class="navbar-nav">
        <?php if ($user['id']): ?>
            <li><a href="<?= $user['role']==='admin' ? 'admin/dashboard.php' : 'dashboard.php' ?>">Dashboard</a></li>
            <li><a href="leaderboard.php" class="active">Leaderboard</a></li>
            <li><a href="profile.php">Profil</a></li>
            <li><a href="logout.php" class="btn btn-outline btn-sm">Logout</a></li>
        <?php else: ?>
            <li><a href="leaderboard.php" class="active">Leaderboard</a></li>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php" class="btn btn-primary btn-sm">Register</a></li>
        <?php endif; ?>
    </ul>
</nav>

<div class="container" style="padding-bottom:4rem">

    <!-- HERO -->
    <div class="lb-hero">
        <div class="lb-hero-title">🏆 Leader<span>board</span></div>
        <div class="lb-hero-sub">Top 50 pemain terbaik di setiap kategori</div>
    </div>

    <!-- SECTION TABS -->
    <div class="lb-section-tabs">
        <a href="leaderboard.php?section=word"
           class="lb-section-tab <?= $section==='word' ? 'active' : '' ?>">
            📝 Word Game <span style="font-size:.7rem;color:var(--accent-red)">HARD</span>
        </a>
        <a href="leaderboard.php?section=puzzle"
           class="lb-section-tab <?= $section==='puzzle' ? 'active' : '' ?>">
            🧩 Slide Puzzle
        </a>
    </div>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- WORD GAME SECTION -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <?php if ($section === 'word'): ?>

        <div style="text-align:center;margin-bottom:1.5rem">
            <span class="lb-info-badge">
                🔴 Hanya dari permainan level <strong>Hard</strong> · Best score per player
            </span>
        </div>

        <?php if (count($wordRows) >= 3): ?>
        <!-- PODIUM TOP 3 -->
        <div class="podium">
            <!-- 2nd -->
            <?php if (isset($wordRows[1])): $r = $wordRows[1]; ?>
            <div class="podium-slot podium-slot-2">
                <div class="podium-avatar"><?= mb_substr($r['username'],0,2) ?></div>
                <div class="podium-name"><?= htmlspecialchars($r['username']) ?></div>
                <div class="podium-score"><?= number_format($r['best_score']) ?></div>
                <div class="podium-bar podium-bar-2">2</div>
            </div>
            <?php endif; ?>
            <!-- 1st -->
            <?php if (isset($wordRows[0])): $r = $wordRows[0]; ?>
            <div class="podium-slot podium-slot-1">
                <div class="podium-crown">👑</div>
                <div class="podium-avatar"><?= mb_substr($r['username'],0,2) ?></div>
                <div class="podium-name"><?= htmlspecialchars($r['username']) ?></div>
                <div class="podium-score"><?= number_format($r['best_score']) ?></div>
                <div class="podium-bar podium-bar-1">1</div>
            </div>
            <?php endif; ?>
            <!-- 3rd -->
            <?php if (isset($wordRows[2])): $r = $wordRows[2]; ?>
            <div class="podium-slot podium-slot-3">
                <div class="podium-avatar"><?= mb_substr($r['username'],0,2) ?></div>
                <div class="podium-name"><?= htmlspecialchars($r['username']) ?></div>
                <div class="podium-score"><?= number_format($r['best_score']) ?></div>
                <div class="podium-bar podium-bar-3">3</div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- TABLE -->
        <?php if (empty($wordRows)): ?>
            <div class="lb-empty">
                <span class="icon">📭</span>
                <p>Belum ada data leaderboard.<br>Jadilah yang pertama menorehkan skor di level Hard!</p>
            </div>
        <?php else: ?>
        <div class="lb-table-wrap">
            <table class="lb-table">
                <thead>
                    <tr>
                        <th class="num">#</th>
                        <th>Player</th>
                        <th>Best Score</th>
                        <th>Best Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($wordRows as $r):
                        $rn = (int)$r['rn'];
                        $rowClass = $rn===1 ? 'top1' : ($rn===2 ? 'top2' : ($rn===3 ? 'top3' : ''));
                        $badgeClass = $rn===1 ? 'rank-1' : ($rn===2 ? 'rank-2' : ($rn===3 ? 'rank-3' : 'rank-n'));
                        $isMe = $user['id'] && strtolower($r['username']) === strtolower($user['username'] ?? '');
                    ?>
                    <tr class="<?= $rowClass ?>">
                        <td class="td-rank">
                            <span class="rank-badge <?= $badgeClass ?>">
                                <?= $rn===1 ? '🥇' : ($rn===2 ? '🥈' : ($rn===3 ? '🥉' : $rn)) ?>
                            </span>
                        </td>
                        <td class="td-name <?= $isMe ? 'me' : '' ?>">
                            <?= htmlspecialchars($r['username']) ?>
                            <?php if ($isMe): ?><span style="font-size:.75rem;color:var(--accent-gold);margin-left:.4rem">(Kamu)</span><?php endif; ?>
                        </td>
                        <td class="td-score"><?= number_format($r['best_score']) ?></td>
                        <td class="td-time"><?= fmtTime($r['best_time_sec']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- SLIDE PUZZLE SECTION -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <?php if ($section === 'puzzle'): ?>

        <div style="text-align:center;margin-bottom:1.5rem">
            <span class="lb-info-badge">
                🧩 Grid 6×6 ke atas · Best score per player per ukuran grid
            </span>
        </div>

        <!-- GRID SIZE TABS -->
        <div class="lb-grid-tabs">
            <?php foreach ($puzzleTabs as $tab): ?>
            <a href="leaderboard.php?section=puzzle&grid=<?= $tab['grid'] ?>"
               class="lb-grid-tab <?= $activeGrid===$tab['grid'] ? 'active' : '' ?>">
                <?= $tab['label'] ?>
            </a>
            <?php endforeach; ?>
        </div>

        <?php
        $rows = $puzzleData[$activeGrid] ?? [];
        $curTab = null;
        foreach ($puzzleTabs as $t) { if ($t['grid']===$activeGrid) { $curTab=$t; break; } }
        ?>

        <?php if (count($rows) >= 3): ?>
        <!-- PODIUM TOP 3 -->
        <div class="podium">
            <?php if (isset($rows[1])): $r = $rows[1]; ?>
            <div class="podium-slot podium-slot-2">
                <div class="podium-avatar"><?= mb_substr($r['username'],0,2) ?></div>
                <div class="podium-name"><?= htmlspecialchars($r['username']) ?></div>
                <div class="podium-score"><?= number_format($r['best_score']) ?></div>
                <div class="podium-bar podium-bar-2">2</div>
            </div>
            <?php endif; ?>
            <?php if (isset($rows[0])): $r = $rows[0]; ?>
            <div class="podium-slot podium-slot-1">
                <div class="podium-crown">👑</div>
                <div class="podium-avatar"><?= mb_substr($r['username'],0,2) ?></div>
                <div class="podium-name"><?= htmlspecialchars($r['username']) ?></div>
                <div class="podium-score"><?= number_format($r['best_score']) ?></div>
                <div class="podium-bar podium-bar-1">1</div>
            </div>
            <?php endif; ?>
            <?php if (isset($rows[2])): $r = $rows[2]; ?>
            <div class="podium-slot podium-slot-3">
                <div class="podium-avatar"><?= mb_substr($r['username'],0,2) ?></div>
                <div class="podium-name"><?= htmlspecialchars($r['username']) ?></div>
                <div class="podium-score"><?= number_format($r['best_score']) ?></div>
                <div class="podium-bar podium-bar-3">3</div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- TABLE -->
        <?php if (empty($rows)): ?>
            <div class="lb-empty">
                <span class="icon">📭</span>
                <p>Belum ada pemain yang menyelesaikan grid <?= $curTab['label'] ?>.<br>
                   Jadilah yang pertama!</p>
            </div>
        <?php else: ?>
        <div class="lb-table-wrap">
            <table class="lb-table">
                <thead>
                    <tr>
                        <th class="num">#</th>
                        <th>Player</th>
                        <th>Best Score</th>
                        <th>Best Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r):
                        $rn = (int)$r['rn'];
                        $rowClass = $rn===1 ? 'top1' : ($rn===2 ? 'top2' : ($rn===3 ? 'top3' : ''));
                        $badgeClass = $rn===1 ? 'rank-1' : ($rn===2 ? 'rank-2' : ($rn===3 ? 'rank-3' : 'rank-n'));
                        $isMe = $user['id'] && strtolower($r['username']) === strtolower($user['username'] ?? '');
                    ?>
                    <tr class="<?= $rowClass ?>">
                        <td class="td-rank">
                            <span class="rank-badge <?= $badgeClass ?>">
                                <?= $rn===1 ? '🥇' : ($rn===2 ? '🥈' : ($rn===3 ? '🥉' : $rn)) ?>
                            </span>
                        </td>
                        <td class="td-name <?= $isMe ? 'me' : '' ?>">
                            <?= htmlspecialchars($r['username']) ?>
                            <?php if ($isMe): ?><span style="font-size:.75rem;color:var(--accent-gold);margin-left:.4rem">(Kamu)</span><?php endif; ?>
                        </td>
                        <td class="td-score"><?= number_format($r['best_score']) ?></td>
                        <td class="td-time"><?= fmtTime($r['best_time_sec']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    <?php endif; ?>

</div>

<footer style="border-top:1px solid var(--border);padding:2rem;text-align:center;color:var(--text-muted);font-size:.85rem">
    <strong style="color:var(--purple-bright)">KeBox</strong> &copy; <?= date('Y') ?> — Platform Game Online
</footer>

</body>
</html>
