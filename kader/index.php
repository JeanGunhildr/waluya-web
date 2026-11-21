<?php
// ==============================================================================
// WALUYA - DASHBOARD KADER POSYANDU
// ==============================================================================
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/kesehatan.php';

$kader = require_kader($pdo);
$active_menu = 'dashboard';

$pos       = $kader['id_posyandu'];
$today     = date('Y-m-d');
$awalBulan = date('Y-m-01');

// Potongan SQL: pemeriksaan terakhir tiap lansia
$lastJoin = "LEFT JOIN riwayat_kesehatan lr ON lr.id_riwayat = (
                SELECT r2.id_riwayat FROM riwayat_kesehatan r2 WHERE r2.id_lansia = l.id_lansia
                 ORDER BY r2.tanggal_pemeriksaan DESC, r2.waktu_pemeriksaan DESC, r2.id_riwayat DESC LIMIT 1)";

// --- Statistik ---------------------------------------------------------------
$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS total,
            SUM(lr.tanggal_pemeriksaan >= :awal) AS sudah,
            SUM(lr.status_kondisi IN ('Perlu Perhatian', 'Risiko Tinggi')) AS tindak_lanjut,
            SUM(lr.status_kondisi = 'Risiko Tinggi') AS risiko
       FROM lansia l $lastJoin
      WHERE l.id_posyandu = :p AND l.status_lansia = 'aktif'"
);
$stmt->execute(['awal' => $awalBulan, 'p' => $pos]);
$st = array_map('intval', $stmt->fetch());

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM riwayat_kesehatan rk JOIN lansia l ON l.id_lansia = rk.id_lansia
      WHERE l.id_posyandu = :p AND rk.tanggal_pemeriksaan >= :awal"
);
$stmt->execute(['p' => $pos, 'awal' => $awalBulan]);
$periksa_bulan_ini = (int)$stmt->fetchColumn();

// --- Pemeriksaan terbaru -----------------------------------------------------
$stmt = $pdo->prepare(
    "SELECT rk.id_riwayat, rk.id_lansia, rk.tanggal_pemeriksaan, rk.waktu_pemeriksaan, rk.tensi_sistolik, rk.tensi_diastolik,
            rk.status_kondisi, l.nama_lengkap
       FROM riwayat_kesehatan rk JOIN lansia l ON l.id_lansia = rk.id_lansia
      WHERE l.id_posyandu = :p
      ORDER BY rk.tanggal_pemeriksaan DESC, rk.waktu_pemeriksaan DESC, rk.id_riwayat DESC LIMIT 5"
);
$stmt->execute(['p' => $pos]);
$terbaru = $stmt->fetchAll();

// --- Perlu tindak lanjut (kondisi terakhir bermasalah; risiko tinggi dulu) ----
$stmt = $pdo->prepare(
    "SELECT l.id_lansia, l.nama_lengkap, lr.tanggal_pemeriksaan, lr.status_kondisi, lr.rujukan_puskesmas
       FROM lansia l $lastJoin
      WHERE l.id_posyandu = :p AND l.status_lansia = 'aktif' AND lr.status_kondisi IN ('Perlu Perhatian', 'Risiko Tinggi')
      ORDER BY (lr.status_kondisi = 'Risiko Tinggi') DESC, lr.tanggal_pemeriksaan DESC LIMIT 5"
);
$stmt->execute(['p' => $pos]);
$tindak = $stmt->fetchAll();

// --- Belum diperiksa bulan ini ----------------------------------------------
$stmt = $pdo->prepare(
    "SELECT l.id_lansia, l.nama_lengkap, l.tanggal_lahir, l.jenis_kelamin, lr.tanggal_pemeriksaan
       FROM lansia l $lastJoin
      WHERE l.id_posyandu = :p AND l.status_lansia = 'aktif' AND (lr.id_riwayat IS NULL OR lr.tanggal_pemeriksaan < :awal)
      ORDER BY lr.tanggal_pemeriksaan IS NOT NULL, lr.tanggal_pemeriksaan ASC, l.nama_lengkap LIMIT 5"
);
$stmt->execute(['p' => $pos, 'awal' => $awalBulan]);
$belum = $stmt->fetchAll();

// --- Jadwal mendatang --------------------------------------------------------
$stmt = $pdo->prepare(
    "SELECT * FROM jadwal_posyandu WHERE id_posyandu = :p AND tanggal >= :t AND status = 'terjadwal'
      ORDER BY tanggal ASC, jam_mulai ASC LIMIT 3"
);
$stmt->execute(['p' => $pos, 't' => $today]);
$agenda = $stmt->fetchAll();
$next = $agenda[0] ?? null;

$persen = $st['total'] ? (int)round($st['sudah'] / $st['total'] * 100) : 0;

$page_title   = 'Dashboard Kader';
$topbar_title = 'Dashboard Kader Posyandu';
include '../components/kader-layout-top.php';
?>

<div class="welcome-banner">
    <h1 class="welcome-title">Selamat Datang, <?= e($_SESSION['nama'] ?? 'Kader Posyandu') ?>!</h1>
    <p class="welcome-subtitle">
        Anda berada di <strong>Panel Layanan Kader — <?= e($kader['nama_posyandu']) ?></strong>. Input pemeriksaan fisik lansia, kelola rekam medis berkala, pantau lansia yang perlu tindak lanjut, dan atur jadwal kegiatan posyandu.
    </p>
</div>

<!-- Statistik -->
<div class="stats-grid">
    <a class="stat-card" href="lansia.php">
        <div class="stat-icon"><i data-lucide="users" style="width:22px;height:22px;"></i></div>
        <div><div class="stat-value num"><?= $st['total'] ?></div><div class="stat-label">Lansia binaan aktif</div></div>
    </a>
    <a class="stat-card" href="riwayat.php?bulan=<?= date('Y-m') ?>">
        <div class="stat-icon blue"><i data-lucide="clipboard-check" style="width:22px;height:22px;"></i></div>
        <div><div class="stat-value num"><?= $st['sudah'] ?><span style="font-size:15px;color:var(--text-light);font-weight:700"> / <?= $st['total'] ?></span></div>
            <div class="stat-label">Diperiksa bulan ini (<?= $persen ?>%) · <?= $periksa_bulan_ini ?> data</div></div>
    </a>
    <a class="stat-card" href="riwayat.php?kondisi=<?= urlencode('Perlu Perhatian') ?>">
        <div class="stat-icon amber"><i data-lucide="triangle-alert" style="width:22px;height:22px;"></i></div>
        <div><div class="stat-value num"><?= $st['tindak_lanjut'] ?></div><div class="stat-label">Perlu tindak lanjut<?= $st['risiko'] ? ' · ' . $st['risiko'] . ' risiko tinggi' : '' ?></div></div>
    </a>
    <a class="stat-card" href="jadwal.php">
        <div class="stat-icon red"><i data-lucide="calendar-clock" style="width:22px;height:22px;"></i></div>
        <div>
            <div class="stat-value" style="font-size:20px"><?= $next ? e(tgl_id($next['tanggal'])) : '—' ?></div>
            <div class="stat-label"><?= $next ? e($next['jenis_kegiatan']) . ' · ' . e(jam_hm($next['jam_mulai'])) : 'Belum ada jadwal mendatang' ?></div>
        </div>
    </a>
</div>

<!-- Fitur & Wewenang Kader -->
<h3 class="section-heading"><i data-lucide="clipboard-list" style="width:18px;height:18px;"></i> Fitur & Wewenang Kader</h3>
<div class="features-grid cols-4">
    <a class="feature-box is-link" href="input-pemeriksaan.php" id="input-pemeriksaan">
        <div class="feature-icon-wrapper"><i data-lucide="plus-circle" style="width:22px;height:22px;"></i></div>
        <h4 class="feature-title">1. Input Pemeriksaan Baru</h4>
        <p class="feature-desc">Catat tensi, berat &amp; tinggi badan, gula darah, kolesterol, asam urat, dan keluhan. Status dihitung otomatis.</p>
        <span class="feature-tag">Create Pemeriksaan</span>
    </a>
    <a class="feature-box is-link" href="riwayat.php" id="riwayat-pemeriksaan">
        <div class="feature-icon-wrapper" style="background:#FEF3C7;color:#D97706;"><i data-lucide="file-text" style="width:22px;height:22px;"></i></div>
        <h4 class="feature-title">2. Riwayat Kesehatan Lansia</h4>
        <p class="feature-desc">Lihat riwayat rekam medis, perbarui hasil pemeriksaan, hapus catatan yang salah, dan ekspor ke CSV.</p>
        <span class="feature-tag">CRUD Rekam Medis</span>
    </a>
    <a class="feature-box is-link" href="lansia.php" id="lansia-binaan">
        <div class="feature-icon-wrapper" style="background:#E0F2FE;color:#0284C7;"><i data-lucide="users" style="width:22px;height:22px;"></i></div>
        <h4 class="feature-title">3. Data Warga Lansia</h4>
        <p class="feature-desc">Daftar lansia binaan, kontak keluarga wali, grafik tren kesehatan, dan tombol langsung periksa.</p>
        <span class="feature-tag">Monitoring Lansia</span>
    </a>
    <a class="feature-box is-link" href="jadwal.php" id="jadwal">
        <div class="feature-icon-wrapper" style="background:#FEE2E2;color:#DC2626;"><i data-lucide="calendar" style="width:22px;height:22px;"></i></div>
        <h4 class="feature-title">4. Jadwal Posyandu</h4>
        <p class="feature-desc">Tambah, ubah, dan hapus agenda pemeriksaan rutin, senam, penyuluhan, dan kunjungan rumah.</p>
        <span class="feature-tag">CRUD Agenda</span>
    </a>
</div>

<div class="grid-2" style="margin-bottom:24px">
    <!-- Pemeriksaan terbaru -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i data-lucide="activity" style="width:18px;height:18px;color:var(--primary);"></i> Pemeriksaan Terbaru</h3>
            <a class="card-link" href="riwayat.php">Lihat semua <i data-lucide="arrow-right" style="width:14px;height:14px;"></i></a>
        </div>
        <?php if (!$terbaru): ?>
            <div class="empty-state" style="padding:28px 10px"><i data-lucide="clipboard-x"></i><h4>Belum ada pemeriksaan</h4><p>Mulai dari menu “Input Pemeriksaan”.</p></div>
        <?php else: ?>
            <ul class="mini-list">
                <?php foreach ($terbaru as $r): ?>
                    <li>
                        <div class="ml-main">
                            <a class="ml-title" href="lansia-detail.php?id=<?= (int)$r['id_lansia'] ?>" style="display:block;text-decoration:none;color:inherit"><?= e($r['nama_lengkap']) ?></a>
                            <div class="ml-sub num"><?= e(tgl_id($r['tanggal_pemeriksaan'])) ?>, <?= e(jam_hm($r['waktu_pemeriksaan'])) ?> · Tensi <?= (int)$r['tensi_sistolik'] ?>/<?= (int)$r['tensi_diastolik'] ?></div>
                        </div>
                        <span class="badge badge-sm <?= badge_class($r['status_kondisi']) ?>"><?= e($r['status_kondisi']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Jadwal mendatang -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i data-lucide="calendar-clock" style="width:18px;height:18px;color:var(--primary);"></i> Jadwal Mendatang</h3>
            <a class="card-link" href="jadwal.php">Kelola <i data-lucide="arrow-right" style="width:14px;height:14px;"></i></a>
        </div>
        <?php if (!$agenda): ?>
            <div class="empty-state" style="padding:28px 10px"><i data-lucide="calendar-x"></i><h4>Belum ada jadwal</h4><p>Tambahkan agenda berikutnya di menu Jadwal.</p></div>
        <?php else: ?>
            <ul class="mini-list">
                <?php foreach ($agenda as $j): ?>
                    <li>
                        <div class="agenda-date" style="width:46px"><div class="m"><?= e(substr(BULAN_ID[(int)date('n', strtotime($j['tanggal']))], 0, 3)) ?></div><div class="d" style="font-size:18px"><?= date('j', strtotime($j['tanggal'])) ?></div></div>
                        <div class="ml-main">
                            <div class="ml-title"><?= e($j['judul']) ?></div>
                            <div class="ml-sub"><?= e(jam_hm($j['jam_mulai'])) ?> WIB · <?= e($j['lokasi']) ?></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<div class="grid-2">
    <!-- Perlu tindak lanjut -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i data-lucide="triangle-alert" style="width:18px;height:18px;color:#D97706;"></i> Perlu Tindak Lanjut</h3>
        </div>
        <?php if (!$tindak): ?>
            <div class="empty-state" style="padding:28px 10px"><i data-lucide="heart-handshake"></i><h4>Semua dalam kondisi baik</h4><p>Tidak ada lansia dengan status perlu perhatian pada pemeriksaan terakhir.</p></div>
        <?php else: ?>
            <ul class="mini-list">
                <?php foreach ($tindak as $r): ?>
                    <li>
                        <div class="ml-main">
                            <a class="ml-title" href="lansia-detail.php?id=<?= (int)$r['id_lansia'] ?>" style="display:block;text-decoration:none;color:inherit"><?= e($r['nama_lengkap']) ?></a>
                            <div class="ml-sub">Diperiksa <?= e(tgl_id($r['tanggal_pemeriksaan'])) ?><?= $r['rujukan_puskesmas'] === 'Ya' ? ' · <b style="color:#B91C1C">dirujuk</b>' : '' ?></div>
                        </div>
                        <span class="badge badge-sm <?= badge_class($r['status_kondisi']) ?>"><?= e($r['status_kondisi']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Belum diperiksa bulan ini -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i data-lucide="user-round-search" style="width:18px;height:18px;color:var(--primary);"></i> Belum Diperiksa Bulan Ini</h3>
            <a class="card-link" href="lansia.php?periksa=belum">Semua <i data-lucide="arrow-right" style="width:14px;height:14px;"></i></a>
        </div>
        <?php if (!$belum): ?>
            <div class="empty-state" style="padding:28px 10px"><i data-lucide="circle-check-big"></i><h4>Semua sudah diperiksa</h4><p>Seluruh lansia aktif sudah punya pemeriksaan bulan ini.</p></div>
        <?php else: ?>
            <ul class="mini-list">
                <?php foreach ($belum as $r): ?>
                    <li>
                        <div class="ml-main">
                            <div class="ml-title"><?= e($r['nama_lengkap']) ?></div>
                            <div class="ml-sub"><?= hitung_usia($r['tanggal_lahir']) ?> th · <?= $r['tanggal_pemeriksaan'] ? 'terakhir ' . e(tgl_id($r['tanggal_pemeriksaan'])) : 'belum pernah diperiksa' ?></div>
                        </div>
                        <?php if ($kader['can_write']): ?><a class="btn btn-primary btn-sm" href="input-pemeriksaan.php?id_lansia=<?= (int)$r['id_lansia'] ?>">Periksa</a><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php include '../components/kader-layout-bottom.php'; ?>
