<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();
$levelNum = max(1, min(99, (int)($_GET['level'] ?? 1)));
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>&#x1F9E9; Puzzle Online &#x2014; KeBox</title>
<link rel="stylesheet" href="../css/style.css">
<style>
<?php include '../includes/lobby-styles.php'; ?>
</style>
</head>
<body>
<?php
$navActive = 'play';
$navBase   = '../';
require_once '../includes/navbar.php';
?>

<div class="container-sm" style="padding-top:2.5rem;padding-bottom:3rem">
    <div class="page-header" style="margin-bottom:2rem">
        <h1>&#x1F9E9; Puzzle Online</h1>
        <p style="color:var(--text-dim)">Level <?= $levelNum ?> &nbsp;&middot;&nbsp; 1 vs 1</p>
    </div>

    <!-- Halaman awal: Buat / Gabung -->
    <div id="main-view">
        <div class="card mb-3">
            <div class="section-label">BUAT ROOM BARU</div>
            <p style="color:var(--text-dim);font-size:.9rem;text-align:center;margin-bottom:1.2rem">
                Buat room dan bagikan kodenya ke temanmu
            </p>
            <button class="btn btn-primary w-full" onclick="createRoom()">Buat Room</button>
        </div>
        <div class="card">
            <div class="section-label">GABUNG ROOM</div>
            <div class="form-group" style="margin-bottom:1rem">
                <label class="form-label">Kode Room</label>
                <input type="text" id="room-code-input" class="form-control code-input"
                       placeholder="ABC123" maxlength="6"
                       oninput="this.value=this.value.toUpperCase()">
            </div>
            <button class="btn btn-outline w-full" onclick="joinRoom()">Gabung Room</button>
        </div>
    </div>

    <!-- Lobby: setelah buat/gabung -->
    <div id="lobby-view" style="display:none">
        <div class="lobby-card">
            <div class="section-label">KODE ROOM</div>
            <div style="text-align:center;margin-bottom:.5rem">
                <div class="lobby-code" id="display-code">------</div>
            </div>
            <div style="font-size:.8rem;color:var(--text-muted);text-align:center;margin-bottom:1.5rem">
                Bagikan kode ini ke temanmu
            </div>

            <div class="section-label">PEMAIN <span id="player-count">(1/2)</span></div>
            <div class="player-list">
                <div class="player-slot filled" id="slot1">
                    <div class="slot-avatar" id="slot1-avatar">--</div>
                    <div class="slot-info">
                        <div class="slot-name" id="slot1-name">-</div>
                        <div class="slot-role">Host &#x1F451;</div>
                    </div>
                    <div class="slot-status ready">&#x2705;</div>
                </div>
                <div class="player-slot waiting" id="slot2">
                    <div class="slot-avatar ghost">?</div>
                    <div class="slot-info">
                        <div class="slot-name">Menunggu pemain 2...</div>
                        <div class="slot-role">Pemain 2</div>
                    </div>
                    <div class="slot-status"><span class="pulse-dot"></span></div>
                </div>
            </div>

            <!-- Aksi Host -->
            <div id="host-actions" style="display:none">
                <button class="btn w-full" id="start-btn" onclick="startGame()" disabled
                        style="opacity:.45;cursor:not-allowed;background:rgba(45,31,94,.5);border-color:var(--border);color:var(--text-muted)">
                    &#x23F3; Menunggu pemain 2...
                </button>
            </div>
            <!-- Info Guest -->
            <div id="guest-actions" style="display:none">
                <div class="waiting-msg">&#x23F3; Menunggu host memulai game...</div>
            </div>

            <button class="btn btn-outline w-full mt-2" onclick="cancelLobby()">&larr; Kembali</button>
        </div>
    </div>
</div>

<script>
const USERNAME = '<?= addslashes($user['username']) ?>';
const GAME_LEVEL = <?= $levelNum ?>;
const PLAY_FILE  = 'puzzle-play.php';
let currentCode = null, myRole = null, pollIv = null, heartbeatIv = null;

function generateCode() {
    const c = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let s = '';
    for (let i = 0; i < 6; i++) s += c[Math.floor(Math.random() * c.length)];
    return s;
}

async function createRoom() {
    try {
        const code = generateCode();
        const res  = await fetch('api-session.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action:'create', code, level: 'puzzle-' + GAME_LEVEL })
        });
        const d = await res.json();
        if (d.error) { alert('Gagal membuat room: ' + d.error); return; }
        currentCode = code;
        myRole      = 'host';
        showLobby('host');
    } catch(e) { alert('Error: ' + e.message); }
}

async function joinRoom() {
    try {
        const raw  = document.getElementById('room-code-input').value;
        const code = raw.toUpperCase().replace(/[^A-Z0-9]/g, '');
        if (code.length !== 6) { alert('Kode harus 6 karakter!'); return; }
        const res  = await fetch('api-session.php?action=join&code=' + code, { method: 'POST' });
        const d    = await res.json();
        if (d.error) { alert(d.error); return; }
        currentCode = code;
        myRole      = 'guest';
        showLobby('guest');
    } catch(e) { alert('Error: ' + e.message); }
}

function showLobby(role) {
    document.getElementById('main-view').style.display  = 'none';
    document.getElementById('lobby-view').style.display = 'block';
    document.getElementById('display-code').textContent  = currentCode;

    if (role === 'host') {
        setSlot('slot1', USERNAME, true);
        document.getElementById('host-actions').style.display = 'block';
    } else {
        // Guest: tampilkan diri di slot2, fetch nama host dari server
        setSlot('slot2', USERNAME, true);
        document.getElementById('guest-actions').style.display = 'block';
        fetch('api-session.php?action=get_state&code=' + currentCode)
            .then(r => r.json())
            .then(s => { if (s && s.player1) setSlot('slot1', s.player1, false); })
            .catch(() => {});
    }
    startPoll(role);
}

function setSlot(slotId, name, isMe) {
    const el        = document.getElementById(slotId);
    const initials  = name.substring(0, 2).toUpperCase();
    const roleLabel = slotId === 'slot1' ? 'Host &#x1F451;' : 'Pemain 2';
    const meTag     = isMe ? ' <span style="color:var(--accent-gold);font-size:.75rem">(Kamu)</span>' : '';
    const avatarId  = slotId + '-avatar';
    const nameId    = slotId + '-name';
    el.className    = 'player-slot filled';
    el.innerHTML    =
        '<div class="slot-avatar" id="' + avatarId + '">' + initials + '</div>' +
        '<div class="slot-info">' +
          '<div class="slot-name" id="' + nameId + '">' + name + meTag + '</div>' +
          '<div class="slot-role">' + roleLabel + '</div>' +
        '</div>' +
        '<div class="slot-status ready">&#x2705;</div>';
    if (slotId === 'slot2') document.getElementById('player-count').textContent = '(2/2)';
}

function fillSlot1(name) {
    const av = document.getElementById('slot1-avatar');
    const nm = document.getElementById('slot1-name');
    if (av) av.textContent = name.substring(0, 2).toUpperCase();
    if (nm) nm.textContent = name;
}

function resetSlot2() {
    const slot2 = document.getElementById('slot2');
    if (!slot2) return;
    slot2.className = 'player-slot waiting';
    slot2.innerHTML =
        '<div class="slot-avatar ghost">?</div>' +
        '<div class="slot-info"><div class="slot-name">Menunggu pemain 2...</div>' +
        '<div class="slot-role">Pemain 2</div></div>' +
        '<div class="slot-status"><span class="pulse-dot"></span></div>';
    document.getElementById('player-count').textContent = '(1/2)';
    const btn = document.getElementById('start-btn');
    if (btn) {
        btn.disabled      = true;
        btn.innerHTML     = '&#x23F3; Menunggu pemain 2...';
        btn.className     = 'btn w-full';
        btn.style.opacity = '.45';
        btn.style.cursor  = 'not-allowed';
    }
}

function startPoll(role) {
    pollIv = setInterval(async () => {
        try {
            const r = await fetch('api-session.php?action=get_state&code=' + currentCode);
            const s = await r.json();

            // Room didelete host → {cancelled:true}
            if (!s || s.cancelled) {
                clearInterval(pollIv);
                if (role === 'guest') {
                    alert('Room telah dibubarkan oleh host.');
                    window.location.href = 'puzzle-online.php?level=' + GAME_LEVEL;
                }
                return;
            }

            // Host: deteksi guest masuk
            if (role === 'host') {
                const slot2 = document.getElementById('slot2');
                if (s.player2 && slot2 && slot2.classList.contains('waiting')) {
                    setSlot('slot2', s.player2, false);
                    const btn    = document.getElementById('start-btn');
                    btn.disabled = false;
                    btn.innerHTML = '&#x1F680; Mulai Game!';
                    btn.style.opacity     = '1';
                    btn.style.cursor      = 'pointer';
                    btn.className = 'btn btn-success w-full';
                }
                if (!s.player2 && slot2 && slot2.classList.contains('filled')) {
                    resetSlot2();
                }
            }

            // Guest: isi nama host kalau belum
            if (role === 'guest' && s.player1) {
                const av = document.getElementById('slot1-avatar');
                if (av && av.textContent === '--') fillSlot1(s.player1);
            }

            // Cek started
            if (s.started) {
                clearInterval(pollIv);
                window.location.href = PLAY_FILE + '?level=' + GAME_LEVEL + '&mode=2p&room=' + currentCode + '&role=' + role;
            }
        } catch(e) {}
    }, 2000);
}

async function startGame() {
    const btn    = document.getElementById('start-btn');
    btn.disabled = true;
    btn.textContent = '&#x23F3; Memulai...';
    try {
        const res = await fetch('api-session.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action:'start', code: currentCode })
        });
        const d = await res.json();
        if (d.success) {
            clearInterval(pollIv);
            window.location.href = PLAY_FILE + '?level=' + GAME_LEVEL + '&mode=2p&room=' + currentCode + '&role=host';
        } else {
            btn.disabled    = false;
            btn.textContent = '&#x1F680; Mulai Game!';
            alert(d.error || 'Gagal memulai game');
        }
    } catch(e) {
        btn.disabled    = false;
        btn.textContent = '&#x1F680; Mulai Game!';
        alert('Error: ' + e.message);
    }
}


async function cancelLobby() {
    clearInterval(pollIv);
    clearInterval(heartbeatIv);
    heartbeatIv = null;
    if (currentCode) {
        try {
            await fetch('api-session.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action: 'cancel', code: currentCode })
            });
        } catch(e) {}
    }
    currentCode = null; myRole = null;
    document.getElementById('lobby-view').style.display  = 'none';
    document.getElementById('main-view').style.display   = 'block';
    document.getElementById('room-code-input').value     = '';
    document.getElementById('player-count').textContent  = '(1/2)';
    document.getElementById('host-actions').style.display  = 'none';
    document.getElementById('guest-actions').style.display = 'none';
    // Reset slot1
    const av = document.getElementById('slot1-avatar');
    const nm = document.getElementById('slot1-name');
    if (av) av.textContent = '--';
    if (nm) nm.textContent = '-';
    // Reset slot2
    const slot2 = document.getElementById('slot2');
    if (slot2) {
        slot2.className = 'player-slot waiting';
        slot2.innerHTML =
            '<div class="slot-avatar ghost">?</div>' +
            '<div class="slot-info"><div class="slot-name">Menunggu pemain 2...</div>' +
            '<div class="slot-role">Pemain 2</div></div>' +
            '<div class="slot-status"><span class="pulse-dot"></span></div>';
    }
    // Reset start btn
    const btn = document.getElementById('start-btn');
    if (btn) {
        btn.disabled      = true;
        btn.innerHTML     = '&#x23F3; Menunggu pemain 2...';
        btn.className     = 'btn w-full';
        btn.style.opacity = '.45';
        btn.style.cursor  = 'not-allowed';
    }
}
</script>

<script src="../music.js"></script>
</body>
</html>