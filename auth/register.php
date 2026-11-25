<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ../pages/home.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($nama === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $error = 'Semua kolom wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        try {
            $stmtCheck = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmtCheck->execute([$email]);

            if ($stmtCheck->fetch()) {
                $error = 'Email sudah terdaftar. Silakan login.';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);

                // Deteksi nama kolom nama di tabel users
                $userCols = $conn->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
                $nameCol = in_array('nama', $userCols) ? 'nama' : (in_array('nama_lengkap', $userCols) ? 'nama_lengkap' : 'username');

                // Insert tanpa kolom role agar otomatis memakai nilai DEFAULT di database
                $stmtInsert = $conn->prepare("INSERT INTO users ($nameCol, email, password) VALUES (?, ?, ?)");
                $stmtInsert->execute([$nama, $email, $hashed]);

                header("Location: login.php?registered=1");
                exit;
            }
        } catch (Exception $ex) {
            $error = 'Gagal mendaftar: ' . $ex->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Account | Kelak'luk Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=3">
</head>

<body class="auth-page-body">

    <div class="figma-auth-shell">
        <div class="figma-auth-top">
            <h1>Register<br>Account</h1>
        </div>

        <div class="figma-auth-sheet">
            <img src="../assets/images/logo.png" alt="Kelak'luk Coffee" class="figma-auth-logo">

            <?php if ($error): ?>
                <div class="alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="figma-input-group">
                    <label for="nama">Nama Lengkap</label>
                    <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($_POST['nama'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan nama lengkap" required>
                </div>

                <div class="figma-input-group">
                    <label for="email">Email / No Telp</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan email kamu" required>
                </div>

                <div class="figma-input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required>
                </div>

                <div class="figma-input-group">
                    <label for="confirm_password">Konfirmasi Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Ulangi password" required>
                </div>

                <button type="submit" class="figma-auth-btn">REGISTER</button>
            </form>

            <div class="figma-auth-links">
                Sudah punya akun? <a href="login.php">Login Account</a>
            </div>

            <div class="figma-auth-divider">Sign up with</div>

            <div class="figma-social-row">
                <a href="login.php" class="figma-social-btn google">
                    <span>G</span> Google
                </a>
                <a href="login.php" class="figma-social-btn facebook">
                    <span>f</span> Facebook
                </a>
            </div>
        </div>
    </div>

    <!-- OVERLAY ANIMASI SAAT KLIK REGISTER -->
<div class="coffee-splash-overlay" id="coffeeSplash">
    <div class="splash-logo-card">
        <img src="../assets/images/logo.png" alt="Kelak'luk Coffee">
    </div>
    <div class="splash-spinner"></div>
    <div class="splash-text">Mendaftarkan akunmu...</div>
</div>

<script>
    const regForm = document.querySelector('.figma-auth-sheet form');
    if (regForm) {
        regForm.addEventListener('submit', function (e) {
            e.preventDefault();
            document.getElementById('coffeeSplash').classList.add('show');
            setTimeout(() => regForm.submit(), 750);
        });
    }
</script>

</body>

</html>