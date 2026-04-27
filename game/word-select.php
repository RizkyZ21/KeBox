<?php
require_once '../includes/auth.php';
requireLogin();
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Word Game — KeBox</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>

<nav class="navbar">
    <a href="../index.php" class="navbar-brand">Ke<span>Box</span></a>
    <ul class="navbar-nav">
        <li><a href="../dashboard.php">Dashboard</a></li>
        <li><a href="../logout.php" class="btn btn-outline btn-sm">Logout</a></li>
    </ul>
</nav>

<div class="container" style="padding-top:2rem;padding-bottom:3rem;max-width:700px">
    <div class="page-header">
        <h1>💬 Word Game</h1>
        <p>Pilih level kesulitan untuk mulai bermain</p>
    </div>

    <div class="grid grid-3 mb-4">
        <a href="word-mode.php?level=easy" class="level-card level-easy" style="text-decoration:none;color:var(--text-main)">
            <div style="font-size:2.5rem;margin-bottom:.8rem">🟢</div>
            <div style="font-family:'Orbitron',monospace;color:var(--accent-green);font-size:1rem;margin-bottom:.5rem">EASY</div>
            <div style="color:var(--text-dim);font-size:.85rem;line-height:1.6">
                4 huruf<br>
                8 percobaan<br>
                Tanpa timer
            </div>
        </a>
        <a href="word-mode.php?level=medium" class="level-card level-medium" style="text-decoration:none;color:var(--text-main)">
            <div style="font-size:2.5rem;margin-bottom:.8rem">🟡</div>
            <div style="font-family:'Orbitron',monospace;color:var(--accent-gold);font-size:1rem;margin-bottom:.5rem">MEDIUM</div>
            <div style="color:var(--text-dim);font-size:.85rem;line-height:1.6">
                5 huruf<br>
                6 percobaan<br>
                Tanpa timer
            </div>
        </a>
        <a href="word-mode.php?level=hard" class="level-card level-hard" style="text-decoration:none;color:var(--text-main)">
            <div style="font-size:2.5rem;margin-bottom:.8rem">🔴</div>
            <div style="font-family:'Orbitron',monospace;color:var(--accent-red);font-size:1rem;margin-bottom:.5rem">HARD</div>
            <div style="color:var(--text-dim);font-size:.85rem;line-height:1.6">
                6 huruf<br>
                4 percobaan<br>
                ⏱ Timer 60 detik
            </div>
        </a>
    </div>

    <div class="card" style="background:rgba(124,77,255,0.06);border-color:rgba(124,77,255,0.2)">
        <div style="font-family:'Orbitron',monospace;font-size:.75rem;letter-spacing:2px;color:var(--text-muted);margin-bottom:1rem">CARA BERMAIN</div>
        <ul style="list-style:none;display:flex;flex-direction:column;gap:.6rem;color:var(--text-dim);font-size:.9rem">
            <li>🟩 <strong style="color:var(--accent-green)">Hijau</strong> — huruf benar di posisi yang tepat</li>
            <li>🟧 <strong style="color:#ff9800)">Oranye</strong> — huruf ada tapi di posisi salah</li>
            <li>⬛ <strong style="color:var(--text-muted)">Gelap</strong> — huruf tidak ada dalam kata</li>
        </ul>
    </div>
</div>

</body>
</html>
