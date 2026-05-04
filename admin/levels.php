<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

$msg = '';
$error = '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ================= DELETE =================
if ($action === 'delete' && isset($_GET['id'])) {
    executeQuery("DELETE FROM puzzle_levels WHERE id=:id", [
        'id' => (int)$_GET['id']
    ]);
    $msg = 'Level berhasil dihapus.';
}

// ================= ADD =================
if ($action === 'add' && $_SERVER['REQUEST_METHOD']==='POST') {
    $levelNum = (int)($_POST['level_num'] ?? 0);
    $gridSize = (int)($_POST['grid_size'] ?? 0);
    $label    = trim($_POST['label'] ?? "");

    if ($levelNum < 1 || $levelNum > 50) {
        $error = 'Nomor level harus antara 1-50.';
    } elseif ($gridSize < 2 || $gridSize > 10) {
        $error = 'Ukuran grid harus antara 2x2 sampai 10x10.';
    } else {
        $stmt = executeQuery("SELECT id FROM puzzle_levels WHERE level_num=:n", [
            'n' => $levelNum
        ]);

        if (fetchOne($stmt)) {
            $error = "Level nomor {$levelNum} sudah ada.";
        } else {
            if (!$label) {
                $label = "Level {$levelNum} ({$gridSize}x{$gridSize})";
            }

            executeQuery(
                "INSERT INTO puzzle_levels (level_num, grid_size, label) VALUES (:n, :g, :l)",
                [
                    'n' => $levelNum,
                    'g' => $gridSize,
                    'l' => $label
                ]
            );

            $msg = "Level berhasil ditambahkan!";
        }
    }
}

// ================= EDIT =================
if ($action === 'edit' && $_SERVER['REQUEST_METHOD']==='POST') {
    $id       = (int)($_POST['id'] ?? 0);
    $levelNum = (int)($_POST['level_num'] ?? 0);
    $gridSize = (int)($_POST['grid_size'] ?? 0);
    $label    = trim($_POST['label'] ?? '');

    if ($gridSize < 2 || $gridSize > 10) {
        $error = 'Ukuran grid harus antara 2-10.';
    } else {
        if (!$label) {
            $label = "Level {$levelNum} ({$gridSize}x{$gridSize})";
        }

        executeQuery(
            "UPDATE puzzle_levels SET level_num=:n, grid_size=:g, label=:l WHERE id=:id",
            [
                'n' => $levelNum,
                'g' => $gridSize,
                'l' => $label,
                'id' => $id
            ]
        );

        $msg = 'Level berhasil diupdate.';
    }
}

// ================= GET EDIT =================
$editLevel = null;
if (($_GET['action'] ?? '') === 'edit' && isset($_GET['id'])) {
    $stmt = executeQuery("SELECT * FROM puzzle_levels WHERE id=:id", [
        'id' => (int)$_GET['id']
    ]);
    $editLevel = fetchOne($stmt);
}

// ================= GET ALL =================
$stmt   = executeQuery("SELECT * FROM puzzle_levels ORDER BY level_num");
$levels = fetchAll($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Level Puzzle — KeBox Admin</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.grid-preview {
    display:inline-grid;
    gap:2px;
    padding:6px;
    background:rgba(124,77,255,.1);
    border-radius:6px;
    border:1px solid rgba(124,77,255,.2);
}
.grid-cell {
    width:8px; height:8px;
    background:var(--purple-mid);
    border-radius:2px;
}
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
        <a href="words.php">💬 Kata Word Game</a>
        <a href="levels.php" class="active">🧩 Level Puzzle</a>
        <div class="sidebar-title">Lainnya</div>
        <a href="../index.php">🌐 Lihat Website</a>
        <a href="../logout.php">🚪 Logout</a>
    </aside>

    <main class="main-content">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
            <h2 style="font-family:'Orbitron',monospace;font-size:1.3rem">🧩 Level Puzzle</h2>
            <button class="btn btn-primary btn-sm" onclick="toggleForm('add-form')">+ Tambah Level</button>
        </div>

        <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <!-- ADD FORM -->
        <div class="card mb-3 <?= ($error && $action==='add') ? '' : 'hidden' ?>" id="add-form">
            <div style="font-family:'Orbitron',monospace;font-size:.7rem;letter-spacing:2px;color:var(--text-muted);margin-bottom:1rem">TAMBAH LEVEL BARU</div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="grid grid-3">
                    <div class="form-group">
                        <label class="form-label">Nomor Level</label>
                        <input type="number" name="level_num" class="form-control" value="<?= htmlspecialchars($_POST['level_num'] ?? '') ?>" min="1" max="50" placeholder="misal: 11" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ukuran Grid</label>
                        <select name="grid_size" class="form-control" onchange="updatePreview(this.value)">
                            <option value="">-- Pilih Grid --</option>
                            <?php for ($g=2; $g<=10; $g++): ?>
                            <option value="<?= $g ?>" <?= ($_POST['grid_size']??'')==$g?'selected':'' ?>><?= $g ?>×<?= $g ?> (<?= $g*$g-1 ?> tile)</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Label (opsional)</label>
                        <input type="text" name="label" class="form-control" value="<?= htmlspecialchars($_POST['label'] ?? '') ?>" placeholder="otomatis jika kosong">
                    </div>
                </div>

                <!-- GRID PREVIEW -->
                <div style="margin-bottom:1rem">
                    <div style="font-size:.8rem;color:var(--text-muted);margin-bottom:.5rem">Preview Grid:</div>
                    <div id="grid-preview" style="display:flex;align-items:center;gap:1rem">
                        <div style="color:var(--text-muted);font-size:.85rem">Pilih ukuran grid untuk melihat preview</div>
                    </div>
                </div>

                <div style="display:flex;gap:1rem">
                    <button type="submit" class="btn btn-primary">Simpan Level</button>
                    <button type="button" class="btn btn-outline" onclick="toggleForm('add-form')">Batal</button>
                </div>
            </form>
        </div>

        <!-- EDIT FORM -->
        <?php if ($editLevel): ?>
        <div class="card mb-3">
            <div style="font-family:'Orbitron',monospace;font-size:.7rem;letter-spacing:2px;color:var(--text-muted);margin-bottom:1rem">EDIT LEVEL <?= $editLevel['level_num'] ?></div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $editLevel['id'] ?>">
                <div class="grid grid-3">
                    <div class="form-group">
                        <label class="form-label">Nomor Level</label>
                        <input type="number" name="level_num" class="form-control" value="<?= $editLevel['level_num'] ?>" min="1" max="50" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ukuran Grid</label>
                        <select name="grid_size" class="form-control">
                            <?php for ($g=2; $g<=10; $g++): ?>
                            <option value="<?= $g ?>" <?= (int)$editLevel['grid_size']===$g?'selected':'' ?>><?= $g ?>×<?= $g ?> (<?= $g*$g-1 ?> tile)</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Label</label>
                        <input type="text" name="label" class="form-control" <?php 
                        $editLabel = $editLevel['label'] ?? "Level {$editLevel['level_num']} ({$editLevel['grid_size']}x{$editLevel['grid_size']})";
                        ?>
                        value="<?= htmlspecialchars($editLabel) ?>">
                    </div>
                </div>
                <div style="display:flex;gap:1rem">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="levels.php" class="btn btn-outline">Batal</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- TABLE -->
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>#</th><th>Label</th><th>Grid</th><th>Jumlah Tile</th><th>Preview</th><th>Kesulitan</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                <?php foreach ($levels as $lv):
                    $n    = (int)$lv['level_num'];
                    $g    = (int)$lv['grid_size'];
                    $diff = $n <= 3 ? 'easy' : ($n <= 7 ? 'medium' : 'hard');
                ?>
                <tr>
                    <td style="color:var(--text-muted)"><?= (int)$lv['id'] ?></td>
                    <?php
                    $label = $lv['label'] ?? "Level {$lv['level_num']} ({$lv['grid_size']}x{$lv['grid_size']})";
                    ?>
                    <td><strong><?= htmlspecialchars($label) ?></strong></td>
                    <td style="font-family:'Orbitron',monospace;color:var(--purple-bright)"><?= $g ?>×<?= $g ?></td>
                    <td style="color:var(--text-dim)"><?= $g*$g-1 ?> tile</td>
                    <td>
                        <div class="grid-preview" style="grid-template-columns:repeat(<?= $g ?>,8px)">
                            <?php for ($i=0; $i<$g*$g; $i++): ?>
                                <div class="grid-cell" style="<?= $i===$g*$g-1?'opacity:.2':'' ?>"></div>
                            <?php endfor; ?>
                        </div>
                    </td>
                    <td><span class="badge badge-<?= $diff ?>"><?= strtoupper($diff) ?></span></td>
                    <td>
                        <div style="display:flex;gap:.5rem">
                            <a href="levels.php?action=edit&id=<?= $lv['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
                            <a href="levels.php?action=delete&id=<?= $lv['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus Level <?= $n ?>?')">🗑️</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($levels)): ?>
                <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:2rem">Belum ada level</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
function toggleForm(id) { document.getElementById(id).classList.toggle('hidden'); }

function updatePreview(size) {
    const container = document.getElementById('grid-preview');
    const g = parseInt(size);
    if (!g) { container.innerHTML = '<div style="color:var(--text-muted);font-size:.85rem">Pilih ukuran grid</div>'; return; }

    let html = `<div style="display:grid;grid-template-columns:repeat(${g},14px);gap:2px;padding:8px;background:rgba(124,77,255,.1);border-radius:8px;border:1px solid rgba(124,77,255,.2)">`;
    for (let i = 0; i < g*g; i++) {
        const isEmpty = i === g*g-1;
        html += `<div style="width:14px;height:14px;border-radius:3px;background:${isEmpty ? 'rgba(124,77,255,.1)' : 'var(--purple-mid)'}"></div>`;
    }
    html += '</div>';
    html += `<div style="color:var(--text-dim);font-size:.85rem">${g}×${g} grid · ${g*g-1} tile · ${g*g} sel</div>`;
    container.innerHTML = html;
}
</script>
</body>
</html>
