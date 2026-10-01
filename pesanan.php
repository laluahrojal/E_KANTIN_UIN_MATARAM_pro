<?php
// admin/pesanan.php - Admin View All Transactions & Payment Methods
$pageTitle = "Data Pesanan Pembeli - Admin TI";
require_once __DIR__ . '/../config/database.php';

require_admin();

$pdo = db();

// Handle Update Status & Hapus Pesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    if ($action === 'update_status') {
        $id_pesanan = (int)($_POST['id_pesanan'] ?? 0);
        $newStatus = sanitize($_POST['status_pesanan'] ?? 'Selesai');
        if ($id_pesanan > 0) {
            $stmtUp = $pdo->prepare("UPDATE pesanan SET status_pesanan = ? WHERE id_pesanan = ?");
            if ($stmtUp->execute([$newStatus, $id_pesanan])) {
                set_flash('success', 'Status pesanan #STR-' . str_pad($id_pesanan, 5, '0', STR_PAD_LEFT) . ' berhasil diperbarui menjadi: ' . $newStatus);
            }
        }
        header("Location: pesanan.php");
        exit;
    } elseif ($action === 'delete_pesanan') {
        $id_pesanan = (int)($_POST['id_pesanan'] ?? 0);
        if ($id_pesanan > 0) {
            // Restore menu stock before deleting order
            $stmtGet = $pdo->prepare("SELECT id_menu, jumlah FROM pesanan WHERE id_pesanan = ?");
            $stmtGet->execute([$id_pesanan]);
            $ordInfo = $stmtGet->fetch();

            if ($ordInfo) {
                $stmtRest = $pdo->prepare("UPDATE menu SET stok = stok + ? WHERE id_menu = ?");
                $stmtRest->execute([(int)$ordInfo['jumlah'], (int)$ordInfo['id_menu']]);
            }

            $stmtDel = $pdo->prepare("DELETE FROM pesanan WHERE id_pesanan = ?");
            if ($stmtDel->execute([$id_pesanan])) {
                set_flash('success', 'Pesanan #STR-' . str_pad($id_pesanan, 5, '0', STR_PAD_LEFT) . ' berhasil dihapus. Pesanan ini otomatis terhapus di sisi user.');
            } else {
                set_flash('danger', 'Gagal menghapus pesanan.');
            }
        }
        header("Location: pesanan.php");
        exit;
    }
}

$search = sanitize($_GET['search'] ?? '');

$query = "
    SELECT p.*, COALESCE(NULLIF(p.nama_pemesan, ''), pembeli.nama) AS nama_pembeli, pembeli.no_hp, menu.nama_menu, menu.kategori, menu.harga AS harga_satuan
    FROM pesanan p
    JOIN pembeli ON p.id_pembeli = pembeli.id_pembeli
    JOIN menu ON p.id_menu = menu.id_menu
";

$params = [];
if (!empty($search)) {
    $query .= " WHERE (p.nama_pemesan LIKE ? OR pembeli.nama LIKE ? OR menu.nama_menu LIKE ? OR p.id_pesanan LIKE ? OR p.metode_pembayaran LIKE ?)";
    $searchTerm = "%" . $search . "%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

$query .= " ORDER BY p.tanggal DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="index.php" class="btn btn-outline-light btn-sm" style="padding: 0.2rem 0.6rem;">← Dashboard</a>
            <span class="badge-kategori">RIWAYAT TRANSAKSI KANTIN</span>
        </div>
        <h1 class="text-white font-weight-700 mb-0">Data Pesanan Pembeli</h1>
        <p class="text-muted" style="font-size: 0.9rem;">Daftar seluruh transaksi pemesanan pembeli, metode bayar (Cash/QRIS), dan konfirmasi status pesanan</p>
    </div>

    <!-- Search Form -->
    <form method="GET" action="pesanan.php" class="d-flex gap-2">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari pembeli/menu/metode..." value="<?= sanitize($search) ?>" style="min-width: 250px;">
        <button type="submit" class="btn btn-primary btn-sm">Cari</button>
        <?php if (!empty($search)): ?>
            <a href="pesanan.php" class="btn btn-outline-light btn-sm">Reset</a>
        <?php endif; ?>
    </form>
</div>

<div class="glass-card p-4 animate-fade-in">
    <?php if (empty($orders)): ?>
        <p class="text-muted text-center py-5 mb-0">
            <?= !empty($search) ? 'Tidak ada data pesanan yang cocok dengan kata kunci "' . sanitize($search) . '".' : 'Belum ada transaksi pesanan.' ?>
        </p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>No. Struk</th>
                        <th>Tanggal & Waktu</th>
                        <th>Nama Pembeli</th>
                        <th>No. HP / WA</th>
                        <th>Menu Pesanan</th>
                        <th>Metode Bayar</th>
                        <th>Status Pesanan</th>
                        <th>Total Harga</th>
                        <th class="text-center">Aksi & Struk</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $ord): ?>
                        <?php 
                            $statusCur = $ord['status_pesanan'] ?? 'Selesai';
                        ?>
                        <tr>
                            <td>
                                <strong class="text-gradient font-mono">#STR-<?= str_pad($ord['id_pesanan'], 5, '0', STR_PAD_LEFT) ?></strong>
                            </td>
                            <td class="text-muted">
                                <?= date('d/m/Y H:i', strtotime($ord['tanggal'])) ?>
                            </td>
                            <td>
                                <strong class="text-white"><?= sanitize($ord['nama_pembeli']) ?></strong>
                            </td>
                            <td class="text-muted font-mono"><?= sanitize($ord['no_hp']) ?></td>
                            <td>
                                <strong><?= sanitize($ord['nama_menu']) ?></strong>
                                <br><small class="text-muted"><?= $ord['jumlah'] ?> porsi (<?= sanitize($ord['kategori']) ?>)</small>
                            </td>
                            <td>
                                <?php if (($ord['metode_pembayaran'] ?? 'Cash') === 'QRIS'): ?>
                                    <span class="badge-stok available" style="background: rgba(99, 102, 241, 0.2); color: #a5b4fc; border-color: rgba(99, 102, 241, 0.4);">
                                        QRIS
                                    </span>
                                <?php else: ?>
                                    <span class="badge-stok available">
                                        Cash
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($statusCur === 'Selesai'): ?>
                                    <span class="badge-stok available">Selesai / Lunas</span>
                                <?php else: ?>
                                    <span class="badge-stok empty" style="background: rgba(245, 158, 11, 0.2); color: #fcd34d; border-color: rgba(245, 158, 11, 0.4);">Diproses</span>
                                <?php endif; ?>
                            </td>
                            <td><strong class="text-gradient" style="font-size: 1.05rem;"><?= format_rupiah($ord['total_harga']) ?></strong></td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <form method="POST" action="pesanan.php" style="display: inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id_pesanan" value="<?= $ord['id_pesanan'] ?>">
                                        <?php if ($statusCur === 'Selesai'): ?>
                                            <input type="hidden" name="status_pesanan" value="Diproses">
                                            <button type="submit" class="btn btn-outline-light btn-sm" title="Ubah status ke Diproses">
                                                Diproses
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="status_pesanan" value="Selesai">
                                            <button type="submit" class="btn btn-primary btn-sm shadow-glow" title="Tandai Selesai / Scan Berhasil">
                                                Tandai Selesai
                                            </button>
                                        <?php endif; ?>
                                    </form>

                                    <a href="<?= get_base_url() ?>struk.php?id=<?= $ord['id_pesanan'] ?>" class="btn btn-outline-light btn-sm" target="_blank">
                                        Struk
                                    </a>

                                    <form method="POST" action="pesanan.php" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pesanan #STR-<?= str_pad($ord['id_pesanan'], 5, '0', STR_PAD_LEFT) ?>? Pesanan ini akan otomatis terhapus di user.');">
                                        <input type="hidden" name="action" value="delete_pesanan">
                                        <input type="hidden" name="id_pesanan" value="<?= $ord['id_pesanan'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Hapus Pesanan Ini">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
