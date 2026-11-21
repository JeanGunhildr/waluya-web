<?php
// ==============================================================================
// WALUYA - PORTAL ANGGOTA KELUARGA
// ==============================================================================
session_start();

// 1. Pengecekan Autentikasi: Apakah pengguna sudah login?
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// 2. Pengecekan Hak Akses (Role Check): Harus bertipe 'keluarga'
if ($_SESSION['role'] !== 'keluarga') {
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/index.php");
    } else {
        header("Location: ../kader/index.php");
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
    <title>Portal Keluarga — Waluya</title>

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

        <!-- ============================================= -->
        <!-- IMPORT REUSABLE SIDEBAR KHUSUS ROLE KELUARGA  -->
        <!-- ============================================= -->
        <?php include '../components/sidebar.php'; ?>

        <!-- WRAPPER KONTEN UTAMA -->
        <div class="main-wrapper">

            <!-- Topbar Header -->
            <header class="dashboard-topbar">
                <div class="topbar-left">
                    <button type="button" class="topbar-mobile-toggle" onclick="toggleSidebar(true)" aria-label="Buka Menu Sidebar">
                        <i data-lucide="menu" style="width: 20px; height: 20px;"></i>
                    </button>
                    <h2 class="topbar-page-title">Portal Pemantauan Keluarga</h2>
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

            <!-- Konten Halaman Portal Keluarga -->
            <main class="dashboard-content">

                <!-- Welcome Hero Banner -->
                <div class="welcome-banner">
                    <h1 class="welcome-title">Selamat Datang, <?= htmlspecialchars($_SESSION['nama'] ?? 'Anggota Keluarga') ?>!</h1>
                    <p class="welcome-subtitle">
                        Akun keluarga Anda telah <strong>diverifikasi & disetujui (ACC) oleh Admin Posyandu</strong>. Di portal ini, Anda dapat memantau riwayat tensi darah, hasil uji laboratorium sederhana, dan catatan kesehatan orang tua tercinta.
                    </p>
                </div>

                <!-- Area Penempatan Fitur Tugas Kelompok -->
                <h3 style="font-size: 17px; font-weight: 700; color: #1E293B; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="activity" style="width: 18px; height: 18px; color: var(--primary);"></i>
                    Fitur Pemantauan Kesehatan Lansia
                </h3>

                <div class="features-grid">

                    <!-- Fitur 1: Riwayat Rekam Medis Lansia -->
                    <div class="feature-box" id="riwayat-kesehatan">
                        <div class="feature-icon-wrapper">
                            <i data-lucide="file-heart" style="width: 22px; height: 22px;"></i>
                        </div>
                        <h4 class="feature-title">1. Riwayat Kesehatan Lansia</h4>
                        <p class="feature-desc">
                            Lihat hasil pemeriksaan tensi darah, denyut nadi, gula darah, kolesterol, dan asam urat.
                        </p>
                        <span class="feature-tag">Read Rekam Medis</span>
                    </div>

                    <!-- Fitur 2: Status Risiko & Deteksi Dini -->
                    <div class="feature-box" id="deteksi-risiko">
                        <div class="feature-icon-wrapper" style="background: #FEF3C7; color: #D97706;">
                            <i data-lucide="alert-circle" style="width: 22px; height: 22px;"></i>
                        </div>
                        <h4 class="feature-title">2. Deteksi Risiko & Saran</h4>
                        <p class="feature-desc">
                            Ketahui status kondisi kesehatan terkini beserta anjuran pola hidup sehat dan rujukan medis.
                        </p>
                        <span class="feature-tag">Deteksi Dini</span>
                    </div>

                    <!-- Fitur 3: Profil Lansia Terdaftar -->
                    <div class="feature-box" id="profil-lansia">
                        <div class="feature-icon-wrapper" style="background: #E0F2FE; color: #0284C7;">
                            <i data-lucide="user" style="width: 22px; height: 22px;"></i>
                        </div>
                        <h4 class="feature-title">3. Profil Anggota Lansia</h4>
                        <p class="feature-desc">
                            Data biodata kependudukan lansia, tanggal lahir, riwayat penyakit, dan nomor BPJS Kesehatan.
                        </p>
                        <span class="feature-tag">Profil Lansia</span>
                    </div>

                    <!-- Fitur 4: Jadwal Kegiatan Posyandu -->
                    <div class="feature-box" id="jadwal">
                        <div class="feature-icon-wrapper" style="background: #FEE2E2; color: #DC2626;">
                            <i data-lucide="calendar" style="width: 22px; height: 22px;"></i>
                        </div>
                        <h4 class="feature-title">4. Jadwal Posyandu</h4>
                        <p class="feature-desc">
                            Cek tanggal dan lokasi pelaksanaan posyandu lansia bulan ini agar tidak terlewatkan.
                        </p>
                        <span class="feature-tag">Agenda Posyandu</span>
                    </div>

                </div>

                <!-- Card Tempat Pengerjaan Anggota Kelompok -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i data-lucide="code" style="width: 18px; height: 18px; color: var(--primary);"></i>
                            Area Koding Tugas Kelompok (Keluarga)
                        </h3>
                    </div>
                    
                    <div style="padding: 24px; background: #F8FAFC; border-radius: 8px; border: 1.5px dashed #CBD5E1; text-align: center; color: #64748B;">
                        <p style="font-weight: 700; color: #1E293B; margin-bottom: 6px;">Template Portal Keluarga Siap Digunakan</p>
                        <p style="font-size: 13.5px;">Teman kelompok Anda dapat langsung menampilkan tabel riwayat kesehatan lansia keluarga yang login di sini.</p>
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
