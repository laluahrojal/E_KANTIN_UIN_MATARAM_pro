<?php
// admin/index.php - High-Tech TI Admin Dashboard
$pageTitle = "Dashboard Admin TI - E-Kantin";
require_once __DIR__ . '/../config/database.php';

require_admin();

$pdo = db();

// Metrics queries
$totalPendapatan = $pdo->query("SELECT SUM(total_harga) FROM pesanan")->fetchColumn() ?: 0;
$totalPesanan = $pdo->query("SELECT COUNT(*) FROM pesanan")->fetchColumn() ?: 0;
$totalMenu = $pdo->query("SELECT COUNT(*) FROM menu")->fetchColumn() ?: 0;
$totalPembeli = $pdo->query("SELECT COUNT(*) FROM pembeli")->fetchColumn() ?: 0;

// Fetch 5 recent orders
$stmtRecent = $pdo->query("
    SELECT p.*, pembeli.nama AS nama_pembeli, menu.nama_menu
    FROM pesanan p
    JOIN pembeli ON p.id_pembeli = pembeli.id_pembeli
    JOIN menu ON p.id_menu = menu.id_menu
    ORDER BY p.tanggal DESC
    LIMIT 5
");
$recentOrders = $stmtRecent->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- TI Admin Welcome Header -->
<div class="glass-card tech-banner p-4 mb-4 animate-fade-in">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="brand-icon" style="width: 54px; height: 54px; font-size: 1.8rem; background: linear-gradient(135deg, #6366f1, #06b6d4);">
                
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge-stok available" style="font-size: 0.75rem;">LIVE SYSTEM TI</span>
                    <span class="text-muted" style="font-size: 0.85rem;"><?= date('l, d F Y') ?></span>
                </div>
                <h1 class="text-white font-weight-700 mb-0" style="font-size: 1.75rem;">
                    Portal Pengelola <span class="text-gradient">E-Kantin Teknologi Informasi</span>
                </h1>
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="menu.php" class="btn btn-primary btn-sm shadow-glow">
                Kelola Menu
            </a>
            <a href="pengaturan.php" class="btn btn-accent btn-sm shadow-glow">
                 Upload Logo & QRIS
            </a>
            <a href="pesanan.php" class="btn btn-outline-light btn-sm">
                 Data Transaksi
            </a>
            <a href="pembeli.php" class="btn btn-outline-light btn-sm">
                 Data Pembeli
            </a>
        </div>
    </div>
</div>

<!-- Stat Cards Grid -->
<div class="grid-stats mb-4 animate-fade-in">
    <a href="pesanan.php" style="text-decoration: none;" class="glass-card p-4 d-flex align-items-center gap-3 glass-card-hover">
        <div class="brand-icon" style="background: linear-gradient(135deg, #10b981, #059669); width: 52px; height: 52px; font-size: 1.5rem;">
            
        </div>
        <div>
            <span class="text-muted" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Total Pendapatan</span>
            <h3 class="text-gradient font-weight-700" style="font-size: 1.6rem;"><?= format_rupiah($totalPendapatan) ?></h3>
        </div>
    </a>

    <a href="pesanan.php" style="text-decoration: none;" class="glass-card p-4 d-flex align-items-center gap-3 glass-card-hover">
        <div class="brand-icon" style="background: linear-gradient(135deg, #6366f1, #4f46e5); width: 52px; height: 52px; font-size: 1.5rem;">
            
        </div>
        <div>
            <span class="text-muted" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Total Pesanan</span>
            <h3 class="text-white font-weight-700" style="font-size: 1.6rem;"><?= number_format($totalPesanan) ?> <small style="font-size: 0.9rem;" class="text-muted">Transaksi</small></h3>
        </div>
    </a>

    <a href="menu.php" style="text-decoration: none;" class="glass-card p-4 d-flex align-items-center gap-3 glass-card-hover">
        <div class="brand-icon" style="background: linear-gradient(135deg, #06b6d4, #0284c7); width: 52px; height: 52px; font-size: 1.5rem;">
            
        </div>
        <div>
            <span class="text-muted" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Menu Aktif</span>
            <h3 class="text-white font-weight-700" style="font-size: 1.6rem;"><?= number_format($totalMenu) ?> <small style="font-size: 0.9rem;" class="text-muted">Item</small></h3>
        </div>
    </a>

    <a href="pembeli.php" style="text-decoration: none;" class="glass-card p-4 d-flex align-items-center gap-3 glass-card-hover">
        <div class="brand-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706); width: 52px; height: 52px; font-size: 1.5rem;">
        </div>
        <div>
            <span class="text-muted" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Pelanggan Pembeli</span>
            <h3 class="text-white font-weight-700" style="font-size: 1.6rem;"><?= number_format($totalPembeli) ?> <small style="font-size: 0.9rem;" class="text-muted">Orang</small></h3>
        </div>
    </a>
</div>

<!-- Recent Transactions Feed Table -->
<div class="glass-card p-4 animate-fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge-kategori">LIVE MONITOR</span>
                <h3 class="text-white font-weight-700 mb-0">Transaksi Pesanan Terbaru</h3>
            </div>
            <p class="text-muted" style="font-size: 0.9rem;">5 Data aktivitas pemesanan terakhir masuk dari pembeli</p>
        </div>
        <a href="pesanan.php" class="btn btn-outline-light btn-sm">
            Lihat Semua Pesanan (<?= number_format($totalPesanan) ?>) →
        </a>
    </div>

    <?php if (empty($recentOrders)): ?>
        <div class="text-center py-5">
            <p class="text-muted mb-0">Belum ada transaksi pemesanan masuk saat ini.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>No. Struk</th>
                        <th>Waktu Transaksi</th>
                        <th>Nama Pembeli</th>
                        <th>Menu Pesanan</th>
                        <th>Metode Bayar</th>
                        <th>Porsi</th>
                        <th>Total Harga</th>
                        <th class="text-center">Struk Digital</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $ord): ?>
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
                            <td><?= sanitize($ord['nama_menu']) ?></td>
                            <td>
                                <?php if (($ord['metode_pembayaran'] ?? 'Cash') === 'QRIS'): ?>
                                    <span class="badge-stok available" style="background: rgba(99, 102, 241, 0.2); color: #a5b4fc; border-color: rgba(99, 102, 241, 0.4); font-size: 0.75rem;">
                                        QRIS
                                    </span>
                                <?php else: ?>
                                    <span class="badge-stok available" style="font-size: 0.75rem;">
                                        Cash
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><strong class="text-white"><?= $ord['jumlah'] ?></strong> porsi</td>
                            <td><strong class="text-gradient" style="font-size: 1.05rem;"><?= format_rupiah($ord['total_harga']) ?></strong></td>
                            <td class="text-center">
                                <a href="<?= get_base_url() ?>struk.php?id=<?= $ord['id_pesanan'] ?>" class="btn btn-outline-light btn-sm" target="_blank">
                                     Cetak Struk
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
