<?php
// ==============================================================================
// WALUYA - DASHBOARD ADMIN
// ==============================================================================
session_start();

// 1. Pengecekan Autentikasi: Apakah pengguna sudah login?
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// 2. Pengecekan Hak Akses (Role Check): Harus bertipe 'admin'
if ($_SESSION['role'] !== 'admin') {
    if ($_SESSION['role'] === 'kader') {
        header("Location: ../kader/index.php");
    } else {
        header("Location: ../keluarga/index.php");
    }
    exit();
}

// Hubungkan ke Database PDO
require_once '../config/database.php';

// Set menu aktif untuk navigasi sidebar
$active_menu = 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin — Waluya</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- CSS Dashboard -->
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>

    <div class="dashboard-layout">

        <!-- ========================================== -->
        <!-- IMPORT REUSABLE SIDEBAR KHUSUS ROLE ADMIN  -->
        <!-- ========================================== -->
        <?php include '../components/sidebar.php'; ?>

        <!-- WRAPPER KONTEN UTAMA -->
        <div class="main-wrapper">

            <!-- Topbar Header -->
            <header class="dashboard-topbar">
                <div class="topbar-left">
                    <button type="button" class="topbar-mobile-toggle" onclick="toggleSidebar(true)" aria-label="Buka Menu Sidebar">
                        <i data-lucide="menu" style="width: 20px; height: 20px;"></i>
                    </button>
                    <h2 class="topbar-page-title">Dashboard Administrator</h2>
                </div>

                <div class="topbar-right">
                    <div class="topbar-date-pill">
                        <i data-lucide="calendar" style="width: 14px; height: 14px; color: var(--primary);"></i>
                        <span><?= date('d M Y') ?></span>
                    </div>

                    <a href="../logout.php" class="btn btn-logout" title="Keluar dari sistem">
                        <i data-lucide="log-out" style="width: 14px; height: 14px;"></i>
                        <span>Keluar</span>
                    </a>
                </div>
            </header>

            <!-- Konten Halaman Dashboard Admin -->
            <main class="dashboard-content">

                <!-- Welcome Hero Banner -->
                <div class="welcome-banner">
                    <h1 class="welcome-title">Selamat Datang, <?= htmlspecialchars($_SESSION['nama'] ?? 'Administrator') ?>!</h1>
                    <p class="welcome-subtitle">
                        Anda berada di <strong>Panel Kontrol Administrator Pusat Waluya</strong>. Gunakan menu navigasi di sebelah kiri untuk mengelola data anggota keluarga, verifikasi (ACC) pendaftaran baru, akun kader, dan riwayat kesehatan lansia.
                    </p>
                </div>

                <!-- Area Penempatan Fitur Tugas Kelompok -->
                <h3 style="font-size: 17px; font-weight: 700; color: #1E293B; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="layout-grid" style="width: 18px; height: 18px; color: var(--primary);"></i>
                    Fitur & Wewenang Administrator
                </h3>

                <div class="features-grid">

                    <!-- Fitur 1: ACC Akun Keluarga Baru -->
                    <div class="feature-box" id="acc-keluarga">
                        <div class="feature-icon-wrapper" style="background: #FEF3C7; color: #D97706;">
                            <i data-lucide="user-check" style="width: 22px; height: 22px;"></i>
                        </div>
                        <h4 class="feature-title">1. ACC Keluarga Baru</h4>
                        <p class="feature-desc">
                            Verifikasi data pendaftar keluarga lansia yang berstatus <code>pending</code> menjadi <code>aktif</code>.
                        </p>
                        <span class="feature-tag">Approval Admin</span>
                    </div>

                    <!-- Fitur 2: CRUD Anggota Keluarga & Lansia -->
                    <div class="feature-box" id="keluarga">
                        <div class="feature-icon-wrapper">
                            <i data-lucide="users" style="width: 22px; height: 22px;"></i>
                        </div>
                        <h4 class="feature-title">2. Kelola Keluarga & Lansia</h4>
                        <p class="feature-desc">
                            Tambah, ubah, hapus, dan lihat relasi anggota keluarga dengan data lansia binaan.
                        </p>
                        <span class="feature-tag">CRUD Keluarga & Lansia</span>
                    </div>

                    <!-- Fitur 3: CRUD Kader Posyandu -->
                    <div class="feature-box" id="kader">
                        <div class="feature-icon-wrapper" style="background: #E0F2FE; color: #0284C7;">
                            <i data-lucide="shield" style="width: 22px; height: 22px;"></i>
                        </div>
                        <h4 class="feature-title">3. Kelola Kader Posyandu</h4>
                        <p class="feature-desc">
                            Pendaftaran kader baru, pengelolaan jabatan kader, dan penempatan wilayah Posyandu.
                        </p>
                        <span class="feature-tag">CRUD Kader</span>
                    </div>

                    <!-- Fitur 4: RUD Riwayat Kesehatan Lansia -->
                    <div class="feature-box" id="rekam-medis">
                        <div class="feature-icon-wrapper" style="background: #FEE2E2; color: #DC2626;">
                            <i data-lucide="activity" style="width: 22px; height: 22px;"></i>
                        </div>
                        <h4 class="feature-title">4. Riwayat Kesehatan (RUD)</h4>
                        <p class="feature-desc">
                            Melihat rekam medis lengkap lansia, mengubah data bila diperlukan, serta penghapusan data.
                        </p>
                        <span class="feature-tag">RUD Rekam Medis</span>
                    </div>

                </div>

                <!-- Card Tempat Pengerjaan Anggota Kelompok -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i data-lucide="code" style="width: 18px; height: 18px; color: var(--primary);"></i>
                            Area Koding Tugas Kelompok (Admin)
                        </h3>
                    </div>
                    
                    <div style="padding: 24px; background: #F8FAFC; border-radius: 8px; border: 1.5px dashed #CBD5E1; text-align: center; color: #64748B;">
                        <p style="font-weight: 700; color: #1E293B; margin-bottom: 6px;">Template Dashboard Admin Siap Digunakan</p>
                        <p style="font-size: 13.5px;">Teman kelompok Anda dapat langsung menaruh tabel ACC keluarga, form input, atau query data di area file ini.</p>
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
