<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();
$user = getCurrentUser();

// Ambil semua level
$stmt   = executeQuery("SELECT id, level_num, grid_size FROM puzzle_levels ORDER BY level_num");
$levels = fetchAll($stmt);

if (empty($levels)) {
    $grids = [2,3,3,4,4,5,5,6,7,8,9,10];
    foreach ($grids as $i => $g) {
        $levels[] = ['id'=>$i+1, 'level_num'=>$i+1, 'grid_size'=>$g];
    }
}

// Ambil best score & best time per level untuk user ini
// Embed $uid sebagai integer langsung (aman, tidak perlu bind variable)
$uid = (int)$user['id'];
$scoreMap = [];
try {
    $scoreStmt = executeQuery(
        "SELECT game_level,
                MAX(score)    AS best_score,
                MIN(duration) AS best_time
         FROM scores
         WHERE user_id = " . $uid . "
           AND game_type = 'puzzle'
         GROUP BY game_level"
    );
    foreach (fetchAll($scoreStmt) as $row) {
        // game_level disimpan sebagai 'level-1', 'level-2', dst.
        if (preg_match('/^level-(\d+)$/', $row['game_level'], $m)) {
            $scoreMap[(int)$m[1]] = [
                'best_score' => (int)$row['best_score'],
                'best_time'  => (int)$row['best_time'],
            ];
        }
    }
} catch (Exception $e) {
    // Jika query gagal, lanjutkan tanpa score (levels tetap tampil)
    $scoreMap = [];
}

// Level N terbuka jika: N === 1  ATAU  ada score untuk N-1
$unlockedMap = [];
foreach ($levels as $lv) {
    $n = (int)$lv['level_num'];
    $unlockedMap[$n] = ($n === 1) || isset($scoreMap[$n - 1]);
}

function fmtTime($sec) {
    return sprintf('%d:%02d', intdiv($sec, 60), $sec % 60);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sliding Puzzle — KeBox</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.level-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 1rem;
}

/* ── Card dasar ── */
.puzzle-level-card {
    padding: 1.2rem;
    border-radius: 14px;
    text-align: center;
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    transition: all .3s;
    text-decoration: none;
    color: var(--text-main);
    display: block;
    position: relative;
    overflow: hidden;
}
.puzzle-level-card.unlocked { cursor: pointer; }
.puzzle-level-card.unlocked:hover {
    border-color: var(--purple-main);
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(124,77,255,.2);
}
.puzzle-level-card .lv {
    font-family: 'Orbitron', monospace;
    font-size: .7rem;
    letter-spacing: 2px;
    color: var(--text-muted);
    margin-bottom: .3rem;
}
.puzzle-level-card .num {
    font-family: 'Orbitron', monospace;
    font-size: 1.8rem;
    font-weight: 900;
    color: var(--purple-bright);
}
.puzzle-level-card .grid-label {
    font-size: .8rem;
    color: var(--text-dim);
    margin-top: .3rem;
}

/* ── Difficulty colours ── */
.diff-easy   { border-color: rgba(0,230,118,.25)  !important; }
.diff-medium { border-color: rgba(255,215,64,.25) !important; }
.diff-hard   { border-color: rgba(255,23,68,.25)  !important; }
.unlocked.diff-easy:hover   { border-color: var(--accent-green) !important; box-shadow: 0 10px 30px rgba(0,230,118,.15)   !important; }
.unlocked.diff-medium:hover { border-color: var(--accent-gold)  !important; box-shadow: 0 10px 30px rgba(255,215,64,.15)  !important; }
.unlocked.diff-hard:hover   { border-color: var(--accent-red)   !important; box-shadow: 0 10px 30px rgba(255,23,68,.15)   !important; }

/* ── Locked card ── */
.puzzle-level-card.locked {
    cursor: not-allowed;
    opacity: .5;
    filter: grayscale(60%);
}
.puzzle-level-card.locked .num { color: var(--text-muted); }
.lock-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,.5);
    border-radius: 13px;
    gap: .3rem;
}
.lock-icon { font-size: 1.6rem; line-height: 1; }
.lock-txt  { font-size: .65rem; color: #aaa; letter-spacing: 1.5px; font-family: 'Orbitron', monospace; }

/* ── Score info ── */
.score-info {
    margin-top: .6rem;
    padding-top: .5rem;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: space-around;
}
.si-item { font-size: .7rem; color: var(--text-dim); line-height: 1.5; }
.si-val  { display: block; font-family: 'Orbitron', monospace; font-size: .78rem; font-weight: 700; }
.si-lbl  { font-size: .62rem; color: var(--text-muted); }
.no-score-yet {
    margin-top: .6rem;
    padding-top: .5rem;
    border-top: 1px solid var(--border);
    font-size: .7rem;
    color: var(--text-muted);
    font-style: italic;
}
</style>
</head>
<body>
<nav class="navbar">
    <a href="../index.php" class="navbar-brand">Ke<span>Box</span></a>
    <ul class="navbar-nav">
        <li><a href="../dashboard.php">Dashboard</a></li>
        <li><a href="../leaderboard.php">Leaderboard</a></li>
        <li><a href="../profile.php">Profil</a></li>
        <li><a href="../logout.php" class="btn btn-outline btn-sm">Logout</a></li>
    </ul>
</nav>

<div class="container" style="padding-top:2rem;padding-bottom:3rem;max-width:900px">
    <div class="page-header">
        <h1>🧩 Sliding Puzzle</h1>
        <p>Selesaikan level sebelumnya untuk membuka level berikutnya</p>
    </div>

    <!-- Legend -->
    <div style="display:flex;gap:.8rem;flex-wrap:wrap;justify-content:center;margin-bottom:1.5rem">
        <span style="display:flex;align-items:center;gap:.4rem;font-size:.8rem;color:var(--text-dim)">
            <span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:rgba(0,230,118,.3);border:1px solid var(--accent-green)"></span> Easy (L1-L3)
        </span>
        <span style="display:flex;align-items:center;gap:.4rem;font-size:.8rem;color:var(--text-dim)">
            <span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:rgba(255,215,64,.3);border:1px solid var(--accent-gold)"></span> Medium (L4-L7)
        </span>
        <span style="display:flex;align-items:center;gap:.4rem;font-size:.8rem;color:var(--text-dim)">
            <span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:rgba(255,23,68,.3);border:1px solid var(--accent-red)"></span> Hard (L8+)
        </span>
    </div>

    <div class="level-grid">
    <?php foreach ($levels as $lv):
        $n        = (int)$lv['level_num'];
        $diff     = $n <= 3 ? 'easy' : ($n <= 7 ? 'medium' : 'hard');
        $unlocked = $unlockedMap[$n];
        $hasScore = isset($scoreMap[$n]);
        $cls      = "puzzle-level-card diff-{$diff} " . ($unlocked ? 'unlocked' : 'locked');
    ?>

        <?php if ($unlocked): ?>
        <a href="puzzle-mode.php?level=<?= $n ?>" class="<?= $cls ?>">
        <?php else: ?>
        <div class="<?= $cls ?>" title="Selesaikan Level <?= $n - 1 ?> terlebih dahulu">
        <?php endif; ?>

            <div class="lv">LEVEL</div>
            <div class="num"><?= $n ?></div>
            <div class="grid-label"><?= (int)$lv['grid_size'] ?>×<?= (int)$lv['grid_size'] ?> grid</div>
            <div class="mt-1">
                <span class="badge badge-<?= $diff ?>"><?= strtoupper($diff) ?></span>
            </div>

            <?php if ($hasScore): ?>
            <div class="score-info">
                <div class="si-item">
                    <span class="si-val" style="color:var(--accent-green)"><?= number_format($scoreMap[$n]['best_score']) ?></span>
                    <span class="si-lbl">Best Score</span>
                </div>
                <div class="si-item">
                    <span class="si-val" style="color:var(--accent-gold)"><?= fmtTime($scoreMap[$n]['best_time']) ?></span>
                    <span class="si-lbl">Best Time</span>
                </div>
            </div>
            <?php elseif ($unlocked): ?>
            <div class="no-score-yet">Belum dimainkan</div>
            <?php endif; ?>

            <?php if (!$unlocked): ?>
            <div class="lock-overlay">
                <span class="lock-icon">🔒</span>
                <span class="lock-txt">LOCKED</span>
            </div>
            <?php endif; ?>

        <?php echo $unlocked ? '</a>' : '</div>'; ?>

    <?php endforeach; ?>
    </div>
</div>
</body>
</html>