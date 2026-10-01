<?php
// pesan.php - Backend Order Handler (Supports Direct Order without Login & Payment Method Choice)
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$id_menu = (int)($_POST['id_menu'] ?? 0);
$jumlah = (int)($_POST['jumlah'] ?? 0);
$nama_pembeli = sanitize($_POST['nama_pembeli'] ?? '');
$no_hp = sanitize($_POST['no_hp'] ?? '');
$metode_pembayaran = sanitize($_POST['metode_pembayaran'] ?? 'Cash');
if (!in_array($metode_pembayaran, ['Cash', 'QRIS'])) {
    $metode_pembayaran = 'Cash';
}

if ($id_menu <= 0 || $jumlah <= 0 || empty($nama_pembeli) || empty($no_hp)) {
    set_flash('danger', 'Data pemesanan tidak lengkap. Mohon isi Nama, No HP, dan Jumlah pesanan.');
    header("Location: index.php");
    exit;
}

$pdo = db();

try {
    // Begin DB transaction for atomic stock check and decrement
    $pdo->beginTransaction();

    // 1. Identify or Create Pembeli record
    $id_pembeli = 0;
    if (is_pembeli_logged_in()) {
        $id_pembeli = $_SESSION['user_id'];
        // Update name and phone number in DB for this logged-in pembeli to match confirmed input
        $stmtUpPembeli = $pdo->prepare("UPDATE pembeli SET nama = ?, no_hp = ? WHERE id_pembeli = ?");
        $stmtUpPembeli->execute([$nama_pembeli, $no_hp, $id_pembeli]);
    } else {
        // Find existing pembeli matching phone number OR name
        $stmtFind = $pdo->prepare("SELECT id_pembeli FROM pembeli WHERE no_hp = ? OR nama = ? LIMIT 1");
        $stmtFind->execute([$no_hp, $nama_pembeli]);
        $existingUser = $stmtFind->fetch();

        if ($existingUser) {
            $id_pembeli = $existingUser['id_pembeli'];
            // Update name and phone number in DB to latest confirmed input
            $stmtUpPembeli = $pdo->prepare("UPDATE pembeli SET nama = ?, no_hp = ? WHERE id_pembeli = ?");
            $stmtUpPembeli->execute([$nama_pembeli, $no_hp, $id_pembeli]);
        } else {
            // Auto create distinct pembeli record for this buyer name & phone
            $cleanPhone = preg_replace('/[^0-9]/', '', $no_hp);
            $cleanName = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($nama_pembeli));
            $autoUsername = 'buyer_' . ($cleanName ?: 'guest') . '_' . substr($cleanPhone, -4) . '_' . rand(100, 999);
            
            $dummyPassword = password_hash('guest123', PASSWORD_BCRYPT);
            $stmtInsertPembeli = $pdo->prepare("INSERT INTO pembeli (nama, username, password, no_hp) VALUES (?, ?, ?, ?)");
            $stmtInsertPembeli->execute([$nama_pembeli, $autoUsername, $dummyPassword, $no_hp]);
            $id_pembeli = $pdo->lastInsertId();
        }
    }

    // Always store & refresh session so buyer can view their history with full session details
    $_SESSION['user_role'] = 'pembeli';
    $_SESSION['user_id'] = $id_pembeli;
    $_SESSION['user_nama'] = $nama_pembeli;
    $_SESSION['user_hp'] = $no_hp;

    // 2. Fetch menu item with FOR UPDATE lock
    $stmtMenu = $pdo->prepare("SELECT * FROM menu WHERE id_menu = ? FOR UPDATE");
    $stmtMenu->execute([$id_menu]);
    $menu = $stmtMenu->fetch();

    if (!$menu) {
        throw new Exception("Menu tidak ditemukan.");
    }

    $stokTersedia = (int)$menu['stok'];
    $hargaSatuan = (float)$menu['harga'];

    // 3. Stock Check
    if ($stokTersedia < $jumlah) {
        throw new Exception("Stok untuk menu '" . $menu['nama_menu'] . "' tidak mencukupi! Stok tersisa: " . $stokTersedia . " porsi.");
    }

    // 4. Calculation
    $total_harga = $hargaSatuan * $jumlah;

    // 5. Update Stock in Database
    $stmtUpdateStok = $pdo->prepare("UPDATE menu SET stok = stok - ? WHERE id_menu = ? AND stok >= ?");
    $stmtUpdateStok->execute([$jumlah, $id_menu, $jumlah]);

    if ($stmtUpdateStok->rowCount() === 0) {
        throw new Exception("Gagal memperbarui stok. Stok telah berubah.");
    }

    // 6. Insert Order into `pesanan` table with distinct nama_pemesan and payment method
    $stmtInsertOrder = $pdo->prepare("
        INSERT INTO pesanan (id_pembeli, nama_pemesan, id_menu, jumlah, total_harga, metode_pembayaran, tanggal) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmtInsertOrder->execute([$id_pembeli, $nama_pembeli, $id_menu, $jumlah, $total_harga, $metode_pembayaran]);

    $id_pesanan = $pdo->lastInsertId();

    // Commit Transaction
    $pdo->commit();

    set_flash('success', 'Pesanan berhasil dibuat!');
    header("Location: struk.php?id=" . $id_pesanan);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('danger', $e->getMessage());
    header("Location: index.php");
    exit;
}
