<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();
$user = getCurrentUser();

$userId = (int)$user['id'];

$stmt = executeQuery(
    "SELECT game_type, COUNT(*) AS total_games, MAX(score) AS best_score 
     FROM scores 
     WHERE user_id = $userId 
     GROUP BY game_type"
);

$stats = fetchAll($stmt);
$statsMap = [];
foreach ($stats as $s) $statsMap[$s['game_type']] = $s;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard — KeBox</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="navbar-brand">Ke<span>Box</span></a>
    <ul class="navbar-nav">
        <li><a href="dashboard.php" class="active">Dashboard</a></li>
        <li><span style="color:var(--text-dim);padding:0 0.5rem">👤 <?= htmlspecialchars($user['username']) ?></span></li>
        <li><a href="logout.php" class="btn btn-outline btn-sm">Logout</a></li>
    </ul>
</nav>

<div class="container" style="padding-top:2rem;padding-bottom:3rem">

    <!-- WELCOME -->
    <div class="mb-3">
        <h1 style="font-family:'Orbitron',monospace;font-size:1.8rem;color:var(--text-main)">
            Halo, <span style="color:var(--purple-bright)"><?= htmlspecialchars($user['username']) ?></span>! 👋
        </h1>
        <p style="color:var(--text-dim)">Pilih game yang ingin kamu mainkan</p>
    </div>

    <!-- STATS -->
    <?php if (!empty($statsMap)): ?>
    <div class="card mb-3">
        <div style="font-family:'Orbitron',monospace;font-size:.75rem;letter-spacing:2px;color:var(--text-muted);margin-bottom:1rem">STATISTIK KAMU</div>
        <div style="display:flex;gap:2rem;flex-wrap:wrap">
            <div class="stat-box">
                <div class="num"><?= $statsMap['word']['total_games'] ?? 0 ?></div>
                <div class="lbl">Game Word</div>
            </div>
            <div class="stat-box">
                <div class="num"><?= $statsMap['puzzle']['total_games'] ?? 0 ?></div>
                <div class="lbl">Game Puzzle</div>
            </div>
            <div class="stat-box">
                <div class="num"><?= $statsMap['word']['best_score'] ?? 0 ?></div>
                <div class="lbl">Best Word Score</div>
            </div>
            <div class="stat-box">
                <div class="num"><?= $statsMap['puzzle']['best_score'] ?? 0 ?></div>
                <div class="lbl">Best Puzzle Score</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- GAME SELECTION -->
    <div style="font-family:'Orbitron',monospace;font-size:.75rem;letter-spacing:2px;color:var(--text-muted);margin-bottom:1rem">PILIH GAME</div>
    <div class="grid grid-2">
        <a href="game/word-select.php" class="game-card">
            <span class="icon">💬</span>
            <h3>Word Game</h3>
            <p>Tebak kata rahasia gaya Wordle. Tersedia 3 level kesulitan.</p>
            <div style="display:flex;gap:.5rem;margin-top:.5rem">
                <span class="badge badge-easy">Easy</span>
                <span class="badge badge-medium">Medium</span>
                <span class="badge badge-hard">Hard</span>
            </div>
        </a>
        <a href="game/puzzle-select.php" class="game-card">
            <span class="icon">🧩</span>
            <h3>Sliding Puzzle</h3>
            <p>Susun angka berurutan dengan menggeser tile. Ada 10 level.</p>
            <div style="display:flex;gap:.5rem;margin-top:.5rem;flex-wrap:wrap">
                <span class="badge badge-easy">Level 1-3</span>
                <span class="badge badge-medium">Level 4-7</span>
                <span class="badge badge-hard">Level 8-10</span>
            </div>
        </a>
    </div>

</div>
</body>
</html>
