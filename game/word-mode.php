<?php
require_once '../includes/auth.php';
requireLogin();
$level = in_array($_GET['level'] ?? '', ['easy','medium','hard']) ? $_GET['level'] : 'easy';
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Pilih Mode — KeBox</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php
$navActive = 'play';
$navBase   = '../';
require_once '../includes/navbar.php';
?>

<div class="container" style="max-width:600px;padding-top:3rem;padding-bottom:3rem">
    <div class="page-header">
        <h1>💬 Word Game</h1>
        <p>Level: <span class="badge badge-<?= $level ?>"><?= strtoupper($level) ?></span> — Pilih mode bermain</p>
    </div>

    <div class="grid grid-2 mt-3">
        <a href="word-play.php?level=<?= $level ?>&mode=1p" class="game-card">
            <span class="icon">🎮</span>
            <h3>1 Player</h3>
            <p>Bermain sendiri, asah kemampuan tebakanmu!</p>
        </a>
        <a href="word-online.php?level=<?= $level ?>" class="game-card">
            <span class="icon">👥</span>
            <h3>2 Player Online</h3>
            <p>Tantang temanmu secara online, siapa yang menang?</p>
        </a>
    </div>

    <div class="text-center mt-4">
        <a href="word-select.php" class="btn btn-outline">← Kembali</a>
    </div>
</div>


<script src="../music.js"></script>
</body>
</html>
