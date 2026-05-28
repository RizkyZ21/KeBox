<?php
require_once 'includes/auth.php';
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>KeBox — Web Game Platform</title>
<link rel="stylesheet" href="css/style.css">
<style>
.floating-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    pointer-events: none;
    animation: drift 8s ease-in-out infinite;
}
.orb1 { width:400px;height:400px;background:rgba(124,77,255,0.07);top:-100px;right:-100px;animation-delay:0s; }
.orb2 { width:300px;height:300px;background:rgba(0,229,255,0.04);bottom:100px;left:-80px;animation-delay:-3s; }
@keyframes drift { 0%,100%{transform:translate(0,0)} 50%{transform:translate(20px,-20px)} }

.preview-grid {
    display: grid;
    grid-template-columns: repeat(3,1fr);
    gap: 1rem;
    margin: 4rem 0;
}
.preview-card {
    padding: 2rem 1.5rem;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 16px;
    text-align: center;
    transition: all 0.3s;
}
.preview-card:hover { border-color: var(--purple-mid); transform: translateY(-4px); }
.preview-card .icon { font-size: 2.5rem; margin-bottom: 1rem; display: block; }
.preview-card h3 { font-family:'Orbitron',monospace; font-size:.95rem; color:var(--purple-bright); margin-bottom:.5rem; }
.preview-card p { color:var(--text-dim); font-size:.85rem; line-height:1.5; }

.scroll-section { padding: 5rem 0; }
.section-title { font-family:'Orbitron',monospace; font-size:1.8rem; font-weight:900; color:var(--text-main); margin-bottom:0.5rem; }
.section-sub { color:var(--text-dim); margin-bottom:2.5rem; }

footer {
    border-top: 1px solid var(--border);
    padding: 2rem;
    text-align: center;
    color: var(--text-muted);
    font-size: .85rem;
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
            <li><a href="leaderboard.php">Leaderboard</a></li>
            <li><a href="logout.php" class="btn btn-outline btn-sm">Logout</a></li>
        <?php else: ?>
            <li><a href="leaderboard.php">Leaderboard</a></li>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php" class="btn btn-primary btn-sm">Register</a></li>
        <?php endif; ?>
    </ul>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="floating-orb orb1"></div>
    <div class="floating-orb orb2"></div>

    <div style="position:relative;z-index:1">
        <div class="hero-title">Ke<span class="box">Box</span></div>
        <p class="hero-sub">Platform web game seru dengan Word Game gaya Wordle dan Sliding Puzzle yang menantang. Main sendiri atau bareng teman!</p>

        <div class="hero-features">
            <div class="feat-badge">🧩 Sliding Puzzle</div>
            <div class="feat-badge">📝 Word Game</div>
            <div class="feat-badge">👥 2 Player Online</div>
            <div class="feat-badge">🏆 10 Level</div>
        </div>

        <?php if ($user['id']): ?>
            <a href="dashboard.php" class="btn btn-primary btn-lg">Main Sekarang</a>
        <?php else: ?>
            <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap">
                <a href="register.php" class="btn btn-primary btn-lg">Daftar Gratis</a>
                <a href="login.php" class="btn btn-outline btn-lg">Login</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- FEATURES -->
<div class="container">
    <div class="scroll-section">
        <div class="text-center mb-3">
            <div class="section-title">Pilihan Game</div>
            <div class="section-sub">Dua game seru yang bisa kamu mainkan</div>
        </div>

        <div class="preview-grid">
            <div class="preview-card">
                <span class="icon">💬</span>
                <h3>Word Game</h3>
                <p>Tebak kata rahasia dengan petunjuk warna. Ada 3 level: Easy (4 huruf), Medium (5 huruf), Hard (6 huruf + timer).</p>
            </div>
            <div class="preview-card">
                <span class="icon">🧩</span>
                <h3>Sliding Puzzle</h3>
                <p>Susun angka secara berurutan dengan menggeser tile. Ada 10 level dengan berbagai ukuran grid.</p>
            </div>
            <div class="preview-card">
                <span class="icon">🌐</span>
                <h3>Multiplayer Online</h3>
                <p>Tantang temanmu secara online! Buat room dan share kode, temanmu bisa langsung join.</p>
            </div>
        </div>
    </div>

    <div class="scroll-section" style="padding-top:0">
        <div class="text-center mb-3">
            <div class="section-title">Tingkat Kesulitan</div>
            <div class="section-sub">Word Game punya 3 level untuk semua kemampuan</div>
        </div>
        <div class="grid grid-3">
            <div style="padding:1.5rem;border-radius:16px;background:rgba(0,230,118,0.07);border:1px solid rgba(0,230,118,0.2);text-align:center">
                <div style="font-size:2rem;margin-bottom:.5rem">🟢</div>
                <div style="font-family:'Orbitron',monospace;color:#69ffb4;margin-bottom:.3rem">EASY</div>
                <div style="color:var(--text-dim);font-size:.85rem">4 huruf · 8 percobaan</div>
            </div>
            <div style="padding:1.5rem;border-radius:16px;background:rgba(255,215,64,0.07);border:1px solid rgba(255,215,64,0.2);text-align:center">
                <div style="font-size:2rem;margin-bottom:.5rem">🟡</div>
                <div style="font-family:'Orbitron',monospace;color:#ffd740;margin-bottom:.3rem">MEDIUM</div>
                <div style="color:var(--text-dim);font-size:.85rem">5 huruf · 6 percobaan</div>
            </div>
            <div style="padding:1.5rem;border-radius:16px;background:rgba(255,23,68,0.07);border:1px solid rgba(255,23,68,0.2);text-align:center">
                <div style="font-size:2rem;margin-bottom:.5rem">🔴</div>
                <div style="font-family:'Orbitron',monospace;color:#ff6090;margin-bottom:.3rem">HARD</div>
                <div style="color:var(--text-dim);font-size:.85rem">6 huruf · 4 percobaan · ⏱ 60 detik</div>
            </div>
        </div>
    </div>
</div>

<footer>
    <strong style="color:var(--purple-bright)">KeBox</strong> &copy; <?= date('Y') ?> — Platform Game Online
</footer>

</body>
</html>
