<?php
// ==============================================================================
// WALUYA - LAYOUT KADER (BAGIAN ATAS): <head>, sidebar, topbar, flash message
// ==============================================================================
// Variabel yang diharapkan dari halaman pemanggil:
//   $page_title    : judul tab browser
//   $topbar_title  : judul di topbar
//   $active_menu   : menu sidebar yang aktif
// ==============================================================================
$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title ?? 'Kader') ?> — Waluya</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>
    <div class="dashboard-layout">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <div class="main-wrapper">
            <header class="dashboard-topbar">
                <div class="topbar-left">
                    <button type="button" class="topbar-mobile-toggle" onclick="toggleSidebar(true)" aria-label="Buka Menu Sidebar">
                        <i data-lucide="menu" style="width: 20px; height: 20px;"></i>
                    </button>
                    <h2 class="topbar-page-title"><?= e($topbar_title ?? 'Kader') ?></h2>
                </div>
                <div class="topbar-right">
                    <div class="topbar-date-pill">
                        <i data-lucide="calendar" style="width: 14px; height: 14px; color: var(--primary);"></i>
                        <span><?= tgl_id(date('Y-m-d')) ?></span>
                    </div>
                    <a href="../logout.php" class="btn btn-logout" title="Keluar dari sistem">
                        <i data-lucide="log-out" style="width: 14px; height: 14px;"></i>
                        <span>Keluar</span>
                    </a>
                </div>
            </header>

            <main class="dashboard-content">
                <?php if ($flash): ?>
                    <div class="alert alert-<?= e($flash['type']) ?>" role="alert">
                        <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle-2' : 'alert-circle' ?>" style="width:18px;height:18px;"></i>
                        <div><?= e($flash['message']) ?></div>
                    </div>
                <?php endif; ?>
                <?php if (!$kader['can_write']): ?>
                    <div class="alert alert-warning">
                        <i data-lucide="lock" style="width:18px;height:18px;"></i>
                        <div>Status kader Anda saat ini <strong><?= e($kader['status_kader']) ?></strong>. Anda hanya dapat melihat data; menambah, mengubah, dan menghapus dinonaktifkan.</div>
                    </div>
                <?php endif; ?>
