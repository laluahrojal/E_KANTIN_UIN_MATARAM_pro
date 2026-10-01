<?php
// admin/menu.php - Admin Menu CRUD Management & Photo Upload
$pageTitle = "Kelola Menu & Upload Foto - Admin TI";
require_once __DIR__ . '/../config/database.php';

require_admin();

$pdo = db();

$uploadDir = __DIR__ . '/../uploads/menu/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Function to handle image upload
function handlePhotoUpload($fileField, $existingFoto = null) {
    global $uploadDir;
    if (isset($_FILES[$fileField]) && $_FILES[$fileField]['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES[$fileField]['tmp_name'];
        $fileName = $_FILES[$fileField]['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (in_array($ext, $allowed)) {
            $newFileName = 'menu_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $targetPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmp, $targetPath)) {
                // Delete old file if updating
                if (!empty($existingFoto) && file_exists($uploadDir . $existingFoto)) {
                    @unlink($uploadDir . $existingFoto);
                }
                return $newFileName;
            }
        }
    }
    return $existingFoto;
}

// Handle Actions (Create, Update, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');

    // CREATE MENU
    if ($action === 'add') {
        $nama_menu = sanitize($_POST['nama_menu'] ?? '');
        $kategori = sanitize($_POST['kategori'] ?? '');
        $harga = (float)($_POST['harga'] ?? 0);
        $stok = (int)($_POST['stok'] ?? 0);

        if (empty($nama_menu) || empty($kategori) || $harga <= 0 || $stok < 0) {
            set_flash('danger', 'Mohon isi semua data menu dengan benar!');
        } else {
            $foto = handlePhotoUpload('foto');
            $stmt = $pdo->prepare("INSERT INTO menu (nama_menu, kategori, harga, stok, foto) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$nama_menu, $kategori, $harga, $stok, $foto])) {
                set_flash('success', 'Menu baru "' . $nama_menu . '" berhasil ditambahkan!');
            } else {
                set_flash('danger', 'Gagal menambahkan menu baru.');
            }
        }
        header("Location: menu.php");
        exit;
    }

    // EDIT / UPDATE MENU
    if ($action === 'edit') {
        $id_menu = (int)($_POST['id_menu'] ?? 0);
        $nama_menu = sanitize($_POST['nama_menu'] ?? '');
        $kategori = sanitize($_POST['kategori'] ?? '');
        $harga = (float)($_POST['harga'] ?? 0);
        $stok = (int)($_POST['stok'] ?? 0);

        if ($id_menu <= 0 || empty($nama_menu) || empty($kategori) || $harga <= 0 || $stok < 0) {
            set_flash('danger', 'Data update menu tidak valid!');
        } else {
            // Get current menu to retrieve existing photo name
            $stmtCur = $pdo->prepare("SELECT foto FROM menu WHERE id_menu = ?");
            $stmtCur->execute([$id_menu]);
            $curMenu = $stmtCur->fetch();
            $existingFoto = $curMenu['foto'] ?? null;

            $foto = handlePhotoUpload('foto', $existingFoto);

            $stmt = $pdo->prepare("UPDATE menu SET nama_menu = ?, kategori = ?, harga = ?, stok = ?, foto = ? WHERE id_menu = ?");
            if ($stmt->execute([$nama_menu, $kategori, $harga, $stok, $foto, $id_menu])) {
                set_flash('success', 'Menu "' . $nama_menu . '" berhasil diperbarui!');
            } else {
                set_flash('danger', 'Gagal memperbarui data menu.');
            }
        }
        header("Location: menu.php");
        exit;
    }

    // DELETE MENU
    if ($action === 'delete') {
        $id_menu = (int)($_POST['id_menu'] ?? 0);
        if ($id_menu > 0) {
            // Fetch photo to delete file
            $stmtCur = $pdo->prepare("SELECT foto FROM menu WHERE id_menu = ?");
            $stmtCur->execute([$id_menu]);
            $curMenu = $stmtCur->fetch();
            if (!empty($curMenu['foto']) && file_exists($uploadDir . $curMenu['foto'])) {
                @unlink($uploadDir . $curMenu['foto']);
            }

            $stmt = $pdo->prepare("DELETE FROM menu WHERE id_menu = ?");
            if ($stmt->execute([$id_menu])) {
                set_flash('success', 'Menu berhasil dihapus!');
            } else {
                set_flash('danger', 'Gagal menghapus menu.');
            }
        }
        header("Location: menu.php");
        exit;
    }
}

// Fetch all menu items for table list
$stmt = $pdo->query("SELECT * FROM menu ORDER BY kategori ASC, nama_menu ASC");
$menuItems = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="index.php" class="btn btn-outline-light btn-sm" style="padding: 0.2rem 0.6rem;">← Dashboard</a>
            <span class="badge-kategori">SISTEM MANAJEMEN MENU</span>
        </div>
        <h1 class="text-white font-weight-700 mb-0">Kelola Menu & Upload Foto</h1>
        <p class="text-muted" style="font-size: 0.9rem;">Tambah, edit, hapus menu, dan unggah gambar produk makanan/minuman</p>
    </div>
    <button class="btn btn-primary shadow-glow" id="btnOpenAddMenu">
        Tambah Menu Baru
    </button>
</div>

<div class="glass-card p-4 animate-fade-in">
    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Foto Makanan</th>
                    <th>Nama Menu</th>
                    <th>Kategori</th>
                    <th>Harga Satuan</th>
                    <th>Stok Porsi</th>
                    <th>Status Stok</th>
                    <th class="text-center">Aksi (Kelola)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($menuItems)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">Belum ada menu yang tersimpan. Klik <strong>Tambah Menu Baru</strong> untuk membuat menu pertama!</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($menuItems as $m): ?>
                        <tr>
                            <td>
                                <?php if (!empty($m['foto']) && file_exists($uploadDir . $m['foto'])): ?>
                                    <img src="<?= get_base_url() ?>uploads/menu/<?= sanitize($m['foto']) ?>" alt="<?= sanitize($m['nama_menu']) ?>" style="width: 54px; height: 54px; object-fit: cover; border-radius: 10px; border: 1px solid var(--border-glass-light); box-shadow: 0 4px 10px rgba(0,0,0,0.3);">
                                <?php else: ?>
                                    <div style="width: 54px; height: 54px; background: rgba(255,255,255,0.06); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; border: 1px dashed var(--border-glass);">
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong class="text-white" style="font-size: 1.05rem;"><?= sanitize($m['nama_menu']) ?></strong></td>
                            <td><span class="badge-kategori"><?= sanitize($m['kategori']) ?></span></td>
                            <td><strong class="text-gradient" style="font-size: 1.1rem;"><?= format_rupiah($m['harga']) ?></strong></td>
                            <td><strong class="text-white"><?= $m['stok'] ?></strong> porsi</td>
                            <td>
                                <?php if ($m['stok'] > 0): ?>
                                    <span class="badge-stok available">Tersedia</span>
                                <?php else: ?>
                                    <span class="badge-stok empty">Stok Habis</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-outline-light btn-sm me-1 btn-edit-menu"
                                        data-id="<?= $m['id_menu'] ?>"
                                        data-nama="<?= sanitize($m['nama_menu']) ?>"
                                        data-kategori="<?= sanitize($m['kategori']) ?>"
                                        data-harga="<?= $m['harga'] ?>"
                                        data-stok="<?= $m['stok'] ?>"
                                        data-foto="<?= sanitize($m['foto'] ?? '') ?>">
                                    Edit
                                </button>
                                <form method="POST" action="menu.php" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus menu <?= sanitize($m['nama_menu']) ?>?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id_menu" value="<?= $m['id_menu'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah / Edit Menu -->
<div class="modal-overlay" id="menuFormModal">
    <div class="modal-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="text-white font-weight-700" id="modalFormTitle">Tambah Menu Baru</h3>
            <button class="btn btn-outline-light btn-sm btn-close-modal" style="padding: 0.2rem 0.5rem;">✕</button>
        </div>

        <form method="POST" action="menu.php" id="formMenu" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id_menu" id="formIdMenu" value="">

            <div class="form-group">
                <label class="form-label">Nama Menu</label>
                <input type="text" name="nama_menu" id="inputNamaMenu" class="form-control" placeholder="Contoh: Nasi Goreng Spesial" required>
            </div>

            <div class="form-group">
                <label class="form-label">Kategori</label>
                <select name="kategori" id="inputKategori" class="form-control" required style="background: rgba(11, 15, 25, 0.9);">
                    <option value="Makanan">Makanan</option>
                    <option value="Minuman">Minuman</option>
                    <option value="Camilan">Camilan</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Harga Satuan (Rp)</label>
                <input type="number" step="500" name="harga" id="inputHarga" class="form-control" placeholder="15000" required min="500">
            </div>

            <div class="form-group">
                <label class="form-label">Jumlah Stok Porsi</label>
                <input type="number" name="stok" id="inputStok" class="form-control" placeholder="25" required min="0">
            </div>

            <div class="form-group mb-4">
                <label class="form-label">Upload Foto Makanan / Minuman</label>
                <input type="file" name="foto" id="inputFoto" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                <small class="text-muted d-block mt-1">Format: JPG, PNG, WEBP (Maksimal 5MB)</small>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 shadow-glow" id="btnSubmitFormMenu">
                Simpan Menu
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('menuFormModal');
    const modalTitle = document.getElementById('modalFormTitle');
    const formAction = document.getElementById('formAction');
    const formIdMenu = document.getElementById('formIdMenu');
    const inputNama = document.getElementById('inputNamaMenu');
    const inputKat = document.getElementById('inputKategori');
    const inputHarga = document.getElementById('inputHarga');
    const inputStok = document.getElementById('inputStok');
    const btnSubmit = document.getElementById('btnSubmitFormMenu');

    // Open Add Menu Modal
    const btnAdd = document.getElementById('btnOpenAddMenu');
    if (btnAdd) {
        btnAdd.addEventListener('click', function () {
            modalTitle.textContent = 'Tambah Menu Baru';
            formAction.value = 'add';
            formIdMenu.value = '';
            inputNama.value = '';
            inputKat.value = 'Makanan';
            inputHarga.value = '';
            inputStok.value = '10';
            btnSubmit.textContent = 'Simpan Menu Baru';
            modal.classList.add('active');
        });
    }

    // Open Edit Menu Modal
    document.querySelectorAll('.btn-edit-menu').forEach(btn => {
        btn.addEventListener('click', function () {
            modalTitle.textContent = 'Edit Data Menu';
            formAction.value = 'edit';
            formIdMenu.value = this.dataset.id;
            inputNama.value = this.dataset.nama;
            inputKat.value = this.dataset.kategori;
            inputHarga.value = this.dataset.harga;
            inputStok.value = this.dataset.stok;
            btnSubmit.textContent = 'Update Data Menu';
            modal.classList.add('active');
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
