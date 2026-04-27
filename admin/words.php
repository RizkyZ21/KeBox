<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

$msg = ''; $error = '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'delete' && isset($_GET['id'])) {
    executeQuery("DELETE FROM words WHERE id=:id", [':id'=>(int)$_GET['id']]);
    $msg = 'Kata berhasil dihapus.';
}

if ($action === 'add' && $_SERVER['REQUEST_METHOD']==='POST') {
    $word  = strtoupper(trim($_POST['word'] ?? ''));
    $level = in_array($_POST['level']??'',['easy','medium','hard']) ? $_POST['level'] : '';

    $lenMap = ['easy'=>4,'medium'=>5,'hard'=>6];

    if (!$word || !$level) {
        $error = 'Semua field wajib diisi.';
    } elseif (!preg_match('/^[A-Z]+$/', $word)) {
        $error = 'Kata hanya boleh huruf.';
    } elseif (strlen($word) !== $lenMap[$level]) {
        $error = "Kata untuk level ".ucfirst($level)." harus tepat {$lenMap[$level]} huruf.";
    } else {
        $stmt = executeQuery("SELECT id FROM words WHERE UPPER(word)=:w AND word_level=:l", [':w'=>$word,':l'=>$level]);
        if (fetchOne($stmt)) {
            $error = 'Kata sudah ada di level tersebut.';
        } else {
            executeQuery("INSERT INTO words (word, word_level) VALUES (:w, :l)", [':w'=>$word,':l'=>$level]);
            $msg = "Kata '{$word}' berhasil ditambahkan.";
        }
    }
}

if ($action === 'edit' && $_SERVER['REQUEST_METHOD']==='POST') {
    $id    = (int)($_POST['id'] ?? 0);
    $word  = strtoupper(trim($_POST['word'] ?? ''));
    $level = in_array($_POST['level']??'',['easy','medium','hard']) ? $_POST['level'] : '';
    $lenMap = ['easy'=>4,'medium'=>5,'hard'=>6];

    if (!$word || !$level) {
        $error = 'Semua field wajib diisi.';
    } elseif (strlen($word) !== $lenMap[$level]) {
        $error = "Kata untuk level ".ucfirst($level)." harus {$lenMap[$level]} huruf.";
    } else {
        executeQuery("UPDATE words SET word=:w, word_level=:l WHERE id=:id", [':w'=>$word,':l'=>$level,':id'=>$id]);
        $msg = 'Kata berhasil diupdate.';
    }
}

$editWord = null;
if (($_GET['action'] ?? '') === 'edit' && isset($_GET['id'])) {
    $stmt = executeQuery("SELECT * FROM words WHERE id=:id", [':id'=>(int)$_GET['id']]);
    $editWord = fetchOne($stmt);
}

$filterLevel = $_GET['level'] ?? '';
if ($filterLevel && in_array($filterLevel, ['easy','medium','hard'])) {
    $stmt = executeQuery("SELECT * FROM words WHERE word_level=:l ORDER BY word_level, word", [':l'=>$filterLevel]);
} else {
    $stmt = executeQuery("SELECT * FROM words ORDER BY word_level, word");
}
$words = fetchAll($stmt);

$countStmt = executeQuery("SELECT word_level, COUNT(*) AS c FROM words GROUP BY word_level");
$counts = [];
foreach (fetchAll($countStmt) as $r) $counts[$r['word_level']] = $r['c'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Kata Word Game — KeBox Admin</title>
<link rel="stylesheet" href="../css/style.css">
<style>
#word-input { text-transform:uppercase; letter-spacing:3px; font-family:'Orbitron',monospace; text-align:center; font-size:1.2rem; }
.len-hint { font-size:.8rem; color:var(--text-muted); margin-top:.3rem; }
</style>
</head>
<body>

<nav class="navbar">
    <a href="../index.php" class="navbar-brand">Ke<span>Box</span></a>
    <ul class="navbar-nav">
        <li><span style="color:var(--accent-red);font-size:.8rem;font-weight:700;letter-spacing:1px">⚡ ADMIN</span></li>
        <li><a href="../logout.php" class="btn btn-outline btn-sm">Logout</a></li>
    </ul>
</nav>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-title">Menu Admin</div>
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="users.php">👥 Kelola User</a>
        <div class="sidebar-title">Game</div>
        <a href="words.php" class="active">💬 Kata Word Game</a>
        <a href="levels.php">🧩 Level Puzzle</a>
        <div class="sidebar-title">Lainnya</div>
        <a href="../index.php">🌐 Lihat Website</a>
        <a href="../logout.php">🚪 Logout</a>
    </aside>

    <main class="main-content">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
            <h2 style="font-family:'Orbitron',monospace;font-size:1.3rem">💬 Kata Word Game</h2>
            <button class="btn btn-primary btn-sm" onclick="toggleForm('add-form')">+ Tambah Kata</button>
        </div>

        <!-- STATS -->
        <div class="grid grid-3 mb-3">
            <div class="card text-center" style="padding:1rem;background:rgba(0,230,118,.06);border-color:rgba(0,230,118,.2)">
                <div style="font-family:'Orbitron',monospace;font-size:1.8rem;color:var(--accent-green)"><?= $counts['easy'] ?? 0 ?></div>
                <div style="color:var(--text-dim);font-size:.8rem">Easy (4 huruf)</div>
            </div>
            <div class="card text-center" style="padding:1rem;background:rgba(255,215,64,.06);border-color:rgba(255,215,64,.2)">
                <div style="font-family:'Orbitron',monospace;font-size:1.8rem;color:var(--accent-gold)"><?= $counts['medium'] ?? 0 ?></div>
                <div style="color:var(--text-dim);font-size:.8rem">Medium (5 huruf)</div>
            </div>
            <div class="card text-center" style="padding:1rem;background:rgba(255,23,68,.06);border-color:rgba(255,23,68,.2)">
                <div style="font-family:'Orbitron',monospace;font-size:1.8rem;color:var(--accent-red)"><?= $counts['hard'] ?? 0 ?></div>
                <div style="color:var(--text-dim);font-size:.8rem">Hard (6 huruf)</div>
            </div>
        </div>

        <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <!-- ADD FORM -->
        <div class="card mb-3 <?= ($error && $action==='add') ? '' : 'hidden' ?>" id="add-form">
            <div style="font-family:'Orbitron',monospace;font-size:.7rem;letter-spacing:2px;color:var(--text-muted);margin-bottom:1rem">TAMBAH KATA BARU</div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Kata</label>
                        <input type="text" id="word-input" name="word" class="form-control" value="<?= htmlspecialchars($_POST['word'] ?? '') ?>" placeholder="KATA" maxlength="6" required>
                        <div class="len-hint" id="len-hint">Masukkan kata sesuai level</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Level</label>
                        <select name="level" id="level-sel" class="form-control" onchange="updateHint()">
                            <option value="">-- Pilih Level --</option>
                            <option value="easy" <?= ($_POST['level']??'')==='easy'?'selected':'' ?>>Easy (4 huruf)</option>
                            <option value="medium" <?= ($_POST['level']??'')==='medium'?'selected':'' ?>>Medium (5 huruf)</option>
                            <option value="hard" <?= ($_POST['level']??'')==='hard'?'selected':'' ?>>Hard (6 huruf)</option>
                        </select>
                    </div>
                </div>
                <div style="display:flex;gap:1rem">
                    <button type="submit" class="btn btn-primary">Simpan Kata</button>
                    <button type="button" class="btn btn-outline" onclick="toggleForm('add-form')">Batal</button>
                </div>
            </form>
        </div>

        <!-- EDIT FORM -->
        <?php if ($editWord): ?>
        <div class="card mb-3">
            <div style="font-family:'Orbitron',monospace;font-size:.7rem;letter-spacing:2px;color:var(--text-muted);margin-bottom:1rem">EDIT KATA</div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $editWord['id'] ?>">
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Kata</label>
                        <input type="text" name="word" class="form-control" style="text-transform:uppercase;letter-spacing:3px;font-family:'Orbitron',monospace;text-align:center;font-size:1.2rem" value="<?= htmlspecialchars(strtoupper($editWord['word'])) ?>" maxlength="6" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Level</label>
                        <select name="level" class="form-control">
                            <option value="easy"   <?= $editWord['level']==='easy'?'selected':'' ?>>Easy (4 huruf)</option>
                            <option value="medium" <?= $editWord['level']==='medium'?'selected':'' ?>>Medium (5 huruf)</option>
                            <option value="hard"   <?= $editWord['level']==='hard'?'selected':'' ?>>Hard (6 huruf)</option>
                        </select>
                    </div>
                </div>
                <div style="display:flex;gap:1rem">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="words.php" class="btn btn-outline">Batal</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- FILTER -->
        <div style="display:flex;gap:.5rem;margin-bottom:1rem;flex-wrap:wrap">
            <a href="words.php" class="btn <?= !$filterLevel?'btn-primary':'btn-outline' ?> btn-sm">Semua</a>
            <a href="words.php?level=easy"   class="btn <?= $filterLevel==='easy'?'btn-primary':'btn-outline' ?> btn-sm">🟢 Easy</a>
            <a href="words.php?level=medium" class="btn <?= $filterLevel==='medium'?'btn-primary':'btn-outline' ?> btn-sm">🟡 Medium</a>
            <a href="words.php?level=hard"   class="btn <?= $filterLevel==='hard'?'btn-primary':'btn-outline' ?> btn-sm">🔴 Hard</a>
        </div>

        <div class="table-wrapper">
            <table>
                <thead><tr><th>#</th><th>Kata</th><th>Level</th><th>Panjang</th><th>Ditambahkan</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($words as $w): ?>
                <tr>
                    <td style="color:var(--text-muted)"><?= (int)$w['id'] ?></td>
                    <td style="font-family:'Orbitron',monospace;font-size:1rem;letter-spacing:3px;color:var(--purple-bright)"><?= htmlspecialchars(strtoupper($w['word'])) ?></td>
                    <td><span class="badge badge-<?= $w['word_level'] ?>"><?= strtoupper($w['word_level']) ?></span></td>
                    <td style="color:var(--text-dim)"><?= strlen($w['word']) ?> huruf</td>
                    <td style="color:var(--text-dim);font-size:.85rem"><?= date('d/m/Y', strtotime($w['created_at'])) ?></td>
                    <td>
                        <div style="display:flex;gap:.5rem">
                            <a href="words.php?action=edit&id=<?= $w['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
                            <a href="words.php?action=delete&id=<?= $w['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus kata ini?')">🗑️</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($words)): ?>
                <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem">Belum ada kata</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
function toggleForm(id) { document.getElementById(id).classList.toggle('hidden'); }
function updateHint() {
    const map = { easy:'4 huruf', medium:'5 huruf', hard:'6 huruf' };
    const sel = document.getElementById('level-sel').value;
    document.getElementById('len-hint').textContent = map[sel] ? `Harus tepat ${map[sel]}` : 'Masukkan kata sesuai level';
}
document.getElementById('word-input').addEventListener('input', function() {
    this.value = this.value.toUpperCase().replace(/[^A-Z]/g,'');
});
</script>
</body>
</html>
