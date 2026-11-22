<?php
// ==============================================================================
// WALUYA - KOMPONEN REUSABLE SIDEBAR (MULTI-ROLE)
// ==============================================================================
// File ini di-include pada dashboard masing-masing role:
// include '../components/sidebar.php';
// ==============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_role  = $_SESSION['role'] ?? 'keluarga';
$user_name     = $_SESSION['nama'] ?? 'Pengguna';
$user_email    = $_SESSION['email'] ?? '';
$active_menu   = $active_menu ?? 'dashboard';

// Ambil jumlah pending keluarga khusus role admin jika koneksi PDO tersedia
$pending_count = 0;
if ($current_role === 'admin' && isset($pdo)) {
    try {
        $pStmt = $pdo->query("SELECT COUNT(*) AS total FROM users WHERE role = 'keluarga' AND status_akun = 'pending'");
        $pending_count = (int)$pStmt->fetch()['total'];
    } catch (Exception $e) {
        $pending_count = 0;
    }
}

// Konfigurasi Label & Peran
$role_labels = [
    'admin'    => ['title' => 'Panel Admin', 'badge' => 'Administrator', 'badge_class' => 'badge-role-admin', 'color' => '#7C3AED'],
    'kader'    => ['title' => 'Panel Kader', 'badge' => 'Kader Posyandu', 'badge_class' => 'badge-role-kader', 'color' => '#047857'],
    'keluarga' => ['title' => 'Portal Keluarga', 'badge' => 'Anggota Keluarga', 'badge_class' => 'badge-role-keluarga', 'color' => '#0284C7']
];

$role_info = $role_labels[$current_role] ?? $role_labels['keluarga'];
?>

<!-- Backdrop Gelap untuk Tampilan Mobile saat Sidebar Terbuka -->
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar(false)"></div>

<!-- ASIDE: Navigasi Sidebar -->
<aside class="dashboard-sidebar" id="dashboardSidebar">

    <!-- Header Sidebar (Logo & Brand) -->
    <div class="sidebar-brand-header">
        <a href="index.php" class="sidebar-brand">
            <span class="sidebar-brand-icon">
                <img src="https://i.pinimg.com/736x/98/0f/03/980f03116b6fc42b54afac95ac23904f.jpg" alt="Logo Waluya">
            </span>
            <div class="sidebar-brand-text">
                <span class="brand-title">Waluya</span>
                <span class="brand-role-desc"><?= htmlspecialchars($role_info['title']) ?></span>
            </div>
        </a>
        <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" onclick="toggleSidebar(false)" aria-label="Tutup Menu">
            <i data-lucide="x" style="width: 20px; height: 20px;"></i>
        </button>
    </div>

    <!-- Navigasi Menu Sesuai Role Pengguna -->
    <nav class="sidebar-nav">

        <?php if ($current_role === 'admin'): ?>
            <!-- ================= MENU UNTUK ADMIN ================= -->
            <div class="nav-section-title">Menu Utama</div>
            <a href="index.php" class="sidebar-link <?= ($active_menu === 'dashboard') ? 'active' : '' ?>">
                <span class="sidebar-link-icon"><i data-lucide="layout-dashboard"></i></span>
                <span class="sidebar-link-text">Dashboard Admin</span>
            </a>

            <div class="nav-section-title">Manajemen Pengguna</div>
            <a href="acc-keluarga.php" class="sidebar-link <?= ($active_menu === 'acc-keluarga') ? 'active' : '' ?>">
                <span class="sidebar-link-icon"><i data-lucide="user-check"></i></span>
                <span class="sidebar-link-text">ACC Keluarga Baru</span>
                <?php if ($pending_count > 0): ?>
                    <span class="nav-counter-badge" title="<?= $pending_count ?> Akun Menunggu ACC"><?= $pending_count ?></span>
                <?php endif; ?>
            </a>
            <a href="keluarga.php" class="sidebar-link <?= ($active_menu === 'keluarga') ? 'active' : '' ?>">
                <span class="sidebar-link-icon"><i data-lucide="users"></i></span>
                <span class="sidebar-link-text">Kelola Keluarga</span>
            </a>
            <a href="lansia.php" class="sidebar-link <?= ($active_menu === 'lansia') ? 'active' : '' ?>">
                <span class="sidebar-link-icon"><i data-lucide="user-plus"></i></span>
                <span class="sidebar-link-text">Kelola Data Lansia</span>
            </a>
            <a href="kader.php" class="sidebar-link <?= ($active_menu === 'kader') ? 'active' : '' ?>">
                <span class="sidebar-link-icon"><i data-lucide="shield"></i></span>
                <span class="sidebar-link-text">Kelola Kader Posyandu</span>
            </a>

            <div class="nav-section-title">Layanan Kesehatan</div>
            <a href="riwayat.php" class="sidebar-link <?= ($active_menu === 'riwayat') ? 'active' : '' ?>">
                <span class="sidebar-link-icon"><i data-lucide="activity"></i></span>
                <span class="sidebar-link-text">Riwayat Medis (RUD)</span>
            </a>
            <a href="posyandu.php" class="sidebar-link <?= ($active_menu === 'posyandu') ? 'active' : '' ?>">
                <span class="sidebar-link-icon"><i data-lucide="building-2"></i></span>
                <span class="sidebar-link-text">Unit Posyandu</span>
            </a>
            <a href="jadwal.php" class="sidebar-link <?= ($active_menu === 'jadwal') ? 'active' : '' ?>">
                <span class="sidebar-link-icon"><i data-lucide="calendar"></i></span>
                <span class="sidebar-link-text">Jadwal Kegiatan</span>
            </a>

        <?php elseif ($current_role === 'kader'): ?>
            <!-- ================= MENU UNTUK KADER ================= -->
            <div class="nav-section-title">Menu Utama</div>
            <a href="index.php" class="sidebar-link <?= ($active_menu === 'dashboard') ? 'active' : '' ?>">
                <span class="sidebar-link-icon"><i data-lucide="layout-dashboard"></i></span>
                <span class="sidebar-link-text">Dashboard Kader</span>
            </a>

            <div class="nav-section-title">Pelayanan Pemeriksaan</div>
            <a href="input-pemeriksaan.php" class="sidebar-link <?= ($active_menu === 'input-pemeriksaan') ? 'active' : '' ?>">
                <span class="sidebar-link-icon"><i data-lucide="plus-circle"></i></span>
                <span class="sidebar-link-text">Input Pemeriksaan</span>
            </a>
            <a href="riwayat.php" class="sidebar-link <?= ($active_menu === 'riwayat') ? 'active' : '' ?>">
                <span class="sidebar-link-icon"><i data-lucide="clipboard-list"></i></span>
                <span class="sidebar-link-text">Riwayat Rekam Medis</span>
            </a>
            <a href="lansia.php" class="sidebar-link <?= ($active_menu === 'lansia') ? 'active' : '' ?>">
                <span class="sidebar-link-icon"><i data-lucide="users"></i></span>
                <span class="sidebar-link-text">Warga Lansia Binaan</span>
            </a>

            <div class="nav-section-title">Agenda Kegiatan</div>
            <a href="jadwal.php" class="sidebar-link <?= ($active_menu === 'jadwal') ? 'active' : '' ?>">
                <span class="sidebar-link-icon"><i data-lucide="calendar"></i></span>
                <span class="sidebar-link-text">Jadwal Posyandu</span>
            </a>

        <?php else: ?>
            <!-- ================= MENU UNTUK KELUARGA ================= -->
            <div class="nav-section-title">Menu Utama</div>
            <a href="index.php" class="sidebar-link <?= ($active_menu === 'dashboard') ? 'active' : '' ?>">
                <span class="sidebar-link-icon"><i data-lucide="layout-dashboard"></i></span>
                <span class="sidebar-link-text">Dashboard Keluarga</span>
            </a>

            <div class="nav-section-title">Pemantauan Lansia</div>
            <a href="riwayat.php" class="sidebar-link <?= ($active_menu === 'riwayat') ? 'active' : '' ?>">
                <span class="sidebar-link-icon"><i data-lucide="activity"></i></span>
                <span class="sidebar-link-text">Riwayat Kesehatan</span>
            </a>
            <a href="risiko.php" class="sidebar-link <?= ($active_menu === 'risiko') ? 'active' : '' ?>">
                <span class="sidebar-link-icon"><i data-lucide="alert-circle"></i></span>
                <span class="sidebar-link-text">Deteksi Risiko & Saran</span>
            </a>
            <a href="profil.php" class="sidebar-link <?= ($active_menu === 'profil') ? 'active' : '' ?>">
                <span class="sidebar-link-icon"><i data-lucide="user"></i></span>
                <span class="sidebar-link-text">Profil Lansia Terdaftar</span>
            </a>

            <div class="nav-section-title">Informasi & Kegiatan</div>
            <a href="jadwal.php" class="sidebar-link <?= ($active_menu === 'jadwal') ? 'active' : '' ?>">
                <span class="sidebar-link-icon"><i data-lucide="calendar"></i></span>
                <span class="sidebar-link-text">Jadwal Posyandu</span>
            </a>
        <?php endif; ?>

        <!-- Navigasi Tambahan Umum -->
        <div class="nav-section-title">Akses Luar</div>
        <a href="../index.php" class="sidebar-link" target="_blank">
            <span class="sidebar-link-icon"><i data-lucide="external-link"></i></span>
            <span class="sidebar-link-text">Halaman Beranda</span>
        </a>

    </nav>

    <!-- Footer Sidebar (Profil Singkat & Tombol Logout) -->
    <div class="sidebar-footer">
        <div class="sidebar-user-card">
            <div class="sidebar-user-avatar">
                <?= strtoupper(substr($user_name, 0, 1)) ?>
            </div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name" title="<?= htmlspecialchars($user_name) ?>"><?= htmlspecialchars($user_name) ?></span>
                <span class="sidebar-user-role badge <?= $role_info['badge_class'] ?>"><?= $role_info['badge'] ?></span>
            </div>
        </div>

        <a href="../logout.php" class="sidebar-logout-btn" title="Keluar dari akun">
            <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
            <span>Keluar Sistem</span>
        </a>
    </div>

</aside>

<script>
    // Fungsi Toggle Sidebar untuk Tampilan Mobile
    function toggleSidebar(forceState) {
        const sidebar = document.getElementById('dashboardSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        if (!sidebar) return;

        if (typeof forceState === 'boolean') {
            if (forceState) {
                sidebar.classList.add('sidebar-open');
                if (backdrop) backdrop.classList.add('backdrop-active');
            } else {
                sidebar.classList.remove('sidebar-open');
                if (backdrop) backdrop.classList.remove('backdrop-active');
            }
        } else {
            sidebar.classList.toggle('sidebar-open');
            if (backdrop) backdrop.classList.toggle('backdrop-active');
        }
    }
</script>
