<?php
// ==============================================================================
// WALUYA - KELOLA DATA LANSIA (ADMIN)
// ==============================================================================
session_start();

// Pengecekan Autentikasi & Role Guard
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: " . ($_SESSION['role'] === 'kader' ? "../kader/index.php" : "../keluarga/index.php"));
    exit();
}

require_once '../config/database.php';
$active_menu = 'lansia';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Data Lansia — Admin Waluya</title>

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

        <!-- WRAPPER KONTEN UTAMA -->
        <div class="main-wrapper">

            <!-- Topbar Header -->
            <header class="dashboard-topbar">
                <div class="topbar-left">
                    <button type="button" class="topbar-mobile-toggle" onclick="toggleSidebar(true)" aria-label="Buka Menu">
                        <i data-lucide="menu" style="width: 20px; height: 20px;"></i>
                    </button>
                    <h2 class="topbar-page-title">Kelola Data Lansia</h2>
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

            <!-- Konten Halaman -->
            <main class="dashboard-content">

                <!-- Title Banner -->
                <div class="welcome-banner">
                    <h1 class="welcome-title">Manajemen Data Warga Lansia</h1>
                    <p class="welcome-subtitle">
                        Halaman untuk mengelola data warga lanjut usia (CRUD): tambah lansia baru, ubah biodata kependudukan (NIK, BPJS), hubungkan dengan keluarga penanggung jawab, dan pantau status keberadaan lansia.
                    </p>
                </div>

                <!-- Card Workspace Pengerjaan Kelola Lansia -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i data-lucide="user-plus" style="width: 18px; height: 18px; color: var(--primary);"></i>
                            Daftar Warga Lanjut Usia (CRUD Lansia)
                        </h3>
                    </div>

                    <!-- Tempat pengerjaan tabel / form CRUD lansia oleh teman kelompok -->
                    <div style="padding: 32px; background: #F8FAFC; border-radius: 8px; border: 1.5px dashed #CBD5E1; text-align: center; color: #64748B;">
                        <p style="font-weight: 700; color: #1E293B; margin-bottom: 6px;">Area Koding Fitur Kelola Lansia</p>
                        <p style="font-size: 13.5px;">Silakan buat form tambah lansia dan tabel data lansia (menampilkan NIK, Nama, Usia, Posyandu, dan Aksi Edit/Hapus) di file ini.</p>
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
