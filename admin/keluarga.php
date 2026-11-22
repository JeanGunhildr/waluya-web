<?php
// ==============================================================================
// WALUYA - CRUD DATA ANGGOTA KELUARGA (ADMINISTRATOR)
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
$active_menu = 'keluarga';
$alert_msg = '';
$alert_type = '';

// 1. Pemrosesan Aksi Edit & Status Keluarga
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $crud_action = $_POST['crud_action'] ?? '';

    // EDIT PROFIL KELUARGA
    if ($crud_action === 'edit') {
        $id_user         = (int)($_POST['id_user'] ?? 0);
        $id_keluarga     = (int)($_POST['id_keluarga'] ?? 0);
        $nama_lengkap    = trim($_POST['nama_lengkap'] ?? '');
        $no_hp           = trim($_POST['no_hp'] ?? '');
        $alamat_domisili = trim($_POST['alamat_domisili'] ?? '');
        $rt              = trim($_POST['rt'] ?? '01');
        $rw              = trim($_POST['rw'] ?? '01');
        $pekerjaan       = trim($_POST['pekerjaan'] ?? '');
        $kontak_darurat  = trim($_POST['kontak_darurat'] ?? '');
        $status_akun     = $_POST['status_akun'] ?? 'aktif';

        if ($id_user > 0 && !empty($nama_lengkap)) {
            try {
                $pdo->beginTransaction();

                // Update data akun users
                $stmtU = $pdo->prepare("UPDATE users SET nama_lengkap = :nama, no_hp = :hp, status_akun = :status WHERE id_user = :uid");
                $stmtU->execute(['nama' => $nama_lengkap, 'hp' => $no_hp, 'status' => $status_akun, 'uid' => $id_user]);

                // Update detail keluarga
                $stmtK = $pdo->prepare("
                    UPDATE keluarga 
                    SET alamat_domisili = :alamat, rt = :rt, rw = :rw, pekerjaan = :pek, kontak_darurat = :kd
                    WHERE id_keluarga = :kid
                ");
                $stmtK->execute([
                    'alamat' => $alamat_domisili,
                    'rt'     => $rt,
                    'rw'     => $rw,
                    'pek'    => $pekerjaan,
                    'kd'     => $kontak_darurat,
                    'kid'    => $id_keluarga
                ]);

                $pdo->commit();
                $alert_type = 'success';
                $alert_msg = "Data keluarga <strong>" . htmlspecialchars($nama_lengkap) . "</strong> berhasil diperbarui!";
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $alert_type = 'danger';
                $alert_msg = "Gagal memperbarui data: " . $e->getMessage();
            }
        }
    }

    // RESET KATA SANDI KE DEFAULT
    elseif ($crud_action === 'reset_password') {
        $id_user = (int)($_POST['id_user'] ?? 0);
        if ($id_user > 0) {
            try {
                $hash = password_hash('password123', PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET password = :p WHERE id_user = :id");
                $stmt->execute(['p' => $hash, 'id' => $id_user]);

                $alert_type = 'success';
                $alert_msg = "Kata sandi pengguna berhasil direset menjadi: <code>password123</code>";
            } catch (PDOException $e) {
                $alert_type = 'danger';
                $alert_msg = "Gagal mereset kata sandi: " . $e->getMessage();
            }
        }
    }

    // HAPUS AKUN KELUARGA
    elseif ($crud_action === 'hapus') {
        $id_user = (int)($_POST['id_user'] ?? 0);
        if ($id_user > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id_user = :id AND role = 'keluarga'");
                $stmt->execute(['id' => $id_user]);

                $alert_type = 'success';
                $alert_msg = "Data akun keluarga berhasil dihapus.";
            } catch (PDOException $e) {
                $alert_type = 'danger';
                $alert_msg = "Gagal menghapus data: " . $e->getMessage();
            }
        }
    }
}

// 2. Query Pencarian & Daftar Keluarga
$search = trim($_GET['q'] ?? '');
try {
    if (!empty($search)) {
        $stmt = $pdo->prepare("
            SELECT u.id_user, u.nama_lengkap, u.email, u.no_hp, u.status_akun, u.created_at AS tgl_daftar,
                   k.id_keluarga, k.nik, k.alamat_domisili, k.rt, k.rw, k.pekerjaan, k.kontak_darurat,
                   (SELECT COUNT(*) FROM lansia l WHERE l.id_keluarga = k.id_keluarga) AS total_lansia
            FROM users u
            JOIN keluarga k ON u.id_user = k.id_user
            WHERE u.role = 'keluarga' AND (u.nama_lengkap LIKE :q OR k.nik LIKE :q OR u.email LIKE :q)
            ORDER BY u.id_user DESC
        ");
        $stmt->execute(['q' => "%$search%"]);
    } else {
        $stmt = $pdo->query("
            SELECT u.id_user, u.nama_lengkap, u.email, u.no_hp, u.status_akun, u.created_at AS tgl_daftar,
                   k.id_keluarga, k.nik, k.alamat_domisili, k.rt, k.rw, k.pekerjaan, k.kontak_darurat,
                   (SELECT COUNT(*) FROM lansia l WHERE l.id_keluarga = k.id_keluarga) AS total_lansia
            FROM users u
            JOIN keluarga k ON u.id_user = k.id_user
            WHERE u.role = 'keluarga'
            ORDER BY u.id_user DESC
        ");
    }
    $keluargaList = $stmt->fetchAll();
} catch (PDOException $e) {
    $keluargaList = [];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Data Keluarga — Admin Waluya</title>

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
                    <h2 class="topbar-page-title">Kelola Penanggung Jawab Keluarga</h2>
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
                    <h1 class="welcome-title">Manajemen Data Anggota Keluarga</h1>
                    <p class="welcome-subtitle">
                        Kelola data wali/penanggung jawab lansia terdaftar: perbarui informasi domisili, kontak darurat, reset kata sandi, serta kontrol status keaktifan akun keluarga.
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

                <!-- Pencarian Keluarga -->
                <div class="card">
                    <form method="GET" style="display: flex; gap: 10px; flex: 1; max-width: 480px;">
                        <div class="search-input-group">
                            <i data-lucide="search"></i>
                            <input type="text" name="q" placeholder="Cari berdasarkan Nama, NIK, atau Email..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <button type="submit" class="btn btn-secondary">Cari</button>
                        <?php if (!empty($search)): ?>
                            <a href="keluarga.php" class="btn btn-secondary">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Tabel Data Keluarga -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i data-lucide="users" style="width: 18px; height: 18px; color: var(--primary);"></i>
                            Daftar Anggota Keluarga Terdaftar (<?= count($keluargaList) ?> Akun)
                        </h3>
                    </div>

                    <?php if (count($keluargaList) > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Nama & Kontak</th>
                                        <th>NIK KTP</th>
                                        <th>Domisili & RT/RW</th>
                                        <th>Kontak Darurat</th>
                                        <th>Lansia Diasuh</th>
                                        <th>Status Akun</th>
                                        <th style="text-align: right;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($keluargaList as $k): ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight: 700; color: #1E293B; font-size: 14px;"><?= htmlspecialchars($k['nama_lengkap']) ?></div>
                                                <div style="font-size: 12.5px; color: #475569;"><?= htmlspecialchars($k['email']) ?></div>
                                                <div style="font-size: 12px; color: #64748B;">HP: <?= htmlspecialchars($k['no_hp']) ?></div>
                                                <?php if (!empty($k['pekerjaan'])): ?>
                                                    <span style="font-size: 11px; color: #0284C7;">Pekerjaan: <?= htmlspecialchars($k['pekerjaan']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span style="font-family: monospace; font-size: 13px; font-weight: 700; color: #1E293B;">
                                                    <?= htmlspecialchars($k['nik']) ?>
                                                </span>
                                            </td>
                                            <td style="font-size: 12.5px; color: #475569;">
                                                <?= htmlspecialchars($k['alamat_domisili']) ?>
                                                <div style="font-size: 11px; color: #64748B;">RT <?= htmlspecialchars($k['rt']) ?> / RW <?= htmlspecialchars($k['rw']) ?></div>
                                            </td>
                                            <td style="font-size: 12.5px; color: #1E293B;">
                                                <?= htmlspecialchars($k['kontak_darurat'] ?? '-') ?>
                                            </td>
                                            <td>
                                                <span class="badge" style="background:#F0FDF4; color:#047857; font-weight:700;">
                                                    <?= $k['total_lansia'] ?> Lansia
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                    $statusBadge = 'badge-active';
                                                    if ($k['status_akun'] === 'pending') $statusBadge = 'badge-pending';
                                                    if ($k['status_akun'] === 'ditolak' || $k['status_akun'] === 'nonaktif') $statusBadge = 'badge-danger';
                                                ?>
                                                <span class="badge <?= $statusBadge ?>">
                                                    <?= ucfirst($k['status_akun']) ?>
                                                </span>
                                            </td>
                                            <td style="text-align: right;">
                                                <div class="table-actions" style="justify-content: flex-end;">
                                                    <button type="button" class="btn btn-sm btn-secondary" onclick='openEditKeluargaModal(<?= json_encode($k) ?>)' title="Edit Data Keluarga">
                                                        <i data-lucide="edit-3" style="width: 13px; height: 13px;"></i>
                                                        <span>Edit</span>
                                                    </button>

                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Reset kata sandi pengguna ini menjadi password123?')">
                                                        <input type="hidden" name="crud_action" value="reset_password">
                                                        <input type="hidden" name="id_user" value="<?= $k['id_user'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-warning" title="Reset Kata Sandi">
                                                            <i data-lucide="key" style="width: 13px; height: 13px;"></i>
                                                        </button>
                                                    </form>

                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun keluarga ini?')">
                                                        <input type="hidden" name="crud_action" value="hapus">
                                                        <input type="hidden" name="id_user" value="<?= $k['id_user'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Akun">
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
                            <p>Tidak ditemukan data anggota keluarga.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </main>
        </div>
    </div>

    <!-- MODAL EDIT KELUARGA -->
    <div class="modal-overlay" id="editKeluargaModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modal-title">Edit Data Anggota Keluarga</h3>
                <button type="button" class="modal-close-btn" onclick="closeEditKeluargaModal()"><i data-lucide="x"></i></button>
            </div>
            <form method="POST">
                <input type="hidden" name="crud_action" value="edit">
                <input type="hidden" name="id_user" id="edit_f_uid">
                <input type="hidden" name="id_keluarga" id="edit_f_kid">

                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap <span style="color:#EF4444;">*</span></label>
                        <input type="text" name="nama_lengkap" id="edit_f_nama" class="form-input" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nomor HP / WhatsApp <span style="color:#EF4444;">*</span></label>
                            <input type="tel" name="no_hp" id="edit_f_hp" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kontak Darurat</label>
                            <input type="tel" name="kontak_darurat" id="edit_f_kd" class="form-input">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Alamat Domisili <span style="color:#EF4444;">*</span></label>
                        <textarea name="alamat_domisili" id="edit_f_alamat" class="form-textarea" rows="2" required></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">RT / RW</label>
                            <div style="display:flex; gap:8px;">
                                <input type="text" name="rt" id="edit_f_rt" class="form-input" placeholder="RT">
                                <input type="text" name="rw" id="edit_f_rw" class="form-input" placeholder="RW">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Pekerjaan</label>
                            <input type="text" name="pekerjaan" id="edit_f_pekerjaan" class="form-input">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status Akun</label>
                        <select name="status_akun" id="edit_f_status" class="form-select">
                            <option value="aktif">Aktif</option>
                            <option value="pending">Pending</option>
                            <option value="nonaktif">Nonaktif</option>
                            <option value="ditolak">Ditolak</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditKeluargaModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function openEditKeluargaModal(data) {
            document.getElementById('edit_f_uid').value = data.id_user;
            document.getElementById('edit_f_kid').value = data.id_keluarga;
            document.getElementById('edit_f_nama').value = data.nama_lengkap;
            document.getElementById('edit_f_hp').value = data.no_hp;
            document.getElementById('edit_f_kd').value = data.kontak_darurat || '';
            document.getElementById('edit_f_alamat').value = data.alamat_domisili;
            document.getElementById('edit_f_rt').value = data.rt || '01';
            document.getElementById('edit_f_rw').value = data.rw || '01';
            document.getElementById('edit_f_pekerjaan').value = data.pekerjaan || '';
            document.getElementById('edit_f_status').value = data.status_akun;

            document.getElementById('editKeluargaModal').classList.add('active');
            lucide.createIcons();
        }
        function closeEditKeluargaModal() {
            document.getElementById('editKeluargaModal').classList.remove('active');
        }
    </script>
</body>

</html>
