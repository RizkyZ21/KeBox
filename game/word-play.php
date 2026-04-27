<?php
require_once '../includes/auth.php';
requireLogin();
$level = in_array($_GET['level'] ?? '', ['easy','medium','hard']) ? $_GET['level'] : 'easy';
$mode  = $_GET['mode'] ?? '1p';
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
<title>Word Game <?= strtoupper($level) ?> — KeBox</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.game-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem; }
.game-info { display:flex; gap:1rem; align-items:center; flex-wrap:wrap; }
.info-pill {
    padding:.3rem .8rem; border-radius:20px; font-size:.8rem; font-weight:700;
    background:rgba(124,77,255,.15); border:1px solid rgba(124,77,255,.3); color:var(--purple-bright);
}
#timer-display { font-family:'Orbitron',monospace; font-size:1.2rem; font-weight:700; color:var(--accent-gold); }
#timer-display.danger { color:var(--accent-red); animation: blink .5s step-end infinite; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.4} }
#message { min-height:1.5rem; font-weight:700; font-size:1rem; text-align:center; margin:.5rem 0; }
.msg-win  { color:var(--accent-green); }
.msg-lose { color:var(--accent-red); }
.msg-info { color:var(--accent-gold); }
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
    <div class="game-header">
        <div>
            <span style="font-family:'Orbitron',monospace;font-size:1.1rem">💬 Word Game</span>
            <span class="badge badge-<?= $level ?> ml-1"><?= strtoupper($level) ?></span>
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

    <div id="message"></div>

    <!-- BOARD -->
    <div class="wordle-board" id="board"></div>

    <!-- KEYBOARD -->
    <div class="wordle-keyboard" id="keyboard">
        <div class="kb-row" id="kb-row1"></div>
        <div class="kb-row" id="kb-row2"></div>
        <div class="kb-row" id="kb-row3"></div>
    </div>

    <div class="text-center mt-3" style="display:flex;gap:1rem;justify-content:center">
        <button class="btn btn-outline" onclick="restartGame()">🔄 Game Baru</button>
        <a href="word-select.php" class="btn btn-outline">← Pilih Level</a>
    </div>
</div>

<!-- WIN/LOSE MODAL -->
<div class="modal-overlay" id="modal">
    <div class="modal-box">
        <div style="font-size:3rem" id="modal-emoji">🎉</div>
        <h2 id="modal-title">Yeay!</h2>
        <p id="modal-msg">Kamu berhasil menebak kata!</p>
        <p style="font-family:'Orbitron',monospace;font-size:1.5rem;color:var(--purple-bright);margin-bottom:1rem" id="modal-word"></p>
        <div style="display:flex;gap:1rem;justify-content:center">
            <button class="btn btn-primary" onclick="restartGame()">🔄 Main Lagi</button>
            <a href="word-select.php" class="btn btn-outline">← Menu</a>
        </div>
    </div>
</div>

<script>
const LEVEL   = '<?= $level ?>';
const WORDLEN = <?= $c['len'] ?>;
const MAXROWS = <?= $c['tries'] ?>;
const TIMER   = <?= $c['timer'] ?>;

let secretWord = '';
let currentRow = 0;
let currentCol = 0;
let currentGuess = [];
let board = [];
let keyStates = {};
let gameOver = false;
let timerInterval = null;
let timeLeft = TIMER;
let startTime = Date.now();

const KEYS = [
    ['Q','W','E','R','T','Y','U','I','O','P'],
    ['A','S','D','F','G','H','J','K','L'],
    ['ENTER','Z','X','C','V','B','N','M','⌫']
];

async function init() {
    const res = await fetch(`api-word.php?level=${LEVEL}`);
    const data = await res.json();
    secretWord = data.word;
    buildBoard();
    buildKeyboard();
    if (TIMER) startTimer();
}

function buildBoard() {
    const el = document.getElementById('board');
    el.innerHTML = '';
    board = [];
    for (let r = 0; r < MAXROWS; r++) {
        const row = document.createElement('div');
        row.className = 'wordle-row';
        const cells = [];
        for (let c = 0; c < WORDLEN; c++) {
            const cell = document.createElement('div');
            cell.className = 'wordle-cell';
            row.appendChild(cell);
            cells.push(cell);
        }
        el.appendChild(row);
        board.push(cells);
    }
}

function buildKeyboard() {
    KEYS.forEach((row, ri) => {
        const el = document.getElementById(`kb-row${ri+1}`);
        el.innerHTML = '';
        row.forEach(k => {
            const btn = document.createElement('button');
            btn.className = 'kb-key' + (k.length > 1 ? ' wide' : '');
            btn.textContent = k;
            btn.dataset.key = k;
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
        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            endGame(false, true);
        }
    }, 1000);
}

function handleKey(key) {
    if (gameOver) return;
    if (key === '⌫' || key === 'BACKSPACE') {
        if (currentCol > 0) {
            currentCol--;
            currentGuess.pop();
            board[currentRow][currentCol].textContent = '';
            board[currentRow][currentCol].classList.remove('filled');
        }
    } else if (key === 'ENTER') {
        submitGuess();
    } else if (/^[A-Z]$/.test(key) && currentCol < WORDLEN) {
        board[currentRow][currentCol].textContent = key;
        board[currentRow][currentCol].classList.add('filled');
        currentGuess.push(key);
        currentCol++;
    }
}

function submitGuess() {
    if (currentCol < WORDLEN) {
        showMessage('Kata kurang huruf!', 'info');
        shakeRow(currentRow);
        return;
    }
    const guess = currentGuess.join('');
    const result = scoreGuess(guess, secretWord);
    revealRow(currentRow, result, guess);

    const won = result.every(r => r === 'correct');
    currentRow++;
    currentCol = 0;
    currentGuess = [];

    if (won) {
        setTimeout(() => endGame(true), 600);
    } else if (currentRow >= MAXROWS) {
        setTimeout(() => endGame(false), 600);
    }
    updateTriesDisplay();
}

function scoreGuess(guess, secret) {
    const result = Array(WORDLEN).fill('absent');
    const secretArr = secret.split('');
    const guessArr  = guess.split('');
    const used = Array(WORDLEN).fill(false);

    // First pass: correct
    for (let i = 0; i < WORDLEN; i++) {
        if (guessArr[i] === secretArr[i]) {
            result[i] = 'correct';
            used[i] = true;
        }
    }
    // Second pass: present
    for (let i = 0; i < WORDLEN; i++) {
        if (result[i] === 'correct') continue;
        for (let j = 0; j < WORDLEN; j++) {
            if (!used[j] && guessArr[i] === secretArr[j]) {
                result[i] = 'present';
                used[j] = true;
                break;
            }
        }
    }
    return result;
}

function revealRow(row, result, guess) {
    result.forEach((r, i) => {
        const cell = board[row][i];
        setTimeout(() => {
            cell.classList.add(r, 'flip');
        }, i * 100);

        // Update key state
        const key = guess[i];
        const prev = keyStates[key] || 'unused';
        const priority = { correct:3, present:2, absent:1, unused:0 };
        if ((priority[r] || 0) > (priority[prev] || 0)) {
            keyStates[key] = r;
        }
        setTimeout(() => updateKeyboardUI(), (i + 1) * 100);
    });
}

function updateKeyboardUI() {
    document.querySelectorAll('.kb-key').forEach(btn => {
        const k = btn.dataset.key;
        if (keyStates[k]) {
            btn.className = `kb-key${k.length > 1 ? ' wide' : ''} ${keyStates[k]}`;
        }
    });
}

function shakeRow(row) {
    const rowEl = document.getElementById('board').children[row];
    rowEl.classList.add('shake');
    setTimeout(() => rowEl.classList.remove('shake'), 500);
}

function showMessage(msg, type='info') {
    const el = document.getElementById('message');
    el.textContent = msg;
    el.className = `msg-${type}`;
    if (type !== 'info') setTimeout(() => { if (el.textContent === msg) el.textContent = ''; }, 2500);
}

function updateTriesDisplay() {
    document.getElementById('tries-left').textContent = MAXROWS - currentRow;
}

function endGame(won, timeout=false) {
    gameOver = true;
    if (timerInterval) clearInterval(timerInterval);

    const duration = Math.floor((Date.now() - startTime) / 1000);
    const score = won ? Math.max(10, 100 - (currentRow-1)*15 + (TIMER ? Math.floor(timeLeft/2) : 0)) : 0;

    // Save score
    fetch('api-score.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ game_type:'word', level:LEVEL, score, duration })
    });

    const modal = document.getElementById('modal');
    document.getElementById('modal-emoji').textContent = won ? '🎉' : '😔';
    document.getElementById('modal-title').textContent = won ? 'Kamu Menang!' : (timeout ? 'Waktu Habis!' : 'Game Over!');
    document.getElementById('modal-msg').textContent   = won ? `Berhasil menebak dalam ${currentRow} percobaan!` : 'Lebih semangat lagi ya!';
    document.getElementById('modal-word').textContent  = `Kata: ${secretWord}`;
    modal.classList.add('show');
}

function restartGame() {
    document.getElementById('modal').classList.remove('show');
    currentRow = 0; currentCol = 0; currentGuess = []; keyStates = {};
    gameOver = false; startTime = Date.now();
    if (timerInterval) clearInterval(timerInterval);
    const td = document.getElementById('timer-display');
    if (td) { td.textContent = TIMER; td.classList.remove('danger'); }
    const tf = document.getElementById('timer-fill');
    if (tf) tf.style.width = '100%';
    document.getElementById('message').textContent = '';
    document.getElementById('tries-left').textContent = MAXROWS;
    init();
}

document.addEventListener('keydown', e => {
    if (e.key === 'Backspace') handleKey('BACKSPACE');
    else if (e.key === 'Enter') handleKey('ENTER');
    else if (/^[a-zA-Z]$/.test(e.key)) handleKey(e.key.toUpperCase());
});

init();
</script>
</body>
</html>
