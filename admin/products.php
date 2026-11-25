<?php
require_once 'auth_check.php';

$error = '';
$editProduct = null;

// Ambil data jika mode edit
if (isset($_GET['edit'])) {
    $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($editId) {
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$editId]);
        $editProduct = $stmt->fetch();
    }
}

// PROSES SIMPAN / UPDATE / HAPUS PRODUK
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = "Sesi tidak valid.";
    } elseif ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            // Hapus file foto lama jika ada
            $stmtOld = $conn->prepare("SELECT gambar FROM products WHERE id = ?");
            $stmtOld->execute([$id]);
            $oldData = $stmtOld->fetch();
            if (!empty($oldData['gambar'])) {
                $oldPath = __DIR__ . '/../assets/images/' . basename($oldData['gambar']);
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: products.php?msg=deleted");
            exit;
        }
    } elseif ($action === 'save') {
        $id             = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $namaProduk     = trim($_POST['nama_produk'] ?? '');
        $categoryId     = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT) ?: null;
        $harga          = filter_input(INPUT_POST, 'harga', FILTER_VALIDATE_FLOAT);
        $status         = ($_POST['status'] ?? 'tersedia') === 'habis' ? 'habis' : 'tersedia';
        $deskripsi      = trim($_POST['deskripsi'] ?? '');
        $gambarLama     = trim($_POST['gambar_lama'] ?? '');
        $namaFileGambar = $gambarLama;

        // Proses upload gambar jika ada file baru
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
                $error = "Upload gagal (Kode error: " . $_FILES['gambar']['error'] . "). Ukuran file terlalu besar atau server menolak.";
            } else {
                $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
                $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($ext, $allowedExt, true)) {
                    $targetDir = __DIR__ . '/../assets/images/';
                    if (!is_dir($targetDir)) {
                        @mkdir($targetDir, 0777, true);
                    }

                    $newFileName = 'menu-' . time() . '-' . random_int(100, 999) . '.' . $ext;
                    $targetPath  = $targetDir . $newFileName;

                    if (move_uploaded_file($_FILES['gambar']['tmp_name'], $targetPath)) {
                        @chmod($targetPath, 0644);
                        // Hapus foto lama jika sedang edit dan diganti foto baru
                        if ($gambarLama !== '' && is_file($targetDir . basename($gambarLama))) {
                            @unlink($targetDir . basename($gambarLama));
                        }
                        $namaFileGambar = $newFileName;
                    } else {
                        $error = "Gagal menyimpan foto ke folder assets/images/. Jalankan 'chmod -R 777 assets/images' di Terminal.";
                    }
                } else {
                    $error = "Format gambar harus JPG, PNG, atau WEBP.";
                }
            }
        }

        if ($error === '' && $namaProduk !== '' && $harga !== false) {
            if ($id) {
                $stmt = $conn->prepare("
                    UPDATE products
                    SET category_id = ?, nama_produk = ?, deskripsi = ?, harga = ?, gambar = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([$categoryId, $namaProduk, $deskripsi, $harga, $namaFileGambar, $status, $id]);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO products (category_id, nama_produk, deskripsi, harga, gambar, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$categoryId, $namaProduk, $deskripsi, $harga, $namaFileGambar, $status]);
            }
            header("Location: products.php?msg=saved");
            exit;
        } elseif ($error === '') {
            $error = "Nama produk dan harga wajib diisi dengan benar.";
        }
    }
}

$categories = $conn->query("SELECT * FROM categories ORDER BY nama_kategori ASC")->fetchAll();
$products = $conn->query("
    SELECT products.*, categories.nama_kategori
    FROM products
    LEFT JOIN categories ON products.category_id = categories.id
    ORDER BY products.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Menu | Admin Kelak'luk Coffee</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>

    <aside class="admin-sidebar">
        <div>
            <a href="dashboard.php" class="admin-brand">
                <span class="admin-brand-mark">K</span>
                <span>
                    <strong>KELAK'LUK</strong>
                    <small>ADMIN PANEL</small>
                </span>
            </a>
            <nav class="admin-nav">
                <a href="dashboard.php">📊 Dashboard &amp; Pesanan</a>
                <a href="products.php" class="active">☕ Kelola Menu</a>
                <a href="categories.php">🏷️ Kategori Menu</a>
                <a href="../pages/home.php" target="_blank">🌐 Lihat Web Pelanggan</a>
            </nav>
        </div>
        <div class="admin-sidebar-footer">
            <a href="../auth/logout.php" onclick="return confirm('Anda yakin ingin logout?')">🚪 Logout</a>
        </div>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1>Kelola Menu Cafe</h1>
                <p>Tambah, ubah, atau atur ketersediaan produk Kelak'luk Coffee.</p>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?= $_GET['msg'] === 'deleted' ? 'Produk berhasil dihapus.' : 'Data produk berhasil disimpan.'; ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= e($error); ?></div>
        <?php endif; ?>

        <!-- FORM TAMBAH / EDIT PRODUK -->
        <section class="admin-card">
            <div class="admin-card-header">
                <h2><?= $editProduct ? 'Edit Menu: ' . e($editProduct['nama_produk']) : 'Tambah Menu Baru'; ?></h2>
                <?php if ($editProduct): ?>
                    <a href="products.php" class="btn btn-secondary">Batal Edit</a>
                <?php endif; ?>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int) ($editProduct['id'] ?? 0); ?>">
                <input type="hidden" name="gambar_lama" value="<?= e($editProduct['gambar'] ?? ''); ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Nama Produk</label>
                        <input type="text" name="nama_produk" class="form-control" required value="<?= e($editProduct['nama_produk'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="category_id" class="form-control">
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int) $cat['id']; ?>" <?= ($editProduct['category_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
                                    <?= e($cat['nama_kategori']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Harga (Rp)</label>
                        <input type="number" name="harga" class="form-control" required min="0" value="<?= e($editProduct['harga'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Status Ketersediaan</label>
                        <select name="status" class="form-control">
                            <option value="tersedia" <?= ($editProduct['status'] ?? '') === 'tersedia' ? 'selected' : ''; ?>>Tersedia</option>
                            <option value="habis" <?= ($editProduct['status'] ?? '') === 'habis' ? 'selected' : ''; ?>>Habis</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Deskripsi Singkat</label>
                        <textarea name="deskripsi" rows="2" class="form-control"><?= e($editProduct['deskripsi'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Foto Menu (JPG/PNG/WEBP)</label>
                        <input type="file" name="gambar" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                        <?php if (!empty($editProduct['gambar']) && is_file(__DIR__ . '/../assets/images/' . basename($editProduct['gambar']))): ?>
                            <div style="margin-top: 8px; display: flex; align-items: center; gap: 10px;">
                                <img src="../assets/images/<?= e(basename($editProduct['gambar'])); ?>" alt="Preview" style="width: 48px; height: 48px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd;">
                                <small style="color: #75675e;">Foto saat ini tersimpan</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <?= $editProduct ? 'Simpan Perubahan' : '+ Tambah Produk'; ?>
                </button>
            </form>
        </section>

        <!-- TABEL DAFTAR PRODUK -->
        <section class="admin-card">
            <div class="admin-card-header">
                <h2>Daftar Menu Saat Ini</h2>
            </div>

            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $prod): ?>
                            <?php $imgFile = basename((string) ($prod['gambar'] ?? '')); ?>
                            <tr>
                                <td>
                                    <?php if ($imgFile !== '' && is_file(__DIR__ . '/../assets/images/' . $imgFile)): ?>
                                        <img src="../assets/images/<?= e($imgFile); ?>" class="thumb-img" alt="">
                                    <?php else: ?>
                                        <div class="thumb-img" style="display: grid; place-items: center;">☕</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= e($prod['nama_produk']); ?></strong><br>
                                    <small style="color: #75675e;"><?= e($prod['deskripsi'] ?? ''); ?></small>
                                </td>
                                <td><?= e($prod['nama_kategori'] ?? '-'); ?></td>
                                <td><strong><?= rupiah($prod['harga']); ?></strong></td>
                                <td>
                                    <span class="badge badge-<?= e($prod['status']); ?>">
                                        <?= e($prod['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.4rem;">
                                        <a href="products.php?edit=<?= (int) $prod['id']; ?>" class="btn btn-secondary">Edit</a>
                                        <form method="POST" onsubmit="return confirm('Hapus produk ini?');">
                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $prod['id']; ?>">
                                            <button type="submit" class="btn btn-danger">Hapus</button>
                                        </form>
                                    </div>
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