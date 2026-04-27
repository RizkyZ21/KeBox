<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();
$level = in_array($_GET['level'] ?? '', ['easy','medium','hard']) ? $_GET['level'] : 'easy';
$user  = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Online Lobby — KeBox</title>
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

<div class="container-sm" style="padding-top:3rem;padding-bottom:3rem">
    <div class="page-header">
        <h1>👥 Mode Online</h1>
        <p>Word Game · <span class="badge badge-<?= $level ?>"><?= strtoupper($level) ?></span></p>
    </div>

    <div class="card mb-3">
        <div style="text-align:center;font-family:'Orbitron',monospace;font-size:.7rem;letter-spacing:2px;color:var(--text-muted);margin-bottom:1rem">BUAT ROOM BARU</div>
        <p style="color:var(--text-dim);font-size:.9rem;text-align:center;margin-bottom:1.2rem">Buat room dan bagikan kode ke temanmu</p>
        <button class="btn btn-primary w-full" onclick="createRoom()">🎮 Buat Room</button>
    </div>

    <div class="card">
        <div style="text-align:center;font-family:'Orbitron',monospace;font-size:.7rem;letter-spacing:2px;color:var(--text-muted);margin-bottom:1rem">GABUNG ROOM</div>
        <div class="form-group">
            <label class="form-label">Kode Room (6 digit)</label>
            <input type="text" id="room-code-input" class="form-control" placeholder="contoh: ABC123" maxlength="6" style="text-transform:uppercase;letter-spacing:4px;text-align:center;font-family:'Orbitron',monospace;font-size:1.2rem">
        </div>
        <button class="btn btn-outline w-full" onclick="joinRoom()">🔗 Gabung Room</button>
    </div>
</div>

<!-- WAITING MODAL -->
<div class="modal-overlay" id="waiting-modal">
    <div class="modal-box">
        <div style="font-size:2.5rem;margin-bottom:1rem">⏳</div>
        <h2>Menunggu Lawan...</h2>
        <p>Bagikan kode ini ke temanmu:</p>
        <div class="lobby-code" id="display-code">------</div>
        <div style="color:var(--text-dim);font-size:.85rem;margin-bottom:1.5rem">Kode berlaku 10 menit</div>
        <button class="btn btn-outline" onclick="cancelRoom()">❌ Batalkan</button>
    </div>
</div>

<script>
const LEVEL = '<?= $level ?>';
const USERNAME = '<?= htmlspecialchars($user['username']) ?>';
let currentRoomId = null;
let pollInterval = null;

function generateCode() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let code = '';
    for (let i = 0; i < 6; i++) code += chars[Math.floor(Math.random() * chars.length)];
    return code;
}

async function createRoom() {
    const code = generateCode();
    currentRoomId = code;

    // Save session to backend
    await fetch('api-session.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action:'create', code, level: LEVEL, username: USERNAME })
    });

    document.getElementById('display-code').textContent = code;
    document.getElementById('waiting-modal').classList.add('show');

    // Poll for player 2
    pollInterval = setInterval(async () => {
        const res = await fetch(`api-session.php?action=check&code=${code}`);
        const data = await res.json();
        if (data.player2) {
            clearInterval(pollInterval);
            document.getElementById('waiting-modal').classList.remove('show');
            window.location.href = `word-play.php?level=${LEVEL}&mode=2p&room=${code}&role=host`;
        }
    }, 2000);
}

async function joinRoom() {
    const code = document.getElementById('room-code-input').value.toUpperCase().trim();
    if (code.length !== 6) { alert('Kode room harus 6 karakter!'); return; }

    const res = await fetch(`api-session.php?action=join&code=${code}&username=${USERNAME}`, { method:'POST' });
    const data = await res.json();
    if (data.success) {
        window.location.href = `word-play.php?level=${LEVEL}&mode=2p&room=${code}&role=guest`;
    } else {
        alert(data.error || 'Room tidak ditemukan atau sudah penuh.');
    }
}

function cancelRoom() {
    clearInterval(pollInterval);
    document.getElementById('waiting-modal').classList.remove('show');
}
</script>
</body>
</html>
