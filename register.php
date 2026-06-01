<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (!$username || !$email || !$password) {
        $error = 'Semua field wajib diisi.';
    } elseif ($password !== $confirm) {
        $error = 'Password dan konfirmasi tidak cocok.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        // Check duplicate
        $stmt = executeQuery("SELECT id FROM users WHERE username=:u OR email=:e", [':u'=>$username,':e'=>$email]);
        if (fetchOne($stmt)) {
            $error = 'Username atau email sudah digunakan.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            executeQuery(
                "INSERT INTO users (username, email, password, user_role) VALUES (:u, :e, :p, 'user')",
                [':u'=>$username, ':e'=>$email, ':p'=>$hash]
            );
            $success = 'Akun berhasil dibuat! <a href="login.php" style="color:var(--purple-bright)">Login sekarang</a>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Register — KeBox</title>
<link rel="stylesheet" href="css/style.css">
<style>
body { display:flex; flex-direction:column; min-height:100vh; }
.login-wrap { flex:1; display:flex; align-items:center; justify-content:center; padding:2rem; }
.login-box { width:100%; max-width:420px; background:var(--bg-card); border:1px solid var(--border); border-radius:20px; padding:2.5rem 2rem; box-shadow:0 30px 80px rgba(0,0,0,.5); }
.login-logo { text-align:center; font-family:'Orbitron',monospace; font-size:2rem; font-weight:900; color:var(--purple-main); margin-bottom:0.3rem; }
.login-logo span { color:var(--accent-cyan); }
.login-sub { text-align:center; color:var(--text-dim); font-size:.9rem; margin-bottom:2rem; }
</style>
</head>
<body>
<?php
$navActive = '';
$navBase   = '';
require_once 'includes/navbar.php';
?>

<div class="login-wrap">
    <div class="login-box">
        <div class="login-logo">Ke<span>Box</span></div>
        <div class="login-sub">Buat akun baru</div>

        <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="username unik kamu" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="email@contoh.com" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="minimal 6 karakter" required>
            </div>
            <div class="form-group">
                <label class="form-label">Konfirmasi Password</label>
                <input type="password" name="confirm" class="form-control" placeholder="ulangi password" required>
            </div>
            <button type="submit" class="btn btn-primary w-full mt-2">🚀 Daftar</button>
        </form>
        <?php endif; ?>

        <p class="text-center mt-3" style="color:var(--text-dim);font-size:.9rem">
            Sudah punya akun? <a href="login.php" style="color:var(--purple-bright)">Login</a>
        </p>
    </div>
</div>

<script src="music.js"></script>
</body>
</html>
