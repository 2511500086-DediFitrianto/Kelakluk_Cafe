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

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Aksi tambah/kurang/hapus item keranjang
if (isset($_GET['action'], $_GET['id'])) {
    $id = (int) $_GET['id'];
    $action = $_GET['action'];

    if ($action === 'inc') {
        $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;
    } elseif ($action === 'dec' && isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]--;
        if ($_SESSION['cart'][$id] <= 0) {
            unset($_SESSION['cart'][$id]);
        }
    } elseif ($action === 'remove') {
        unset($_SESSION['cart'][$id]);
    }

    header("Location: cart.php");
    exit;
}

$cartItems = [];
$totalHarga = 0;

if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $products = $stmt->fetchAll();

    foreach ($products as $prod) {
        $qty = (int) ($_SESSION['cart'][$prod['id']] ?? 0);
        if ($qty > 0) {
            $subtotal = $prod['harga'] * $qty;
            $totalHarga += $subtotal;
            $cartItems[] = [
                'product' => $prod,
                'qty' => $qty,
                'subtotal' => $subtotal
            ];
        }
    }
}

$cartCount = array_sum(array_map('intval', $_SESSION['cart'] ?? []));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Saya | Kelak'luk Coffee</title>
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
            <a href="cart.php" class="active">Cart<?= $cartCount ? ' (' . $cartCount . ')' : ''; ?></a>
            <a href="history.php">History</a>
        </nav>

        <div class="header-actions">
            <span class="user-greeting">Halo, <?= e($_SESSION['nama'] ?? 'Pelanggan'); ?></span>
            <a href="../auth/logout.php" class="logout-button">Logout</a>
        </div>
    </header>

    <main class="page-wrapper">
        <div class="page-top-bar">
            <span class="figma-pill-title">Keranjang Pesanan</span>
            <a href="menu.php" class="back-pill-btn">+ Tambah Menu</a>
        </div>

        <?php if (isset($_GET['added'])): ?>
            <div class="alert success">Menu berhasil ditambahkan ke keranjang!</div>
        <?php endif; ?>

        <?php if (empty($cartItems)): ?>
            <div class="figma-empty-box">
                <div class="empty-icon">🛒</div>
                <h2>Keranjang Masih Kosong</h2>
                <p>Yuk, pilih kopi atau menu favoritmu dari Kelak'luk Coffee terlebih dahulu.</p>
                <a href="menu.php" class="btn-caramel-solid">Lihat Menu →</a>
            </div>
        <?php else: ?>
            <div class="cart-layout-grid">
                <div class="cart-items-stack">
                    <?php foreach ($cartItems as $item): ?>
                        <?php $p = $item['product']; ?>
                        <article class="figma-cart-item">
                            <div class="figma-cart-thumb">
                                <?php if (!empty($p['gambar']) && is_file(__DIR__ . '/../assets/images/' . $p['gambar'])): ?>
                                    <img src="../assets/images/<?= e($p['gambar']); ?>" alt="<?= e($p['nama_produk']); ?>">
                                <?php else: ?>
                                    <span>☕</span>
                                <?php endif; ?>
                            </div>

                            <div class="figma-cart-meta">
                                <h3><?= e($p['nama_produk']); ?></h3>
                                <div class="unit-price"><?= rupiah($p['harga']); ?> / item</div>

                                <div class="cart-qty-actions">
                                    <a href="cart.php?action=dec&id=<?= (int) $p['id']; ?>" class="mini-qty-btn">−</a>
                                    <span class="cart-qty-num"><?= $item['qty']; ?></span>
                                    <a href="cart.php?action=inc&id=<?= (int) $p['id']; ?>" class="mini-qty-btn">+</a>
                                </div>
                            </div>

                            <div class="figma-cart-right">
                                <span class="item-subtotal"><?= rupiah($item['subtotal']); ?></span>
                                <a href="cart.php?action=remove&id=<?= (int) $p['id']; ?>" class="remove-item-link">Hapus</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <aside class="figma-summary-card">
                    <h2>Rincian Pembayaran</h2>
                    <div class="summary-line">
                        <span>Total Item</span>
                        <strong><?= $cartCount; ?> item</strong>
                    </div>
                    <div class="summary-line">
                        <span>Biaya Layanan</span>
                        <span>Gratis</span>
                    </div>
                    <div class="summary-line total">
                        <span>Total Pembayaran</span>
                        <strong><?= rupiah($totalHarga); ?></strong>
                    </div>

                    <a href="checkout.php" class="btn-caramel-solid" style="width: 100%; margin-top: 18px;">
                        Lanjut Checkout →
                    </a>
                </aside>
            </div>
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