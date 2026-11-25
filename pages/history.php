<?php
session_start();
require_once '../config/database.php';

function getProductImage($prod) {
    $raw = trim((string) ($prod['gambar'] ?? ($prod['foto'] ?? ($prod['image'] ?? ''))));
    if ($raw === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $raw)) {
        return $raw;
    }

    $clean = ltrim($raw, '/');
    $candidates = [
        '../' . $clean,
        '../assets/images/' . $clean,
        '../assets/images/products/' . $clean,
        '../uploads/' . $clean,
        '../assets/uploads/' . $clean,
        '../assets/images/' . basename($clean),
        '../uploads/' . basename($clean),
    ];

    foreach ($candidates as $path) {
        if (file_exists(__DIR__ . '/' . $path)) {
            return $path;
        }
    }
    return '';
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function rupiah($value) {
    return 'Rp' . number_format((float) $value, 0, ',', '.');
}

$stmtOrders = $conn->prepare("
    SELECT *
    FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmtOrders->execute([$_SESSION['user_id']]);
$orders = $stmtOrders->fetchAll();

$stmtItems = $conn->prepare("
    SELECT order_items.*, products.nama_produk
    FROM order_items
    LEFT JOIN products ON order_items.product_id = products.id
    WHERE order_items.order_id = ?
");

$cartCount = array_sum(array_map('intval', $_SESSION['cart'] ?? []));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan | Kelak'luk Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=5">
    <link rel="stylesheet" href="../assets/css/detail-cart.css?v=5">
</head>

<body>

    <header class="main-header">
        <a href="home.php" class="brand">
            <img src="../assets/images/logo.png" alt="Kelak'luk Coffee" class="brand-logo">
        </a>

        <form action="menu.php" method="GET" class="header-search">
            <span>🔍</span>
            <input type="text" name="q" placeholder="Cari kopi, matcha, atau makanan...">
        </form>

        <nav class="main-nav">
            <a href="home.php">Home</a>
            <a href="menu.php">Our Menu</a>
            <a href="cart.php">Cart<?= $cartCount ? ' (' . $cartCount . ')' : ''; ?></a>
            <a href="history.php" class="active">History</a>
        </nav>

        <div class="header-actions">
            <span class="user-greeting">Halo, <?= e($_SESSION['nama'] ?? 'Pelanggan'); ?></span>
            <a href="../auth/logout.php" class="logout-button">Logout</a>
        </div>
    </header>

    <main class="page-wrapper">
        <div class="page-top-bar" style="max-width: 860px; margin: 0 auto 24px;">
            <span class="figma-pill-title">Riwayat &amp; Status Pesanan</span>
            <a href="menu.php" class="figma-more-btn">+ Pesan Lagi →</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert success" style="max-width: 860px; margin: 0 auto 18px;">
                Pesanan berhasil dibuat! Silakan pantau status pesananmu di bawah ini.
            </div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="figma-empty-box">
                <div class="empty-icon">🧾</div>
                <h2>Belum Ada Riwayat Pesanan</h2>
                <p>Kamu belum pernah melakukan pemesanan. Yuk, pesan kopi pertamamu sekarang!</p>
                <a href="menu.php" class="btn-caramel-solid">Jelajahi Menu →</a>
            </div>
        <?php else: ?>
            <section class="history-stack">
                <?php foreach ($orders as $order): ?>
                    <?php
                    $stmtItems->execute([$order['id']]);
                    $items = $stmtItems->fetchAll();
                    $statusKey = strtolower(trim($order['status'] ?? 'pending'));
                    ?>
                    <article class="figma-history-card">
                        <div class="history-top">
                            <div>
                                <div class="history-code"><?= e($order['nomor_pesanan']); ?></div>
                                <div class="history-date"><?= date('d M Y, H:i', strtotime($order['created_at'])); ?> WIB</div>
                            </div>
                            <span class="status-pill status-<?= e($statusKey); ?>">
                                <?= e($order['status']); ?>
                            </span>
                        </div>

                        <div class="history-body">
                            <?php foreach ($items as $item): ?>
                                <div class="history-line">
                                    <span><?= e($item['nama_produk'] ?? 'Produk'); ?> <strong>x<?= (int) $item['jumlah']; ?></strong></span>
                                    <span><?= rupiah($item['subtotal']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (!empty($order['catatan'])): ?>
                            <div class="history-note-box">
                                <?= e($order['catatan']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="history-bottom">
                            <span style="color: var(--text-muted);">
                                Pembayaran: <strong style="color: var(--espresso);"><?= e($order['metode_pembayaran'] ?? '-'); ?></strong>
                            </span>
                            <div>
                                <span style="color: var(--text-muted);">Total: </span>
                                <span class="history-total-price"><?= rupiah($order['total_harga']); ?></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>

    <footer class="main-footer">
        <div class="footer-grid">
            <div class="footer-col">
                <a href="home.php" class="footer-logo-wrap">
                    <img src="../assets/images/logo.png" alt="Kelak'luk Coffee" class="footer-logo">
                </a>
                <p>Good Coffee, Good Mood, Good Moments.<br>Racikan kopi pilihan dan suasana hangat setiap hari.</p>
            </div>
            <div class="footer-col">
                <h4>Jam Operasional</h4>
                <p>Senin – Minggu<br>08.00 – 22.00 WIB<br>Dine In &amp; Take Away</p>
            </div>
        </div>
        <div class="footer-bottom">
            <small>&copy; <?= date('Y'); ?> Kelak'luk Coffee. All rights reserved.</small>
        </div>
    </footer>

    <script src="../assets/js/script.js"></script>
</body>

</html>