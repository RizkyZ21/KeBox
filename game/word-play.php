<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();
$level = in_array($_GET['level'] ?? '', ['easy','medium','hard']) ? $_GET['level'] : 'easy';
$mode  = $_GET['mode'] ?? '1p';
$room  = preg_replace('/[^A-Z0-9]/', '', strtoupper($_GET['room'] ?? ''));
$role  = in_array($_GET['role'] ?? '', ['host','guest']) ? $_GET['role'] : 'host';
$is2p  = ($mode === '2p' && strlen($room) === 6);
$user  = getCurrentUser();

$config = [
    'easy'   => ['len'=>4,'tries'=>8,'timer'=>0],
    'medium' => ['len'=>5,'tries'=>6,'timer'=>0],
    'hard'   => ['len'=>6,'tries'=>4,'timer'=>60],
];
$c = $config[$level];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Word Game <?= strtoupper($level) ?><?= $is2p ? ' — 2P' : '' ?> — KeBox</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.game-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem; }
.game-info { display:flex; gap:1rem; align-items:center; flex-wrap:wrap; }
.info-pill { padding:.3rem .8rem; border-radius:20px; font-size:.8rem; font-weight:700; background:rgba(124,77,255,.15); border:1px solid rgba(124,77,255,.3); color:var(--purple-bright); }
#timer-display { font-family:'Orbitron',monospace; font-size:1.2rem; font-weight:700; color:var(--accent-gold); }
#timer-display.danger { color:var(--accent-red); animation:blink .5s step-end infinite; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.4} }
#message { min-height:1.5rem; font-weight:700; font-size:1rem; text-align:center; margin:.5rem 0; }
.msg-win  { color:var(--accent-green); }
.msg-lose { color:var(--accent-red); }
.msg-info { color:var(--accent-gold); }

/* 2P Layout */
.mp-layout { display:flex; gap:1.5rem; align-items:flex-start; }
.mp-game   { flex:1; min-width:0; }
.mp-sidebar{ width:220px; min-width:220px; flex-shrink:0; }
@media(max-width:860px){ .mp-layout{flex-direction:column;} .mp-sidebar{width:100%;} }
.mp-vs { text-align:center; font-family:'Orbitron',monospace; font-size:.65rem; letter-spacing:3px; color:var(--text-muted); padding:.3rem 0 .8rem; }
.mp-player-card { background:rgba(45,31,94,.4); border:1px solid var(--border); border-radius:10px; padding:.8rem 1rem; margin-bottom:.7rem; }
.mp-player-name { font-weight:700; font-size:.88rem; margin-bottom:.5rem; }
.mp-player-name.you { color:var(--accent-gold); }
.mp-player-name.opp { color:var(--accent-cyan); }
.mp-guess-dots { display:flex; gap:4px; flex-wrap:wrap; margin:.4rem 0; }
.mp-dot { width:14px; height:14px; border-radius:3px; border:1px solid var(--border); background:transparent; }
.mp-dot.used { background:rgba(124,77,255,.5); border-color:var(--purple-mid); }
.mp-dot.won  { background:rgba(0,229,100,.5); border-color:var(--accent-green); }
.mp-dot.lost { background:rgba(255,59,59,.3); border-color:var(--accent-red); }
.mp-room { text-align:center; padding:.7rem 1rem; background:rgba(45,31,94,.4); border:1px solid var(--border); border-radius:10px; }
.mp-room-code { font-family:'Orbitron',monospace; font-size:1.2rem; letter-spacing:4px; color:var(--purple-bright); margin:.3rem 0; }
.conn-pill { text-align:center; margin-top:.8rem; font-size:.75rem; color:var(--accent-cyan); }
/* Guest loading */
#guest-loading { display:none; background:rgba(10,6,30,.9); border-radius:12px; padding:2rem; text-align:center; color:var(--text-dim); }
.ld-spinner { width:36px; height:36px; border:3px solid var(--border); border-top-color:var(--purple-bright); border-radius:50%; animation:spin .8s linear infinite; margin:0 auto 1rem; }
@keyframes spin { to{transform:rotate(360deg)} }
</style>
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

<?php if ($is2p): ?>
<div class="container" style="padding-top:1.5rem;padding-bottom:3rem">
<div class="mp-layout">
<div class="mp-game">
<?php else: ?>
<div class="container-md" style="padding-top:1.5rem;padding-bottom:3rem">
<?php endif; ?>

    <div class="game-header">
        <div>
            <span style="font-family:'Orbitron',monospace;font-size:1.1rem">💬 Word Game</span>
            <span class="badge badge-<?= $level ?> ml-1"><?= strtoupper($level) ?></span>
            <?php if ($is2p): ?>
            <span class="badge badge-hard ml-1" style="font-size:.7rem">2P</span>
            <?php endif; ?>
        </div>
        <div class="game-info">
            <span class="info-pill">❤️ <span id="tries-left"><?= $c['tries'] ?></span> sisa</span>
            <?php if ($c['timer']): ?>
            <span class="info-pill">⏱ <span id="timer-display"><?= $c['timer'] ?></span>s</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($c['timer']): ?>
    <div class="timer-bar"><div class="timer-fill" id="timer-fill" style="width:100%"></div></div>
    <?php endif; ?>

    <!-- Guest loading overlay -->
    <?php if ($is2p): ?>
    <div id="guest-loading">
        <div class="ld-spinner"></div>
        <div>Menunggu host memilih kata...</div>
    </div>
    <?php endif; ?>

    <div id="game-area">
        <div id="message"></div>
        <div class="wordle-board" id="board"></div>
        <div class="wordle-keyboard" id="keyboard">
            <div class="kb-row" id="kb-row1"></div>
            <div class="kb-row" id="kb-row2"></div>
            <div class="kb-row" id="kb-row3"></div>
        </div>
        <div class="text-center mt-3" style="display:flex;gap:1rem;justify-content:center">
            <?php if (!$is2p): ?>
            <button class="btn btn-outline" onclick="restartGame()">🔄 Game Baru</button>
            <?php endif; ?>
            <a href="word-select.php" class="btn btn-outline">← Pilih Level</a>
        </div>
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
            <div id="my-dots" class="mp-guess-dots"></div>
            <div id="my-status" style="font-size:.78rem;color:var(--text-muted)"></div>
        </div>

        <!-- Opponent status -->
        <div class="mp-player-card">
            <div class="mp-player-name opp" id="opp-name">⏳ Menunggu lawan...</div>
            <div id="opp-dots" class="mp-guess-dots"></div>
            <div id="opp-status" style="font-size:.78rem;color:var(--text-muted)"></div>
        </div>

        <!-- Room info -->
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

<!-- WIN/LOSE MODAL (1P) -->
<div class="modal-overlay" id="modal">
    <div class="modal-box">
        <div style="font-size:3rem" id="modal-emoji">🎉</div>
        <h2 id="modal-title">Yeay!</h2>
        <p id="modal-msg">Kamu berhasil menebak kata!</p>
        <p style="font-family:'Orbitron',monospace;font-size:1.5rem;color:var(--purple-bright);margin-bottom:1rem" id="modal-word"></p>
        <?php if (!$is2p): ?>
        <div style="display:flex;gap:1rem;justify-content:center">
            <button class="btn btn-primary" onclick="restartGame()">🔄 Main Lagi</button>
            <a href="word-select.php" class="btn btn-outline">← Menu</a>
        </div>
        <?php else: ?>
        <p style="color:var(--accent-cyan);font-size:.9rem" id="mp-waiting-msg">⏳ Menunggu lawan selesai...</p>
        <?php endif; ?>
    </div>
</div>

<?php if ($is2p): ?>
<!-- 2P RESULT MODAL -->
<div class="modal-overlay" id="mp-result-modal">
    <div class="modal-box">
        <div id="mp-res-emoji" style="font-size:3.5rem;margin-bottom:.5rem">🏆</div>
        <h2 id="mp-res-title">Kamu Menang!</h2>
        <p id="mp-res-msg" style="color:var(--text-dim);margin-bottom:.5rem"></p>
        <p style="font-family:'Orbitron',monospace;font-size:1.3rem;color:var(--purple-bright)" id="mp-res-word"></p>
        <div style="display:flex;gap:1rem;justify-content:center;margin-top:1.5rem">
            <a href="word-select.php" class="btn btn-outline">← Pilih Level</a>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
const LEVEL   = '<?= $level ?>';
const WORDLEN = <?= $c['len'] ?>;
const MAXROWS = <?= $c['tries'] ?>;
const TIMER   = <?= $c['timer'] ?>;

let secretWord = '';
let currentRow = 0, currentCol = 0;
let currentGuess = [], board = [], keyStates = {};
let gameOver = false;
let timerInterval = null, timeLeft = TIMER, startTime = Date.now();

const KEYS = [
    ['Q','W','E','R','T','Y','U','I','O','P'],
    ['A','S','D','F','G','H','J','K','L'],
    ['ENTER','Z','X','C','V','B','N','M','⌫']
];

async function init(presetWord = null) {
    if (presetWord) {
        secretWord = presetWord;
    } else {
        const res  = await fetch(`api-word.php?level=${LEVEL}`);
        const data = await res.json();
        secretWord = data.word;
    }
    currentRow = 0; currentCol = 0; currentGuess = []; keyStates = {};
    gameOver = false; startTime = Date.now();
    if (timerInterval) clearInterval(timerInterval);
    timeLeft = TIMER;
    const td = document.getElementById('timer-display');
    const tf = document.getElementById('timer-fill');
    if (td) { td.textContent = TIMER; td.classList.remove('danger'); }
    if (tf) tf.style.width = '100%';
    document.getElementById('message').textContent = '';
    document.getElementById('tries-left').textContent = MAXROWS;
    buildBoard(); buildKeyboard();
    if (TIMER) startTimer();
    updateMyDots(0, false, false);
}

function buildBoard() {
    const el = document.getElementById('board');
    el.innerHTML = ''; board = [];
    for (let r = 0; r < MAXROWS; r++) {
        const row = document.createElement('div');
        row.className = 'wordle-row';
        const cells = [];
        for (let c = 0; c < WORDLEN; c++) {
            const cell = document.createElement('div');
            cell.className = 'wordle-cell';
            row.appendChild(cell); cells.push(cell);
        }
        el.appendChild(row); board.push(cells);
    }
}

function buildKeyboard() {
    KEYS.forEach((row, ri) => {
        const el = document.getElementById(`kb-row${ri+1}`);
        el.innerHTML = '';
        row.forEach(k => {
            const btn = document.createElement('button');
            btn.className = 'kb-key' + (k.length > 1 ? ' wide' : '');
            btn.textContent = k; btn.dataset.key = k;
            btn.addEventListener('click', () => handleKey(k));
            el.appendChild(btn);
        });
    });
}

function startTimer() {
    const fill = document.getElementById('timer-fill');
    const display = document.getElementById('timer-display');
    timeLeft = TIMER;
    timerInterval = setInterval(() => {
        timeLeft--;
        const pct = (timeLeft / TIMER) * 100;
        if (fill) fill.style.width = pct + '%';
        if (display) {
            display.textContent = timeLeft;
            if (timeLeft <= 10) display.classList.add('danger');
        }
        if (timeLeft <= 0) { clearInterval(timerInterval); endGame(false, true); }
    }, 1000);
}

function handleKey(key) {
    if (gameOver) return;
    if (key === '⌫' || key === 'BACKSPACE') {
        if (currentCol > 0) { currentCol--; currentGuess.pop(); board[currentRow][currentCol].textContent=''; board[currentRow][currentCol].classList.remove('filled'); }
    } else if (key === 'ENTER') {
        submitGuess();
    } else if (/^[A-Z]$/.test(key) && currentCol < WORDLEN) {
        board[currentRow][currentCol].textContent = key;
        board[currentRow][currentCol].classList.add('filled');
        currentGuess.push(key); currentCol++;
    }
}

function submitGuess() {
    if (currentCol < WORDLEN) { showMessage('Kata kurang huruf!','info'); shakeRow(currentRow); return; }
    const guess = currentGuess.join('');
    const result = scoreGuess(guess, secretWord);
    revealRow(currentRow, result, guess);
    const won = result.every(r => r === 'correct');
    currentRow++; currentCol = 0; currentGuess = [];
    if (won) { setTimeout(() => endGame(true), 600); }
    else if (currentRow >= MAXROWS) { setTimeout(() => endGame(false), 600); }
    updateTriesDisplay();
    updateMyDots(currentRow, won && currentRow > 0, false);
    if (typeof IS_2P !== 'undefined' && IS_2P) post2pProgress(false, false);
}

function scoreGuess(guess, secret) {
    const result = Array(WORDLEN).fill('absent');
    const sArr = secret.split(''), gArr = guess.split(''), used = Array(WORDLEN).fill(false);
    for (let i = 0; i < WORDLEN; i++) { if (gArr[i]===sArr[i]) { result[i]='correct'; used[i]=true; } }
    for (let i = 0; i < WORDLEN; i++) {
        if (result[i]==='correct') continue;
        for (let j = 0; j < WORDLEN; j++) {
            if (!used[j] && gArr[i]===sArr[j]) { result[i]='present'; used[j]=true; break; }
        }
    }
    return result;
}

function revealRow(row, result, guess) {
    result.forEach((r, i) => {
        const cell = board[row][i];
        setTimeout(() => cell.classList.add(r,'flip'), i * 100);
        const key = guess[i];
        const prev = keyStates[key] || 'unused';
        const pri  = {correct:3,present:2,absent:1,unused:0};
        if ((pri[r]||0) > (pri[prev]||0)) keyStates[key] = r;
        setTimeout(() => updateKeyboardUI(), (i+1)*100);
    });
}

function updateKeyboardUI() {
    document.querySelectorAll('.kb-key').forEach(btn => {
        const k = btn.dataset.key;
        if (keyStates[k]) btn.className = `kb-key${k.length>1?' wide':''} ${keyStates[k]}`;
    });
}

function shakeRow(row) {
    const rowEl = document.getElementById('board').children[row];
    rowEl.classList.add('shake'); setTimeout(() => rowEl.classList.remove('shake'), 500);
}

function showMessage(msg, type='info') {
    const el = document.getElementById('message');
    el.textContent = msg; el.className = `msg-${type}`;
    if (type !== 'info') setTimeout(() => { if (el.textContent===msg) el.textContent=''; }, 2500);
}

function updateTriesDisplay() {
    document.getElementById('tries-left').textContent = MAXROWS - currentRow;
}

function endGame(won, timeout=false) {
    gameOver = true;
    if (timerInterval) clearInterval(timerInterval);
    const elapsed = Math.floor((Date.now() - startTime) / 1000);
    const score = won ? Math.max(10, 100 - (currentRow-1)*15 + (TIMER ? Math.floor(timeLeft/2) : 0)) : 0;

    fetch('api-score.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ game_type:'word', level:LEVEL, score, duration:elapsed })
    });

    if (typeof IS_2P !== 'undefined' && IS_2P) {
        post2pProgress(true, won, elapsed);
        updateMyDots(currentRow, won, !won);
        // Show 1p modal briefly (for the word reveal), wait text for non-winner
        document.getElementById('modal-emoji').textContent = won ? '🎉' : '😔';
        document.getElementById('modal-title').textContent = won ? 'Kamu Menang!' : (timeout ? 'Waktu Habis!' : 'Game Over!');
        document.getElementById('modal-msg').textContent   = won ? `Berhasil dalam ${currentRow} percobaan!` : 'Semangat lagi ya!';
        document.getElementById('modal-word').textContent  = `Kata: ${secretWord}`;
        const wm = document.getElementById('mp-waiting-msg');
        if (wm) wm.style.display = 'block';
        document.getElementById('modal').classList.add('show');
        return;
    }

    document.getElementById('modal-emoji').textContent = won ? '🎉' : '😔';
    document.getElementById('modal-title').textContent = won ? 'Kamu Menang!' : (timeout ? 'Waktu Habis!' : 'Game Over!');
    document.getElementById('modal-msg').textContent   = won ? `Berhasil menebak dalam ${currentRow} percobaan!` : 'Lebih semangat lagi ya!';
    document.getElementById('modal-word').textContent  = `Kata: ${secretWord}`;
    document.getElementById('modal').classList.add('show');
}

function restartGame() {
    document.getElementById('modal').classList.remove('show');
    currentRow=0; currentCol=0; currentGuess=[]; keyStates={};
    gameOver=false; startTime=Date.now();
    init();
}

function updateMyDots(guessCount, won, lost) {
    const el = document.getElementById('my-dots');
    if (!el) return;
    let html = '';
    for (let i = 0; i < MAXROWS; i++) {
        let cls = 'mp-dot';
        if (i < guessCount) cls += (won && i===guessCount-1) ? ' won' : (lost ? ' lost' : ' used');
        html += `<div class="${cls}"></div>`;
    }
    el.innerHTML = html;
    const ms = document.getElementById('my-status');
    if (ms) {
        if (won) ms.innerHTML = '<span style="color:var(--accent-green)">✅ Berhasil!</span>';
        else if (lost) ms.innerHTML = '<span style="color:var(--accent-red)">❌ Kehabisan percobaan</span>';
        else ms.textContent = `${guessCount}/${MAXROWS} tebakan`;
    }
}

function updateOppDots(opp, oppName) {
    const el = document.getElementById('opp-dots');
    const os = document.getElementById('opp-status');
    const on = document.getElementById('opp-name');
    if (!opp || !el) return;
    if (oppName && on) on.textContent = '🎮 ' + oppName;
    const g = opp.guesses || 0;
    const won  = opp.won  || false;
    const done = opp.done || false;
    let html = '';
    for (let i = 0; i < MAXROWS; i++) {
        let cls = 'mp-dot';
        if (i < g) cls += (won && i===g-1) ? ' won' : (done && !won ? ' lost' : ' used');
        html += `<div class="${cls}"></div>`;
    }
    el.innerHTML = html;
    if (os) {
        if (won) os.innerHTML = '<span style="color:var(--accent-green)">✅ Berhasil!</span>';
        else if (done && !won) os.innerHTML = '<span style="color:var(--accent-red)">❌ Kehabisan</span>';
        else if (g > 0) os.textContent = `${g}/${MAXROWS} tebakan`;
    }
}

document.addEventListener('keydown', e => {
    if (e.key==='Backspace') handleKey('BACKSPACE');
    else if (e.key==='Enter') handleKey('ENTER');
    else if (/^[a-zA-Z]$/.test(e.key)) handleKey(e.key.toUpperCase());
});

// ── 2P MODE ──────────────────────────────────────────────────────
<?php if ($is2p): ?>
const IS_2P   = true;
const ROOM    = '<?= $room ?>';
const ROLE    = '<?= $role ?>';
const MY_NAME = '<?= htmlspecialchars($user['username']) ?>';

let syncInterval2p = null;
let lastSyncGuess  = -1;

async function init2p() {
    document.getElementById('game-area').style.opacity = '0.4';
    document.getElementById('game-area').style.pointerEvents = 'none';
    document.getElementById('guest-loading').style.display = 'block';

    if (ROLE === 'host') {
        await init(); // fetch word from api-word.php
        // Save word to session
        await fetch('api-session.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'set_word', code:ROOM, word:secretWord })
        });
    } else {
        // Guest: wait for word
        const state = await pollUntil(s => s.word && s.word.length > 0, 90000);
        if (!state) { alert('Host tidak merespons. Kembali ke menu.'); window.location.href='word-select.php'; return; }
        setOppName(state.player1);
        await init(state.word);
    }

    document.getElementById('guest-loading').style.display = 'none';
    document.getElementById('game-area').style.opacity = '';
    document.getElementById('game-area').style.pointerEvents = '';
    startSync2p();
}

async function pollUntil(condition, timeout) {
    const start = Date.now();
    return new Promise(resolve => {
        const iv = setInterval(async () => {
            if (Date.now()-start > timeout) { clearInterval(iv); resolve(null); return; }
            try {
                const r = await fetch(`api-session.php?action=get_state&code=${ROOM}`);
                const s = await r.json();
                if (s && condition(s)) { clearInterval(iv); resolve(s); }
                const oppName = ROLE==='host' ? s.player2 : s.player1;
                if (oppName) setOppName(oppName);
            } catch(e) {}
        }, 1500);
    });
}

async function post2pProgress(done, won, elapsed = null) {
    try {
        await fetch('api-session.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({
                action:'update', code:ROOM,
                guesses:currentRow, done, won,
                time: done ? elapsed : null
            })
        });
    } catch(e) {}
}

function setOppName(name) {
    const el = document.getElementById('opp-name');
    if (el && name) el.textContent = '🎮 ' + name;
}

function startSync2p() {
    // Initial opp name
    fetch(`api-session.php?action=get_state&code=${ROOM}`)
        .then(r=>r.json()).then(s=>{
            const n = ROLE==='host' ? s.player2 : s.player1;
            if (n) setOppName(n);
        }).catch(()=>{});

    syncInterval2p = setInterval(async () => {
        try {
            const r = await fetch(`api-session.php?action=get_state&code=${ROOM}`);
            const state = await r.json();
            if (!state || state.error) return;

            // Update opp panel
            const oppKey  = ROLE==='host' ? 'p2' : 'p1';
            const oppName = ROLE==='host' ? state.player2 : state.player1;
            updateOppDots(state[oppKey], oppName);

            // Connection check
            const cp = document.getElementById('conn-pill');
            if (cp && state[oppKey] && state[oppKey].ts) {
                const age = Math.floor(Date.now()/1000) - state[oppKey].ts;
                cp.textContent = age < 15 ? '🟢 Terhubung' : '🔴 Koneksi lawan bermasalah';
                cp.style.color = age < 15 ? 'var(--accent-cyan)' : 'var(--accent-red)';
            }

            // Deteksi lawan forfeit / keluar game
            if (state.forfeit && state.forfeit !== MY_NAME && !gameOver) {
                clearInterval(syncInterval2p);
                gameOver = true;
                document.getElementById('modal').classList.remove('show');
                show2pResult(MY_NAME, state.word || secretWord, state.forfeit);
                return;
            }

            // Check winner normal
            if (state.winner) {
                clearInterval(syncInterval2p);
                document.getElementById('modal').classList.remove('show');
                show2pResult(state.winner, state.word || secretWord, null);
            }
        } catch(e) {
            const cp = document.getElementById('conn-pill');
            if (cp) { cp.textContent='🔴 Koneksi bermasalah'; cp.style.color='var(--accent-red)'; }
        }
    }, 2000);
}

function show2pResult(winner, word, forfeitPlayer) {
    const iWon = winner===MY_NAME;
    const isDraw = winner==='draw';
    document.getElementById('mp-res-emoji').textContent = isDraw ? '🤝' : (iWon ? '🏆' : '😔');
    document.getElementById('mp-res-title').textContent = isDraw ? 'Seri!' : (iWon ? 'Kamu Menang!' : 'Kamu Kalah!');
    let msg = '';
    if (forfeitPlayer) {
        msg = iWon ? `${forfeitPlayer} keluar dari game. Kamu menang!` : `Kamu keluar dari game.`;
    } else if (isDraw) {
        msg = 'Kalian berdua tidak berhasil menebak kata.';
    } else {
        msg = iWon ? 'Kamu menebak lebih cepat!' : `${winner} berhasil menebak lebih cepat!`;
    }
    document.getElementById('mp-res-msg').textContent = msg;
    document.getElementById('mp-res-word').textContent = `Kata: ${word}`;
    document.getElementById('mp-result-modal').classList.add('show');
}

// Kirim forfeit saat player menutup/meninggalkan halaman
window.addEventListener('beforeunload', () => {
    if (syncInterval2p) {
        navigator.sendBeacon('api-session.php', JSON.stringify({ action:'forfeit', code:ROOM }));
    }
});

// Start
init2p();
<?php else: ?>
init();
<?php endif; ?>
</script>
</body>
</html>
