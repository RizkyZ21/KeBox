<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();
$levelNum = max(1, min(99, (int)($_GET['level'] ?? 1)));

$stmt = executeQuery("SELECT * FROM puzzle_levels WHERE level_num=:n", [':n'=>$levelNum]);
$lv   = fetchOne($stmt);
$gridSizeMap = [1=>2,2=>3,3=>3,4=>4,5=>4,6=>5,7=>5,8=>6,9=>7,10=>8,11=>9,12=>10];
$gridSize = $lv ? (int)$lv['grid_size'] : ($gridSizeMap[$levelNum] ?? 4);
$diff = $levelNum <= 3 ? 'easy' : ($levelNum <= 7 ? 'medium' : 'hard');

// Total level tersedia (untuk tombol next level)
$maxStmt  = executeQuery("SELECT MAX(level_num) AS max_lvl FROM puzzle_levels");
$maxRow   = fetchOne($maxStmt);
$maxLevel = $maxRow ? (int)$maxRow['max_lvl'] : 12;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sliding Puzzle Level <?= $levelNum ?> — KeBox</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.game-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.2rem; flex-wrap:wrap; gap:.8rem; }
.pill { padding:.3rem .8rem; border-radius:20px; font-size:.8rem; font-weight:700; background:rgba(124,77,255,.15); border:1px solid rgba(124,77,255,.3); color:var(--purple-bright); }
#elapsed { font-family:'Orbitron',monospace; font-size:1rem; color:var(--accent-cyan); }
#moves-count { font-family:'Orbitron',monospace; font-size:1rem; color:var(--accent-gold); }
#puzzle-wrap { display:flex; justify-content:center; }
#progress-bar { height:4px; background:var(--bg-card2); border-radius:2px; margin-bottom:1rem; overflow:hidden; }
#progress-fill { height:100%; background:linear-gradient(90deg,var(--purple-main),var(--accent-cyan)); border-radius:2px; transition:width .3s; }
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

<div class="container-md" style="padding-top:1.5rem;padding-bottom:3rem">

    <div class="game-top">
        <div>
            <span style="font-family:'Orbitron',monospace;font-size:1rem">🧩 Level <?= $levelNum ?></span>
            <span class="badge badge-<?= $diff ?> ml-1"><?= $gridSize ?>×<?= $gridSize ?></span>
        </div>
        <div style="display:flex;gap:1rem;align-items:center">
            <span class="pill">⏱ <span id="elapsed">0:00</span></span>
            <span class="pill">👆 <span id="moves-count">0</span> moves</span>
        </div>
    </div>

    <div id="progress-bar"><div id="progress-fill" style="width:0%"></div></div>

    <div id="puzzle-wrap">
        <div id="puzzle-grid" class="puzzle-grid"></div>
    </div>

    <div class="text-center mt-3" style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap">
        <button class="btn btn-outline" onclick="shuffleBoard()">🔀 Acak Ulang</button>
        <button class="btn btn-outline" onclick="resetBoard()">🔄 Reset</button>
        <a href="puzzle-select.php" class="btn btn-outline">← Pilih Level</a>
    </div>

    <div class="card mt-3" style="text-align:center;background:rgba(124,77,255,.05)">
        <div style="font-size:.8rem;color:var(--text-muted);margin-bottom:.5rem">CARA BERMAIN</div>
        <div style="color:var(--text-dim);font-size:.9rem">Klik tile di sebelah kotak kosong untuk menggesernya. Susun angka dari 1 sampai <?= $gridSize*$gridSize-1 ?> dari kiri atas!</div>
    </div>
</div>

<!-- WIN MODAL -->
<div class="modal-overlay" id="win-modal">
    <div class="modal-box">
        <div style="font-size:3rem;margin-bottom:.5rem">🎉</div>
        <h2>Puzzle Selesai!</h2>
        <p style="margin-bottom:.5rem">Selamat, kamu berhasil!</p>
        <div style="display:flex;gap:2rem;justify-content:center;margin-bottom:1.5rem">
            <div class="stat-box"><div class="num" id="win-time">-</div><div class="lbl">Waktu</div></div>
            <div class="stat-box"><div class="num" id="win-moves">-</div><div class="lbl">Moves</div></div>
            <div class="stat-box"><div class="num" id="win-score">-</div><div class="lbl">Score</div></div>
        </div>
        <div style="display:flex;gap:1rem;justify-content:center">
            <button class="btn btn-primary" onclick="restartGame()">🔄 Main Lagi</button>
            <?php if ($levelNum < $maxLevel): ?>
            <a href="puzzle-mode.php?level=<?= $levelNum+1 ?>" class="btn btn-success">Level <?= $levelNum+1 ?> →</a>
            <?php endif; ?>
            <a href="puzzle-select.php" class="btn btn-outline">Menu</a>
        </div>
    </div>
</div>

<script>
const GRID = <?= $gridSize ?>;
const LEVEL = <?= $levelNum ?>;
const TOTAL = GRID * GRID;
const LEVEL_LABEL = 'level-<?= $levelNum ?>';

let tiles = [];
let emptyIdx = TOTAL - 1;
let moves = 0;
let solved = false;
let startTime = null;
let timerInterval = null;
let originalState = [];

function init() {
    // Generate solved state: [1, 2, ..., N-1, 0]
    tiles = Array.from({length: TOTAL - 1}, (_, i) => i + 1);
    tiles.push(0);
    originalState = [...tiles];
    emptyIdx = TOTAL - 1;
    moves = 0;
    solved = false;
    updateMovesDisplay();
    shuffleBoard();
}

function shuffleBoard() {
    // Do random valid moves from solved state to ensure solvable
    tiles = Array.from({length: TOTAL - 1}, (_, i) => i + 1);
    tiles.push(0);
    emptyIdx = TOTAL - 1;

    const shuffleMoves = GRID <= 3 ? 50 : GRID <= 4 ? 100 : GRID <= 5 ? 150 : 200;
    for (let i = 0; i < shuffleMoves; i++) {
        const neighbors = getNeighbors(emptyIdx);
        const pick = neighbors[Math.floor(Math.random() * neighbors.length)];
        tiles[emptyIdx] = tiles[pick];
        tiles[pick] = 0;
        emptyIdx = pick;
    }
    moves = 0;
    solved = false;
    updateMovesDisplay();
    if (timerInterval) clearInterval(timerInterval);
    startTime = null;
    document.getElementById('elapsed').textContent = '0:00';
    renderBoard();
    updateProgress();
}

function resetBoard() {
    tiles = [...originalState];
    emptyIdx = TOTAL - 1;
    moves = 0; solved = false;
    if (timerInterval) clearInterval(timerInterval);
    startTime = null;
    document.getElementById('elapsed').textContent = '0:00';
    updateMovesDisplay();
    renderBoard();
    updateProgress();
}

function getNeighbors(idx) {
    const neighbors = [];
    const row = Math.floor(idx / GRID);
    const col = idx % GRID;
    if (row > 0) neighbors.push(idx - GRID);
    if (row < GRID-1) neighbors.push(idx + GRID);
    if (col > 0) neighbors.push(idx - 1);
    if (col < GRID-1) neighbors.push(idx + 1);
    return neighbors;
}

function renderBoard() {
    const grid = document.getElementById('puzzle-grid');
    const tileSize = Math.min(Math.floor(480 / GRID), 70);
    grid.style.gridTemplateColumns = `repeat(${GRID}, ${tileSize}px)`;
    grid.style.gap = GRID > 5 ? '3px' : '4px';
    grid.innerHTML = '';

    tiles.forEach((val, idx) => {
        const tile = document.createElement('div');
        tile.className = 'puzzle-tile' + (val === 0 ? ' empty' : '');
        tile.style.width  = tileSize + 'px';
        tile.style.height = tileSize + 'px';
        tile.style.fontSize = tileSize > 50 ? '1.1rem' : '.75rem';

        if (val !== 0) {
            tile.textContent = val;
            // Check if in correct position
            if (val === idx + 1) tile.classList.add('correct');
            tile.addEventListener('click', () => moveTile(idx));
        }
        grid.appendChild(tile);
    });
}

function moveTile(idx) {
    if (solved) return;
    const neighbors = getNeighbors(emptyIdx);
    if (!neighbors.includes(idx)) return;

    // Start timer on first move
    if (!startTime) {
        startTime = Date.now();
        timerInterval = setInterval(updateTimer, 1000);
    }

    tiles[emptyIdx] = tiles[idx];
    tiles[idx] = 0;
    emptyIdx = idx;
    moves++;
    updateMovesDisplay();
    renderBoard();
    updateProgress();

    if (checkWin()) {
        solved = true;
        clearInterval(timerInterval);
        setTimeout(showWin, 400);
    }
}

function checkWin() {
    for (let i = 0; i < TOTAL - 1; i++) {
        if (tiles[i] !== i + 1) return false;
    }
    return tiles[TOTAL - 1] === 0;
}

function updateProgress() {
    let correct = 0;
    for (let i = 0; i < TOTAL - 1; i++) { if (tiles[i] === i + 1) correct++; }
    const pct = (correct / (TOTAL - 1)) * 100;
    document.getElementById('progress-fill').style.width = pct + '%';
}

function updateMovesDisplay() {
    document.getElementById('moves-count').textContent = moves;
}

function updateTimer() {
    if (!startTime) return;
    const elapsed = Math.floor((Date.now() - startTime) / 1000);
    const m = Math.floor(elapsed / 60);
    const s = elapsed % 60;
    document.getElementById('elapsed').textContent = `${m}:${s.toString().padStart(2,'0')}`;
}

function showWin() {
    const elapsed = startTime ? Math.floor((Date.now() - startTime) / 1000) : 0;
    const m = Math.floor(elapsed / 60);
    const s = elapsed % 60;
    const timeStr = `${m}:${s.toString().padStart(2,'0')}`;
    const score = Math.max(10, 1000 - moves * 2 - elapsed);

    document.getElementById('win-time').textContent  = timeStr;
    document.getElementById('win-moves').textContent = moves;
    document.getElementById('win-score').textContent = score;
    document.getElementById('win-modal').classList.add('show');

    // Save score
    fetch('api-score.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        keepalive: true,
        body: JSON.stringify({ game_type:'puzzle', level: LEVEL_LABEL, score, duration: elapsed })
    });
}

function restartGame() {
    document.getElementById('win-modal').classList.remove('show');
    init();
}

init();
</script>
</body>
</html>