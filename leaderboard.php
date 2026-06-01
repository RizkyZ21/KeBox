<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
$user = getCurrentUser();

// ── Level tabs word ──────────────────────────────────────────────
$wordLevels = [
    ['label' => 'Easy',   'level' => 'easy',   'len' => 4, 'color' => 'var(--accent-cyan)'],
    ['label' => 'Medium', 'level' => 'medium',  'len' => 5, 'color' => 'var(--accent-gold)'],
    ['label' => 'Hard',   'level' => 'hard',    'len' => 6, 'color' => 'var(--accent-red)'],
];

// ── Level tabs puzzle ────────────────────────────────────────────
$puzzleTabs = [
    ['label' => '6×6',  'game_level' => 'level-8',  'grid' => 6],
    ['label' => '7×7',  'game_level' => 'level-9',  'grid' => 7],
    ['label' => '8×8',  'game_level' => 'level-10', 'grid' => 8],
    ['label' => '9×9',  'game_level' => 'level-11', 'grid' => 9],
    ['label' => '10×10','game_level' => 'level-12', 'grid' => 10],
];

// ── Tab aktif ─────────────────────────────────────────────────────
$section     = in_array($_GET['section'] ?? '', ['word','puzzle']) ? $_GET['section'] : 'word';
$activeGrid  = in_array($_GET['grid'] ?? '', ['6','7','8','9','10']) ? (int)$_GET['grid'] : 6;
$activeWordLv= in_array($_GET['wlv'] ?? '', ['easy','medium','hard']) ? $_GET['wlv'] : 'hard';

// ── Query helper: word leaderboard per level ──────────────────────
// Ranking: skor tertinggi → waktu tercepat → percobaan paling sedikit
function queryWord($level) {
    $stmt = executeQuery(
        "SELECT rn, username, best_score, best_time_sec, best_attempts
         FROM (
             SELECT
                 ROW_NUMBER() OVER (
                     ORDER BY MAX(s.score) DESC,
                              MIN(CASE WHEN s.score > 0 THEN s.duration   END) ASC NULLS LAST,
                              MIN(CASE WHEN s.score > 0 THEN s.attempts   END) ASC NULLS LAST
                 ) AS rn,
                 u.username,
                 MAX(s.score)                                           AS best_score,
                 MIN(CASE WHEN s.score > 0 THEN s.duration   END)      AS best_time_sec,
                 MIN(CASE WHEN s.score > 0 THEN s.attempts   END)      AS best_attempts
             FROM scores s
             JOIN users u ON s.user_id = u.id
             WHERE s.game_type = 'word'
               AND s.game_level = '$level'
               AND s.score > 0
             GROUP BY u.id, u.username
         )
         WHERE rn <= 50
         ORDER BY rn"
    );
    return fetchAll($stmt);
}

// ── Query helper: puzzle leaderboard per grid ─────────────────────
// Ranking: skor tertinggi → moves paling sedikit → waktu tercepat
function queryPuzzle($gl) {
    $stmt = executeQuery(
        "SELECT rn, username, best_score, best_time_sec, best_moves
         FROM (
             SELECT
                 ROW_NUMBER() OVER (
                     ORDER BY MAX(s.score) DESC,
                              MIN(CASE WHEN s.score > 0 THEN s.moves     END) ASC NULLS LAST,
                              MIN(CASE WHEN s.score > 0 THEN s.duration  END) ASC NULLS LAST
                 ) AS rn,
                 u.username,
                 MAX(s.score)                                           AS best_score,
                 MIN(CASE WHEN s.score > 0 THEN s.moves     END)       AS best_moves,
                 MIN(CASE WHEN s.score > 0 THEN s.duration  END)       AS best_time_sec
             FROM scores s
             JOIN users u ON s.user_id = u.id
             WHERE s.game_type = 'puzzle'
               AND s.game_level = '$gl'
             GROUP BY u.id, u.username
         )
         WHERE rn <= 50
         ORDER BY rn"
    );
    return fetchAll($stmt);
}

// ── Ambil data sesuai section + tab aktif ─────────────────────────
$wordRows   = ($section === 'word')   ? queryWord($activeWordLv)   : [];
$puzzleRows = ($section === 'puzzle') ? queryPuzzle('level-' . ($activeGrid + 2)) : [];

// Map grid→level_num: 6→8, 7→9, 8→10, 9→11, 10→12
$gridToLevel = [6=>8, 7=>9, 8=>10, 9=>11, 10=>12];
$puzzleRows  = ($section === 'puzzle') ? queryPuzzle('level-' . $gridToLevel[$activeGrid]) : [];

// ── Helpers ───────────────────────────────────────────────────────
function fmtTime($sec) {
    if ($sec === null || $sec === '') return '—';
    $s = (int)$sec;
    if ($s < 60) return $s . 's';
    return floor($s / 60) . 'm ' . ($s % 60) . 's';
}
function fmtNum($v) {
    return ($v === null || $v === '') ? '—' : (int)$v;
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
.lb-hero { text-align:center; padding:3rem 0 2rem; }
.lb-hero-title {
    font-family:'Orbitron',monospace; font-size:2.2rem; font-weight:900;
    color:var(--text-main); letter-spacing:3px;
    text-shadow:0 0 30px rgba(124,77,255,.5);
}
.lb-hero-title span { color:var(--accent-gold); }
.lb-hero-sub { color:var(--text-dim); margin-top:.5rem; font-size:1rem; }

/* Section tabs */
.lb-section-tabs { display:flex; justify-content:center; gap:.75rem; margin-bottom:2rem; }
.lb-section-tab {
    font-family:'Orbitron',monospace; font-size:.8rem; letter-spacing:2px;
    padding:.6rem 2rem; border-radius:12px; border:1px solid var(--border);
    background:var(--bg-card); color:var(--text-dim); cursor:pointer;
    text-decoration:none; transition:all .2s; display:inline-flex; align-items:center; gap:.5rem;
}
.lb-section-tab:hover { border-color:var(--purple-mid); color:var(--text-main); background:rgba(124,77,255,.1); }
.lb-section-tab.active {
    background:linear-gradient(135deg,rgba(124,77,255,.25),rgba(0,229,255,.1));
    border-color:var(--purple-main); color:var(--text-main);
    box-shadow:0 0 20px rgba(124,77,255,.2);
}

/* Sub-tabs */
.lb-sub-tabs { display:flex; justify-content:center; gap:.5rem; margin-bottom:1.5rem; flex-wrap:wrap; }
.lb-sub-tab {
    font-family:'Orbitron',monospace; font-size:.75rem; letter-spacing:1px;
    padding:.4rem 1.2rem; border-radius:8px; border:1px solid var(--border);
    background:var(--bg-card); color:var(--text-dim); text-decoration:none; transition:all .2s;
}
.lb-sub-tab:hover  { border-color:var(--accent-cyan); color:var(--accent-cyan); }
.lb-sub-tab.active { background:rgba(0,229,255,.1); border-color:var(--accent-cyan); color:var(--accent-cyan); box-shadow:0 0 12px rgba(0,229,255,.15); }

/* Table */
.lb-table-wrap { background:var(--bg-card); border:1px solid var(--border); border-radius:16px; overflow:hidden; box-shadow:var(--shadow-purple); }
.lb-table { width:100%; border-collapse:collapse; }
.lb-table thead tr { background:rgba(124,77,255,.12); border-bottom:1px solid var(--border); }
.lb-table th { font-family:'Orbitron',monospace; font-size:.7rem; letter-spacing:2px; color:var(--text-muted); padding:.9rem 1.2rem; text-align:left; text-transform:uppercase; }
.lb-table th.num, .lb-table td.num { text-align:center; }
.lb-table td { padding:.75rem 1.2rem; color:var(--text-dim); font-size:.95rem; border-bottom:1px solid rgba(45,31,94,.5); vertical-align:middle; }
.lb-table tbody tr:last-child td { border-bottom:none; }
.lb-table tbody tr:hover td { background:rgba(124,77,255,.06); }

.rank-badge { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:50%; font-family:'Orbitron',monospace; font-size:.75rem; font-weight:700; }
.rank-1 { background:rgba(255,215,64,.2);  border:1px solid var(--accent-gold); color:var(--accent-gold);  box-shadow:0 0 12px rgba(255,215,64,.3); }
.rank-2 { background:rgba(200,200,220,.1); border:1px solid #a0a0c0; color:#c0c0d8; }
.rank-3 { background:rgba(205,127,50,.15); border:1px solid #cd7f32; color:#e09a50; }
.rank-n { background:rgba(45,31,94,.5);    border:1px solid var(--border); color:var(--text-muted); }

.td-rank  { text-align:center; width:60px; }
.td-score { font-family:'Orbitron',monospace; font-size:1rem; color:var(--purple-bright); font-weight:700; }
.td-time  { color:var(--accent-cyan); font-size:.88rem; }
.td-extra { color:var(--accent-gold); font-size:.88rem; }
.td-name  { color:var(--text-main); font-weight:600; font-size:1rem; }
.td-name.me { color:var(--accent-gold); }

.lb-table tbody tr.top1 td { background:rgba(255,215,64,.04); }
.lb-table tbody tr.top2 td { background:rgba(192,192,210,.03); }
.lb-table tbody tr.top3 td { background:rgba(205,127,50,.04); }

/* Podium */
.podium { display:flex; justify-content:center; align-items:flex-end; gap:1rem; margin-bottom:2rem; }
.podium-slot { display:flex; flex-direction:column; align-items:center; gap:.5rem; }
.podium-avatar { width:52px; height:52px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-family:'Orbitron',monospace; font-size:1.1rem; font-weight:900; text-transform:uppercase; letter-spacing:1px; border:2px solid; }
.podium-slot-1 .podium-avatar { background:rgba(255,215,64,.15); border-color:var(--accent-gold); color:var(--accent-gold); box-shadow:0 0 20px rgba(255,215,64,.3); }
.podium-slot-2 .podium-avatar { background:rgba(180,180,210,.08); border-color:#a0a0c0; color:#c0c0d8; }
.podium-slot-3 .podium-avatar { background:rgba(205,127,50,.1);   border-color:#cd7f32; color:#e09a50; }
.podium-name   { font-size:.85rem; color:var(--text-main); font-weight:600; }
.podium-score  { font-family:'Orbitron',monospace; font-size:.8rem; color:var(--purple-bright); }
.podium-extra  { font-size:.72rem; color:var(--text-muted); display:flex; flex-direction:column; align-items:center; gap:.15rem; margin-top:.1rem; }
.podium-extra span { background:rgba(255,255,255,.05); border:1px solid var(--border); border-radius:20px; padding:.1rem .5rem; white-space:nowrap; }
.podium-extra .t { color:var(--accent-cyan); }
.podium-extra .m { color:var(--accent-gold); }
.podium-crown  { font-size:1.4rem; }
.podium-extra  { font-size:.72rem; color:var(--text-muted); display:flex; flex-direction:column; align-items:center; gap:.15rem; margin:.15rem 0; }
.podium-extra span { background:rgba(255,255,255,.05); border:1px solid var(--border); border-radius:20px; padding:.1rem .55rem; white-space:nowrap; }
.podium-extra .t { color:var(--accent-cyan); }
.podium-extra .m { color:var(--accent-gold); }
.podium-bar    { display:flex; align-items:center; justify-content:center; border-radius:8px 8px 0 0; font-family:'Orbitron',monospace; font-size:.9rem; font-weight:900; border:1px solid; width:80px; }
.podium-bar-1  { height:70px; background:rgba(255,215,64,.12); border-color:rgba(255,215,64,.4); color:var(--accent-gold); }
.podium-bar-2  { height:52px; background:rgba(180,180,210,.08); border-color:rgba(180,180,210,.3); color:#a0a0c0; }
.podium-bar-3  { height:38px; background:rgba(205,127,50,.1);   border-color:rgba(205,127,50,.3); color:#cd7f32; }

.lb-info-badge { display:inline-flex; align-items:center; gap:.4rem; padding:.35rem .9rem; border-radius:20px; font-size:.8rem; background:rgba(124,77,255,.1); border:1px solid rgba(124,77,255,.25); color:var(--purple-bright); margin-bottom:1.5rem; }
.lb-sort-info  { font-size:.78rem; color:var(--text-muted); margin-bottom:1.5rem; display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; justify-content:center; }
.sort-chip     { padding:.2rem .65rem; border-radius:20px; border:1px solid var(--border); background:rgba(255,255,255,.03); font-size:.75rem; }
.sort-chip.p1  { border-color:var(--purple-bright); color:var(--purple-bright); }
.sort-chip.p2  { border-color:var(--accent-cyan);   color:var(--accent-cyan); }
.sort-chip.p3  { border-color:var(--accent-gold);   color:var(--accent-gold); }

.lb-empty { padding:4rem 2rem; text-align:center; color:var(--text-muted); }
.lb-empty .icon { font-size:3rem; display:block; margin-bottom:1rem; }
</style>
</head>
<body>

<?php
$navActive = 'leaderboard';
$navBase   = '';
require_once 'includes/navbar.php';
?>

<div class="container" style="padding-bottom:4rem">

    <div class="lb-hero">
        <div class="lb-hero-title">🏆 Leader<span>board</span></div>
        <div class="lb-hero-sub">Top 50 pemain terbaik di setiap kategori</div>
    </div>

    <!-- SECTION TABS -->
    <div class="lb-section-tabs">
        <a href="leaderboard.php?section=word&wlv=<?= $activeWordLv ?>"
           class="lb-section-tab <?= $section==='word' ? 'active' : '' ?>">
            📝 Word Game
        </a>
        <a href="leaderboard.php?section=puzzle&grid=<?= $activeGrid ?>"
           class="lb-section-tab <?= $section==='puzzle' ? 'active' : '' ?>">
            🧩 Slide Puzzle
        </a>
    </div>

    <?php if ($section === 'word'): ?>
    <!-- ═══════ WORD GAME ═══════ -->

    <!-- Level tabs -->
    <div class="lb-sub-tabs">
        <?php foreach ($wordLevels as $wl): ?>
        <a href="leaderboard.php?section=word&wlv=<?= $wl['level'] ?>"
           class="lb-sub-tab <?= $activeWordLv===$wl['level'] ? 'active' : '' ?>"
           style="<?= $activeWordLv===$wl['level'] ? "border-color:{$wl['color']};color:{$wl['color']};background:rgba(0,0,0,.15)" : '' ?>">
            <?= $wl['label'] ?> <span style="font-size:.65rem;opacity:.7">(<?= $wl['len'] ?> huruf)</span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Sort info -->
    <div class="lb-sort-info">
        Urutan:
        <span class="sort-chip p1">① Skor tertinggi</span>
        <span class="sort-chip p2">② Waktu tercepat</span>
        <span class="sort-chip p3">③ Percobaan paling sedikit</span>
    </div>

    <?php
    $rows = $wordRows;
    $curWL = null;
    foreach ($wordLevels as $wl) { if ($wl['level']===$activeWordLv) { $curWL=$wl; break; } }
    ?>

    <?php if (count($rows) >= 3): ?>
    <div class="podium">
        <?php if (isset($rows[1])): $r=$rows[1]; ?>
        <div class="podium-slot podium-slot-2">
            <div class="podium-avatar"><?= mb_substr($r['username'],0,2) ?></div>
            <div class="podium-name"><?= htmlspecialchars($r['username']) ?></div>
            <div class="podium-score"><?= number_format($r['best_score']) ?></div>
            <div class="podium-extra">
                <span class="t">⏱ <?= fmtTime($r['best_time_sec']) ?></span>
                <span class="m">🔁 <?= fmtNum($r['best_attempts'])!=='—' ? fmtNum($r['best_attempts'])."x" : '—' ?></span>
            </div>
            <div class="podium-bar podium-bar-2">2</div>
        </div>
        <?php endif; ?>
        <?php if (isset($rows[0])): $r=$rows[0]; ?>
        <div class="podium-slot podium-slot-1">
            <div class="podium-crown">👑</div>
            <div class="podium-avatar"><?= mb_substr($r['username'],0,2) ?></div>
            <div class="podium-name"><?= htmlspecialchars($r['username']) ?></div>
            <div class="podium-score"><?= number_format($r['best_score']) ?></div>
            <div class="podium-extra">
                <span class="t">⏱ <?= fmtTime($r['best_time_sec']) ?></span>
                <span class="m">🔁 <?= fmtNum($r['best_attempts'])!=='—' ? fmtNum($r['best_attempts'])."x" : '—' ?></span>
            </div>
            <div class="podium-bar podium-bar-1">1</div>
        </div>
        <?php endif; ?>
        <?php if (isset($rows[2])): $r=$rows[2]; ?>
        <div class="podium-slot podium-slot-3">
            <div class="podium-avatar"><?= mb_substr($r['username'],0,2) ?></div>
            <div class="podium-name"><?= htmlspecialchars($r['username']) ?></div>
            <div class="podium-score"><?= number_format($r['best_score']) ?></div>
            <div class="podium-extra">
                <span class="t">⏱ <?= fmtTime($r['best_time_sec']) ?></span>
                <span class="m">🔁 <?= fmtNum($r['best_attempts'])!=='—' ? fmtNum($r['best_attempts'])."x" : '—' ?></span>
            </div>
            <div class="podium-bar podium-bar-3">3</div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($rows)): ?>
        <div class="lb-empty">
            <span class="icon">📭</span>
            <p>Belum ada skor untuk level <?= $curWL['label'] ?>.<br>Jadilah yang pertama!</p>
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
                    <th>Min Percobaan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r):
                    $rn=$r['rn']; $rnInt=(int)$rn;
                    $rowClass  = $rnInt===1?'top1':($rnInt===2?'top2':($rnInt===3?'top3':''));
                    $badgeCls  = $rnInt===1?'rank-1':($rnInt===2?'rank-2':($rnInt===3?'rank-3':'rank-n'));
                    $isMe      = $user['id'] && strtolower($r['username'])===strtolower($user['username']??'');
                ?>
                <tr class="<?= $rowClass ?>">
                    <td class="td-rank">
                        <span class="rank-badge <?= $badgeCls ?>">
                            <?= $rnInt===1?'🥇':($rnInt===2?'🥈':($rnInt===3?'🥉':$rnInt)) ?>
                        </span>
                    </td>
                    <td class="td-name <?= $isMe?'me':'' ?>">
                        <?= htmlspecialchars($r['username']) ?>
                        <?php if ($isMe): ?><span style="font-size:.75rem;color:var(--accent-gold);margin-left:.4rem">(Kamu)</span><?php endif; ?>
                    </td>
                    <td class="td-score"><?= number_format($r['best_score']) ?></td>
                    <td class="td-time"><?= fmtTime($r['best_time_sec']) ?></td>
                    <td class="td-extra"><?= fmtNum($r['best_attempts']) !== '—' ? fmtNum($r['best_attempts']).'x' : '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php elseif ($section === 'puzzle'): ?>
    <!-- ═══════ SLIDE PUZZLE ═══════ -->

    <!-- Grid size tabs -->
    <div class="lb-sub-tabs">
        <?php foreach ($puzzleTabs as $tab): ?>
        <a href="leaderboard.php?section=puzzle&grid=<?= $tab['grid'] ?>"
           class="lb-sub-tab <?= $activeGrid===$tab['grid']?'active':'' ?>">
            <?= $tab['label'] ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Sort info -->
    <div class="lb-sort-info">
        Urutan:
        <span class="sort-chip p1">① Skor tertinggi</span>
        <span class="sort-chip p3">② Moves paling sedikit</span>
        <span class="sort-chip p2">③ Waktu tercepat</span>
    </div>

    <?php $rows = $puzzleRows; ?>

    <?php if (count($rows) >= 3): ?>
    <div class="podium">
        <?php if (isset($rows[1])): $r=$rows[1]; ?>
        <div class="podium-slot podium-slot-2">
            <div class="podium-avatar"><?= mb_substr($r['username'],0,2) ?></div>
            <div class="podium-name"><?= htmlspecialchars($r['username']) ?></div>
            <div class="podium-score"><?= number_format($r['best_score']) ?></div>
            <div class="podium-extra">
                <span class="m">👆 <?= fmtNum($r['best_moves']) ?> moves</span>
                <span class="t">⏱ <?= fmtTime($r['best_time_sec']) ?></span>
            </div>
            <div class="podium-bar podium-bar-2">2</div>
        </div>
        <?php endif; ?>
        <?php if (isset($rows[0])): $r=$rows[0]; ?>
        <div class="podium-slot podium-slot-1">
            <div class="podium-crown">👑</div>
            <div class="podium-avatar"><?= mb_substr($r['username'],0,2) ?></div>
            <div class="podium-name"><?= htmlspecialchars($r['username']) ?></div>
            <div class="podium-score"><?= number_format($r['best_score']) ?></div>
            <div class="podium-extra">
                <span class="m">👆 <?= fmtNum($r['best_moves']) ?> moves</span>
                <span class="t">⏱ <?= fmtTime($r['best_time_sec']) ?></span>
            </div>
            <div class="podium-bar podium-bar-1">1</div>
        </div>
        <?php endif; ?>
        <?php if (isset($rows[2])): $r=$rows[2]; ?>
        <div class="podium-slot podium-slot-3">
            <div class="podium-avatar"><?= mb_substr($r['username'],0,2) ?></div>
            <div class="podium-name"><?= htmlspecialchars($r['username']) ?></div>
            <div class="podium-score"><?= number_format($r['best_score']) ?></div>
            <div class="podium-extra">
                <span class="m">👆 <?= fmtNum($r['best_moves']) ?> moves</span>
                <span class="t">⏱ <?= fmtTime($r['best_time_sec']) ?></span>
            </div>
            <div class="podium-bar podium-bar-3">3</div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($rows)): ?>
        <div class="lb-empty">
            <span class="icon">📭</span>
            <p>Belum ada pemain yang menyelesaikan grid <?= $activeGrid ?>×<?= $activeGrid ?>.<br>Jadilah yang pertama!</p>
        </div>
    <?php else: ?>
    <div class="lb-table-wrap">
        <table class="lb-table">
            <thead>
                <tr>
                    <th class="num">#</th>
                    <th>Player</th>
                    <th>Best Score</th>
                    <th>Min Moves</th>
                    <th>Best Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r):
                    $rn=$r['rn']; $rnInt=(int)$rn;
                    $rowClass  = $rnInt===1?'top1':($rnInt===2?'top2':($rnInt===3?'top3':''));
                    $badgeCls  = $rnInt===1?'rank-1':($rnInt===2?'rank-2':($rnInt===3?'rank-3':'rank-n'));
                    $isMe      = $user['id'] && strtolower($r['username'])===strtolower($user['username']??'');
                ?>
                <tr class="<?= $rowClass ?>">
                    <td class="td-rank">
                        <span class="rank-badge <?= $badgeCls ?>">
                            <?= $rnInt===1?'🥇':($rnInt===2?'🥈':($rnInt===3?'🥉':$rnInt)) ?>
                        </span>
                    </td>
                    <td class="td-name <?= $isMe?'me':'' ?>">
                        <?= htmlspecialchars($r['username']) ?>
                        <?php if ($isMe): ?><span style="font-size:.75rem;color:var(--accent-gold);margin-left:.4rem">(Kamu)</span><?php endif; ?>
                    </td>
                    <td class="td-score"><?= number_format($r['best_score']) ?></td>
                    <td class="td-extra"><?= fmtNum($r['best_moves']) ?></td>
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


<script src="music.js"></script>
</body>
</html>
