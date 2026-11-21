<?php
// ==============================================================================
// WALUYA - HELPER AUTENTIKASI, CSRF, FLASH MESSAGE & UTILITAS UMUM
// ==============================================================================
// Pemakaian (dari folder kader/):
//   require_once '../config/database.php';
//   require_once '../config/auth.php';
//   $kader = require_kader($pdo);          // guard + profil kader
// ==============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Escape output HTML (singkat). */
function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// ------------------------------------------------------------------------------
// GUARD ROLE KADER
// ------------------------------------------------------------------------------
/**
 * Memastikan pengguna login sebagai kader & punya profil di tabel `kader`.
 * Mengembalikan array: id_kader, id_posyandu, nama_posyandu, jabatan, status_kader, can_write.
 */
function require_kader(PDO $pdo): array
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php?error=unauthorized");
        exit();
    }
    if (($_SESSION['role'] ?? '') !== 'kader') {
        header("Location: " . (($_SESSION['role'] ?? '') === 'admin' ? "../admin/index.php" : "../keluarga/index.php"));
        exit();
    }

    $stmt = $pdo->prepare(
        "SELECT k.id_kader, k.id_posyandu, k.jabatan, k.status_kader, p.nama_posyandu, p.alamat AS alamat_posyandu
           FROM kader k
           JOIN posyandu p ON p.id_posyandu = k.id_posyandu
           JOIN users u ON u.id_user = k.id_user
          WHERE k.id_user = :uid AND u.status_akun = 'aktif'
          LIMIT 1"
    );
    $stmt->execute(['uid' => $_SESSION['user_id']]);
    $kader = $stmt->fetch();

    if (!$kader) {
        http_response_code(403);
        die('Profil kader Anda belum dilengkapi atau akun tidak aktif. Silakan hubungi Admin Posyandu.');
    }

    // Kader yang cuti/nonaktif hanya boleh melihat data, tidak boleh mengubah.
    $kader['can_write'] = ($kader['status_kader'] === 'aktif');
    return $kader;
}

/** Hentikan aksi tulis bila kader tidak berstatus aktif. */
function require_can_write(array $kader, string $redirect): void
{
    if (!$kader['can_write']) {
        flash_set('danger', 'Status kader Anda sedang ' . $kader['status_kader'] . ', sehingga hanya bisa melihat data.');
        redirect($redirect);
    }
}

function redirect(string $url): void
{
    header("Location: $url");
    exit();
}

// ------------------------------------------------------------------------------
// CSRF
// ------------------------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Verifikasi token pada request POST; hentikan bila tidak valid. */
function csrf_verify(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        die('Sesi formulir kedaluwarsa atau tidak valid. Silakan muat ulang halaman dan coba lagi.');
    }
}

// ------------------------------------------------------------------------------
// FLASH MESSAGE (Post-Redirect-Get)
// ------------------------------------------------------------------------------
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

// ------------------------------------------------------------------------------
// FORMAT TANGGAL (Bahasa Indonesia)
// ------------------------------------------------------------------------------
const BULAN_ID = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const HARI_ID  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

function tgl_id(?string $date, bool $withDay = false): string
{
    if (!$date) {
        return '-';
    }
    $ts = strtotime($date);
    if (!$ts) {
        return '-';
    }
    $s = date('j', $ts) . ' ' . substr(BULAN_ID[(int)date('n', $ts)], 0, 3) . ' ' . date('Y', $ts);
    return $withDay ? HARI_ID[(int)date('w', $ts)] . ', ' . $s : $s;
}

function tgl_id_panjang(?string $date): string
{
    if (!$date || !($ts = strtotime($date))) {
        return '-';
    }
    return date('j', $ts) . ' ' . BULAN_ID[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

function jam_hm(?string $time): string
{
    return $time ? substr($time, 0, 5) : '';
}

// ------------------------------------------------------------------------------
// PAGINASI SEDERHANA
// ------------------------------------------------------------------------------
/** Bangun query string dari $_GET tanpa key tertentu, lalu tambahkan override. */
function qs(array $override = [], array $remove = []): string
{
    $params = $_GET;
    foreach ($remove as $r) {
        unset($params[$r]);
    }
    foreach ($override as $k => $v) {
        if ($v === null || $v === '') {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    return $params ? '?' . http_build_query($params) : '?';
}

/** Render navigasi paginasi (kosong bila hanya 1 halaman). */
function render_pagination(int $page, int $perPage, int $total): string
{
    $pages = max(1, (int)ceil($total / $perPage));
    $from  = $total ? ($page - 1) * $perPage + 1 : 0;
    $to    = min($total, $page * $perPage);
    $html  = '<div class="pagination"><div class="pg-info">Menampilkan ' . $from . '–' . $to . ' dari ' . $total . ' data</div>';
    if ($pages > 1) {
        $html .= '<div class="pg-links">';
        if ($page > 1) {
            $html .= '<a href="' . e(qs(['page' => $page - 1])) . '" aria-label="Sebelumnya">‹</a>';
        }
        $start = max(1, $page - 2);
        $end   = min($pages, $page + 2);
        if ($start > 1) {
            $html .= '<a href="' . e(qs(['page' => 1])) . '">1</a>' . ($start > 2 ? '<span class="pg-info">…</span>' : '');
        }
        for ($i = $start; $i <= $end; $i++) {
            $html .= $i === $page
                ? '<span class="pg-cur">' . $i . '</span>'
                : '<a href="' . e(qs(['page' => $i])) . '">' . $i . '</a>';
        }
        if ($end < $pages) {
            $html .= ($end < $pages - 1 ? '<span class="pg-info">…</span>' : '') . '<a href="' . e(qs(['page' => $pages])) . '">' . $pages . '</a>';
        }
        if ($page < $pages) {
            $html .= '<a href="' . e(qs(['page' => $page + 1])) . '" aria-label="Berikutnya">›</a>';
        }
        $html .= '</div>';
    }
    return $html . '</div>';
}
