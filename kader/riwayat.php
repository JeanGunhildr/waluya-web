<?php
// ==============================================================================
// WALUYA - RIWAYAT REKAM MEDIS LANSIA (KADER - CRUD)
//  Read   : daftar + filter + detail   | Create/Update : input-pemeriksaan.php
//  Delete : aksi POST di halaman ini   | Export       : ?export=csv
// ==============================================================================
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/kesehatan.php';

$kader = require_kader($pdo);
$active_menu = 'riwayat';

// ------------------------------------------------------------------------------
// DELETE (POST)
// ------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_verify();
    require_can_write($kader, 'riwayat.php');

    $id = (int)($_POST['id_riwayat'] ?? 0);
    $stmt = $pdo->prepare(
        "DELETE FROM riwayat_kesehatan
          WHERE id_riwayat = :id
            AND id_lansia IN (SELECT id_lansia FROM lansia WHERE id_posyandu = :p)"
    );
    $stmt->execute(['id' => $id, 'p' => $kader['id_posyandu']]);
    flash_set($stmt->rowCount() ? 'success' : 'danger', $stmt->rowCount() ? 'Data pemeriksaan berhasil dihapus.' : 'Data tidak ditemukan atau bukan dari posyandu Anda.');
    redirect('riwayat.php' . (!empty($_POST['back']) && is_string($_POST['back']) && $_POST['back'][0] === '?' ? $_POST['back'] : ''));
}

// ------------------------------------------------------------------------------
// FILTER
// ------------------------------------------------------------------------------
$q        = trim((string)($_GET['q'] ?? ''));
$kondisi  = (string)($_GET['kondisi'] ?? '');
$bulan    = (string)($_GET['bulan'] ?? '');
$id_lansia = (int)($_GET['id_lansia'] ?? 0);
$rujukan  = (string)($_GET['rujukan'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 10;

$where  = ['l.id_posyandu = :p'];
$params = ['p' => $kader['id_posyandu']];
if ($q !== '') {
    $where[] = '(l.nama_lengkap LIKE :q OR l.nik LIKE :q2)';
    $params['q']  = "%$q%";
    $params['q2'] = "%$q%";
}
if (in_array($kondisi, STATUS_KONDISI_LIST, true)) {
    $where[] = 'rk.status_kondisi = :kondisi';
    $params['kondisi'] = $kondisi;
} else {
    $kondisi = '';
}
if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan)) {
    $where[] = "DATE_FORMAT(rk.tanggal_pemeriksaan, '%Y-%m') = :bulan";
    $params['bulan'] = $bulan;
} else {
    $bulan = '';
}
if ($id_lansia > 0) {
    $where[] = 'rk.id_lansia = :idl';
    $params['idl'] = $id_lansia;
}
if ($rujukan === 'Ya') {
    $where[] = "rk.rujukan_puskesmas = 'Ya'";
} else {
    $rujukan = '';
}
$whereSql = implode(' AND ', $where);

$baseFrom = "FROM riwayat_kesehatan rk
             JOIN lansia l ON l.id_lansia = rk.id_lansia
             LEFT JOIN kader kd ON kd.id_kader = rk.id_kader
             LEFT JOIN users uk ON uk.id_user = kd.id_user";
$selectCols = "rk.*, l.nama_lengkap AS nama_lansia, l.nik AS nik_lansia, l.jenis_kelamin, l.tanggal_lahir,
               uk.nama_lengkap AS nama_kader";

// ------------------------------------------------------------------------------
// EXPORT CSV (semua hasil filter)
// ------------------------------------------------------------------------------
if (($_GET['export'] ?? '') === 'csv') {
    $stmt = $pdo->prepare("SELECT $selectCols $baseFrom WHERE $whereSql ORDER BY rk.tanggal_pemeriksaan DESC, rk.waktu_pemeriksaan DESC");
    $stmt->execute($params);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="riwayat-pemeriksaan-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
    fputcsv($out, ['Tanggal', 'Waktu', 'Nama Lansia', 'NIK', 'Usia', 'JK', 'BB (kg)', 'TB (cm)', 'IMT', 'Status IMT',
        'Sistolik', 'Diastolik', 'Status Tensi', 'Nadi (bpm)', 'Suhu (C)', 'Gula Darah', 'Status Gula', 'Kolesterol',
        'Status Kolesterol', 'Asam Urat', 'Status Asam Urat', 'Keluhan', 'Catatan Kader', 'Status Kondisi', 'Rujukan', 'Kader Pemeriksa']);
    // Cegah CSV/formula injection pada kolom teks bebas.
    $safe = function ($s) {
        $s = (string)$s;
        return ($s !== '' && strpos("=+-@\t\r", $s[0]) !== false) ? "'" . $s : $s;
    };
    while ($r = $stmt->fetch()) {
        fputcsv($out, [
            $r['tanggal_pemeriksaan'], jam_hm($r['waktu_pemeriksaan']), $safe($r['nama_lansia']), "'" . $r['nik_lansia'],
            hitung_usia($r['tanggal_lahir']), $r['jenis_kelamin'], $r['berat_badan'], $r['tinggi_badan'], $r['imt'], $r['status_imt'],
            $r['tensi_sistolik'], $r['tensi_diastolik'], $r['status_tensi'], $r['detak_jantung'], $r['suhu_tubuh'],
            $r['gula_darah'], $r['status_gula_darah'], $r['kolesterol'], $r['status_kolesterol'], $r['asam_urat'], $r['status_asam_urat'],
            $safe($r['keluhan']), $safe($r['catatan_kader']), $r['status_kondisi'], $r['rujukan_puskesmas'], $safe($r['nama_kader']),
        ]);
    }
    fclose($out);
    exit();
}

// ------------------------------------------------------------------------------
// DATA TABEL
// ------------------------------------------------------------------------------
$stmt = $pdo->prepare("SELECT COUNT(*) $baseFrom WHERE $whereSql");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$maxPage = max(1, (int)ceil($total / $perPage));
$page = min($page, $maxPage);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT $selectCols $baseFrom WHERE $whereSql
                       ORDER BY rk.tanggal_pemeriksaan DESC, rk.waktu_pemeriksaan DESC, rk.id_riwayat DESC
                       LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT id_lansia, nama_lengkap FROM lansia WHERE id_posyandu = :p ORDER BY nama_lengkap");
$stmt->execute(['p' => $kader['id_posyandu']]);
$opsi_lansia = $stmt->fetchAll();

$has_filter = ($q !== '' || $kondisi !== '' || $bulan !== '' || $id_lansia > 0 || $rujukan !== '');
$qsRaw = $_SERVER['QUERY_STRING'] ?? '';
$back = $qsRaw !== '' ? '?' . $qsRaw : '';

$page_title   = 'Riwayat Rekam Medis';
$topbar_title = 'Riwayat Rekam Medis';
include '../components/kader-layout-top.php';
?>

<div class="welcome-banner">
    <h1 class="welcome-title">Daftar Riwayat Rekam Medis Lansia</h1>
    <p class="welcome-subtitle">
        Seluruh hasil pemeriksaan lansia binaan <strong><?= e($kader['nama_posyandu']) ?></strong>. Kader dapat menambah catatan baru, melihat detail, mengubah hasil, dan menghapus rekaman yang salah input.
    </p>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i data-lucide="clipboard-list" style="width:18px;height:18px;color:var(--primary);"></i>
            Tabel Riwayat Pemeriksaan Kesehatan
        </h3>
        <a href="input-pemeriksaan.php" class="btn btn-primary btn-sm <?= $kader['can_write'] ? '' : 'is-disabled' ?>">
            <i data-lucide="plus" style="width:14px;height:14px;"></i> Pemeriksaan Baru
        </a>
    </div>

    <form method="get" class="toolbar">
        <div class="search-box">
            <i data-lucide="search"></i>
            <input type="search" name="q" class="form-input" placeholder="Cari nama atau NIK lansia…" value="<?= e($q) ?>">
        </div>
        <select name="id_lansia" class="form-select" style="width:auto;max-width:200px" aria-label="Filter lansia">
            <option value="">Semua lansia</option>
            <?php foreach ($opsi_lansia as $o): ?>
                <option value="<?= (int)$o['id_lansia'] ?>" <?= $id_lansia === (int)$o['id_lansia'] ? 'selected' : '' ?>><?= e($o['nama_lengkap']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="kondisi" class="form-select" style="width:auto" aria-label="Filter kondisi">
            <option value="">Semua kondisi</option>
            <?php foreach (STATUS_KONDISI_LIST as $s): ?>
                <option value="<?= e($s) ?>" <?= $kondisi === $s ? 'selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="month" name="bulan" class="form-input" style="width:auto" value="<?= e($bulan) ?>" aria-label="Filter bulan">
        <label style="display:inline-flex;gap:6px;align-items:center;font-size:12.5px;font-weight:700;color:var(--text-secondary);white-space:nowrap">
            <input type="checkbox" name="rujukan" value="Ya" <?= $rujukan === 'Ya' ? 'checked' : '' ?>> Dirujuk saja
        </label>
        <button class="btn btn-secondary btn-sm" type="submit"><i data-lucide="filter" style="width:14px;height:14px;"></i> Terapkan</button>
        <?php if ($has_filter): ?><a href="riwayat.php" class="btn btn-ghost btn-sm">Reset</a><?php endif; ?>
        <span class="toolbar-spacer"></span>
        <a href="<?= e(qs(['export' => 'csv'], ['page'])) ?>" class="btn btn-secondary btn-sm"><i data-lucide="download" style="width:14px;height:14px;"></i> Ekspor CSV</a>
    </form>

    <?php if (!$rows): ?>
        <div class="empty-state">
            <i data-lucide="clipboard-x"></i>
            <h4><?= $has_filter ? 'Tidak ada data yang cocok' : 'Belum ada riwayat pemeriksaan' ?></h4>
            <p><?= $has_filter ? 'Coba ubah atau reset filter pencarian.' : 'Mulai dengan menambahkan pemeriksaan pertama.' ?></p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table table-responsive">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Lansia</th>
                        <th>Hasil Pengukuran</th>
                        <th>Status</th>
                        <th>Pemeriksa</th>
                        <th class="col-actions">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r):
                    $detail = [
                        'id' => (int)$r['id_riwayat'],
                        'nama' => $r['nama_lansia'], 'nik' => $r['nik_lansia'],
                        'usia' => hitung_usia($r['tanggal_lahir']), 'jk' => $r['jenis_kelamin'],
                        'tgl' => tgl_id_panjang($r['tanggal_pemeriksaan']), 'jam' => jam_hm($r['waktu_pemeriksaan']),
                        'bb' => $r['berat_badan'] + 0, 'tb' => $r['tinggi_badan'] + 0, 'imt' => $r['imt'] + 0, 's_imt' => $r['status_imt'],
                        'sis' => $r['tensi_sistolik'], 'dia' => $r['tensi_diastolik'], 's_tensi' => $r['status_tensi'],
                        'nadi' => $r['detak_jantung'], 'suhu' => $r['suhu_tubuh'] + 0,
                        'gula' => $r['gula_darah'], 's_gula' => $r['status_gula_darah'],
                        'kol' => $r['kolesterol'], 's_kol' => $r['status_kolesterol'],
                        'au' => $r['asam_urat'] !== null ? $r['asam_urat'] + 0 : null, 's_au' => $r['status_asam_urat'],
                        'keluhan' => $r['keluhan'], 'catatan' => $r['catatan_kader'],
                        'kondisi' => $r['status_kondisi'], 'rujuk' => $r['rujukan_puskesmas'], 'kader' => $r['nama_kader'],
                        'badge' => [
                            'imt' => badge_class($r['status_imt']), 'tensi' => badge_class($r['status_tensi']),
                            'gula' => badge_class($r['status_gula_darah']), 'kol' => badge_class($r['status_kolesterol']),
                            'au' => badge_class($r['status_asam_urat']), 'kondisi' => badge_class($r['status_kondisi']),
                        ],
                    ];
                ?>
                    <tr>
                        <td data-label="Tanggal">
                            <div class="cell-main num nowrap"><?= e(tgl_id($r['tanggal_pemeriksaan'])) ?></div>
                            <div class="cell-sub"><?= e(jam_hm($r['waktu_pemeriksaan'])) ?> WIB</div>
                        </td>
                        <td data-label="Lansia">
                            <a href="lansia-detail.php?id=<?= (int)$r['id_lansia'] ?>" class="cell-main" style="text-decoration:none;color:inherit"><?= e($r['nama_lansia']) ?></a>
                            <div class="cell-sub"><?= hitung_usia($r['tanggal_lahir']) ?> th · <?= $r['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></div>
                        </td>
                        <td data-label="Hasil">
                            <div class="vital-line num">
                                <span>Tensi <b><?= (int)$r['tensi_sistolik'] ?>/<?= (int)$r['tensi_diastolik'] ?></b></span>
                                <span>BB <b><?= e($r['berat_badan'] + 0) ?></b> kg</span>
                                <span>IMT <b><?= e($r['imt'] + 0) ?></b></span>
                                <?php if ($r['gula_darah'] !== null): ?><span>GD <b><?= (int)$r['gula_darah'] ?></b></span><?php endif; ?>
                                <?php if ($r['kolesterol'] !== null): ?><span>Kol <b><?= (int)$r['kolesterol'] ?></b></span><?php endif; ?>
                                <?php if ($r['asam_urat'] !== null): ?><span>AU <b><?= e($r['asam_urat'] + 0) ?></b></span><?php endif; ?>
                            </div>
                        </td>
                        <td data-label="Status">
                            <span class="badge <?= badge_class($r['status_kondisi']) ?>"><?= e($r['status_kondisi']) ?></span>
                            <?php if ($r['rujukan_puskesmas'] === 'Ya'): ?>
                                <div class="cell-sub" style="color:#B91C1C;font-weight:700">Dirujuk ke Puskesmas</div>
                            <?php endif; ?>
                        </td>
                        <td data-label="Pemeriksa"><span class="cell-sub" style="font-size:12.5px"><?= e($r['nama_kader'] ?: '—') ?></span></td>
                        <td class="col-actions">
                            <button type="button" class="btn btn-secondary btn-sm btn-icon" title="Lihat detail" aria-label="Lihat detail"
                                    data-detail="<?= e(json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>"
                                    onclick="showDetail(this)"><i data-lucide="eye" style="width:15px;height:15px;"></i></button>
                            <?php if ($kader['can_write']): ?>
                                <a href="input-pemeriksaan.php?edit=<?= (int)$r['id_riwayat'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Ubah" aria-label="Ubah"><i data-lucide="pencil" style="width:15px;height:15px;"></i></a>
                                <button type="button" class="btn btn-outline-danger btn-sm btn-icon" title="Hapus" aria-label="Hapus"
                                        data-id="<?= (int)$r['id_riwayat'] ?>" data-nama="<?= e($r['nama_lansia']) ?>" data-tgl="<?= e(tgl_id_panjang($r['tanggal_pemeriksaan'])) ?>"
                                        onclick="askDelete(this)"><i data-lucide="trash-2" style="width:15px;height:15px;"></i></button>
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

<!-- MODAL DETAIL -->
<div class="modal-overlay" id="modalDetail" role="dialog" aria-modal="true" aria-labelledby="mdTitle">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 class="modal-title" id="mdTitle">Detail Pemeriksaan</h3>
            <button type="button" class="modal-x" onclick="closeModal('modalDetail')" aria-label="Tutup"><i data-lucide="x" style="width:18px;height:18px;"></i></button>
        </div>
        <div class="modal-body" id="mdBody"></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('modalDetail')">Tutup</button>
            <?php if ($kader['can_write']): ?><a href="#" class="btn btn-primary" id="mdEdit"><i data-lucide="pencil" style="width:14px;height:14px;"></i> Ubah</a><?php endif; ?>
        </div>
    </div>
</div>

<!-- MODAL HAPUS -->
<div class="modal-overlay" id="modalDelete" role="dialog" aria-modal="true" aria-labelledby="mdelTitle">
    <div class="modal modal-sm">
        <form method="post" action="riwayat.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id_riwayat" id="delId">
            <input type="hidden" name="back" value="<?= e($back) ?>">
            <div class="modal-body" style="padding-top:26px">
                <div class="confirm-icon"><i data-lucide="trash-2" style="width:24px;height:24px;"></i></div>
                <h3 class="modal-title" id="mdelTitle" style="text-align:center;margin-bottom:8px">Hapus data pemeriksaan?</h3>
                <p class="confirm-text">Pemeriksaan <strong id="delNama"></strong> pada <strong id="delTgl"></strong> akan dihapus permanen dan tidak dapat dikembalikan.</p>
            </div>
            <div class="modal-footer" style="justify-content:center">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalDelete')">Batal</button>
                <button type="submit" class="btn btn-danger">Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>

<?php ob_start(); ?>
<script>
function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
function tile(label, val, unit, status, cls) {
    if (val === null || val === '' || val === undefined) return '';
    return '<div class="vital-tile"><div class="vt-l">' + esc(label) + '</div><div class="vt-v">' + esc(val) + ' <small>' + esc(unit) + '</small></div>' +
           (status ? '<span class="badge badge-sm ' + esc(cls) + '">' + esc(status) + '</span>' : '') + '</div>';
}
function showDetail(btn) {
    var d = JSON.parse(btn.getAttribute('data-detail'));
    var h = '<div class="detail-grid" style="margin-bottom:20px">' +
        '<div class="detail-item"><div class="dl">Lansia</div><div class="dv">' + esc(d.nama) + '</div></div>' +
        '<div class="detail-item"><div class="dl">NIK</div><div class="dv num">' + esc(d.nik) + '</div></div>' +
        '<div class="detail-item"><div class="dl">Usia / JK</div><div class="dv">' + esc(d.usia) + ' th · ' + (d.jk === 'L' ? 'Laki-laki' : 'Perempuan') + '</div></div>' +
        '<div class="detail-item"><div class="dl">Waktu pemeriksaan</div><div class="dv">' + esc(d.tgl) + ', ' + esc(d.jam) + ' WIB</div></div>' +
        '<div class="detail-item"><div class="dl">Kader pemeriksa</div><div class="dv">' + esc(d.kader || '—') + '</div></div>' +
        '<div class="detail-item"><div class="dl">Status kondisi</div><div class="dv"><span class="badge ' + esc(d.badge.kondisi) + '">' + esc(d.kondisi) + '</span>' +
            (d.rujuk === 'Ya' ? ' <span class="badge badge-danger">Dirujuk</span>' : '') + '</div></div></div>' +
        '<div class="vital-tiles">' +
        tile('Berat badan', d.bb, 'kg') + tile('Tinggi badan', d.tb, 'cm') + tile('IMT', d.imt, '', d.s_imt, d.badge.imt) +
        tile('Tekanan darah', d.sis + '/' + d.dia, 'mmHg', d.s_tensi, d.badge.tensi) +
        tile('Detak jantung', d.nadi, 'bpm') + tile('Suhu', d.suhu, '°C') +
        tile('Gula darah', d.gula, 'mg/dL', d.s_gula, d.badge.gula) + tile('Kolesterol', d.kol, 'mg/dL', d.s_kol, d.badge.kol) +
        tile('Asam urat', d.au, 'mg/dL', d.s_au, d.badge.au) + '</div>' +
        '<div class="detail-grid" style="margin-top:20px">' +
        '<div class="detail-item full"><div class="dl">Keluhan</div><div class="dv" style="font-weight:500;white-space:pre-line">' + esc(d.keluhan || '—') + '</div></div>' +
        '<div class="detail-item full"><div class="dl">Catatan / saran kader</div><div class="dv" style="font-weight:500;white-space:pre-line">' + esc(d.catatan || '—') + '</div></div></div>';
    document.getElementById('mdBody').innerHTML = h;
    var ed = document.getElementById('mdEdit'); if (ed) ed.href = 'input-pemeriksaan.php?edit=' + encodeURIComponent(d.id);
    openModal('modalDetail');
}
function askDelete(btn) {
    document.getElementById('delId').value = btn.getAttribute('data-id');
    document.getElementById('delNama').textContent = btn.getAttribute('data-nama');
    document.getElementById('delTgl').textContent = btn.getAttribute('data-tgl');
    openModal('modalDelete');
}
</script>
<?php
$page_scripts = ob_get_clean();
include '../components/kader-layout-bottom.php';
