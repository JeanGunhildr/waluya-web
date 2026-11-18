<?php
// ==============================================================================
// HALAMAN LOGIN - WALUYA
// ==============================================================================
// File ini disiapkan untuk pengerjaan form login pengguna (Admin, Kader, Anggota Keluarga)
// Alur yang disarankan:
// 1. Cek autentikasi email & password dengan tabel users (password_verify)
// 2. Cek status_akun (jika role keluarga dan status 'pending', beri info nunggu ACC admin)
// 3. Simpan session (id_user, nama, role) dan arahkan ke dashboard masing-masing:
//    - admin    -> admin/index.php
//    - kader    -> kader/index.php
//    - keluarga -> keluarga/index.php
// ==============================================================================
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Waluya</title>
    <!-- Menghubungkan CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <!-- Mulai koding form login di sini -->

</body>

</html>