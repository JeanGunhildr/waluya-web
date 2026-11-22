<?php
// ==============================================================================
// WALUYA - RIWAYAT REKAM MEDIS LANSIA (ADMIN - RUD)
// ==============================================================================
// Admin memiliki hak akses RUD:
// 1. Read   : Memantau riwayat pemeriksaan seluruh posyandu dan filter kondisi
// 2. Update : Mengoreksi data rekam medis lansia jika kader keliru input
// 3. Delete : Menghapus rekaman yang tidak valid / duplikasi
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
$active_menu = 'riwayat';
$alert_msg = '';
$alert_type = '';

// ------------------------------------------------------------------------------
// FUNGSI BANTU PERHITUNGAN STATUS MEDIS STANDAR KEMENKES
// ------------------------------------------------------------------------------
function hitungIndikatorMedis($bb, $tb, $sis, $dia, $gula, $kol, $asam, $jk = 'L') {
    $tb_m = $tb > 0 ? ($tb / 100) : 0;
    $imt = $tb_m > 0 ? round($bb / ($tb_m * $tb_m), 2) : 0;

    if ($imt < 18.5) {
        $status_imt = 'Kurus';
    } elseif ($imt <= 24.9) {
        $status_imt = 'Normal';
    } elseif ($imt <= 29.9) {
        $status_imt = 'Kelebihan BB';
    } else {
        $status_imt = 'Obesitas';
    }

    if ($sis < 90 || $dia < 60) {
        $status_tensi = 'Hipotensi';
    } elseif ($sis < 120 && $dia < 80) {
        $status_tensi = 'Normal';
    } elseif (($sis >= 120 && $sis <= 139) || ($dia >= 80 && $dia <= 89)) {
        $status_tensi = 'Pra-hipertensi';
    } elseif (($sis >= 140 && $sis <= 159) || ($dia >= 90 && $dia <= 99)) {
        $status_tensi = 'Hipertensi 1';
    } else {
        $status_tensi = 'Hipertensi 2';
    }

    $status_gula = null;
    if ($gula !== null && $gula !== '') {
        $g = (int)$gula;
        if ($g < 70) $status_gula = 'Rendah';
        elseif ($g <= 140) $status_gula = 'Normal';
        else $status_gula = 'Tinggi';
    }

    $status_kol = null;
    if ($kol !== null && $kol !== '') {
        $status_kol = ((int)$kol <= 200) ? 'Normal' : 'Tinggi';
    }

    $status_asam = null;
    if ($asam !== null && $asam !== '') {
        $a = (float)$asam;
        $batas = ($jk === 'P') ? 6.0 : 7.0;
        $status_asam = ($a <= $batas) ? 'Normal' : 'Tinggi';
    }

    return [
        'imt'               => $imt,
        'status_imt'        => $status_imt,
        'status_tensi'      => $status_tensi,
        'status_gula_darah' => $status_gula,
        'status_kolesterol' => $status_kol,
        'status_asam_urat'  => $status_asam
    ];
}

// ------------------------------------------------------------------------------
// 1. PEMROSESAN AKSI RUD (UPDATE & DELETE)
// ------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $crud_action = $_POST['crud_action'] ?? '';

    // A. UPDATE / KOREKSI REKAM MEDIS
    if ($crud_action === 'update_riwayat') {
        $id_riwayat     = (int)($_POST['id_riwayat'] ?? 0);
        $tgl_periksa    = $_POST['tanggal_pemeriksaan'] ?? date('Y-m-d');
        $wkt_periksa    = $_POST['waktu_pemeriksaan'] ?? date('H:i:s');
        $berat_badan    = (float)($_POST['berat_badan'] ?? 0);
        $tinggi_badan   = (float)($_POST['tinggi_badan'] ?? 0);
        $tensi_sistolik = (int)($_POST['tensi_sistolik'] ?? 0);
        $tensi_diastolik= (int)($_POST['tensi_diastolik'] ?? 0);
        $detak_jantung  = (int)($_POST['detak_jantung'] ?? 0);
        $suhu_tubuh     = (float)($_POST['suhu_tubuh'] ?? 0);

        $gula_darah     = ($_POST['gula_darah'] !== '') ? (int)$_POST['gula_darah'] : null;
        $kolesterol     = ($_POST['kolesterol'] !== '') ? (int)$_POST['kolesterol'] : null;
        $asam_urat      = ($_POST['asam_urat'] !== '') ? (float)$_POST['asam_urat'] : null;

        $keluhan        = trim($_POST['keluhan'] ?? '');
        $catatan_kader  = trim($_POST['catatan_kader'] ?? '');
        $status_kondisi = $_POST['status_kondisi'] ?? 'Aman / Sehat';
        $rujukan        = $_POST['rujukan_puskesmas'] ?? 'Tidak';

        if ($id_riwayat <= 0 || $berat_badan <= 0 || $tinggi_badan <= 0 || $tensi_sistolik <= 0) {
            $alert_type = 'danger';
            $alert_msg = "Harap isi parameter fisik utama (Berat Badan, Tinggi Badan, dan Tekanan Darah).";
        } else {
            try {
                // Ambil data lansia untuk verifikasi jenis kelamin
                $stmtLansia = $pdo->prepare("SELECT l.jenis_kelamin, l.nama_lengkap FROM riwayat_kesehatan rk JOIN lansia l ON rk.id_lansia = l.id_lansia WHERE rk.id_riwayat = :id");
                $stmtLansia->execute(['id' => $id_riwayat]);
                $lansiaData = $stmtLansia->fetch();
                $jk = $lansiaData['jenis_kelamin'] ?? 'L';
                $nama_lansia = $lansiaData['nama_lengkap'] ?? "Lansia #$id_riwayat";

                // Hitung otomatis klasifikasi medis
                $kalkulasi = hitungIndikatorMedis($berat_badan, $tinggi_badan, $tensi_sistolik, $tensi_diastolik, $gula_darah, $kolesterol, $asam_urat, $jk);

                $updateSql = "
                    UPDATE riwayat_kesehatan SET
                        tanggal_pemeriksaan = :tgl,
                        waktu_pemeriksaan = :wkt,
                        berat_badan = :bb,
                        tinggi_badan = :tb,
                        imt = :imt,
                        status_imt = :st_imt,
                        tensi_sistolik = :sis,
                        tensi_diastolik = :dia,
                        status_tensi = :st_tensi,
                        detak_jantung = :hr,
                        suhu_tubuh = :suhu,
                        gula_darah = :gula,
                        status_gula_darah = :st_gula,
                        kolesterol = :kol,
                        status_kolesterol = :st_kol,
                        asam_urat = :asam,
                        status_asam_urat = :st_asam,
                        keluhan = :keluhan,
                        catatan_kader = :catatan,
                        status_kondisi = :kondisi,
                        rujukan_puskesmas = :rujukan,
                        updated_at = NOW()
                    WHERE id_riwayat = :id
                ";

                $stmtUpdate = $pdo->prepare($updateSql);
                $stmtUpdate->execute([
                    'tgl'      => $tgl_periksa,
                    'wkt'      => $wkt_periksa,
                    'bb'       => $berat_badan,
                    'tb'       => $tinggi_badan,
                    'imt'      => $kalkulasi['imt'],
                    'st_imt'   => $kalkulasi['status_imt'],
                    'sis'      => $tensi_sistolik,
                    'dia'      => $tensi_diastolik,
                    'st_tensi' => $kalkulasi['status_tensi'],
                    'hr'       => $detak_jantung,
                    'suhu'     => $suhu_tubuh,
                    'gula'     => $gula_darah,
                    'st_gula'  => $kalkulasi['status_gula_darah'],
                    'kol'      => $kolesterol,
                    'st_kol'   => $kalkulasi['status_kolesterol'],
                    'asam'     => $asam_urat,
                    'st_asam'  => $kalkulasi['status_asam_urat'],
                    'keluhan'  => $keluhan,
                    'catatan'  => $catatan_kader,
                    'kondisi'  => $status_kondisi,
                    'rujukan'  => $rujukan,
                    'id'       => $id_riwayat
                ]);

                $alert_type = 'success';
                $alert_msg = "Data rekam medis untuk <strong>" . htmlspecialchars($nama_lansia) . "</strong> (Pemeriksaan: " . date('d/m/Y', strtotime($tgl_periksa)) . ") berhasil diperbarui!";
            } catch (PDOException $e) {
                $alert_type = 'danger';
                $alert_msg = "Gagal memperbarui data rekam medis: " . $e->getMessage();
            }
        }
    }

    // B. DELETE / HAPUS REKAM MEDIS
    elseif ($crud_action === 'delete_riwayat') {
        $id_riwayat = (int)($_POST['id_riwayat'] ?? 0);

        if ($id_riwayat > 0) {
            try {
                $stmtDel = $pdo->prepare("DELETE FROM riwayat_kesehatan WHERE id_riwayat = :id");
                $stmtDel->execute(['id' => $id_riwayat]);

                $alert_type = 'success';
                $alert_msg = "Rekaman pemeriksaan medis ID #$id_riwayat berhasil dihapus secara permanen.";
            } catch (PDOException $e) {
                $alert_type = 'danger';
                $alert_msg = "Gagal menghapus rekaman: " . $e->getMessage();
            }
        }
    }
}

// ------------------------------------------------------------------------------
// 2. FILTER & PENCARIAN DATA RIWAYAT
// ------------------------------------------------------------------------------
$search        = trim($_GET['q'] ?? '');
$filterKondisi = $_GET['kondisi'] ?? 'semua';
$filterRujukan = $_GET['rujukan'] ?? 'semua';
$filterTanggal = $_GET['tanggal'] ?? '';

$sql = "
    SELECT 
        v.*,
        p.nama_posyandu
    FROM v_riwayat_kesehatan_lengkap v
    JOIN lansia l ON v.id_lansia = l.id_lansia
    JOIN posyandu p ON l.id_posyandu = p.id_posyandu
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND v.nama_lansia LIKE :s1";
    $params['s1'] = "%$search%";
}

if ($filterKondisi !== 'semua') {
    $sql .= " AND v.status_kondisi = :kondisi";
    $params['kondisi'] = $filterKondisi;
}

if ($filterRujukan !== 'semua') {
    $sql .= " AND v.rujukan_puskesmas = :rujukan";
    $params['rujukan'] = $filterRujukan;
}

if (!empty($filterTanggal)) {
    $sql .= " AND v.tanggal_pemeriksaan = :tgl";
    $params['tgl'] = $filterTanggal;
}

$sql .= " ORDER BY v.tanggal_pemeriksaan DESC, v.waktu_pemeriksaan DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $riwayatList = $stmt->fetchAll();
} catch (PDOException $e) {
    $riwayatList = [];
}

// ------------------------------------------------------------------------------
// 3. STATISTIK RINGKASAN REKAM MEDIS
// ------------------------------------------------------------------------------
try {
    $statQuery = $pdo->query("
        SELECT 
            COUNT(*) AS total_periksa,
            COUNT(CASE WHEN status_kondisi = 'Aman / Sehat' THEN 1 END) AS total_sehat,
            COUNT(CASE WHEN status_kondisi = 'Perlu Perhatian' THEN 1 END) AS total_perhatian,
            COUNT(CASE WHEN status_kondisi = 'Risiko Tinggi' THEN 1 END) AS total_risiko,
            COUNT(CASE WHEN rujukan_puskesmas = 'Ya' THEN 1 END) AS total_rujukan
        FROM riwayat_kesehatan
    ");
    $stats = $statQuery->fetch();
} catch (Exception $e) {
    $stats = ['total_periksa' => 0, 'total_sehat' => 0, 'total_perhatian' => 0, 'total_risiko' => 0, 'total_rujukan' => 0];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Rekam Medis (RUD) — Admin Waluya</title>

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
            <!-- TOPBAR -->
            <header class="dashboard-topbar">
                <div class="topbar-left">
                    <button type="button" class="topbar-mobile-toggle" onclick="toggleSidebar(true)" aria-label="Buka Menu">
                        <i data-lucide="menu" style="width: 20px; height: 20px;"></i>
                    </button>
                    <h2 class="topbar-page-title">Riwayat Rekam Medis Lansia (RUD)</h2>
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

                <!-- HEADER TITLE (Clean Dashboard Style, Tanpa Background Hijau) -->
                <div class="welcome-banner">
                    <h1 class="welcome-title">Pemantauan & Koreksi Rekam Medis (RUD)</h1>
                    <p class="welcome-subtitle">
                        Kelola data pemeriksaan berkala lansia Posyandu. Administrator memiliki wewenang untuk melihat catatan kesehatan (*Read*), mengoreksi kesalahan data pengukuran (*Update*), serta menghapus berkas tidak valid (*Delete*).
                    </p>
                </div>

                <!-- ALERT NOTIFIKASI -->
                <?php if (!empty($alert_msg)): ?>
                    <div class="alert-banner alert-banner-<?= $alert_type ?>">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i data-lucide="<?= $alert_type === 'success' ? 'check-circle' : 'alert-circle' ?>" style="width: 20px; height: 20px;"></i>
                            <span><?= $alert_msg ?></span>
                        </div>
                        <button type="button" onclick="this.parentElement.style.display='none'" style="background: none; border: none; cursor: pointer; color: inherit;">
                            <i data-lucide="x" style="width: 16px; height: 16px;"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- STATISTIK REKAM MEDIS -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="feature-icon-wrapper" style="background: #EFF6FF; color: #2563EB;">
                            <i data-lucide="clipboard-check" style="width: 22px; height: 22px;"></i>
                        </div>
                        <div>
                            <div class="kpi-value"><?= number_format($stats['total_periksa'] ?? 0) ?></div>
                            <div class="kpi-label">Total Pemeriksaan</div>
                            <div class="kpi-subtext">Seluruh sesi Posyandu</div>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="feature-icon-wrapper" style="background: #ECFDF5; color: #059669;">
                            <i data-lucide="shield-check" style="width: 22px; height: 22px;"></i>
                        </div>
                        <div>
                            <div class="kpi-value"><?= number_format($stats['total_sehat'] ?? 0) ?></div>
                            <div class="kpi-label">Aman / Sehat</div>
                            <div class="kpi-subtext">Kondisi fisik stabil</div>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="feature-icon-wrapper" style="background: #FFFBEB; color: #D97706;">
                            <i data-lucide="alert-triangle" style="width: 22px; height: 22px;"></i>
                        </div>
                        <div>
                            <div class="kpi-value"><?= number_format($stats['total_perhatian'] ?? 0) ?></div>
                            <div class="kpi-label">Perlu Perhatian</div>
                            <div class="kpi-subtext">Gejala awal / pra-hipertensi</div>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="feature-icon-wrapper" style="background: #FEF2F2; color: #DC2626;">
                            <i data-lucide="heart-pulse" style="width: 22px; height: 22px;"></i>
                        </div>
                        <div>
                            <div class="kpi-value"><?= number_format($stats['total_risiko'] ?? 0) ?></div>
                            <div class="kpi-label">Risiko Tinggi</div>
                            <div class="kpi-subtext"><?= $stats['total_rujukan'] ?> dirujuk Puskesmas</div>
                        </div>
                    </div>
                </div>

                <!-- CARD TABEL REKAM MEDIS -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i data-lucide="activity" style="width: 18px; height: 18px; color: var(--primary);"></i>
                            Daftar Hasil Pemeriksaan Kesehatan Lansia
                        </h3>
                        <span class="badge badge-active"><?= count($riwayatList) ?> Catatan Ditemukan</span>
                    </div>

                    <!-- PENCARIAN & FILTER -->
                    <form method="GET" action="riwayat.php" class="search-box-wrapper">
                        <div class="search-input-group">
                            <i data-lucide="search"></i>
                            <input type="text" name="q" placeholder="Cari berdasarkan nama lansia..." value="<?= htmlspecialchars($search) ?>">
                        </div>

                        <select name="kondisi" class="form-select" style="max-width: 180px;" onchange="this.form.submit()">
                            <option value="semua" <?= $filterKondisi === 'semua' ? 'selected' : '' ?>>Semua Kondisi</option>
                            <option value="Aman / Sehat" <?= $filterKondisi === 'Aman / Sehat' ? 'selected' : '' ?>>Aman / Sehat</option>
                            <option value="Perlu Perhatian" <?= $filterKondisi === 'Perlu Perhatian' ? 'selected' : '' ?>>Perlu Perhatian</option>
                            <option value="Risiko Tinggi" <?= $filterKondisi === 'Risiko Tinggi' ? 'selected' : '' ?>>Risiko Tinggi</option>
                        </select>

                        <select name="rujukan" class="form-select" style="max-width: 160px;" onchange="this.form.submit()">
                            <option value="semua" <?= $filterRujukan === 'semua' ? 'selected' : '' ?>>Semua Rujukan</option>
                            <option value="Ya" <?= $filterRujukan === 'Ya' ? 'selected' : '' ?>>Dirujuk ke PKM</option>
                            <option value="Tidak" <?= $filterRujukan === 'Tidak' ? 'selected' : '' ?>>Tanpa Rujukan</option>
                        </select>

                        <input type="date" name="tanggal" class="form-input" style="max-width: 160px;" value="<?= htmlspecialchars($filterTanggal) ?>" onchange="this.form.submit()">

                        <button type="submit" class="btn btn-primary btn-sm">
                            <i data-lucide="filter" style="width: 14px; height: 14px;"></i>
                            <span>Filter</span>
                        </button>

                        <?php if (!empty($search) || $filterKondisi !== 'semua' || $filterRujukan !== 'semua' || !empty($filterTanggal)): ?>
                            <a href="riwayat.php" class="btn btn-secondary btn-sm" title="Reset Filter">
                                <i data-lucide="rotate-ccw" style="width: 14px; height: 14px;"></i>
                                <span>Reset</span>
                            </a>
                        <?php endif; ?>
                    </form>

                    <!-- TABEL DATA -->
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 120px;">Tgl / Waktu</th>
                                    <th>Warga Lansia</th>
                                    <th>Tanda Vital</th>
                                    <th>Antropometri</th>
                                    <th>Hasil POCT (Lab)</th>
                                    <th>Status Kondisi</th>
                                    <th>Pemeriksa</th>
                                    <th style="width: 130px; text-align: center;">Aksi (RUD)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($riwayatList)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 36px; color: var(--text-muted);">
                                            <i data-lucide="clipboard-x" style="width: 36px; height: 36px; margin-bottom: 8px; opacity: 0.5;"></i>
                                            <p style="font-weight: 600;">Tidak ditemukan data riwayat rekam medis yang sesuai.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($riwayatList as $item): ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight: 700; color: var(--text-main); font-size: 13px;">
                                                    <?= date('d M Y', strtotime($item['tanggal_pemeriksaan'])) ?>
                                                </div>
                                                <div style="font-size: 11px; color: var(--text-muted);">
                                                    <?= date('H:i', strtotime($item['waktu_pemeriksaan'])) ?> WIB
                                                </div>
                                                <div style="font-size: 11px; color: var(--primary-dark); font-weight: 600; margin-top: 3px;">
                                                    <?= htmlspecialchars($item['nama_posyandu'] ?? 'Posyandu') ?>
                                                </div>
                                            </td>

                                            <td>
                                                <div style="font-weight: 700; color: var(--text-main); font-size: 14px;">
                                                    <?= htmlspecialchars($item['nama_lansia']) ?>
                                                </div>
                                                <div style="font-size: 12px; color: var(--text-muted);">
                                                    NIK: <?= htmlspecialchars($item['nik_lansia']) ?>
                                                </div>
                                                <div style="font-size: 11.5px; color: var(--text-secondary); margin-top: 2px;">
                                                    <span style="font-weight: 600;"><?= $item['usia_lansia'] ?> thn</span> • 
                                                    <?= $item['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>
                                                </div>
                                                <?php if (!empty($item['nama_keluarga_wali'])): ?>
                                                    <div style="font-size: 11px; color: #0284C7; margin-top: 2px;">
                                                        <i data-lucide="user" style="width: 10px; height: 10px; display: inline;"></i> Wali: <?= htmlspecialchars($item['nama_keluarga_wali']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <div style="font-weight: 700; font-size: 13.5px; color: #1E293B;">
                                                    <?= $item['tensi_sistolik'] ?>/<?= $item['tensi_diastolik'] ?> <span style="font-size: 11px; font-weight: normal; color: var(--text-muted);">mmHg</span>
                                                </div>
                                                <div style="margin-top: 4px;">
                                                    <?php
                                                    $tensiBadge = 'badge-active';
                                                    if ($item['status_tensi'] === 'Hipertensi 1') $tensiBadge = 'badge-pending';
                                                    if ($item['status_tensi'] === 'Hipertensi 2') $tensiBadge = 'badge-danger';
                                                    if ($item['status_tensi'] === 'Pra-hipertensi') $tensiBadge = 'badge-pending';
                                                    ?>
                                                    <span class="badge <?= $tensiBadge ?>" style="font-size: 10.5px; padding: 2px 7px;">
                                                        <?= htmlspecialchars($item['status_tensi']) ?>
                                                    </span>
                                                </div>
                                                <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">
                                                    Nadi: <strong><?= $item['detak_jantung'] ?></strong> bpm | Suhu: <strong><?= number_format($item['suhu_tubuh'], 1) ?></strong>°C
                                                </div>
                                            </td>

                                            <td>
                                                <div style="font-size: 12.5px; color: var(--text-main);">
                                                    BB: <strong><?= number_format($item['berat_badan'], 1) ?></strong> kg | TB: <strong><?= number_format($item['tinggi_badan'], 0) ?></strong> cm
                                                </div>
                                                <div style="font-size: 12.5px; font-weight: 700; color: var(--text-secondary); margin-top: 2px;">
                                                    IMT: <?= number_format($item['imt'], 1) ?>
                                                </div>
                                                <div style="margin-top: 3px;">
                                                    <span class="badge <?= ($item['status_imt'] === 'Normal') ? 'badge-active' : 'badge-pending' ?>" style="font-size: 10.5px; padding: 2px 6px;">
                                                        <?= htmlspecialchars($item['status_imt']) ?>
                                                    </span>
                                                </div>
                                            </td>

                                            <td>
                                                <div style="font-size: 11.5px; line-height: 1.6;">
                                                    <div>Gula Darah: <strong><?= $item['gula_darah'] !== null ? $item['gula_darah'] . ' mg/dL' : '-' ?></strong></div>
                                                    <div>Kolesterol: <strong><?= $item['kolesterol'] !== null ? $item['kolesterol'] . ' mg/dL' : '-' ?></strong></div>
                                                    <div>Asam Urat: <strong><?= $item['asam_urat'] !== null ? number_format($item['asam_urat'], 1) . ' mg/dL' : '-' ?></strong></div>
                                                </div>
                                            </td>

                                            <td>
                                                <?php
                                                $kondisiBadge = 'badge-active';
                                                if ($item['status_kondisi'] === 'Perlu Perhatian') $kondisiBadge = 'badge-pending';
                                                if ($item['status_kondisi'] === 'Risiko Tinggi') $kondisiBadge = 'badge-danger';
                                                ?>
                                                <span class="badge <?= $kondisiBadge ?>" style="font-weight: 700;">
                                                    <?= htmlspecialchars($item['status_kondisi']) ?>
                                                </span>
                                                <?php if ($item['rujukan_puskesmas'] === 'Ya'): ?>
                                                    <div style="margin-top: 5px;">
                                                        <span class="badge badge-danger" style="font-size: 10.5px; padding: 2px 7px;">
                                                            <i data-lucide="alert-circle" style="width: 10px; height: 10px; display: inline;"></i> Rujukan PKM
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <div style="font-weight: 600; font-size: 12.5px; color: var(--text-main);">
                                                    <?= htmlspecialchars($item['nama_kader_pemeriksa'] ?? 'Kader Posyandu') ?>
                                                </div>
                                                <div style="font-size: 11px; color: var(--text-muted);">
                                                    Petugas Verifikator
                                                </div>
                                            </td>

                                            <td style="text-align: center;">
                                                <div class="table-actions" style="justify-content: center;">
                                                    <!-- Tombol Lihat Detail KMS -->
                                                    <button type="button" class="btn btn-secondary btn-sm" title="Lihat Rekam Medis Lengkap" onclick="bukaModalDetail(<?= htmlspecialchars(json_encode($item)) ?>)">
                                                        <i data-lucide="eye" style="width: 13px; height: 13px;"></i>
                                                    </button>

                                                    <!-- Tombol Koreksi / Edit (Update) -->
                                                    <button type="button" class="btn btn-warning btn-sm" title="Koreksi Data Pemeriksaan (Update)" onclick="bukaModalEdit(<?= htmlspecialchars(json_encode($item)) ?>)">
                                                        <i data-lucide="edit-3" style="width: 13px; height: 13px;"></i>
                                                    </button>

                                                    <!-- Tombol Hapus (Delete) -->
                                                    <button type="button" class="btn btn-outline-danger btn-sm" title="Hapus Rekam Medis (Delete)" onclick="bukaModalHapus(<?= $item['id_riwayat'] ?>, '<?= htmlspecialchars(addslashes($item['nama_lansia'])) ?>', '<?= date('d/m/Y', strtotime($item['tanggal_pemeriksaan'])) ?>')">
                                                        <i data-lucide="trash-2" style="width: 13px; height: 13px;"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- ============================================================================== -->
    <!-- MODAL 1: DETAIL LENGKAP REKAM MEDIS (KMS DIGITAL) -->
    <!-- ============================================================================== -->
    <div class="modal-overlay" id="modalDetail">
        <div class="modal-card" style="max-width: 680px;">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="clipboard-list" style="width: 20px; height: 20px; color: var(--primary);"></i>
                    <h3 class="modal-title">Kartu Rekam Medis Lansia (KMS Digital)</h3>
                </div>
                <button type="button" class="modal-close-btn" onclick="tutupModal('modalDetail')">
                    <i data-lucide="x" style="width: 18px; height: 18px;"></i>
                </button>
            </div>
            <div class="modal-body" id="detailBodyContent">
                <!-- Diisi via JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" onclick="tutupModal('modalDetail')">Tutup</button>
            </div>
        </div>
    </div>

    <!-- ============================================================================== -->
    <!-- MODAL 2: EDIT / KOREKSI DATA PEMERIKSAAN (UPDATE) -->
    <!-- ============================================================================== -->
    <div class="modal-overlay" id="modalEdit">
        <div class="modal-card" style="max-width: 720px;">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="edit-3" style="width: 20px; height: 20px; color: #D97706;"></i>
                    <h3 class="modal-title">Koreksi Data Rekam Medis (Update)</h3>
                </div>
                <button type="button" class="modal-close-btn" onclick="tutupModal('modalEdit')">
                    <i data-lucide="x" style="width: 18px; height: 18px;"></i>
                </button>
            </div>

            <form method="POST" action="riwayat.php" id="formEditRiwayat">
                <input type="hidden" name="crud_action" value="update_riwayat">
                <input type="hidden" name="id_riwayat" id="edit_id_riwayat">

                <div class="modal-body">
                    <div style="padding: 12px 16px; background: #F8FAFC; border-radius: 8px; border-left: 4px solid var(--primary); margin-bottom: 20px;">
                        <span style="font-size: 13.5px; font-weight: 700; color: var(--text-main);" id="edit_nama_lansia_badge">Nama Lansia</span>
                        <div style="font-size: 12px; color: var(--text-muted);" id="edit_posyandu_info">Posyandu Lansia</div>
                    </div>

                    <!-- Waktu Pemeriksaan -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tanggal Pemeriksaan *</label>
                            <input type="date" name="tanggal_pemeriksaan" id="edit_tanggal_pemeriksaan" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Waktu Pemeriksaan *</label>
                            <input type="time" name="waktu_pemeriksaan" id="edit_waktu_pemeriksaan" class="form-input" required>
                        </div>
                    </div>

                    <!-- Antropometri -->
                    <div style="font-weight: 700; font-size: 13.5px; color: var(--text-main); margin: 16px 0 10px; border-bottom: 1px solid var(--border-light); padding-bottom: 6px;">
                        1. Parameter Antropometri Fisik
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Berat Badan (kg) *</label>
                            <input type="number" step="0.1" name="berat_badan" id="edit_berat_badan" class="form-input" required oninput="hitungOtomatisKoreksi()">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tinggi Badan (cm) *</label>
                            <input type="number" step="0.5" name="tinggi_badan" id="edit_tinggi_badan" class="form-input" required oninput="hitungOtomatisKoreksi()">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Indeks Massa Tubuh (IMT)</label>
                            <input type="text" id="preview_imt" class="form-input" style="background: #F1F5F9; font-weight: 700;" readonly>
                        </div>
                    </div>

                    <!-- Tanda Vital -->
                    <div style="font-weight: 700; font-size: 13.5px; color: var(--text-main); margin: 16px 0 10px; border-bottom: 1px solid var(--border-light); padding-bottom: 6px;">
                        2. Tanda-Tanda Vital
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tensi Sistolik (mmHg) *</label>
                            <input type="number" name="tensi_sistolik" id="edit_tensi_sistolik" class="form-input" required oninput="hitungOtomatisKoreksi()">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tensi Diastolik (mmHg) *</label>
                            <input type="number" name="tensi_diastolik" id="edit_tensi_diastolik" class="form-input" required oninput="hitungOtomatisKoreksi()">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Detak Jantung / Nadi (bpm) *</label>
                            <input type="number" name="detak_jantung" id="edit_detak_jantung" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Suhu Tubuh (°C) *</label>
                            <input type="number" step="0.1" name="suhu_tubuh" id="edit_suhu_tubuh" class="form-input" required>
                        </div>
                    </div>

                    <!-- Tes Laboratorium Sederhana (POCT) -->
                    <div style="font-weight: 700; font-size: 13.5px; color: var(--text-main); margin: 16px 0 10px; border-bottom: 1px solid var(--border-light); padding-bottom: 6px;">
                        3. Tes Laboratorium Sederhana / POCT
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Gula Darah Sewaktu (mg/dL)</label>
                            <input type="number" name="gula_darah" id="edit_gula_darah" class="form-input" placeholder="Contoh: 110">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kolesterol Total (mg/dL)</label>
                            <input type="number" name="kolesterol" id="edit_kolesterol" class="form-input" placeholder="Contoh: 180">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Asam Urat (mg/dL)</label>
                            <input type="number" step="0.1" name="asam_urat" id="edit_asam_urat" class="form-input" placeholder="Contoh: 5.4">
                        </div>
                    </div>

                    <!-- Catatan & Rekomendasi -->
                    <div style="font-weight: 700; font-size: 13.5px; color: var(--text-main); margin: 16px 0 10px; border-bottom: 1px solid var(--border-light); padding-bottom: 6px;">
                        4. Keluhan, Status & Tindak Lanjut
                    </div>
                    <div class="form-group">
                        <label class="form-label">Keluhan Fisik Lansia</label>
                        <textarea name="keluhan" id="edit_keluhan" class="form-textarea" rows="2" placeholder="Keluhan yang dirasakan lansia..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Catatan & Saran Edukasi Kader</label>
                        <textarea name="catatan_kader" id="edit_catatan_kader" class="form-textarea" rows="2" placeholder="Saran pola makan, aktivitas fisik, atau obat..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Kesimpulan Status Kondisi *</label>
                            <select name="status_kondisi" id="edit_status_kondisi" class="form-select" required>
                                <option value="Aman / Sehat">Aman / Sehat</option>
                                <option value="Perlu Perhatian">Perlu Perhatian</option>
                                <option value="Risiko Tinggi">Risiko Tinggi</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Perlu Rujukan ke Puskesmas? *</label>
                            <select name="rujukan_puskesmas" id="edit_rujukan_puskesmas" class="form-select" required>
                                <option value="Tidak">Tidak Perlu</option>
                                <option value="Ya">Ya, Rujuk ke Puskesmas</option>
                            </select>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="tutupModal('modalEdit')">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save" style="width: 14px; height: 14px;"></i>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================================== -->
    <!-- MODAL 3: KONFIRMASI HAPUS REKAM MEDIS (DELETE) -->
    <!-- ============================================================================== -->
    <div class="modal-overlay" id="modalHapus">
        <div class="modal-card" style="max-width: 440px;">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="alert-triangle" style="width: 20px; height: 20px; color: #DC2626;"></i>
                    <h3 class="modal-title">Hapus Rekam Medis</h3>
                </div>
                <button type="button" class="modal-close-btn" onclick="tutupModal('modalHapus')">
                    <i data-lucide="x" style="width: 18px; height: 18px;"></i>
                </button>
            </div>
            <form method="POST" action="riwayat.php">
                <input type="hidden" name="crud_action" value="delete_riwayat">
                <input type="hidden" name="id_riwayat" id="hapus_id_riwayat">

                <div class="modal-body" style="text-align: center; padding: 24px;">
                    <div style="width: 48px; height: 48px; border-radius: 50%; background: #FEE2E2; color: #DC2626; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                        <i data-lucide="trash-2" style="width: 24px; height: 24px;"></i>
                    </div>
                    <h4 style="font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 8px;">Konfirmasi Penghapusan</h4>
                    <p style="font-size: 13.5px; color: var(--text-muted); line-height: 1.5;">
                        Apakah Anda yakin ingin menghapus data rekam medis <strong id="hapus_nama_lansia" style="color: var(--text-main);"></strong> tanggal <strong id="hapus_tgl_pemeriksaan"></strong>? Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>

                <div class="modal-footer" style="justify-content: center;">
                    <button type="button" class="btn btn-secondary" onclick="tutupModal('modalHapus')">Batal</button>
                    <button type="submit" class="btn btn-logout">
                        <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                        <span>Ya, Hapus Data</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        lucide.createIcons();

        function tutupModal(id) {
            const m = document.getElementById(id);
            if (m) m.classList.remove('active');
        }

        // BUKA MODAL DETAIL
        function bukaModalDetail(data) {
            const body = document.getElementById('detailBodyContent');
            if (!body) return;

            const tgl = new Date(data.tanggal_pemeriksaan).toLocaleDateString('id-ID', {
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
            });

            body.innerHTML = `
                <div style="padding: 14px 18px; background: #F8FAFC; border-radius: 10px; margin-bottom: 20px; border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <div style="font-size: 16px; font-weight: 800; color: var(--text-main);">${escapeHtml(data.nama_lansia)}</div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                            NIK: ${escapeHtml(data.nik_lansia)} • Usia: <strong>${data.usia_lansia} Tahun</strong> (${data.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'})
                        </div>
                        <div style="font-size: 12px; color: #0284C7; margin-top: 4px;">
                            Wali / Keluarga: <strong>${escapeHtml(data.nama_keluarga_wali || 'Belum ditautkan')}</strong>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span class="badge ${data.status_kondisi === 'Aman / Sehat' ? 'badge-active' : (data.status_kondisi === 'Perlu Perhatian' ? 'badge-pending' : 'badge-danger')}" style="font-size: 12px; padding: 4px 10px;">
                            ${escapeHtml(data.status_kondisi)}
                        </span>
                        <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">${tgl}</div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin-bottom: 20px;">
                    <!-- Kotak 1: Antropometri -->
                    <div style="padding: 16px; border: 1px solid var(--border-color); border-radius: 10px; background: #FFFFFF;">
                        <h4 style="font-size: 13px; font-weight: 700; color: var(--text-secondary); margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                            <i data-lucide="scale" style="width: 15px; height: 15px; color: var(--primary);"></i>
                            Antropometri Fisik
                        </h4>
                        <div style="display: flex; flex-direction: column; gap: 8px; font-size: 13px;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Berat Badan:</span>
                                <strong>${Number(data.berat_badan).toFixed(1)} kg</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Tinggi Badan:</span>
                                <strong>${Number(data.tinggi_badan).toFixed(0)} cm</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Indeks Massa Tubuh:</span>
                                <strong>${Number(data.imt).toFixed(1)} (${escapeHtml(data.status_imt)})</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Kotak 2: Tanda Vital -->
                    <div style="padding: 16px; border: 1px solid var(--border-color); border-radius: 10px; background: #FFFFFF;">
                        <h4 style="font-size: 13px; font-weight: 700; color: var(--text-secondary); margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                            <i data-lucide="heart" style="width: 15px; height: 15px; color: #EF4444;"></i>
                            Tanda Vital
                        </h4>
                        <div style="display: flex; flex-direction: column; gap: 8px; font-size: 13px;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Tekanan Darah:</span>
                                <strong>${data.tensi_sistolik}/${data.tensi_diastolik} mmHg</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Kategori Tensi:</span>
                                <span class="badge ${data.status_tensi === 'Normal' ? 'badge-active' : 'badge-pending'}">${escapeHtml(data.status_tensi)}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Detak Nadi:</span>
                                <strong>${data.detak_jantung} bpm</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Suhu Tubuh:</span>
                                <strong>${Number(data.suhu_tubuh).toFixed(1)} °C</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kotak 3: Hasil POCT -->
                <div style="padding: 16px; border: 1px solid var(--border-color); border-radius: 10px; background: #FFFFFF; margin-bottom: 20px;">
                    <h4 style="font-size: 13px; font-weight: 700; color: var(--text-secondary); margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="test-tube" style="width: 15px; height: 15px; color: #8B5CF6;"></i>
                        Hasil Pemeriksaan Laboratorium Sederhana (POCT)
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; font-size: 13px;">
                        <div style="background: #F8FAFC; padding: 10px 14px; border-radius: 8px;">
                            <div style="color: var(--text-muted); font-size: 11.5px;">Gula Darah Sewaktu</div>
                            <div style="font-size: 16px; font-weight: 800; color: var(--text-main); margin-top: 2px;">
                                ${data.gula_darah ? data.gula_darah + ' mg/dL' : '<span style="font-weight:normal; color:#94A3B8;">Tidak dicek</span>'}
                            </div>
                        </div>
                        <div style="background: #F8FAFC; padding: 10px 14px; border-radius: 8px;">
                            <div style="color: var(--text-muted); font-size: 11.5px;">Kolesterol Total</div>
                            <div style="font-size: 16px; font-weight: 800; color: var(--text-main); margin-top: 2px;">
                                ${data.kolesterol ? data.kolesterol + ' mg/dL' : '<span style="font-weight:normal; color:#94A3B8;">Tidak dicek</span>'}
                            </div>
                        </div>
                        <div style="background: #F8FAFC; padding: 10px 14px; border-radius: 8px;">
                            <div style="color: var(--text-muted); font-size: 11.5px;">Asam Urat</div>
                            <div style="font-size: 16px; font-weight: 800; color: var(--text-main); margin-top: 2px;">
                                ${data.asam_urat ? Number(data.asam_urat).toFixed(1) + ' mg/dL' : '<span style="font-weight:normal; color:#94A3B8;">Tidak dicek</span>'}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kotak 4: Keluhan & Saran Edukasi -->
                <div style="padding: 16px; border: 1px solid var(--border-color); border-radius: 10px; background: #FFFFFF; font-size: 13px; line-height: 1.6;">
                    <div style="margin-bottom: 12px;">
                        <span style="font-weight: 700; color: var(--text-main); display: block; margin-bottom: 2px;">Keluhan Lansia:</span>
                        <div style="color: var(--text-secondary);">${escapeHtml(data.keluhan || 'Tidak ada keluhan khusus yang dicatat.')}</div>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <span style="font-weight: 700; color: var(--text-main); display: block; margin-bottom: 2px;">Catatan & Tindakan Kader:</span>
                        <div style="color: var(--text-secondary);">${escapeHtml(data.catatan_kader || 'Lanjutkan pemantauan rutin bulan berikutnya.')}</div>
                    </div>
                    <div style="border-top: 1px solid var(--border-light); padding-top: 10px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                        <div>
                            Rujukan Puskesmas: 
                            <strong style="color: ${data.rujukan_puskesmas === 'Ya' ? '#DC2626' : '#059669'};">
                                ${data.rujukan_puskesmas === 'Ya' ? 'YA, MEMERLUKAN RUJUKAN' : 'TIDAK DIPERLUKAN'}
                            </strong>
                        </div>
                        <div style="font-size: 12px; color: var(--text-muted);">
                            Kader Pemeriksa: <strong>${escapeHtml(data.nama_kader_pemeriksa || '-')}</strong>
                        </div>
                    </div>
                </div>
            `;

            lucide.createIcons();
            document.getElementById('modalDetail').classList.add('active');
        }

        // BUKA MODAL EDIT (UPDATE)
        function bukaModalEdit(data) {
            document.getElementById('edit_id_riwayat').value = data.id_riwayat;
            document.getElementById('edit_nama_lansia_badge').textContent = data.nama_lansia + ' (NIK: ' + data.nik_lansia + ')';
            document.getElementById('edit_posyandu_info').textContent = 'Puskesmas / ' + (data.nama_posyandu || 'Posyandu Lansia');

            document.getElementById('edit_tanggal_pemeriksaan').value = data.tanggal_pemeriksaan;
            document.getElementById('edit_waktu_pemeriksaan').value = data.waktu_pemeriksaan;

            document.getElementById('edit_berat_badan').value = data.berat_badan;
            document.getElementById('edit_tinggi_badan').value = data.tinggi_badan;
            document.getElementById('edit_tensi_sistolik').value = data.tensi_sistolik;
            document.getElementById('edit_tensi_diastolik').value = data.tensi_diastolik;
            document.getElementById('edit_detak_jantung').value = data.detak_jantung;
            document.getElementById('edit_suhu_tubuh').value = data.suhu_tubuh;

            document.getElementById('edit_gula_darah').value = data.gula_darah || '';
            document.getElementById('edit_kolesterol').value = data.kolesterol || '';
            document.getElementById('edit_asam_urat').value = data.asam_urat || '';

            document.getElementById('edit_keluhan').value = data.keluhan || '';
            document.getElementById('edit_catatan_kader').value = data.catatan_kader || '';
            document.getElementById('edit_status_kondisi').value = data.status_kondisi;
            document.getElementById('edit_rujukan_puskesmas').value = data.rujukan_puskesmas;

            hitungOtomatisKoreksi();
            document.getElementById('modalEdit').classList.add('active');
        }

        // PERHITUNGAN OTOMATIS REALTIME DI FORM KOREKSI
        function hitungOtomatisKoreksi() {
            const bb = parseFloat(document.getElementById('edit_berat_badan').value) || 0;
            const tb = parseFloat(document.getElementById('edit_tinggi_badan').value) || 0;
            const sis = parseInt(document.getElementById('edit_tensi_sistolik').value) || 0;
            const dia = parseInt(document.getElementById('edit_tensi_diastolik').value) || 0;

            if (bb > 0 && tb > 0) {
                const tbM = tb / 100;
                const imt = (bb / (tbM * tbM)).toFixed(2);
                let label = 'Normal';
                if (imt < 18.5) label = 'Kurus';
                else if (imt > 29.9) label = 'Obesitas';
                else if (imt > 24.9) label = 'Kelebihan BB';

                document.getElementById('preview_imt').value = imt + ' (' + label + ')';
            } else {
                document.getElementById('preview_imt').value = '-';
            }

            // Saran otomatis kondisi jika sistolik ekstrem
            const kondisiSel = document.getElementById('edit_status_kondisi');
            const rujukanSel = document.getElementById('edit_rujukan_puskesmas');
            if (sis >= 160 || dia >= 100) {
                if (kondisiSel.value === 'Aman / Sehat') kondisiSel.value = 'Risiko Tinggi';
                if (rujukanSel.value === 'Tidak') rujukanSel.value = 'Ya';
            }
        }

        // BUKA MODAL HAPUS
        function bukaModalHapus(id, nama, tgl) {
            document.getElementById('hapus_id_riwayat').value = id;
            document.getElementById('hapus_nama_lansia').textContent = nama;
            document.getElementById('hapus_tgl_pemeriksaan').textContent = tgl;
            document.getElementById('modalHapus').classList.add('active');
        }

        function escapeHtml(text) {
            if (!text) return '';
            return String(text)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    </script>
</body>

</html>
