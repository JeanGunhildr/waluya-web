<?php
// ==============================================================================
// WALUYA - MANAJEMEN JADWAL KEGIATAN POSYANDU (ADMINISTRATOR - CRUD)
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
$active_menu = 'jadwal';
$alert_msg = '';
$alert_type = '';

// ------------------------------------------------------------------------------
// 1. PEMROSESAN AKSI CRUD JADWAL
// ------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $crud_action = $_POST['crud_action'] ?? '';

    // A. TAMBAH JADWAL BARU
    if ($crud_action === 'tambah') {
        $id_posyandu    = (int)($_POST['id_posyandu'] ?? 0);
        $id_kader       = !empty($_POST['id_kader']) ? (int)$_POST['id_kader'] : null;
        $judul          = trim($_POST['judul'] ?? '');
        $jenis_kegiatan = $_POST['jenis_kegiatan'] ?? 'Pemeriksaan Rutin';
        $tanggal        = $_POST['tanggal'] ?? '';
        $jam_mulai      = $_POST['jam_mulai'] ?? '';
        $jam_selesai    = !empty($_POST['jam_selesai']) ? $_POST['jam_selesai'] : null;
        $lokasi         = trim($_POST['lokasi'] ?? '');
        $deskripsi      = trim($_POST['deskripsi'] ?? '');
        $target_peserta = !empty($_POST['target_peserta']) ? (int)$_POST['target_peserta'] : null;
        $status         = $_POST['status'] ?? 'terjadwal';

        if ($id_posyandu <= 0 || empty($judul) || empty($tanggal) || empty($jam_mulai) || empty($lokasi)) {
            $alert_type = 'danger';
            $alert_msg = "Harap lengkapi kolom wajib bertanda bintang (*) : Posyandu, Judul, Tanggal, Jam Mulai, dan Lokasi.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO jadwal_posyandu (id_posyandu, id_kader, judul, jenis_kegiatan, tanggal, jam_mulai, jam_selesai, lokasi, deskripsi, target_peserta, status, created_at)
                    VALUES (:pos, :kdr, :judul, :jenis, :tgl, :mulai, :selesai, :lok, :desk, :target, :st, NOW())
                ");
                $stmt->execute([
                    'pos'     => $id_posyandu,
                    'kdr'     => $id_kader,
                    'judul'   => $judul,
                    'jenis'   => $jenis_kegiatan,
                    'tgl'     => $tanggal,
                    'mulai'   => $jam_mulai,
                    'selesai' => $jam_selesai,
                    'lok'     => $lokasi,
                    'desk'    => $deskripsi,
                    'target'  => $target_peserta,
                    'st'      => $status
                ]);

                $alert_type = 'success';
                $alert_msg = "Jadwal kegiatan <strong>" . htmlspecialchars($judul) . "</strong> berhasil ditambahkan!";
            } catch (PDOException $e) {
                $alert_type = 'danger';
                $alert_msg = "Gagal menambahkan jadwal kegiatan: " . $e->getMessage();
            }
        }
    }

    // B. UBAH / UPDATE JADWAL
    elseif ($crud_action === 'edit') {
        $id_jadwal      = (int)($_POST['id_jadwal'] ?? 0);
        $id_posyandu    = (int)($_POST['id_posyandu'] ?? 0);
        $id_kader       = !empty($_POST['id_kader']) ? (int)$_POST['id_kader'] : null;
        $judul          = trim($_POST['judul'] ?? '');
        $jenis_kegiatan = $_POST['jenis_kegiatan'] ?? 'Pemeriksaan Rutin';
        $tanggal        = $_POST['tanggal'] ?? '';
        $jam_mulai      = $_POST['jam_mulai'] ?? '';
        $jam_selesai    = !empty($_POST['jam_selesai']) ? $_POST['jam_selesai'] : null;
        $lokasi         = trim($_POST['lokasi'] ?? '');
        $deskripsi      = trim($_POST['deskripsi'] ?? '');
        $target_peserta = !empty($_POST['target_peserta']) ? (int)$_POST['target_peserta'] : null;
        $status         = $_POST['status'] ?? 'terjadwal';

        if ($id_jadwal <= 0 || $id_posyandu <= 0 || empty($judul) || empty($tanggal) || empty($jam_mulai) || empty($lokasi)) {
            $alert_type = 'danger';
            $alert_msg = "Harap lengkapi semua kolom wajib bertanda bintang (*).";
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE jadwal_posyandu SET
                        id_posyandu    = :pos,
                        id_kader       = :kdr,
                        judul          = :judul,
                        jenis_kegiatan = :jenis,
                        tanggal        = :tgl,
                        jam_mulai      = :mulai,
                        jam_selesai    = :selesai,
                        lokasi         = :lok,
                        deskripsi      = :desk,
                        target_peserta = :target,
                        status         = :st,
                        updated_at     = NOW()
                    WHERE id_jadwal    = :id
                ");
                $stmt->execute([
                    'pos'     => $id_posyandu,
                    'kdr'     => $id_kader,
                    'judul'   => $judul,
                    'jenis'   => $jenis_kegiatan,
                    'tgl'     => $tanggal,
                    'mulai'   => $jam_mulai,
                    'selesai' => $jam_selesai,
                    'lok'     => $lokasi,
                    'desk'    => $deskripsi,
                    'target'  => $target_peserta,
                    'st'      => $status,
                    'id'      => $id_jadwal
                ]);

                $alert_type = 'success';
                $alert_msg = "Jadwal kegiatan <strong>" . htmlspecialchars($judul) . "</strong> berhasil diperbarui!";
            } catch (PDOException $e) {
                $alert_type = 'danger';
                $alert_msg = "Gagal memperbarui jadwal: " . $e->getMessage();
            }
        }
    }

    // C. UPDATE STATUS CEPAT
    elseif ($crud_action === 'update_status') {
        $id_jadwal   = (int)($_POST['id_jadwal'] ?? 0);
        $status_baru = $_POST['status_baru'] ?? 'terjadwal';

        if ($id_jadwal > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE jadwal_posyandu SET status = :st, updated_at = NOW() WHERE id_jadwal = :id");
                $stmt->execute(['st' => $status_baru, 'id' => $id_jadwal]);

                $alert_type = 'success';
                $alert_msg = "Status kegiatan berhasil diubah menjadi <strong>" . strtoupper($status_baru) . "</strong>.";
            } catch (PDOException $e) {
                $alert_type = 'danger';
                $alert_msg = "Gagal mengubah status: " . $e->getMessage();
            }
        }
    }

    // D. HAPUS JADWAL
    elseif ($crud_action === 'hapus') {
        $id_jadwal = (int)($_POST['id_jadwal'] ?? 0);

        if ($id_jadwal > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM jadwal_posyandu WHERE id_jadwal = :id");
                $stmt->execute(['id' => $id_jadwal]);

                $alert_type = 'success';
                $alert_msg = "Jadwal kegiatan berhasil dihapus dari sistem.";
            } catch (PDOException $e) {
                $alert_type = 'danger';
                $alert_msg = "Gagal menghapus jadwal: " . $e->getMessage();
            }
        }
    }
}

// ------------------------------------------------------------------------------
// 2. QUERY DAFTAR POSYANDU & KADER UNTUK FORM DROPDOWN
// ------------------------------------------------------------------------------
$listPosyandu = [];
try {
    $listPosyandu = $pdo->query("SELECT id_posyandu, nama_posyandu FROM posyandu WHERE status = 'aktif' ORDER BY nama_posyandu ASC")->fetchAll();
} catch (Exception $e) {
    $listPosyandu = [];
}

$listKader = [];
try {
    $listKader = $pdo->query("
        SELECT k.id_kader, u.nama_lengkap, k.jabatan, p.nama_posyandu
        FROM kader k
        JOIN users u ON k.id_user = u.id_user
        JOIN posyandu p ON k.id_posyandu = p.id_posyandu
        WHERE k.status_kader = 'aktif'
        ORDER BY u.nama_lengkap ASC
    ")->fetchAll();
} catch (Exception $e) {
    $listKader = [];
}

// ------------------------------------------------------------------------------
// 3. FILTER & PENCARIAN DATA JADWAL
// ------------------------------------------------------------------------------
$search         = trim($_GET['q'] ?? '');
$filterPosyandu = (int)($_GET['posyandu'] ?? 0);
$filterStatus   = $_GET['status'] ?? 'semua';
$filterJenis    = $_GET['jenis'] ?? 'semua';

$sql = "
    SELECT 
        j.*,
        p.nama_posyandu,
        p.kelurahan AS kelurahan_posyandu,
        u_kader.nama_lengkap AS nama_kader_penanggung_jawab
    FROM jadwal_posyandu j
    JOIN posyandu p ON j.id_posyandu = p.id_posyandu
    LEFT JOIN kader k ON j.id_kader = k.id_kader
    LEFT JOIN users u_kader ON k.id_user = u_kader.id_user
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND j.judul LIKE :s1";
    $params['s1'] = "%$search%";
}

if ($filterPosyandu > 0) {
    $sql .= " AND j.id_posyandu = :pos";
    $params['pos'] = $filterPosyandu;
}

if ($filterStatus !== 'semua') {
    $sql .= " AND j.status = :st";
    $params['st'] = $filterStatus;
}

if ($filterJenis !== 'semua') {
    $sql .= " AND j.jenis_kegiatan = :jenis";
    $params['jenis'] = $filterJenis;
}

$sql .= " ORDER BY j.tanggal DESC, j.jam_mulai DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $jadwalList = $stmt->fetchAll();
} catch (PDOException $e) {
    $jadwalList = [];
}

// ------------------------------------------------------------------------------
// 4. STATISTIK RINGKASAN JADWAL
// ------------------------------------------------------------------------------
try {
    $statQuery = $pdo->query("
        SELECT 
            COUNT(*) AS total_jadwal,
            COUNT(CASE WHEN status = 'terjadwal' THEN 1 END) AS total_terjadwal,
            COUNT(CASE WHEN status = 'selesai' THEN 1 END) AS total_selesai,
            COALESCE(SUM(target_peserta), 0) AS total_target_lansia
        FROM jadwal_posyandu
    ");
    $stats = $statQuery->fetch();
} catch (Exception $e) {
    $stats = ['total_jadwal' => 0, 'total_terjadwal' => 0, 'total_selesai' => 0, 'total_target_lansia' => 0];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Kegiatan Posyandu — Admin Waluya</title>

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
                    <h2 class="topbar-page-title">Panel Admin</h2>
                </div>
                <div class="topbar-right">
                    <div class="topbar-date-pill">
                        <i data-lucide="calendar" style="width: 14px; height: 14px; color: var(--primary);"></i>
                        <span><?= date('d M Y') ?></span>
                    </div>
                </div>
            </header>

            <main class="dashboard-content">

                <!-- HEADER TITLE (Clean Dashboard Style, Tanpa Background Hijau) -->
                <div class="welcome-banner">
                    <h1 class="welcome-title">Manajemen Jadwal Kegiatan Posyandu Lansia</h1>
                    <p class="welcome-subtitle">
                        Pusat pemantauan dan pengelolaan seluruh agenda Posyandu Lansia (Pemeriksaan Rutin, Senam Lansia, Penyuluhan Kesehatan, dan Kunjungan Rumah) di seluruh wilayah binaan.
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

                <!-- STATISTIK JADWAL -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-icon icon-primary">
                            <i data-lucide="calendar" style="width: 22px; height: 22px;"></i>
                        </div>
                        <div class="kpi-details">
                            <span class="kpi-value"><?= number_format($stats['total_jadwal'] ?? 0) ?></span>
                            <span class="kpi-label">Total Agenda</span>
                            <span class="kpi-subtext">Semua posyandu</span>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-icon icon-warning">
                            <i data-lucide="clock" style="width: 22px; height: 22px;"></i>
                        </div>
                        <div class="kpi-details">
                            <span class="kpi-value"><?= number_format($stats['total_terjadwal'] ?? 0) ?></span>
                            <span class="kpi-label">Mendatang</span>
                            <span class="kpi-subtext">Menunggu pelaksanaan</span>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-icon icon-primary" style="background: #D1FAE5; color: #065F46;">
                            <i data-lucide="check-circle-2" style="width: 22px; height: 22px;"></i>
                        </div>
                        <div class="kpi-details">
                            <span class="kpi-value"><?= number_format($stats['total_selesai'] ?? 0) ?></span>
                            <span class="kpi-label">Telah Selesai</span>
                            <span class="kpi-subtext">Kegiatan terlaksana</span>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-icon icon-info">
                            <i data-lucide="users" style="width: 22px; height: 22px;"></i>
                        </div>
                        <div class="kpi-details">
                            <span class="kpi-value"><?= number_format($stats['total_target_lansia'] ?? 0) ?></span>
                            <span class="kpi-label">Target Lansia</span>
                            <span class="kpi-subtext">Total perkiraan peserta</span>
                        </div>
                    </div>
                </div>

                <!-- CARD DAFTAR JADWAL -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i data-lucide="calendar-days" style="width: 18px; height: 18px; color: var(--primary);"></i>
                            Daftar Agenda & Kegiatan Posyandu
                        </h3>
                        <button type="button" class="btn btn-primary btn-sm" onclick="bukaModalTambah()">
                            <i data-lucide="plus" style="width: 14px; height: 14px;"></i>
                            <span>Tambah Jadwal</span>
                        </button>
                    </div>

                    <!-- PENCARIAN & FILTER -->
                    <form method="GET" action="jadwal.php" class="search-box-wrapper">
                        <div class="search-input-group">
                            <i data-lucide="search"></i>
                            <input type="text" name="q" placeholder="Cari nama agenda kegiatan..." value="<?= htmlspecialchars($search) ?>">
                        </div>

                        <select name="posyandu" class="form-select" style="max-width: 180px;" onchange="this.form.submit()">
                            <option value="0">Semua Posyandu</option>
                            <?php foreach ($listPosyandu as $p): ?>
                                <option value="<?= $p['id_posyandu'] ?>" <?= $filterPosyandu === (int)$p['id_posyandu'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['nama_posyandu']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="jenis" class="form-select" style="max-width: 180px;" onchange="this.form.submit()">
                            <option value="semua" <?= $filterJenis === 'semua' ? 'selected' : '' ?>>Semua Jenis</option>
                            <option value="Pemeriksaan Rutin" <?= $filterJenis === 'Pemeriksaan Rutin' ? 'selected' : '' ?>>Pemeriksaan Rutin</option>
                            <option value="Senam Lansia" <?= $filterJenis === 'Senam Lansia' ? 'selected' : '' ?>>Senam Lansia</option>
                            <option value="Penyuluhan" <?= $filterJenis === 'Penyuluhan' ? 'selected' : '' ?>>Penyuluhan</option>
                            <option value="Kunjungan Rumah" <?= $filterJenis === 'Kunjungan Rumah' ? 'selected' : '' ?>>Kunjungan Rumah</option>
                            <option value="Lainnya" <?= $filterJenis === 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                        </select>

                        <select name="status" class="form-select" style="max-width: 160px;" onchange="this.form.submit()">
                            <option value="semua" <?= $filterStatus === 'semua' ? 'selected' : '' ?>>Semua Status</option>
                            <option value="terjadwal" <?= $filterStatus === 'terjadwal' ? 'selected' : '' ?>>Terjadwal</option>
                            <option value="selesai" <?= $filterStatus === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                            <option value="dibatalkan" <?= $filterStatus === 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                        </select>

                        <button type="submit" class="btn btn-primary btn-sm">
                            <i data-lucide="filter" style="width: 14px; height: 14px;"></i>
                            <span>Filter</span>
                        </button>

                        <?php if (!empty($search) || $filterPosyandu > 0 || $filterStatus !== 'semua' || $filterJenis !== 'semua'): ?>
                            <a href="jadwal.php" class="btn btn-secondary btn-sm" title="Reset Filter">
                                <i data-lucide="rotate-ccw" style="width: 14px; height: 14px;"></i>
                                <span>Reset</span>
                            </a>
                        <?php endif; ?>
                    </form>

                    <!-- TABEL DATA JADWAL -->
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 130px;">Waktu Kegiatan</th>
                                    <th>Agenda & Jenis Kegiatan</th>
                                    <th>Posyandu & Lokasi</th>
                                    <th style="text-align: center;">Target Peserta</th>
                                    <th>Penanggung Jawab</th>
                                    <th style="text-align: center;">Status</th>
                                    <th style="width: 130px; text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($jadwalList)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 36px; color: var(--text-muted);">
                                            <i data-lucide="calendar-x" style="width: 36px; height: 36px; margin-bottom: 8px; opacity: 0.5;"></i>
                                            <p style="font-weight: 600;">Tidak ditemukan jadwal kegiatan yang sesuai dengan filter.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($jadwalList as $j): ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight: 700; color: var(--text-main); font-size: 13px;">
                                                    <?= date('d M Y', strtotime($j['tanggal'])) ?>
                                                </div>
                                                <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
                                                    <i data-lucide="clock" style="width: 11px; height: 11px; display: inline;"></i>
                                                    <?= date('H:i', strtotime($j['jam_mulai'])) ?> <?= $j['jam_selesai'] ? '- ' . date('H:i', strtotime($j['jam_selesai'])) : '' ?> WIB
                                                </div>
                                            </td>

                                            <td>
                                                <div style="font-weight: 800; color: var(--text-main); font-size: 14px;">
                                                    <?= htmlspecialchars($j['judul']) ?>
                                                </div>
                                                <div style="margin-top: 4px;">
                                                    <?php
                                                    $jenisBadge = 'badge-role-kader';
                                                    if ($j['jenis_kegiatan'] === 'Senam Lansia') $jenisBadge = 'badge-role-keluarga';
                                                    if ($j['jenis_kegiatan'] === 'Penyuluhan') $jenisBadge = 'badge-role-admin';
                                                    ?>
                                                    <span class="badge <?= $jenisBadge ?>" style="font-size: 10.5px; padding: 2px 8px;">
                                                        <?= htmlspecialchars($j['jenis_kegiatan']) ?>
                                                    </span>
                                                </div>
                                                <?php if (!empty($j['deskripsi'])): ?>
                                                    <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px; max-width: 340px; line-height: 1.4;">
                                                        <?= htmlspecialchars($j['deskripsi']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <div style="font-weight: 700; font-size: 13px; color: var(--text-main);">
                                                    <?= htmlspecialchars($j['nama_posyandu']) ?>
                                                </div>
                                                <div style="font-size: 11.5px; color: var(--text-secondary); margin-top: 2px;">
                                                    <i data-lucide="map-pin" style="width: 11px; height: 11px; display: inline;"></i>
                                                    <?= htmlspecialchars($j['lokasi']) ?>
                                                </div>
                                            </td>

                                            <td style="text-align: center;">
                                                <?php if ($j['target_peserta']): ?>
                                                    <span class="badge badge-active" style="font-weight: 700; font-size: 12px; padding: 3px 8px;">
                                                        <?= $j['target_peserta'] ?> Lansia
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color: var(--text-light); font-size: 12px;">-</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <div style="font-weight: 600; font-size: 12.5px; color: var(--text-main);">
                                                    <?= htmlspecialchars($j['nama_kader_penanggung_jawab'] ?? 'Semua Kader Unit') ?>
                                                </div>
                                                <div style="font-size: 11px; color: var(--text-muted);">
                                                    Penanggung Jawab Kegiatan
                                                </div>
                                            </td>

                                            <td style="text-align: center;">
                                                <?php
                                                $stBadge = 'badge-pending';
                                                if ($j['status'] === 'selesai') $stBadge = 'badge-active';
                                                if ($j['status'] === 'dibatalkan') $stBadge = 'badge-danger';
                                                ?>
                                                <span class="badge <?= $stBadge ?>" style="font-weight: 700;">
                                                    <?= strtoupper($j['status']) ?>
                                                </span>
                                            </td>

                                            <td style="text-align: center;">
                                                <div class="table-actions" style="justify-content: center;">
                                                    <!-- Tombol Edit -->
                                                    <button type="button" class="btn btn-warning btn-sm" title="Edit Jadwal" onclick="bukaModalEdit(<?= htmlspecialchars(json_encode($j)) ?>)">
                                                        <i data-lucide="edit-3" style="width: 13px; height: 13px;"></i>
                                                    </button>

                                                    <!-- Tombol Ubah Status Cepat -->
                                                    <form method="POST" action="jadwal.php" style="display: inline;">
                                                        <input type="hidden" name="crud_action" value="update_status">
                                                        <input type="hidden" name="id_jadwal" value="<?= $j['id_jadwal'] ?>">
                                                        <input type="hidden" name="status_baru" value="<?= $j['status'] === 'terjadwal' ? 'selesai' : 'terjadwal' ?>">
                                                        <button type="submit" class="btn btn-secondary btn-sm" title="<?= $j['status'] === 'terjadwal' ? 'Tandai Kegiatan Selesai' : 'Kembalikan ke Terjadwal' ?>">
                                                            <i data-lucide="<?= $j['status'] === 'terjadwal' ? 'check-circle' : 'rotate-ccw' ?>" style="width: 13px; height: 13px; color: <?= $j['status'] === 'terjadwal' ? '#014C38' : '#64748B' ?>;"></i>
                                                        </button>
                                                    </form>

                                                    <!-- Tombol Hapus -->
                                                    <button type="button" class="btn btn-outline-danger btn-sm" title="Hapus Jadwal" onclick="bukaModalHapus(<?= $j['id_jadwal'] ?>, '<?= htmlspecialchars(addslashes($j['judul'])) ?>')">
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
    <!-- MODAL TAMBAH JADWAL -->
    <!-- ============================================================================== -->
    <div class="modal-overlay" id="modalTambah">
        <div class="modal-card">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="calendar-plus" style="width: 20px; height: 20px; color: var(--primary);"></i>
                    <h3 class="modal-title">Tambah Jadwal Posyandu Baru</h3>
                </div>
                <button type="button" class="modal-close-btn" onclick="tutupModal('modalTambah')">
                    <i data-lucide="x" style="width: 18px; height: 18px;"></i>
                </button>
            </div>

            <form method="POST" action="jadwal.php">
                <input type="hidden" name="crud_action" value="tambah">

                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Unit Posyandu *</label>
                            <select name="id_posyandu" class="form-select" required>
                                <option value="">-- Pilih Posyandu --</option>
                                <?php foreach ($listPosyandu as $p): ?>
                                    <option value="<?= $p['id_posyandu'] ?>"><?= htmlspecialchars($p['nama_posyandu']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Kader Penanggung Jawab</label>
                            <select name="id_kader" class="form-select">
                                <option value="">-- Semua Kader Unit / Opsional --</option>
                                <?php foreach ($listKader as $k): ?>
                                    <option value="<?= $k['id_kader'] ?>"><?= htmlspecialchars($k['nama_lengkap']) ?> (<?= $k['nama_posyandu'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label class="form-label">Nama / Judul Kegiatan *</label>
                            <input type="text" name="judul" class="form-input" placeholder="Contoh: Posyandu Lansia Rutin Bulan Ini" required>
                        </div>

                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Jenis Kegiatan *</label>
                            <select name="jenis_kegiatan" class="form-select" required>
                                <option value="Pemeriksaan Rutin">Pemeriksaan Rutin</option>
                                <option value="Senam Lansia">Senam Lansia</option>
                                <option value="Penyuluhan">Penyuluhan</option>
                                <option value="Kunjungan Rumah">Kunjungan Rumah</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tanggal Pelaksanaan *</label>
                            <input type="date" name="tanggal" class="form-input" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Jam Mulai *</label>
                            <input type="time" name="jam_mulai" class="form-input" required value="08:00">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Jam Selesai</label>
                            <input type="time" name="jam_selesai" class="form-input" value="11:00">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label class="form-label">Lokasi Kegiatan *</label>
                            <input type="text" name="lokasi" class="form-input" placeholder="Contoh: Balai Pertemuan RW 05" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Target Lansia (Orang)</label>
                            <input type="number" name="target_peserta" class="form-input" placeholder="Contoh: 40">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Deskripsi / Catatan Tambahan</label>
                        <textarea name="deskripsi" class="form-textarea" rows="2" placeholder="Tuliskan perlengkapan yang perlu dibawa lansia atau agenda spesifik..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status Awal Agenda *</label>
                        <select name="status" class="form-select" required>
                            <option value="terjadwal">Terjadwal (Mendatang)</option>
                            <option value="selesai">Selesai</option>
                            <option value="dibatalkan">Dibatalkan</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="tutupModal('modalTambah')">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                        <span>Simpan Jadwal</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================================== -->
    <!-- MODAL EDIT JADWAL -->
    <!-- ============================================================================== -->
    <div class="modal-overlay" id="modalEdit">
        <div class="modal-card">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="edit-3" style="width: 20px; height: 20px; color: #D97706;"></i>
                    <h3 class="modal-title">Edit Jadwal Kegiatan</h3>
                </div>
                <button type="button" class="modal-close-btn" onclick="tutupModal('modalEdit')">
                    <i data-lucide="x" style="width: 18px; height: 18px;"></i>
                </button>
            </div>

            <form method="POST" action="jadwal.php">
                <input type="hidden" name="crud_action" value="edit">
                <input type="hidden" name="id_jadwal" id="edit_id_jadwal">

                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Unit Posyandu *</label>
                            <select name="id_posyandu" id="edit_id_posyandu" class="form-select" required>
                                <?php foreach ($listPosyandu as $p): ?>
                                    <option value="<?= $p['id_posyandu'] ?>"><?= htmlspecialchars($p['nama_posyandu']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Kader Penanggung Jawab</label>
                            <select name="id_kader" id="edit_id_kader" class="form-select">
                                <option value="">-- Semua Kader Unit / Opsional --</option>
                                <?php foreach ($listKader as $k): ?>
                                    <option value="<?= $k['id_kader'] ?>"><?= htmlspecialchars($k['nama_lengkap']) ?> (<?= $k['nama_posyandu'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label class="form-label">Nama / Judul Kegiatan *</label>
                            <input type="text" name="judul" id="edit_judul" class="form-input" required>
                        </div>

                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Jenis Kegiatan *</label>
                            <select name="jenis_kegiatan" id="edit_jenis_kegiatan" class="form-select" required>
                                <option value="Pemeriksaan Rutin">Pemeriksaan Rutin</option>
                                <option value="Senam Lansia">Senam Lansia</option>
                                <option value="Penyuluhan">Penyuluhan</option>
                                <option value="Kunjungan Rumah">Kunjungan Rumah</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tanggal Pelaksanaan *</label>
                            <input type="date" name="tanggal" id="edit_tanggal" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Jam Mulai *</label>
                            <input type="time" name="jam_mulai" id="edit_jam_mulai" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Jam Selesai</label>
                            <input type="time" name="jam_selesai" id="edit_jam_selesai" class="form-input">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label class="form-label">Lokasi Kegiatan *</label>
                            <input type="text" name="lokasi" id="edit_lokasi" class="form-input" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Target Lansia (Orang)</label>
                            <input type="number" name="target_peserta" id="edit_target_peserta" class="form-input">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Deskripsi / Catatan Tambahan</label>
                        <textarea name="deskripsi" id="edit_deskripsi" class="form-textarea" rows="2"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status Agenda *</label>
                        <select name="status" id="edit_status" class="form-select" required>
                            <option value="terjadwal">Terjadwal (Mendatang)</option>
                            <option value="selesai">Selesai</option>
                            <option value="dibatalkan">Dibatalkan</option>
                        </select>
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
    <!-- MODAL KONFIRMASI HAPUS JADWAL -->
    <!-- ============================================================================== -->
    <div class="modal-overlay" id="modalHapus">
        <div class="modal-card" style="max-width: 440px;">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="alert-triangle" style="width: 20px; height: 20px; color: #DC2626;"></i>
                    <h3 class="modal-title">Hapus Jadwal Kegiatan</h3>
                </div>
                <button type="button" class="modal-close-btn" onclick="tutupModal('modalHapus')">
                    <i data-lucide="x" style="width: 18px; height: 18px;"></i>
                </button>
            </div>
            <form method="POST" action="jadwal.php">
                <input type="hidden" name="crud_action" value="hapus">
                <input type="hidden" name="id_jadwal" id="hapus_id_jadwal">

                <div class="modal-body" style="text-align: center; padding: 24px;">
                    <div style="width: 48px; height: 48px; border-radius: 50%; background: #FEE2E2; color: #DC2626; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                        <i data-lucide="calendar-x" style="width: 24px; height: 24px;"></i>
                    </div>
                    <h4 style="font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 8px;">Konfirmasi Penghapusan</h4>
                    <p style="font-size: 13.5px; color: var(--text-muted); line-height: 1.5;">
                        Apakah Anda yakin ingin menghapus agenda kegiatan <strong id="hapus_judul" style="color: var(--text-main);"></strong>? Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>

                <div class="modal-footer" style="justify-content: center;">
                    <button type="button" class="btn btn-secondary" onclick="tutupModal('modalHapus')">Batal</button>
                    <button type="submit" class="btn btn-logout">
                        <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                        <span>Ya, Hapus Jadwal</span>
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

        function bukaModalTambah() {
            document.getElementById('modalTambah').classList.add('active');
        }

        function bukaModalEdit(data) {
            document.getElementById('edit_id_jadwal').value = data.id_jadwal;
            document.getElementById('edit_id_posyandu').value = data.id_posyandu;
            document.getElementById('edit_id_kader').value = data.id_kader || '';
            document.getElementById('edit_judul').value = data.judul;
            document.getElementById('edit_jenis_kegiatan').value = data.jenis_kegiatan;
            document.getElementById('edit_tanggal').value = data.tanggal;
            document.getElementById('edit_jam_mulai').value = data.jam_mulai;
            document.getElementById('edit_jam_selesai').value = data.jam_selesai || '';
            document.getElementById('edit_lokasi').value = data.lokasi;
            document.getElementById('edit_deskripsi').value = data.deskripsi || '';
            document.getElementById('edit_target_peserta').value = data.target_peserta || '';
            document.getElementById('edit_status').value = data.status;

            document.getElementById('modalEdit').classList.add('active');
        }

        function bukaModalHapus(id, judul) {
            document.getElementById('hapus_id_jadwal').value = id;
            document.getElementById('hapus_judul').textContent = judul;
            document.getElementById('modalHapus').classList.add('active');
        }
    </script>
</body>

</html>
