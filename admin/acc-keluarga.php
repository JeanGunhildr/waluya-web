<?php
// ==============================================================================
// WALUYA - VERIFIKASI & ACC KELUARGA BARU (ADMIN)
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
$active_menu = 'acc-keluarga';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACC Keluarga Baru — Admin Waluya</title>

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
                    <h2 class="topbar-page-title">Persetujuan (ACC) Akun Keluarga</h2>
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
                    <h1 class="welcome-title">Verifikasi & ACC Pendaftaran Keluarga Baru</h1>
                    <p class="welcome-subtitle">
                        Halaman untuk memverifikasi pendaftar baru dari pihak keluarga. Admin dapat memeriksa kecocokan NIK dan domisili, lalu mengubah status akun dari <code>pending</code> menjadi <code>aktif</code> atau <code>ditolak</code>.
                    </p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i data-lucide="user-check" style="width: 18px; height: 18px; color: var(--primary);"></i>
                            Antrean Akun Keluarga Menunggu Persetujuan
                        </h3>
                    </div>

                    <!-- Tempat pengerjaan tabel antrean ACC keluarga oleh teman kelompok -->
                    <div style="padding: 32px; background: #F8FAFC; border-radius: 8px; border: 1.5px dashed #CBD5E1; text-align: center; color: #64748B;">
                        <p style="font-weight: 700; color: #1E293B; margin-bottom: 6px;">Area Koding Fitur ACC Keluarga</p>
                        <p style="font-size: 13.5px;">Bisa menggunakan query view <code>SELECT * FROM v_antrean_acc_keluarga</code> dan menambahkan tombol tombol aksi "Setujui (ACC)" atau "Tolak".</p>
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
