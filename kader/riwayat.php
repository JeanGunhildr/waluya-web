<?php
// ==============================================================================
// WALUYA - RIWAYAT REKAM MEDIS LANSIA (KADER - CRUD)
// ==============================================================================
session_start();

// Pengecekan Autentikasi & Role Guard
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}
if ($_SESSION['role'] !== 'kader') {
    header("Location: " . ($_SESSION['role'] === 'admin' ? "../admin/index.php" : "../keluarga/index.php"));
    exit();
}

require_once '../config/database.php';
$active_menu = 'riwayat';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Rekam Medis — Kader Waluya</title>

    <!-- Google Fonts & Lucide Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- CSS Dashboard -->
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>

    <div class="dashboard-layout">
        <!-- IMPORT REUSABLE SIDEBAR -->
        <?php include '../components/sidebar.php'; ?>

        <div class="main-wrapper">
            <header class="dashboard-topbar">
                <div class="topbar-left">
                    <button type="button" class="topbar-mobile-toggle" onclick="toggleSidebar(true)" aria-label="Buka Menu">
                        <i data-lucide="menu" style="width: 20px; height: 20px;"></i>
                    </button>
                    <h2 class="topbar-page-title">Riwayat Rekam Medis (CRUD)</h2>
                </div>
                <div class="topbar-right">
                    <div class="topbar-date-pill">
                        <i data-lucide="calendar" style="width: 14px; height: 14px; color: var(--primary);"></i>
                        <span><?= date('d M Y') ?></span>
                    </div>
                    <a href="../logout.php" class="btn btn-logout" title="Keluar">
                        <i data-lucide="log-out" style="width: 14px; height: 14px;"></i>
                        <span>Keluar</span>
                    </a>
                </div>
            </header>

            <main class="dashboard-content">
                <div class="welcome-banner">
                    <h1 class="welcome-title">Daftar Riwayat Rekam Medis Lansia</h1>
                    <p class="welcome-subtitle">
                        Tabel riwayat seluruh hasil pemeriksaan lansia binaan Posyandu. Kader memiliki hak penuh untuk menambahkan catatan baru (Create), melihat riwayat (Read), mengubah hasil (Update), dan menghapus rekaman (Delete).
                    </p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i data-lucide="clipboard-list" style="width: 18px; height: 18px; color: var(--primary);"></i>
                            Tabel Riwayat Pemeriksaan Kesehatan
                        </h3>
                    </div>

                    <!-- Tempat pengerjaan tabel CRUD riwayat kesehatan kader -->
                    <div style="padding: 32px; background: #F8FAFC; border-radius: 8px; border: 1.5px dashed #CBD5E1; text-align: center; color: #64748B;">
                        <p style="font-weight: 700; color: #1E293B; margin-bottom: 6px;">Area Koding Fitur Riwayat Rekam Medis (Kader)</p>
                        <p style="font-size: 13.5px;">Tempat menampilkan riwayat pemeriksaan berkala dari tabel <code>riwayat_kesehatan</code> beserta tombol aksi edit dan hapus.</p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>
