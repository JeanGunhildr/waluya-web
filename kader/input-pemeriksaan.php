<?php
// ==============================================================================
// WALUYA - INPUT / EDIT PEMERIKSAAN KESEHATAN (KADER)
// ------------------------------------------------------------------------------
//  Mode tambah : input-pemeriksaan.php[?id_lansia=ID]
//  Mode ubah   : input-pemeriksaan.php?edit=ID_RIWAYAT
// ==============================================================================
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/kesehatan.php';

$kader = require_kader($pdo);
$active_menu = 'input-pemeriksaan';

// ------------------------------------------------------------------------------
// Daftar lansia aktif pada posyandu kader (untuk dropdown)
// ------------------------------------------------------------------------------
$stmt = $pdo->prepare(
    "SELECT id_lansia, nama_lengkap, jenis_kelamin, tanggal_lahir
       FROM lansia WHERE id_posyandu = :p AND status_lansia = 'aktif' ORDER BY nama_lengkap"
);
$stmt->execute(['p' => $kader['id_posyandu']]);
$daftar_lansia = $stmt->fetchAll();

// ------------------------------------------------------------------------------
// Mode ubah: muat data riwayat (hanya milik posyandu kader ini)
// ------------------------------------------------------------------------------
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : (int)($_POST['edit_id'] ?? 0);
$existing = null;
if ($edit_id > 0) {
    $stmt = $pdo->prepare(
        "SELECT rk.*, l.nama_lengkap, l.jenis_kelamin, l.tanggal_lahir
           FROM riwayat_kesehatan rk
           JOIN lansia l ON l.id_lansia = rk.id_lansia
          WHERE rk.id_riwayat = :id AND l.id_posyandu = :p"
    );
    $stmt->execute(['id' => $edit_id, 'p' => $kader['id_posyandu']]);
    $existing = $stmt->fetch();
    if (!$existing) {
        flash_set('danger', 'Data pemeriksaan tidak ditemukan atau bukan dari posyandu Anda.');
        redirect('riwayat.php');
    }
}
$is_edit = $existing !== null;

// ------------------------------------------------------------------------------
// Nilai awal form
// ------------------------------------------------------------------------------
$v = [
    'id_lansia'           => (int)($_GET['id_lansia'] ?? 0),
    'tanggal_pemeriksaan' => date('Y-m-d'),
    'waktu_pemeriksaan'   => date('H:i'),
    'berat_badan' => '', 'tinggi_badan' => '',
    'tensi_sistolik' => '', 'tensi_diastolik' => '',
    'detak_jantung' => '', 'suhu_tubuh' => '',
    'gula_darah' => '', 'kolesterol' => '', 'asam_urat' => '',
    'keluhan' => '', 'catatan_kader' => '',
    'status_kondisi' => '', 'rujukan_puskesmas' => '',
];
if ($is_edit) {
    foreach ($v as $k => $_) {
        if (isset($existing[$k])) {
            $v[$k] = $existing[$k];
        }
    }
    $v['waktu_pemeriksaan'] = jam_hm($existing['waktu_pemeriksaan']);
    foreach (['berat_badan', 'tinggi_badan', 'suhu_tubuh', 'asam_urat'] as $k) {
        if ($v[$k] !== null && $v[$k] !== '') $v[$k] = rtrim(rtrim(number_format((float)$v[$k], 2, '.', ''), '0'), '.');
    }
}

$errors = [];

// ------------------------------------------------------------------------------
// Proses simpan (POST)
// ------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    require_can_write($kader, 'riwayat.php');

    // Ambil input mentah
    $kolom_angka = ['berat_badan', 'tinggi_badan', 'tensi_sistolik', 'tensi_diastolik', 'detak_jantung',
                    'suhu_tubuh', 'gula_darah', 'kolesterol', 'asam_urat'];
    foreach ($v as $k => $_) {
        if (isset($_POST[$k]) && is_string($_POST[$k])) {
            $val = trim($_POST[$k]);
            // Terima koma desimal ("36,5") hanya pada kolom angka; teks bebas dibiarkan.
            $v[$k] = in_array($k, $kolom_angka, true) ? str_replace(',', '.', $val) : $val;
        }
    }

    // Lansia: mode ubah mengunci lansia asli
    $id_lansia = $is_edit ? (int)$existing['id_lansia'] : (int)$v['id_lansia'];
    $stmt = $pdo->prepare("SELECT id_lansia, jenis_kelamin FROM lansia WHERE id_lansia = :id AND id_posyandu = :p AND (status_lansia = 'aktif' OR id_lansia = :cur)");
    $stmt->execute(['id' => $id_lansia, 'p' => $kader['id_posyandu'], 'cur' => $is_edit ? $id_lansia : 0]);
    $lansia = $stmt->fetch();
    if (!$lansia) {
        $errors[] = 'Pilih lansia yang valid dari posyandu Anda.';
    }

    // Tanggal & waktu
    $dt = DateTime::createFromFormat('Y-m-d', $v['tanggal_pemeriksaan']);
    if (!$dt || $dt->format('Y-m-d') !== $v['tanggal_pemeriksaan']) {
        $errors[] = 'Tanggal pemeriksaan tidak valid.';
    } elseif ($v['tanggal_pemeriksaan'] > date('Y-m-d')) {
        $errors[] = 'Tanggal pemeriksaan tidak boleh di masa depan.';
    }
    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $v['waktu_pemeriksaan'])) {
        $errors[] = 'Waktu pemeriksaan tidak valid.';
    }

    // Angka wajib & opsional dengan rentang wajar
    $num = function (string $key, string $label, float $min, float $max, bool $required, bool $int) use (&$v, &$errors) {
        $raw = $v[$key];
        if ($raw === '' || $raw === null) {
            if ($required) $errors[] = "$label wajib diisi.";
            return null;
        }
        if (!is_numeric($raw)) {
            $errors[] = "$label harus berupa angka.";
            return null;
        }
        $n = (float)$raw;
        if ($int && floor($n) != $n) {
            $errors[] = "$label harus bilangan bulat.";
            return null;
        }
        if ($n < $min || $n > $max) {
            $errors[] = "$label di luar rentang wajar ($min – $max).";
            return null;
        }
        return $int ? (int)$n : $n;
    };
    $bb   = $num('berat_badan', 'Berat badan', 20, 200, true, false);
    $tb   = $num('tinggi_badan', 'Tinggi badan', 100, 220, true, false);
    $sis  = $num('tensi_sistolik', 'Tensi sistolik', 50, 300, true, true);
    $dia  = $num('tensi_diastolik', 'Tensi diastolik', 30, 200, true, true);
    $nadi = $num('detak_jantung', 'Detak jantung', 30, 220, true, true);
    $suhu = $num('suhu_tubuh', 'Suhu tubuh', 30, 43, true, false);
    $gula = $num('gula_darah', 'Gula darah', 20, 800, false, true);
    $kol  = $num('kolesterol', 'Kolesterol', 50, 600, false, true);
    $au   = $num('asam_urat', 'Asam urat', 1, 20, false, false);

    if ($sis !== null && $dia !== null && $dia >= $sis) {
        $errors[] = 'Tensi diastolik harus lebih kecil dari sistolik.';
    }
    if (mb_strlen($v['keluhan']) > 2000 || mb_strlen($v['catatan_kader']) > 2000) {
        $errors[] = 'Keluhan / catatan maksimal 2000 karakter.';
    }

    if (!$errors) {
        $imt = hitung_imt($bb, $tb);
        $row = [
            'status_imt'        => klasifikasi_imt($imt),
            'status_tensi'      => klasifikasi_tensi($sis, $dia),
            'status_gula_darah' => klasifikasi_gula($gula),
            'status_kolesterol' => klasifikasi_kolesterol($kol),
            'status_asam_urat'  => klasifikasi_asam_urat($au, $lansia['jenis_kelamin']),
            'suhu_tubuh'        => $suhu,
            'detak_jantung'     => $nadi,
            'gula_darah'        => $gula,
        ];
        [$saran_kondisi, $saran_rujuk] = saran_kondisi($row);

        // Kader boleh menimpa saran; nilai dibatasi whitelist enum.
        $kondisi = in_array($v['status_kondisi'], STATUS_KONDISI_LIST, true) ? $v['status_kondisi'] : $saran_kondisi;
        $rujuk   = in_array($v['rujukan_puskesmas'], ['Ya', 'Tidak'], true) ? $v['rujukan_puskesmas'] : $saran_rujuk;

        $params = [
            'id_lansia' => $id_lansia,
            'tgl'  => $v['tanggal_pemeriksaan'],
            'wkt'  => $v['waktu_pemeriksaan'] . ':00',
            'bb' => $bb, 'tb' => $tb, 'imt' => $imt, 's_imt' => $row['status_imt'],
            'sis' => $sis, 'dia' => $dia, 's_tensi' => $row['status_tensi'],
            'nadi' => $nadi, 'suhu' => $suhu,
            'gula' => $gula, 's_gula' => $row['status_gula_darah'],
            'kol' => $kol, 's_kol' => $row['status_kolesterol'],
            'au' => $au, 's_au' => $row['status_asam_urat'],
            'keluhan' => $v['keluhan'] !== '' ? $v['keluhan'] : null,
            'catatan' => $v['catatan_kader'] !== '' ? $v['catatan_kader'] : null,
            'kondisi' => $kondisi, 'rujuk' => $rujuk,
        ];

        try {
            if ($is_edit) {
                $params['id'] = $edit_id;
                $pdo->prepare(
                    "UPDATE riwayat_kesehatan SET
                        tanggal_pemeriksaan = :tgl, waktu_pemeriksaan = :wkt,
                        berat_badan = :bb, tinggi_badan = :tb, imt = :imt, status_imt = :s_imt,
                        tensi_sistolik = :sis, tensi_diastolik = :dia, status_tensi = :s_tensi,
                        detak_jantung = :nadi, suhu_tubuh = :suhu,
                        gula_darah = :gula, status_gula_darah = :s_gula,
                        kolesterol = :kol, status_kolesterol = :s_kol,
                        asam_urat = :au, status_asam_urat = :s_au,
                        keluhan = :keluhan, catatan_kader = :catatan,
                        status_kondisi = :kondisi, rujukan_puskesmas = :rujuk
                      WHERE id_riwayat = :id AND id_lansia = :id_lansia"
                )->execute($params);
                flash_set('success', 'Data pemeriksaan berhasil diperbarui.');
            } else {
                $params['id_kader'] = $kader['id_kader'];
                $pdo->prepare(
                    "INSERT INTO riwayat_kesehatan
                        (id_lansia, id_kader, tanggal_pemeriksaan, waktu_pemeriksaan,
                         berat_badan, tinggi_badan, imt, status_imt,
                         tensi_sistolik, tensi_diastolik, status_tensi, detak_jantung, suhu_tubuh,
                         gula_darah, status_gula_darah, kolesterol, status_kolesterol, asam_urat, status_asam_urat,
                         keluhan, catatan_kader, status_kondisi, rujukan_puskesmas)
                     VALUES
                        (:id_lansia, :id_kader, :tgl, :wkt,
                         :bb, :tb, :imt, :s_imt,
                         :sis, :dia, :s_tensi, :nadi, :suhu,
                         :gula, :s_gula, :kol, :s_kol, :au, :s_au,
                         :keluhan, :catatan, :kondisi, :rujuk)"
                )->execute($params);
                flash_set('success', 'Pemeriksaan berhasil disimpan. Status kondisi: ' . $kondisi . '.');
            }
            redirect('riwayat.php');
        } catch (PDOException $ex) {
            $errors[] = 'Gagal menyimpan ke database. Silakan coba lagi.';
        }
    }
}

$sel_lansia = $is_edit ? (int)$existing['id_lansia'] : (int)$v['id_lansia'];
$page_title   = $is_edit ? 'Ubah Pemeriksaan' : 'Input Pemeriksaan';
$topbar_title = $is_edit ? 'Ubah Data Pemeriksaan' : 'Input Pemeriksaan Lansia';

include '../components/kader-layout-top.php';
?>

<div class="welcome-banner">
    <h1 class="welcome-title"><?= $is_edit ? 'Ubah Hasil Pemeriksaan' : 'Formulir Pemeriksaan Fisik & Skrining Lansia' ?></h1>
    <p class="welcome-subtitle">
        <?php if ($is_edit): ?>
            Memperbarui pemeriksaan <strong><?= e($existing['nama_lengkap']) ?></strong> tanggal <?= e(tgl_id_panjang($existing['tanggal_pemeriksaan'])) ?>. Status IMT, tensi, dan parameter darah dihitung ulang otomatis saat disimpan.
        <?php else: ?>
            Catat hasil pengukuran sesi posyandu: berat &amp; tinggi badan, tekanan darah, detak jantung, suhu, gula darah, kolesterol, asam urat, keluhan, dan tindak lanjut. Status kesehatan dihitung otomatis.
        <?php endif; ?>
    </p>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger" role="alert">
        <i data-lucide="alert-circle" style="width:18px;height:18px;"></i>
        <div>Periksa kembali isian Anda:
            <ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
        </div>
    </div>
<?php endif; ?>

<?php if (!$daftar_lansia && !$is_edit): ?>
    <div class="card">
        <div class="empty-state">
            <i data-lucide="users"></i>
            <h4>Belum ada lansia aktif di posyandu Anda</h4>
            <p>Minta Admin menambahkan data lansia terlebih dahulu agar bisa diperiksa.</p>
        </div>
    </div>
<?php else: ?>

<form method="post" action="" id="formPemeriksaan" novalidate class="card">
    <?= csrf_field() ?>
    <?php if ($is_edit): ?><input type="hidden" name="edit_id" value="<?= (int)$edit_id ?>"><?php endif; ?>

    <div class="card-header">
        <h3 class="card-title">
            <i data-lucide="<?= $is_edit ? 'pencil' : 'plus-circle' ?>" style="width:18px;height:18px;color:var(--primary);"></i>
            <?= $is_edit ? 'Ubah Rekam Medis' : 'Formulir Rekam Medis Pemeriksaan Baru' ?>
        </h3>
    </div>

    <div class="form-section"><i data-lucide="user" style="width:15px;height:15px;"></i> Data Pasien & Waktu</div>
    <div class="form-grid">
        <div class="form-group full">
            <label class="form-label" for="id_lansia">Lansia yang diperiksa <span class="req">*</span></label>
            <?php if ($is_edit): ?>
                <input type="text" class="form-input" value="<?= e($existing['nama_lengkap']) ?> (<?= hitung_usia($existing['tanggal_lahir']) ?> th, <?= $existing['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>)" readonly>
                <input type="hidden" id="id_lansia" data-jk="<?= e($existing['jenis_kelamin']) ?>" value="<?= (int)$existing['id_lansia'] ?>">
                <span class="form-hint">Lansia tidak dapat diganti saat mengubah. Hapus lalu input ulang bila salah pilih.</span>
            <?php else: ?>
                <select class="form-select" name="id_lansia" id="id_lansia" required>
                    <option value="">— Pilih lansia —</option>
                    <?php foreach ($daftar_lansia as $l): ?>
                        <option value="<?= (int)$l['id_lansia'] ?>" data-jk="<?= e($l['jenis_kelamin']) ?>" <?= $sel_lansia === (int)$l['id_lansia'] ? 'selected' : '' ?>>
                            <?= e($l['nama_lengkap']) ?> — <?= hitung_usia($l['tanggal_lahir']) ?> th (<?= e($l['jenis_kelamin']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label class="form-label" for="tanggal_pemeriksaan">Tanggal <span class="req">*</span></label>
            <input type="date" class="form-input" id="tanggal_pemeriksaan" name="tanggal_pemeriksaan" value="<?= e($v['tanggal_pemeriksaan']) ?>" max="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label" for="waktu_pemeriksaan">Waktu <span class="req">*</span></label>
            <input type="time" class="form-input" id="waktu_pemeriksaan" name="waktu_pemeriksaan" value="<?= e($v['waktu_pemeriksaan']) ?>" required>
        </div>
    </div>

    <div class="form-section"><i data-lucide="ruler" style="width:15px;height:15px;"></i> Antropometri</div>
    <div class="form-grid">
        <div class="form-group">
            <label class="form-label" for="berat_badan">Berat badan <span class="req">*</span></label>
            <div class="input-unit"><input type="text" inputmode="decimal" class="form-input calc" id="berat_badan" name="berat_badan" value="<?= e($v['berat_badan']) ?>" placeholder="62.5" required><span class="unit">kg</span></div>
        </div>
        <div class="form-group">
            <label class="form-label" for="tinggi_badan">Tinggi badan <span class="req">*</span></label>
            <div class="input-unit"><input type="text" inputmode="decimal" class="form-input calc" id="tinggi_badan" name="tinggi_badan" value="<?= e($v['tinggi_badan']) ?>" placeholder="165" required><span class="unit">cm</span></div>
        </div>
        <div class="form-group">
            <label class="form-label">IMT (otomatis)</label>
            <input type="text" class="form-input" id="imt_out" readonly placeholder="Terhitung dari BB & TB">
        </div>
    </div>

    <div class="form-section"><i data-lucide="heart-pulse" style="width:15px;height:15px;"></i> Tanda Vital</div>
    <div class="form-grid">
        <div class="form-group">
            <label class="form-label">Tekanan darah <span class="req">*</span></label>
            <div class="bp-pair">
                <input type="text" inputmode="numeric" class="form-input calc" id="tensi_sistolik" name="tensi_sistolik" value="<?= e($v['tensi_sistolik']) ?>" placeholder="120" aria-label="Sistolik" required>
                <span class="sep">/</span>
                <input type="text" inputmode="numeric" class="form-input calc" id="tensi_diastolik" name="tensi_diastolik" value="<?= e($v['tensi_diastolik']) ?>" placeholder="80" aria-label="Diastolik" required>
            </div>
            <span class="form-hint">Sistolik / diastolik, mmHg</span>
        </div>
        <div class="form-group">
            <label class="form-label" for="detak_jantung">Detak jantung <span class="req">*</span></label>
            <div class="input-unit"><input type="text" inputmode="numeric" class="form-input calc" id="detak_jantung" name="detak_jantung" value="<?= e($v['detak_jantung']) ?>" placeholder="78" required><span class="unit">bpm</span></div>
        </div>
        <div class="form-group">
            <label class="form-label" for="suhu_tubuh">Suhu tubuh <span class="req">*</span></label>
            <div class="input-unit"><input type="text" inputmode="decimal" class="form-input calc" id="suhu_tubuh" name="suhu_tubuh" value="<?= e($v['suhu_tubuh']) ?>" placeholder="36.5" required><span class="unit">°C</span></div>
        </div>
    </div>

    <div class="form-section"><i data-lucide="droplets" style="width:15px;height:15px;"></i> Pemeriksaan Darah (opsional)</div>
    <div class="form-grid">
        <div class="form-group">
            <label class="form-label" for="gula_darah">Gula darah sewaktu</label>
            <div class="input-unit"><input type="text" inputmode="numeric" class="form-input calc" id="gula_darah" name="gula_darah" value="<?= e($v['gula_darah']) ?>" placeholder="110"><span class="unit">mg/dL</span></div>
        </div>
        <div class="form-group">
            <label class="form-label" for="kolesterol">Kolesterol total</label>
            <div class="input-unit"><input type="text" inputmode="numeric" class="form-input calc" id="kolesterol" name="kolesterol" value="<?= e($v['kolesterol']) ?>" placeholder="180"><span class="unit">mg/dL</span></div>
        </div>
        <div class="form-group">
            <label class="form-label" for="asam_urat">Asam urat</label>
            <div class="input-unit"><input type="text" inputmode="decimal" class="form-input calc" id="asam_urat" name="asam_urat" value="<?= e($v['asam_urat']) ?>" placeholder="5.2"><span class="unit">mg/dL</span></div>
        </div>
    </div>

    <div class="form-section"><i data-lucide="activity" style="width:15px;height:15px;"></i> Hasil Penilaian Otomatis</div>
    <div class="live-preview" id="livePreview" aria-live="polite">
        <span class="lp-item" style="color:var(--text-light)">Isi data di atas untuk melihat status IMT, tensi, dan parameter darah.</span>
    </div>

    <div class="form-section"><i data-lucide="clipboard-pen" style="width:15px;height:15px;"></i> Keluhan, Catatan & Tindak Lanjut</div>
    <div class="form-grid">
        <div class="form-group full">
            <label class="form-label" for="keluhan">Keluhan lansia</label>
            <textarea class="form-textarea" id="keluhan" name="keluhan" maxlength="2000" placeholder="Contoh: pusing di pagi hari, sendi kaku..."><?= e($v['keluhan']) ?></textarea>
        </div>
        <div class="form-group full">
            <label class="form-label" for="catatan_kader">Catatan / saran kader</label>
            <textarea class="form-textarea" id="catatan_kader" name="catatan_kader" maxlength="2000" placeholder="Contoh: kurangi makanan asin, kontrol ulang 2 minggu lagi..."><?= e($v['catatan_kader']) ?></textarea>
        </div>
        <div class="form-group">
            <label class="form-label" for="status_kondisi">Status kondisi keseluruhan</label>
            <select class="form-select" id="status_kondisi" name="status_kondisi">
                <?php foreach (STATUS_KONDISI_LIST as $s): ?>
                    <option value="<?= e($s) ?>" <?= $v['status_kondisi'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
            </select>
            <span class="form-hint" id="kondisiHint">Disarankan otomatis; bisa Anda ubah.</span>
        </div>
        <div class="form-group">
            <label class="form-label" for="rujukan_puskesmas">Rujuk ke Puskesmas?</label>
            <select class="form-select" id="rujukan_puskesmas" name="rujukan_puskesmas">
                <option value="Tidak" <?= $v['rujukan_puskesmas'] !== 'Ya' ? 'selected' : '' ?>>Tidak</option>
                <option value="Ya" <?= $v['rujukan_puskesmas'] === 'Ya' ? 'selected' : '' ?>>Ya, perlu dirujuk</option>
            </select>
        </div>
    </div>

    <div class="form-actions">
        <a href="riwayat.php" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary" <?= $kader['can_write'] ? '' : 'disabled' ?>>
            <i data-lucide="save" style="width:15px;height:15px;"></i>
            <?= $is_edit ? 'Simpan Perubahan' : 'Simpan Pemeriksaan' ?>
        </button>
    </div>
</form>

<?php endif; ?>

<?php
ob_start();
?>
<script>
(function () {
    var isEdit = <?= $is_edit ? 'true' : 'false' ?>;
    var $ = function (id) { return document.getElementById(id); };
    var form = $('formPemeriksaan');
    if (!form) return;

    // Padanan fungsi klasifikasi PHP (config/kesehatan.php) untuk pratinjau langsung.
    // Server tetap menghitung ulang saat simpan.
    function num(id) { var v = $(id).value.replace(',', '.').trim(); return v === '' || isNaN(v) ? null : parseFloat(v); }
    function cls(cat) { return { 'Normal': 'badge-active', 'Aman / Sehat': 'badge-active', 'Kelebihan BB': 'badge-pending', 'Pra-hipertensi': 'badge-pending', 'Perlu Perhatian': 'badge-pending', 'Rendah': 'badge-pending', 'Kurus': 'badge-pending', 'Hipotensi': 'badge-pending' }[cat] || 'badge-danger'; }

    function imt(bb, tb) { return (bb && tb) ? bb / Math.pow(tb / 100, 2) : null; }
    function sImt(x) { return x < 18.5 ? 'Kurus' : x <= 25 ? 'Normal' : x <= 27 ? 'Kelebihan BB' : 'Obesitas'; }
    function sTensi(s, d) {
        if (s < 90 || d < 60) return 'Hipotensi';
        if (s >= 160 || d >= 100) return 'Hipertensi 2';
        if (s >= 140 || d >= 90) return 'Hipertensi 1';
        if (s >= 130 || d >= 85) return 'Pra-hipertensi';
        return 'Normal';
    }
    function sGula(g) { return g < 70 ? 'Rendah' : g >= 200 ? 'Tinggi' : 'Normal'; }
    function sKol(k) { return k >= 200 ? 'Tinggi' : 'Normal'; }
    function sAu(a, jk) { return a > (jk === 'P' ? 6 : 7) ? 'Tinggi' : 'Normal'; }

    function jenisKelamin() {
        var el = $('id_lansia');
        if (!el) return 'L';
        if (el.tagName === 'SELECT') { var o = el.options[el.selectedIndex]; return o ? (o.getAttribute('data-jk') || 'L') : 'L'; }
        return el.getAttribute('data-jk') || 'L';
    }

    var kondisiDirty = isEdit;
    $('status_kondisi').addEventListener('change', function () { kondisiDirty = true; });
    $('rujukan_puskesmas').addEventListener('change', function () { kondisiDirty = true; });

    function chip(label, val, status) {
        return '<span class="lp-item">' + label + ' <span class="badge badge-sm ' + cls(status) + '">' + (val ? val + ' · ' : '') + status + '</span></span>';
    }

    function update() {
        var bb = num('berat_badan'), tb = num('tinggi_badan');
        var sis = num('tensi_sistolik'), dia = num('tensi_diastolik');
        var nadi = num('detak_jantung'), suhu = num('suhu_tubuh');
        var gula = num('gula_darah'), kol = num('kolesterol'), au = num('asam_urat');
        var jk = jenisKelamin(), out = [], perhatian = false, tinggi = false;

        var m = imt(bb, tb);
        $('imt_out').value = m ? m.toFixed(2) : '';
        if (m) { var s = sImt(m); out.push(chip('IMT', m.toFixed(1), s)); if (s !== 'Normal') perhatian = true; }
        if (sis && dia) {
            var st = sTensi(sis, dia); out.push(chip('Tensi', sis + '/' + dia, st));
            if (st !== 'Normal') perhatian = true; if (st === 'Hipertensi 2' || st === 'Hipotensi') tinggi = true;
        }
        if (gula !== null) { var sg = sGula(gula); out.push(chip('Gula', gula, sg)); if (sg !== 'Normal') perhatian = true; if (gula < 70 || gula >= 300) tinggi = true; }
        if (kol !== null) { var sk = sKol(kol); out.push(chip('Kolesterol', kol, sk)); if (sk !== 'Normal') perhatian = true; }
        if (au !== null) { var sa = sAu(au, jk); out.push(chip('Asam urat', au, sa)); if (sa !== 'Normal') perhatian = true; }
        if (suhu !== null) { if (suhu >= 38.5 || suhu < 35) tinggi = true; else if (suhu >= 37.6) perhatian = true; }
        if (nadi !== null) { if (nadi < 50 || nadi > 120) tinggi = true; else if (nadi < 60 || nadi > 100) perhatian = true; }

        $('livePreview').innerHTML = out.length ? out.join('') : '<span class="lp-item" style="color:var(--text-light)">Isi data di atas untuk melihat status IMT, tensi, dan parameter darah.</span>';

        if (!kondisiDirty && out.length) {
            $('status_kondisi').value = tinggi ? 'Risiko Tinggi' : perhatian ? 'Perlu Perhatian' : 'Aman / Sehat';
            $('rujukan_puskesmas').value = tinggi ? 'Ya' : 'Tidak';
        }
    }

    form.querySelectorAll('.calc').forEach(function (el) { el.addEventListener('input', update); });
    if ($('id_lansia')) $('id_lansia').addEventListener('change', update);
    update();

    // Validasi ringan sisi klien (server tetap memvalidasi penuh)
    form.addEventListener('submit', function (ev) {
        var bad = false;
        form.querySelectorAll('[required]').forEach(function (el) {
            var empty = !el.value.trim();
            el.classList.toggle('is-invalid', empty);
            if (empty) bad = true;
        });
        if (bad) { ev.preventDefault(); var f = form.querySelector('.is-invalid'); if (f) f.focus(); }
    });
})();
</script>
<?php
$page_scripts = ob_get_clean();
include '../components/kader-layout-bottom.php';
