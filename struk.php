<?php
// struk.php - Digital Receipt View & Print Page
$pageTitle = "Struk Pesanan Digital";
require_once __DIR__ . '/config/database.php';

// Fetch order details
$id_pesanan = (int)($_GET['id'] ?? 0);
if ($id_pesanan <= 0) {
    set_flash('danger', 'ID Struk pesanan tidak ditemukan.');
    header("Location: index.php");
    exit;
}

$pdo = db();
$stmt = $pdo->prepare("
    SELECT p.*, COALESCE(NULLIF(p.nama_pemesan, ''), pembeli.nama) AS nama_pembeli, pembeli.no_hp, menu.nama_menu, menu.kategori, menu.harga AS harga_satuan
    FROM pesanan p
    JOIN pembeli ON p.id_pembeli = pembeli.id_pembeli
    JOIN menu ON p.id_menu = menu.id_menu
    WHERE p.id_pesanan = ?
");
$stmt->execute([$id_pesanan]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('danger', 'Detail pesanan tidak ditemukan atau Anda tidak memiliki akses.');
    header("Location: index.php");
    exit;
}

$logoKantin = get_setting('logo_kantin', '');
$qrisImage = get_setting('qris_image', '');
$qrisNama = get_setting('qris_nama_pemilik', 'Kantin TI');
$qrisCatatan = get_setting('qris_catatan', '');
$namaKantin = get_setting('nama_kantin', 'E-KANTIN TI');
$statusPesanan = $order['status_pesanan'] ?? 'Selesai';
$metodeBayar = $order['metode_pembayaran'] ?? 'Cash';

require_once __DIR__ . '/includes/header.php';
?>

<div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 max-w-500 mx-auto no-print">
        <a href="index.php" class="btn btn-outline-light btn-sm">
            ← Kembali ke Katalog
        </a>
        <a href="riwayat.php" class="btn btn-outline-light btn-sm">
            Riwayat Pesanan
        </a>
        <button id="btnPrintStruk" class="btn btn-primary btn-sm shadow-glow" onclick="window.print();">
            Cetak Struk
        </button>
    </div>

    <!-- Struk Paper container -->
    <div class="receipt-paper animate-fade-in">
        <div class="receipt-header">
            <?php if (!empty($logoKantin) && file_exists(__DIR__ . '/uploads/logo/' . $logoKantin)): ?>
                <div style="margin-bottom: 0.75rem;">
                    <img src="<?= get_base_url() ?>uploads/logo/<?= sanitize($logoKantin) ?>" alt="Logo Kantin" style="max-height: 65px; max-width: 160px; object-fit: contain;">
                </div>
            <?php endif; ?>

            <h2><?= strtoupper(sanitize($namaKantin)) ?></h2>
            <p style="font-size: 0.85rem; color: #64748b; margin-top: 0.25rem;">Kantin Digital Modern & Fast Service</p>
            <p style="font-size: 0.8rem; color: #94a3b8; margin-top: 0.5rem;">
                No. Struk: <strong>#STR-<?= str_pad($order['id_pesanan'], 5, '0', STR_PAD_LEFT) ?></strong>
            </p>
        </div>

        <div style="font-size: 0.85rem; line-height: 1.6;">
            <div class="d-flex justify-content-between mb-1">
                <span>Tanggal:</span>
                <strong><?= date('d/m/Y H:i', strtotime($order['tanggal'])) ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span>Pembeli:</span>
                <strong><?= sanitize($order['nama_pembeli']) ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span>No. HP:</span>
                <strong><?= sanitize($order['no_hp']) ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span>Pembayaran:</span>
                <strong style="color: <?= $metodeBayar === 'QRIS' ? '#4f46e5' : '#059669' ?>;">
                    <?= $metodeBayar === 'QRIS' ? 'QRIS (Digital)' : 'Cash (Tunai)' ?>
                </strong>
            </div>
            <div class="d-flex justify-content-between">
                <span>Status Pesanan:</span>
                <strong style="color: <?= $statusPesanan === 'Selesai' ? '#16a34a' : '#d97706' ?>;">
                    <?= $statusPesanan === 'Selesai' ? 'SELESAI / LUNAS' : 'DIPROSES' ?>
                </strong>
            </div>
        </div>

        <div class="receipt-divider"></div>

        <table class="receipt-table">
            <thead>
                <tr style="text-align: left; font-size: 0.85rem; color: #64748b;">
                    <th>Item Menu</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr style="font-size: 0.9rem;">
                    <td>
                        <strong><?= sanitize($order['nama_menu']) ?></strong><br>
                        <small style="color: #64748b;"><?= format_rupiah($order['harga_satuan']) ?> / porsi</small>
                    </td>
                    <td style="text-align: center; vertical-align: top;">
                        <?= $order['jumlah'] ?>x
                    </td>
                    <td style="text-align: right; vertical-align: top;">
                        <strong><?= format_rupiah($order['total_harga']) ?></strong>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="receipt-divider"></div>

        <div class="d-flex justify-content-between align-items-center" style="font-size: 1.1rem; font-weight: 700; color: #0f172a;">
            <span>TOTAL BAYAR:</span>
            <span><?= format_rupiah($order['total_harga']) ?></span>
        </div>

        <?php if ($metodeBayar === 'QRIS'): ?>
            <div class="receipt-divider"></div>
            <div class="text-center" style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <p style="font-size: 0.8rem; font-weight: 700; color: #1e293b; margin-bottom: 4px;">PEMBAYARAN QRIS OTOMATIS</p>
                <p style="font-size: 0.75rem; color: #64748b; margin-bottom: 8px;"><?= sanitize($qrisNama) ?></p>

                <?php if (!empty($qrisImage) && file_exists(__DIR__ . '/uploads/qris/' . $qrisImage)): ?>
                    <img src="<?= get_base_url() ?>uploads/qris/<?= sanitize($qrisImage) ?>" alt="QRIS Code" style="max-width: 150px; max-height: 150px; object-fit: contain; border-radius: 6px; border: 1px solid #cbd5e1; padding: 4px; background: white;">
                    <?php if (!empty($qrisCatatan)): ?>
                        <p style="font-size: 0.7rem; color: #64748b; margin-top: 6px; margin-bottom: 0;"><?= sanitize($qrisCatatan) ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p style="font-size: 0.75rem; color: #94a3b8; margin-bottom: 0;">Scan QRIS di meja kasir kantin</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="receipt-divider"></div>

        <div class="text-center" style="font-size: 0.8rem; color: #64748b; margin-top: 1rem;">
            <p>-- Terima kasih atas pesanan Anda! --</p>
            <p>Silakan tunjukkan struk ini saat mengambil makanan di kasir kantin.</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
