<?php
require_once 'includes/auth.php';
requireLogin();
$user = getCurrentUser();
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
        <li><a href="leaderboard.php">Leaderboard</a></li>
        <li><a href="profile.php">Profil</a></li>
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
            <p>Susun angka berurutan dengan menggeser tile. Ada 12 level.</p>
            <div style="display:flex;gap:.5rem;margin-top:.5rem;flex-wrap:wrap">
                <span class="badge badge-easy">Level 1-3</span>
                <span class="badge badge-medium">Level 4-7</span>
                <span class="badge badge-hard">Level 8-10</span>
            </div>
        </a>
    </div>

    <!-- SHORTCUT KE PROFIL -->
    <div style="margin-top:2rem;text-align:center">
        <a href="profile.php" class="btn btn-outline">
            👤 Lihat Profil &amp; Statistik Kamu
        </a>
    </div>

</div>
</body>
</html>
