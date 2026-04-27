<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();
$user = getCurrentUser();

$stmt  = executeQuery("SELECT id, level_num, grid_size, label FROM puzzle_levels ORDER BY level_num");
$levels = fetchAll($stmt);

// Fallback levels if DB empty
if (empty($levels)) {
    $grids = [3,3,4,4,4,5,5,6,7,8];
    foreach ($grids as $i => $g) {
        $levels[] = ['id'=>$i+1,'level_num'=>$i+1,'grid_size'=>$g,'label'=>"Level ".($i+1)." ({$g}x{$g})"];
    }
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
.level-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:1rem; }
.puzzle-level-card {
    padding:1.2rem; border-radius:14px; text-align:center; cursor:pointer;
    background:var(--bg-card); border:1.5px solid var(--border);
    transition:all .3s; text-decoration:none; color:var(--text-main);
    display:block;
}
.puzzle-level-card:hover { border-color:var(--purple-main); transform:translateY(-3px); box-shadow:0 10px 30px rgba(124,77,255,.2); }
.puzzle-level-card .lv { font-family:'Orbitron',monospace; font-size:.7rem; letter-spacing:2px; color:var(--text-muted); margin-bottom:.3rem; }
.puzzle-level-card .num { font-family:'Orbitron',monospace; font-size:1.8rem; font-weight:900; color:var(--purple-bright); }
.puzzle-level-card .grid-label { font-size:.8rem; color:var(--text-dim); margin-top:.3rem; }
.diff-easy   { border-color:rgba(0,230,118,.25)  !important; }
.diff-medium { border-color:rgba(255,215,64,.25) !important; }
.diff-hard   { border-color:rgba(255,23,68,.25)  !important; }
.diff-easy:hover   { border-color:var(--accent-green) !important; box-shadow:0 10px 30px rgba(0,230,118,.15) !important; }
.diff-medium:hover { border-color:var(--accent-gold)  !important; box-shadow:0 10px 30px rgba(255,215,64,.15) !important; }
.diff-hard:hover   { border-color:var(--accent-red)   !important; box-shadow:0 10px 30px rgba(255,23,68,.15)  !important; }
</style>
</head>
<body>
<nav class="navbar">
    <a href="../index.php" class="navbar-brand">Ke<span>Box</span></a>
    <ul class="navbar-nav">
        <li><a href="../dashboard.php">Dashboard</a></li>
        <li><a href="../logout.php" class="btn btn-outline btn-sm">Logout</a></li>
    </ul>
</nav>

<div class="container" style="padding-top:2rem;padding-bottom:3rem;max-width:800px">
    <div class="page-header">
        <h1>🧩 Sliding Puzzle</h1>
        <p>Pilih level untuk mulai bermain</p>
    </div>

    <div style="display:flex;gap:.8rem;flex-wrap:wrap;justify-content:center;margin-bottom:1.5rem">
        <span style="display:flex;align-items:center;gap:.4rem;font-size:.8rem;color:var(--text-dim)">
            <span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:rgba(0,230,118,.3);border:1px solid var(--accent-green)"></span> Easy (L1-L3)
        </span>
        <span style="display:flex;align-items:center;gap:.4rem;font-size:.8rem;color:var(--text-dim)">
            <span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:rgba(255,215,64,.3);border:1px solid var(--accent-gold)"></span> Medium (L4-L7)
        </span>
        <span style="display:flex;align-items:center;gap:.4rem;font-size:.8rem;color:var(--text-dim)">
            <span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:rgba(255,23,68,.3);border:1px solid var(--accent-red)"></span> Hard (L8-L10)
        </span>
    </div>

    <div class="level-grid">
        <?php foreach ($levels as $lv):
            $n   = (int)$lv['level_num'];
            $diff = $n <= 3 ? 'easy' : ($n <= 7 ? 'medium' : 'hard');
        ?>
        <a href="puzzle-mode.php?level=<?= $n ?>" class="puzzle-level-card diff-<?= $diff ?>">
            <div class="lv">LEVEL</div>
            <div class="num"><?= $n ?></div>
            <div class="grid-label">
                <?= (int)$lv['grid_size'] ?>×<?= (int)$lv['grid_size'] ?> grid
            </div>
            <div class="mt-1">
                <span class="badge badge-<?= $diff ?>"><?= strtoupper($diff) ?></span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
