<?php
// seed.php - One-click Database Installer for E-Kantin

require_once __DIR__ . '/config/database.php';

$message = '';
$status = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' || isset($_GET['auto']) || (php_sapi_name() === 'cli')) {
    try {
        // Connect to MySQL server without selecting a specific database first
        $pdoServer = getDBConnection(false);
        if (!$pdoServer) {
            throw new Exception("Gagal terhubung ke MySQL Server pada " . DB_HOST . ":" . DB_PORT . ". Pastikan WampServer / MySQL service sudah berjalan.");
        }

        // Read database.sql
        $sqlPath = __DIR__ . '/database.sql';
        if (!file_exists($sqlPath)) {
            throw new Exception("File database.sql tidak ditemukan!");
        }

        $sqlContent = file_get_contents($sqlPath);
        
        // Create uploads directory if not exists
        $uploadDir = __DIR__ . '/uploads/menu';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Execute queries batch
        $queries = array_filter(array_map('trim', explode(';', $sqlContent)));
        
        foreach ($queries as $query) {
            if (!empty($query)) {
                $pdoServer->exec($query);
            }
        }

        // Add foto column if db_ekantin.menu table exists but doesn't have foto column
        try {
            $pdoServer->exec("USE db_ekantin; ALTER TABLE menu ADD COLUMN foto VARCHAR(255) NULL DEFAULT NULL;");
        } catch (Exception $ex) {
            // Column already exists, ignore
        }

        // Always reset/update admin and default pembeli password to valid bcrypt hash
        $adminHash = password_hash('admin123', PASSWORD_BCRYPT);
        $userHash = password_hash('user123', PASSWORD_BCRYPT);

        $pdoServer->exec("USE db_ekantin;");
        $stmtAdminSeed = $pdoServer->prepare("
            INSERT INTO admin (nama_admin, username, password) 
            VALUES ('Administrator Kantin', 'admin', ?)
            ON DUPLICATE KEY UPDATE password = ?, nama_admin = 'Administrator Kantin'
        ");
        $stmtAdminSeed->execute([$adminHash, $adminHash]);

        $stmtUserSeed = $pdoServer->prepare("
            INSERT INTO pembeli (nama, username, password, no_hp) 
            VALUES ('Budi Santoso', 'budi', ?, '081234567890')
            ON DUPLICATE KEY UPDATE password = ?
        ");
        $stmtUserSeed->execute([$userHash, $userHash]);

        $status = 'success';
        $message = 'Database `db_ekantin` & tabel berhasil dibuat dan diisi data seed awal!';
        
        if (php_sapi_name() === 'cli') {
            echo "[SUCCESS] $message\n";
            exit(0);
        }
    } catch (Exception $e) {
        $status = 'danger';
        $message = 'Error: ' . $e->getMessage();
        if (php_sapi_name() === 'cli') {
            echo "[ERROR] $message\n";
            exit(1);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installer Database E-Kantin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gradient-dark min-vh-100 d-flex align-items-center justify-content-center p-3">
    <div class="glass-card text-center p-5 max-w-600 w-100 animate-fade-in">
        <div class="badge-icon bg-primary-glow mb-3 mx-auto">
            <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s8-1.79 8-4"></path>
            </svg>
        </div>
        <h1 class="text-white font-weight-700 mb-2">Installer Database E-Kantin</h1>
        <p class="text-muted mb-4">Inisialisasi database <code class="code-badge">db_ekantin</code> dan buat sampel data otomatis.</p>

        <?php if ($status): ?>
            <div class="alert alert-<?= $status ?> mb-4">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3 shadow-glow">
                Setup / Reset Database Sekarang
            </button>
        </form>

        <div class="card-footer-info text-start mt-4 pt-4 border-top-glass">
            <h5 class="text-white mb-2">Kredensial Default:</h5>
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Admin Username:</span>
                <strong class="text-white">admin</strong>
            </div>
            <div class="d-flex justify-content-between mb-3">
                <span class="text-muted">Admin Password:</span>
                <strong class="text-white">admin123</strong>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Pembeli Username:</span>
                <strong class="text-white">budi</strong>
            </div>
            <div class="d-flex justify-content-between mb-3">
                <span class="text-muted">Pembeli Password:</span>
                <strong class="text-white">user123</strong>
            </div>
            <div class="text-center mt-3">
                <a href="index.php" class="btn btn-outline-light btn-sm me-2">Ke Halaman Utama</a>
                <a href="admin/login.php" class="btn btn-outline-primary btn-sm">Login Admin</a>
            </div>
        </div>
    </div>
</body>
</html>
