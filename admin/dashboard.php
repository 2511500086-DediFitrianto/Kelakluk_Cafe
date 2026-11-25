<?php
require_once 'auth_check.php';

// Proses update status pesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $token = $_POST['csrf_token'] ?? '';
    $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $newStatus = $_POST['status'] ?? '';
    $allowedStatus = ['pending', 'diproses', 'siap', 'selesai', 'dibatalkan'];

    if (hash_equals($_SESSION['csrf_token'], $token) && $orderId && in_array($newStatus, $allowedStatus, true)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        header("Location: dashboard.php?updated=1");
        exit;
    }
}

// Statistik Ringkas
$totalPendapatan = $conn->query("SELECT COALESCE(SUM(total_harga), 0) FROM orders WHERE status = 'selesai'")->fetchColumn();
$pesananAktif = $conn->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'diproses', 'siap')")->fetchColumn();
$totalProduk = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalPelanggan = $conn->query("SELECT COUNT(*) FROM users WHERE role != 'admin' OR role IS NULL")->fetchColumn();

// Filter status pesanan
$filterStatus = $_GET['status'] ?? '';
if ($filterStatus !== '') {
    $stmtOrders = $conn->prepare("
        SELECT orders.*, users.nama AS nama_pelanggan
        FROM orders
        LEFT JOIN users ON orders.user_id = users.id
        WHERE orders.status = ?
        ORDER BY orders.created_at DESC
    ");
    $stmtOrders->execute([$filterStatus]);
} else {
    $stmtOrders = $conn->query("
        SELECT orders.*, users.nama AS nama_pelanggan
        FROM orders
        LEFT JOIN users ON orders.user_id = users.id
        ORDER BY orders.created_at DESC
    ");
}
$orders = $stmtOrders->fetchAll();

// Query item per pesanan
$stmtItems = $conn->prepare("
    SELECT order_items.jumlah, products.nama_produk
    FROM order_items
    LEFT JOIN products ON order_items.product_id = products.id
    WHERE order_items.order_id = ?
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Kelakluk Cafe</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>

    <!-- SIDEBAR -->
    <aside class="admin-sidebar">
        <div>
            <a href="dashboard.php" class="admin-brand">
                <span class="admin-brand-mark">K</span>
                <span>
                    <strong>KELAKLUK</strong>
                    <small>ADMIN PANEL</small>
                </span>
            </a>

            <nav class="admin-nav">
                <a href="dashboard.php" class="active">📊 Dashboard & Pesanan</a>
                <a href="products.php">☕ Kelola Menu</a>
                <a href="categories.php">🏷️ Kategori Menu</a>
                <a href="../pages/home.php" target="_blank">🌐 Lihat Web Pelanggan</a>
            </nav>
        </div>

        <div class="admin-sidebar-footer">
            <small style="color: #d6c5b8; padding: 0 0.75rem;">
                Login: <?= e($_SESSION['nama'] ?? 'Admin'); ?>
            </small>
            <a href="../auth/logout.php">🚪 Logout</a>
        </div>
    </aside>

    <!-- KONTEN UTAMA -->
    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1>Dashboard Kelakluk Cafe</h1>
                <p>Pantau penjualan dan kelola pesanan pelanggan secara real-time.</p>
            </div>
        </div>

        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">Status pesanan berhasil diperbarui.</div>
        <?php endif; ?>

        <!-- KARTU STATISTIK -->
        <section class="stats-grid">
            <div class="stat-card">
                <span>Pendapatan (Selesai)</span>
                <strong><?= rupiah($totalPendapatan); ?></strong>
            </div>
            <div class="stat-card">
                <span>Pesanan Aktif</span>
                <strong><?= (int) $pesananAktif; ?> Pesanan</strong>
            </div>
            <div class="stat-card">
                <span>Total Menu</span>
                <strong><?= (int) $totalProduk; ?> Item</strong>
            </div>
            <div class="stat-card">
                <span>Pelanggan Terdaftar</span>
                <strong><?= (int) $totalPelanggan; ?> Akun</strong>
            </div>
        </section>

        <!-- DAFTAR PESANAN -->
        <section class="admin-card">
            <div class="admin-card-header">
                <h2>Daftar Pesanan Masuk</h2>
                <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                    <a href="dashboard.php" class="btn <?= $filterStatus === '' ? 'btn-primary' : 'btn-secondary'; ?>">Semua</a>
                    <a href="dashboard.php?status=pending" class="btn <?= $filterStatus === 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">Pending</a>
                    <a href="dashboard.php?status=diproses" class="btn <?= $filterStatus === 'diproses' ? 'btn-primary' : 'btn-secondary'; ?>">Diproses</a>
                    <a href="dashboard.php?status=siap" class="btn <?= $filterStatus === 'siap' ? 'btn-primary' : 'btn-secondary'; ?>">Siap</a>
                    <a href="dashboard.php?status=selesai" class="btn <?= $filterStatus === 'selesai' ? 'btn-primary' : 'btn-secondary'; ?>">Selesai</a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>No. Pesanan</th>
                            <th>Pelanggan</th>
                            <th>Detail Menu & Catatan</th>
                            <th>Total & Bayar</th>
                            <th>Status</th>
                            <th>Ubah Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #75675e; padding: 2rem;">
                                    Belum ada pesanan pada kategori ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <?php
                                $stmtItems->execute([$order['id']]);
                                $items = $stmtItems->fetchAll();
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= e($order['nomor_pesanan']); ?></strong><br>
                                        <small style="color: #75675e;"><?= date('d/m/Y H:i', strtotime($order['created_at'])); ?></small>
                                    </td>
                                    <td><?= e($order['nama_pelanggan'] ?? 'Pelanggan'); ?></td>
                                    <td>
                                        <?php foreach ($items as $item): ?>
                                            <div>• <?= e($item['nama_produk']); ?> <strong>(x<?= (int) $item['jumlah']; ?>)</strong></div>
                                        <?php endforeach; ?>
                                        <?php if (!empty($order['catatan'])): ?>
                                            <small style="color: #8c5832; display: block; margin-top: 0.25rem;">
                                                <?= e($order['catatan']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= rupiah($order['total_harga']); ?></strong><br>
                                        <small style="color: #75675e;"><?= e($order['metode_pembayaran']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= e($order['status']); ?>">
                                            <?= e($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: flex; gap: 0.4rem;">
                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="order_id" value="<?= (int) $order['id']; ?>">

                                            <select name="status" class="form-control" style="padding: 0.4rem;">
                                                <?php foreach (['pending', 'diproses', 'siap', 'selesai', 'dibatalkan'] as $st): ?>
                                                    <option value="<?= $st; ?>" <?= $order['status'] === $st ? 'selected' : ''; ?>>
                                                        <?= ucfirst($st); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-primary">Simpan</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

</body>
</html>