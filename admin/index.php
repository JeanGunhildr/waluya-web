<?php
// ==============================================================================
// WALUYA - DASHBOARD UTAMA ADMINISTRATOR
// ==============================================================================
session_start();

// 1. Pengecekan Autentikasi & Role Guard
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

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

$active_menu = 'dashboard';
$alert_msg = '';
$alert_type = '';

// 2. Pemrosesan Aksi Cepat Dashboard (ACC / Tolak Pendaftaran Keluarga)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'approve_keluarga') {
        $idUser = (int)($_POST['id_user'] ?? 0);
        if ($idUser > 0) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET status_akun = 'aktif', 
                        id_admin_acc = :admin_id, 
                        tanggal_acc = NOW(), 
                        catatan_acc = 'Disetujui oleh Administrator' 
                    WHERE id_user = :id AND role = 'keluarga'
                ");
                $stmt->execute(['admin_id' => $_SESSION['user_id'], 'id' => $idUser]);

                // Catat log aktivitas
                try {
                    $log = $pdo->prepare("INSERT INTO log_aktivitas (id_user, aksi, modul, deskripsi, ip_address, user_agent) VALUES (:uid, 'ACC_KELUARGA', 'User', :desc, :ip, :ua)");
                    $log->execute([
                        'uid'  => $_SESSION['user_id'],
                        'desc' => "Admin menyetujui akun keluarga ID: $idUser",
                        'ip'   => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                        'ua'   => $_SERVER['HTTP_USER_AGENT'] ?? ''
                    ]);
                } catch (Exception $e) {}

                $alert_type = 'success';
                $alert_msg = "Akun keluarga berhasil disetujui (ACC)! Pengguna sekarang dapat login ke portal keluarga.";
            } catch (PDOException $e) {
                $alert_type = 'danger';
                $alert_msg = "Gagal menyetujui akun: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'reject_keluarga') {
        $idUser = (int)($_POST['id_user'] ?? 0);
        $alasan = trim($_POST['alasan_penolakan'] ?? 'Data dokumen NIK/KK tidak sesuai dengan data wilayah binaan.');
        if ($idUser > 0) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET status_akun = 'ditolak', 
                        id_admin_acc = :admin_id, 
                        tanggal_acc = NOW(), 
                        catatan_acc = :alasan 
                    WHERE id_user = :id AND role = 'keluarga'
                ");
                $stmt->execute(['admin_id' => $_SESSION['user_id'], 'alasan' => $alasan, 'id' => $idUser]);

                $alert_type = 'warning';
                $alert_msg = "Pendaftaran akun keluarga telah ditolak dengan catatan yang dikirimkan.";
            } catch (PDOException $e) {
                $alert_type = 'danger';
                $alert_msg = "Gagal menolak akun: " . $e->getMessage();
            }
        }
    }
}

// 3. Query Statistik Utama Real-time
try {
    $totalLansia = (int)$pdo->query("SELECT COUNT(*) FROM lansia WHERE status_lansia = 'aktif'")->fetchColumn();
    $totalKader = (int)$pdo->query("SELECT COUNT(*) FROM kader WHERE status_kader = 'aktif'")->fetchColumn();
    $totalKeluarga = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'keluarga' AND status_akun = 'aktif'")->fetchColumn();
    $totalPending = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'keluarga' AND status_akun = 'pending'")->fetchColumn();
    $totalPemeriksaan = (int)$pdo->query("SELECT COUNT(*) FROM riwayat_kesehatan")->fetchColumn();
} catch (PDOException $e) {
    $totalLansia = $totalKader = $totalKeluarga = $totalPending = $totalPemeriksaan = 0;
}

// 4. Query Antrean ACC Pendaftaran Keluarga (Status Pending)
$pendingQueue = [];
try {
    $stmtPending = $pdo->query("
        SELECT u.id_user, u.nama_lengkap, u.email, u.no_hp, u.created_at AS waktu_mendaftar,
               k.nik, k.alamat_domisili, k.rt, k.rw, k.kontak_darurat
        FROM users u
        LEFT JOIN keluarga k ON u.id_user = k.id_user
        WHERE u.role = 'keluarga' AND u.status_akun = 'pending'
        ORDER BY u.created_at DESC
        LIMIT 5
    ");
    $pendingQueue = $stmtPending->fetchAll();
} catch (PDOException $e) {
    $pendingQueue = [];
}

// 5. Query 5 Pemeriksaan Fisik & Lab Lansia Terbaru
$riwayatTerbaru = [];
try {
    $stmtRiwayat = $pdo->query("
        SELECT rk.id_riwayat, rk.tanggal_pemeriksaan, rk.tensi_sistolik, rk.tensi_diastolik, 
               rk.status_tensi, rk.imt, rk.status_imt, rk.gula_darah, rk.status_kondisi,
               l.nama_lengkap AS nama_lansia, TIMESTAMPDIFF(YEAR, l.tanggal_lahir, CURDATE()) AS usia_lansia,
               u_kader.nama_lengkap AS nama_kader
        FROM riwayat_kesehatan rk
        JOIN lansia l ON rk.id_lansia = l.id_lansia
        LEFT JOIN kader k ON rk.id_kader = k.id_kader
        LEFT JOIN users u_kader ON k.id_user = u_kader.id_user
        ORDER BY rk.tanggal_pemeriksaan DESC, rk.id_riwayat DESC
        LIMIT 5
    ");
    $riwayatTerbaru = $stmtRiwayat->fetchAll();
} catch (PDOException $e) {
    $riwayatTerbaru = [];
}

// 6. Query 5 Data Warga Lansia Terbaru
$lansiaTerbaru = [];
try {
    $stmtLansia = $pdo->query("
        SELECT l.id_lansia, l.nik, l.nama_lengkap, l.jenis_kelamin, l.status_lansia,
               TIMESTAMPDIFF(YEAR, l.tanggal_lahir, CURDATE()) AS usia,
               p.nama_posyandu, u_kel.nama_lengkap AS nama_wali
        FROM lansia l
        JOIN posyandu p ON l.id_posyandu = p.id_posyandu
        LEFT JOIN keluarga kel ON l.id_keluarga = kel.id_keluarga
        LEFT JOIN users u_kel ON kel.id_user = u_kel.id_user
        ORDER BY l.id_lansia DESC
        LIMIT 5
    ");
    $lansiaTerbaru = $stmtLansia->fetchAll();
} catch (PDOException $e) {
    $lansiaTerbaru = [];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Utama — Admin Waluya</title>

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

        <!-- IMPORT REUSABLE SIDEBAR KHUSUS ROLE ADMIN -->
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

                <!-- Header Judul Bersih Tanpa Background Hijau -->
                <div class="welcome-banner">
                    <h1 class="welcome-title">Selamat Datang, <?= htmlspecialchars($_SESSION['nama'] ?? 'Administrator') ?>!</h1>
                    <p class="welcome-subtitle">
                        Pusat kendali dan pengelolaan sistem Posyandu Lansia Waluya. Pantau antrean verifikasi keluarga, statistik warga binaan, data kader, dan riwayat rekam medis.
                    </p>
                </div>

                <!-- Notifikasi Feedback Pesan -->
                <?php if (!empty($alert_msg)): ?>
                    <div class="alert-banner alert-banner-<?= $alert_type ?>">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i data-lucide="<?= $alert_type === 'success' ? 'check-circle' : 'alert-circle' ?>" style="width: 18px; height: 18px;"></i>
                            <span><?= htmlspecialchars($alert_msg) ?></span>
                        </div>
                        <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; cursor:pointer; color:inherit;">
                            <i data-lucide="x" style="width: 16px; height: 16px;"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- 1. KPI Statistics Cards Grid -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-icon icon-primary">
                            <i data-lucide="user-plus" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div class="kpi-details">
                            <span class="kpi-value"><?= number_format($totalLansia) ?></span>
                            <span class="kpi-label">Warga Lansia</span>
                            <span class="kpi-subtext">Terdaftar aktif</span>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-icon icon-warning">
                            <i data-lucide="user-check" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div class="kpi-details">
                            <span class="kpi-value"><?= number_format($totalPending) ?></span>
                            <span class="kpi-label">Menunggu ACC</span>
                            <span class="kpi-subtext"><?= $totalPending > 0 ? 'Perlu tindakan verifikasi' : 'Tidak ada antrean' ?></span>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-icon icon-info">
                            <i data-lucide="users" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div class="kpi-details">
                            <span class="kpi-value"><?= number_format($totalKeluarga) ?></span>
                            <span class="kpi-label">Keluarga Disetujui</span>
                            <span class="kpi-subtext">Akun wali aktif</span>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-icon icon-purple">
                            <i data-lucide="shield-check" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div class="kpi-details">
                            <span class="kpi-value"><?= number_format($totalKader) ?></span>
                            <span class="kpi-label">Kader Posyandu</span>
                            <span class="kpi-subtext">Kader bertugas</span>
                        </div>
                    </div>
                </div>

                <!-- 2. FITUR UTAMA 1: ANTREAN PERSETUJUAN (ACC) KELUARGA BARU -->
                <div class="card" id="section-acc">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i data-lucide="user-check" style="width: 18px; height: 18px; color: #D97706;"></i>
                            Antrean Verifikasi & ACC Pendaftaran Keluarga Baru
                        </h3>
                        <a href="acc-keluarga.php" class="btn btn-sm btn-secondary">Lihat Semua Antrean</a>
                    </div>

                    <?php if (count($pendingQueue) > 0): ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Nama Lengkap</th>
                                        <th>Kontak (Email / No HP)</th>
                                        <th>NIK KTP & Domisili</th>
                                        <th>Waktu Daftar</th>
                                        <th style="text-align: right;">Aksi Persetujuan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingQueue as $p): 
                                        $cleanNama = preg_replace('/\s*\(Menunggu ACC\)$/i', '', $p['nama_lengkap']);
                                        $domisili = htmlspecialchars($p['alamat_domisili'] ?? '-');
                                        $rt = htmlspecialchars($p['rt'] ?? '-');
                                        $rw = htmlspecialchars($p['rw'] ?? '-');
                                        $alamatText = (stripos($domisili, 'RT') !== false) ? $domisili : "$domisili (RT $rt / RW $rw)";
                                    ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight: 700; color: #1E293B; font-size: 13.5px;"><?= htmlspecialchars($cleanNama) ?></div>
                                                <div style="margin-top: 4px;">
                                                    <span class="badge badge-pending">Menunggu ACC</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-size: 13px; color: #334155; font-weight: 500;"><?= htmlspecialchars($p['email']) ?></div>
                                                <div style="font-size: 12px; color: #64748B; margin-top: 2px;"><?= htmlspecialchars($p['no_hp']) ?></div>
                                            </td>
                                            <td>
                                                <div style="font-size: 13px; font-weight: 700; color: #1E293B;">NIK: <?= htmlspecialchars($p['nik'] ?? '-') ?></div>
                                                <div style="font-size: 12px; color: #64748B; margin-top: 2px;"><?= $alamatText ?></div>
                                            </td>
                                            <td style="font-size: 12.5px; color: #64748B;">
                                                <div style="font-weight: 600; color: #334155;"><?= date('d M Y', strtotime($p['waktu_mendaftar'])) ?></div>
                                                <div style="font-size: 11px; color: #94A3B8; margin-top: 2px;"><?= date('H:i', strtotime($p['waktu_mendaftar'])) ?> WIB</div>
                                            </td>
                                            <td style="text-align: right;">
                                                <div class="table-actions" style="justify-content: flex-end;">
                                                    <!-- Form Setujui (ACC) -->
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui akun keluarga ini?')">
                                                        <input type="hidden" name="action" value="approve_keluarga">
                                                        <input type="hidden" name="id_user" value="<?= $p['id_user'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-success" title="Setujui pendaftaran ini">
                                                            <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                                                            <span>Setujui (ACC)</span>
                                                        </button>
                                                    </form>

                                                    <!-- Tombol Buka Modal Tolak -->
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="openRejectModal(<?= $p['id_user'] ?>, '<?= htmlspecialchars(addslashes($cleanNama)) ?>')"
                                                            title="Tolak pendaftaran">
                                                        <i data-lucide="x" style="width: 14px; height: 14px;"></i>
                                                        <span>Tolak</span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="padding: 24px; text-align: center; color: #047857; background: #ECFDF5; border-radius: 8px; border: 1px solid #A7F3D0;">
                            <i data-lucide="check-circle" style="width: 24px; height: 24px; display: inline-block; vertical-align: -4px; margin-right: 6px;"></i>
                            <strong>Tidak Ada Antrean Pendaftaran</strong> — Semua permohonan akun keluarga telah diverifikasi dan disetujui.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 3. FITUR UTAMA 2: PEMERIKSAAN KESEHATAN TERBARU (MONITORING REKAM MEDIS) -->
                <div class="card" id="section-riwayat">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i data-lucide="activity" style="width: 18px; height: 18px; color: var(--primary);"></i>
                            Pemeriksaan Fisik & Skrining Lansia Terbaru
                        </h3>
                        <a href="riwayat.php" class="btn btn-sm btn-secondary">Lihat Semua Rekam Medis (RUD)</a>
                    </div>

                    <?php if (count($riwayatTerbaru) > 0): ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Nama Lansia</th>
                                        <th>Tekanan Darah</th>
                                        <th>IMT & Gula Darah</th>
                                        <th>Status Risiko</th>
                                        <th>Kader Pemeriksa</th>
                                        <th style="text-align: right;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($riwayatTerbaru as $r): ?>
                                        <tr>
                                            <td style="font-size: 13px; font-weight: 600; color: #475569;">
                                                <?= date('d M Y', strtotime($r['tanggal_pemeriksaan'])) ?>
                                            </td>
                                            <td>
                                                <div style="font-weight: 700; color: #1E293B; font-size: 13.5px;"><?= htmlspecialchars($r['nama_lansia']) ?></div>
                                                <span style="font-size: 12px; color: #64748B;"><?= $r['usia_lansia'] ?> Tahun</span>
                                            </td>
                                            <td>
                                                <div style="font-weight: 700; color: #1E293B;">
                                                    <?= $r['tensi_sistolik'] ?>/<?= $r['tensi_diastolik'] ?> <small style="color:#64748B;">mmHg</small>
                                                </div>
                                                <div style="margin-top: 3px;">
                                                    <span class="badge <?= $r['status_tensi'] === 'Normal' ? 'badge-active' : 'badge-danger' ?>" style="font-size: 10.5px; padding: 2px 7px;">
                                                        <?= htmlspecialchars($r['status_tensi']) ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-size: 13px;">IMT: <strong><?= $r['imt'] ?></strong> <span style="font-size: 11.5px; color: #64748B;">(<?= htmlspecialchars($r['status_imt']) ?>)</span></div>
                                                <div style="font-size: 12px; color: #64748B; margin-top: 2px;">Gula Darah: <?= $r['gula_darah'] ? $r['gula_darah'] . ' mg/dL' : '-' ?></div>
                                            </td>
                                            <td>
                                                <?php
                                                    $kondisiClass = 'badge-active';
                                                    if ($r['status_kondisi'] === 'Risiko Tinggi') {
                                                        $kondisiClass = 'badge-danger';
                                                    } elseif ($r['status_kondisi'] === 'Perlu Perhatian') {
                                                        $kondisiClass = 'badge-pending';
                                                    }
                                                ?>
                                                <span class="badge <?= $kondisiClass ?>" style="font-weight: 700;">
                                                    <?= htmlspecialchars($r['status_kondisi']) ?>
                                                </span>
                                            </td>
                                            <td style="font-size: 13px; color: #475569;">
                                                <?= htmlspecialchars($r['nama_kader'] ?? 'Kader Posyandu') ?>
                                            </td>
                                            <td style="text-align: right;">
                                                <a href="riwayat.php?q=<?= urlencode($r['nama_lansia']) ?>" class="btn btn-sm btn-secondary" title="Buka Detail Rekam Medis">
                                                    <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
                                                    <span>Detail</span>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; color: #64748B; padding: 20px;">Belum ada data pemeriksaan kesehatan yang tercatat.</p>
                    <?php endif; ?>
                </div>

                <!-- 4. FITUR UTAMA 3: DAFTAR WARGA LANSIA & AKSI CEPAT -->
                <div class="card" id="section-lansia">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i data-lucide="user-plus" style="width: 18px; height: 18px; color: var(--primary);"></i>
                            Data Warga Lansia Terdaftar
                        </h3>
                        <div style="display: flex; gap: 8px;">
                            <a href="lansia.php" class="btn btn-sm btn-primary">
                                <i data-lucide="plus" style="width: 14px; height: 14px;"></i>
                                <span>Tambah Lansia</span>
                            </a>
                            <a href="lansia.php" class="btn btn-sm btn-secondary">Kelola Semua</a>
                        </div>
                    </div>

                    <?php if (count($lansiaTerbaru) > 0): ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>NIK</th>
                                        <th>Nama Lengkap</th>
                                        <th>Jenis Kelamin</th>
                                        <th>Usia</th>
                                        <th>Unit Posyandu</th>
                                        <th>Penanggung Jawab (Wali)</th>
                                        <th style="text-align: right;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lansiaTerbaru as $l): ?>
                                        <tr>
                                            <td style="font-family: monospace; font-size: 13px; color: #475569;">
                                                <?= htmlspecialchars($l['nik']) ?>
                                            </td>
                                            <td style="font-weight: 700; color: #1E293B; font-size: 13.5px;">
                                                <?= htmlspecialchars($l['nama_lengkap']) ?>
                                            </td>
                                            <td>
                                                <?= $l['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>
                                            </td>
                                            <td>
                                                <?= $l['usia'] ?> Tahun
                                            </td>
                                            <td>
                                                <span class="badge" style="background:#F1F5F9; color:#334155; font-weight: 600;">
                                                    <?= htmlspecialchars($l['nama_posyandu']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($l['nama_wali'] ?? 'Mandiri / Tanpa Wali') ?>
                                            </td>
                                            <td style="text-align: right;">
                                                <a href="lansia.php?q=<?= urlencode($l['nik']) ?>" class="btn btn-sm btn-secondary" title="Kelola Lansia">
                                                    <i data-lucide="edit-3" style="width: 13px; height: 13px;"></i>
                                                    <span>Ubah</span>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; color: #64748B; padding: 20px;">Belum ada data warga lansia yang terdaftar.</p>
                    <?php endif; ?>
                </div>

                <!-- 5. PANEL PINTASAN MENU CEPAT ADMINISTRATOR -->
                <h3 style="font-size: 16px; font-weight: 700; color: #1E293B; margin: 28px 0 16px; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="navigation" style="width: 18px; height: 18px; color: var(--primary);"></i>
                    Pintasan Pengelolaan Sistem
                </h3>

                <div class="features-grid">
                    <a href="acc-keluarga.php" class="feature-box" style="text-decoration: none; text-align: left; display: block;">
                        <div class="feature-icon-wrapper" style="background: #FEF3C7; color: #D97706;">
                            <i data-lucide="user-check" style="width: 22px; height: 22px;"></i>
                        </div>
                        <h4 class="feature-title">Persetujuan Akun Keluarga</h4>
                        <p class="feature-desc">Verifikasi akun baru anggota keluarga yang mendaftar secara mandiri.</p>
                        <span class="feature-tag">Buka Halaman ACC</span>
                    </a>

                    <a href="lansia.php" class="feature-box" style="text-decoration: none; text-align: left; display: block;">
                        <div class="feature-icon-wrapper" style="background: #ECFDF5; color: var(--primary);">
                            <i data-lucide="user-plus" style="width: 22px; height: 22px;"></i>
                        </div>
                        <h4 class="feature-title">Kelola Warga Lansia (CRUD)</h4>
                        <p class="feature-desc">Tambah, ubah identitas, hapus, dan kelola hubungan lansia dengan keluarga.</p>
                        <span class="feature-tag">Buka CRUD Lansia</span>
                    </a>

                    <a href="kader.php" class="feature-box" style="text-decoration: none; text-align: left; display: block;">
                        <div class="feature-icon-wrapper" style="background: #E0F2FE; color: #0284C7;">
                            <i data-lucide="shield" style="width: 22px; height: 22px;"></i>
                        </div>
                        <h4 class="feature-title">Kelola Kader Posyandu (CRUD)</h4>
                        <p class="feature-desc">Pendaftaran kader baru, penempatan posyandu, dan penetapan jabatan.</p>
                        <span class="feature-tag">Buka CRUD Kader</span>
                    </a>

                    <a href="riwayat.php" class="feature-box" style="text-decoration: none; text-align: left; display: block;">
                        <div class="feature-icon-wrapper" style="background: #FEE2E2; color: #DC2626;">
                            <i data-lucide="activity" style="width: 22px; height: 22px;"></i>
                        </div>
                        <h4 class="feature-title">Riwayat Rekam Medis (RUD)</h4>
                        <p class="feature-desc">Pantau seluruh hasil cek kesehatan, ubah hasil ukur, dan hapus pencatatan.</p>
                        <span class="feature-tag">Buka Rekam Medis</span>
                    </a>
                </div>

            </main>

        </div>

    </div>

    <!-- MODAL PENOLAKAN PENDAFTARAN KELUARGA -->
    <div class="modal-overlay" id="rejectModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modal-title">Tolak Pendaftaran Keluarga</h3>
                <button type="button" class="modal-close-btn" onclick="closeRejectModal()">
                    <i data-lucide="x" style="width: 18px; height: 18px;"></i>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject_keluarga">
                    <input type="hidden" name="id_user" id="rejectUserId">
                    <p style="font-size: 13.5px; color: #475569; margin-bottom: 14px;">
                        Anda akan menolak permohonan akun atas nama <strong id="rejectUserName" style="color:#1E293B;"></strong>.
                    </p>
                    <div class="form-group">
                        <label class="form-label" for="alasan_penolakan">Alasan Penolakan <span style="color:#EF4444;">*</span></label>
                        <textarea name="alasan_penolakan" id="alasan_penolakan" class="form-textarea" rows="3" required
                                  placeholder="Contoh: NIK tidak terdaftar di wilayah RT/RW binaan Posyandu ini atau foto dokumen tidak jelas."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak Akun Ini</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function openRejectModal(userId, userName) {
            document.getElementById('rejectUserId').value = userId;
            document.getElementById('rejectUserName').textContent = userName;
            document.getElementById('rejectModal').classList.add('active');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.remove('active');
        }
    </script>
</body>

</html>
