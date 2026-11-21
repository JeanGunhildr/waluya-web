<?php
// ==============================================================================
// WALUYA - HALAMAN LOGIN
// ==============================================================================
session_start();

// Jika pengguna sudah login, arahkan langsung ke dashboard sesuai role masing-masing
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
$error_type = ''; // 'danger' atau 'warning'
$success_message = '';

// Pesan dari redirect (Logout / Registrasi Baru / Unauthorized)
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'logout') {
        $success_message = 'Anda telah berhasil keluar dari sistem Waluya.';
    } elseif ($_GET['status'] === 'registered') {
        $error_type = 'warning';
        $error_message = 'Pendaftaran berhasil! Akun Anda saat ini sedang menunggu persetujuan (ACC) dari Admin Posyandu.';
    }
}
if (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
    $error_type = 'danger';
    $error_message = 'Silakan login terlebih dahulu untuk mengakses halaman tersebut.';
}

// Proses Form Login saat tombol Submit ditekan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $error_type = 'danger';
        $error_message = 'Harap isi Email/Nomor HP dan Kata Sandi.';
    } else {
        try {
            // Cari user berdasarkan email ATAU nomor handphone (gunakan parameter berbeda untuk native prepared statements)
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email OR no_hp = :no_hp LIMIT 1");
            $stmt->execute(['email' => $identifier, 'no_hp' => $identifier]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Pengecekan status akun (Khusus anggota keluarga yang mendaftar mandiri)
                if ($user['status_akun'] === 'pending') {
                    $error_type = 'warning';
                    $error_message = 'Akun Anda sedang dalam proses verifikasi (ACC) oleh Admin Posyandu. Silakan tunggu hingga admin menyetujui akun Anda.';
                } elseif ($user['status_akun'] === 'ditolak') {
                    $error_type = 'danger';
                    $catatan = !empty($user['catatan_acc']) ? ' Alasan: ' . htmlspecialchars($user['catatan_acc']) : '';
                    $error_message = 'Pendaftaran akun Anda ditolak oleh Admin Posyandu.' . $catatan;
                } elseif ($user['status_akun'] === 'nonaktif') {
                    $error_type = 'danger';
                    $error_message = 'Akun Anda telah dinonaktifkan. Silakan hubungi pihak Posyandu atau Admin.';
                } else {
                    // Status Aktif: Simpan Session dan Arahkan ke Dashboard Role
                    $_SESSION['user_id']     = $user['id_user'];
                    $_SESSION['nama']        = $user['nama_lengkap'];
                    $_SESSION['email']       = $user['email'];
                    $_SESSION['role']        = $user['role'];
                    $_SESSION['status_akun']  = $user['status_akun'];
                    $_SESSION['foto_profil'] = $user['foto_profil'] ?? 'default_avatar.png';

                    // Update waktu terakhir login
                    $updateStmt = $pdo->prepare("UPDATE users SET terakhir_login = NOW() WHERE id_user = :id");
                    $updateStmt->execute(['id' => $user['id_user']]);

                    // Catat log aktivitas jika ada tabel log_aktivitas
                    try {
                        $logStmt = $pdo->prepare("INSERT INTO log_aktivitas (id_user, aksi, modul, deskripsi, ip_address, user_agent) VALUES (:uid, 'LOGIN', 'Auth', 'User berhasil login ke sistem', :ip, :ua)");
                        $logStmt->execute([
                            'uid' => $user['id_user'],
                            'ip'  => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                            'ua'  => $_SERVER['HTTP_USER_AGENT'] ?? ''
                        ]);
                    } catch (Exception $e) {
                        // Abaikan bila log gagal
                    }

                    // Pengalihan halaman berdasarkan role
                    if ($user['role'] === 'admin') {
                        header("Location: admin/index.php");
                    } elseif ($user['role'] === 'kader') {
                        header("Location: kader/index.php");
                    } else {
                        header("Location: keluarga/index.php");
                    }
                    exit();
                }
            } else {
                $error_type = 'danger';
                $error_message = 'Email / Nomor HP atau kata sandi yang Anda masukkan salah.';
            }
        } catch (PDOException $e) {
            $error_type = 'danger';
            $error_message = 'Terjadi kesalahan sistem database: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Akun — Waluya</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Stylesheet Khusus Auth Bertema Index -->
    <link rel="stylesheet" href="assets/css/auth.css">
</head>

<body>

    <!-- Header / Navbar Sederhana -->
    <header class="auth-header">
        <a href="index.php" class="auth-logo">
            <span class="auth-logo-icon">
                <img src="https://i.pinimg.com/736x/98/0f/03/980f03116b6fc42b54afac95ac23904f.jpg" alt="Logo Waluya">
            </span>
            <div>
                <span class="auth-logo-title">Waluya</span>
                <span class="auth-logo-sub">Posyandu Lansia</span>
            </div>
        </a>

        <a href="index.php" class="btn-back-home">
            <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
            <span>Kembali ke Beranda</span>
        </a>
    </header>

    <!-- Kontainer Kartu Login -->
    <main class="auth-container">
        <div class="auth-card-wrapper card-login">

            <!-- Panel Form Login (Tampil Bersih & Terpusat) -->
            <div class="auth-form-panel">
                <h1 class="auth-title">Selamat Datang Kembali</h1>
                <p class="auth-subtitle">Silakan masukkan akun Anda untuk masuk ke sistem Waluya</p>

                <!-- Alert Pesan Sukses / Error / Menunggu ACC -->
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <i data-lucide="check-circle" class="alert-icon" style="width: 18px; height: 18px;"></i>
                        <div class="alert-content">
                            <strong>Berhasil</strong>
                            <?= htmlspecialchars($success_message) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-<?= $error_type === 'warning' ? 'warning' : 'danger' ?>">
                        <i data-lucide="<?= $error_type === 'warning' ? 'alert-triangle' : 'alert-circle' ?>" class="alert-icon" style="width: 18px; height: 18px;"></i>
                        <div class="alert-content">
                            <strong><?= $error_type === 'warning' ? 'Menunggu Persetujuan Admin' : 'Perhatian' ?></strong>
                            <?= htmlspecialchars($error_message) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" autocomplete="off">
                    <div class="form-group">
                        <label class="form-label" for="identifier">Email atau Nomor HP <span class="required">*</span></label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <i data-lucide="user" style="width: 18px; height: 18px;"></i>
                            </span>
                            <input type="text" id="identifier" name="identifier" class="form-control" 
                                   placeholder="misal: budi@gmail.com atau 081234567890" 
                                   value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>" required autofocus>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Kata Sandi <span class="required">*</span></label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <i data-lucide="lock" style="width: 18px; height: 18px;"></i>
                            </span>
                            <input type="password" id="password" name="password" class="form-control" 
                                   placeholder="Masukkan kata sandi akun Anda" required>
                            <button type="button" class="password-toggle" id="togglePassword" aria-label="Lihat kata sandi">
                                <i data-lucide="eye" id="eyeIcon" style="width: 18px; height: 18px;"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i data-lucide="log-in" style="width: 18px; height: 18px;"></i>
                        <span>Masuk ke Akun</span>
                    </button>
                </form>

                <p class="auth-footer-text">
                    Belum memiliki akun keluarga? 
                    <a href="register.php">Daftar Akun Baru</a>
                </p>

                <!-- Box Bantuan Akun Demo untuk Kemudahan Tugas Kelompok -->
                <div class="demo-accounts-box">
                    <div class="demo-title">
                        <span><i data-lucide="key" style="width: 13px; height: 13px; display: inline-block; vertical-align: -2px;"></i> Akun Uji Coba Cepat (Password: password123)</span>
                    </div>
                    <div class="demo-items-grid">
                        <div class="demo-pill" onclick="fillDemo('admin@waluya.id', 'password123')" title="Klik untuk isi form">
                            <span class="demo-pill-role">Admin</span>
                            <span class="demo-pill-email">admin@waluya.id</span>
                        </div>
                        <div class="demo-pill" onclick="fillDemo('kader.siti@waluya.id', 'password123')" title="Klik untuk isi form">
                            <span class="demo-pill-role">Kader</span>
                            <span class="demo-pill-email">kader.siti@waluya.id</span>
                        </div>
                        <div class="demo-pill" onclick="fillDemo('budi.santoso@gmail.com', 'password123')" title="Klik untuk isi form">
                            <span class="demo-pill-role">Keluarga (Aktif)</span>
                            <span class="demo-pill-email">budi.santoso@gmail.com</span>
                        </div>
                        <div class="demo-pill" onclick="fillDemo('dewi.lestari@gmail.com', 'password123')" title="Klik untuk uji akun pending ACC Admin">
                            <span class="demo-pill-role" style="color: #D97706;">Keluarga (Pending)</span>
                            <span class="demo-pill-email">dewi.lestari@gmail.com</span>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </main>

    <script>
        // Inisialisasi ikon Lucide
        lucide.createIcons();

        // Fitur Toggle Password Visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        if (togglePassword) {
            togglePassword.addEventListener('click', function () {
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                eyeIcon.setAttribute('data-lucide', isPassword ? 'eye-off' : 'eye');
                lucide.createIcons();
            });
        }

        // Helper isi otomatis akun demo untuk mempermudah penilaian / pengujian
        function fillDemo(email, pass) {
            document.getElementById('identifier').value = email;
            document.getElementById('password').value = pass;
            document.getElementById('password').focus();
        }
    </script>
</body>

</html>
