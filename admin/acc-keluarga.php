<?php
// ==============================================================================
// WALUYA - VERIFIKASI & ACC PENDAFTARAN KELUARGA (ADMIN)
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
$alert_msg = '';
$alert_type = '';

// 1. Pemrosesan Aksi ACC / Tolak / Reset Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $userId = (int)($_POST['id_user'] ?? 0);

    if ($action === 'approve' && $userId > 0) {
        try {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET status_akun = 'aktif', id_admin_acc = :admin_id, tanggal_acc = NOW(), catatan_acc = 'Disetujui oleh Administrator'
                WHERE id_user = :id AND role = 'keluarga'
            ");
            $stmt->execute(['admin_id' => $_SESSION['user_id'], 'id' => $userId]);

            $alert_type = 'success';
            $alert_msg = "Akun keluarga berhasil disetujui (ACC)! Pengguna kini dapat masuk ke portal keluarga.";
        } catch (PDOException $e) {
            $alert_type = 'danger';
            $alert_msg = "Gagal memproses persetujuan akun: " . $e->getMessage();
        }
    } elseif ($action === 'reject' && $userId > 0) {
        $alasan = trim($_POST['alasan_penolakan'] ?? 'Data identitas NIK atau domisili tidak cocok.');
        try {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET status_akun = 'ditolak', id_admin_acc = :admin_id, tanggal_acc = NOW(), catatan_acc = :alasan
                WHERE id_user = :id AND role = 'keluarga'
            ");
            $stmt->execute(['admin_id' => $_SESSION['user_id'], 'alasan' => $alasan, 'id' => $userId]);

            $alert_type = 'warning';
            $alert_msg = "Pendaftaran akun keluarga berhasil ditolak.";
        } catch (PDOException $e) {
            $alert_type = 'danger';
            $alert_msg = "Gagal menolak akun: " . $e->getMessage();
        }
    } elseif ($action === 'reset_pending' && $userId > 0) {
        try {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET status_akun = 'pending', id_admin_acc = NULL, tanggal_acc = NULL, catatan_acc = NULL
                WHERE id_user = :id AND role = 'keluarga'
            ");
            $stmt->execute(['id' => $userId]);

            $alert_type = 'info';
            $alert_msg = "Status akun dikembalikan menjadi pending.";
        } catch (PDOException $e) {
            $alert_type = 'danger';
            $alert_msg = "Gagal mereset status: " . $e->getMessage();
        }
    }
}

// 2. Tab Filter Status
$current_tab = $_GET['tab'] ?? 'pending';
if (!in_array($current_tab, ['pending', 'aktif', 'ditolak'])) {
    $current_tab = 'pending';
}

// 3. Hitung Jumlah Masing-Masing Tab
try {
    $countPending = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'keluarga' AND status_akun = 'pending'")->fetchColumn();
    $countAktif   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'keluarga' AND status_akun = 'aktif'")->fetchColumn();
    $countDitolak = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'keluarga' AND status_akun = 'ditolak'")->fetchColumn();
} catch (PDOException $e) {
    $countPending = $countAktif = $countDitolak = 0;
}

// 4. Query Data Sesuai Tab yang Dipilih
$search = trim($_GET['q'] ?? '');
try {
    $sql = "
        SELECT u.id_user, u.nama_lengkap, u.email, u.no_hp, u.status_akun, u.created_at AS waktu_daftar, 
               u.tanggal_acc, u.catatan_acc, u_admin.nama_lengkap AS nama_admin,
               k.nik, k.alamat_domisili, k.rt, k.rw, k.pekerjaan, k.kontak_darurat
        FROM users u
        LEFT JOIN keluarga k ON u.id_user = k.id_user
        LEFT JOIN users u_admin ON u.id_admin_acc = u_admin.id_user
        WHERE u.role = 'keluarga' AND u.status_akun = :status
    ";
    $params = ['status' => $current_tab];
    if (!empty($search)) {
        $sql .= " AND u.nama_lengkap LIKE :q";
        $params['q'] = "%$search%";
    }
    $sql .= " ORDER BY u.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $usersList = $stmt->fetchAll();
} catch (PDOException $e) {
    $usersList = [];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACC Pendaftaran Keluarga — Admin Waluya</title>

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
                    <h1 class="welcome-title">Verifikasi & Persetujuan (ACC) Akun Keluarga</h1>
                    <p class="welcome-subtitle">
                        Sesuai standar operasional Posyandu, setiap akun keluarga yang mendaftar secara mandiri memerlukan persetujuan Admin agar data kesehatan lansia hanya diakses oleh keluarga yang berwenang.
                    </p>
                </div>

                <!-- Alert Feedback -->
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

                <!-- Tab Filter Menu -->
                <div style="display: flex; gap: 12px; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                    <a href="acc-keluarga.php?tab=pending" class="btn <?= $current_tab === 'pending' ? 'btn-primary' : 'btn-secondary' ?>">
                        <i data-lucide="clock" style="width: 16px; height: 16px;"></i>
                        <span>Menunggu ACC</span>
                        <span class="badge" style="background: <?= $current_tab === 'pending' ? '#FFFFFF' : '#EF4444' ?>; color: <?= $current_tab === 'pending' ? 'var(--primary-dark)' : '#FFFFFF' ?>; font-size: 11px;">
                            <?= $countPending ?>
                        </span>
                    </a>

                    <a href="acc-keluarga.php?tab=aktif" class="btn <?= $current_tab === 'aktif' ? 'btn-primary' : 'btn-secondary' ?>">
                        <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i>
                        <span>Sudah Disetujui (Aktif)</span>
                        <span class="badge" style="background:#F1F5F9; color:#334155; font-size:11px;"><?= $countAktif ?></span>
                    </a>

                    <a href="acc-keluarga.php?tab=ditolak" class="btn <?= $current_tab === 'ditolak' ? 'btn-primary' : 'btn-secondary' ?>">
                        <i data-lucide="x-circle" style="width: 16px; height: 16px;"></i>
                        <span>Pendaftaran Ditolak</span>
                        <span class="badge" style="background:#F1F5F9; color:#334155; font-size:11px;"><?= $countDitolak ?></span>
                    </a>
                </div>

                <!-- Pencarian Keluarga -->
                <div class="card" style="margin-bottom: 20px;">
                    <form method="GET" style="display: flex; gap: 10px; flex: 1; max-width: 480px;">
                        <input type="hidden" name="tab" value="<?= htmlspecialchars($current_tab) ?>">
                        <div class="search-input-group">
                            <i data-lucide="search"></i>
                            <input type="text" name="q" placeholder="Cari berdasarkan nama pemohon..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <button type="submit" class="btn btn-secondary">Cari</button>
                        <?php if (!empty($search)): ?>
                            <a href="acc-keluarga.php?tab=<?= urlencode($current_tab) ?>" class="btn btn-secondary">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Tabel Akun Sesuai Tab -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i data-lucide="<?= $current_tab === 'pending' ? 'user-check' : ($current_tab === 'aktif' ? 'check' : 'x') ?>" style="width: 18px; height: 18px; color: var(--primary);"></i>
                            Daftar Pendaftar (Status: <?= strtoupper($current_tab) ?>) — <?= count($usersList) ?> Akun
                        </h3>
                    </div>

                    <?php if (count($usersList) > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Nama Lengkap & Kontak</th>
                                        <th>Identitas NIK KTP</th>
                                        <th>Alamat Domisili</th>
                                        <th>Kontak Darurat</th>
                                        <th>Waktu Pendaftaran</th>
                                        <?php if ($current_tab !== 'pending'): ?>
                                            <th>Keterangan / Verifikator</th>
                                        <?php endif; ?>
                                        <th style="text-align: right;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usersList as $u): ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight: 700; color: #1E293B; font-size: 14px;"><?= htmlspecialchars($u['nama_lengkap']) ?></div>
                                                <div style="font-size: 12.5px; color: #475569;"><?= htmlspecialchars($u['email']) ?></div>
                                                <div style="font-size: 12px; color: #64748B;">HP: <?= htmlspecialchars($u['no_hp']) ?></div>
                                                <?php if (!empty($u['pekerjaan'])): ?>
                                                    <span style="font-size: 11px; color: #0284C7;">Pekerjaan: <?= htmlspecialchars($u['pekerjaan']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span style="font-family: monospace; font-size: 13px; font-weight: 700; color: #1E293B;">
                                                    <?= htmlspecialchars($u['nik'] ?? 'Belum ada NIK') ?>
                                                </span>
                                            </td>
                                            <td style="font-size: 12.5px; color: #475569;">
                                                <?= htmlspecialchars($u['alamat_domisili'] ?? '-') ?>
                                                <div style="font-size: 11px; color: #64748B;">RT <?= htmlspecialchars($u['rt'] ?? '-') ?> / RW <?= htmlspecialchars($u['rw'] ?? '-') ?></div>
                                            </td>
                                            <td style="font-size: 12.5px; color: #1E293B;">
                                                <?= htmlspecialchars($u['kontak_darurat'] ?? '-') ?>
                                            </td>
                                            <td style="font-size: 12px; color: #64748B;">
                                                <?= date('d M Y, H:i', strtotime($u['waktu_daftar'])) ?>
                                            </td>
                                            <?php if ($current_tab !== 'pending'): ?>
                                                <td style="font-size: 12px; color: #475569;">
                                                    <div>Oleh: <strong><?= htmlspecialchars($u['nama_admin'] ?? 'Admin') ?></strong></div>
                                                    <div style="color: #64748B;"><?= $u['tanggal_acc'] ? date('d M Y, H:i', strtotime($u['tanggal_acc'])) : '-' ?></div>
                                                    <?php if (!empty($u['catatan_acc'])): ?>
                                                        <div style="font-style: italic; color: #D97706; margin-top: 2px;">"<?= htmlspecialchars($u['catatan_acc']) ?>"</div>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                            <td style="text-align: right;">
                                                <div class="table-actions" style="justify-content: flex-end;">
                                                    <?php if ($current_tab === 'pending'): ?>
                                                        <!-- Aksi Setujui (ACC) -->
                                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui akun keluarga ini?')">
                                                            <input type="hidden" name="action" value="approve">
                                                            <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-success" title="Setujui pendaftaran ini">
                                                                <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                                                                <span>Setujui (ACC)</span>
                                                            </button>
                                                        </form>

                                                        <!-- Tombol Tolak Modal -->
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="openRejectModal(<?= $u['id_user'] ?>, '<?= htmlspecialchars(addslashes($u['nama_lengkap'])) ?>')"
                                                                title="Tolak pendaftaran">
                                                            <i data-lucide="x" style="width: 14px; height: 14px;"></i>
                                                            <span>Tolak</span>
                                                        </button>
                                                    <?php elseif ($current_tab === 'ditolak'): ?>
                                                        <!-- Aksi Kembalikan / Buka Kembali ke Pending -->
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="action" value="reset_pending">
                                                            <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-secondary" title="Kembalikan ke antrean pending">
                                                                <i data-lucide="rotate-ccw" style="width: 14px; height: 14px;"></i>
                                                                <span>Tinjau Ulang</span>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="badge badge-active">Terverifikasi</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; color: #64748B; padding: 40px;">
                            <i data-lucide="check-circle-2" style="width: 32px; height: 32px; color: #CBD5E1; margin-bottom: 8px;"></i>
                            <p>Tidak ada data pendaftar pada tab <strong><?= ucfirst($current_tab) ?></strong>.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- MODAL PENOLAKAN PENDAFTARAN KELUARGA -->
    <div class="modal-overlay" id="rejectModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modal-title">Tolak Pendaftaran Keluarga</h3>
                <button type="button" class="modal-close-btn" onclick="closeRejectModal()"><i data-lucide="x"></i></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="id_user" id="rejectUserId">
                    <p style="font-size: 13.5px; color: #475569; margin-bottom: 14px;">
                        Anda akan menolak akun pendaftar atas nama <strong id="rejectUserName" style="color:#1E293B;"></strong>.
                    </p>
                    <div class="form-group">
                        <label class="form-label" for="alasan_penolakan">Alasan Penolakan <span style="color:#EF4444;">*</span></label>
                        <textarea name="alasan_penolakan" id="alasan_penolakan" class="form-textarea" rows="3" required
                                  placeholder="Contoh: NIK tidak sesuai KTP atau alamat berada di luar wilayah binaan Posyandu ini."></textarea>
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
