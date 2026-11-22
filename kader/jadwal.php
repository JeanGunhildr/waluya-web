<?php
// ==============================================================================
// WALUYA - JADWAL KEGIATAN POSYANDU (KADER - CRUD)
// ==============================================================================
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/kesehatan.php';

$kader = require_kader($pdo);
$active_menu = 'jadwal';

const JENIS_KEGIATAN = ['Pemeriksaan Rutin', 'Senam Lansia', 'Penyuluhan', 'Kunjungan Rumah', 'Lainnya'];
const STATUS_JADWAL  = ['terjadwal', 'selesai', 'dibatalkan'];

$errors = [];
$open_form = false;
$form = [
    'id_jadwal' => 0, 'judul' => '', 'jenis_kegiatan' => 'Pemeriksaan Rutin', 'tanggal' => '',
    'jam_mulai' => '08:00', 'jam_selesai' => '', 'lokasi' => $kader['alamat_posyandu'] ?? '',
    'deskripsi' => '', 'target_peserta' => '', 'status' => 'terjadwal',
];

// ------------------------------------------------------------------------------
// POST: simpan (tambah/ubah) & hapus
// ------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    require_can_write($kader, 'jadwal.php');
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM jadwal_posyandu WHERE id_jadwal = :id AND id_posyandu = :p");
        $stmt->execute(['id' => (int)($_POST['id_jadwal'] ?? 0), 'p' => $kader['id_posyandu']]);
        flash_set($stmt->rowCount() ? 'success' : 'danger', $stmt->rowCount() ? 'Jadwal berhasil dihapus.' : 'Jadwal tidak ditemukan.');
        redirect('jadwal.php' . (isset($_POST['tab']) && in_array($_POST['tab'], ['mendatang', 'lalu', 'semua'], true) ? '?tab=' . $_POST['tab'] : ''));
    }

    if ($action === 'save') {
        foreach ($form as $k => $_) {
            $form[$k] = isset($_POST[$k]) && is_string($_POST[$k]) ? trim($_POST[$k]) : $form[$k];
        }
        $form['id_jadwal'] = (int)$form['id_jadwal'];

        if ($form['judul'] === '' || mb_strlen($form['judul']) > 150) $errors[] = 'Judul kegiatan wajib diisi (maksimal 150 karakter).';
        if (!in_array($form['jenis_kegiatan'], JENIS_KEGIATAN, true)) $errors[] = 'Jenis kegiatan tidak valid.';
        $dt = DateTime::createFromFormat('Y-m-d', $form['tanggal']);
        if (!$dt || $dt->format('Y-m-d') !== $form['tanggal']) $errors[] = 'Tanggal kegiatan tidak valid.';
        $reTime = '/^([01]\d|2[0-3]):[0-5]\d$/';
        if (!preg_match($reTime, $form['jam_mulai'])) $errors[] = 'Jam mulai tidak valid.';
        if ($form['jam_selesai'] !== '') {
            if (!preg_match($reTime, $form['jam_selesai'])) $errors[] = 'Jam selesai tidak valid.';
            elseif (preg_match($reTime, $form['jam_mulai']) && $form['jam_selesai'] <= $form['jam_mulai']) $errors[] = 'Jam selesai harus setelah jam mulai.';
        }
        if ($form['lokasi'] === '' || mb_strlen($form['lokasi']) > 255) $errors[] = 'Lokasi wajib diisi (maksimal 255 karakter).';
        if (mb_strlen($form['deskripsi']) > 2000) $errors[] = 'Deskripsi maksimal 2000 karakter.';
        $target = null;
        if ($form['target_peserta'] !== '') {
            if (!ctype_digit($form['target_peserta']) || (int)$form['target_peserta'] > 10000) $errors[] = 'Target peserta harus berupa angka 0–10000.';
            else $target = (int)$form['target_peserta'];
        }
        if (!in_array($form['status'], STATUS_JADWAL, true)) $errors[] = 'Status jadwal tidak valid.';

        if (!$errors) {
            $p = [
                'judul' => $form['judul'], 'jenis' => $form['jenis_kegiatan'], 'tgl' => $form['tanggal'],
                'mulai' => $form['jam_mulai'] . ':00', 'selesai' => $form['jam_selesai'] !== '' ? $form['jam_selesai'] . ':00' : null,
                'lokasi' => $form['lokasi'], 'desk' => $form['deskripsi'] !== '' ? $form['deskripsi'] : null,
                'target' => $target, 'status' => $form['status'],
            ];
            if ($form['id_jadwal'] > 0) {
                $p['id'] = $form['id_jadwal'];
                $p['pos'] = $kader['id_posyandu'];
                $stmt = $pdo->prepare(
                    "UPDATE jadwal_posyandu SET judul=:judul, jenis_kegiatan=:jenis, tanggal=:tgl, jam_mulai=:mulai, jam_selesai=:selesai,
                            lokasi=:lokasi, deskripsi=:desk, target_peserta=:target, status=:status
                      WHERE id_jadwal=:id AND id_posyandu=:pos"
                );
                $stmt->execute($p);
                // rowCount bisa 0 bila tidak ada perubahan nilai, jadi cek keberadaan barisnya.
                $cek = $pdo->prepare("SELECT 1 FROM jadwal_posyandu WHERE id_jadwal=:id AND id_posyandu=:pos");
                $cek->execute(['id' => $form['id_jadwal'], 'pos' => $kader['id_posyandu']]);
                $ada = (bool)$cek->fetchColumn();
                flash_set($ada ? 'success' : 'danger', $ada ? 'Jadwal berhasil diperbarui.' : 'Jadwal tidak ditemukan.');
            } else {
                $p['pos'] = $kader['id_posyandu'];
                $p['kader'] = $kader['id_kader'];
                $pdo->prepare(
                    "INSERT INTO jadwal_posyandu (id_posyandu, id_kader, judul, jenis_kegiatan, tanggal, jam_mulai, jam_selesai, lokasi, deskripsi, target_peserta, status)
                     VALUES (:pos, :kader, :judul, :jenis, :tgl, :mulai, :selesai, :lokasi, :desk, :target, :status)"
                )->execute($p);
                flash_set('success', 'Jadwal baru berhasil ditambahkan.');
            }
            redirect('jadwal.php');
        }
        $open_form = true; // tampilkan kembali modal dengan isian + pesan error
    }
}

// ------------------------------------------------------------------------------
// DATA
// ------------------------------------------------------------------------------
$tab = (string)($_GET['tab'] ?? 'mendatang');
if (!in_array($tab, ['mendatang', 'lalu', 'semua'], true)) $tab = 'mendatang';
$today = date('Y-m-d');

$stmt = $pdo->prepare(
    "SELECT SUM(tanggal >= :t1 AND status = 'terjadwal') AS mendatang,
            SUM(NOT (tanggal >= :t2 AND status = 'terjadwal')) AS lalu,
            COUNT(*) AS semua
       FROM jadwal_posyandu WHERE id_posyandu = :p"
);
$stmt->execute(['t1' => $today, 't2' => $today, 'p' => $kader['id_posyandu']]);
$cnt = array_map('intval', $stmt->fetch());

$cond  = ['mendatang' => "tanggal >= :t AND status = 'terjadwal'", 'lalu' => "NOT (tanggal >= :t AND status = 'terjadwal')", 'semua' => '1 = 1'][$tab];
$order = $tab === 'mendatang' ? 'tanggal ASC, jam_mulai ASC' : 'tanggal DESC, jam_mulai DESC';
$stmt = $pdo->prepare(
    "SELECT j.*, u.nama_lengkap AS nama_pembuat
       FROM jadwal_posyandu j
       LEFT JOIN kader k ON k.id_kader = j.id_kader
       LEFT JOIN users u ON u.id_user = k.id_user
      WHERE j.id_posyandu = :p AND ($cond) ORDER BY $order LIMIT 100"
);
$qp = ['p' => $kader['id_posyandu']];
if ($tab !== 'semua') $qp['t'] = $today;
$stmt->execute($qp);
$jadwal = $stmt->fetchAll();

$icons = ['Pemeriksaan Rutin' => 'stethoscope', 'Senam Lansia' => 'dumbbell', 'Penyuluhan' => 'megaphone', 'Kunjungan Rumah' => 'house', 'Lainnya' => 'calendar'];

$page_title   = 'Jadwal Posyandu';
$topbar_title = 'Jadwal Kegiatan Posyandu';
include '../components/kader-layout-top.php';
?>

<div class="welcome-banner">
    <h1 class="welcome-title">Agenda & Jadwal Kegiatan Posyandu Lansia</h1>
    <p class="welcome-subtitle">
        Jadwal pemeriksaan rutin bulanan, senam lansia, penyuluhan gizi, dan kunjungan rumah untuk <strong><?= e($kader['nama_posyandu']) ?></strong>. Kader dapat menambah, mengubah, dan menghapus agenda.
    </p>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger" role="alert">
        <i data-lucide="alert-circle" style="width:18px;height:18px;"></i>
        <div>Jadwal belum tersimpan:
            <ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i data-lucide="calendar" style="width:18px;height:18px;color:var(--primary);"></i>
            Agenda Kegiatan Posyandu
        </h3>
        <button type="button" class="btn btn-primary btn-sm" onclick="openForm()" <?= $kader['can_write'] ? '' : 'disabled' ?>>
            <i data-lucide="plus" style="width:14px;height:14px;"></i> Tambah Jadwal
        </button>
    </div>

    <div class="toolbar">
        <div class="tabs">
            <a href="?tab=mendatang" class="<?= $tab === 'mendatang' ? 'active' : '' ?>">Mendatang (<?= $cnt['mendatang'] ?>)</a>
            <a href="?tab=lalu" class="<?= $tab === 'lalu' ? 'active' : '' ?>">Selesai / Lalu (<?= $cnt['lalu'] ?>)</a>
            <a href="?tab=semua" class="<?= $tab === 'semua' ? 'active' : '' ?>">Semua (<?= $cnt['semua'] ?>)</a>
        </div>
    </div>

    <?php if (!$jadwal): ?>
        <div class="empty-state">
            <i data-lucide="calendar-x"></i>
            <h4>Belum ada jadwal pada tab ini</h4>
            <p>Klik “Tambah Jadwal” untuk membuat agenda posyandu berikutnya.</p>
        </div>
    <?php else: ?>
        <div class="agenda-list">
            <?php foreach ($jadwal as $j):
                $ts = strtotime($j['tanggal']);
                $lewat = $j['tanggal'] < $today;
                $payload = [
                    'id_jadwal' => (int)$j['id_jadwal'], 'judul' => $j['judul'], 'jenis_kegiatan' => $j['jenis_kegiatan'],
                    'tanggal' => $j['tanggal'], 'jam_mulai' => jam_hm($j['jam_mulai']), 'jam_selesai' => jam_hm($j['jam_selesai']),
                    'lokasi' => $j['lokasi'], 'deskripsi' => (string)$j['deskripsi'],
                    'target_peserta' => $j['target_peserta'] === null ? '' : (string)$j['target_peserta'], 'status' => $j['status'],
                ];
            ?>
                <div class="agenda-item <?= $lewat ? 'is-past' : '' ?> <?= $j['status'] === 'dibatalkan' ? 'is-cancel' : '' ?>">
                    <div class="agenda-date" aria-hidden="true">
                        <div class="m"><?= e(substr(BULAN_ID[(int)date('n', $ts)], 0, 3)) ?></div>
                        <div class="d"><?= date('j', $ts) ?></div>
                    </div>
                    <div class="agenda-body">
                        <div class="agenda-title">
                            <?= e($j['judul']) ?>
                            <span class="badge badge-sm badge-info"><?= e($j['jenis_kegiatan']) ?></span>
                            <span class="badge badge-sm <?= badge_class($j['status']) ?>"><?= e(ucfirst($j['status'])) ?></span>
                        </div>
                        <div class="agenda-meta">
                            <span><i data-lucide="calendar-days"></i><?= e(tgl_id($j['tanggal'], true)) ?></span>
                            <span><i data-lucide="clock"></i><?= e(jam_hm($j['jam_mulai'])) ?><?= $j['jam_selesai'] ? '–' . e(jam_hm($j['jam_selesai'])) : '' ?> WIB</span>
                            <span><i data-lucide="map-pin"></i><?= e($j['lokasi']) ?></span>
                            <?php if ($j['target_peserta'] !== null): ?><span><i data-lucide="users"></i>Target <?= (int)$j['target_peserta'] ?> lansia</span><?php endif; ?>
                        </div>
                        <?php if ($j['deskripsi']): ?><p class="agenda-desc"><?= nl2br(e($j['deskripsi'])) ?></p><?php endif; ?>
                    </div>
                    <?php if ($kader['can_write']): ?>
                        <div class="agenda-actions">
                            <button type="button" class="btn btn-secondary btn-sm btn-icon" title="Ubah" aria-label="Ubah jadwal"
                                    data-jadwal="<?= e(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>"
                                    onclick="openForm(this)"><i data-lucide="pencil" style="width:15px;height:15px;"></i></button>
                            <button type="button" class="btn btn-outline-danger btn-sm btn-icon" title="Hapus" aria-label="Hapus jadwal"
                                    data-id="<?= (int)$j['id_jadwal'] ?>" data-judul="<?= e($j['judul']) ?>" onclick="askDelete(this)"><i data-lucide="trash-2" style="width:15px;height:15px;"></i></button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL TAMBAH / UBAH -->
<div class="modal-overlay <?= $open_form ? 'open' : '' ?>" id="modalForm" role="dialog" aria-modal="true" aria-labelledby="mfTitle">
    <div class="modal modal-lg">
        <form method="post" action="jadwal.php?tab=<?= e($tab) ?>" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id_jadwal" id="f_id_jadwal" value="<?= (int)$form['id_jadwal'] ?>">
            <div class="modal-header">
                <h3 class="modal-title" id="mfTitle"><?= $form['id_jadwal'] ? 'Ubah Jadwal' : 'Tambah Jadwal Baru' ?></h3>
                <button type="button" class="modal-x" onclick="closeModal('modalForm')" aria-label="Tutup"><i data-lucide="x" style="width:18px;height:18px;"></i></button>
            </div>
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group full">
                        <label class="form-label" for="f_judul">Judul kegiatan <span class="req">*</span></label>
                        <input class="form-input" id="f_judul" name="judul" maxlength="150" required value="<?= e($form['judul']) ?>" placeholder="Posyandu Lansia Bulanan - Januari">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="f_jenis">Jenis kegiatan <span class="req">*</span></label>
                        <select class="form-select" id="f_jenis" name="jenis_kegiatan">
                            <?php foreach (JENIS_KEGIATAN as $jk): ?><option <?= $form['jenis_kegiatan'] === $jk ? 'selected' : '' ?>><?= e($jk) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="f_status">Status</label>
                        <select class="form-select" id="f_status" name="status">
                            <?php foreach (STATUS_JADWAL as $s): ?><option value="<?= e($s) ?>" <?= $form['status'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="f_tanggal">Tanggal <span class="req">*</span></label>
                        <input type="date" class="form-input" id="f_tanggal" name="tanggal" required value="<?= e($form['tanggal']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="f_target">Target peserta</label>
                        <div class="input-unit"><input type="number" min="0" max="10000" class="form-input" id="f_target" name="target_peserta" value="<?= e($form['target_peserta']) ?>" placeholder="40"><span class="unit">lansia</span></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="f_mulai">Jam mulai <span class="req">*</span></label>
                        <input type="time" class="form-input" id="f_mulai" name="jam_mulai" required value="<?= e($form['jam_mulai']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="f_selesai">Jam selesai</label>
                        <input type="time" class="form-input" id="f_selesai" name="jam_selesai" value="<?= e($form['jam_selesai']) ?>">
                    </div>
                    <div class="form-group full">
                        <label class="form-label" for="f_lokasi">Lokasi <span class="req">*</span></label>
                        <input class="form-input" id="f_lokasi" name="lokasi" maxlength="255" required value="<?= e($form['lokasi']) ?>" placeholder="Balai Warga RW 05">
                    </div>
                    <div class="form-group full">
                        <label class="form-label" for="f_deskripsi">Keterangan</label>
                        <textarea class="form-textarea" id="f_deskripsi" name="deskripsi" maxlength="2000" placeholder="Materi, hal yang perlu dibawa, narasumber…"><?= e($form['deskripsi']) ?></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalForm')">Batal</button>
                <button type="submit" class="btn btn-primary"><i data-lucide="save" style="width:14px;height:14px;"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL HAPUS -->
<div class="modal-overlay" id="modalDelete" role="dialog" aria-modal="true" aria-labelledby="mdelTitle">
    <div class="modal modal-sm">
        <form method="post" action="jadwal.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id_jadwal" id="delId">
            <input type="hidden" name="tab" value="<?= e($tab) ?>">
            <div class="modal-body" style="padding-top:26px">
                <div class="confirm-icon"><i data-lucide="trash-2" style="width:24px;height:24px;"></i></div>
                <h3 class="modal-title" id="mdelTitle" style="text-align:center;margin-bottom:8px">Hapus jadwal?</h3>
                <p class="confirm-text">Jadwal <strong id="delJudul"></strong> akan dihapus permanen. Untuk membatalkan kegiatan tanpa menghapus, ubah statusnya menjadi “Dibatalkan”.</p>
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
var DEFAULT_LOKASI = <?= json_encode($kader['alamat_posyandu'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
function openForm(btn) {
    var d = btn ? JSON.parse(btn.getAttribute('data-jadwal')) : {
        id_jadwal: 0, judul: '', jenis_kegiatan: 'Pemeriksaan Rutin', tanggal: '', jam_mulai: '08:00', jam_selesai: '',
        lokasi: DEFAULT_LOKASI, deskripsi: '', target_peserta: '', status: 'terjadwal'
    };
    document.getElementById('mfTitle').textContent = d.id_jadwal ? 'Ubah Jadwal' : 'Tambah Jadwal Baru';
    document.getElementById('f_id_jadwal').value = d.id_jadwal;
    document.getElementById('f_judul').value = d.judul;
    document.getElementById('f_jenis').value = d.jenis_kegiatan;
    document.getElementById('f_status').value = d.status;
    document.getElementById('f_tanggal').value = d.tanggal;
    document.getElementById('f_target').value = d.target_peserta;
    document.getElementById('f_mulai').value = d.jam_mulai;
    document.getElementById('f_selesai').value = d.jam_selesai;
    document.getElementById('f_lokasi').value = d.lokasi;
    document.getElementById('f_deskripsi').value = d.deskripsi;
    openModal('modalForm');
    document.getElementById('f_judul').focus();
}
function askDelete(btn) {
    document.getElementById('delId').value = btn.getAttribute('data-id');
    document.getElementById('delJudul').textContent = btn.getAttribute('data-judul');
    openModal('modalDelete');
}
<?php if ($open_form): ?>document.body.style.overflow = 'hidden';<?php endif; ?>
</script>
<?php
$page_scripts = ob_get_clean();
include '../components/kader-layout-bottom.php';
