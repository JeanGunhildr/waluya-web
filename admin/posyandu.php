<?php
// ==============================================================================
// WALUYA - MASTER DATA UNIT POSYANDU LANSIA (ADMINISTRATOR - CRUD)
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
$active_menu = 'posyandu';
$alert_msg = '';
$alert_type = '';

// ------------------------------------------------------------------------------
// 1. PEMROSESAN AKSI CRUD POSYANDU
// ------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $crud_action = $_POST['crud_action'] ?? '';

    // A. TAMBAH UNIT POSYANDU BARU
    if ($crud_action === 'tambah') {
        $nama_posyandu     = trim($_POST['nama_posyandu'] ?? '');
        $alamat            = trim($_POST['alamat'] ?? '');
        $rt                = trim($_POST['rt'] ?? '01');
        $rw                = trim($_POST['rw'] ?? '01');
        $kelurahan         = trim($_POST['kelurahan'] ?? '');
        $kecamatan         = trim($_POST['kecamatan'] ?? '');
        $kota              = trim($_POST['kota'] ?? '');
        $puskesmas_pembina = trim($_POST['puskesmas_pembina'] ?? '');
        $kontak_posyandu   = trim($_POST['kontak_posyandu'] ?? '');
        $status            = $_POST['status'] ?? 'aktif';

        if (empty($nama_posyandu) || empty($alamat) || empty($kelurahan) || empty($puskesmas_pembina)) {
            $alert_type = 'danger';
            $alert_msg = "Harap lengkapi semua data wajib (Nama Posyandu, Alamat, Kelurahan, dan Puskesmas Pembina).";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO posyandu (nama_posyandu, alamat, rt, rw, kelurahan, kecamatan, kota, puskesmas_pembina, kontak_posyandu, status, created_at)
                    VALUES (:nama, :alamat, :rt, :rw, :kel, :kec, :kota, :pkm, :kontak, :status, NOW())
                ");
                $stmt->execute([
                    'nama'   => $nama_posyandu,
                    'alamat' => $alamat,
                    'rt'     => $rt,
                    'rw'     => $rw,
                    'kel'    => $kelurahan,
                    'kec'    => $kecamatan,
                    'kota'   => $kota,
                    'pkm'    => $puskesmas_pembina,
                    'kontak' => $kontak_posyandu,
                    'status' => $status
                ]);

                $alert_type = 'success';
                $alert_msg = "Unit Posyandu <strong>" . htmlspecialchars($nama_posyandu) . "</strong> berhasil ditambahkan!";
            } catch (PDOException $e) {
                $alert_type = 'danger';
                $alert_msg = "Gagal menambahkan Posyandu: " . $e->getMessage();
            }
        }
    }

    // B. UBAH / UPDATE UNIT POSYANDU
    elseif ($crud_action === 'edit') {
        $id_posyandu       = (int)($_POST['id_posyandu'] ?? 0);
        $nama_posyandu     = trim($_POST['nama_posyandu'] ?? '');
        $alamat            = trim($_POST['alamat'] ?? '');
        $rt                = trim($_POST['rt'] ?? '01');
        $rw                = trim($_POST['rw'] ?? '01');
        $kelurahan         = trim($_POST['kelurahan'] ?? '');
        $kecamatan         = trim($_POST['kecamatan'] ?? '');
        $kota              = trim($_POST['kota'] ?? '');
        $puskesmas_pembina = trim($_POST['puskesmas_pembina'] ?? '');
        $kontak_posyandu   = trim($_POST['kontak_posyandu'] ?? '');
        $status            = $_POST['status'] ?? 'aktif';

        if ($id_posyandu <= 0 || empty($nama_posyandu) || empty($alamat) || empty($kelurahan) || empty($puskesmas_pembina)) {
            $alert_type = 'danger';
            $alert_msg = "Harap lengkapi semua kolom bertanda bintang (*).";
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE posyandu SET
                        nama_posyandu     = :nama,
                        alamat            = :alamat,
                        rt                = :rt,
                        rw                = :rw,
                        kelurahan         = :kel,
                        kecamatan         = :kec,
                        kota              = :kota,
                        puskesmas_pembina = :pkm,
                        kontak_posyandu   = :kontak,
                        status            = :status,
                        updated_at        = NOW()
                    WHERE id_posyandu     = :id
                ");
                $stmt->execute([
                    'nama'   => $nama_posyandu,
                    'alamat' => $alamat,
                    'rt'     => $rt,
                    'rw'     => $rw,
                    'kel'    => $kelurahan,
                    'kec'    => $kecamatan,
                    'kota'   => $kota,
                    'pkm'    => $puskesmas_pembina,
                    'kontak' => $kontak_posyandu,
                    'status' => $status,
                    'id'     => $id_posyandu
                ]);

                $alert_type = 'success';
                $alert_msg = "Data Posyandu <strong>" . htmlspecialchars($nama_posyandu) . "</strong> berhasil diperbarui!";
            } catch (PDOException $e) {
                $alert_type = 'danger';
                $alert_msg = "Gagal memperbarui Posyandu: " . $e->getMessage();
            }
        }
    }

    // C. TOGGLE STATUS AKTIF / NONAKTIF
    elseif ($crud_action === 'toggle_status') {
        $id_posyandu = (int)($_POST['id_posyandu'] ?? 0);
        $status_baru = $_POST['status_baru'] ?? 'aktif';

        if ($id_posyandu > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE posyandu SET status = :st, updated_at = NOW() WHERE id_posyandu = :id");
                $stmt->execute(['st' => $status_baru, 'id' => $id_posyandu]);

                $alert_type = 'success';
                $alert_msg = "Status operasional Posyandu berhasil diubah menjadi <strong>" . strtoupper($status_baru) . "</strong>.";
            } catch (PDOException $e) {
                $alert_type = 'danger';
                $alert_msg = "Gagal mengubah status: " . $e->getMessage();
            }
        }
    }

    // D. HAPUS UNIT POSYANDU
    elseif ($crud_action === 'hapus') {
        $id_posyandu = (int)($_POST['id_posyandu'] ?? 0);

        if ($id_posyandu > 0) {
            try {
                // Cek relasi lansia & kader
                $checkL = $pdo->prepare("SELECT COUNT(*) FROM lansia WHERE id_posyandu = :id");
                $checkL->execute(['id' => $id_posyandu]);
                $jmlLansia = (int)$checkL->fetchColumn();

                $checkK = $pdo->prepare("SELECT COUNT(*) FROM kader WHERE id_posyandu = :id");
                $checkK->execute(['id' => $id_posyandu]);
                $jmlKader = (int)$checkK->fetchColumn();

                if ($jmlLansia > 0 || $jmlKader > 0) {
                    // Jangan hapus paksa agar tidak melanggar relasi data master
                    $alert_type = 'warning';
                    $alert_msg = "Unit Posyandu tidak dapat dihapus permanen karena masih memiliki <strong>$jmlLansia warga lansia</strong> dan <strong>$jmlKader kader</strong> terdaftar. Anda dapat menonaktifkan statusnya.";
                } else {
                    $delStmt = $pdo->prepare("DELETE FROM posyandu WHERE id_posyandu = :id");
                    $delStmt->execute(['id' => $id_posyandu]);

                    $alert_type = 'success';
                    $alert_msg = "Unit Posyandu berhasil dihapus dari sistem.";
                }
            } catch (PDOException $e) {
                $alert_type = 'danger';
                $alert_msg = "Gagal menghapus Posyandu: " . $e->getMessage();
            }
        }
    }
}

// ------------------------------------------------------------------------------
// 2. QUERY & PENCARIAN UNIT POSYANDU
// ------------------------------------------------------------------------------
$search       = trim($_GET['q'] ?? '');
$filterStatus = $_GET['status'] ?? 'semua';

$sql = "
    SELECT 
        p.*,
        (SELECT COUNT(*) FROM lansia l WHERE l.id_posyandu = p.id_posyandu) AS total_lansia,
        (SELECT COUNT(*) FROM kader k WHERE k.id_posyandu = p.id_posyandu AND k.status_kader = 'aktif') AS total_kader
    FROM posyandu p
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (p.nama_posyandu LIKE :s1 OR p.kelurahan LIKE :s2 OR p.kecamatan LIKE :s3 OR p.puskesmas_pembina LIKE :s4)";
    $params['s1'] = "%$search%";
    $params['s2'] = "%$search%";
    $params['s3'] = "%$search%";
    $params['s4'] = "%$search%";
}

if ($filterStatus !== 'semua') {
    $sql .= " AND p.status = :st";
    $params['st'] = $filterStatus;
}

$sql .= " ORDER BY p.id_posyandu ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $posyanduList = $stmt->fetchAll();
} catch (PDOException $e) {
    $posyanduList = [];
}

// ------------------------------------------------------------------------------
// 3. STATISTIK RINGKASAN POSYANDU
// ------------------------------------------------------------------------------
try {
    $kpiQuery = $pdo->query("
        SELECT 
            COUNT(*) AS total_posyandu,
            COUNT(CASE WHEN status = 'aktif' THEN 1 END) AS total_aktif,
            COUNT(CASE WHEN status = 'nonaktif' THEN 1 END) AS total_nonaktif
        FROM posyandu
    ");
    $kpiData = $kpiQuery->fetch();

    $totalLansiaAll = (int)$pdo->query("SELECT COUNT(*) FROM lansia")->fetchColumn();
    $totalKaderAll  = (int)$pdo->query("SELECT COUNT(*) FROM kader WHERE status_kader = 'aktif'")->fetchColumn();
} catch (Exception $e) {
    $kpiData = ['total_posyandu' => 0, 'total_aktif' => 0, 'total_nonaktif' => 0];
    $totalLansiaAll = 0;
    $totalKaderAll = 0;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Unit Posyandu — Admin Waluya</title>

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
                    <h2 class="topbar-page-title">Unit Posyandu Lansia</h2>
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
                    <h1 class="welcome-title">Master Data Unit Posyandu Lansia</h1>
                    <p class="welcome-subtitle">
                        Manajemen titik lokasi layanan Posyandu Lansia binaan Puskesmas. Admin dapat mendaftarkan unit posyandu baru, memperbarui wilayah kerja balai RW, serta memonitor beban warga binaan.
                    </p>
                </div>

                <!-- ALERT NOTIFIKASI -->
                <?php if (!empty($alert_msg)): ?>
                    <div class="alert-banner alert-banner-<?= $alert_type ?>">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i data-lucide="<?= $alert_type === 'success' ? 'check-circle' : ($alert_type === 'warning' ? 'alert-triangle' : 'alert-circle') ?>" style="width: 20px; height: 20px;"></i>
                            <span><?= $alert_msg ?></span>
                        </div>
                        <button type="button" onclick="this.parentElement.style.display='none'" style="background: none; border: none; cursor: pointer; color: inherit;">
                            <i data-lucide="x" style="width: 16px; height: 16px;"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- STATISTIK UNIT POSYANDU -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="feature-icon-wrapper" style="background: #EFF6FF; color: #2563EB;">
                            <i data-lucide="building-2" style="width: 22px; height: 22px;"></i>
                        </div>
                        <div>
                            <div class="kpi-value"><?= number_format($kpiData['total_posyandu'] ?? 0) ?></div>
                            <div class="kpi-label">Total Posyandu</div>
                            <div class="kpi-subtext">Unit terdaftar di sistem</div>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="feature-icon-wrapper" style="background: #ECFDF5; color: #059669;">
                            <i data-lucide="check-circle-2" style="width: 22px; height: 22px;"></i>
                        </div>
                        <div>
                            <div class="kpi-value"><?= number_format($kpiData['total_aktif'] ?? 0) ?></div>
                            <div class="kpi-label">Posyandu Aktif</div>
                            <div class="kpi-subtext">Melayani pemeriksaan rutin</div>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="feature-icon-wrapper" style="background: #F0FDF4; color: #16A34A;">
                            <i data-lucide="user-plus" style="width: 22px; height: 22px;"></i>
                        </div>
                        <div>
                            <div class="kpi-value"><?= number_format($totalLansiaAll) ?></div>
                            <div class="kpi-label">Lansia Terbina</div>
                            <div class="kpi-subtext">Di seluruh posyandu</div>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="feature-icon-wrapper" style="background: #FAF5FF; color: #7C3AED;">
                            <i data-lucide="shield" style="width: 22px; height: 22px;"></i>
                        </div>
                        <div>
                            <div class="kpi-value"><?= number_format($totalKaderAll) ?></div>
                            <div class="kpi-label">Kader Aktif</div>
                            <div class="kpi-subtext">Petugas posyandu lapangan</div>
                        </div>
                    </div>
                </div>

                <!-- CARD DAFTAR POSYANDU -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i data-lucide="map-pin" style="width: 18px; height: 18px; color: var(--primary);"></i>
                            Daftar Titik Posyandu Lansia
                        </h3>
                        <button type="button" class="btn btn-primary btn-sm" onclick="bukaModalTambah()">
                            <i data-lucide="plus" style="width: 14px; height: 14px;"></i>
                            <span>Tambah Posyandu</span>
                        </button>
                    </div>

                    <!-- PENCARIAN & FILTER -->
                    <form method="GET" action="posyandu.php" class="search-box-wrapper">
                        <div class="search-input-group">
                            <i data-lucide="search"></i>
                            <input type="text" name="q" placeholder="Cari nama posyandu, kelurahan, kecamatan, puskesmas..." value="<?= htmlspecialchars($search) ?>">
                        </div>

                        <select name="status" class="form-select" style="max-width: 180px;" onchange="this.form.submit()">
                            <option value="semua" <?= $filterStatus === 'semua' ? 'selected' : '' ?>>Semua Status</option>
                            <option value="aktif" <?= $filterStatus === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="nonaktif" <?= $filterStatus === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                        </select>

                        <button type="submit" class="btn btn-primary btn-sm">
                            <i data-lucide="filter" style="width: 14px; height: 14px;"></i>
                            <span>Filter</span>
                        </button>

                        <?php if (!empty($search) || $filterStatus !== 'semua'): ?>
                            <a href="posyandu.php" class="btn btn-secondary btn-sm" title="Reset Filter">
                                <i data-lucide="rotate-ccw" style="width: 14px; height: 14px;"></i>
                                <span>Reset</span>
                            </a>
                        <?php endif; ?>
                    </form>

                    <!-- TABEL DATA POSYANDU -->
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">ID</th>
                                    <th>Unit Posyandu</th>
                                    <th>Alamat & Wilayah Kerja</th>
                                    <th>Puskesmas Pembina</th>
                                    <th style="text-align: center;">Lansia</th>
                                    <th style="text-align: center;">Kader</th>
                                    <th style="text-align: center;">Status</th>
                                    <th style="width: 140px; text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($posyanduList)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 36px; color: var(--text-muted);">
                                            <i data-lucide="building-2" style="width: 36px; height: 36px; margin-bottom: 8px; opacity: 0.5;"></i>
                                            <p style="font-weight: 600;">Tidak ada unit posyandu yang sesuai dengan pencarian.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($posyanduList as $pos): ?>
                                        <tr>
                                            <td style="font-weight: 700; color: var(--text-light); text-align: center;">
                                                #<?= $pos['id_posyandu'] ?>
                                            </td>

                                            <td>
                                                <div style="font-weight: 800; color: var(--text-main); font-size: 14px;">
                                                    <?= htmlspecialchars($pos['nama_posyandu']) ?>
                                                </div>
                                                <?php if (!empty($pos['kontak_posyandu'])): ?>
                                                    <div style="font-size: 11.5px; color: #0284C7; margin-top: 3px;">
                                                        <i data-lucide="phone" style="width: 11px; height: 11px; display: inline;"></i> <?= htmlspecialchars($pos['kontak_posyandu']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <div style="font-size: 13px; color: var(--text-main);">
                                                    <?= htmlspecialchars($pos['alamat']) ?>
                                                </div>
                                                <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
                                                    RT <?= htmlspecialchars($pos['rt']) ?> / RW <?= htmlspecialchars($pos['rw']) ?>, Kel. <?= htmlspecialchars($pos['kelurahan']) ?>
                                                </div>
                                                <div style="font-size: 11.5px; color: var(--text-secondary);">
                                                    Kec. <?= htmlspecialchars($pos['kecamatan']) ?>, <?= htmlspecialchars($pos['kota']) ?>
                                                </div>
                                            </td>

                                            <td>
                                                <div style="font-weight: 700; font-size: 13px; color: var(--text-main);">
                                                    <?= htmlspecialchars($pos['puskesmas_pembina']) ?>
                                                </div>
                                                <div style="font-size: 11px; color: var(--text-muted);">
                                                    Faskes Tingkat I Rujukan
                                                </div>
                                            </td>

                                            <td style="text-align: center;">
                                                <span class="badge badge-active" style="font-weight: 700; font-size: 12px; padding: 4px 10px;">
                                                    <?= $pos['total_lansia'] ?> Warga
                                                </span>
                                            </td>

                                            <td style="text-align: center;">
                                                <span class="badge badge-role-kader" style="font-weight: 700; font-size: 12px; padding: 4px 10px;">
                                                    <?= $pos['total_kader'] ?> Kader
                                                </span>
                                            </td>

                                            <td style="text-align: center;">
                                                <span class="badge <?= $pos['status'] === 'aktif' ? 'badge-active' : 'badge-danger' ?>" style="font-weight: 700;">
                                                    <?= strtoupper($pos['status']) ?>
                                                </span>
                                            </td>

                                            <td style="text-align: center;">
                                                <div class="table-actions" style="justify-content: center;">
                                                    <!-- Tombol Edit -->
                                                    <button type="button" class="btn btn-warning btn-sm" title="Edit Posyandu" onclick="bukaModalEdit(<?= htmlspecialchars(json_encode($pos)) ?>)">
                                                        <i data-lucide="edit-3" style="width: 13px; height: 13px;"></i>
                                                    </button>

                                                    <!-- Tombol Toggle Status -->
                                                    <form method="POST" action="posyandu.php" style="display: inline;">
                                                        <input type="hidden" name="crud_action" value="toggle_status">
                                                        <input type="hidden" name="id_posyandu" value="<?= $pos['id_posyandu'] ?>">
                                                        <input type="hidden" name="status_baru" value="<?= $pos['status'] === 'aktif' ? 'nonaktif' : 'aktif' ?>">
                                                        <button type="submit" class="btn btn-secondary btn-sm" title="<?= $pos['status'] === 'aktif' ? 'Nonaktifkan Posyandu' : 'Aktifkan Posyandu' ?>">
                                                            <i data-lucide="<?= $pos['status'] === 'aktif' ? 'power-off' : 'power' ?>" style="width: 13px; height: 13px; color: <?= $pos['status'] === 'aktif' ? '#DC2626' : '#10B981' ?>;"></i>
                                                        </button>
                                                    </form>

                                                    <!-- Tombol Hapus -->
                                                    <button type="button" class="btn btn-outline-danger btn-sm" title="Hapus Posyandu" onclick="bukaModalHapus(<?= $pos['id_posyandu'] ?>, '<?= htmlspecialchars(addslashes($pos['nama_posyandu'])) ?>', <?= $pos['total_lansia'] ?>, <?= $pos['total_kader'] ?>)">
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
    <!-- MODAL TAMBAH POSYANDU -->
    <!-- ============================================================================== -->
    <div class="modal-overlay" id="modalTambah">
        <div class="modal-card">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="building-2" style="width: 20px; height: 20px; color: var(--primary);"></i>
                    <h3 class="modal-title">Tambah Unit Posyandu Lansia</h3>
                </div>
                <button type="button" class="modal-close-btn" onclick="tutupModal('modalTambah')">
                    <i data-lucide="x" style="width: 18px; height: 18px;"></i>
                </button>
            </div>

            <form method="POST" action="posyandu.php">
                <input type="hidden" name="crud_action" value="tambah">

                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Nama Unit Posyandu *</label>
                        <input type="text" name="nama_posyandu" class="form-input" placeholder="Contoh: Posyandu Lansia Melati 01" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Alamat Lengkap / Balai Pertemuan *</label>
                        <input type="text" name="alamat" class="form-input" placeholder="Contoh: Balai Warga RW 03, Jl. Merpati No. 12" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">RT *</label>
                            <input type="text" name="rt" class="form-input" placeholder="01" maxlength="5" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">RW *</label>
                            <input type="text" name="rw" class="form-input" placeholder="03" maxlength="5" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Kelurahan *</label>
                            <input type="text" name="kelurahan" class="form-input" placeholder="Contoh: Sukagalih" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kecamatan *</label>
                            <input type="text" name="kecamatan" class="form-input" placeholder="Contoh: Sukajadi" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Kota / Kabupaten *</label>
                            <input type="text" name="kota" class="form-input" value="Kota Bandung" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Puskesmas Pembina *</label>
                            <input type="text" name="puskesmas_pembina" class="form-input" placeholder="Contoh: Puskesmas Sukajadi" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Kontak / WhatsApp Pengurus</label>
                            <input type="text" name="kontak_posyandu" class="form-input" placeholder="Contoh: 081234567890">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status Operasional *</label>
                            <select name="status" class="form-select" required>
                                <option value="aktif">Aktif (Beroperasi)</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="tutupModal('modalTambah')">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                        <span>Simpan Unit Posyandu</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================================== -->
    <!-- MODAL EDIT POSYANDU -->
    <!-- ============================================================================== -->
    <div class="modal-overlay" id="modalEdit">
        <div class="modal-card">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="edit-3" style="width: 20px; height: 20px; color: #D97706;"></i>
                    <h3 class="modal-title">Edit Data Posyandu</h3>
                </div>
                <button type="button" class="modal-close-btn" onclick="tutupModal('modalEdit')">
                    <i data-lucide="x" style="width: 18px; height: 18px;"></i>
                </button>
            </div>

            <form method="POST" action="posyandu.php">
                <input type="hidden" name="crud_action" value="edit">
                <input type="hidden" name="id_posyandu" id="edit_id_posyandu">

                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Nama Unit Posyandu *</label>
                        <input type="text" name="nama_posyandu" id="edit_nama_posyandu" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Alamat Lengkap / Balai Pertemuan *</label>
                        <input type="text" name="alamat" id="edit_alamat" class="form-input" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">RT *</label>
                            <input type="text" name="rt" id="edit_rt" class="form-input" maxlength="5" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">RW *</label>
                            <input type="text" name="rw" id="edit_rw" class="form-input" maxlength="5" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Kelurahan *</label>
                            <input type="text" name="kelurahan" id="edit_kelurahan" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kecamatan *</label>
                            <input type="text" name="kecamatan" id="edit_kecamatan" class="form-input" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Kota / Kabupaten *</label>
                            <input type="text" name="kota" id="edit_kota" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Puskesmas Pembina *</label>
                            <input type="text" name="puskesmas_pembina" id="edit_puskesmas_pembina" class="form-input" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Kontak / WhatsApp Pengurus</label>
                            <input type="text" name="kontak_posyandu" id="edit_kontak_posyandu" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status Operasional *</label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="aktif">Aktif (Beroperasi)</option>
                                <option value="nonaktif">Nonaktif</option>
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
    <!-- MODAL KONFIRMASI HAPUS POSYANDU -->
    <!-- ============================================================================== -->
    <div class="modal-overlay" id="modalHapus">
        <div class="modal-card" style="max-width: 440px;">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="alert-triangle" style="width: 20px; height: 20px; color: #DC2626;"></i>
                    <h3 class="modal-title">Hapus Unit Posyandu</h3>
                </div>
                <button type="button" class="modal-close-btn" onclick="tutupModal('modalHapus')">
                    <i data-lucide="x" style="width: 18px; height: 18px;"></i>
                </button>
            </div>
            <form method="POST" action="posyandu.php">
                <input type="hidden" name="crud_action" value="hapus">
                <input type="hidden" name="id_posyandu" id="hapus_id_posyandu">

                <div class="modal-body" style="text-align: center; padding: 24px;">
                    <div style="width: 48px; height: 48px; border-radius: 50%; background: #FEE2E2; color: #DC2626; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                        <i data-lucide="building-2" style="width: 24px; height: 24px;"></i>
                    </div>
                    <h4 style="font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 8px;">Hapus Data Posyandu</h4>
                    <p style="font-size: 13.5px; color: var(--text-muted); line-height: 1.5;">
                        Apakah Anda yakin ingin menghapus unit <strong id="hapus_nama_posyandu" style="color: var(--text-main);"></strong>?
                    </p>
                    <div id="hapus_warning_box" style="display: none; margin-top: 14px; padding: 10px 14px; background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 8px; font-size: 12px; color: #92400E; text-align: left;">
                        <i data-lucide="alert-circle" style="width: 14px; height: 14px; display: inline;"></i>
                        <strong>Catatan:</strong> Unit ini memiliki lansia/kader terdaftar. Sistem akan menolak jika terdapat relasi aktif untuk melindungi integritas data rekam medis.
                    </div>
                </div>

                <div class="modal-footer" style="justify-content: center;">
                    <button type="button" class="btn btn-secondary" onclick="tutupModal('modalHapus')">Batal</button>
                    <button type="submit" class="btn btn-logout">
                        <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                        <span>Ya, Hapus Posyandu</span>
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
            document.getElementById('edit_id_posyandu').value = data.id_posyandu;
            document.getElementById('edit_nama_posyandu').value = data.nama_posyandu;
            document.getElementById('edit_alamat').value = data.alamat;
            document.getElementById('edit_rt').value = data.rt;
            document.getElementById('edit_rw').value = data.rw;
            document.getElementById('edit_kelurahan').value = data.kelurahan;
            document.getElementById('edit_kecamatan').value = data.kecamatan;
            document.getElementById('edit_kota').value = data.kota;
            document.getElementById('edit_puskesmas_pembina').value = data.puskesmas_pembina;
            document.getElementById('edit_kontak_posyandu').value = data.kontak_posyandu || '';
            document.getElementById('edit_status').value = data.status;

            document.getElementById('modalEdit').classList.add('active');
        }

        function bukaModalHapus(id, nama, lansiaCount, kaderCount) {
            document.getElementById('hapus_id_posyandu').value = id;
            document.getElementById('hapus_nama_posyandu').textContent = nama;

            const warn = document.getElementById('hapus_warning_box');
            if (lansiaCount > 0 || kaderCount > 0) {
                warn.style.display = 'block';
            } else {
                warn.style.display = 'none';
            }

            document.getElementById('modalHapus').classList.add('active');
        }
    </script>
</body>

</html>
