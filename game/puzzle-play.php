<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();
$levelNum = max(1, min(99, (int)($_GET['level'] ?? 1)));
$mode  = $_GET['mode'] ?? '1p';
$room  = preg_replace('/[^A-Z0-9]/', '', strtoupper($_GET['room'] ?? ''));
$role  = in_array($_GET['role'] ?? '', ['host','guest']) ? $_GET['role'] : 'host';
$is2p  = ($mode === '2p' && strlen($room) === 6);
$user  = getCurrentUser();

$stmt = executeQuery("SELECT * FROM puzzle_levels WHERE level_num=:n", [':n'=>$levelNum]);
$lv   = fetchOne($stmt);
$gridSizeMap = [1=>2,2=>3,3=>3,4=>4,5=>4,6=>5,7=>5,8=>6,9=>7,10=>8,11=>9,12=>10];
$gridSize = $lv ? (int)$lv['grid_size'] : ($gridSizeMap[$levelNum] ?? 4);
$diff = $levelNum <= 3 ? 'easy' : ($levelNum <= 7 ? 'medium' : 'hard');

$maxStmt  = executeQuery("SELECT MAX(level_num) AS max_lvl FROM puzzle_levels");
$maxRow   = fetchOne($maxStmt);
$maxLevel = $maxRow ? (int)$maxRow['max_lvl'] : 12;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sliding Puzzle Level <?= $levelNum ?><?= $is2p ? ' — 2P' : '' ?> — KeBox</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.game-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.2rem; flex-wrap:wrap; gap:.8rem; }
.pill { padding:.3rem .8rem; border-radius:20px; font-size:.8rem; font-weight:700; background:rgba(124,77,255,.15); border:1px solid rgba(124,77,255,.3); color:var(--purple-bright); }
#elapsed { font-family:'Orbitron',monospace; font-size:1rem; color:var(--accent-cyan); }
#moves-count { font-family:'Orbitron',monospace; font-size:1rem; color:var(--accent-gold); }
#puzzle-wrap { display:flex; justify-content:center; }
#progress-bar { height:4px; background:var(--bg-card2); border-radius:2px; margin-bottom:1rem; overflow:hidden; }
#progress-fill { height:100%; background:linear-gradient(90deg,var(--purple-main),var(--accent-cyan)); border-radius:2px; transition:width .3s; }

/* 2P Layout */
.mp-layout { display:flex; gap:1.5rem; align-items:flex-start; }
.mp-game   { flex:1; min-width:0; }
.mp-sidebar{ width:240px; min-width:240px; flex-shrink:0; }
@media(max-width:860px){
    .mp-layout  { flex-direction:column; }
    .mp-sidebar { width:100%; }
}
/* Sidebar cards */
.mp-vs { text-align:center; font-family:'Orbitron',monospace; font-size:.65rem; letter-spacing:3px; color:var(--text-muted); padding:.3rem 0 .8rem; }
.mp-player-card { background:rgba(45,31,94,.4); border:1px solid var(--border); border-radius:10px; padding:.8rem 1rem; margin-bottom:.7rem; }
.mp-player-name { font-weight:700; font-size:.88rem; margin-bottom:.5rem; }
.mp-player-name.you { color:var(--accent-gold); }
.mp-player-name.opp { color:var(--accent-cyan); }
.mp-bar-wrap { height:6px; background:rgba(45,31,94,.8); border-radius:3px; overflow:hidden; margin:.4rem 0; }
.mp-bar-fill { height:100%; border-radius:3px; transition:width .6s; }
.mp-bar-fill.you { background:linear-gradient(90deg,var(--accent-gold),var(--purple-bright)); }
.mp-bar-fill.opp { background:linear-gradient(90deg,var(--accent-cyan),var(--purple-main)); }
.mp-stats { display:flex; justify-content:space-between; font-size:.76rem; color:var(--text-muted); }
.conn-pill { text-align:center; margin-top:.8rem; font-size:.75rem; color:var(--accent-cyan); }
/* Room info */
.mp-room { text-align:center; padding:.7rem 1rem; background:rgba(45,31,94,.4); border:1px solid var(--border); border-radius:10px; }
.mp-room-code { font-family:'Orbitron',monospace; font-size:1.2rem; letter-spacing:4px; color:var(--purple-bright); margin:.3rem 0; }
/* Guest loading overlay */
#guest-loading {
    display:none; position:absolute; inset:0;
    background:rgba(10,6,30,.85); border-radius:8px;
    flex-direction:column; align-items:center; justify-content:center; gap:1rem;
    z-index:10; color:var(--text-dim); font-size:.9rem;
}
.ld-spinner { width:36px; height:36px; border:3px solid var(--border); border-top-color:var(--purple-bright); border-radius:50%; animation:spin .8s linear infinite; }
@keyframes spin { to{transform:rotate(360deg)} }
/* Waiting toast */
#mp-waiting {
    display:none; position:fixed; bottom:1.5rem; right:1.5rem;
    background:rgba(0,229,255,.1); border:1px solid var(--accent-cyan);
    border-radius:12px; padding:.8rem 1.2rem;
    color:var(--accent-cyan); font-size:.88rem; z-index:200;
}
</style>
</head>
<body>

<?php
$navActive = 'play';
$navBase   = '../';
require_once '../includes/navbar.php';
?>

<?php if ($is2p): ?>
<div class="container" style="padding-top:1.5rem;padding-bottom:3rem">
<div class="mp-layout">
<div class="mp-game">
<?php else: ?>
<div class="container-md" style="padding-top:1.5rem;padding-bottom:3rem">
<?php endif; ?>

    <div class="game-top">
        <div>
            <span style="font-family:'Orbitron',monospace;font-size:1rem">🧩 Level <?= $levelNum ?></span>
            <span class="badge badge-<?= $diff ?> ml-1"><?= $gridSize ?>×<?= $gridSize ?></span>
            <?php if ($is2p): ?>
            <span class="badge badge-hard ml-1" style="font-size:.7rem">2P</span>
            <?php endif; ?>
        </div>
        <div style="display:flex;gap:1rem;align-items:center">
            <span class="pill">⏱ <span id="elapsed">0:00</span></span>
            <span class="pill">👆 <span id="moves-count">0</span> moves</span>
        </div>
    </div>

    <div id="progress-bar"><div id="progress-fill" style="width:0%"></div></div>

    <div id="puzzle-wrap" style="position:relative">
        <div id="puzzle-grid" class="puzzle-grid"></div>
        <?php if ($is2p && $role === 'guest'): ?>
        <div id="guest-loading">
            <div class="ld-spinner"></div>
            <span>Menunggu host menyiapkan board...</span>
        </div>
        <?php endif; ?>
    </div>

    <div class="text-center mt-3" style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap">
        <?php if (!$is2p): ?>
        <button class="btn btn-outline" onclick="shuffleBoard()">🔀 Acak Ulang</button>
        <?php endif; ?>
        <a href="puzzle-select.php" class="btn btn-outline">← Pilih Level</a>
    </div>

    <div class="card mt-3" style="text-align:center;background:rgba(124,77,255,.05)">
        <div style="font-size:.8rem;color:var(--text-muted);margin-bottom:.5rem">CARA BERMAIN</div>
        <div style="color:var(--text-dim);font-size:.9rem">Klik tile di sebelah kotak kosong untuk menggesernya. Susun angka dari 1 sampai <?= $gridSize*$gridSize-1 ?> dari kiri atas!</div>
    </div>

<?php if ($is2p): ?>
</div><!-- mp-game -->

<!-- SIDEBAR -->
<div class="mp-sidebar">
    <div class="card" style="padding:1rem">
        <div class="mp-vs">MULTIPLAYER</div>

        <!-- My status -->
        <div class="mp-player-card">
            <div class="mp-player-name you">🎮 <?= htmlspecialchars($user['username']) ?> <span style="font-size:.7rem;opacity:.7">(Kamu)</span></div>
            <div class="mp-bar-wrap"><div class="mp-bar-fill you" id="my-pct-bar" style="width:0%"></div></div>
            <div class="mp-stats"><span id="my-moves">0 moves</span><span id="my-pct">0%</span></div>
            <div id="my-status" style="font-size:.78rem;margin-top:.4rem;color:var(--text-muted)"></div>
        </div>

        <!-- Opponent status -->
        <div class="mp-player-card">
            <div class="mp-player-name opp" id="opp-name">⏳ Menunggu lawan...</div>
            <div class="mp-bar-wrap"><div class="mp-bar-fill opp" id="opp-pct-bar" style="width:0%"></div></div>
            <div class="mp-stats"><span id="opp-moves">-</span><span id="opp-pct">0%</span></div>
            <div id="opp-status" style="font-size:.78rem;margin-top:.4rem;color:var(--text-muted)"></div>
        </div>

        <!-- Room code -->
        <div class="mp-room">
            <div style="font-size:.65rem;letter-spacing:2px;color:var(--text-muted);font-family:'Orbitron',monospace">KODE ROOM</div>
            <div class="mp-room-code"><?= $room ?></div>
            <div class="conn-pill" id="conn-pill">🟢 Terhubung</div>
        </div>
    </div>
</div>
</div><!-- mp-layout -->
<?php endif; ?>

</div><!-- container -->

<!-- WIN MODAL (1P) -->
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
        <?php if (!$is2p): ?>
        <div style="display:flex;gap:1rem;justify-content:center">
            <button class="btn btn-primary" onclick="restartGame()">🔄 Main Lagi</button>
            <?php if ($levelNum < $maxLevel): ?>
            <a href="puzzle-mode.php?level=<?= $levelNum+1 ?>" class="btn btn-success">Level <?= $levelNum+1 ?> →</a>
            <?php endif; ?>
            <a href="puzzle-select.php" class="btn btn-outline">Menu</a>
        </div>
        <?php else: ?>
        <p style="color:var(--accent-cyan);font-size:.9rem">⏳ Menunggu lawan selesai...</p>
        <?php endif; ?>
    </div>
</div>

<?php if ($is2p): ?>
<!-- 2P RESULT MODAL -->
<div class="modal-overlay" id="mp-result-modal">
    <div class="modal-box">
        <div id="mp-res-emoji" style="font-size:3.5rem;margin-bottom:.5rem">🏆</div>
        <h2 id="mp-res-title">Kamu Menang!</h2>
        <p id="mp-res-msg" style="color:var(--text-dim)"></p>
        <div style="display:flex;gap:2rem;justify-content:center;margin:1.2rem 0">
            <div class="stat-box"><div class="num" id="mp-res-time">-</div><div class="lbl">Waktu</div></div>
            <div class="stat-box"><div class="num" id="mp-res-moves">-</div><div class="lbl">Moves</div></div>
        </div>
        <div style="display:flex;gap:1rem;justify-content:center">
            <a href="puzzle-select.php" class="btn btn-outline">← Pilih Level</a>
        </div>
    </div>
</div>
<!-- Waiting toast -->
<div id="mp-waiting">✅ Puzzle selesai! Menunggu lawan...</div>
<?php endif; ?>

<script>
const GRID  = <?= $gridSize ?>;
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

// ── CORE GAME ────────────────────────────────────────────────────

function init(presetTiles = null) {
    if (presetTiles) {
        tiles    = [...presetTiles];
        emptyIdx = tiles.indexOf(0);
        moves    = 0; solved = false;
        updateMovesDisplay();
        if (timerInterval) clearInterval(timerInterval);
        startTime = null;
        document.getElementById('elapsed').textContent = '0:00';
        renderBoard(); updateProgress();
    } else {
        tiles = Array.from({length: TOTAL - 1}, (_, i) => i + 1);
        tiles.push(0);
        originalState = [...tiles];
        emptyIdx = TOTAL - 1;
        moves = 0; solved = false;
        updateMovesDisplay();
        shuffleBoard();
    }
}

function shuffleBoard() {
    tiles = Array.from({length: TOTAL - 1}, (_, i) => i + 1);
    tiles.push(0);
    emptyIdx = TOTAL - 1;
    const n = GRID <= 3 ? 50 : GRID <= 4 ? 100 : GRID <= 5 ? 150 : 200;
    for (let i = 0; i < n; i++) {
        const nb = getNeighbors(emptyIdx);
        const pick = nb[Math.floor(Math.random() * nb.length)];
        tiles[emptyIdx] = tiles[pick]; tiles[pick] = 0; emptyIdx = pick;
    }
    moves = 0; solved = false;
    updateMovesDisplay();
    if (timerInterval) clearInterval(timerInterval);
    startTime = null;
    document.getElementById('elapsed').textContent = '0:00';
    renderBoard(); updateProgress();
}

function getNeighbors(idx) {
    const nb = [], row = Math.floor(idx / GRID), col = idx % GRID;
    if (row > 0) nb.push(idx - GRID);
    if (row < GRID-1) nb.push(idx + GRID);
    if (col > 0) nb.push(idx - 1);
    if (col < GRID-1) nb.push(idx + 1);
    return nb;
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
        tile.style.cssText = `width:${tileSize}px;height:${tileSize}px;font-size:${tileSize > 50 ? '1.1rem' : '.75rem'}`;
        if (val !== 0) {
            tile.textContent = val;
            if (val === idx + 1) tile.classList.add('correct');
            tile.addEventListener('click', () => moveTile(idx));
        }
        grid.appendChild(tile);
    });
}

function moveTile(idx) {
    if (solved) return;
    const nb = getNeighbors(emptyIdx);
    if (!nb.includes(idx)) return;
    if (!startTime) {
        startTime = Date.now();
        timerInterval = setInterval(updateTimer, 1000);
    }
    tiles[emptyIdx] = tiles[idx]; tiles[idx] = 0; emptyIdx = idx;
    moves++; updateMovesDisplay(); renderBoard(); updateProgress();
    if (checkWin()) { solved = true; clearInterval(timerInterval); setTimeout(showWin, 400); }
}

function checkWin() {
    for (let i = 0; i < TOTAL - 1; i++) { if (tiles[i] !== i + 1) return false; }
    return tiles[TOTAL - 1] === 0;
}

function updateProgress() {
    let correct = 0;
    for (let i = 0; i < TOTAL - 1; i++) { if (tiles[i] === i + 1) correct++; }
    document.getElementById('progress-fill').style.width = ((correct / (TOTAL - 1)) * 100) + '%';
}

function updateMovesDisplay() { document.getElementById('moves-count').textContent = moves; }

function updateTimer() {
    if (!startTime) return;
    const s = Math.floor((Date.now() - startTime) / 1000);
    document.getElementById('elapsed').textContent = `${Math.floor(s/60)}:${(s%60).toString().padStart(2,'0')}`;
}

function showWin() {
    const elapsed = startTime ? Math.floor((Date.now() - startTime) / 1000) : 0;
    const m = Math.floor(elapsed / 60), s = elapsed % 60;
    const timeStr = `${m}:${s.toString().padStart(2,'0')}`;
    const score = Math.max(10, 1000 - moves * 2 - elapsed);

    document.getElementById('win-time').textContent  = timeStr;
    document.getElementById('win-moves').textContent = moves;
    document.getElementById('win-score').textContent = score;

    // Save score to leaderboard
    fetch('api-score.php', {
        method: 'POST', keepalive: true,
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ game_type:'puzzle', level: LEVEL_LABEL, score, duration: elapsed, moves })
    });

    if (typeof IS_2P !== 'undefined' && IS_2P) {
        // 2P: post finish, show toast, let sync handle result modal
        post2pProgress(true, elapsed);
        document.getElementById('mp-waiting').style.display = 'flex';
        document.getElementById('mp-res-time').textContent  = timeStr;
        document.getElementById('mp-res-moves').textContent = moves;
        const ms = document.getElementById('my-status');
        if (ms) ms.innerHTML = '<span style="color:var(--accent-gold)">✅ Selesai!</span>';
        return;
    }
    document.getElementById('win-modal').classList.add('show');
}

function restartGame() {
    document.getElementById('win-modal').classList.remove('show');
    init();
}

// ── 2P MODE ──────────────────────────────────────────────────────
<?php if ($is2p): ?>
const IS_2P    = true;
const ROOM     = '<?= $room ?>';
const ROLE     = '<?= $role ?>';
const MY_NAME  = '<?= htmlspecialchars($user['username']) ?>';

let syncInterval2p  = null;
let lastSyncMoves   = -1;
let lastOppTs       = 0;
let myFinishTime    = null;

async function init2p() {
    if (ROLE === 'host') {
        init(); // shuffle board
        await saveBoard();
        startSync2p();
    } else {
        // Guest: show loading, wait for board
        document.getElementById('guest-loading').style.display = 'flex';
        document.getElementById('puzzle-grid').style.pointerEvents = 'none';
        const state = await pollUntil(s => s.board && s.board.length > 0, 90000);
        document.getElementById('guest-loading').style.display = 'none';
        document.getElementById('puzzle-grid').style.pointerEvents = '';
        if (!state) { alert('Host tidak merespons. Kembali ke menu.'); window.location.href = 'puzzle-select.php'; return; }
        setOppName(state.player1);
        init(state.board);
        startSync2p();
    }
}

// Poll get_state until condition(state) is true or timeout
async function pollUntil(condition, timeout) {
    const start = Date.now();
    return new Promise(resolve => {
        const iv = setInterval(async () => {
            if (Date.now() - start > timeout) { clearInterval(iv); resolve(null); return; }
            try {
                const r = await fetch(`api-session.php?action=get_state&code=${ROOM}`);
                const s = await r.json();
                if (s && condition(s)) { clearInterval(iv); resolve(s); }
                // Update opp name if available
                const oppName = ROLE === 'host' ? s.player2 : s.player1;
                if (oppName) setOppName(oppName);
            } catch(e) {}
        }, 1500);
    });
}

async function saveBoard() {
    try {
        await fetch('api-session.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'set_board', code:ROOM, board:tiles, grid:GRID })
        });
    } catch(e) {}
}

async function post2pProgress(done = false, elapsed = null) {
    let correct = 0;
    for (let i = 0; i < TOTAL - 1; i++) { if (tiles[i] === i+1) correct++; }
    const pct = (correct / (TOTAL - 1)) * 100;
    updateMyPanel(moves, pct);
    try {
        await fetch('api-session.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'update', code:ROOM, moves, pct, done, time: elapsed })
        });
    } catch(e) {}
}

function setOppName(name) {
    const el = document.getElementById('opp-name');
    if (el && name) el.textContent = '🎮 ' + name;
}

function updateMyPanel(mv, pct) {
    const bar = document.getElementById('my-pct-bar');
    const mm  = document.getElementById('my-moves');
    const mp  = document.getElementById('my-pct');
    if (bar) bar.style.width  = pct + '%';
    if (mm)  mm.textContent   = mv + ' moves';
    if (mp)  mp.textContent   = Math.round(pct) + '%';
}

function updateOppPanel(state) {
    const oppKey  = ROLE === 'host' ? 'p2' : 'p1';
    const oppName = ROLE === 'host' ? state.player2 : state.player1;
    const opp     = state[oppKey];
    if (!opp) return;
    if (oppName) setOppName(oppName);

    const pb = document.getElementById('opp-pct-bar');
    const om = document.getElementById('opp-moves');
    const op = document.getElementById('opp-pct');
    const os = document.getElementById('opp-status');
    const cp = document.getElementById('conn-pill');

    if (pb) pb.style.width = (opp.pct || 0) + '%';
    if (om) om.textContent = (opp.moves || 0) + ' moves';
    if (op) op.textContent = Math.round(opp.pct || 0) + '%';
    if (os && opp.done) os.innerHTML = '<span style="color:var(--accent-cyan)">✅ Selesai!</span>';

    // Connection check: if opp ts > 0 and recent (< 15s)
    if (cp && opp.ts) {
        const age = Math.floor(Date.now()/1000) - opp.ts;
        cp.textContent = age < 15 ? '🟢 Terhubung' : '🔴 Koneksi lawan bermasalah';
        cp.style.color = age < 15 ? 'var(--accent-cyan)' : 'var(--accent-red)';
    }
}

function startSync2p() {
    // Initial setup
    fetch(`api-session.php?action=get_state&code=${ROOM}`)
        .then(r => r.json()).then(s => {
            const n = ROLE === 'host' ? s.player2 : s.player1;
            if (n) setOppName(n);
        }).catch(()=>{});

    syncInterval2p = setInterval(async () => {
        // POST my progress if moves changed
        if (moves !== lastSyncMoves && !solved) {
            lastSyncMoves = moves;
            post2pProgress(false, null);
        }

        // GET state
        try {
            const r = await fetch(`api-session.php?action=get_state&code=${ROOM}`);
            const state = await r.json();
            if (!state || state.error) return;

            updateOppPanel(state);

            // Deteksi lawan forfeit / keluar game
            if (state.forfeit && state.forfeit !== MY_NAME && !solved) {
                clearInterval(syncInterval2p);
                document.getElementById('mp-waiting').style.display = 'none';
                show2pResult(true, MY_NAME, state.forfeit);
                return;
            }

            // Check for game over normal
            if (state.winner) {
                clearInterval(syncInterval2p);
                document.getElementById('mp-waiting').style.display = 'none';
                const iWon = state.winner === MY_NAME;
                show2pResult(iWon, state.winner, state.forfeit || null);
            }
        } catch(e) {
            const cp = document.getElementById('conn-pill');
            if (cp) { cp.textContent = '🔴 Koneksi bermasalah'; cp.style.color='var(--accent-red)'; }
        }
    }, 2000);
}

function show2pResult(iWon, winner, forfeitPlayer) {
    const modal = document.getElementById('mp-result-modal');
    document.getElementById('mp-res-emoji').textContent = iWon ? '🏆' : '😔';
    document.getElementById('mp-res-title').textContent = iWon ? 'Kamu Menang!' : 'Kamu Kalah!';
    let msg = '';
    if (forfeitPlayer && forfeitPlayer !== MY_NAME) {
        msg = `${forfeitPlayer} keluar dari game. Kamu menang!`;
    } else {
        msg = iWon
            ? `Selamat! Kamu menyelesaikan puzzle lebih cepat dari ${winner}!`
            : `${winner} menyelesaikan puzzle lebih cepat!`;
    }
    document.getElementById('mp-res-msg').textContent = msg;
    modal.classList.add('show');
    // Hide 1p win modal if open
    document.getElementById('win-modal').classList.remove('show');
}

// Kirim forfeit saat player menutup/meninggalkan halaman
window.addEventListener('beforeunload', () => {
    if (syncInterval2p) {
        navigator.sendBeacon('api-session.php', JSON.stringify({ action:'forfeit', code:ROOM }));
    }
});

// Start in 2p mode
init2p();
<?php else: ?>
// Start in 1p mode
init();
<?php endif; ?>
</script>

<script src="../music.js"></script>
</body>
</html>
