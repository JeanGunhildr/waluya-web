<?php
// ==============================================================================
// WALUYA - CRUD DATA KADER POSYANDU (ADMINISTRATOR)
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
$active_menu = 'kader';
$alert_msg = '';
$alert_type = '';

// 1. Pemrosesan Aksi CRUD Kader
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $crud_action = $_POST['crud_action'] ?? '';

    // TAMBAH KADER BARU (Users + Kader)
    if ($crud_action === 'tambah') {
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $email        = trim($_POST['email'] ?? '');
        $no_hp        = trim($_POST['no_hp'] ?? '');
        $password     = $_POST['password'] ?? 'password123';
        $nik          = trim($_POST['nik'] ?? '');
        $jabatan      = $_POST['jabatan'] ?? 'Anggota';
        $id_posyandu  = (int)($_POST['id_posyandu'] ?? 1);
        $tgl_gabung   = $_POST['tanggal_bergabung'] ?? date('Y-m-d');

        if (empty($nama_lengkap) || empty($email) || empty($no_hp) || empty($nik)) {
            $alert_type = 'danger';
            $alert_msg = "Harap lengkapi semua kolom wajib.";
        } else {
            try {
                // Cek duplikasi email atau no HP
                $check = $pdo->prepare("SELECT id_user FROM users WHERE email = :email OR no_hp = :hp LIMIT 1");
                $check->execute(['email' => $email, 'hp' => $no_hp]);
                if ($check->fetch()) {
                    $alert_type = 'danger';
                    $alert_msg = "Alamat email atau nomor HP tersebut sudah terdaftar di sistem!";
                } else {
                    $pdo->beginTransaction();

                    // 1. Buat akun users (role = 'kader', status = 'aktif')
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmtUser = $pdo->prepare("
                        INSERT INTO users (nama_lengkap, email, no_hp, password, role, status_akun, id_admin_acc, tanggal_acc, created_at)
                        VALUES (:nama, :email, :hp, :pass, 'kader', 'aktif', :admin_id, NOW(), NOW())
                    ");
                    $stmtUser->execute([
                        'nama'     => $nama_lengkap,
                        'email'    => $email,
                        'hp'       => $no_hp,
                        'pass'     => $hash,
                        'admin_id' => $_SESSION['user_id']
                    ]);
                    $newUserId = $pdo->lastInsertId();

                    // 2. Buat profil kader
                    $stmtKader = $pdo->prepare("
                        INSERT INTO kader (id_user, id_posyandu, nik, jabatan, tanggal_bergabung, status_kader, created_at)
                        VALUES (:uid, :pos, :nik, :jabatan, :tgl, 'aktif', NOW())
                    ");
                    $stmtKader->execute([
                        'uid'     => $newUserId,
                        'pos'     => $id_posyandu,
                        'nik'     => $nik,
                        'jabatan' => $jabatan,
                        'tgl'     => $tgl_gabung
                    ]);

                    $pdo->commit();
                    $alert_type = 'success';
                    $alert_msg = "Akun dan data kader baru <strong>" . htmlspecialchars($nama_lengkap) . "</strong> berhasil ditambahkan! Kata sandi default: <code>$password</code>";
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $alert_type = 'danger';
                $alert_msg = "Gagal menambahkan kader: " . $e->getMessage();
            }
        }
    }

    // UBAH / UPDATE KADER
    elseif ($crud_action === 'edit') {
        $id_kader     = (int)($_POST['id_kader'] ?? 0);
        $id_user      = (int)($_POST['id_user'] ?? 0);
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $no_hp        = trim($_POST['no_hp'] ?? '');
        $jabatan      = $_POST['jabatan'] ?? 'Anggota';
        $id_posyandu  = (int)($_POST['id_posyandu'] ?? 1);
        $status_kader = $_POST['status_kader'] ?? 'aktif';

        if ($id_kader > 0 && !empty($nama_lengkap)) {
            try {
                $pdo->beginTransaction();

                // Update users
                $stmtU = $pdo->prepare("UPDATE users SET nama_lengkap = :nama, no_hp = :hp WHERE id_user = :uid");
                $stmtU->execute(['nama' => $nama_lengkap, 'hp' => $no_hp, 'uid' => $id_user]);

                // Update kader
                $stmtK = $pdo->prepare("UPDATE kader SET jabatan = :jab, id_posyandu = :pos, status_kader = :status WHERE id_kader = :kid");
                $stmtK->execute(['jab' => $jabatan, 'pos' => $id_posyandu, 'status' => $status_kader, 'kid' => $id_kader]);

                $pdo->commit();
                $alert_type = 'success';
                $alert_msg = "Data kader <strong>" . htmlspecialchars($nama_lengkap) . "</strong> berhasil diperbarui!";
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $alert_type = 'danger';
                $alert_msg = "Gagal memperbarui data kader: " . $e->getMessage();
            }
        }
    }

    // HAPUS KADER
    elseif ($crud_action === 'hapus') {
        $id_user = (int)($_POST['id_user'] ?? 0);
        if ($id_user > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id_user = :id AND role = 'kader'");
                $stmt->execute(['id' => $id_user]);

                $alert_type = 'success';
                $alert_msg = "Akun kader dan data profilnya berhasil dihapus.";
            } catch (PDOException $e) {
                $alert_type = 'danger';
                $alert_msg = "Gagal menghapus kader: " . $e->getMessage();
            }
        }
    }
}

// 2. Query Daftar Kader
$search = trim($_GET['q'] ?? '');
try {
    if (!empty($search)) {
        $stmtKader = $pdo->prepare("
            SELECT k.id_kader, k.nik, k.jabatan, k.tanggal_bergabung, k.status_kader,
                   u.id_user, u.nama_lengkap, u.email, u.no_hp, u.terakhir_login,
                   p.nama_posyandu, p.id_posyandu
            FROM kader k
            JOIN users u ON k.id_user = u.id_user
            JOIN posyandu p ON k.id_posyandu = p.id_posyandu
            WHERE u.nama_lengkap LIKE :q
            ORDER BY k.id_kader DESC
        ");
        $stmtKader->execute(['q' => "%$search%"]);
    } else {
        $stmtKader = $pdo->query("
            SELECT k.id_kader, k.nik, k.jabatan, k.tanggal_bergabung, k.status_kader,
                   u.id_user, u.nama_lengkap, u.email, u.no_hp, u.terakhir_login,
                   p.nama_posyandu, p.id_posyandu
            FROM kader k
            JOIN users u ON k.id_user = u.id_user
            JOIN posyandu p ON k.id_posyandu = p.id_posyandu
            ORDER BY k.id_kader DESC
        ");
    }
    $kaderList = $stmtKader->fetchAll();
} catch (PDOException $e) {
    $kaderList = [];
}

// 3. Master Posyandu untuk Dropdown
try {
    $posyanduList = $pdo->query("SELECT id_posyandu, nama_posyandu FROM posyandu WHERE status = 'aktif'")->fetchAll();
} catch (PDOException $e) {
    $posyanduList = [];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kader Posyandu — Admin Waluya</title>

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
                    <h2 class="topbar-page-title">Kelola Kader Posyandu (CRUD)</h2>
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
                    <h1 class="welcome-title">Manajemen Akun & Data Kader Posyandu</h1>
                    <p class="welcome-subtitle">
                        Kelola data kader pemeriksa: registrasi akun kader baru, penentuan jabatan posyandu, unit penempatan, dan status keaktifan penugasan.
                    </p>
                </div>

                <!-- Alert Pesan -->
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

                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                        <form method="GET" style="display: flex; gap: 10px; flex: 1; max-width: 480px;">
                            <div class="search-input-group">
                                <i data-lucide="search"></i>
                                <input type="text" name="q" placeholder="Cari berdasarkan nama kader..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <button type="submit" class="btn btn-secondary">Cari</button>
                            <?php if (!empty($search)): ?>
                                <a href="kader.php" class="btn btn-secondary" title="Reset pencarian">Reset</a>
                            <?php endif; ?>
                        </form>

                        <button type="button" class="btn btn-primary" onclick="openTambahKaderModal()">
                            <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
                            <span>Tambah Kader Baru</span>
                        </button>
                    </div>
                </div>

                <!-- Tabel Data Kader -->
                <div class="card">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nama Kader & Kontak</th>
                                    <th>NIK Kader</th>
                                    <th>Jabatan</th>
                                    <th>Unit Posyandu</th>
                                    <th>Tgl Bergabung</th>
                                    <th>Status</th>
                                    <th style="text-align: right;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kaderList as $k): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 700; color: #1E293B; font-size: 14px;"><?= htmlspecialchars($k['nama_lengkap']) ?></div>
                                            <div style="font-size: 12.5px; color: #475569;"><?= htmlspecialchars($k['email']) ?></div>
                                            <div style="font-size: 12px; color: #64748B;">HP: <?= htmlspecialchars($k['no_hp']) ?></div>
                                        </td>
                                        <td>
                                            <span style="font-family: monospace; font-size: 13px; font-weight: 700; color: #1E293B;">
                                                <?= htmlspecialchars($k['nik']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge" style="background:#E0F2FE; color:#0369A1; font-weight:700;">
                                                <?= htmlspecialchars($k['jabatan']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-role-kader"><?= htmlspecialchars($k['nama_posyandu']) ?></span>
                                        </td>
                                        <td style="font-size: 12.5px; color: #64748B;">
                                            <?= date('d M Y', strtotime($k['tanggal_bergabung'])) ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $k['status_kader'] === 'aktif' ? 'badge-active' : 'badge-danger' ?>">
                                                <?= ucfirst($k['status_kader']) ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right;">
                                            <div class="table-actions" style="justify-content: flex-end;">
                                                <button type="button" class="btn btn-sm btn-secondary" onclick='openEditKaderModal(<?= json_encode($k) ?>)' title="Ubah Data Kader">
                                                    <i data-lucide="edit-3" style="width: 13px; height: 13px;"></i>
                                                    <span>Edit</span>
                                                </button>

                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun kader <?= htmlspecialchars(addslashes($k['nama_lengkap'])) ?>?')">
                                                    <input type="hidden" name="crud_action" value="hapus">
                                                    <input type="hidden" name="id_user" value="<?= $k['id_user'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Kader">
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
                </div>

            </main>
        </div>
    </div>

    <!-- MODAL 1: TAMBAH KADER BARU -->
    <div class="modal-overlay" id="tambahKaderModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modal-title">Tambah Akun & Data Kader Baru</h3>
                <button type="button" class="modal-close-btn" onclick="closeTambahKaderModal()"><i data-lucide="x"></i></button>
            </div>
            <form method="POST">
                <input type="hidden" name="crud_action" value="tambah">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap Kader <span style="color:#EF4444;">*</span></label>
                        <input type="text" name="nama_lengkap" class="form-input" required placeholder="Nama lengkap kader">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Email Akun Login <span style="color:#EF4444;">*</span></label>
                            <input type="email" name="email" class="form-input" required placeholder="kader@waluya.id">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nomor HP / WhatsApp <span style="color:#EF4444;">*</span></label>
                            <input type="tel" name="no_hp" class="form-input" required placeholder="08xxxxxxxxxx">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">NIK KTP Kader (16 Digit) <span style="color:#EF4444;">*</span></label>
                            <input type="text" name="nik" class="form-input" maxlength="16" required placeholder="3273xxxxxxxxxxxx">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kata Sandi Awal</label>
                            <input type="text" name="password" class="form-input" value="password123" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Jabatan di Posyandu <span style="color:#EF4444;">*</span></label>
                            <select name="jabatan" class="form-select" required>
                                <option value="Ketua">Ketua</option>
                                <option value="Sekretaris">Sekretaris</option>
                                <option value="Bendahara">Bendahara</option>
                                <option value="Anggota" selected>Anggota</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Unit Posyandu Penempatan <span style="color:#EF4444;">*</span></label>
                            <select name="id_posyandu" class="form-select" required>
                                <?php foreach ($posyanduList as $pos): ?>
                                    <option value="<?= $pos['id_posyandu'] ?>"><?= htmlspecialchars($pos['nama_posyandu']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tanggal Bergabung</label>
                        <input type="date" name="tanggal_bergabung" class="form-input" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeTambahKaderModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Kader</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: EDIT KADER -->
    <div class="modal-overlay" id="editKaderModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modal-title">Edit Data & Penugasan Kader</h3>
                <button type="button" class="modal-close-btn" onclick="closeEditKaderModal()"><i data-lucide="x"></i></button>
            </div>
            <form method="POST">
                <input type="hidden" name="crud_action" value="edit">
                <input type="hidden" name="id_kader" id="edit_kid">
                <input type="hidden" name="id_user" id="edit_uid">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap Kader <span style="color:#EF4444;">*</span></label>
                        <input type="text" name="nama_lengkap" id="edit_knama" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nomor HP / WhatsApp <span style="color:#EF4444;">*</span></label>
                        <input type="tel" name="no_hp" id="edit_khp" class="form-input" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Jabatan</label>
                            <select name="jabatan" id="edit_kjabatan" class="form-select">
                                <option value="Ketua">Ketua</option>
                                <option value="Sekretaris">Sekretaris</option>
                                <option value="Bendahara">Bendahara</option>
                                <option value="Anggota">Anggota</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Unit Posyandu</label>
                            <select name="id_posyandu" id="edit_kpos" class="form-select">
                                <?php foreach ($posyanduList as $pos): ?>
                                    <option value="<?= $pos['id_posyandu'] ?>"><?= htmlspecialchars($pos['nama_posyandu']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status Keaktifan</label>
                        <select name="status_kader" id="edit_kstatus" class="form-select">
                            <option value="aktif">Aktif</option>
                            <option value="cuti">Cuti</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditKaderModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function openTambahKaderModal() {
            document.getElementById('tambahKaderModal').classList.add('active');
        }
        function closeTambahKaderModal() {
            document.getElementById('tambahKaderModal').classList.remove('active');
        }

        function openEditKaderModal(data) {
            document.getElementById('edit_kid').value = data.id_kader;
            document.getElementById('edit_uid').value = data.id_user;
            document.getElementById('edit_knama').value = data.nama_lengkap;
            document.getElementById('edit_khp').value = data.no_hp;
            document.getElementById('edit_kjabatan').value = data.jabatan;
            document.getElementById('edit_kpos').value = data.id_posyandu;
            document.getElementById('edit_kstatus').value = data.status_kader;

            document.getElementById('editKaderModal').classList.add('active');
            lucide.createIcons();
        }
        function closeEditKaderModal() {
            document.getElementById('editKaderModal').classList.remove('active');
        }
    </script>
</body>

</html>
