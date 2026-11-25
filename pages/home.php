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

// Ambil produk unggulan
$stmtFeatured = $conn->query("
    SELECT products.*, categories.nama_kategori
    FROM products
    LEFT JOIN categories ON products.category_id = categories.id
    WHERE products.status = 'tersedia'
    ORDER BY products.created_at DESC
    LIMIT 8
");
$featuredProducts = $stmtFeatured->fetchAll();

// Ambil kategori
$categories = $conn->query("SELECT * FROM categories ORDER BY nama_kategori ASC")->fetchAll();
$cartCount = array_sum(array_map('intval', $_SESSION['cart'] ?? []));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | Kelak'luk Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=8">
</head>

<body>

    <!-- HEADER DENGAN SEARCH BAR FIGMA -->
    <header class="main-header">
        <a href="home.php" class="brand">
            <img src="../assets/images/logo.png" alt="Kelak'luk Coffee" class="brand-logo">
        </a>

        <form action="menu.php" method="GET" class="header-search">
            <span>🔍</span>
            <input type="text" name="q" placeholder="Cari kopi, matcha, atau makanan...">
        </form>

        <nav class="main-nav">
            <a href="home.php" class="active">Home</a>
            <a href="menu.php">Our Menu</a>
            <a href="cart.php">Cart<?= $cartCount ? ' (' . $cartCount . ')' : ''; ?></a>
            <a href="history.php">History</a>
        </nav>

        <div class="header-actions">
            <span class="user-greeting">Halo, <?= e($_SESSION['nama'] ?? 'Pelanggan'); ?></span>
            <a href="../auth/logout.php" class="logout-button">Logout</a>
        </div>
    </header>

    <main class="cafe-container"> 
        <!-- 1. HERO PROMO BANNER (FULL SLIDER + TEKS GRADASI) -->
        <section class="figma-hero-banner home-animate delay-1">
            <div class="figma-hero-visual hero-slider">
                <img src="../assets/images/hero-1.jpg" class="hero-slide active" alt="Promo Kelak'luk Coffee 1">
                <img src="../assets/images/hero-2.jpg" class="hero-slide" alt="Promo Kelak'luk Coffee 2">
                <img src="../assets/images/hero-3.jpg" class="hero-slide" alt="Promo Kelak'luk Coffee 3">
                <img src="../assets/images/hero-4.jpg" class="hero-slide" alt="Promo Kelak'luk Coffee 4">
            </div>
        
            <div class="figma-hero-overlay">
                <div class="figma-hero-text">
                    <span class="figma-promo-tag">Signature Promo • Kelak'luk Coffee</span>
                    <h1>
                        COFFEE
                        <span>Blend &amp; Fresh Brew</span>
                    </h1>
                    <div class="figma-discount-badge">
                        <small>SPECIAL<br>OFFER</small>
                        <strong>50% OFF</strong>
                    </div>
                    <br>
                    <a href="menu.php" class="figma-cta-btn">Pesan Sekarang →</a>
                </div>
            </div>
        </section>

        <!-- 2. 4 IKON FITUR CEPAT -->
        <section class="quick-features home-animate delay-2">
            <a href="menu.php" class="quick-feature-item">
                <div class="quick-icon-box">☕</div>
                <div>
                    <strong>Pesanan Baru</strong>
                    <small>Pilih kopi &amp; camilan</small>
                </div>
            </a>

            <a href="menu.php" class="quick-feature-item">
                <div class="quick-icon-box">🛵</div>
                <div>
                    <strong>Dine In &amp; Take Away</strong>
                    <small>Pesan cepat tanpa antre</small>
                </div>
            </a>

            <a href="cart.php" class="quick-feature-item">
                <div class="quick-icon-box">🛒</div>
                <div>
                    <strong>Keranjang Saya</strong>
                    <small><?= $cartCount; ?> item dipilih</small>
                </div>
            </a>

            <a href="history.php" class="quick-feature-item">
                <div class="quick-icon-box">🧾</div>
                <div>
                    <strong>Riwayat Order</strong>
                    <small>Cek status pesananmu</small>
                </div>
            </a>
        </section>

        <!-- 3. PILL KATEGORI CEPAT -->
        <div class="category-list home-animate delay-2">
            <a href="menu.php" class="selected">Semua Menu</a>
            <?php foreach ($categories as $cat): ?>
                <a href="menu.php?category=<?= (int) $cat['id']; ?>">
                    <?= e($cat['nama_kategori']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- 4. SECTION MENU FAVORIT -->
        <section class="home-animate delay-3">
            <div class="figma-section-bar">
                <span class="figma-pill-title">Menu Pilihan Hari Ini</span>
                <a href="menu.php" class="figma-more-btn">Lihat lainnya →</a>
            </div>

            <div class="product-grid">
                <?php if (empty($featuredProducts)): ?>
                    <p class="empty-message">Belum ada menu tersedia saat ini.</p>
                <?php else: ?>
                    <?php foreach ($featuredProducts as $product): ?>
                        <?php $imgSrc = getProductImage($product); ?>
                        <article class="product-card">
                            <a href="detail.php?id=<?= (int) $product['id']; ?>" class="product-thumb">
                                <?php if (!empty($product['nama_kategori'])): ?>
                                    <span class="product-badge"><?= e($product['nama_kategori']); ?></span>
                                <?php endif; ?>

                                <?php if ($imgSrc !== ''): ?>
                                    <img src="<?= e($imgSrc); ?>" alt="<?= e($product['nama_produk']); ?>">
                                <?php else: ?>
                                    <span class="product-fallback-icon">☕</span>
                                <?php endif; ?>
                            </a>

                            <div class="product-info">
                                <h3><?= e($product['nama_produk']); ?></h3>
                                <p><?= e($product['deskripsi'] ?: 'Racikan spesial Kelak\'luk Coffee.'); ?></p>

                                <div class="product-bottom">
                                    <div class="price-block">
                                        <small>Harga</small>
                                        <strong><?= rupiah($product['harga']); ?></strong>
                                    </div>

                                    <a href="detail.php?id=<?= (int) $product['id']; ?>" class="detail-button">
                                        Pesan
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- 5. KARTU INFO LOKASI KAFE -->
        <section class="cafe-info-banner home-animate delay-4">
            <div>
                <h3>Kelak'luk Coffee</h3>
                <div class="cafe-rating">★★★★★ 4.9 • Coffee Shop &amp; Eatery</div>
                <p>
                    Nikmati suasana hangat dengan racikan kopi pilihan, matcha, dan hidangan pendamping favorit.<br>
                    Buka setiap hari pukul <strong>08.00 – 22.00 WIB</strong> • Tersedia pembayaran <strong>Cash &amp; QRIS</strong>.
                </p>
            </div>
            <a href="menu.php" class="figma-cta-btn">Jelajahi Semua Menu →</a>
        </section>

    </main>

    <!-- FOOTER -->
    <footer class="main-footer">
        <div class="footer-grid">
            <div class="footer-col">
                <a href="home.php" class="footer-logo-wrap">
                    <img src="../assets/images/logo.png" alt="Kelak'luk Coffee" class="footer-logo">
                </a>
                <p>
                    Good Coffee, Good Mood, Good Moments.<br>
                    Racikan kopi pilihan dan suasana hangat setiap hari.
                </p>
            </div>

            <div class="footer-col">
                <h4>Jam Operasional</h4>
                <p>
                    Senin – Minggu<br>
                    08.00 – 22.00 WIB<br>
                    Dine In &amp; Take Away
                </p>
            </div>
        </div>

        <div class="footer-bottom">
            <small>&copy; <?= date('Y'); ?> Kelak'luk Coffee. All rights reserved.</small>
        </div>
    </footer>

    <script src="../assets/js/script.js"></script>
</body>

</html>