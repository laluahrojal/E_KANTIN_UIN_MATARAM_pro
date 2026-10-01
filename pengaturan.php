<?php
// admin/pengaturan.php - Admin Management for Logo & QRIS Payment
$pageTitle = "Pengaturan Logo & QRIS - Admin TI";
require_once __DIR__ . '/../config/database.php';

require_admin();

$pdo = db();

$logoDir = __DIR__ . '/../uploads/logo/';
$qrisDir = __DIR__ . '/../uploads/qris/';

if (!file_exists($logoDir)) @mkdir($logoDir, 0777, true);
if (!file_exists($qrisDir)) @mkdir($qrisDir, 0777, true);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');

    // 1. UPDATE KANTIN NAME & SETTINGS
    if ($action === 'update_general') {
        $namaKantin = sanitize($_POST['nama_kantin'] ?? 'E-Kantin TI');
        set_setting('nama_kantin', $namaKantin);
        set_flash('success', 'Pengaturan umum nama kantin berhasil diperbarui!');
        header("Location: pengaturan.php");
        exit;
    }

    // 2. UPLOAD / UPDATE LOGO KANTIN
    if ($action === 'upload_logo') {
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['logo_file']['tmp_name'];
            $fileName = $_FILES['logo_file']['name'];
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg'];

            if (in_array($ext, $allowed)) {
                $newFileName = 'logo_' . time() . '_' . rand(100, 999) . '.' . $ext;
                $targetPath = $logoDir . $newFileName;

                if (move_uploaded_file($fileTmp, $targetPath)) {
                    // Delete old logo file if exists
                    $oldLogo = get_setting('logo_kantin');
                    if (!empty($oldLogo) && file_exists($logoDir . $oldLogo)) {
                        @unlink($logoDir . $oldLogo);
                    }

                    set_setting('logo_kantin', $newFileName);
                    set_flash('success', 'Logo Kantin berhasil diunggah!');
                } else {
                    set_flash('danger', 'Gagal memindahkan file logo yang diunggah.');
                }
            } else {
                set_flash('danger', 'Format file logo tidak valid! Gunakan JPG, PNG, WEBP, atau SVG.');
            }
        } else {
            set_flash('danger', 'Silakan pilih file logo terlebih dahulu.');
        }
        header("Location: pengaturan.php");
        exit;
    }

    // 3. DELETE LOGO KANTIN
    if ($action === 'delete_logo') {
        $oldLogo = get_setting('logo_kantin');
        if (!empty($oldLogo) && file_exists($logoDir . $oldLogo)) {
            @unlink($logoDir . $oldLogo);
        }
        set_setting('logo_kantin', '');
        set_flash('success', 'Logo Kantin berhasil dihapus!');
        header("Location: pengaturan.php");
        exit;
    }

    // 4. UPLOAD / UPDATE QRIS IMAGE & PAYMENT DETAILS
    if ($action === 'upload_qris') {
        $qrisNama = sanitize($_POST['qris_nama_pemilik'] ?? '');
        $qrisCatatan = sanitize($_POST['qris_catatan'] ?? '');

        set_setting('qris_nama_pemilik', $qrisNama);
        set_setting('qris_catatan', $qrisCatatan);

        if (isset($_FILES['qris_file']) && $_FILES['qris_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['qris_file']['tmp_name'];
            $fileName = $_FILES['qris_file']['name'];
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($ext, $allowed)) {
                $newFileName = 'qris_' . time() . '_' . rand(100, 999) . '.' . $ext;
                $targetPath = $qrisDir . $newFileName;

                if (move_uploaded_file($fileTmp, $targetPath)) {
                    // Delete old QRIS image file if exists
                    $oldQris = get_setting('qris_image');
                    if (!empty($oldQris) && file_exists($qrisDir . $oldQris)) {
                        @unlink($qrisDir . $oldQris);
                    }

                    set_setting('qris_image', $newFileName);
                    set_flash('success', 'Gambar QRIS Pembayaran berhasil diunggah & berlaku untuk seluruh transaksi pesanan!');
                } else {
                    set_flash('danger', 'Gagal memindahkan file gambar QRIS.');
                }
            } else {
                set_flash('danger', 'Format gambar QRIS tidak valid! Gunakan JPG, PNG, atau WEBP.');
            }
        } else {
            set_flash('success', 'Informasi QRIS Pembayaran berhasil diperbarui!');
        }

        header("Location: pengaturan.php");
        exit;
    }

    // 5. DELETE QRIS IMAGE
    if ($action === 'delete_qris') {
        $oldQris = get_setting('qris_image');
        if (!empty($oldQris) && file_exists($qrisDir . $oldQris)) {
            @unlink($qrisDir . $oldQris);
        }
        set_setting('qris_image', '');
        set_flash('success', 'Gambar QRIS berhasil dihapus!');
        header("Location: pengaturan.php");
        exit;
    }
}

// Current Settings
$logoKantin = get_setting('logo_kantin', '');
$qrisImage = get_setting('qris_image', '');
$qrisNama = get_setting('qris_nama_pemilik', 'Kantin Teknologi Informasi');
$qrisCatatan = get_setting('qris_catatan', 'Scan QRIS untuk pembayaran instan via BCA, GoPay, OVO, Dana, LinkAja, atau m-Banking');
$namaKantin = get_setting('nama_kantin', 'E-Kantin TI');

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="index.php" class="btn btn-outline-light btn-sm" style="padding: 0.2rem 0.6rem;">← Dashboard</a>
            <span class="badge-kategori">KONFIGURASI ADMIN</span>
        </div>
        <h1 class="text-white font-weight-700 mb-0">Pengaturan Logo & QRIS Pembayaran</h1>
        <p class="text-muted" style="font-size: 0.9rem;">Kelola identitas logo kantin dan unggah barcode QRIS otomatis untuk seluruh pesanan pembeli</p>
    </div>
</div>

<div class="row" style="display: flex; flex-wrap: wrap; gap: 1.5rem;">
    <!-- LOGO KANTIN SECTION -->
    <div style="flex: 1; min-width: 320px;">
        <div class="glass-card p-4 animate-fade-in h-100">
            <div class="d-flex align-items-center gap-3 mb-3 border-bottom-glass pb-3" style="border-bottom: 1px solid var(--border-glass);">
                <div class="brand-icon" style="background: linear-gradient(135deg, #6366f1, #a855f7); width: 46px; height: 46px; font-size: 1.4rem;">
                </div>
                <div>
                    <h3 class="text-white font-weight-700 mb-0">Upload Logo Kantin</h3>
                    <p class="text-muted mb-0" style="font-size: 0.85rem;">Logo akan muncul di Navbar, Banner, dan Struk Digital</p>
                </div>
            </div>

            <!-- Current Logo Preview -->
            <div class="text-center p-4 mb-4" style="background: rgba(15, 23, 42, 0.6); border: 2px dashed var(--border-glass-light); border-radius: var(--radius-md);">
                <?php if (!empty($logoKantin) && file_exists($logoDir . $logoKantin)): ?>
                    <img src="<?= get_base_url() ?>uploads/logo/<?= sanitize($logoKantin) ?>" 
                         alt="Logo Kantin" 
                         style="max-height: 110px; max-width: 100%; object-fit: contain; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.5));" class="mb-3">
                    <p class="text-muted small mb-3">File aktif: <code class="text-gradient"><?= sanitize($logoKantin) ?></code></p>
                    <form method="POST" action="pengaturan.php" onsubmit="return confirm('Hapus logo kantin saat ini?');">
                        <input type="hidden" name="action" value="delete_logo">
                        <button type="submit" class="btn btn-danger btn-sm">Hapus Logo</button>
                    </form>
                <?php else: ?>
                    <div style="font-size: 3rem; margin-bottom: 0.5rem; opacity: 0.6;"></div>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">Belum ada logo kantin diunggah.<br>Menggunakan ikon default E-Kantin TI.</p>
                <?php endif; ?>
            </div>

            <!-- Form Upload Logo -->
            <form method="POST" action="pengaturan.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_logo">
                <div class="form-group mb-3">
                    <label class="form-label">Pilih File Logo Baru</label>
                    <input type="file" name="logo_file" class="form-control" accept="image/jpeg,image/png,image/webp,image/svg+xml" required>
                    <small class="text-muted d-block mt-1">Format disarankan: PNG Transparan atau SVG. Maksimal 5MB.</small>
                </div>
                <button type="submit" class="btn btn-primary w-100 shadow-glow">
                    Unggah Logo Kantin
                </button>
            </form>
        </div>
    </div>

    <!-- QRIS PAYMENT SECTION -->
    <div style="flex: 1; min-width: 320px;">
        <div class="glass-card p-4 animate-fade-in h-100">
            <div class="d-flex align-items-center gap-3 mb-3 border-bottom-glass pb-3" style="border-bottom: 1px solid var(--border-glass);">
                <div class="brand-icon" style="background: linear-gradient(135deg, #06b6d4, #10b981); width: 46px; height: 46px; font-size: 1.4rem;">
                </div>
                <div>
                    <h3 class="text-white font-weight-700 mb-0">Upload QRIS Pembayaran</h3>
                    <p class="text-muted mb-0" style="font-size: 0.85rem;">Sekali upload otomatis muncul di semua form pesanan QRIS & struk</p>
                </div>
            </div>

            <!-- Current QRIS Preview -->
            <div class="text-center p-3 mb-4" style="background: rgba(15, 23, 42, 0.6); border: 2px dashed var(--border-glass-light); border-radius: var(--radius-md);">
                <?php if (!empty($qrisImage) && file_exists($qrisDir . $qrisImage)): ?>
                    <div style="background: white; padding: 12px; border-radius: 12px; display: inline-block; max-width: 220px;" class="mb-3">
                        <img src="<?= get_base_url() ?>uploads/qris/<?= sanitize($qrisImage) ?>" 
                             alt="QRIS Barcode Pembayaran" 
                             style="width: 100%; max-height: 200px; object-fit: contain;">
                    </div>
                    <div class="mb-2">
                        <span class="badge-stok available">QRIS Aktif Otomatis</span>
                    </div>
                    <p class="text-white font-weight-700 mb-1"><?= sanitize($qrisNama) ?></p>
                    <p class="text-muted small mb-3"><?= sanitize($qrisCatatan) ?></p>
                    <form method="POST" action="pengaturan.php" onsubmit="return confirm('Hapus gambar QRIS pembayaran?');">
                        <input type="hidden" name="action" value="delete_qris">
                        <button type="submit" class="btn btn-danger btn-sm">Hapus Gambar QRIS</button>
                    </form>
                <?php else: ?>
                    <div style="font-size: 3rem; margin-bottom: 0.5rem; opacity: 0.6;"></div>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">Belum ada gambar QRIS diunggah.<br>Upload barcode QRIS toko Anda di bawah ini!</p>
                <?php endif; ?>
            </div>

            <!-- Form Upload QRIS -->
            <form method="POST" action="pengaturan.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_qris">
                
                <div class="form-group mb-3">
                    <label class="form-label">Nama Merchant / Pemilik QRIS</label>
                    <input type="text" name="qris_nama_pemilik" class="form-control" value="<?= sanitize($qrisNama) ?>" placeholder="Contoh: KANTIN TI - UNIVERISTAS" required>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">Petunjuk / Catatan Pembayaran QRIS</label>
                    <input type="text" name="qris_catatan" class="form-control" value="<?= sanitize($qrisCatatan) ?>" placeholder="Contoh: Scan QRIS via GoPay, ShopeePay, m-Banking">
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">Upload Gambar / Barcode QRIS Baru</label>
                    <input type="file" name="qris_file" class="form-control" accept="image/jpeg,image/png,image/webp" <?= empty($qrisImage) ? 'required' : '' ?>>
                    <small class="text-muted d-block mt-1">Format: JPG, PNG, WEBP. Sekali diunggah langsung otomatis tampil di semua pesanan QRIS!</small>
                </div>

                <button type="submit" class="btn btn-primary w-100 shadow-glow">
                    Simpan & Update Data QRIS
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
