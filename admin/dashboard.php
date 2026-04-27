<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();
$user = getCurrentUser();

$stmtUsers = executeQuery("SELECT COUNT(*) AS c FROM users WHERE user_role='user'");
$totalUsers = fetchOne($stmtUsers)['c'];

$stmtWords = executeQuery("SELECT COUNT(*) AS c FROM words");
$totalWords = fetchOne($stmtWords)['c'];

$stmtLevels = executeQuery("SELECT COUNT(*) AS c FROM puzzle_levels");
$totalLevels = fetchOne($stmtLevels)['c'];

$stmtScores = executeQuery("SELECT COUNT(*) AS c FROM scores");
$totalScores = fetchOne($stmtScores)['c'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Dashboard — KeBox</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>

<nav class="navbar">
    <a href="../index.php" class="navbar-brand">Ke<span>Box</span></a>
    <ul class="navbar-nav">
        <li><span style="color:var(--accent-red);font-size:.8rem;font-weight:700;letter-spacing:1px">⚡ ADMIN</span></li>
        <li><span style="color:var(--text-dim);padding:0 .5rem">👤 <?= htmlspecialchars($user['username']) ?></span></li>
        <li><a href="../logout.php" class="btn btn-outline btn-sm">Logout</a></li>
    </ul>
</nav>

<div class="dashboard-layout">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-title">Menu Admin</div>
        <a href="dashboard.php" class="active">📊 Dashboard</a>
        <a href="users.php">👥 Kelola User</a>
        <div class="sidebar-title">Game</div>
        <a href="words.php">💬 Kata Word Game</a>
        <a href="levels.php">🧩 Level Puzzle</a>
        <div class="sidebar-title">Lainnya</div>
        <a href="../index.php">🌐 Lihat Website</a>
        <a href="../logout.php">🚪 Logout</a>
    </aside>

    <!-- MAIN -->
    <main class="main-content">
        <div class="mb-3">
            <h1 style="font-family:'Orbitron',monospace;font-size:1.6rem">Admin Dashboard</h1>
            <p style="color:var(--text-dim)">Kelola semua aspek KeBox dari sini</p>
        </div>

        <!-- STATS CARDS -->
        <div class="grid grid-4 mb-4">
            <div class="card text-center">
                <div style="font-size:2rem;margin-bottom:.5rem">👥</div>
                <div style="font-family:'Orbitron',monospace;font-size:2rem;font-weight:900;color:var(--accent-cyan)"><?= $totalUsers ?></div>
                <div style="color:var(--text-dim);font-size:.8rem;text-transform:uppercase;letter-spacing:1px">Total User</div>
            </div>
            <div class="card text-center">
                <div style="font-size:2rem;margin-bottom:.5rem">💬</div>
                <div style="font-family:'Orbitron',monospace;font-size:2rem;font-weight:900;color:var(--accent-gold)"><?= $totalWords ?></div>
                <div style="color:var(--text-dim);font-size:.8rem;text-transform:uppercase;letter-spacing:1px">Total Kata</div>
            </div>
            <div class="card text-center">
                <div style="font-size:2rem;margin-bottom:.5rem">🧩</div>
                <div style="font-family:'Orbitron',monospace;font-size:2rem;font-weight:900;color:var(--purple-bright)"><?= $totalLevels ?></div>
                <div style="color:var(--text-dim);font-size:.8rem;text-transform:uppercase;letter-spacing:1px">Level Puzzle</div>
            </div>
            <div class="card text-center">
                <div style="font-size:2rem;margin-bottom:.5rem">🏆</div>
                <div style="font-family:'Orbitron',monospace;font-size:2rem;font-weight:900;color:var(--accent-green)"><?= $totalScores ?></div>
                <div style="color:var(--text-dim);font-size:.8rem;text-transform:uppercase;letter-spacing:1px">Total Skor</div>
            </div>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="card mb-3">
            <div style="font-family:'Orbitron',monospace;font-size:.7rem;letter-spacing:2px;color:var(--text-muted);margin-bottom:1rem">AKSI CEPAT</div>
            <div style="display:flex;gap:1rem;flex-wrap:wrap">
                <a href="users.php" class="btn btn-outline">👥 Kelola User</a>
                <a href="words.php" class="btn btn-outline">💬 Tambah Kata</a>
                <a href="levels.php" class="btn btn-outline">🧩 Tambah Level Puzzle</a>
            </div>
        </div>

        <!-- RECENT SCORES -->
        <div class="card">
            <div style="font-family:'Orbitron',monospace;font-size:.7rem;letter-spacing:2px;color:var(--text-muted);margin-bottom:1rem">SKOR TERBARU</div>
            <?php
            $stmt = executeQuery(
                "SELECT s.score, s.game_type, s.game_level, s.created_at, u.username
                 FROM scores s JOIN users u ON s.user_id=u.id
                 ORDER BY s.created_at DESC FETCH FIRST 10 ROWS ONLY"
            );
            $scores = fetchAll($stmt);
            ?>
            <?php if (empty($scores)): ?>
                <p style="color:var(--text-muted);text-align:center;padding:2rem">Belum ada skor</p>
            <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>User</th><th>Game</th><th>Level</th><th>Score</th><th>Tanggal</th></tr></thead>
                    <tbody>
                    <?php foreach ($scores as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['username']) ?></td>
                            <td><span class="badge badge-<?= $s['game_type']==='word'?'easy':'medium' ?>"><?= strtoupper($s['game_type']) ?></span></td>
                            <td><?= htmlspecialchars($s['game_level']) ?></td>
                            <td style="font-family:'Orbitron',monospace;color:var(--accent-gold)"><?= (int)$s['score'] ?></td>
                            <td style="color:var(--text-dim);font-size:.85rem"><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>
