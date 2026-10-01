<?php
// register.php
$pageTitle = "Registrasi Pembeli";
require_once __DIR__ . '/config/database.php';

if (is_pembeli_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = sanitize($_POST['nama'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $no_hp = sanitize($_POST['no_hp'] ?? '');

    if (empty($nama) || empty($username) || empty($password) || empty($no_hp)) {
        $error = 'Semua bidang form registrasi wajib diisi!';
    } else {
        $pdo = db();
        // Check username uniqueness
        $stmtCheck = $pdo->prepare("SELECT id_pembeli FROM pembeli WHERE username = ?");
        $stmtCheck->execute([$username]);
        if ($stmtCheck->fetch()) {
            $error = 'Username "' . $username . '" sudah digunakan. Silakan pilih username lain.';
        } else {
            // Hash password and insert
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmtInsert = $pdo->prepare("INSERT INTO pembeli (nama, username, password, no_hp) VALUES (?, ?, ?, ?)");
            if ($stmtInsert->execute([$nama, $username, $hashedPassword, $no_hp])) {
                set_flash('success', 'Registrasi berhasil! Silakan login dengan akun Anda.');
                header("Location: login.php");
                exit;
            } else {
                $error = 'Gagal mendaftarkan akun. Silakan coba lagi.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-center align-items-center py-5">
    <div class="glass-card p-5 max-w-500 w-100 animate-fade-in">
        <div class="text-center mb-4">
            <h2 class="text-white font-weight-700 mb-1">Daftar Akun E-Kantin</h2>
            <p class="text-muted">Buat akun untuk melakukan pemesanan makanan & minuman</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger mb-4">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" placeholder="Contoh: Budi Santoso" value="<?= sanitize($_POST['nama'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Pilih username unik" value="<?= sanitize($_POST['username'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">No. Handphone (WA)</label>
                <input type="tel" name="no_hp" class="form-control" placeholder="081234567890" value="<?= sanitize($_POST['no_hp'] ?? '') ?>" required>
            </div>

            <div class="form-group mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 shadow-glow mb-3">
                Daftar Sekarang
            </button>
        </form>

        <div class="text-center mt-3 pt-3 border-top-glass">
            <p class="text-muted" style="font-size: 0.9rem;">
                Sudah memiliki akun? <a href="login.php" class="text-gradient" style="text-decoration: none; font-weight: 600;">Login di sini</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
