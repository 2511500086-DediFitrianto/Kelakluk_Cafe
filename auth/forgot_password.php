<?php
session_start();
require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ../pages/home.php");
    exit;
}

$error = '';
$verifiedUser = null;
$step = 'verify'; // Tahap 1: Cek email | Tahap 2: Buat password baru

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'check_email';

    if ($action === 'check_email') {
        $email = trim($_POST['email'] ?? '');

        if ($email === '') {
            $error = 'Silakan masukkan alamat email akunmu.';
        } else {
            $stmt = $conn->prepare("SELECT id, email FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $_SESSION['reset_email'] = $user['email'];
                $step = 'reset';
            } else {
                $error = 'Email tidak ditemukan. Pastikan email sudah terdaftar.';
            }
        }
    } elseif ($action === 'update_password') {
        $resetEmail = $_SESSION['reset_email'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($resetEmail === '') {
            $step = 'verify';
            $error = 'Sesi reset sandi habis. Silakan masukkan email kembali.';
        } elseif (strlen($newPassword) < 6) {
            $step = 'reset';
            $error = 'Kata sandi baru minimal 6 karakter.';
        } elseif ($newPassword !== $confirmPassword) {
            $step = 'reset';
            $error = 'Konfirmasi kata sandi tidak cocok.';
        } else {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmtUpdate = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmtUpdate->execute([$hashed, $resetEmail]);

            unset($_SESSION['reset_email']);
            header("Location: login.php?reset=1");
            exit;
        }
    }
} elseif (!empty($_SESSION['reset_email']) && isset($_GET['step']) && $_GET['step'] === 'reset') {
    $step = 'reset';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Kelak'luk Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=6">
</head>

<body class="auth-page-body">

    <div class="figma-auth-shell">
        <div class="figma-auth-top">
            <h1>Forgot<br>Password</h1>
            <p><?= $step === 'verify' ? 'Masukkan email akunmu untuk mengatur ulang kata sandi.' : 'Buat kata sandi baru untuk akunmu.'; ?></p>
        </div>

        <div class="figma-auth-sheet">
            <img src="../assets/images/logo.png" alt="Kelak'luk Coffee" class="figma-auth-logo">

            <?php if ($error): ?>
                <div class="alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if ($step === 'verify'): ?>
                <!-- TAHAP 1: VERIFIKASI EMAIL TERDAFTAR -->
                <form method="POST">
                    <input type="hidden" name="action" value="check_email">

                    <div class="figma-input-group">
                        <label for="email">Email Terdaftar</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan email kamu" required>
                    </div>

                    <button type="submit" class="figma-auth-btn">LANJUTKAN</button>
                </form>
            <?php else: ?>
                <!-- TAHAP 2: ATUR KATA SANDI BARU -->
                <div class="alert success">
                    Email terverifikasi: <strong><?= htmlspecialchars($_SESSION['reset_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="update_password">

                    <div class="figma-input-group">
                        <label for="new_password">Kata Sandi Baru</label>
                        <input type="password" id="new_password" name="new_password" placeholder="Minimal 6 karakter" required>
                    </div>

                    <div class="figma-input-group">
                        <label for="confirm_password">Konfirmasi Kata Sandi Baru</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Ulangi kata sandi baru" required>
                    </div>

                    <button type="submit" class="figma-auth-btn">SIMPAN SANDI BARU</button>
                </form>
            <?php endif; ?>

            <div class="figma-auth-links" style="margin-top: 22px;">
                Ingat kata sandi? <a href="login.php">Kembali ke Login</a>
            </div>
        </div>
    </div>

    <!-- OVERLAY ANIMASI SAAT KLIK RESET SANDI -->
<div class="coffee-splash-overlay" id="coffeeSplash">
    <div class="splash-logo-card">
        <img src="../assets/images/logo.png" alt="Kelak'luk Coffee">
    </div>
    <div class="splash-spinner"></div>
    <div class="splash-text">Memproses permintaanmu...</div>
</div>

<script>
    const resetForm = document.querySelector('.figma-auth-sheet form');
    if (resetForm) {
        resetForm.addEventListener('submit', function (e) {
            e.preventDefault();
            document.getElementById('coffeeSplash').classList.add('show');
            setTimeout(() => resetForm.submit(), 750);
        });
    }
</script>

</body>

</html>