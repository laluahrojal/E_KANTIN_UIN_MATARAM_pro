<?php
// login.php - Halaman Login Pembeli E-Kantin
$pageTitle = "Login Pembeli";
require_once __DIR__ . '/config/database.php';

if (is_pembeli_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi!';
    } else {
        $pdo = db();
        if (!$pdo) {
            $error = 'Gagal terhubung ke database. Silakan pastikan server MySQL sudah berjalan.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM pembeli WHERE username = ? LIMIT 1");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_role'] = 'pembeli';
                    $_SESSION['user_id'] = $user['id_pembeli'];
                    $_SESSION['user_nama'] = $user['nama'];
                    $_SESSION['user_username'] = $user['username'];
                    $_SESSION['user_hp'] = $user['no_hp'];

                    set_flash('success', 'Selamat datang kembali, ' . $user['nama'] . '!');
                    header("Location: index.php");
                    exit;
                } else {
                    $error = 'Username atau password yang Anda masukkan salah!';
                }
            } catch (Exception $e) {
                $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-center align-items-center py-5">
    <div class="glass-card p-5 max-w-400 w-100 animate-fade-in">
        <div class="text-center mb-4">
            <div class="badge-icon bg-primary-glow mb-3 mx-auto" style="width: 56px; height: 56px; font-size: 1.8rem; background: linear-gradient(135deg, #6366f1, #a855f7);">
            </div>
            <h2 class="text-white font-weight-700 mb-1">Login Pembeli</h2>
            <p class="text-muted" style="font-size: 0.9rem;">Masuk ke akun Anda untuk memesan menu kantin</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger mb-4 animate-fade-in">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group mb-3">
                <label class="form-label">Username</label>
                <div class="position-relative">
                    <input type="text" name="username" class="form-control" placeholder="Masukkan username" value="<?= sanitize($_POST['username'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group mb-4">
                <label class="form-label">Password</label>
                <div class="position-relative">
                    <input type="password" id="pembeliPassword" name="password" class="form-control" placeholder="Masukkan password" required style="padding-right: 4.5rem;">
                    <button type="button" onclick="togglePembeliPassword()" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 0.25rem 0.5rem; font-size: 0.85rem;" title="Lihat Password">
                        Lihat
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 shadow-glow mb-3">
                Masuk Sekarang
            </button>
        </form>

        <div class="text-center mt-3 pt-3 border-top-glass d-flex justify-content-between align-items-center">
            <a href="register.php" class="text-gradient" style="text-decoration: none; font-size: 0.9rem; font-weight: 600;">
                Daftar Akun Baru
            </a>
            <a href="admin/login.php" class="text-muted" style="text-decoration: none; font-size: 0.85rem;">
                Login Admin →
            </a>
        </div>
    </div>
</div>

<script>
function togglePembeliPassword() {
    const p = document.getElementById('pembeliPassword');
    p.type = (p.type === 'password') ? 'text' : 'password';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
