<?php
session_start();
require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? '') === 'admin') {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../pages/home.php");
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'] ?? ($user['nama_lengkap'] ?? ($user['username'] ?? 'Pelanggan'));
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'] ?? 'user';

            if ($_SESSION['role'] === 'admin') {
                header("Location: ../admin/dashboard.php");
            } else {
                header("Location: ../pages/home.php");
            }
            exit;
        } else {
            $error = 'Email atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Account | Kelak'luk Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=6">
</head>

<body class="auth-page-body">

    <div class="figma-auth-shell">
        <div class="figma-auth-top">
            <h1>Login<br>Account</h1>
        </div>

        <div class="figma-auth-sheet">
            <img src="../assets/images/logo.png" alt="Kelak'luk Coffee" class="figma-auth-logo">

            <?php if (isset($_GET['registered'])): ?>
                <div class="alert success">Pendaftaran berhasil! Silakan login ke akunmu.</div>
            <?php endif; ?>

            <?php if (isset($_GET['reset'])): ?>
                <div class="alert success">Kata sandi berhasil diperbarui! Silakan login dengan sandi barumu.</div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="figma-input-group">
                    <label for="email">Email / No Telp</label>
                    <input type="email" id="email" name="email" placeholder="Masukkan email kamu" required>
                </div>

                <div class="figma-input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                </div>

                <button type="submit" class="figma-auth-btn" id="loginBtn">LOGIN</button>
            </form>

            <div class="figma-auth-links">
                <a href="forgot_password.php">Forgot Password?</a>
            </div>

            <div class="figma-auth-links" style="margin-top: 10px;">
                Belum punya akun? <a href="register.php">Register Account</a>
            </div>

            <div class="figma-auth-divider">Sign up with</div>

            <div class="figma-social-row">
                <a href="register.php" class="figma-social-btn google">
                    <span>G</span> Google
                </a>
                <a href="register.php" class="figma-social-btn facebook">
                    <span>f</span> Facebook
                </a>
            </div>
        </div>
    </div>

    <!-- OVERLAY ANIMASI SAAT KLIK LOGIN -->
    <div class="coffee-splash-overlay" id="coffeeSplash">
        <div class="splash-logo-card">
            <img src="../assets/images/logo.png" alt="Kelak'luk Coffee">
        </div>
        <div class="splash-spinner"></div>
        <div class="splash-text">Menyiapkan Kelak'luk Coffee...</div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const form = this;
            const splash = document.getElementById('coffeeSplash');
            splash.classList.add('show');

            setTimeout(function () {
                form.submit();
            }, 850);
        });
    </script>

</body>

</html>