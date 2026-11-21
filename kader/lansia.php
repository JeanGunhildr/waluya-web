<?php
// ==============================================================================
// WALUYA - DATA WARGA LANSIA BINAAN (KADER)
// Kader hanya MELIHAT data lansia (pengelolaan master data lansia ada di Admin)
// dan dapat langsung memulai pemeriksaan dari daftar ini.
// ==============================================================================
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/kesehatan.php';

$kader = require_kader($pdo);
$active_menu = 'lansia';

// ------------------------------------------------------------------------------
// FILTER
// ------------------------------------------------------------------------------
$q       = trim((string)($_GET['q'] ?? ''));
$status  = (string)($_GET['status'] ?? 'aktif');
$periksa = (string)($_GET['periksa'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

if (!in_array($status, ['aktif', 'pindah', 'meninggal', 'semua'], true)) $status = 'aktif';
if (!in_array($periksa, ['belum', 'sudah'], true)) $periksa = '';

$awalBulan = date('Y-m-01');
$where  = ['l.id_posyandu = :p'];
$params = ['p' => $kader['id_posyandu']];

if ($status !== 'semua') {
    $where[] = 'l.status_lansia = :st';
    $params['st'] = $status;
}
if ($q !== '') {
    $where[] = '(l.nama_lengkap LIKE :q OR l.nik LIKE :q2 OR l.alamat LIKE :q3)';
    $params['q'] = $params['q2'] = $params['q3'] = "%$q%";
}
if ($periksa === 'belum') {
    $where[] = '(lr.id_riwayat IS NULL OR lr.tanggal_pemeriksaan < :awal)';
    $params['awal'] = $awalBulan;
} elseif ($periksa === 'sudah') {
    $where[] = 'lr.tanggal_pemeriksaan >= :awal';
    $params['awal'] = $awalBulan;
}
$whereSql = implode(' AND ', $where);

$from = "FROM lansia l
         LEFT JOIN keluarga kel ON kel.id_keluarga = l.id_keluarga
         LEFT JOIN users uw ON uw.id_user = kel.id_user
         LEFT JOIN riwayat_kesehatan lr ON lr.id_riwayat = (
              SELECT r2.id_riwayat FROM riwayat_kesehatan r2 WHERE r2.id_lansia = l.id_lansia
               ORDER BY r2.tanggal_pemeriksaan DESC, r2.waktu_pemeriksaan DESC, r2.id_riwayat DESC LIMIT 1)";

$stmt = $pdo->prepare("SELECT COUNT(*) $from WHERE $whereSql");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$page = min($page, max(1, (int)ceil($total / $perPage)));
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT l.*, uw.nama_lengkap AS nama_wali, uw.no_hp AS hp_wali, kel.kontak_darurat,
            lr.tanggal_pemeriksaan AS last_tgl, lr.status_kondisi AS last_kondisi
     $from WHERE $whereSql ORDER BY l.nama_lengkap LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Ringkasan kecil untuk header
$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS aktif,
            SUM(CASE WHEN NOT EXISTS (SELECT 1 FROM riwayat_kesehatan r WHERE r.id_lansia = l.id_lansia AND r.tanggal_pemeriksaan >= :awal) THEN 1 ELSE 0 END) AS belum
       FROM lansia l WHERE l.id_posyandu = :p AND l.status_lansia = 'aktif'"
);
$stmt->execute(['p' => $kader['id_posyandu'], 'awal' => $awalBulan]);
$ringkas = $stmt->fetch();

/** Normalisasi nomor HP Indonesia ke format wa.me (62xxxxxxxx). */
function wa_link(?string $hp): ?string
{
    $d = preg_replace('/\D+/', '', (string)$hp);
    if ($d === '') return null;
    if ($d[0] === '0') $d = '62' . substr($d, 1);
    return 'https://wa.me/' . $d;
}

$has_filter = ($q !== '' || $periksa !== '' || $status !== 'aktif');
$page_title   = 'Warga Lansia Binaan';
$topbar_title = 'Warga Lansia Binaan';
include '../components/kader-layout-top.php';
?>

<div class="welcome-banner">
    <h1 class="welcome-title">Data Warga Lanjut Usia Binaan Posyandu</h1>
    <p class="welcome-subtitle">
        Daftar warga lansia di wilayah <strong><?= e($kader['nama_posyandu']) ?></strong> beserta usia, alamat, riwayat penyakit bawaan, kontak keluarga penanggung jawab, dan pemeriksaan terakhir.
    </p>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i data-lucide="users" style="width:18px;height:18px;color:var(--primary);"></i>
            Daftar Warga Lansia Wilayah Posyandu
        </h3>
        <span class="badge badge-muted"><?= (int)$ringkas['aktif'] ?> aktif · <?= (int)$ringkas['belum'] ?> belum diperiksa bulan ini</span>
    </div>

    <form method="get" class="toolbar">
        <div class="search-box">
            <i data-lucide="search"></i>
            <input type="search" name="q" class="form-input" placeholder="Cari nama, NIK, atau alamat…" value="<?= e($q) ?>">
        </div>
        <select name="periksa" class="form-select" style="width:auto" aria-label="Filter pemeriksaan bulan ini">
            <option value="">Semua status periksa</option>
            <option value="belum" <?= $periksa === 'belum' ? 'selected' : '' ?>>Belum diperiksa bulan ini</option>
            <option value="sudah" <?= $periksa === 'sudah' ? 'selected' : '' ?>>Sudah diperiksa bulan ini</option>
        </select>
        <select name="status" class="form-select" style="width:auto" aria-label="Filter status lansia">
            <option value="aktif" <?= $status === 'aktif' ? 'selected' : '' ?>>Status: Aktif</option>
            <option value="pindah" <?= $status === 'pindah' ? 'selected' : '' ?>>Pindah</option>
            <option value="meninggal" <?= $status === 'meninggal' ? 'selected' : '' ?>>Meninggal</option>
            <option value="semua" <?= $status === 'semua' ? 'selected' : '' ?>>Semua status</option>
        </select>
        <button class="btn btn-secondary btn-sm" type="submit"><i data-lucide="filter" style="width:14px;height:14px;"></i> Terapkan</button>
        <?php if ($has_filter): ?><a href="lansia.php" class="btn btn-ghost btn-sm">Reset</a><?php endif; ?>
    </form>

    <?php if (!$rows): ?>
        <div class="empty-state">
            <i data-lucide="user-x"></i>
            <h4>Tidak ada data lansia</h4>
            <p><?= $has_filter ? 'Coba ubah atau reset filter pencarian.' : 'Admin belum menambahkan lansia untuk posyandu ini.' ?></p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table table-responsive">
                <thead>
                    <tr>
                        <th>Lansia</th>
                        <th>Usia / Alamat</th>
                        <th>Riwayat Penyakit</th>
                        <th>Keluarga Wali</th>
                        <th>Pemeriksaan Terakhir</th>
                        <th class="col-actions">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r):
                    $hp  = $r['hp_wali'] ?: $r['kontak_darurat'];
                    $wa  = wa_link($hp);
                    $sudahBulanIni = $r['last_tgl'] && $r['last_tgl'] >= $awalBulan;
                ?>
                    <tr>
                        <td data-label="Lansia">
                            <a href="lansia-detail.php?id=<?= (int)$r['id_lansia'] ?>" class="cell-main" style="text-decoration:none;color:inherit"><?= e($r['nama_lengkap']) ?></a>
                            <div class="cell-sub num">NIK <?= e($r['nik']) ?></div>
                        </td>
                        <td data-label="Usia / Alamat">
                            <div class="cell-main"><?= hitung_usia($r['tanggal_lahir']) ?> th · <?= $r['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></div>
                            <div class="cell-sub">RT <?= e($r['rt']) ?>/RW <?= e($r['rw']) ?> · <?= e($r['alamat']) ?></div>
                        </td>
                        <td data-label="Penyakit">
                            <?php if ($r['riwayat_penyakit']): ?>
                                <span style="font-size:12.5px"><?= e($r['riwayat_penyakit']) ?></span>
                            <?php else: ?><span class="cell-sub">—</span><?php endif; ?>
                        </td>
                        <td data-label="Keluarga Wali">
                            <?php if ($r['nama_wali'] || $hp): ?>
                                <div class="cell-main" style="font-size:12.5px"><?= e($r['nama_wali'] ?: 'Keluarga') ?><?= $r['hubungan_keluarga'] ? ' <span class="cell-sub" style="display:inline">(' . e($r['hubungan_keluarga']) . ')</span>' : '' ?></div>
                                <div class="cell-sub num">
                                    <?= e($hp) ?>
                                    <?php if ($wa): ?> · <a href="<?= e($wa) ?>" target="_blank" rel="noopener noreferrer" style="color:var(--primary-dark);font-weight:700">WhatsApp</a><?php endif; ?>
                                </div>
                            <?php else: ?><span class="cell-sub">Belum terhubung</span><?php endif; ?>
                        </td>
                        <td data-label="Terakhir diperiksa">
                            <?php if ($r['last_tgl']): ?>
                                <div class="cell-main num" style="font-size:12.5px"><?= e(tgl_id($r['last_tgl'])) ?></div>
                                <span class="badge badge-sm <?= badge_class($r['last_kondisi']) ?>"><?= e($r['last_kondisi']) ?></span>
                            <?php else: ?>
                                <span class="badge badge-sm badge-muted">Belum pernah</span>
                            <?php endif; ?>
                            <?php if ($r['status_lansia'] === 'aktif' && !$sudahBulanIni): ?>
                                <div class="cell-sub" style="color:#B45309;font-weight:700">Belum bulan ini</div>
                            <?php endif; ?>
                        </td>
                        <td class="col-actions">
                            <a href="lansia-detail.php?id=<?= (int)$r['id_lansia'] ?>" class="btn btn-secondary btn-sm" title="Lihat detail & tren"><i data-lucide="eye" style="width:14px;height:14px;"></i> Detail</a>
                            <?php if ($r['status_lansia'] === 'aktif' && $kader['can_write']): ?>
                                <a href="input-pemeriksaan.php?id_lansia=<?= (int)$r['id_lansia'] ?>" class="btn btn-primary btn-sm"><i data-lucide="stethoscope" style="width:14px;height:14px;"></i> Periksa</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= render_pagination($page, $perPage, $total) ?>
    <?php endif; ?>
</div>

<?php include '../components/kader-layout-bottom.php'; ?>
