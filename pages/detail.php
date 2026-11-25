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

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$productId) {
    header("Location: menu.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT products.*, categories.nama_kategori
    FROM products
    LEFT JOIN categories ON products.category_id = categories.id
    WHERE products.id = ?
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: menu.php");
    exit;
}

// Proses tambah ke keranjang
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qty = max(1, (int) ($_POST['qty'] ?? 1));
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $qty;

    if (isset($_POST['buy_now'])) {
        header("Location: checkout.php");
        exit;
    }

    header("Location: cart.php?added=1");
    exit;
}

$cartCount = array_sum(array_map('intval', $_SESSION['cart'] ?? []));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($product['nama_produk']); ?> | Kelak'luk Coffee</title>
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
            <a href="menu.php" class="active">Our Menu</a>
            <a href="cart.php">Cart<?= $cartCount ? ' (' . $cartCount . ')' : ''; ?></a>
            <a href="history.php">History</a>
        </nav>

        <div class="header-actions">
            <span class="user-greeting">Halo, <?= e($_SESSION['nama'] ?? 'Pelanggan'); ?></span>
            <a href="../auth/logout.php" class="logout-button">Logout</a>
        </div>
    </header>

    <main class="page-wrapper">
        <div class="page-top-bar">
            <a href="menu.php" class="back-pill-btn">← Kembali ke Menu</a>
            <span class="figma-pill-title">Detail Pesanan</span>
        </div>

        <section class="detail-card-grid">
            <div class="detail-image-box">
                <?php if (!empty($product['gambar']) && is_file(__DIR__ . '/../assets/images/' . $product['gambar'])): ?>
                    <img src="../assets/images/<?= e($product['gambar']); ?>" alt="<?= e($product['nama_produk']); ?>">
                <?php else: ?>
                    <span class="placeholder-icon">☕</span>
                <?php endif; ?>
            </div>

            <div class="detail-content">
                <div>
                    <div class="detail-badge-row">
                        <span class="detail-cat-pill"><?= e($product['nama_kategori'] ?? 'Menu'); ?></span>
                        <span class="available-label">Tersedia</span>
                    </div>

                    <h1 class="detail-title"><?= e($product['nama_produk']); ?></h1>
                    <div class="detail-price"><?= rupiah($product['harga']); ?></div>

                    <p class="detail-desc">
                        <?= e($product['deskripsi'] ?: 'Racikan spesial Kelak\'luk Coffee yang dibuat fresh saat pesanan diterima.'); ?>
                    </p>
                </div>

                <form method="POST">
                    <div class="qty-control-row">
                        <label for="qtyInput">Jumlah Pesanan</label>
                        <div class="qty-stepper">
                            <button type="button" class="qty-btn" onclick="changeQty(-1)">−</button>
                            <input type="number" id="qtyInput" name="qty" value="1" min="1" max="50" class="qty-input" readonly>
                            <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
                        </div>
                    </div>

                    <div class="detail-action-buttons">
                        <button type="submit" name="add_cart" class="btn-espresso-solid">
                            🛒 + Keranjang
                        </button>
                        <button type="submit" name="buy_now" class="btn-caramel-solid">
                            Pesan Sekarang →
                        </button>
                    </div>
                </form>
            </div>
        </section>
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

    <script>
        function changeQty(delta) {
            const input = document.getElementById('qtyInput');
            let val = parseInt(input.value || '1', 10) + delta;
            if (val < 1) val = 1;
            if (val > 50) val = 50;
            input.value = val;
        }
    </script>
    <script src="../assets/js/script.js"></script>
</body>

</html>