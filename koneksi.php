<?php
// config/koneksi.php - Koneksi Database MySQL (Auto-Fallback Remote -> Local WampServer)

// Matikan exception otomatis MySQLi di PHP 8+ agar tidak melempar Fatal Error saat IP remote offline
mysqli_report(MYSQLI_REPORT_OFF);

$host = "195.88.211.20";
$port = 3298;
$user = "tiuinmtr_mimin";
$pass = "";
$db   = "db_ekantin";

// Coba koneksi ke server remote terlebih dahulu
$koneksi = @mysqli_connect($host, $user, $pass, $db, (int)$port);

// Jika remote gagal (offline/port ditolak), otomatis fallback ke WampServer lokal (127.0.0.1:3306)
if (!$koneksi) {
    $host = "127.0.0.1";
    $port = 3306;
    $user = "root";
    $pass = "";
    $koneksi = @mysqli_connect($host, $user, $pass, $db, $port);
}

if (!defined('DB_HOST')) define('DB_HOST', $host);
if (!defined('DB_PORT')) define('DB_PORT', $port);
if (!defined('DB_USER')) define('DB_USER', $user);
if (!defined('DB_PASS')) define('DB_PASS', $pass);
if (!defined('DB_NAME')) define('DB_NAME', $db);

/**
 * Helper PDO Connection dengan Fallback
 */
function getDBConnection($includeDbName = true) {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
        if ($includeDbName) {
            $dsn .= ";dbname=" . DB_NAME;
        }
        return new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (Throwable $e) {
        // Backup fallback ke local WampServer jika PDO error
        try {
            $dsnLocal = "mysql:host=127.0.0.1;port=3306;charset=utf8mb4";
            if ($includeDbName) {
                $dsnLocal .= ";dbname=" . DB_NAME;
            }
            return new PDO($dsnLocal, "root", "", [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (Throwable $ex) {
            return null;
        }
    }
}
