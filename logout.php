<?php
// ==============================================================================
// WALUYA - PROSES LOGOUT SISTEM
// ==============================================================================
session_start();

// Catat log aktivitas jika session user masih ada
if (isset($_SESSION['user_id'])) {
    require_once 'config/database.php';
    try {
        $logStmt = $pdo->prepare("INSERT INTO log_aktivitas (id_user, aksi, modul, deskripsi, ip_address, user_agent) VALUES (:uid, 'LOGOUT', 'Auth', 'User logout dari sistem', :ip, :ua)");
        $logStmt->execute([
            'uid' => $_SESSION['user_id'],
            'ip'  => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'ua'  => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        // Abaikan jika pencatatan log gagal
    }
}

// Hapus semua data sesi
$_SESSION = [];

// Hapus cookie sesi jika ada di browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan session
session_destroy();

// Redirect ke halaman login dengan parameter status
header("Location: login.php?status=logout");
exit();
