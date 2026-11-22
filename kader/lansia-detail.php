<?php
// ==============================================================================
// WALUYA - DETAIL LANSIA BINAAN + TREN KESEHATAN (KADER)
// ==============================================================================
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/kesehatan.php';

$kader = require_kader($pdo);
$active_menu = 'lansia';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    "SELECT l.*, uw.nama_lengkap AS nama_wali, uw.no_hp AS hp_wali, kel.kontak_darurat
       FROM lansia l
       LEFT JOIN keluarga kel ON kel.id_keluarga = l.id_keluarga
       LEFT JOIN users uw ON uw.id_user = kel.id_user
      WHERE l.id_lansia = :id AND l.id_posyandu = :p"
);
$stmt->execute(['id' => $id, 'p' => $kader['id_posyandu']]);
$l = $stmt->fetch();
if (!$l) {
    flash_set('danger', 'Data lansia tidak ditemukan atau bukan dari posyandu Anda.');
    redirect('lansia.php');
}

// 12 pemeriksaan terakhir untuk grafik (urut lama -> baru) & 15 terakhir untuk tabel
$stmt = $pdo->prepare("SELECT * FROM riwayat_kesehatan WHERE id_lansia = :id ORDER BY tanggal_pemeriksaan DESC, waktu_pemeriksaan DESC, id_riwayat DESC LIMIT 15");
$stmt->execute(['id' => $id]);
$riwayat = $stmt->fetchAll();
$seri = array_reverse(array_slice($riwayat, 0, 12));
$last = $riwayat[0] ?? null;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM riwayat_kesehatan WHERE id_lansia = :id");
$stmt->execute(['id' => $id]);
$total_periksa = (int)$stmt->fetchColumn();

/** Sparkline SVG sederhana dari deret angka (null dilewati). */
function sparkline(array $vals, string $color = '#10B981'): string
{
    $pts = array_values(array_filter($vals, fn($v) => $v !== null));
    if (count($pts) < 2) {
        return '<div class="cell-sub" style="height:56px;display:flex;align-items:center">Butuh minimal 2 data untuk grafik.</div>';
    }
    $w = 200; $h = 56; $pad = 6;
    $min = min($pts); $max = max($pts);
    $range = ($max - $min) ?: 1;
    $n = count($pts);
    $coords = [];
    foreach ($pts as $i => $v) {
        $x = $pad + ($w - 2 * $pad) * ($i / ($n - 1));
        $y = $h - $pad - ($h - 2 * $pad) * (($v - $min) / $range);
        $coords[] = round($x, 1) . ',' . round($y, 1);
    }
    $lastXY = explode(',', end($coords));
    return '<svg viewBox="0 0 ' . $w . ' ' . $h . '" preserveAspectRatio="none" role="img" aria-label="Grafik tren">'
        . '<polyline fill="none" stroke="' . $color . '" stroke-width="2.2" stroke-linejoin="round" stroke-linecap="round" points="' . implode(' ', $coords) . '"/>'
        . '<circle cx="' . $lastXY[0] . '" cy="' . $lastXY[1] . '" r="3.5" fill="' . $color . '"/></svg>';
}

$trend = [
    ['Tensi sistolik', 'mmHg', array_map(fn($r) => (int)$r['tensi_sistolik'], $seri), '#EF4444', $last ? $last['tensi_sistolik'] . '/' . $last['tensi_diastolik'] : null],
    ['Berat badan', 'kg', array_map(fn($r) => (float)$r['berat_badan'], $seri), '#0284C7', $last ? $last['berat_badan'] + 0 : null],
    ['Gula darah', 'mg/dL', array_map(fn($r) => $r['gula_darah'] !== null ? (int)$r['gula_darah'] : null, $seri), '#D97706', $last ? $last['gula_darah'] : null],
    ['Kolesterol', 'mg/dL', array_map(fn($r) => $r['kolesterol'] !== null ? (int)$r['kolesterol'] : null, $seri), '#7C3AED', $last ? $last['kolesterol'] : null],
];

$hp = $l['hp_wali'] ?: $l['kontak_darurat'];
$page_title   = 'Detail ' . $l['nama_lengkap'];
$topbar_title = 'Detail Lansia Binaan';
include '../components/kader-layout-top.php';
?>

<div class="welcome-banner" style="display:flex;flex-wrap:wrap;gap:16px;justify-content:space-between;align-items:center">
    <div class="profile-head">
        <div class="avatar-lg <?= $l['jenis_kelamin'] === 'P' ? 'p' : '' ?>"><?= e(strtoupper(mb_substr($l['nama_lengkap'], 0, 1))) ?></div>
        <div>
            <h1 class="welcome-title" style="margin-bottom:4px"><?= e($l['nama_lengkap']) ?></h1>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                <span class="badge badge-muted"><?= hitung_usia($l['tanggal_lahir']) ?> tahun · <?= $l['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></span>
                <span class="badge <?= badge_class($l['status_lansia']) ?>"><?= e(ucfirst($l['status_lansia'])) ?></span>
                <?php if ($last): ?><span class="badge <?= badge_class($last['status_kondisi']) ?>"><?= e($last['status_kondisi']) ?></span><?php endif; ?>
            </div>
        </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="lansia.php" class="btn btn-secondary"><i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Kembali</a>
        <?php if ($l['status_lansia'] === 'aktif' && $kader['can_write']): ?>
            <a href="input-pemeriksaan.php?id_lansia=<?= (int)$l['id_lansia'] ?>" class="btn btn-primary"><i data-lucide="stethoscope" style="width:14px;height:14px;"></i> Periksa Lansia Ini</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title"><i data-lucide="id-card" style="width:18px;height:18px;color:var(--primary);"></i> Biodata & Kontak</h3></div>
    <div class="detail-grid">
        <div class="detail-item"><div class="dl">NIK</div><div class="dv num"><?= e($l['nik']) ?></div></div>
        <div class="detail-item"><div class="dl">Tempat, tgl lahir</div><div class="dv"><?= e($l['tempat_lahir']) ?>, <?= e(tgl_id_panjang($l['tanggal_lahir'])) ?></div></div>
        <div class="detail-item"><div class="dl">Golongan darah</div><div class="dv"><?= e($l['golongan_darah']) ?></div></div>
        <div class="detail-item"><div class="dl">No. HP lansia</div><div class="dv num"><?= e($l['no_hp'] ?: '—') ?></div></div>
        <div class="detail-item full"><div class="dl">Alamat</div><div class="dv"><?= e($l['alamat']) ?>, RT <?= e($l['rt']) ?>/RW <?= e($l['rw']) ?></div></div>
        <div class="detail-item"><div class="dl">Keluarga penanggung jawab</div><div class="dv"><?= e($l['nama_wali'] ?: '—') ?><?= $l['hubungan_keluarga'] ? ' (' . e($l['hubungan_keluarga']) . ')' : '' ?></div></div>
        <div class="detail-item"><div class="dl">Kontak keluarga</div><div class="dv num"><?= e($hp ?: '—') ?></div></div>
        <div class="detail-item full"><div class="dl">Riwayat penyakit</div><div class="dv"><?= e($l['riwayat_penyakit'] ?: '—') ?></div></div>
        <div class="detail-item full"><div class="dl">Riwayat alergi</div><div class="dv"><?= e($l['riwayat_alergi'] ?: '—') ?></div></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i data-lucide="trending-up" style="width:18px;height:18px;color:var(--primary);"></i> Tren Kesehatan</h3>
        <span class="badge badge-muted"><?= $total_periksa ?> pemeriksaan tercatat</span>
    </div>
    <?php if (!$last): ?>
        <div class="empty-state"><i data-lucide="activity"></i><h4>Belum ada pemeriksaan</h4><p>Grafik akan muncul setelah lansia ini diperiksa.</p></div>
    <?php else: ?>
        <div class="trend-grid">
            <?php foreach ($trend as [$judul, $unit, $vals, $warna, $akhir]): ?>
                <div class="trend-card">
                    <div class="tc-title"><?= e($judul) ?></div>
                    <div class="tc-last"><?= $akhir !== null ? e($akhir) : '—' ?> <small><?= $akhir !== null ? e($unit) : '' ?></small></div>
                    <?= sparkline($vals, $warna) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i data-lucide="clipboard-list" style="width:18px;height:18px;color:var(--primary);"></i> Pemeriksaan Terakhir</h3>
        <a class="card-link" href="riwayat.php?id_lansia=<?= (int)$l['id_lansia'] ?>">Semua riwayat <i data-lucide="arrow-right" style="width:14px;height:14px;"></i></a>
    </div>
    <?php if ($riwayat): ?>
        <div class="table-wrap">
            <table class="data-table table-responsive">
                <thead><tr><th>Tanggal</th><th>Tensi</th><th>BB / IMT</th><th>Gula / Kol / AU</th><th>Kondisi</th><th class="col-actions">Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($riwayat as $r): ?>
                    <tr>
                        <td data-label="Tanggal"><span class="cell-main num"><?= e(tgl_id($r['tanggal_pemeriksaan'])) ?></span></td>
                        <td data-label="Tensi" class="num"><?= (int)$r['tensi_sistolik'] ?>/<?= (int)$r['tensi_diastolik'] ?> <span class="badge badge-sm <?= badge_class($r['status_tensi']) ?>"><?= e($r['status_tensi']) ?></span></td>
                        <td data-label="BB / IMT" class="num"><?= e($r['berat_badan'] + 0) ?> kg / <?= e($r['imt'] + 0) ?></td>
                        <td data-label="Gula / Kol / AU" class="num"><?= $r['gula_darah'] ?? '–' ?> / <?= $r['kolesterol'] ?? '–' ?> / <?= $r['asam_urat'] !== null ? e($r['asam_urat'] + 0) : '–' ?></td>
                        <td data-label="Kondisi"><span class="badge badge-sm <?= badge_class($r['status_kondisi']) ?>"><?= e($r['status_kondisi']) ?></span></td>
                        <td class="col-actions">
                            <?php if ($kader['can_write']): ?><a href="input-pemeriksaan.php?edit=<?= (int)$r['id_riwayat'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Ubah" aria-label="Ubah"><i data-lucide="pencil" style="width:15px;height:15px;"></i></a><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state"><i data-lucide="clipboard-x"></i><h4>Belum ada data</h4></div>
    <?php endif; ?>
</div>

<?php include '../components/kader-layout-bottom.php'; ?>
