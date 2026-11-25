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

$selectedCategory = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
$searchQuery = trim($_GET['q'] ?? '');

$categories = $conn->query("SELECT * FROM categories ORDER BY nama_kategori ASC")->fetchAll();

// Query produk dengan filter kategori & pencarian
$sql = "
    SELECT products.*, categories.nama_kategori
    FROM products
    LEFT JOIN categories ON products.category_id = categories.id
    WHERE products.status = 'tersedia'
";
$params = [];

if ($selectedCategory) {
    $sql .= " AND products.category_id = ?";
    $params[] = $selectedCategory;
}

if ($searchQuery !== '') {
    $sql .= " AND (products.nama_produk LIKE ? OR products.deskripsi LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

$sql .= " ORDER BY products.nama_produk ASC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$cartCount = array_sum(array_map('intval', $_SESSION['cart'] ?? []));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Menu | Kelak'luk Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=8">
</head>

<body>

    <header class="main-header">
        <a href="home.php" class="brand">
            <img src="../assets/images/logo.png" alt="Kelak'luk Coffee" class="brand-logo">
        </a>

        <form action="menu.php" method="GET" class="header-search">
            <span>🔍</span>
            <input type="text" name="q" value="<?= e($searchQuery); ?>" placeholder="Cari kopi, matcha, atau makanan...">
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

    <main class="cafe-container">
        <div class="figma-section-bar">
            <span class="figma-pill-title">
                <?= $searchQuery !== '' ? 'Hasil Pencarian: "' . e($searchQuery) . '"' : 'Semua Menu Kelak\'luk Coffee'; ?>
            </span>
        </div>

        <!-- FILTER KATEGORI PILL -->
        <div class="category-list">
            <a href="menu.php" class="<?= !$selectedCategory && $searchQuery === '' ? 'selected' : ''; ?>">Semua</a>
            <?php foreach ($categories as $category): ?>
                <a href="menu.php?category=<?= (int) $category['id']; ?>"
                   class="<?= $selectedCategory === (int) $category['id'] ? 'selected' : ''; ?>">
                    <?= e($category['nama_kategori']); ?>
                </a>
            <?php endforeach; ?>

            <?php if ($searchQuery !== ''): ?>
                <a href="menu.php" class="reset-pill">Reset Pencarian ✕</a>
            <?php endif; ?>
        </div>

        <!-- GRID MENU -->
        <div class="product-grid">
            <?php if (empty($products)): ?>
                <p class="empty-message">Menu tidak ditemukan. Coba kata kunci atau kategori lain.</p>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
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
    </main>

    <footer class="main-footer">
        <div class="footer-grid">
            <div class="footer-col">
                <a href="home.php" class="footer-logo-wrap">
                    <img src="../assets/images/logo.png" alt="Kelak'luk Coffee" class="footer-logo">
                </a>
                <p>Good Coffee, Good Mood, Good Moments.</p>
            </div>
            <div class="footer-col">
                <h4>Jam Operasional</h4>
                <p>Senin – Minggu<br>08.00 – 22.00 WIB</p>
            </div>
        </div>
        <div class="footer-bottom">
            <small>&copy; <?= date('Y'); ?> Kelak'luk Coffee. All rights reserved.</small>
        </div>
    </footer>

    <script src="../assets/js/script.js"></script>
</body>

</html>