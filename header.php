<?php
// includes/header.php
require_once __DIR__ . '/../config/database.php';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' - E-Kantin' : 'E-Kantin - Modern Canteen App' ?></title>
    
    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= get_base_url() ?>assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/navbar.php'; ?>

<main class="container">
<?php if ($flash): ?>
    <div class="alert alert-<?= sanitize($flash['type']) ?> animate-fade-in mb-4">
        <span><?= sanitize($flash['message']) ?></span>
    </div>
<?php endif; ?>
