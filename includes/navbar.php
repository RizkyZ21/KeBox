<?php
/**
 * includes/navbar.php
 *
 * Cara pakai:
 *   require_once 'includes/navbar.php';         // dari root
 *   require_once '../includes/navbar.php';       // dari game/
 *
 * Parameter (set sebelum require):
 *   $navActive  = 'dashboard' | 'leaderboard' | 'profile' | ''   (default '')
 *   $navBase    = ''  (root pages)  atau  '../'  (game/ pages)    (default '')
 *
 * Contoh:
 *   $navActive = 'leaderboard'; $navBase = '';
 *   require_once 'includes/navbar.php';
 */

$navActive = $navActive ?? '';
$navBase   = $navBase   ?? '';
$_u        = $user      ?? ['id' => null, 'username' => '', 'role' => 'user'];
?>
<nav class="navbar">
    <a href="<?= $navBase ?>index.php" class="navbar-brand">Ke<span>Box</span></a>
    <ul class="navbar-nav">
        <?php if ($_u['id']): ?>
            <li>
                <a href="<?= $navBase ?><?= $_u['role']==='admin' ? 'admin/dashboard.php' : 'dashboard.php' ?>"
                   <?= $navActive==='dashboard' ? 'class="active"' : '' ?>>Dashboard</a>
            </li>
            <li>
                <a href="<?= $navBase ?>leaderboard.php"
                   <?= $navActive==='leaderboard' ? 'class="active"' : '' ?>>Leaderboard</a>
            </li>
            <?php if ($navActive !== 'play'): // Sembunyikan Profil di halaman game biar rapi ?>
            <li>
                <a href="<?= $navBase ?>profile.php"
                   <?= $navActive==='profile' ? 'class="active"' : '' ?>>Profil</a>
            </li>
            <?php endif; ?>
            <li><a href="<?= $navBase ?>logout.php" class="btn btn-outline btn-sm">Logout</a></li>
        <?php else: ?>
            <li>
                <a href="<?= $navBase ?>leaderboard.php"
                   <?= $navActive==='leaderboard' ? 'class="active"' : '' ?>>Leaderboard</a>
            </li>
            <li><a href="<?= $navBase ?>login.php">Login</a></li>
            <li><a href="<?= $navBase ?>register.php" class="btn btn-primary btn-sm">Register</a></li>
        <?php endif; ?>
        <li><button id="music-toggle-btn" title="Toggle musik">🎵</button></li>
    </ul>
</nav>
