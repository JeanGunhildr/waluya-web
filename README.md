# Waluya - Platform Digital Pemantauan Kesehatan Posyandu Lansia

<div align="center">
  <img src="assets/images/logo.png" alt="Logo Waluya" width="120" style="margin-bottom: 12px;" />
  <h3>Platform Digital Terpadu untuk Pemantauan Kesehatan Lansia dan Pelayanan Posyandu</h3>
  <p>Mewujudkan Lansia Sehat, Aktif, Mandiri, dan Bahagia melalui digitalisasi Kartu Menuju Sehat (KMS) Lansia.</p>
</div>

---

## 📋 Tentang Proyek

**Waluya** adalah sistem informasi berbasis web yang dirancang untuk mendukung pelayanan kesehatan lanjut usia pada Posyandu Lansia. Sistem ini menghubungkan tiga pihak utama—**Administrator**, **Kader Posyandu**, dan **Keluarga/Wali Lansia**—dalam satu ekosistem digital terpadu guna memantau riwayat kesehatan, deteksi dini risiko penyakit tidak menular (hipertensi, diabetes, status gizi/IMT), serta penjadwalan pemeriksaan rutin secara transparan dan akurat.

---

## 👥 Tim Pengembang (Anggota Kelompok & NIM)

> **Universitas Jenderal Soedirman**

| No | Foto / Inisial | Nama Lengkap | NIM | Tanggung Jawab Utama |
|:---:|:---:|:---|:---:|:---|
| 1 | 👨‍💻 | **Muhammad Rifqi Agus Setianto** | *[H1D025113]* | Landing Page Publik & Seluruh Modul Dashboard Administrator |
| 2 | 👩‍💻 | **Aulia Rahma Feriza** | *[H1D025108]* | Seluruh Modul Dashboard Kader Posyandu |
| 3 | 👩‍💻 | **Shabila Saqina Bahari** | *[H1D025158]* | Modul Autentikasi Sistem & Seluruh Modul Pemantauan Keluarga (KMS Digital) |

### 📌 Rincian Modul yang Dikerjakan Setiap Anggota Tim:

#### 1. 👨‍💻 Muhammad Rifqi Agus Setianto (NIM: H1D025113)
*Penanggung Jawab: Landing Page Publik & Seluruh Modul Dashboard Administrator*
- **Landing Page Publik (`index.php`)**: Hero Section interaktif, Tentang Waluya, Layanan Unggulan Posyandu, Alur 4 Langkah Pelayanan, FAQ Interaktif, dan Footer Informasi.
- **Dashboard Utama Administrator (`admin/index.php`)**: Kartu metrik KPI eksekutif (Lansia, Antrean ACC, Keluarga, Kader), antrean cepat verifikasi keluarga, tabel ringkasan skrining rekam medis terbaru, dan agenda posyandu terdekat.
- **Modul Verifikasi & Persetujuan Akun Keluarga (`admin/acc-keluarga.php` & `admin/verifikasi-keluarga.php`)**: Manajemen antrean registrasi keluarga berstatus *menunggu_acc*, verifikasi NIK KTP & domisili, aksi *Approve* (Setujui) & *Reject* (Tolak) permohonan akun, serta audit log.
- **Modul Manajemen Akun Keluarga (`admin/keluarga.php`)**: CRUD akun keluarga/wali lansia, pemetaan hubungan wali dengan warga lansia, filter status akun, dan reset profil.
- **Modul Master Data Warga Lansia (`admin/lansia.php`)**: CRUD master demografi lansia, hitung umur otomatis (*auto-calculated age*), penautan unit posyandu binaan, dan penugasan wali penanggung jawab.
- **Modul Manajemen Kader Posyandu (`admin/kader.php`)**: Pendataan kader aktif, penetapan wilayah unit posyandu binaan, dan manajemen akun petugas lapangan.
- **Modul Manajemen Unit Posyandu (`admin/posyandu.php`)**: Master titik pelayanan posyandu per dusun/RW, pemetaan beban lansia binaan, dan alokasi kader bertugas.
- **Modul Manajemen Jadwal Posyandu (`admin/jadwal.php`)**: Penjadwalan agenda pelayanan posyandu berkala, penugasan kader, pengaturan status kegiatan, dan kalender kegiatan.
- **Modul Rekam Medis Global (`admin/riwayat.php`)**: Pusat arsip riwayat pemeriksaan fisik (RUD) seluruh lansia lintas posyandu dengan modal detail & filter tingkat risiko.

#### 2. 👩‍💻 Aulia Rahma Feriza (NIM: H1D025108)
*Penanggung Jawab: Seluruh Modul Dashboard Kader Posyandu*
- **Dashboard Utama Kader (`kader/index.php`)**: Ringkasan aktivitas pemeriksaan bulanan kader, pengingat jadwal tugas hari ini, tombol pintas input pemeriksaan, dan feed kunjungan terbaru.
- **Modul Input Pemeriksaan & Skrining Lansia (`kader/input-pemeriksaan.php`)**: Form pengukuran antropometri (Berat Badan, Tinggi Badan, IMT otomatis), tanda vital (Tekanan Darah Sistol/Diastol), tes laboratorium POCT (Gula Darah, Kolesterol Total, Asam Urat), anamnesis keluhan, sistem klasifikasi risiko otomatis (*Aman*, *Perlu Perhatian*, *Risiko Tinggi*), serta catatan edukasi dan rujukan.
- **Modul Data Pasien Lansia Binaan (`kader/lansia.php`)**: Direktori seluruh warga lansia di wilayah kerja posyandu binaan, monitoring riwayat penyakit, dan akses cepat kontak keluarga.
- **Modul Profil & KMS Digital Lansia (`kader/lansia-detail.php`)**: Kartu Menuju Sehat (KMS) digital individual per pasien, histori pemeriksaan berkesinambungan, dan grafik tren kesehatan lansia.
- **Modul Riwayat & Log Pemeriksaan Kader (`kader/riwayat.php`)**: Arsip riwayat pemeriksaan fisik yang telah diinputkan oleh kader dengan filter tanggal dan kategori risiko kesehatan.
- **Modul Agenda & Jadwal Tugas Kader (`kader/jadwal.php`)**: Kalender tugas operasional kader, detail waktu pelaksanaan posyandu, titik lokasi pos, dan checklist persiapan alat.

#### 3. 👩‍💻 Shabila Saqina Bahari (NIM: H1D025158)
*Penanggung Jawab: Modul Autentikasi Sistem & Seluruh Modul Pemantauan Keluarga (KMS Digital)*
- **Modul Autentikasi Pengguna Multi-Role (`login.php`)**: Portal login tunggal untuk 3 level pengguna (Admin, Kader, Keluarga), sistem pengalihan dinamis (*role-based redirect*), proteksi status akun keluarga terverifikasi, dan enkripsi password bcrypt.
- **Modul Pendaftaran Akun Keluarga Mandiri (`register.php`)**: Formulir registrasi mandiri untuk wali lansia, validasi keunikan NIK KTP (16 digit) & email, serta integrasi otomatis ke antrean verifikasi admin berstatus *menunggu_acc*.
- **Modul Logout & Audit Sesi (`logout.php`)**: Terminasi sesi aman server-side (*session destroy* & cookie clean-up) serta pencatatan audit log aktivitas login/logout.
- **Modul Dashboard Pemantauan Keluarga (`keluarga/index.php`)**: Tampilan informasi lansia yang diasuh, ringkasan hasil pengukuran fisik terakhir (tensi, gula darah, berat badan, IMT), badge indikator status risiko kesehatan visual, dan pengingat jadwal posyandu terdekat.
- **Modul Riwayat Rekam Medis & KMS Digital Keluarga (`keluarga/riwayat.php`)**: Kartu Menuju Sehat (KMS) digital keluarga pengganti kartu fisik, riwayat pemeriksaan vital bulanan, serta catatan anjuran diet, obat, dan pola hidup dari kader.
- **Modul Analisis Deteksi Dini & Edukasi Risiko (`keluarga/risiko.php`)**: Modul edukasi makna status risiko orang tua, panduan perawatan & nutrisi lansia di rumah, serta panduan deteksi dini tanda bahaya kegawatdaruratan (*red flags*).
- **Modul Kalender & Informasi Jadwal Posyandu (`keluarga/jadwal.php`)**: Kalender jadwal pelayanan posyandu lansia di lingkungan tempat tinggal agar keluarga dapat mendampingi lansia tepat waktu.
- **Modul Profil Akun & Hubungan Keluarga (`keluarga/profil.php`)**: Pengelolaan data diri wali, status hubungan keluarga dengan lansia (anak/menantu/cucu/wali), dan fitur pembaruan kata sandi akun.

---

## 🖥️ Rincian Modul Sistem per Dashboard

Sistem Waluya dibangun dengan arsitektur modular berbasis peran pengguna (*Role-Based Access Control*). Berikut adalah rincian modul-modul lengkap yang tersedia pada masing-masing dashboard:

---

### 🛡️ A. Modul Dashboard Administrator (`admin/`)

Dashboard Administrator bertindak sebagai pusat kendali operasional, validasi data, manajemen master data, dan audit sistem posyandu.

#### 1. 📊 Modul Dashboard Utama & Analytics (`admin/index.php`)
- **Kartu Metrik KPI Eksekutif**: Menampilkan ringkasan statistik *real-time* mencakup total lansia terdaftar, antrean keluarga yang menunggu verifikasi (dengan badge indikator alert), total keluarga aktif/disetujui, dan total kader posyandu yang bertugas.
- **Widget Quick Action Antrean Verifikasi Keluarga**: Tabel pendaftaran keluarga terbaru dengan tombol aksi langsung untuk **Setujui (ACC)** atau **Tolak** pendaftaran tanpa harus berpindah halaman.
- **Widget Pemantauan Pemeriksaan & Skrining Terbaru**: Menampilkan data vital lansia dari hasil posyandu terkini (tekanan darah, kadar gula darah, IMT, status risiko, dan nama kader pemeriksa) serta tombol modal detail pemeriksaan.
- **Widget Agenda Posyandu Terdekat**: Ringkasan jadwal posyandu mendatang beserta lokasi dan unit pelaksana.

#### 2. ✅ Modul Verifikasi & Persetujuan Akun Keluarga (`admin/acc-keluarga.php`)
- **Manajemen Antrean Verifikasi (Pending Queue)**: Menampilkan seluruh data pemohon akun keluarga berstatus *menunggu_acc*.
- **Validasi Berkas & Identitas Pemohon**: Pengecekan kesesuaian Nomor Induk Kependudukan (NIK KTP 16 digit), nama lengkap wali, nomor kontak WhatsApp/telepon aktif, dan domisili (RT/RW, dusun, kelurahan).
- **Aksi Persetujuan (Approve)**: Mengubah status akun keluarga menjadi aktif secara instan sehingga keluarga dapat langsung masuk ke sistem.
- **Aksi Penolakan (Reject)**: Menolak permohonan pendaftaran yang tidak valid disertai catatan evaluasi.
- **Sistem Audit Log**: Setiap aksi persetujuan atau penolakan dicatat ke dalam log aktivitas admin untuk kebutuhan akuntabilitas.

#### 3. 👨‍👩‍👧 Modul Manajemen Akun Keluarga (`admin/keluarga.php`)
- **Master Data Akun Keluarga**: Direktori seluruh akun keluarga terdaftar, baik yang berstatus aktif, nonaktif, maupun ditolak.
- **Pemetaan Relasi Wali & Lansia**: Melihat daftar warga lansia yang berada di bawah perwalian dan tanggung jawab akun keluarga bersangkutan.
- **Operasi CRUD Akun Keluarga**: Tambah akun keluarga secara manual oleh admin, ubah profil dan kontak darurat, reset status akun, serta penghapusan akun yang tidak aktif.
- **Filter & Pencarian Akun**: Penelusuran cepat berdasarkan nama wali, email, NIK, atau status persetujuan akun.

#### 4. 🧓 Modul Master Data Warga Lansia (`admin/lansia.php`)
- **Pendataan Komprehensif Demografi Lansia**: Pencatatan NIK, nama lengkap, tempat dan tanggal lahir, kalkulasi usia otomatis (*auto-calculated age*), jenis kelamin, golongan darah, dan alamat domisili.
- **Penautan Wali Keluarga & Posyandu Binaan**: Menghubungkan profil lansia ke akun keluarga penanggung jawab dan menugaskannya ke posyandu terdekat.
- **Pencarian & Multi-Filter**: Filter lansia berdasarkan unit posyandu, rentang usia, dan pencarian instan nama/NIK.
- **Operasi CRUD Lansia**: Tambah lansia baru, edit informasi medis dasar, dan hapus data lansia dengan proteksi integritas data relasional.

#### 5. 🩺 Modul Manajemen Kader Posyandu (`admin/kader.php`)
- **Direktori Petugas & Kader**: Pengelolaan data seluruh kader posyandu yang aktif membina kesehatan warga lansia.
- **Penugasan Wilayah & Unit Posyandu**: Menetapkan wilayah tugas kader pada titik unit posyandu tertentu.
- **Manajemen Akun & Hak Akses Kader**: Pembuatan kredensial login kader, pembaruan kontak telepon, serta pengaktifan/penonaktifan hak akses kader.
- **Monitoring Keaktifan Kader**: Pelacakan riwayat keaktifan kader dalam melakukan input pemeriksaan dan penjadwalan.

#### 6. 🏥 Modul Manajemen Unit Posyandu (`admin/posyandu.php`)
- **Master Data Titik Layanan**: Pendataan posyandu lansia di setiap dusun/RW (nama posyandu, alamat lengkap, RT/RW, kapasitas layanan).
- **Pemetaan Beban Layanan**: Menghitung secara otomatis jumlah lansia binaan dan jumlah kader bertugas di masing-masing unit posyandu.
- **Operasi CRUD Unit Posyandu**: Penambahan titik posyandu baru, perbaikan data operasional, dan penonaktifan unit posyandu.

#### 7. 📅 Modul Manajemen Jadwal Pelayanan Posyandu (`admin/jadwal.php`)
- **Penjadwalan Agenda Kegiatan**: Pembuatan agenda posyandu berkala mencakup tanggal, jam mulai-selesai, unit posyandu, lokasi pos, dan tema/agenda kegiatan posyandu.
- **Penugasan Tim Kader Lapangan**: Menentukan kader-kader penanggung jawab yang bertugas pada setiap kegiatan.
- **Status Pelaksanaan Kegiatan**: Pengaturan progres agenda (*Mendatang*, *Sedang Berlangsung*, *Selesai*, atau *Dibatalkan*).
- **Kalender & Filter Agenda**: Penelusuran jadwal berdasarkan unit posyandu dan rentang tanggal pelaksanaan.

#### 8. 📋 Modul Riwayat Rekam Medis Global (`admin/riwayat.php`)
- **Pusat Arsip Rekam Medis (RUD)**: Menampilkan seluruh histori pemeriksaan fisik lansia dari seluruh posyandu di bawah satu wilayah kerja.
- **Parameter Kesehatan Lengkap**: Tekanan Darah (Sistolik & Diastolik), Berat Badan, Tinggi Badan, IMT, Gula Darah Sewaktu/Puasa, Kolesterol Total, Asam Urat, Keluhan Subjektif, dan Catatan Rekomendasi/Rujukan.
- **Monitoring Status Risiko Wilayah**: Klasifikasi otomatis status risiko kesehatan (*Aman/Sehat*, *Perlu Perhatian*, *Risiko Tinggi*).
- **Modal Detail Rekam Medis**: Peninjauan kartu rekam medis lengkap per kunjungan serta filter pencarian berdasarkan lansia, posyandu, atau kategori risiko.

---

### 🩺 B. Modul Dashboard Kader Posyandu (`kader/`)

Dashboard Kader Posyandu dirancang untuk kecepatan input, operasional pemeriksaan di lapangan, skrining risiko otomatis, dan pemantauan riwayat kesehatan warga binaan.

#### 1. 📊 Modul Dashboard Utama Kader (`kader/index.php`)
- **Ringkasan Aktivitas Lapangan**: Informasi jumlah lansia yang dibina di wilayah kerja kader, total pemeriksaan yang telah dilakukan pada bulan berjalan, serta jumlah lansia kategori risiko tinggi yang perlu pemantauan ekstra.
- **Pengingat Jadwal Tugas Hari Ini**: Notifikasi lokasi unit posyandu dan waktu pelaksanaan tugas pemeriksaan kader hari ini.
- **Shortcut Aksi Cepat**: Tombol pintas untuk langsung membuka form input pemeriksaan fisik lansia baru.
- **Daftar Kunjungan Terbaru**: Tabel pasien lansia yang baru saja selesai diperiksa beserta ringkasan status kesehatannya.

#### 2. 📝 Modul Input Pemeriksaan Fisik & Skrining Lansia (`kader/input-pemeriksaan.php`)
- **Pencarian Data Pasien Cepat**: Autocomplete / pencarian nama dan NIK lansia binaan sebelum memulai pemeriksaan.
- **Input Pengukuran Antropometri**:
  - Berat Badan (kg) dan Tinggi Badan (cm).
  - Kalkulasi otomatis Indeks Massa Tubuh (IMT / BMI).
  - Interpretasi otomatis status gizi lansia (*Kurus/Kurang Gizi*, *Normal*, *Kelebihan Berat Badan*, *Obesitas*).
- **Input Tanda-Tanda Vital & Hemodinamik**:
  - Tekanan Darah Sistolik dan Diastolik (mmHg).
  - Interpretasi otomatis tensi (*Hipotensi*, *Normal*, *Pra-Hipertensi*, *Hipertensi Derajat 1/2*).
- **Input Uji Laboratorium Sederhana (POCT - Point of Care Testing)**:
  - Kadar Gula Darah Sewaktu / Puasa (mg/dL).
  - Kadar Kolesterol Total (mg/dL).
  - Kadar Asam Urat (mg/dL).
- **Pencatatan Anamnesis Keluhan**: Formulir pencatatan keluhan fisik subjektif lansia (misal: pusing, nyeri persendian, sesak napas, kesemutan).
- **Algoritma Klasifikasi Risiko Otomatis**: Sistem secara otomatis menentukan status risiko:
  - 🟢 **Aman / Sehat**: Semua parameter dalam batas normal.
  - 🟡 **Perlu Perhatian**: Ditemukan pra-hipertensi, kadar gula darah/asam urat di atas ambang normal ringan, atau masalah IMT.
  - 🔴 **Risiko Tinggi**: Tekanan darah hipertensi tinggi atau kadar gula darah tinggi yang berisiko komplikasi.
- **Rekomendasi, Edukasi & Rujukan**: Input catatan konseling gizi, anjuran gaya hidup, serta catatan rujukan ke Puskesmas/Dokter jika diperlukan penanganan medis lanjutan.

#### 3. 👥 Modul Direktori & Profil Pasien Lansia (`kader/lansia.php` & `kader/lansia-detail.php`)
- **Direktori Pasien Wilayah Binaan**: Daftar lengkap lansia di wilayah posyandu kader dilengkapi data umur, alamat, dan kontak keluarga.
- **Kartu Menuju Sehat (KMS) Digital Individu (`lansia-detail.php`)**: Profil komprehensif pasien lansia yang menyajikan:
  - Riwayat penyakit sebelumnya dan faktor risiko keluarga.
  - Grafik tren perkembangan berat badan, tekanan darah, dan gula darah antar-kunjungan.
  - Riwayat lengkap seluruh sesi pemeriksaan posyandu yang pernah dijalani pasien.
  - Akses cepat kontak darurat keluarga/wali pendamping.

#### 4. 🗂️ Modul Riwayat & Log Pemeriksaan Kader (`kader/riwayat.php`)
- **Arsip Pemeriksaan Kader**: Daftar seluruh catatan pemeriksaan yang pernah diinputkan oleh kader bersangkutan.
- **Fitur Filter & Penelusuran**: Filter berdasarkan rentang tanggal kegiatan, status risiko kesehatan lansia, dan pencarian nama lansia.
- **Modal Detail Hasil Pemeriksaan**: Menampilkan rincian parameter fisik dan catatan konseling yang telah tersimpan.

#### 5. 🗓️ Modul Agenda Jadwal Posyandu Kader (`kader/jadwal.php`)
- **Jadwal Tugas Posyandu**: Menampilkan kalender kegiatan posyandu di mana kader dijadwalkan bertugas.
- **Rincian Lokasi & Perlengkapan**: Memberikan informasi waktu pelaksanaan, titik lokasi pos pelayanan, serta perlengkapan kesehatan posyandu yang harus disiapkan.

---

### 👨‍👩‍👧 C. Modul Dashboard Keluarga / Wali Lansia (`keluarga/`)

Dashboard Keluarga dirancang ramah pengguna (*user-friendly*) agar keluarga dapat memantau kesehatan orang tua secara mandiri, transparan, dan berkelanjutan dari rumah.

#### 1. 📊 Modul Dashboard Utama Keluarga (`keluarga/index.php`)
- **Kartu Informasi Orang Tua / Lansia**: Menampilkan data dasar lansia yang berada dalam asuhan wali (nama lengkap, usia, posyandu binaan).
- **Indikator Status Vital Terkini**: Ringkasan hasil pemeriksaan fisik terakhir meliputi angka tensi darah, gula darah, berat badan/IMT, dan kolesterol.
- **Badge Status Risiko Kesehatan**: Status kondisi kesehatan dengan indikator warna yang mudah dipahami (*Aman*, *Perlu Perhatian*, atau *Risiko Tinggi*).
- **Pengingat Jadwal Posyandu Terdekat**: Pengingat agenda posyandu lansia berikutnya agar wali dapat mengantar orang tua tepat waktu.

#### 2. 📖 Modul Riwayat Rekam Medis & KMS Digital Keluarga (`keluarga/riwayat.php`)
- **KMS Lansia Digital Interaktif**: Menggantikan buku fisik KMS dengan buku catatan digital yang aman dan tidak dapat hilang.
- **Histori Perkembangan Kesehatan**: Catatan pemeriksaan fisik dari bulan ke bulan untuk melihat perkembangan kesehatan lansia.
- **Rincian Hasil Pemeriksaan Fisik**: Angka tensi, gula darah, asam urat, status gizi, dan keluhan yang dialami orang tua.
- **Catatan & Pesan dari Kader**: Membaca instruksi dan saran kesehatan yang diberikan oleh kader posyandu (misal: pantangan makanan, tips minum obat teratur, anjuran istirahat).

#### 3. 🛡️ Modul Analisis Deteksi Dini & Panduan Risiko (`keluarga/risiko.php`)
- **Penjelasan Status Kesehatan Lansia**: Edukasi mendalam mengenai makna di balik kategori risiko kesehatan orang tua.
- **Panduan Pendampingan Lansia di Rumah**: Tips nutrisi seimbang khusus lansia (rendah garam, rendah gula, cukup serat), panduan senam/olahraga lansia ringan, dan tips menjaga kesehatan mental lansia.
- **Deteksi Dini Tanda Bahaya (Red Flags)**: Edukasi mengenali tanda-tanda darurat (gejala stroke dini, serangan jantung, hipoglikemia berat) yang memerlukan penanganan IGD/puskesmas segera.

#### 4. 📅 Modul Jadwal Kegiatan Posyandu Keluarga (`keluarga/jadwal.php`)
- **Kalender Agenda Posyandu Lingkungan**: Jadwal buka posyandu di lingkungan tempat tinggal keluarga.
- **Rincian Agenda Layanan**: Informasi waktu buka posyandu, alamat lokasi, jenis layanan pemeriksaan yang diselenggarakan, dan nama kader bertugas.

#### 5. ⚙️ Modul Profil Akun & Hubungan Keluarga (`keluarga/profil.php`)
- **Informasi Biodata Wali**: Informasi akun keluarga (nama lengkap, NIK, nomor telepon/WhatsApp, dan alamat domisili).
- **Status Hubungan Keluarga**: Informasi status relasi dengan lansia yang diasuh (misal: anak kandung, menantu, cucu, atau wali keluarga).
- **Keamanan Akun Wali**: Fitur pembaruan kata sandi (*change password*) untuk keamanan akun.

---

### 🌐 D. Modul Portal Publik & Autentikasi Sistem

Modul yang dapat diakses publik untuk edukasi masyarakat dan pintu gerbang keamanan sistem.

#### 1. 🌍 Modul Landing Page Publik (`index.php`)
- **Navbar Navigasi Interaktif**: Navigasi responsif menuju seksi Beranda, Tentang Waluya, Layanan Unggulan, Alur Pelayanan, dan FAQ.
- **Hero Banner & Call-to-Action**: Slogan kepedulian posyandu lansia dengan tombol CTA untuk mendaftar akun keluarga atau login ke sistem.
- **Seksi Edukasi & Layanan Unggulan**: Informasi mengenai program pemeriksaan berkala lansia, KMS digital, skrining penyakit tidak menular (PTM), dan penjadwalan terpadu.
- **Alur Pelayanan 4 Langkah**: Penjelasan alur posyandu modern (1. Registrasi Akun -> 2. Verifikasi Data -> 3. Pemeriksaan Posyandu -> 4. Pemantauan Real-time via Gawai).
- **Akordion Tanya Jawab (FAQ)**: Jawaban atas pertanyaan umum masyarakat seputar pelayanan posyandu dan penggunaan platform Waluya.
- **Footer Informasi & Kontak**: Kontak layanan posyandu, jam operasional, alamat pos, dan informasi legalitas.

#### 2. 🔐 Modul Autentikasi Pengguna (`login.php`)
- **Single Portal Login**: Pintu masuk terpadu untuk Administrator, Kader Posyandu, dan Keluarga.
- **Pemisahan Jalur Berdasarkan Role (*Role-Based Redirection*)**: Mengarahkan pengguna secara otomatis ke dashboard yang tepat setelah login berhasil.
- **Proteksi Status Akun Keluarga**: Pengecekan otomatis status verifikasi. Pengguna yang masih *menunggu_acc* atau berstatus *ditolak* akan menerima pesan informasi status tanpa bisa mengakses data sensitif.
- **Keamanan Hashing Bcrypt & Proteksi Brute Force**: Enkripsi password menggunakan `password_hash()` bawaan PHP standar industri.

#### 3. 📝 Modul Pendaftaran Akun Keluarga Mandiri (`register.php`)
- **Formulir Pendaftaran Step-by-Step**: Pengisian biodata nama lengkap, email unik, NIK KTP (validasi panjang 16 digit), nomor telepon/WhatsApp aktif, alamat tinggal, dan konfirmasi password.
- **Validasi Integritas Data**: Mencegah duplikasi email atau NIK ganda di basis data.
- **Alur Otomatisasi Status Menunggu Verifikasi**: Akun yang baru mendaftar langsung berstatus *menunggu_acc* dan otomatis masuk ke tabel antrean verifikasi di dashboard admin.

#### 4. 🚪 Modul Logout & Terminasi Sesi (`logout.php`)
- **Pembersihan Sesi Aman**: Penghapusan data session server-side, unset cookie sesi, dan perusakan session token (`session_destroy`).
- **Audit Logging Otomatis**: Setiap aktivitas logout tercatat pada tabel `log_aktivitas` dengan mencantumkan ID pengguna, alamat IP (*IP Address*), dan browser (*User Agent*).

---

## 🛠️ Arsitektur & Teknologi

| Komponen | Teknologi | Keterangan |
|---|---|---|
| **Bahasa Pemrograman** | PHP Native (>= 8.0) | Menggunakan PDO (*PHP Data Objects*) dengan *Prepared Statements* untuk keamanan SQL Injection |
| **Basis Data** | MySQL / MariaDB | Relasional database dengan integrasi Foreign Keys & ON DELETE/CASCADE terstruktur |
| **Gaya & Desain Antarmuka** | Custom Modern CSS3 | Palet warna *Sage Green* medis (`#2D5A43`, `#5A8C74`), ramah mata dan lansia, tata letak responsif |
| **Tipografi & Ikon** | Google Fonts (*Plus Jakarta Sans*), Lucide Icons | Ikonografi modern dan font yang mudah dibaca |
| **Web Server** | Apache (XAMPP Environment) | Kompatibel penuh dengan local server XAMPP / Laragon / LAMP |

---

## 📂 Struktur Direktori

```text
waluya/
├── admin/                  # Modul & Halaman untuk Administrator
│   ├── acc-keluarga.php    # Modul persetujuan & verifikasi akun keluarga baru
│   ├── index.php           # Dashboard utama & ringkasan analytics admin
│   ├── jadwal.php          # Modul manajemen jadwal posyandu
│   ├── kader.php           # Modul manajemen data & penugasan kader
│   ├── keluarga.php        # Modul manajemen master akun keluarga
│   ├── lansia.php          # Modul manajemen data demografi lansia
│   ├── posyandu.php        # Modul manajemen titik & unit posyandu
│   ├── riwayat.php         # Modul riwayat rekam medis (RUD) seluruh lansia
│   └── verifikasi-keluarga.php # Handler aksi cepat verifikasi pendaftaran
├── assets/                 # Aset statis aplikasi
│   ├── css/                # Stylesheet modular (style.css, auth.css, dashboard.css)
│   ├── images/             # Gambar & Logo resmi (logo.png)
│   └── js/                 # Skrip client-side & interaktivitas
├── components/             # Komponen antarmuka modular
│   ├── kader-layout-top.php
│   ├── kader-sidebar.php
│   └── sidebar.php         # Sidebar universal berbasis role pengguna
├── config/                 # Konfigurasi aplikasi
│   └── database.php        # Koneksi PDO MySQL & fungsi utilitas
├── kader/                  # Modul & Halaman untuk Kader Posyandu
│   ├── index.php           # Dashboard utama kader posyandu
│   ├── input-pemeriksaan.php # Form input data vital, POCT lab, & skrining risiko
│   ├── jadwal.php          # Jadwal tugas kader posyandu
│   ├── lansia-detail.php   # Profil detail & riwayat pemeriksaan per lansia (KMS)
│   ├── lansia.php          # Direktori data lansia binaan kader
│   └── riwayat.php         # Riwayat pemeriksaan yang dilakukan kader
├── keluarga/               # Modul & Halaman untuk Keluarga / Wali
│   ├── index.php           # Dashboard utama pemantauan kesehatan keluarga
│   ├── jadwal.php          # Jadwal kegiatan posyandu di lingkungan keluarga
│   ├── profil.php          # Pengaturan profil wali & ubah password
│   ├── risiko.php          # Modul deteksi dini, edukasi risiko, & tips rawat lansia
│   └── riwayat.php         # Riwayat rekam medis digital (KMS Digital)
├── database.sql            # Skrip skema database lengkap & data awal
├── index.php               # Halaman utama (Landing Page publik)
├── login.php               # Halaman autentikasi login pengguna multi-role
├── logout.php              # Skrip proses logout aman & pencatatan log
├── register.php            # Halaman pendaftaran akun keluarga baru
└── README.md               # Dokumentasi lengkap proyek
```

---

## 🚀 Panduan Instalasi & Penggunaan

### 1. Prasyarat Sistem
- **Web Server**: Apache via [XAMPP](https://www.apachefriends.org/) atau Laragon.
- **PHP**: Versi 8.0 ke atas dengan ekstensi `pdo_mysql` aktif.
- **MySQL/MariaDB**: Port default 3306.
- **Web Browser**: Google Chrome, Mozilla Firefox, Microsoft Edge, atau browser modern lainnya.

### 2. Langkah Instalasi

1. **Unduh / Clone Repositori**
   Tempatkan folder proyek ke dalam direktori web server lokal:
   ```bash
   # Masuk ke direktori htdocs XAMPP
   cd C:\xampp\htdocs

   # Clone repositori (atau salin folder proyek secara manual)
   git clone https://github.com/JeanGunhildr/waluya.git waluya
   ```

2. **Jalankan Apache & MySQL**
   Buka panel kontrol **XAMPP Control Panel**, lalu klik tombol **Start** pada modul **Apache** dan **MySQL**.

3. **Impor Basis Data (Database)**
   - Buka browser dan akses [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
   - Buat basis data baru bernama: `waluya_db`.
   - Pilih database `waluya_db`, klik tab **Import**.
   - Pilih file `database.sql` yang ada di root direktori proyek, lalu klik tombol **Import** / **Kirim**.

4. **Konfigurasi Database (Jika Diperlukan)**
   Periksa konfigurasi koneksi pada file `config/database.php`:
   ```php
   $host = 'localhost';
   $db   = 'waluya_db';
   $user = 'root';
   $pass = ''; // Biarkan kosong jika default XAMPP
   ```

5. **Akses Aplikasi**
   Buka web browser dan arahkan ke:
   ```text
   http://localhost/waluya
   ```

---

## 🔑 Akun Uji Coba (Demo Credentials)

Untuk memudahkan proses pengujian dan presentasi, gunakan akun bawaan yang telah disediakan:

| Peran (Role) | Email Login | Password | Keterangan |
|---|---|---|---|
| **Administrator** | `admin@waluya.id` | `password123` | Akses penuh seluruh modul dan persetujuan user |
| **Kader Posyandu** | `siti.kader@gmail.com` | `password123` | Akses input pemeriksaan fisik & jadwal |
| **Keluarga Lansia** | `budi.santoso@gmail.com` | `password123` | Akun keluarga terverifikasi (Memantau orang tua) |

---

## 🔒 Keamanan & Praktik Terbaik

- **Password Hashing**: Menggunakan algoritma enkripsi standar industri `PASSWORD_BCRYPT`.
- **Prepared Statements**: Seluruh kueri basis data menggunakan PDO parameter binding untuk mencegah ancaman *SQL Injection*.
- **XSS Protection**: Seluruh output data dinamis difilter menggunakan `htmlspecialchars()`.
- **Session Security**: Validasi sesi di setiap halaman dengan pemisahan hak akses per role pengguna.
- **Activity Logging**: Transaksi penting tercatat otomatis pada tabel `log_aktivitas`.

---

<div align="center">
  <p>© 2026 <b>Waluya</b> - Posyandu Lansia Digital. Dibuat untuk pemenuhan tugas akademik.</p>
</div>
