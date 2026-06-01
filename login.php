<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin/dashboard.php' : 'dashboard.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = executeQuery(
            "SELECT id, username, password, user_role FROM users WHERE username = :u OR email = :u",
            [':u' => $username]
        );
        $user = fetchOne($stmt);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['user_role'];
            header('Location: ' . ($user['user_role']==='admin' ? 'admin/dashboard.php' : 'dashboard.php'));
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    } else {
        $error = 'Semua field wajib diisi.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — KeBox</title>
<link rel="stylesheet" href="css/style.css">
<style>
body { display:flex; flex-direction:column; min-height:100vh; }
.login-wrap {
    flex:1; display:flex; align-items:center; justify-content:center; padding:2rem;
}
.login-box {
    width:100%; max-width:420px;
    background:var(--bg-card);
    border:1px solid var(--border);
    border-radius:20px;
    padding:2.5rem 2rem;
    box-shadow:0 30px 80px rgba(0,0,0,.5), 0 0 60px rgba(124,77,255,.1);
}
.login-logo {
    text-align:center;
    font-family:'Orbitron',monospace;
    font-size:2rem; font-weight:900;
    color:var(--purple-main);
    margin-bottom:0.3rem;
    text-shadow:0 0 20px rgba(124,77,255,.5);
}
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
        <div class="login-sub">Masuk ke akunmu</div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Username / Email</label>
                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="masukkan username atau email" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="masukkan password" required>
            </div>
            <button type="submit" class="btn btn-primary w-full mt-2">🔑 Login</button>
        </form>

        <p class="text-center mt-3" style="color:var(--text-dim);font-size:.9rem">
            Belum punya akun? <a href="register.php" style="color:var(--purple-bright)">Daftar sekarang</a>
        </p>
    </div>
</div>

<script src="music.js"></script>
</body>
</html>
