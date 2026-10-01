<?php
// config/database.php - E-Kantin Database Connection & Core Utility Helpers

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load MySQL Database Connection File
require_once __DIR__ . '/koneksi.php';


// Auto DB Migrations (Ensures pengaturan table and metode_pembayaran column exist)
function check_db_migrations($pdo) {
    static $migrated = false;
    if ($migrated) return;

    try {
        // 1. Create pengaturan table if not exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS pengaturan (
                id_pengaturan INT AUTO_INCREMENT PRIMARY KEY,
                nama_setting VARCHAR(100) NOT NULL UNIQUE,
                nilai_setting TEXT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 2. Add metode_pembayaran and status_pesanan columns to pesanan table if missing
        try {
            $pdo->exec("ALTER TABLE pesanan ADD COLUMN metode_pembayaran VARCHAR(50) NOT NULL DEFAULT 'Cash'");
        } catch (Exception $e) {
            // Column already exists, ignore
        }

        try {
            $pdo->exec("ALTER TABLE pesanan ADD COLUMN status_pesanan VARCHAR(30) NOT NULL DEFAULT 'Selesai'");
        } catch (Exception $e) {
            // Column already exists, ignore
        }

        try {
            $pdo->exec("ALTER TABLE pesanan ADD COLUMN nama_pemesan VARCHAR(100) NULL AFTER id_pembeli");
        } catch (Exception $e) {
            // Column already exists, ignore
        }


        // 3. Ensure upload directories exist
        $dirs = [
            __DIR__ . '/../uploads/menu',
            __DIR__ . '/../uploads/logo',
            __DIR__ . '/../uploads/qris'
        ];
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                @mkdir($dir, 0777, true);
            }
        }

        $migrated = true;
    } catch (Exception $e) {
        // Log or handle error gracefully
    }
}

// Global PDO instance helper
function db() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = getDBConnection(true);
        if (!$pdo) {
            $script = $_SERVER['SCRIPT_NAME'] ?? '';
            if (strpos($script, 'seed.php') === false) {
                header("Location: " . get_base_url() . "seed.php");
                exit;
            }
        } else {
            check_db_migrations($pdo);
        }
    }
    return $pdo;
}

// Settings Helpers
function get_setting($key, $default = '') {
    static $cache = null;
    $pdo = db();
    if ($cache === null && $pdo) {
        try {
            $stmt = $pdo->query("SELECT nama_setting, nilai_setting FROM pengaturan");
            $rows = $stmt->fetchAll();
            $cache = [];
            foreach ($rows as $row) {
                $cache[$row['nama_setting']] = $row['nilai_setting'];
            }
        } catch (Exception $e) {
            $cache = [];
        }
    }
    return $cache[$key] ?? $default;
}

function set_setting($key, $value) {
    $pdo = db();
    if (!$pdo) return false;
    $stmt = $pdo->prepare("
        INSERT INTO pengaturan (nama_setting, nilai_setting) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE nilai_setting = VALUES(nilai_setting)
    ");
    $result = $stmt->execute([$key, $value]);
    return $result;
}

// Base URL helper
function get_base_url() {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $dir = rtrim(dirname($script), '/');
    if (substr($dir, -6) === '/admin') {
        $dir = substr($dir, 0, -6);
    }
    if (preg_match('/^[a-zA-Z]:/', $dir)) {
        return '';
    }
    return $protocol . "://" . $host . ($dir ? $dir : '') . "/";
}

// Sanitize string helper
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Format Rupiah helper
function format_rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

// Flash Message Helpers
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Auth Helpers
function is_pembeli_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin_logged_in() {
    return !empty($_SESSION['admin_logged_in']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
}

function require_pembeli() {
    if (!is_pembeli_logged_in()) {
        set_flash('danger', 'Silakan isi nama dan nomor HP untuk memesan.');
        header("Location: " . get_base_url() . "index.php");
        exit;
    }
}

function require_admin() {
    if (!is_admin_logged_in()) {
        set_flash('danger', 'Akses khusus Admin. Silakan login terlebih dahulu.');
        header("Location: " . get_base_url() . "admin/login.php");
        exit;
    }
}
