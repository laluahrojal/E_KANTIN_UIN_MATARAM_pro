<?php
// index.php - Main Catalog Page for Pembeli (Direct Order Without Mandatory Login)
$pageTitle = "Katalog Menu Kantin";
require_once __DIR__ . '/config/database.php';

$pdo = db();

// Fetch distinct categories
$stmtKategori = $pdo->query("SELECT DISTINCT kategori FROM menu ORDER BY kategori ASC");
$kategoriList = $stmtKategori->fetchAll(PDO::FETCH_COLUMN);

// Fetch all menu items
$stmtMenu = $pdo->query("SELECT * FROM menu ORDER BY kategori ASC, nama_menu ASC");
$menuList = $stmtMenu->fetchAll();

// Menu emoji helper function fallback
function getMenuIcon($kategori, $nama) {
    return '';
}

$qrisImage = get_setting('qris_image', '');
$qrisNama = get_setting('qris_nama_pemilik', 'Kantin TI');
$qrisCatatan = get_setting('qris_catatan', 'Scan QRIS untuk pembayaran instan');

require_once __DIR__ . '/includes/header.php';
?>

<!-- Banner Hero Section -->
<div class="glass-card p-5 mb-5 text-center position-relative overflow-hidden">
    <div class="max-w-600 mx-auto">
        <span class="badge-kategori mb-2">Pesan Instan tanpa Ribet Login</span>
        <h1 class="text-white font-weight-700 mb-3" style="font-size: 2.25rem;">
            Selamat Datang di <span class="text-gradient"><?= sanitize(get_setting('nama_kantin', 'E-Kantin TI')) ?></span>
        </h1>
        <p class="text-muted mb-4">
            Pilih menu favoritmu, tentukan metode pembayaran (Cash atau QRIS), lalu dapatkan struk pesanan digital secara langsung!
        </p>

        <!-- Live Search Bar -->
        <div class="form-group mb-0 max-w-500 mx-auto" style="margin: 0 auto;">
            <input type="text" id="searchMenu" class="form-control form-control-lg" placeholder="Cari menu favoritmu (cth: Nasi Goreng, Es Teh)...">
        </div>
    </div>
</div>

<!-- Category Tabs -->
<div class="d-flex flex-wrap gap-2 mb-4 justify-content-center">
    <button class="btn btn-primary category-tab active" data-category="all">
        Semua Menu (<?= count($menuList) ?>)
    </button>
    <?php foreach ($kategoriList as $kat): ?>
        <button class="btn btn-outline-light category-tab" data-category="<?= sanitize($kat) ?>">
            <?= sanitize($kat) ?>
        </button>
    <?php endforeach; ?>
</div>

<!-- Menu Catalog Grid -->
<div class="grid-menu" id="menuGrid">
    <?php if (empty($menuList)): ?>
        <div class="glass-card p-5 text-center grid-span-all" style="grid-column: 1 / -1;">
            <p class="text-muted mb-0">Belum ada menu yang tersedia saat ini.</p>
        </div>
    <?php else: ?>
        <?php foreach ($menuList as $item): ?>
            <div class="menu-item-wrapper" 
                 data-id="<?= $item['id_menu'] ?>"
                 data-name="<?= sanitize($item['nama_menu']) ?>"
                 data-category="<?= sanitize($item['kategori']) ?>"
                 data-harga="<?= $item['harga'] ?>"
                 data-stok="<?= $item['stok'] ?>">
                
                <div class="glass-card menu-card glass-card-hover">
                    <div class="menu-card-img" style="position: relative; overflow: hidden;">
                        <?php if (!empty($item['foto']) && file_exists(__DIR__ . '/uploads/menu/' . $item['foto'])): ?>
                            <img src="<?= get_base_url() ?>uploads/menu/<?= sanitize($item['foto']) ?>" 
                                 alt="<?= sanitize($item['nama_menu']) ?>" 
                                 style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <span><?= getMenuIcon($item['kategori'], $item['nama_menu']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="menu-card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge-kategori"><?= sanitize($item['kategori']) ?></span>
                            <?php if ($item['stok'] > 0): ?>
                                <span class="badge-stok available">
                                    <svg width="12" height="12" fill="currentColor" viewBox="0 0 16 16"><circle cx="8" cy="8" r="8"/></svg>
                                    Stok: <?= $item['stok'] ?>
                                </span>
                            <?php else: ?>
                                <span class="badge-stok empty">
                                    <svg width="12" height="12" fill="currentColor" viewBox="0 0 16 16"><circle cx="8" cy="8" r="8"/></svg>
                                    Stok Habis
                                </span>
                            <?php endif; ?>
                        </div>

                        <h3 class="menu-title"><?= sanitize($item['nama_menu']) ?></h3>
                        
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top-glass" style="border-top: 1px solid var(--border-glass);">
                            <div class="menu-price"><?= format_rupiah($item['harga']) ?></div>
                            
                            <?php if ($item['stok'] > 0): ?>
                                <button class="btn btn-primary btn-sm btn-open-order"
                                        data-id="<?= $item['id_menu'] ?>"
                                        data-nama="<?= sanitize($item['nama_menu']) ?>"
                                        data-kategori="<?= sanitize($item['kategori']) ?>"
                                        data-harga="<?= $item['harga'] ?>"
                                        data-stok="<?= $item['stok'] ?>">
                                    Pesan
                                </button>
                            <?php else: ?>
                                <button class="btn btn-outline-light btn-sm opacity-50" disabled>
                                    Habis
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal Pemesanan Menu (Langsung tanpa perlu Login) -->
<div class="modal-overlay" id="orderModal">
    <div class="modal-container" style="max-height: 90vh; overflow-y: auto;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="text-white font-weight-700 mb-0">Form Pemesanan Menu</h3>
            <button class="btn btn-outline-light btn-sm btn-close-modal" style="padding: 0.2rem 0.5rem;">✕</button>
        </div>

        <form method="POST" action="pesan.php">
            <input type="hidden" name="id_menu" id="modal_id_menu" value="">

            <div class="glass-card p-3 mb-3" style="background: rgba(15, 23, 42, 0.5);">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="badge-kategori" id="modal_kategori">Kategori</span>
                    <span class="text-muted" style="font-size: 0.85rem;" id="modal_stok_text">Stok: 0</span>
                </div>
                <h4 class="text-white mb-1" id="modal_nama_menu">Nama Menu</h4>
                <div class="text-gradient font-weight-700" id="modal_harga_text" style="font-size: 1.25rem;">Rp 0</div>
            </div>

            <!-- Identitas Pemesan (Langsung isi tanpa login) -->
            <div class="form-group mb-3">
                <label class="form-label">Nama Anda (Pemesan)</label>
                <input type="text" name="nama_pembeli" id="input_nama_pembeli" class="form-control" placeholder="Contoh: Budi Santoso" value="<?= sanitize($_SESSION['user_nama'] ?? '') ?>" required>
            </div>

            <div class="form-group mb-3">
                <label class="form-label">No. Handphone / WhatsApp</label>
                <input type="tel" name="no_hp" id="input_no_hp" class="form-control" placeholder="Contoh: 081234567890" value="<?= sanitize($_SESSION['user_hp'] ?? '') ?>" required>
            </div>

            <div class="form-group mb-3">
                <label class="form-label">Tentukan Jumlah Porsi</label>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-light" id="btnQtyMinus" style="width: 44px; height: 44px; font-size: 1.25rem;">-</button>
                    <input type="number" name="jumlah" id="modal_jumlah" class="form-control text-center font-weight-700" style="font-size: 1.2rem; height: 44px;" value="1" min="1" required>
                    <button type="button" class="btn btn-outline-light" id="btnQtyPlus" style="width: 44px; height: 44px; font-size: 1.25rem;">+</button>
                </div>
            </div>

            <!-- Choice of Payment Method (Cash vs QRIS) -->
            <div class="form-group mb-3">
                <label class="form-label">Pilih Metode Pembayaran</label>
                <div class="payment-selector-grid">
                    <label class="payment-card-option">
                        <input type="radio" name="metode_pembayaran" value="Cash" checked class="radio-payment-input">
                        <div class="payment-card-box">
                            <div class="payment-info">
                                <strong>Cash / Tunai</strong>
                                <small>Bayar langsung di kasir kantin</small>
                            </div>
                        </div>
                    </label>

                    <label class="payment-card-option">
                        <input type="radio" name="metode_pembayaran" value="QRIS" class="radio-payment-input">
                        <div class="payment-card-box">
                            <div class="payment-info">
                                <strong>QRIS / Non-Tunai</strong>
                                <small>Scan QR Code digital instan</small>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- QRIS Barcode Image Preview Container (Visible when QRIS selected) -->
            <div id="qrisContainerModal" class="glass-card p-3 mb-3 text-center" style="display: none; background: rgba(15, 23, 42, 0.8); border: 1px solid var(--accent-glow);">
                <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                    <span class="badge-stok available" style="font-size: 0.75rem;">METODE QRIS OTOMATIS</span>
                </div>
                <strong class="text-white d-block mb-1"><?= sanitize($qrisNama) ?></strong>
                
                <?php if (!empty($qrisImage) && file_exists(__DIR__ . '/uploads/qris/' . $qrisImage)): ?>
                    <div style="background: white; padding: 10px; border-radius: 12px; display: inline-block; margin: 8px 0; max-width: 200px; box-shadow: 0 4px 15px rgba(0,0,0,0.5);">
                        <img src="<?= get_base_url() ?>uploads/qris/<?= sanitize($qrisImage) ?>" alt="QRIS Code Kantin" style="width: 100%; max-height: 180px; object-fit: contain;">
                    </div>
                    <p class="text-muted small mb-0" style="font-size: 0.8rem;"><?= sanitize($qrisCatatan) ?></p>
                <?php else: ?>
                    <div class="p-3 my-2" style="background: rgba(255,255,255,0.05); border-radius: var(--radius-sm);">
                        <p class="text-muted small mb-0 mt-1">Gambar barcode QRIS belum diunggah admin di server.<br>Silakan lakukan pembayaran QRIS di kasir kantin.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="glass-card p-3 mb-3 d-flex justify-content-between align-items-center" style="background: rgba(99, 102, 241, 0.1); border-color: rgba(99, 102, 241, 0.3);">
                <span class="text-muted">Total Bayar:</span>
                <strong class="text-gradient" id="modal_total_text" style="font-size: 1.4rem;">Rp 0</strong>
            </div>

            <button type="submit" id="btnPesanSubmit" class="btn btn-primary btn-lg w-100 shadow-glow">
                Konfirmasi & Pesan Sekarang
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('orderModal');
    const modalIdMenu = document.getElementById('modal_id_menu');
    const modalNamaMenu = document.getElementById('modal_nama_menu');
    const modalKategori = document.getElementById('modal_kategori');
    const modalHargaText = document.getElementById('modal_harga_text');
    const modalStokText = document.getElementById('modal_stok_text');
    const modalJumlah = document.getElementById('modal_jumlah');
    const modalTotalText = document.getElementById('modal_total_text');
    const btnQtyMinus = document.getElementById('btnQtyMinus');
    const btnQtyPlus = document.getElementById('btnQtyPlus');
    const btnCloseModal = document.querySelector('.btn-close-modal');

    let currentHarga = 0;
    let currentStok = 0;

    function formatRupiahJS(angka) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka);
    }

    function updateTotal() {
        let qty = parseInt(modalJumlah.value) || 1;
        if (qty < 1) qty = 1;
        if (qty > currentStok) qty = currentStok;
        modalJumlah.value = qty;
        modalTotalText.textContent = formatRupiahJS(qty * currentHarga);
    }

    const inputNamaPembeli = document.getElementById('input_nama_pembeli');
    const inputNoHp = document.getElementById('input_no_hp');

    function autoFillPembeliData() {
        if (inputNamaPembeli && !inputNamaPembeli.value) {
            const savedNama = localStorage.getItem('kantin_pembeli_nama');
            if (savedNama) inputNamaPembeli.value = savedNama;
        }
        if (inputNoHp && !inputNoHp.value) {
            const savedHp = localStorage.getItem('kantin_pembeli_hp');
            if (savedHp) inputNoHp.value = savedHp;
        }
    }

    // Run autoFill on page load
    autoFillPembeliData();

    // Open Modal
    document.querySelectorAll('.btn-open-order').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const nama = this.dataset.nama;
            const kategori = this.dataset.kategori;
            const harga = parseFloat(this.dataset.harga);
            const stok = parseInt(this.dataset.stok);

            currentHarga = harga;
            currentStok = stok;

            modalIdMenu.value = id;
            modalNamaMenu.textContent = nama;
            modalKategori.textContent = kategori;
            modalHargaText.textContent = formatRupiahJS(harga);
            modalStokText.textContent = 'Stok Tersedia: ' + stok + ' porsi';
            modalJumlah.value = 1;
            modalJumlah.max = stok;

            // Auto-fill buyer details if saved in localStorage
            autoFillPembeliData();

            updateTotal();
            modal.classList.add('active');
        });
    });

    // Save buyer data to localStorage when confirmation button is pressed
    const orderForm = document.querySelector('#orderModal form');
    if (orderForm) {
        orderForm.addEventListener('submit', function () {
            if (inputNamaPembeli && inputNamaPembeli.value) {
                localStorage.setItem('kantin_pembeli_nama', inputNamaPembeli.value.trim());
            }
            if (inputNoHp && inputNoHp.value) {
                localStorage.setItem('kantin_pembeli_hp', inputNoHp.value.trim());
            }
        });
    }

    // Close Modal
    if (btnCloseModal) {
        btnCloseModal.addEventListener('click', function () {
            modal.classList.remove('active');
        });
    }

    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });

    // Qty +/-
    btnQtyMinus.addEventListener('click', function () {
        let val = parseInt(modalJumlah.value) || 1;
        if (val > 1) {
            modalJumlah.value = val - 1;
            updateTotal();
        }
    });

    btnQtyPlus.addEventListener('click', function () {
        let val = parseInt(modalJumlah.value) || 1;
        if (val < currentStok) {
            modalJumlah.value = val + 1;
            updateTotal();
        }
    });

    modalJumlah.addEventListener('input', updateTotal);

    // Live Search
    const searchInput = document.getElementById('searchMenu');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            document.querySelectorAll('.menu-item-wrapper').forEach(item => {
                const name = item.dataset.name.toLowerCase();
                const category = item.dataset.category.toLowerCase();
                if (name.includes(query) || category.includes(query)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    // Category Tabs Filter
    document.querySelectorAll('.category-tab').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active', 'btn-primary'));
            document.querySelectorAll('.category-tab').forEach(t => t.classList.add('btn-outline-light'));
            this.classList.remove('btn-outline-light');
            this.classList.add('active', 'btn-primary');

            const category = this.dataset.category;
            document.querySelectorAll('.menu-item-wrapper').forEach(item => {
                if (category === 'all' || item.dataset.category === category) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });

    // Payment method toggle logic for QRIS preview in order modal
    const radioPayments = document.querySelectorAll('input[name="metode_pembayaran"]');
    const qrisBox = document.getElementById('qrisContainerModal');

    radioPayments.forEach(radio => {
        radio.addEventListener('change', function () {
            if (this.value === 'QRIS') {
                qrisBox.style.display = 'block';
            } else {
                qrisBox.style.display = 'none';
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
