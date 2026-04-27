<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

$msg = ''; $error = '';

// Handle actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    executeQuery("DELETE FROM users WHERE id=:id AND user_role!='admin'", [':id'=>$id]);
    $msg = 'User berhasil dihapus.';
}

if ($action === 'add' && $_SERVER['REQUEST_METHOD']==='POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = in_array($_POST['user_role']??'',['user','admin']) ? $_POST['user_role'] : 'user';

    if (!$username || !$email || !$password) {
        $error = 'Semua field wajib diisi.';
    } else {
        $stmt = executeQuery("SELECT id FROM users WHERE username=:u OR email=:e", [':u'=>$username,':e'=>$email]);
        if (fetchOne($stmt)) {
            $error = 'Username atau email sudah digunakan.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            executeQuery(
                "INSERT INTO users (username, email, password, user_role) VALUES (:u,:e,:p,:r)",
                [':u'=>$username,':e'=>$email,':p'=>$hash,':r'=>$role]
            );
            $msg = 'User berhasil ditambahkan.';
        }
    }
}

if ($action === 'edit' && $_SERVER['REQUEST_METHOD']==='POST') {
    $id       = (int)($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role     = in_array($_POST['user_role']??'',['user','admin']) ? $_POST['user_role'] : 'user';
    $password = $_POST['password'] ?? '';

    if (!$username || !$email) {
        $error = 'Username dan email wajib diisi.';
    } else {
        if ($password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            executeQuery("UPDATE users SET username=:u, email=:e, user_role=:r, password=:p WHERE id=:id",
                [':u'=>$username,':e'=>$email,':r'=>$role,':p'=>$hash,':id'=>$id]);
        } else {
            executeQuery("UPDATE users SET username=:u, email=:e, user_role=:r WHERE id=:id",
                [':u'=>$username,':e'=>$email,':r'=>$role,':id'=>$id]);
        }
        $msg = 'User berhasil diupdate.';
    }
}

// Fetch for edit
$editUser = null;
if ($_GET['action'] ?? '' === 'edit' && isset($_GET['id'])) {
    $stmt = executeQuery("SELECT id, username, email, password, user_role, created_at FROM users WHERE id=:id", [':id'=>(int)$_GET['id']]);
    $editUser = fetchOne($stmt);
}

// List users
$search = trim($_GET['q'] ?? '');
if ($search) {
    $stmt = executeQuery("SELECT id, username, email, password, user_role, created_at FROM users WHERE LOWER(username) LIKE :q OR LOWER(email) LIKE :q ORDER BY id",
        [':q' => '%' . strtolower($search) . '%']);
} else {
    $stmt = executeQuery("SELECT id, username, email, password, user_role, created_at FROM users ORDER BY id");
}
$users = fetchAll($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Kelola User — KeBox Admin</title>
<link rel="stylesheet" href="../css/style.css">
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
        <a href="users.php" class="active">👥 Kelola User</a>
        <div class="sidebar-title">Game</div>
        <a href="words.php">💬 Kata Word Game</a>
        <a href="levels.php">🧩 Level Puzzle</a>
        <div class="sidebar-title">Lainnya</div>
        <a href="../index.php">🌐 Lihat Website</a>
        <a href="../logout.php">🚪 Logout</a>
    </aside>

    <main class="main-content">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
            <h2 style="font-family:'Orbitron',monospace;font-size:1.3rem">👥 Kelola User</h2>
            <button class="btn btn-primary btn-sm" onclick="toggleForm('add-form')">+ Tambah User</button>
        </div>

        <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <!-- ADD FORM -->
        <div class="card mb-3 <?= ($error && $action==='add') || isset($_POST['action']) && $_POST['action']==='add' ? '' : 'hidden' ?>" id="add-form">
            <div style="font-family:'Orbitron',monospace;font-size:.7rem;letter-spacing:2px;color:var(--text-muted);margin-bottom:1rem">TAMBAH USER BARU</div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="user_role" class="form-control">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div style="display:flex;gap:1rem">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <button type="button" class="btn btn-outline" onclick="toggleForm('add-form')">Batal</button>
                </div>
            </form>
        </div>

        <!-- EDIT FORM -->
        <?php if ($editUser): ?>
        <div class="card mb-3" id="edit-form">
            <div style="font-family:'Orbitron',monospace;font-size:.7rem;letter-spacing:2px;color:var(--text-muted);margin-bottom:1rem">EDIT USER</div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $editUser['id'] ?>">
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($editUser['username']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($editUser['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password Baru (kosongkan jika tidak diubah)</label>
                        <input type="password" name="password" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="user_role" class="form-control">
                            <option value="user" <?= $editUser['user_role']==='user'?'selected':'' ?>>User</option>
                            <option value="admin" <?= $editUser['user_role']==='admin'?'selected':'' ?>>Admin</option>
                        </select>
                    </div>
                </div>
                <div style="display:flex;gap:1rem">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="users.php" class="btn btn-outline">Batal</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- SEARCH -->
        <div class="card mb-2" style="padding:1rem">
            <form method="GET" style="display:flex;gap:1rem">
                <input type="text" name="q" class="form-control" placeholder="Cari username atau email..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary btn-sm">Cari</button>
                <?php if ($search): ?><a href="users.php" class="btn btn-outline btn-sm">Reset</a><?php endif; ?>
            </form>
        </div>

        <!-- TABLE -->
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>#</th><th>Username</th><th>Email</th><th>Role</th><th>Bergabung</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td style="color:var(--text-muted)"><?= (int)$u['id'] ?></td>
                    <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                    <td style="color:var(--text-dim)"><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="badge badge-<?= $u['user_role'] ?>"><?= strtoupper($u['user_role']) ?></span></td>
                    <td style="color:var(--text-dim);font-size:.85rem"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <div style="display:flex;gap:.5rem">
                            <a href="users.php?action=edit&id=<?= $u['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
                            <?php if ($u['user_role'] !== 'admin'): ?>
                            <a href="users.php?action=delete&id=<?= $u['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus user <?= htmlspecialchars($u['username']) ?>?')">🗑️</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem">Tidak ada user ditemukan</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
function toggleForm(id) {
    document.getElementById(id).classList.toggle('hidden');
}
</script>
</body>
</html>
