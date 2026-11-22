<?php
// ==============================================================================
// WALUYA - ATURAN KLASIFIKASI HASIL PEMERIKSAAN LANSIA
// ==============================================================================
// Nilai status dihitung di server (bukan dipercaya dari form) lalu disimpan ke
// kolom status_* pada tabel riwayat_kesehatan. Ambang batas yang dipakai:
//
//  IMT         : <18,5 Kurus | 18,5-25 Normal | >25-27 Kelebihan BB | >27 Obesitas   (Kemenkes RI)
//  Tensi       : Hipotensi <90 atau <60 | Normal <130 dan <85 |
//                Pra-hipertensi 130-139 / 85-89 | Hipertensi 1 140-159 / 90-99 |
//                Hipertensi 2 >=160 / >=100
//  Gula darah  : dianggap gula darah sewaktu (mg/dL): <70 Rendah | 70-199 Normal | >=200 Tinggi
//  Kolesterol  : total >=200 mg/dL Tinggi
//  Asam urat   : Laki-laki >7,0 | Perempuan >6,0 mg/dL Tinggi
//
// Ubah angka di fungsi-fungsi berikut bila Puskesmas pembina memakai standar lain.
// ==============================================================================

const STATUS_KONDISI_LIST = ['Aman / Sehat', 'Perlu Perhatian', 'Risiko Tinggi'];

function hitung_imt(float $bb, float $tbCm): float
{
    $m = $tbCm / 100;
    return $m > 0 ? round($bb / ($m * $m), 2) : 0.0;
}

function klasifikasi_imt(float $imt): string
{
    if ($imt < 18.5) return 'Kurus';
    if ($imt <= 25.0) return 'Normal';
    if ($imt <= 27.0) return 'Kelebihan BB';
    return 'Obesitas';
}

function klasifikasi_tensi(int $sis, int $dia): string
{
    if ($sis < 90 || $dia < 60) return 'Hipotensi';
    if ($sis >= 160 || $dia >= 100) return 'Hipertensi 2';
    if ($sis >= 140 || $dia >= 90) return 'Hipertensi 1';
    if ($sis >= 130 || $dia >= 85) return 'Pra-hipertensi';
    return 'Normal';
}

function klasifikasi_gula(?int $gd): ?string
{
    if ($gd === null) return null;
    if ($gd < 70) return 'Rendah';
    if ($gd >= 200) return 'Tinggi';
    return 'Normal';
}

function klasifikasi_kolesterol(?int $kol): ?string
{
    if ($kol === null) return null;
    return $kol >= 200 ? 'Tinggi' : 'Normal';
}

function klasifikasi_asam_urat(?float $au, string $jk): ?string
{
    if ($au === null) return null;
    $batas = ($jk === 'P') ? 6.0 : 7.0;
    return $au > $batas ? 'Tinggi' : 'Normal';
}

/**
 * Saran status kondisi keseluruhan + apakah perlu rujukan.
 * Risiko Tinggi  : tensi Hipertensi 2/Hipotensi, gula <70 atau >=300, suhu >=38,5 atau <35,
 *                  nadi <50 atau >120.
 * Perlu Perhatian: ada salah satu parameter di luar rentang normal.
 */
function saran_kondisi(array $d): array
{
    $tinggi = (
        in_array($d['status_tensi'], ['Hipertensi 2', 'Hipotensi'], true)
        || ($d['gula_darah'] !== null && ($d['gula_darah'] < 70 || $d['gula_darah'] >= 300))
        || $d['suhu_tubuh'] >= 38.5 || $d['suhu_tubuh'] < 35.0
        || $d['detak_jantung'] < 50 || $d['detak_jantung'] > 120
    );
    if ($tinggi) {
        return ['Risiko Tinggi', 'Ya'];
    }

    $perhatian = (
        $d['status_imt'] !== 'Normal'
        || $d['status_tensi'] !== 'Normal'
        || in_array($d['status_gula_darah'], ['Rendah', 'Tinggi'], true)
        || $d['status_kolesterol'] === 'Tinggi'
        || $d['status_asam_urat'] === 'Tinggi'
        || $d['suhu_tubuh'] >= 37.6
        || $d['detak_jantung'] < 60 || $d['detak_jantung'] > 100
    );
    return [$perhatian ? 'Perlu Perhatian' : 'Aman / Sehat', 'Tidak'];
}

/** Kelas CSS badge untuk status. */
function badge_class(?string $status): string
{
    switch ($status) {
        case 'Normal':
        case 'Aman / Sehat':
        case 'aktif':
        case 'terjadwal':
            return 'badge-active';
        case 'Kelebihan BB':
        case 'Pra-hipertensi':
        case 'Perlu Perhatian':
        case 'Rendah':
        case 'Kurus':
        case 'Hipotensi':
            return 'badge-pending';
        case 'Tinggi':
        case 'Hipertensi 1':
        case 'Hipertensi 2':
        case 'Obesitas':
        case 'Risiko Tinggi':
        case 'dibatalkan':
            return 'badge-danger';
        case 'selesai':
            return 'badge-info';
        default:
            return 'badge-muted';
    }
}

function hitung_usia(string $tglLahir): int
{
    try {
        return (new DateTime($tglLahir))->diff(new DateTime('today'))->y;
    } catch (Exception $e) {
        return 0;
    }
}
