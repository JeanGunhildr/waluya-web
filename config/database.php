<?php
// ==============================================================================
// KONEKSI DATABASE WALUYA (PDO NATIVE)
// ==============================================================================
// Cara memanggil di file lain:
// require_once '../config/database.php'; (dari dalam folder admin/kader/keluarga)
// require_once 'config/database.php';   (dari root / login.php)
// ==============================================================================

// Zona waktu aplikasi: WIB (agar tanggal/jam default form & filter "bulan ini" benar)
date_default_timezone_set('Asia/Jakarta');

$host     = 'localhost';
$database = 'waluya_db';
$username = 'root';
$password = '';
$charset  = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$database};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lempar exception jika terjadi error SQL
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Ambil data sebagai array asosiatif
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Gunakan prepared statements asli untuk keamanan SQL Injection
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    $pdo->exec("SET time_zone = '+07:00'"); // samakan CURRENT_TIMESTAMP database dengan WIB
} catch (PDOException $e) {
    die("Koneksi database Waluya gagal: " . $e->getMessage());
}

?>