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

if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function rupiah($value) {
    return 'Rp' . number_format((float) $value, 0, ',', '.');
}

// Ambil item keranjang
$ids = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
$stmt->execute($ids);
$products = $stmt->fetchAll();

$cartItems = [];
$totalHarga = 0;

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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipePesanan = $_POST['tipe_pesanan'] ?? 'Take Away';
    $nomorMeja = trim($_POST['nomor_meja'] ?? '');
    $catatanTambahan = trim($_POST['catatan'] ?? '');
    $metodePembayaran = $_POST['metode_pembayaran'] ?? 'QRIS';

    if ($tipePesanan === 'Dine In' && $nomorMeja === '') {
        $error = 'Silakan isi nomor meja untuk pesanan Dine In.';
    } else {
        $infoCatatan = $tipePesanan;
        if ($tipePesanan === 'Dine In') {
            $infoCatatan .= " (Meja: $nomorMeja)";
        }
        if ($catatanTambahan !== '') {
            $infoCatatan .= " | Catatan: $catatanTambahan";
        }

        $nomorPesanan = 'KLK-' . date('Ymd') . '-' . rand(1000, 9999);

        try {
            $conn->beginTransaction();

            // Deteksi kolom tabel orders secara otomatis agar tidak error
            $orderCols = $conn->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
            $insertOrderCols = ['user_id', 'nomor_pesanan', 'total_harga', 'status'];
            $insertOrderVals = [$_SESSION['user_id'], $nomorPesanan, $totalHarga, 'pending'];

            if (in_array('metode_pembayaran', $orderCols)) {
                $insertOrderCols[] = 'metode_pembayaran';
                $insertOrderVals[] = $metodePembayaran;
            }
            if (in_array('catatan', $orderCols)) {
                $insertOrderCols[] = 'catatan';
                $insertOrderVals[] = $infoCatatan;
            }

            $colList = implode(', ', $insertOrderCols);
            $valPlaceholders = implode(', ', array_fill(0, count($insertOrderCols), '?'));

            $stmtOrder = $conn->prepare("INSERT INTO orders ($colList) VALUES ($valPlaceholders)");
            $stmtOrder->execute($insertOrderVals);
            $orderId = $conn->lastInsertId();

            // Deteksi kolom tabel order_items secara otomatis
            $itemCols = $conn->query("SHOW COLUMNS FROM order_items")->fetchAll(PDO::FETCH_COLUMN);
            $priceCol = in_array('harga_satuan', $itemCols) ? 'harga_satuan' : 'harga';
            $hasSubtotal = in_array('subtotal', $itemCols);

            if ($hasSubtotal) {
                $stmtItem = $conn->prepare("
                    INSERT INTO order_items (order_id, product_id, jumlah, {$priceCol}, subtotal)
                    VALUES (?, ?, ?, ?, ?)
                ");
                foreach ($cartItems as $item) {
                    $stmtItem->execute([
                        $orderId,
                        $item['product']['id'],
                        $item['qty'],
                        $item['product']['harga'],
                        $item['subtotal']
                    ]);
                }
            } else {
                $stmtItem = $conn->prepare("
                    INSERT INTO order_items (order_id, product_id, jumlah, {$priceCol})
                    VALUES (?, ?, ?, ?)
                ");
                foreach ($cartItems as $item) {
                    $stmtItem->execute([
                        $orderId,
                        $item['product']['id'],
                        $item['qty'],
                        $item['product']['harga']
                    ]);
                }
            }

            $conn->commit();
            unset($_SESSION['cart']);

            header("Location: history.php?success=1");
            exit;
        } catch (Exception $ex) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = 'Gagal memproses pesanan: ' . $ex->getMessage();
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
    <title>Checkout | Kelak'luk Coffee</title>
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
            <a href="cart.php" class="back-pill-btn">← Kembali ke Keranjang</a>
            <span class="figma-pill-title">Konfirmasi Pesanan</span>
        </div>

        <?php if ($error): ?>
            <div class="alert error"><?= e($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="cart-layout-grid">
            <div class="checkout-card">
                <h3>1. Tipe Pemesanan</h3>
                <div class="option-pills-grid">
                    <label class="radio-tile">
                        <input type="radio" name="tipe_pesanan" value="Dine In" checked onclick="toggleMeja(true)">
                        <span class="radio-tile-box">🍽️ Dine In (Makan di Tempat)</span>
                    </label>
                    <label class="radio-tile">
                        <input type="radio" name="tipe_pesanan" value="Take Away" onclick="toggleMeja(false)">
                        <span class="radio-tile-box">🛍️ Take Away (Bawa Pulang)</span>
                    </label>
                </div>

                <div class="checkout-field" id="mejaField">
                    <label for="nomorMeja">Nomor Meja *</label>
                    <input type="text" id="nomorMeja" name="nomor_meja" placeholder="Contoh: Meja 05">
                </div>

                <div class="checkout-field">
                    <label for="catatanInput">Catatan Pesanan (Opsional)</label>
                    <textarea id="catatanInput" name="catatan" rows="2" placeholder="Contoh: Less sugar, es sedikit..."></textarea>
                </div>

                <h3 style="margin-top: 22px;">2. Metode Pembayaran (Payment)</h3>
                <div class="option-pills-grid">
                    <label class="radio-tile">
                        <input type="radio" name="metode_pembayaran" value="QRIS" checked onclick="toggleQris(true)">
                        <span class="radio-tile-box">📱 QRIS (Scan Instan)</span>
                    </label>
                    <label class="radio-tile">
                        <input type="radio" name="metode_pembayaran" value="Cash" onclick="toggleQris(false)">
                        <span class="radio-tile-box">💵 Cash (Bayar di Kasir)</span>
                    </label>
                </div>

                <?php
                $qrisPayload = "00020101021226570011ID.CO.QRIS.WWW0215ID10260000123450303UMI51440014ID.CO.QRIS.WWW0215ID1026000012345520458125303360540" . strlen((string)$totalHarga) . $totalHarga . "5802ID5915KELAKLUK COFFEE6013PANGKALPINANG6304A1B2";
                $qrisImageUrl = "https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=" . urlencode($qrisPayload);
                ?>
                <div class="qris-box" id="qrisBox">
                    <span class="qris-header-badge">QRIS • ALL E-WALLET &amp; M-BANKING</span>
                    <div class="qris-merchant">KELAK'LUK COFFEE</div>
                    <div class="qris-amount"><?= rupiah($totalHarga); ?></div>
                    <div class="qris-qr-frame">
                        <img src="<?= e($qrisImageUrl); ?>" alt="Kode QRIS Kelak'luk Coffee">
                    </div>
                    <p class="qris-hint">
                        Scan menggunakan GoPay, OVO, DANA, ShopeePay, atau Mobile Banking.<br>
                        Klik <strong>"Buat Pesanan Sekarang"</strong> setelah melakukan pembayaran.
                    </p>
                </div>
            </div>

            <aside class="figma-summary-card">
                <h2>Rincian Pembayaran</h2>

                <?php foreach ($cartItems as $item): ?>
                    <div class="summary-line">
                        <span><?= e($item['product']['nama_produk']); ?> (x<?= $item['qty']; ?>)</span>
                        <strong><?= rupiah($item['subtotal']); ?></strong>
                    </div>
                <?php endforeach; ?>

                <div class="summary-line total">
                    <span>Total Pembayaran</span>
                    <strong><?= rupiah($totalHarga); ?></strong>
                </div>

                <button type="submit" class="btn-caramel-solid" style="width: 100%; margin-top: 18px;">
                    Buat Pesanan Sekarang ✓
                </button>
            </aside>
        </form>
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

    <nav class="figma-bottom-nav">
        <a href="home.php"><span>⌂</span><small>Home</small></a>
        <a href="menu.php"><span>◎</span><small>Menu</small></a>
        <a href="cart.php" class="active"><span>🛒</span><small>Cart<?= $cartCount ? ' (' . $cartCount . ')' : ''; ?></small></a>
        <a href="history.php"><span>🔔</span><small>Order</small></a>
        <a href="../auth/logout.php"><span>👤</span><small>Logout</small></a>
    </nav>

    <script>
        function toggleMeja(show) {
            document.getElementById('mejaField').style.display = show ? 'block' : 'none';
        }
        function toggleQris(show) {
            document.getElementById('qrisBox').style.display = show ? 'block' : 'none';
        }
    </script>
    <script src="../assets/js/script.js"></script>
</body>

</html>