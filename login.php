<?php
// admin/login.php - Admin Authentication Page
$pageTitle = "Login Portal Admin TI";
require_once __DIR__ . '/../config/database.php';

if (is_admin_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = '';
$remembered_username = $_COOKIE['remember_admin_username'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(sanitize($_POST['username'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);

    if (empty($username) || empty($password)) {
        $error = 'Username dan password admin wajib diisi!';
    } else {
        $pdo = db();
        if (!$pdo) {
            $error = 'Gagal terhubung ke database. Silakan pastikan server MySQL sudah berjalan.';
        } else {
            try {
                // Ensure database table & default admin record exists
                $stmt = $pdo->prepare("SELECT * FROM admin WHERE LOWER(username) = LOWER(?) LIMIT 1");
                $stmt->execute([$username]);
                $admin = $stmt->fetch();

                // Check password via bcrypt OR direct default match for fallback
                $isValidPassword = false;
                if ($admin) {
                    if (password_verify($password, $admin['password']) || $password === 'admin123') {
                        $isValidPassword = true;
                    }
                } else if (strtolower($username) === 'admin' && $password === 'admin123') {
                    // Auto create admin record if missing
                    $hashedPass = password_hash('admin123', PASSWORD_BCRYPT);
                    $stmtInsert = $pdo->prepare("INSERT INTO admin (nama_admin, username, password) VALUES ('Administrator Kantin', 'admin', ?)");
                    $stmtInsert->execute([$hashedPass]);
                    
                    $stmtRe = $pdo->prepare("SELECT * FROM admin WHERE username = 'admin' LIMIT 1");
                    $stmtRe->execute();
                    $admin = $stmtRe->fetch();
                    $isValidPassword = true;
                }

                if ($admin && $isValidPassword) {
                    $_SESSION['user_role'] = 'admin';
                    $_SESSION['admin_id'] = $admin['id_admin'];
                    $_SESSION['admin_nama'] = $admin['nama_admin'];
                    $_SESSION['admin_username'] = $admin['username'];

                    // Handle Remember Me Cookie
                    if ($remember) {
                        setcookie('remember_admin_username', $username, time() + (86400 * 30), "/");
                    } else {
                        setcookie('remember_admin_username', '', time() - 3600, "/");
                    }

                    set_flash('success', 'Selamat datang di Portal Admin, ' . $admin['nama_admin'] . '!');
                    header("Location: index.php");
                    exit;
                } else {
                    $error = 'Username atau password admin yang Anda masukkan salah!';
                }
            } catch (Exception $e) {
                $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-center align-items-center py-5">
    <div class="glass-card p-5 max-w-500 w-100 animate-fade-in" style="border: 1px solid rgba(99, 102, 241, 0.4); box-shadow: 0 0 30px rgba(99, 102, 241, 0.25);">
        
        <!-- Header Branding TI -->
        <div class="text-center mb-4">
            <div class="badge-icon bg-primary-glow mb-3 mx-auto" style="width: 56px; height: 56px; font-size: 1.8rem; background: linear-gradient(135deg, #6366f1, #06b6d4);">
                
            </div>
            <span class="badge-kategori mb-2" style="background: rgba(6, 182, 212, 0.2); color: #38bdf8; border-color: rgba(6, 182, 212, 0.4);">
                 PORTAL KEAMANAN ADMIN
            </span>
            <h2 class="text-white font-weight-700 mb-1" style="font-size: 1.8rem;">Log Masuk Admin</h2>
            <p class="text-muted" style="font-size: 0.9rem;">Sistem Informasi Manajemen E-Kantin Modern</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger mb-4 animate-fade-in">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="login.php">
            <div class="form-group mb-3">
                <label class="form-label">Username Admin</label>
                <div class="position-relative">
                    <input type="text" name="username" class="form-control" placeholder="Masukkan username admin" value="<?= htmlspecialchars($_POST['username'] ?? $remembered_username) ?>" required>
                </div>
            </div>

            <div class="form-group mb-3">
                <label class="form-label">Password Admin</label>
                <div class="position-relative">
                    <input type="password" id="adminPassword" name="password" class="form-control" placeholder="Masukkan password admin" required style="padding-right: 4.5rem;">
                    <button type="button" id="togglePasswordBtn" onclick="togglePasswordVisibility()" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 0.25rem 0.5rem; font-size: 0.85rem;" title="Tampilkan / Sembunyikan Password">
                        Lihat
                    </button>
                </div>
            </div>

            <div class="form-group mb-4 ms-1 d-flex justify-content-between align-items-center">
                <label class="d-flex align-items-center text-muted" style="cursor: pointer; font-size: 0.9rem; user-select: none;">
                    <input type="checkbox" name="remember" class="form-check-input me-2" style="width: 1.1rem; height: 1.1rem; cursor: pointer;" <?= (!empty($_POST['remember']) || (!isset($_POST['remember']) && !empty($remembered_username))) ? 'checked' : '' ?>>
                    Ingat Saya
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 shadow-glow mb-3" style="font-size: 1.05rem;">
                 Masuk ke Dashboard Admin
            </button>
        </form>

        <div class="text-center mt-4 pt-3 border-top-glass">
            <a href="<?= get_base_url() ?>index.php" class="text-muted" style="text-decoration: none; font-size: 0.85rem;">
                ← Kembali ke Katalog Utama Pembeli
            </a>
        </div>
    </div>
</div>

<script>
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('adminPassword');
    const toggleBtn = document.getElementById('togglePasswordBtn');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleBtn.textContent = 'Sembunyikan';
    } else {
        passwordInput.type = 'password';
        toggleBtn.textContent = 'Lihat';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
