<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();
$levelNum = max(1, min(99, (int)($_GET["level"] ?? 1)));

$stmt = executeQuery("SELECT * FROM puzzle_levels WHERE level_num=:n", [':n'=>$levelNum]);
$lv   = fetchOne($stmt);
$gridSizeMap = [1=>2,2=>3,3=>3,4=>4,5=>4,6=>5,7=>5,8=>6,9=>7,10=>8,11=>9,12=>10];
$gridSize = $lv ? (int)$lv['grid_size'] : ($gridSizeMap[$levelNum] ?? 4);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Pilih Mode Puzzle — KeBox</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<nav class="navbar">
    <a href="../index.php" class="navbar-brand">Ke<span>Box</span></a>
    <ul class="navbar-nav">
        <li><a href="../dashboard.php">Dashboard</a></li>
        <li><a href="../leaderboard.php">Leaderboard</a></li>
        <li><a href="../logout.php" class="btn btn-outline btn-sm">Logout</a></li>
    </ul>
</nav>

<div class="container" style="max-width:600px;padding-top:3rem;padding-bottom:3rem">
    <div class="page-header">
        <h1>🧩 Sliding Puzzle</h1>
        <p>Level <?= $levelNum ?> (<?= $gridSize ?>×<?= $gridSize ?>) — Pilih mode bermain</p>
    </div>

    <div class="grid grid-2 mt-3">
        <a href="puzzle-play.php?level=<?= $levelNum ?>&mode=1p" class="game-card">
            <span class="icon">🎮</span>
            <h3>1 Player</h3>
            <p>Bermain sendiri, seberapa cepat kamu bisa selesaikan?</p>
        </a>
        <a href="puzzle-online.php?level=<?= $levelNum ?>" class="game-card">
            <span class="icon">👥</span>
            <h3>2 Player Online</h3>
            <p>Siapa yang lebih cepat menyelesaikan puzzle?</p>
        </a>
    </div>

    <div class="text-center mt-4">
        <a href="puzzle-select.php" class="btn btn-outline">← Pilih Level</a>
    </div>
</div>
</body>
</html>
