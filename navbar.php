<?php
// includes/navbar.php
$baseUrl = get_base_url();
$currentScript = basename($_SERVER['SCRIPT_NAME']);
$isAdminPage = (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false);
$logoKantin = get_setting('logo_kantin', '');
$logoPath = __DIR__ . '/../uploads/logo/' . $logoKantin;
?>
<nav class="navbar-glass">
    <div class="nav-container">
        <a href="<?= $baseUrl ?>index.php" class="brand-logo">
            <?php if (!empty($logoKantin) && file_exists($logoPath)): ?>
                <img src="<?= $baseUrl ?>uploads/logo/<?= sanitize($logoKantin) ?>" alt="Logo Kantin" style="height: 38px; width: auto; max-width: 140px; object-fit: contain; filter: drop-shadow(0 2px 8px rgba(0,0,0,0.3));">
            <?php else: ?>
                <div class="brand-icon">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
            <?php endif; ?>
            <span><?= sanitize(get_setting('nama_kantin', 'E-Kantin TI')) ?></span>
        </a>

        <ul class="nav-links">
            <?php if ($isAdminPage && is_admin_logged_in()): ?>
                <li>
                    <a href="<?= $baseUrl ?>admin/index.php" class="nav-link <?= $currentScript == 'index.php' ? 'active' : '' ?>">
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="<?= $baseUrl ?>admin/menu.php" class="nav-link <?= $currentScript == 'menu.php' ? 'active' : '' ?>">
                        Kelola Menu & Foto
                    </a>
                </li>
                <li>
                    <a href="<?= $baseUrl ?>admin/pesanan.php" class="nav-link <?= $currentScript == 'pesanan.php' ? 'active' : '' ?>">
                        Data Pesanan
                    </a>
                </li>
                <li>
                    <a href="<?= $baseUrl ?>admin/pembeli.php" class="nav-link <?= $currentScript == 'pembeli.php' ? 'active' : '' ?>">
                        Data Pembeli
                    </a>
                </li>
                <li>
                    <a href="<?= $baseUrl ?>admin/pengaturan.php" class="nav-link <?= $currentScript == 'pengaturan.php' ? 'active' : '' ?>">
                        Logo & QRIS
                    </a>
                </li>
                <li>
                    <a href="<?= $baseUrl ?>logout.php" class="btn btn-outline-light btn-sm me-2">
                        Logout (<?= sanitize($_SESSION['admin_nama'] ?? 'Admin') ?>)
                    </a>
                </li>
            <?php else: ?>
                <li>
                    <a href="<?= $baseUrl ?>index.php" class="nav-link <?= $currentScript == 'index.php' ? 'active' : '' ?>">
                        Beranda Katalog
                    </a>
                </li>
                
                <li>
                    <a href="<?= $baseUrl ?>riwayat.php" class="nav-link <?= $currentScript == 'riwayat.php' ? 'active' : '' ?>">
                        Riwayat Pesanan
                    </a>
                </li>

                <li>
                    <a href="<?= $baseUrl ?>admin/login.php" class="btn btn-primary btn-sm shadow-glow" style="font-size: 0.85rem;">
                        Portal Admin TI
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
