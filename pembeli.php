<?php
// admin/pembeli.php - Admin View & Delete Registered Pembeli Users
$pageTitle = "Data Pembeli - Admin TI";
require_once __DIR__ . '/../config/database.php';

require_admin();

$pdo = db();

// Handle Delete Pembeli POST Action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    if ($action === 'delete') {
        $id_pembeli = (int)($_POST['id_pembeli'] ?? 0);
        if ($id_pembeli > 0) {
            $stmtDel = $pdo->prepare("DELETE FROM pembeli WHERE id_pembeli = ?");
            if ($stmtDel->execute([$id_pembeli])) {
                set_flash('success', 'Data pembeli beserta riwayat pesanan nya berhasil dihapus!');
            } else {
                set_flash('danger', 'Gagal menghapus data pembeli.');
            }
        }
        header("Location: pembeli.php");
        exit;
    }
}

$stmt = $pdo->query("
    SELECT p.*, COUNT(pesanan.id_pesanan) AS total_pesanan, COALESCE(SUM(pesanan.total_harga), 0) AS total_belanja
    FROM pembeli p
    LEFT JOIN pesanan ON p.id_pembeli = pesanan.id_pembeli
    GROUP BY p.id_pembeli
    ORDER BY p.id_pembeli DESC
");
$pembeliList = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="index.php" class="btn btn-outline-light btn-sm" style="padding: 0.2rem 0.6rem;">← Dashboard</a>
            <span class="badge-kategori">DATA PELANGGAN KANTIN</span>
        </div>
        <h1 class="text-white font-weight-700 mb-0">Data Pembeli / Pelanggan</h1>
        <p class="text-muted" style="font-size: 0.9rem;">Daftar seluruh pengguna pembeli yang telah terdaftar di sistem E-Kantin</p>
    </div>
</div>

<div class="glass-card p-4 animate-fade-in">
    <?php if (empty($pembeliList)): ?>
        <p class="text-muted text-center py-5 mb-0">Belum ada pembeli terdaftar saat ini.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Pembeli</th>
                        <th>Username</th>
                        <th>No. Handphone / WA</th>
                        <th>Total Pemesanan</th>
                        <th>Total Transaksi</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pembeliList as $user): ?>
                        <tr>
                            <td>#<?= $user['id_pembeli'] ?></td>
                            <td><strong class="text-white"><?= sanitize($user['nama']) ?></strong></td>
                            <td class="text-muted font-mono"><?= sanitize($user['username']) ?></td>
                            <td class="text-muted font-mono"><?= sanitize($user['no_hp']) ?></td>
                            <td><strong class="text-white"><?= number_format($user['total_pesanan']) ?></strong> transaksi</td>
                            <td><strong class="text-gradient"><?= format_rupiah($user['total_belanja']) ?></strong></td>
                            <td class="text-center">
                                <form method="POST" action="pembeli.php" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data pembeli <?= sanitize($user['nama']) ?>? Seluruh riwayat pesanan nya juga akan dihapus.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id_pembeli" value="<?= $user['id_pembeli'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
