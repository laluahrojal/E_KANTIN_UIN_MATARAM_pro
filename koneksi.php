<?php
// koneksi.php - Root MySQL Database Connection File
mysqli_report(MYSQLI_REPORT_OFF);

require_once __DIR__ . '/config/koneksi.php';

// Global $conn / $koneksi
if (!isset($koneksi) || !$koneksi) {
    $koneksi = @mysqli_connect("127.0.0.1", "root", "", "db_ekantin", 3306);
}
$conn = $koneksi;
