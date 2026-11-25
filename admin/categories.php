<?php
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (hash_equals($_SESSION['csrf_token'], $token)) {
        if ($action === 'add') {
            $nama = trim($_POST['nama_kategori'] ?? '');
            if ($nama !== '') {
                $stmt = $conn->prepare("INSERT INTO categories (nama_kategori) VALUES (?)");
                $stmt->execute([$nama]);
            }
        } elseif ($action === 'delete') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if ($id) {
                $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$id]);
            }
        }
        header("Location: categories.php?success=1");
        exit;
    }
}

$categories = $conn->query("
    SELECT categories.*, COUNT(products.id) AS total_produk
    FROM categories
    LEFT JOIN products ON products.category_id = categories.id
    GROUP BY categories.id
    ORDER BY categories.nama_kategori ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Menu | Admin Kelakluk Cafe</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>

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
                <a href="dashboard.php">📊 Dashboard & Pesanan</a>
                <a href="products.php">☕ Kelola Menu</a>
                <a href="categories.php" class="active">🏷️ Kategori Menu</a>
                <a href="../pages/home.php" target="_blank">🌐 Lihat Web Pelanggan</a>
            </nav>
        </div>
        <div class="admin-sidebar-footer">
            <a href="../auth/logout.php">🚪 Logout</a>
        </div>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1>Kategori Menu</h1>
                <p>Kelola kelompok kategori minuman dan makanan.</p>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Daftar kategori berhasil diperbarui.</div>
        <?php endif; ?>

        <section class="admin-card">
            <form method="POST" style="display: flex; gap: 0.75rem; max-width: 500px;">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="add">
                <input type="text" name="nama_kategori" class="form-control" placeholder="Nama kategori baru (misal: Non-Coffee)" required style="flex: 1;">
                <button type="submit" class="btn btn-primary">+ Tambah</button>
            </form>
        </section>

        <section class="admin-card">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Nama Kategori</th>
                            <th>Jumlah Menu</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><strong><?= e($cat['nama_kategori']); ?></strong></td>
                                <td><?= (int) $cat['total_produk']; ?> produk</td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Hapus kategori ini?');">
                                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $cat['id']; ?>">
                                        <button type="submit" class="btn btn-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

</body>
</html>