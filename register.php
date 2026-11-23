<?php
// ==============================================================================
// WALUYA - HALAMAN PENDAFTARAN ANGGOTA KELUARGA
// ==============================================================================
session_start();

// Jika pengguna sudah login, arahkan langsung ke dashboard sesuai role
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/index.php");
    } elseif ($_SESSION['role'] === 'kader') {
        header("Location: kader/index.php");
    } elseif ($_SESSION['role'] === 'keluarga') {
        header("Location: keluarga/index.php");
    }
    exit();
}

require_once 'config/database.php';

$error_message = '';
$is_success = false;

// Tangkap dan proses data registrasi keluarga saat form di-submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Data Akun
    $nama_lengkap     = trim($_POST['nama_lengkap'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $no_hp            = trim($_POST['no_hp'] ?? '');
    $password         = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // 2. Data Detail Keluarga
    $nik              = trim($_POST['nik'] ?? '');
    $alamat_domisili  = trim($_POST['alamat_domisili'] ?? '');
    $rt               = trim($_POST['rt'] ?? '01');
    $rw               = trim($_POST['rw'] ?? '01');
    $pekerjaan        = trim($_POST['pekerjaan'] ?? '');
    $kontak_darurat   = trim($_POST['kontak_darurat'] ?? '');

    // Validasi Dasar
    if (empty($nama_lengkap) || empty($email) || empty($no_hp) || empty($password) || empty($nik) || empty($alamat_domisili) || empty($kontak_darurat)) {
        $error_message = 'Harap lengkapi semua kolom bertanda bintang (*) yang wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Format alamat email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Kata sandi minimal harus 6 karakter demi keamanan akun.';
    } elseif ($password !== $password_confirm) {
        $error_message = 'Konfirmasi kata sandi tidak cocok dengan kata sandi yang dimasukkan.';
    } elseif (!preg_match('/^[0-9]{16}$/', $nik)) {
        $error_message = 'Nomor Induk Kependudukan (NIK) harus terdiri dari tepat 16 digit angka.';
    } else {
        try {
            // Cek apakah email atau nomor HP sudah terdaftar di tabel users
            $checkUser = $pdo->prepare("SELECT id_user, email, no_hp FROM users WHERE email = :email OR no_hp = :no_hp LIMIT 1");
            $checkUser->execute(['email' => $email, 'no_hp' => $no_hp]);
            $existingUser = $checkUser->fetch();

            if ($existingUser) {
                if ($existingUser['email'] === $email) {
                    $error_message = 'Alamat email ini sudah terdaftar. Silakan gunakan email lain atau masuk ke akun Anda.';
                } else {
                    $error_message = 'Nomor HP/WhatsApp ini sudah terdaftar. Silakan gunakan nomor lain.';
                }
            } else {
                // Cek apakah NIK sudah pernah terdaftar di tabel keluarga
                $checkNik = $pdo->prepare("SELECT id_keluarga FROM keluarga WHERE nik = :nik LIMIT 1");
                $checkNik->execute(['nik' => $nik]);
                if ($checkNik->fetch()) {
                    $error_message = 'NIK keluarga ini sudah terdaftar dalam sistem.';
                } else {
                    // Simpan data pendaftaran secara atomik (Database Transaction)
                    $pdo->beginTransaction();

                    // Hash password dengan bcrypt
                    $password_hash = password_hash($password, PASSWORD_BCRYPT);

                    // 1. Simpan ke tabel users (role 'keluarga', status_akun 'pending' menunggu ACC Admin)
                    $insertUser = $pdo->prepare("
                        INSERT INTO users (nama_lengkap, email, no_hp, password, role, status_akun, created_at)
                        VALUES (:nama, :email, :no_hp, :password, 'keluarga', 'pending', NOW())
                    ");
                    $insertUser->execute([
                        'nama'     => $nama_lengkap,
                        'email'    => $email,
                        'no_hp'    => $no_hp,
                        'password' => $password_hash
                    ]);
                    $newUserId = $pdo->lastInsertId();

                    // 2. Simpan ke tabel keluarga
                    $insertKeluarga = $pdo->prepare("
                        INSERT INTO keluarga (id_user, nik, alamat_domisili, rt, rw, pekerjaan, kontak_darurat, created_at)
                        VALUES (:id_user, :nik, :alamat, :rt, :rw, :pekerjaan, :kontak_darurat, NOW())
                    ");
                    $insertKeluarga->execute([
                        'id_user'        => $newUserId,
                        'nik'            => $nik,
                        'alamat'         => $alamat_domisili,
                        'rt'             => $rt,
                        'rw'             => $rw,
                        'pekerjaan'      => $pekerjaan,
                        'kontak_darurat' => $kontak_darurat
                    ]);

                    // Catat log aktivitas jika tabel log_aktivitas tersedia
                    try {
                        $logStmt = $pdo->prepare("INSERT INTO log_aktivitas (id_user, aksi, modul, deskripsi, ip_address, user_agent) VALUES (:uid, 'REGISTER', 'Auth', 'Pendaftaran akun keluarga baru berstatus pending', :ip, :ua)");
                        $logStmt->execute([
                            'uid' => $newUserId,
                            'ip'  => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                            'ua'  => $_SERVER['HTTP_USER_AGENT'] ?? ''
                        ]);
                    } catch (Exception $e) {}

                    $pdo->commit();
                    $is_success = true;
                }
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_message = 'Terjadi kesalahan sistem saat menyimpan pendaftaran: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun Keluarga — Waluya</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Stylesheet Khusus Auth Bertema Index -->
    <link rel="stylesheet" href="assets/css/auth.css?v=<?= filemtime('assets/css/auth.css') ?>">
</head>

<body>

    <!-- Header / Navbar Sederhana -->
    <header class="auth-header">
        <a href="index.php" class="auth-logo">
            <img src="assets/images/logo.png" alt="Logo Waluya" class="auth-logo-img" width="38" height="38" style="width: 38px; height: 38px; max-width: 38px; max-height: 38px; object-fit: contain; flex-shrink: 0;">
            <span class="auth-logo-title">Waluya</span>
        </a>

        <a href="index.php" class="btn-back-home">
            <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
            <span>Kembali ke Beranda</span>
        </a>
    </header>

    <!-- Kontainer Kartu Registrasi -->
    <main class="auth-container">
        <div class="auth-card-wrapper card-register">

            <!-- Panel Form Registrasi / State Sukses -->
            <div class="auth-form-panel">

                <?php if ($is_success): ?>
                <!-- TAMPILAN SUKSES PENDAFTARAN -->
                <div class="register-success-card">
                    <div class="success-icon-badge">
                        <i data-lucide="check" style="width: 38px; height: 38px; stroke-width: 2.5;"></i>
                    </div>
                    <h1 class="success-title">Pendaftaran Berhasil!</h1>
                    <p class="success-desc">
                        Terima kasih <strong><?= htmlspecialchars($nama_lengkap) ?></strong>. Data pendaftaran akun
                        keluarga Anda telah berhasil tersimpan dalam sistem Waluya.
                    </p>

                    <div class="info-status-box">
                        <div class="info-status-title">
                            <i data-lucide="clock" style="width: 18px; height: 18px;"></i>
                            Status Akun: Menunggu Persetujuan (ACC) Admin
                        </div>
                        <p class="info-status-desc">
                            Sesuai prosedur keamanan data kesehatan, Admin Posyandu akan memverifikasi kesesuaian data
                            NIK dan domisili Anda terlebih dahulu. Setelah disetujui, Anda dapat langsung login.
                        </p>
                    </div>

                    <a href="login.php" class="btn-submit" style="text-decoration: none;">
                        <i data-lucide="log-in" style="width: 18px; height: 18px;"></i>
                        <span>Menuju Halaman Login</span>
                    </a>
                </div>

                <?php else: ?>
                <!-- FORMULIR PENDAFTARAN -->
                <h1 class="auth-title">Daftar Akun Keluarga</h1>
                <p class="auth-subtitle">Daftarkan akun Anda untuk memantau kesehatan anggota keluarga lansia</p>

                <!-- Alert Jika Terdapat Kesalahan Input -->
                <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i data-lucide="alert-circle" class="alert-icon" style="width: 18px; height: 18px;"></i>
                    <div class="alert-content">
                        <strong>Gagal Mendaftar</strong>
                        <?= htmlspecialchars($error_message) ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Informasi Langkah Pendaftaran di Bagian Paling Atas Form -->
                <div class="register-steps-banner">
                    <div class="steps-timeline">
                        <div class="step-item">
                            <span class="step-num">1</span>
                            <div class="step-text">
                                <strong>Isi Formulir</strong>
                                <span>Lengkapi data diri & NIK</span>
                            </div>
                        </div>
                        <div class="step-arrow"><i data-lucide="chevron-right" style="width: 16px; height: 16px;"></i>
                        </div>
                        <div class="step-item">
                            <span class="step-num">2</span>
                            <div class="step-text">
                                <strong>Verifikasi Admin</strong>
                                <span>Menunggu ACC Admin</span>
                            </div>
                        </div>
                        <div class="step-arrow"><i data-lucide="chevron-right" style="width: 16px; height: 16px;"></i>
                        </div>
                        <div class="step-item">
                            <span class="step-num">3</span>
                            <div class="step-text">
                                <strong>Mulai Pantau</strong>
                                <span>Akses rekam medis lansia</span>
                            </div>
                        </div>
                    </div>
                </div>

                <form action="register.php" method="POST" autocomplete="off">

                    <div class="form-grid">
                        <!-- Bagian 1: Data Akun Login -->
                        <div class="form-group full-width">
                            <label class="form-label" for="nama_lengkap">Nama Lengkap Anda <span
                                    class="required">*</span></label>
                            <div class="input-wrapper">
                                <span class="input-icon"><i data-lucide="user"
                                        style="width: 18px; height: 18px;"></i></span>
                                <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control"
                                    placeholder="Contoh: Budi Santoso"
                                    value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>" required autofocus>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="email">Alamat Email <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <span class="input-icon"><i data-lucide="mail"
                                        style="width: 18px; height: 18px;"></i></span>
                                <input type="email" id="email" name="email" class="form-control"
                                    placeholder="nama@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                    required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="no_hp">Nomor HP / WhatsApp <span
                                    class="required">*</span></label>
                            <div class="input-wrapper">
                                <span class="input-icon"><i data-lucide="phone"
                                        style="width: 18px; height: 18px;"></i></span>
                                <input type="tel" id="no_hp" name="no_hp" class="form-control"
                                    placeholder="081234567890" value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>"
                                    required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">Kata Sandi <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <span class="input-icon"><i data-lucide="lock"
                                        style="width: 18px; height: 18px;"></i></span>
                                <input type="password" id="password" name="password" class="form-control"
                                    placeholder="Minimal 6 karakter" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password_confirm">Konfirmasi Kata Sandi <span
                                    class="required">*</span></label>
                            <div class="input-wrapper">
                                <span class="input-icon"><i data-lucide="check-check"
                                        style="width: 18px; height: 18px;"></i></span>
                                <input type="password" id="password_confirm" name="password_confirm"
                                    class="form-control" placeholder="Ulangi kata sandi" required>
                            </div>
                        </div>

                        <!-- Bagian 2: Data Kependudukan & Wali Keluarga -->
                        <div class="form-group">
                            <label class="form-label" for="nik">NIK KTP Anda (16 Digit) <span
                                    class="required">*</span></label>
                            <div class="input-wrapper">
                                <span class="input-icon"><i data-lucide="credit-card"
                                        style="width: 18px; height: 18px;"></i></span>
                                <input type="text" id="nik" name="nik" class="form-control" maxlength="16"
                                    placeholder="3273xxxxxxxxxxxx" value="<?= htmlspecialchars($_POST['nik'] ?? '') ?>"
                                    required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="kontak_darurat">Nomor Kontak Darurat <span
                                    class="required">*</span></label>
                            <div class="input-wrapper">
                                <span class="input-icon"><i data-lucide="phone-call"
                                        style="width: 18px; height: 18px;"></i></span>
                                <input type="tel" id="kontak_darurat" name="kontak_darurat" class="form-control"
                                    placeholder="Nomor alternatif keluarga"
                                    value="<?= htmlspecialchars($_POST['kontak_darurat'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label class="form-label" for="alamat_domisili">Alamat Domisili Lengkap <span
                                    class="required">*</span></label>
                            <textarea id="alamat_domisili" name="alamat_domisili" class="form-control"
                                placeholder="Nama jalan, nomor rumah, kelurahan, kecamatan"
                                required><?= htmlspecialchars($_POST['alamat_domisili'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="rt">RT / RW</label>
                            <div style="display: flex; gap: 8px;">
                                <input type="text" id="rt" name="rt" class="form-control" style="padding-left: 14px;"
                                    placeholder="RT (01)" value="<?= htmlspecialchars($_POST['rt'] ?? '01') ?>">
                                <input type="text" id="rw" name="rw" class="form-control" style="padding-left: 14px;"
                                    placeholder="RW (05)" value="<?= htmlspecialchars($_POST['rw'] ?? '05') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="pekerjaan">Pekerjaan (Opsional)</label>
                            <input type="text" id="pekerjaan" name="pekerjaan" class="form-control"
                                style="padding-left: 14px;" placeholder="Contoh: Wiraswasta, Guru, Karyawan"
                                value="<?= htmlspecialchars($_POST['pekerjaan'] ?? '') ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i data-lucide="user-plus" style="width: 18px; height: 18px;"></i>
                        <span>Daftarkan Akun Keluarga</span>
                    </button>
                </form>

                <p class="auth-footer-text">
                    Sudah memiliki akun?
                    <a href="login.php">Masuk di Sini</a>
                </p>
                <?php endif; ?>

            </div>

        </div>
    </main>

    <script>
    // Render ikon Lucide
    lucide.createIcons();
    </script>
</body>

</html>