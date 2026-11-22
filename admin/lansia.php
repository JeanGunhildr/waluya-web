<?php
// ==============================================================================
// WALUYA - CRUD DATA WARGA LANSIA (ADMINISTRATOR)
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
$alert_msg = '';
$alert_type = '';

// 1. Pemrosesan Aksi CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $crud_action = $_POST['crud_action'] ?? '';

    // TAMBAH LANSIA BARU
    if ($crud_action === 'tambah') {
        $nik            = trim($_POST['nik'] ?? '');
        $no_kk          = trim($_POST['no_kk'] ?? '');
        $nama_lengkap   = trim($_POST['nama_lengkap'] ?? '');
        $tempat_lahir   = trim($_POST['tempat_lahir'] ?? '');
        $tanggal_lahir  = $_POST['tanggal_lahir'] ?? '';
        $jenis_kelamin  = $_POST['jenis_kelamin'] ?? 'L';
        $golongan_darah = $_POST['golongan_darah'] ?? 'Tidak Tahu';
        $agama          = $_POST['agama'] ?? 'Islam';
        $alamat         = trim($_POST['alamat'] ?? '');
        $rt             = trim($_POST['rt'] ?? '01');
        $rw             = trim($_POST['rw'] ?? '01');
        $no_hp          = trim($_POST['no_hp'] ?? '');
        $id_posyandu    = (int)($_POST['id_posyandu'] ?? 1);
        $id_keluarga    = !empty($_POST['id_keluarga']) ? (int)$_POST['id_keluarga'] : null;
        $hubungan       = trim($_POST['hubungan_keluarga'] ?? 'Orang Tua');
        $penyakit       = trim($_POST['riwayat_penyakit'] ?? '');

        if (empty($nik) || empty($nama_lengkap) || empty($tanggal_lahir) || empty($alamat)) {
            $alert_type = 'danger';
            $alert_msg = "Harap lengkapi kolom bertanda bintang (*) yang wajib diisi.";
        } else {
            try {
                // Cek duplikasi NIK lansia
                $check = $pdo->prepare("SELECT id_lansia FROM lansia WHERE nik = :nik LIMIT 1");
                $check->execute(['nik' => $nik]);
                if ($check->fetch()) {
                    $alert_type = 'danger';
                    $alert_msg = "NIK Lansia $nik sudah terdaftar dalam sistem!";
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO lansia (id_posyandu, id_keluarga, nik, no_kk, nama_lengkap, tempat_lahir, tanggal_lahir, 
                                            jenis_kelamin, golongan_darah, agama, alamat, rt, rw, no_hp, 
                                            hubungan_keluarga, riwayat_penyakit, status_lansia, created_at)
                        VALUES (:pos, :kel, :nik, :kk, :nama, :tempat, :tgl, :jk, :goldar, :agama, :alamat, :rt, :rw, :hp, :hub, :penyakit, 'aktif', NOW())
                    ");
                    $stmt->execute([
                        'pos'      => $id_posyandu,
                        'kel'      => $id_keluarga,
                        'nik'      => $nik,
                        'kk'       => $no_kk,
                        'nama'     => $nama_lengkap,
                        'tempat'   => $tempat_lahir,
                        'tgl'      => $tanggal_lahir,
                        'jk'       => $jenis_kelamin,
                        'goldar'   => $golongan_darah,
                        'agama'    => $agama,
                        'alamat'   => $alamat,
                        'rt'       => $rt,
                        'rw'       => $rw,
                        'hp'       => $no_hp,
                        'hub'      => $hubungan,
                        'penyakit' => $penyakit
                    ]);

                    $alert_type = 'success';
                    $alert_msg = "Data lansia baru <strong>" . htmlspecialchars($nama_lengkap) . "</strong> berhasil ditambahkan!";
                }
            } catch (PDOException $e) {
                $alert_type = 'danger';
                $alert_msg = "Gagal menyimpan data lansia: " . $e->getMessage();
            }
        }
    }

    // UBAH / UPDATE LANSIA
    elseif ($crud_action === 'edit') {
        $id_lansia      = (int)($_POST['id_lansia'] ?? 0);
        $nama_lengkap   = trim($_POST['nama_lengkap'] ?? '');
        $tempat_lahir   = trim($_POST['tempat_lahir'] ?? '');
        $tanggal_lahir  = $_POST['tanggal_lahir'] ?? '';
        $jenis_kelamin  = $_POST['jenis_kelamin'] ?? 'L';
        $golongan_darah = $_POST['golongan_darah'] ?? 'Tidak Tahu';
        $alamat         = trim($_POST['alamat'] ?? '');
        $rt             = trim($_POST['rt'] ?? '01');
        $rw             = trim($_POST['rw'] ?? '01');
        $no_hp          = trim($_POST['no_hp'] ?? '');
        $id_posyandu    = (int)($_POST['id_posyandu'] ?? 1);
        $id_keluarga    = !empty($_POST['id_keluarga']) ? (int)$_POST['id_keluarga'] : null;
        $hubungan       = trim($_POST['hubungan_keluarga'] ?? '');
        $penyakit       = trim($_POST['riwayat_penyakit'] ?? '');
        $status_lansia  = $_POST['status_lansia'] ?? 'aktif';

        if ($id_lansia > 0 && !empty($nama_lengkap)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE lansia 
                    SET nama_lengkap = :nama, tempat_lahir = :tempat, tanggal_lahir = :tgl,
                        jenis_kelamin = :jk, golongan_darah = :goldar, alamat = :alamat,
                        rt = :rt, rw = :rw, no_hp = :hp, id_posyandu = :pos, id_keluarga = :kel,
                        hubungan_keluarga = :hub, riwayat_penyakit = :penyakit, status_lansia = :status
                    WHERE id_lansia = :id
                ");
                $stmt->execute([
                    'nama'     => $nama_lengkap,
                    'tempat'   => $tempat_lahir,
                    'tgl'      => $tanggal_lahir,
                    'jk'       => $jenis_kelamin,
                    'goldar'   => $golongan_darah,
                    'alamat'   => $alamat,
                    'rt'       => $rt,
                    'rw'       => $rw,
                    'hp'       => $no_hp,
                    'pos'      => $id_posyandu,
                    'kel'      => $id_keluarga,
                    'hub'      => $hubungan,
                    'penyakit' => $penyakit,
                    'status'   => $status_lansia,
                    'id'       => $id_lansia
                ]);

                $alert_type = 'success';
                $alert_msg = "Data lansia <strong>" . htmlspecialchars($nama_lengkap) . "</strong> berhasil diperbarui!";
            } catch (PDOException $e) {
                $alert_type = 'danger';
                $alert_msg = "Gagal memperbarui data lansia: " . $e->getMessage();
            }
        }
    }

    // HAPUS LANSIA
    elseif ($crud_action === 'hapus') {
        $id_lansia = (int)($_POST['id_lansia'] ?? 0);
        if ($id_lansia > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM lansia WHERE id_lansia = :id");
                $stmt->execute(['id' => $id_lansia]);

                $alert_type = 'success';
                $alert_msg = "Data lansia berhasil dihapus dari sistem.";
            } catch (PDOException $e) {
                $alert_type = 'danger';
                $alert_msg = "Gagal menghapus data lansia: " . $e->getMessage();
            }
        }
    }
}

// 2. Query Pencarian & Daftar Lansia
$search = trim($_GET['q'] ?? '');
try {
    if (!empty($search)) {
        $stmtList = $pdo->prepare("
            SELECT l.*, TIMESTAMPDIFF(YEAR, l.tanggal_lahir, CURDATE()) AS usia,
                   p.nama_posyandu, u_kel.nama_lengkap AS nama_wali, u_kel.no_hp AS kontak_wali
            FROM lansia l
            JOIN posyandu p ON l.id_posyandu = p.id_posyandu
            LEFT JOIN keluarga kel ON l.id_keluarga = kel.id_keluarga
            LEFT JOIN users u_kel ON kel.id_user = u_kel.id_user
            WHERE l.nama_lengkap LIKE :q OR l.nik LIKE :q OR l.alamat LIKE :q
            ORDER BY l.id_lansia DESC
        ");
        $stmtList->execute(['q' => "%$search%"]);
    } else {
        $stmtList = $pdo->query("
            SELECT l.*, TIMESTAMPDIFF(YEAR, l.tanggal_lahir, CURDATE()) AS usia,
                   p.nama_posyandu, u_kel.nama_lengkap AS nama_wali, u_kel.no_hp AS kontak_wali
            FROM lansia l
            JOIN posyandu p ON l.id_posyandu = p.id_posyandu
            LEFT JOIN keluarga kel ON l.id_keluarga = kel.id_keluarga
            LEFT JOIN users u_kel ON kel.id_user = u_kel.id_user
            ORDER BY l.id_lansia DESC
        ");
    }
    $lansiaList = $stmtList->fetchAll();
} catch (PDOException $e) {
    $lansiaList = [];
}

// 3. Query Master Posyandu & Keluarga untuk Dropdown
try {
    $posyanduList = $pdo->query("SELECT id_posyandu, nama_posyandu FROM posyandu WHERE status = 'aktif'")->fetchAll();
    $keluargaList = $pdo->query("
        SELECT k.id_keluarga, k.nik, u.nama_lengkap, u.no_hp 
        FROM keluarga k 
        JOIN users u ON k.id_user = u.id_user 
        WHERE u.status_akun = 'aktif'
        ORDER BY u.nama_lengkap ASC
    ")->fetchAll();
} catch (PDOException $e) {
    $posyanduList = [];
    $keluargaList = [];
}
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

        <div class="main-wrapper">
            <!-- Topbar Header -->
            <header class="dashboard-topbar">
                <div class="topbar-left">
                    <button type="button" class="topbar-mobile-toggle" onclick="toggleSidebar(true)" aria-label="Buka Menu">
                        <i data-lucide="menu" style="width: 20px; height: 20px;"></i>
                    </button>
                    <h2 class="topbar-page-title">Kelola Data Warga Lansia (CRUD)</h2>
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

            <!-- Main Content -->
            <main class="dashboard-content">

                <div class="welcome-banner">
                    <h1 class="welcome-title">Master Data Warga Lansia</h1>
                    <p class="welcome-subtitle">
                        Kelola informasi kependudukan warga lanjut usia binaan Posyandu: pendaftaran lansia baru, edit profil biodata, dan relasi penanggung jawab keluarga.
                    </p>
                </div>

                <!-- Alert Pesan Feedback -->
                <?php if (!empty($alert_msg)): ?>
                    <div class="alert-banner alert-banner-<?= $alert_type ?>">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i data-lucide="<?= $alert_type === 'success' ? 'check-circle' : 'alert-circle' ?>" style="width: 18px; height: 18px;"></i>
                            <span><?= $alert_msg ?></span>
                        </div>
                        <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; cursor:pointer; color:inherit;">
                            <i data-lucide="x" style="width: 16px; height: 16px;"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Bar Pencarian & Tombol Tambah Data -->
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                        <form method="GET" style="display: flex; gap: 10px; flex: 1; max-width: 480px;">
                            <div class="search-input-group">
                                <i data-lucide="search"></i>
                                <input type="text" name="q" placeholder="Cari berdasarkan Nama, NIK, atau Alamat..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <button type="submit" class="btn btn-secondary">Cari</button>
                            <?php if (!empty($search)): ?>
                                <a href="lansia.php" class="btn btn-secondary" title="Reset pencarian">Reset</a>
                            <?php endif; ?>
                        </form>

                        <button type="button" class="btn btn-primary" onclick="openTambahModal()">
                            <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
                            <span>Tambah Lansia Baru</span>
                        </button>
                    </div>
                </div>

                <!-- Tabel Data Lansia -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i data-lucide="users" style="width: 18px; height: 18px; color: var(--primary);"></i>
                            Daftar Warga Lansia Terdaftar (<?= count($lansiaList) ?> Lansia)
                        </h3>
                    </div>

                    <?php if (count($lansiaList) > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>NIK & Identitas</th>
                                        <th>Jenis Kelamin</th>
                                        <th>Usia / Tgl Lahir</th>
                                        <th>Unit Posyandu</th>
                                        <th>Keluarga Wali</th>
                                        <th>Alamat Domisili</th>
                                        <th style="text-align: right;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lansiaList as $l): ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight: 700; color: #1E293B; font-size: 14px;"><?= htmlspecialchars($l['nama_lengkap']) ?></div>
                                                <div style="font-size: 12px; font-family: monospace; color: #64748B;">NIK: <?= htmlspecialchars($l['nik']) ?></div>
                                                <?php if (!empty($l['riwayat_penyakit'])): ?>
                                                    <span style="font-size: 11px; color: #D97706; display: block; margin-top: 2px;">
                                                        <i data-lucide="alert-circle" style="width: 11px; height: 11px; display: inline-block; vertical-align: -1px;"></i>
                                                        <?= htmlspecialchars($l['riwayat_penyakit']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge" style="background:#F1F5F9; color:#334155;">
                                                    <?= $l['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?= $l['usia'] ?> Tahun</strong>
                                                <div style="font-size: 11.5px; color: #64748B;"><?= date('d M Y', strtotime($l['tanggal_lahir'])) ?></div>
                                            </td>
                                            <td>
                                                <span class="badge badge-role-kader"><?= htmlspecialchars($l['nama_posyandu']) ?></span>
                                            </td>
                                            <td>
                                                <?php if (!empty($l['nama_wali'])): ?>
                                                    <div style="font-weight: 600; color: #1E293B;"><?= htmlspecialchars($l['nama_wali']) ?></div>
                                                    <span style="font-size: 11px; color: #0284C7;"><?= htmlspecialchars($l['hubungan_keluarga'] ?? 'Wali') ?></span>
                                                <?php else: ?>
                                                    <span style="color: #94A3B8; font-size: 12.5px;">Tinggal Mandiri</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="font-size: 12.5px; color: #475569;">
                                                <?= htmlspecialchars($l['alamat']) ?> (RT <?= htmlspecialchars($l['rt']) ?> / RW <?= htmlspecialchars($l['rw']) ?>)
                                            </td>
                                            <td style="text-align: right;">
                                                <div class="table-actions" style="justify-content: flex-end;">
                                                    <!-- Tombol Edit Modal -->
                                                    <button type="button" class="btn btn-sm btn-secondary" onclick='openEditModal(<?= json_encode($l) ?>)' title="Ubah Data Lansia">
                                                        <i data-lucide="edit-3" style="width: 13px; height: 13px;"></i>
                                                        <span>Edit</span>
                                                    </button>

                                                    <!-- Form Hapus Lansia -->
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data lansia <?= htmlspecialchars(addslashes($l['nama_lengkap'])) ?>? Semua rekam medis terkait juga akan terhapus.')">
                                                        <input type="hidden" name="crud_action" value="hapus">
                                                        <input type="hidden" name="id_lansia" value="<?= $l['id_lansia'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Lansia">
                                                            <i data-lucide="trash-2" style="width: 13px; height: 13px;"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; color: #64748B; padding: 36px;">
                            <i data-lucide="user-x" style="width: 32px; height: 32px; margin-bottom: 8px; color: #CBD5E1;"></i>
                            <p>Tidak ditemukan data warga lansia.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </main>
        </div>
    </div>

    <!-- MODAL 1: FORM TAMBAH LANSIA BARU -->
    <div class="modal-overlay" id="tambahModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modal-title">Tambah Data Warga Lansia Baru</h3>
                <button type="button" class="modal-close-btn" onclick="closeTambahModal()"><i data-lucide="x"></i></button>
            </div>
            <form method="POST">
                <input type="hidden" name="crud_action" value="tambah">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">NIK Lansia (16 Digit) <span style="color:#EF4444;">*</span></label>
                            <input type="text" name="nik" class="form-input" maxlength="16" required placeholder="3273xxxxxxxxxxxx">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nomor Kartu Keluarga (KK)</label>
                            <input type="text" name="no_kk" class="form-input" maxlength="16" placeholder="Nomor KK (opsional)">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nama Lengkap Lansia <span style="color:#EF4444;">*</span></label>
                        <input type="text" name="nama_lengkap" class="form-input" required placeholder="Nama lengkap sesuai KTP">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" class="form-input" placeholder="Kota kelahiran">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tanggal Lahir <span style="color:#EF4444;">*</span></label>
                            <input type="date" name="tanggal_lahir" class="form-input" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Jenis Kelamin <span style="color:#EF4444;">*</span></label>
                            <select name="jenis_kelamin" class="form-select" required>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Golongan Darah</label>
                            <select name="golongan_darah" class="form-select">
                                <option value="Tidak Tahu">Tidak Tahu</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="AB">AB</option>
                                <option value="O">O</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Unit Posyandu <span style="color:#EF4444;">*</span></label>
                            <select name="id_posyandu" class="form-select" required>
                                <?php foreach ($posyanduList as $pos): ?>
                                    <option value="<?= $pos['id_posyandu'] ?>"><?= htmlspecialchars($pos['nama_posyandu']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Keluarga Penanggung Jawab</label>
                            <select name="id_keluarga" class="form-select">
                                <option value="">-- Tinggal Mandiri / Tanpa Wali --</option>
                                <?php foreach ($keluargaList as $kel): ?>
                                    <option value="<?= $kel['id_keluarga'] ?>"><?= htmlspecialchars($kel['nama_lengkap']) ?> (NIK: <?= $kel['nik'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Hubungan dengan Keluarga</label>
                            <input type="text" name="hubungan_keluarga" class="form-input" placeholder="Contoh: Ayah Kandung, Ibu Mertua">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nomor HP Lansia</label>
                            <input type="tel" name="no_hp" class="form-input" placeholder="08xxxxxxxxxx (jika ada)">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Alamat Domisili <span style="color:#EF4444;">*</span></label>
                        <textarea name="alamat" class="form-textarea" rows="2" required placeholder="Alamat jalan, nomor rumah"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">RT / RW</label>
                            <div style="display:flex; gap:8px;">
                                <input type="text" name="rt" class="form-input" value="03" placeholder="RT">
                                <input type="text" name="rw" class="form-input" value="05" placeholder="RW">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Riwayat Penyakit Bawaan</label>
                            <input type="text" name="riwayat_penyakit" class="form-input" placeholder="Contoh: Hipertensi, Asam Urat, Diabetes">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeTambahModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Data Lansia</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: FORM EDIT DATA LANSIA -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modal-title">Edit Data Warga Lansia</h3>
                <button type="button" class="modal-close-btn" onclick="closeEditModal()"><i data-lucide="x"></i></button>
            </div>
            <form method="POST">
                <input type="hidden" name="crud_action" value="edit">
                <input type="hidden" name="id_lansia" id="edit_id_lansia">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap Lansia <span style="color:#EF4444;">*</span></label>
                        <input type="text" name="nama_lengkap" id="edit_nama" class="form-input" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" id="edit_tempat" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tanggal Lahir <span style="color:#EF4444;">*</span></label>
                            <input type="date" name="tanggal_lahir" id="edit_tgl" class="form-input" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="jenis_kelamin" id="edit_jk" class="form-select">
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Golongan Darah</label>
                            <select name="golongan_darah" id="edit_goldar" class="form-select">
                                <option value="Tidak Tahu">Tidak Tahu</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="AB">AB</option>
                                <option value="O">O</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Unit Posyandu</label>
                            <select name="id_posyandu" id="edit_posyandu" class="form-select" required>
                                <?php foreach ($posyanduList as $pos): ?>
                                    <option value="<?= $pos['id_posyandu'] ?>"><?= htmlspecialchars($pos['nama_posyandu']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Keluarga Wali</label>
                            <select name="id_keluarga" id="edit_keluarga" class="form-select">
                                <option value="">-- Tinggal Mandiri / Tanpa Wali --</option>
                                <?php foreach ($keluargaList as $kel): ?>
                                    <option value="<?= $kel['id_keluarga'] ?>"><?= htmlspecialchars($kel['nama_lengkap']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Alamat Domisili <span style="color:#EF4444;">*</span></label>
                        <textarea name="alamat" id="edit_alamat" class="form-textarea" rows="2" required></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Riwayat Penyakit</label>
                            <input type="text" name="riwayat_penyakit" id="edit_penyakit" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status Keberadaan</label>
                            <select name="status_lansia" id="edit_status" class="form-select">
                                <option value="aktif">Aktif</option>
                                <option value="pindah">Pindah Wilayah</option>
                                <option value="meninggal">Meninggal Dunia</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function openTambahModal() {
            document.getElementById('tambahModal').classList.add('active');
        }
        function closeTambahModal() {
            document.getElementById('tambahModal').classList.remove('active');
        }

        function openEditModal(data) {
            document.getElementById('edit_id_lansia').value = data.id_lansia;
            document.getElementById('edit_nama').value = data.nama_lengkap;
            document.getElementById('edit_tempat').value = data.tempat_lahir || '';
            document.getElementById('edit_tgl').value = data.tanggal_lahir;
            document.getElementById('edit_jk').value = data.jenis_kelamin;
            document.getElementById('edit_goldar').value = data.golongan_darah || 'Tidak Tahu';
            document.getElementById('edit_posyandu').value = data.id_posyandu;
            document.getElementById('edit_keluarga').value = data.id_keluarga || '';
            document.getElementById('edit_alamat').value = data.alamat;
            document.getElementById('edit_penyakit').value = data.riwayat_penyakit || '';
            document.getElementById('edit_status').value = data.status_lansia || 'aktif';

            document.getElementById('editModal').classList.add('active');
            lucide.createIcons();
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }
    </script>
</body>

</html>
