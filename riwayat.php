<?php
// riwayat.php - Buyer Order History Page & Status Tracking (Linked with Session Name & Phone)
$pageTitle = "Riwayat Pesanan Saya";
require_once __DIR__ . '/config/database.php';

$pdo = db();

// Get active session buyer or search filter
$userId = $_SESSION['user_id'] ?? 0;
$userNama = $_SESSION['user_nama'] ?? '';
$userHp = $_SESSION['user_hp'] ?? '';

// Auto resolve userId from session name/hp if userId is not set
if ($userId <= 0 && (!empty($userNama) || !empty($userHp))) {
    $searchKey = !empty($userHp) ? $userHp : $userNama;
    $stmtFindSess = $pdo->prepare("SELECT id_pembeli, nama, no_hp FROM pembeli WHERE no_hp = ? OR nama = ? OR username = ? ORDER BY id_pembeli DESC LIMIT 1");
    $stmtFindSess->execute([$searchKey, $searchKey, $searchKey]);
    $foundSess = $stmtFindSess->fetch();
    if ($foundSess) {
        $userId = $foundSess['id_pembeli'];
        $_SESSION['user_id'] = $userId;
        $userNama = $foundSess['nama'];
        $userHp = $foundSess['no_hp'];
    }
}

$searchHp = sanitize($_GET['hp'] ?? $_POST['no_hp'] ?? '');
$searchQuery = sanitize($_GET['search'] ?? '');

// Handle phone/name search POST form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'search_hp') {
    $inputHp = sanitize($_POST['no_hp'] ?? '');
    if (!empty($inputHp)) {
        // Find user by name, phone number, or username
        $stmtFind = $pdo->prepare("SELECT id_pembeli, nama, no_hp FROM pembeli WHERE no_hp LIKE ? OR nama LIKE ? OR username LIKE ? ORDER BY id_pembeli DESC LIMIT 1");
        $stmtFind->execute(['%' . $inputHp . '%', '%' . $inputHp . '%', '%' . $inputHp . '%']);
        $foundPembeli = $stmtFind->fetch();

        if ($foundPembeli) {
            $_SESSION['user_role'] = 'pembeli';
            $_SESSION['user_id'] = $foundPembeli['id_pembeli'];
            $_SESSION['user_nama'] = $foundPembeli['nama'];
            $_SESSION['user_hp'] = $foundPembeli['no_hp'];
            
            $userId = $foundPembeli['id_pembeli'];
            $userNama = $foundPembeli['nama'];
            $userHp = $foundPembeli['no_hp'];
            set_flash('success', 'Riwayat pesanan ditemukan untuk ' . sanitize($userNama) . ' (' . sanitize($userHp) . ').');
        } else {
            $searchHp = $inputHp;
            set_flash('warning', 'Hasil pencarian untuk nama / nomor HP: ' . sanitize($inputHp));
        }
    }
}

// Fetch order history
$orders = [];
$pembeliInfo = null;

if ($userId > 0) {
    // Fetch buyer account details
    $stmtBuyer = $pdo->prepare("SELECT * FROM pembeli WHERE id_pembeli = ?");
    $stmtBuyer->execute([$userId]);
    $pembeliInfo = $stmtBuyer->fetch();

    $query = "
        SELECT p.*, COALESCE(NULLIF(p.nama_pemesan, ''), pembeli.nama) AS nama_pembeli, pembeli.no_hp AS no_hp_pembeli, menu.nama_menu, menu.kategori, menu.harga AS harga_satuan, menu.foto
        FROM pesanan p
        JOIN pembeli ON p.id_pembeli = pembeli.id_pembeli
        JOIN menu ON p.id_menu = menu.id_menu
        WHERE p.id_pembeli = ?
    ";
    $params = [$userId];

    if (!empty($searchQuery)) {
        $query .= " AND (menu.nama_menu LIKE ? OR p.id_pesanan LIKE ? OR p.metode_pembayaran LIKE ? OR p.status_pesanan LIKE ? OR pembeli.nama LIKE ? OR pembeli.no_hp LIKE ?)";
        $st = "%" . $searchQuery . "%";
        $params[] = $st; $params[] = $st; $params[] = $st; $params[] = $st; $params[] = $st; $params[] = $st;
    }

    $query .= " ORDER BY p.tanggal DESC";
    $stmtOrders = $pdo->prepare($query);
    $stmtOrders->execute($params);
    $orders = $stmtOrders->fetchAll();
} elseif (!empty($searchHp) || !empty($userNama)) {
    $searchKey = !empty($searchHp) ? $searchHp : $userNama;
    $query = "
        SELECT p.*, COALESCE(NULLIF(p.nama_pemesan, ''), pembeli.nama) AS nama_pembeli, pembeli.no_hp AS no_hp_pembeli, menu.nama_menu, menu.kategori, menu.harga AS harga_satuan, menu.foto
        FROM pesanan p
        JOIN pembeli ON p.id_pembeli = pembeli.id_pembeli
        JOIN menu ON p.id_menu = menu.id_menu
        WHERE (pembeli.no_hp LIKE ? OR pembeli.nama LIKE ? OR pembeli.username LIKE ?)
    ";
    $stHp = "%" . $searchKey . "%";
    $params = [$stHp, $stHp, $stHp];

    if (!empty($searchQuery)) {
        $query .= " AND (menu.nama_menu LIKE ? OR p.id_pesanan LIKE ? OR p.metode_pembayaran LIKE ? OR p.status_pesanan LIKE ?)";
        $st = "%" . $searchQuery . "%";
        $params[] = $st; $params[] = $st; $params[] = $st; $params[] = $st;
    }

    $query .= " ORDER BY p.tanggal DESC";
    $stmtOrders = $pdo->prepare($query);
    $stmtOrders->execute($params);
    $orders = $stmtOrders->fetchAll();
}

// Calculate Statistics
$totalPesanan = count($orders);
$totalPengeluaran = 0;
$totalSelesai = 0;
$totalDiproses = 0;

foreach ($orders as $o) {
    $totalPengeluaran += (float)$o['total_harga'];
    if (($o['status_pesanan'] ?? 'Selesai') === 'Selesai') {
        $totalSelesai++;
    } else {
        $totalDiproses++;
    }
}

$qrisImage = get_setting('qris_image', '');
$qrisNama = get_setting('qris_nama_pemilik', 'Kantin TI');
$qrisCatatan = get_setting('qris_catatan', 'Scan QRIS untuk pembayaran');

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Banner -->
<div class="tech-banner p-4 p-md-5 mb-4 animate-fade-in position-relative overflow-hidden">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <span class="badge-kategori">DASHBOARD RIWAYAT PESANAN</span>
                <?php if (!empty($userNama)): ?>
                    <span class="badge-stok available" style="font-size: 0.85rem; padding: 0.35rem 0.75rem;">
                        Sesi Pemesan: <strong><?= sanitize($userNama) ?></strong> <?= !empty($userHp) ? '(' . sanitize($userHp) . ')' : '' ?>
                    </span>
                <?php endif; ?>
            </div>
            <h1 class="text-white font-weight-700 mb-1" style="font-size: 2rem;">
                Riwayat & Status Pesanan <span class="text-gradient">Saya</span>
            </h1>
            <p class="text-muted mb-0" style="font-size: 0.95rem;">
                Pantau proses pesanan kantin Anda, cetak struk digital, dan periksa status pembayaran secara langsung.
            </p>
        </div>

        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-primary shadow-glow">
                Pesan Menu Baru
            </a>
        </div>
    </div>
</div>

<!-- Guest / Phone & Name Lookup Card -->
<div class="glass-card p-4 mb-4 animate-fade-in" style="background: rgba(15, 23, 42, 0.65); border: 1px solid var(--border-glass-light);">
    <div class="row align-items-center">
        <form method="POST" action="riwayat.php" class="d-flex flex-wrap align-items-end gap-3 w-100">
            <input type="hidden" name="action" value="search_hp">
            <div class="flex-grow-1" style="min-width: 260px;">
                <label class="form-label mb-1 text-white">
                    Cari Pesanan Berdasarkan Nama Pemesan / No. HP / WhatsApp
                </label>
                <input type="text" name="no_hp" id="searchHpInput" class="form-control" placeholder="Masukkan Nama Pemesan atau No. HP..." value="<?= sanitize($searchHp ?: ($userNama ?: $userHp)) ?>" required>
            </div>
            <div>
                <button type="submit" class="btn btn-accent shadow-glow">
                    Lacak Pesanan Sesi
                </button>
            </div>
            <?php if ($userId > 0 || !empty($searchHp) || !empty($userNama)): ?>
                <div>
                    <a href="riwayat.php" class="btn btn-outline-light btn-sm" style="padding: 0.75rem 1rem;">
                        Reset Filter
                    </a>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if ($userId > 0 || !empty($searchHp) || !empty($userNama) || !empty($orders)): ?>
    <!-- Statistics Summary Cards -->
    <div class="grid-stats mb-4 animate-fade-in">
        <div class="glass-card p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small d-block mb-1">TOTAL PESANAN</span>
                    <h3 class="text-white font-weight-700 mb-0" style="font-size: 1.8rem;"><?= $totalPesanan ?></h3>
                </div>
                <div class="brand-icon" style="width: 48px; height: 48px; background: rgba(99, 102, 241, 0.2); color: #a5b4fc; font-size: 1.4rem;">
                </div>
            </div>
        </div>

        <div class="glass-card p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small d-block mb-1">TOTAL TRANSAKSI</span>
                    <h3 class="text-gradient font-weight-700 mb-0" style="font-size: 1.8rem;"><?= format_rupiah($totalPengeluaran) ?></h3>
                </div>
                <div class="brand-icon" style="width: 48px; height: 48px; background: rgba(6, 182, 212, 0.2); color: #38bdf8; font-size: 1.4rem;">
                </div>
            </div>
        </div>

        <div class="glass-card p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small d-block mb-1">PESANAN DIPROSES</span>
                    <h3 class="text-warning font-weight-700 mb-0" style="font-size: 1.8rem; color: #fcd34d;"><?= $totalDiproses ?></h3>
                </div>
                <div class="brand-icon" style="width: 48px; height: 48px; background: rgba(245, 158, 11, 0.2); color: #fcd34d; font-size: 1.4rem;">
                </div>
            </div>
        </div>

        <div class="glass-card p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small d-block mb-1">PESANAN SELESAI</span>
                    <h3 class="text-success font-weight-700 mb-0" style="font-size: 1.8rem; color: #6ee7b7;"><?= $totalSelesai ?></h3>
                </div>
                <div class="brand-icon" style="width: 48px; height: 48px; background: rgba(16, 185, 129, 0.2); color: #6ee7b7; font-size: 1.4rem;">
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Filter & Orders Table -->
<div class="glass-card p-4 animate-fade-in mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <h3 class="text-white font-weight-700 mb-0" style="font-size: 1.25rem;">
            Daftar Transaksi Pesanan
        </h3>

        <!-- In-Table Search Filter -->
        <form method="GET" action="riwayat.php" class="d-flex gap-2">
            <?php if (!empty($searchHp)): ?>
                <input type="hidden" name="hp" value="<?= sanitize($searchHp) ?>">
            <?php endif; ?>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari menu / nama / No Struk..." value="<?= sanitize($searchQuery) ?>" style="min-width: 240px;">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <?php if (!empty($searchQuery)): ?>
                <a href="riwayat.php<?= !empty($searchHp) ? '?hp=' . urlencode($searchHp) : '' ?>" class="btn btn-outline-light btn-sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <h4 class="text-white font-weight-600 mb-2">Belum ada data pesanan</h4>
            <p class="text-muted max-w-500 mx-auto mb-4">
                <?= ($userId > 0 || !empty($searchHp) || !empty($userNama)) 
                    ? 'Tidak ada riwayat transaksi pesanan yang sesuai dengan kata kunci pencarian.' 
                    : 'Silakan masukkan Nama Pemesan / Nomor HP Anda pada kotak pencarian di atas atau buat pesanan menu baru.' ?>
            </p>
            <a href="index.php" class="btn btn-primary shadow-glow">
                Lihat Katalog Menu Kantin
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>No. Struk</th>
                        <th>Pemesan (Nama / HP)</th>
                        <th>Tanggal & Waktu</th>
                        <th>Menu Pesanan</th>
                        <th>Jumlah & Total</th>
                        <th>Metode Bayar</th>
                        <th>Status Pesanan</th>
                        <th class="text-center">Aksi & Struk</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $ord): ?>
                        <?php 
                            $statusCur = $ord['status_pesanan'] ?? 'Selesai';
                            $metodeBayar = $ord['metode_pembayaran'] ?? 'Cash';
                            $namaPemesanOrd = $ord['nama_pembeli'] ?? ($userNama ?: 'Pemesan Kantin');
                            $hpPemesanOrd   = $ord['no_hp_pembeli'] ?? ($userHp ?: '-');
                        ?>
                        <tr>
                            <td>
                                <a href="struk.php?id=<?= $ord['id_pesanan'] ?>" class="text-gradient font-mono text-decoration-none font-weight-700" style="font-size: 1.05rem;" title="Klik untuk lihat struk">
                                    #STR-<?= str_pad($ord['id_pesanan'], 5, '0', STR_PAD_LEFT) ?>
                                </a>
                            </td>
                            <td>
                                <strong class="text-white d-block" style="font-size: 0.95rem;"><?= sanitize($namaPemesanOrd) ?></strong>
                                <small class="text-muted" style="font-size: 0.825rem;"><?= sanitize($hpPemesanOrd) ?></small>
                            </td>
                            <td class="text-muted" style="font-size: 0.875rem;">
                                <?= date('d/m/Y', strtotime($ord['tanggal'])) ?>
                                <br>
                                <small class="text-dim"><?= date('H:i', strtotime($ord['tanggal'])) ?> WIB</small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if (!empty($ord['foto']) && file_exists(__DIR__ . '/uploads/menu/' . $ord['foto'])): ?>
                                        <img src="<?= get_base_url() ?>uploads/menu/<?= sanitize($ord['foto']) ?>" alt="Foto Menu" style="width: 42px; height: 42px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-glass);">
                                    <?php else: ?>
                                        <div style="width: 42px; height: 42px; background: rgba(255,255,255,0.05); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <strong class="text-white d-block"><?= sanitize($ord['nama_menu']) ?></strong>
                                        <span class="badge-kategori" style="font-size: 0.725rem; padding: 0.15rem 0.5rem;"><?= sanitize($ord['kategori']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <strong class="text-white"><?= $ord['jumlah'] ?> porsi</strong>
                                <br>
                                <span class="text-gradient font-weight-700" style="font-size: 1rem;"><?= format_rupiah($ord['total_harga']) ?></span>
                            </td>
                            <td>
                                <?php if ($metodeBayar === 'QRIS'): ?>
                                    <span class="badge-stok available" style="background: rgba(99, 102, 241, 0.2); color: #a5b4fc; border-color: rgba(99, 102, 241, 0.4);">
                                        QRIS (Digital)
                                    </span>
                                <?php else: ?>
                                    <span class="badge-stok available">
                                        Cash (Tunai)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($statusCur === 'Selesai'): ?>
                                    <span class="badge-stok available">
                                        SELESAI / LUNAS
                                    </span>
                                <?php else: ?>
                                    <span class="badge-stok empty" style="background: rgba(245, 158, 11, 0.2); color: #fcd34d; border-color: rgba(245, 158, 11, 0.4);">
                                        DIPROSES
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-2 justify-content-center flex-wrap">
                                    <a href="struk.php?id=<?= $ord['id_pesanan'] ?>" class="btn btn-outline-light btn-sm">
                                        Lihat Struk
                                    </a>

                                    <?php if ($metodeBayar === 'QRIS' && $statusCur !== 'Selesai'): ?>
                                        <button type="button" class="btn btn-accent btn-sm btn-show-qris-modal" 
                                                data-id="<?= $ord['id_pesanan'] ?>"
                                                data-total="<?= format_rupiah($ord['total_harga']) ?>">
                                            QR Code
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Quick QRIS Code View for Unfinished QRIS Orders -->
<div class="modal-overlay" id="qrisViewModal">
    <div class="modal-container text-center max-w-400">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="text-white font-weight-700 mb-0">Pembayaran QRIS Kantin</h3>
            <button class="btn btn-outline-light btn-sm btn-close-modal" style="padding: 0.2rem 0.5rem;">✕</button>
        </div>

        <div class="glass-card p-3 mb-3" style="background: rgba(15, 23, 42, 0.8);">
            <p class="text-muted small mb-1">Total yang harus dibayar:</p>
            <h2 class="text-gradient font-weight-700" id="modalQrisTotal">Rp 0</h2>
            <small class="text-muted d-block mt-1"><?= sanitize($qrisNama) ?></small>
        </div>

        <?php if (!empty($qrisImage) && file_exists(__DIR__ . '/uploads/qris/' . $qrisImage)): ?>
            <div style="background: white; padding: 12px; border-radius: 12px; display: inline-block; margin-bottom: 12px; max-width: 220px; box-shadow: 0 4px 20px rgba(0,0,0,0.6);">
                <img src="<?= get_base_url() ?>uploads/qris/<?= sanitize($qrisImage) ?>" alt="QRIS Code Kantin" style="width: 100%; max-height: 200px; object-fit: contain;">
            </div>
            <p class="text-muted small mb-3"><?= sanitize($qrisCatatan) ?></p>
        <?php else: ?>
            <div class="p-4 mb-3" style="background: rgba(255,255,255,0.05); border-radius: var(--radius-md);">
                <p class="text-muted small mb-0 mt-2">Gambar barcode QRIS dapat di-scan di kasir kantin saat mengambil pesanan.</p>
            </div>
        <?php endif; ?>

        <button type="button" class="btn btn-primary w-100 btn-close-modal">
            Tutup
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const qrisModal = document.getElementById('qrisViewModal');
    const modalQrisTotal = document.getElementById('modalQrisTotal');

    document.querySelectorAll('.btn-show-qris-modal').forEach(btn => {
        btn.addEventListener('click', function () {
            const total = this.dataset.total;
            if (modalQrisTotal) modalQrisTotal.textContent = total;
            if (qrisModal) qrisModal.classList.add('active');
        });
    });

    const searchInput = document.getElementById('searchHpInput');
    if (searchInput && !searchInput.value) {
        const savedHp = localStorage.getItem('kantin_pembeli_hp');
        const savedNama = localStorage.getItem('kantin_pembeli_nama');
        if (savedHp || savedNama) {
            searchInput.value = savedHp || savedNama;
            // Optionally auto submit if no results shown yet
            const hasOrders = <?= empty($orders) ? 'false' : 'true' ?>;
            const hasSearched = <?= ($userId > 0 || !empty($searchHp) || !empty($userNama)) ? 'true' : 'false' ?>;
            if (!hasSearched && !hasOrders) {
                searchInput.closest('form').submit();
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
