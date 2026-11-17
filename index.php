<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Waluya — Platform digital untuk membantu pengelolaan dan pemantauan kesehatan lansia di Posyandu.">
    <title>Waluya — Pemantauan Kesehatan Lansia</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css?v=1.3">
</head>

<body>

    <!-- ========== NAVBAR ========== -->
    <nav class="navbar" id="navbar">
        <div class="container navbar-inner">
            <a href="#beranda" class="navbar-logo">
                <span class="logo-icon">
                    <img src="https://i.pinimg.com/736x/98/0f/03/980f03116b6fc42b54afac95ac23904f.jpg" alt="">
                </span>
                <span class="logo-text">Waluya</span>
            </a>

            <ul class="navbar-menu" id="navMenu">
                <li><a href="#beranda" class="nav-link active">Beranda</a></li>
                <li><a href="#tentang" class="nav-link">Tentang</a></li>
                <li><a href="#layanan" class="nav-link">Layanan</a></li>
                <li><a href="#cara-kerja" class="nav-link">Cara Kerja</a></li>
                <li><a href="#faq" class="nav-link">FAQ</a></li>
                <li class="mobile-menu-action">
                    <a href="login.php" class="btn btn-primary btn-mobile-login">
                        <i data-lucide="log-in" class="btn-icon"></i>
                        <span>Masuk Akun</span>
                    </a>
                </li>
            </ul>

            <div class="navbar-actions">
                <a href="login.php" class="btn btn-login" aria-label="Login Waluya">
                    <i data-lucide="log-in" class="btn-icon"></i>
                    <span>Login</span>
                </a>
            </div>

            <button class="hamburger" id="hamburger" aria-label="Toggle menu">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
        </div>
    </nav>

    <!-- ========== HERO SECTION ========== -->
    <section class="hero" id="beranda">
        <div class="container">
            <div class="hero-inner">
                <div class="hero-content fade-in-up">
                    <span class="hero-badge">
                        <i data-lucide="shield-check" class="badge-icon"></i>
                        Platform Kesehatan Lansia Terpercaya
                    </span>
                    <h1 class="hero-title">
                        Perhatian Lebih,<br>
                        Kesehatan Lansia<br>
                        <span class="hero-title-accent">Lebih Terpantau.</span>
                    </h1>
                    <p class="hero-description">
                        Pantau riwayat pemeriksaan dan perkembangan kesehatan lansia secara digital dalam satu platform
                        yang
                        terhubung dengan keluarga dan kader Posyandu.
                    </p>
                    <div class="hero-buttons">
                        <a href="register.php" class="btn btn-primary">
                            <i data-lucide="arrow-right" class="btn-icon"></i>
                            Mulai Sekarang
                        </a>
                        <a href="login.php" class="btn btn-outline">
                            <i data-lucide="log-in" class="btn-icon"></i>
                            Masuk Akun
                        </a>
                    </div>
                    <div class="hero-stats">
                        <div class="stat-item">
                            <span class="stat-number">500+</span>
                            <span class="stat-label">Lansia Terpantau</span>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <span class="stat-number">50+</span>
                            <span class="stat-label">Posyandu Aktif</span>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <span class="stat-number">200+</span>
                            <span class="stat-label">Kader Terlatih</span>
                        </div>
                    </div>
                </div>

                <div class="hero-visual fade-in-up" style="animation-delay: 0.2s;">
                    <div class="hero-image-container">
                        <div class="hero-image-frame">
                            <img src="https://i.pinimg.com/736x/42/e1/7f/42e17f86a8f40af68f902bc1ecd51dda.jpg"
                                alt="Lansia Indonesia bersama keluarga dan kader Posyandu Waluya" class="hero-img">
                            <div class="hero-img-overlay"></div>
                        </div>

                        <!-- Floating health metric cards -->
                        <div class="floating-card card-top-right">
                            <div class="floating-icon-wrap icon-pulse">
                                <i data-lucide="heart-pulse" class="floating-icon"></i>
                            </div>
                            <div>
                                <span class="floating-label">Detak Jantung</span>
                                <span class="floating-value">78 bpm</span>
                            </div>
                        </div>
                        <div class="floating-card card-bottom-left">
                            <div class="floating-icon-wrap icon-activity">
                                <i data-lucide="activity" class="floating-icon"></i>
                            </div>
                            <div>
                                <span class="floating-label">Tekanan Darah</span>
                                <span class="floating-value">120/80</span>
                            </div>
                        </div>
                        <div class="floating-card card-mid-right">
                            <div class="floating-icon-wrap icon-temp">
                                <i data-lucide="thermometer" class="floating-icon"></i>
                            </div>
                            <div>
                                <span class="floating-label">Suhu Tubuh</span>
                                <span class="floating-value">36.5°C</span>
                            </div>
                        </div>
                    </div>
                    <!-- Decorative elements removed -->
                </div>
            </div>
        </div>
        <div class="hero-wave">
            <svg viewBox="0 0 1440 120" preserveAspectRatio="none">
                <path d="M0,60 C360,120 720,0 1080,60 C1260,90 1380,80 1440,60 L1440,120 L0,120 Z" fill="#F8FCF9" />
            </svg>
        </div>
    </section>

    <!-- ========== TENTANG WALUYA ========== -->
    <section class="about" id="tentang">
        <div class="container">
            <div class="about-inner">
                <div class="about-visual fade-in-up">
                    <div class="about-image-wrapper">
                        <img src="https://i.pinimg.com/1200x/4f/d4/36/4fd4366aa01b53d973f0e27c9d4d6d65.jpg"
                            alt="Pemeriksaan kesehatan lansia di Posyandu Waluya" class="about-img">
                        <div class="about-img-overlay"></div>
                        <div class="about-accent-card">
                            <div class="about-accent-icon-wrap">
                                <i data-lucide="shield-check" class="accent-icon"></i>
                            </div>
                            <div>
                                <span class="accent-title">Data Aman &amp; Terpercaya</span>
                                <span class="accent-desc">Standar Posyandu Lansia</span>
                            </div>
                        </div>
                        <div class="about-badge-floating">
                            <i data-lucide="activity" class="badge-float-icon"></i>
                            <span>Pemeriksaan Rutin Aktif</span>
                        </div>
                    </div>
                </div>
                <div class="about-content fade-in-up" style="animation-delay: 0.15s;">
                    <span class="section-label">
                        <i data-lucide="info" class="section-label-icon"></i>
                        Tentang Kami
                    </span>
                    <h2 class="section-title">Mengenal <span class="text-accent">Waluya</span></h2>
                    <p class="about-description">
                        Platform digital Posyandu Lansia untuk pencatatan dan pemantauan kesehatan warga lanjut usia
                        secara terstruktur, mudah diakses, dan aman bagi keluarga serta kader.
                    </p>
                    <div class="about-features">
                        <div class="about-feature-item">
                            <div class="feature-check">
                                <i data-lucide="check-circle-2" class="check-icon"></i>
                            </div>
                            <div>
                                <h4>Pencatatan Lebih Terstruktur</h4>
                                <p>Data pemeriksaan tersimpan rapi dan mudah diakses kapan saja.</p>
                            </div>
                        </div>
                        <div class="about-feature-item">
                            <div class="feature-check">
                                <i data-lucide="check-circle-2" class="check-icon"></i>
                            </div>
                            <div>
                                <h4>Riwayat Kesehatan Digital</h4>
                                <p>Seluruh riwayat pemeriksaan tersimpan secara digital dan aman.</p>
                            </div>
                        </div>
                        <div class="about-feature-item">
                            <div class="feature-check">
                                <i data-lucide="check-circle-2" class="check-icon"></i>
                            </div>
                            <div>
                                <h4>Pemantauan Lebih Mudah</h4>
                                <p>Pantau kondisi kesehatan lansia dari mana saja dengan mudah.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== LAYANAN DAN FITUR ========== -->
    <section class="services" id="layanan">
        <div class="container">
            <div class="section-header fade-in-up">
                <span class="section-label">
                    <i data-lucide="layout-grid" class="section-label-icon"></i>
                    Layanan Kami
                </span>
                <h2 class="section-title">Fitur Unggulan <span class="text-accent">Waluya</span></h2>
                <p class="section-subtitle">
                    Berbagai fitur yang dirancang untuk memudahkan pengelolaan dan pemantauan
                    kesehatan lansia secara menyeluruh.
                </p>
            </div>
            <div class="services-grid">
                <div class="service-card fade-in-up">
                    <div class="service-icon-wrap">
                        <i data-lucide="file-heart" class="service-icon"></i>
                    </div>
                    <h3 class="service-title">Rekam Medis Digital</h3>
                    <p class="service-desc">
                        Penyimpanan data pemeriksaan kesehatan lansia secara terstruktur,
                        aman, dan mudah diakses oleh kader maupun keluarga.
                    </p>
                </div>

                <div class="service-card fade-in-up" style="animation-delay: 0.1s;">
                    <div class="service-icon-wrap">
                        <i data-lucide="bar-chart-3" class="service-icon"></i>
                    </div>
                    <h3 class="service-title">Grafik Kesehatan</h3>
                    <p class="service-desc">
                        Visualisasi perkembangan hasil pemeriksaan dari waktu ke waktu
                        untuk memahami tren kesehatan lansia.
                    </p>
                </div>

                <div class="service-card fade-in-up" style="animation-delay: 0.2s;">
                    <div class="service-icon-wrap">
                        <i data-lucide="shield-alert" class="service-icon"></i>
                    </div>
                    <h3 class="service-title">Deteksi Risiko</h3>
                    <p class="service-desc">
                        Identifikasi awal kondisi kesehatan yang memerlukan perhatian
                        lebih lanjut dari tenaga medis.
                    </p>
                </div>

                <div class="service-card fade-in-up" style="animation-delay: 0.3s;">
                    <div class="service-icon-wrap">
                        <i data-lucide="users" class="service-icon"></i>
                    </div>
                    <h3 class="service-title">Pemantauan Keluarga</h3>
                    <p class="service-desc">
                        Memudahkan keluarga mengakses riwayat dan status kesehatan
                        lansia secara transparan dan real-time.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== CARA KERJA (ALUR LAYANAN) ========== -->
    <section class="how-it-works" id="cara-kerja">
        <div class="container">
            <div class="section-header fade-in-up">
                <span class="section-label">
                    <i data-lucide="route" class="section-label-icon"></i>
                    Alur Layanan
                </span>
                <h2 class="section-title">Bagaimana <span class="text-accent">Waluya</span> Bekerja?</h2>
                <p class="section-subtitle">
                    Empat langkah mudah dan praktis untuk mulai memantau perkembangan kesehatan lansia secara
                    terstruktur.
                </p>
            </div>

            <div class="process-flow fade-in-up">
                <div class="process-track-line"></div>

                <div class="process-step">
                    <div class="process-node">
                        <span class="node-number">01</span>
                    </div>
                    <div class="process-icon-box">
                        <i data-lucide="user-plus" class="process-icon"></i>
                    </div>
                    <div class="process-info">
                        <span class="process-step-tag">Langkah 1</span>
                        <h3 class="process-title">Buat Akun Keluarga</h3>
                        <p class="process-desc">
                            Daftarkan diri Anda sebagai anggota keluarga secara cepat untuk mulai menggunakan platform.
                        </p>
                    </div>
                    <div class="process-connector">
                        <i data-lucide="arrow-right" class="connector-arrow"></i>
                    </div>
                </div>

                <div class="process-step">
                    <div class="process-node">
                        <span class="node-number">02</span>
                    </div>
                    <div class="process-icon-box">
                        <i data-lucide="heart-handshake" class="process-icon"></i>
                    </div>
                    <div class="process-info">
                        <span class="process-step-tag">Langkah 2</span>
                        <h3 class="process-title">Daftarkan Lansia</h3>
                        <p class="process-desc">
                            Masukkan data profil dan kondisi awal anggota keluarga lansia yang ingin dipantau.
                        </p>
                    </div>
                    <div class="process-connector">
                        <i data-lucide="arrow-right" class="connector-arrow"></i>
                    </div>
                </div>

                <div class="process-step">
                    <div class="process-node">
                        <span class="node-number">03</span>
                    </div>
                    <div class="process-icon-box">
                        <i data-lucide="badge-check" class="process-icon"></i>
                    </div>
                    <div class="process-info">
                        <span class="process-step-tag">Langkah 3</span>
                        <h3 class="process-title">Verifikasi Kader</h3>
                        <p class="process-desc">
                            Kader Posyandu Lansia memverifikasi data dan menghubungkan jadwal pemeriksaan rutin.
                        </p>
                    </div>
                    <div class="process-connector">
                        <i data-lucide="arrow-right" class="connector-arrow"></i>
                    </div>
                </div>

                <div class="process-step">
                    <div class="process-node">
                        <span class="node-number">04</span>
                    </div>
                    <div class="process-icon-box">
                        <i data-lucide="monitor-check" class="process-icon"></i>
                    </div>
                    <div class="process-info">
                        <span class="process-step-tag">Langkah 4</span>
                        <h3 class="process-title">Pantau Kesehatan</h3>
                        <p class="process-desc">
                            Pantau riwayat tensi, gula darah, berat badan, dan grafik kesehatan secara real-time.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== FAQ SECTION ========== -->
    <section class="faq" id="faq">
        <div class="container">
            <div class="section-header fade-in-up">
                <span class="section-label">
                    <i data-lucide="help-circle" class="section-label-icon"></i>
                    Pertanyaan Umum
                </span>
                <h2 class="section-title">Pertanyaan yang Sering <span class="text-accent">Diajukan</span></h2>
                <p class="section-subtitle">
                    Temukan jawaban atas berbagai pertanyaan umum seputar fitur dan penggunaan platform Waluya.
                </p>
            </div>

            <div class="faq-container fade-in-up">
                <div class="faq-list">
                    <div class="faq-item active">
                        <button class="faq-question" type="button" aria-expanded="true">
                            <span class="faq-question-text">Apa itu platform Waluya?</span>
                            <span class="faq-chevron">
                                <i data-lucide="chevron-down" class="chevron-icon"></i>
                            </span>
                        </button>
                        <div class="faq-answer">
                            <p>
                                Waluya merupakan platform digital berbasis web yang dirancang khusus untuk membantu
                                Posyandu Lansia dan keluarga dalam mencatat, memantau, dan mengelola perkembangan
                                kesehatan warga lanjut usia secara terstruktur, praktis, dan aman.
                            </p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question" type="button" aria-expanded="false">
                            <span class="faq-question-text">Bagaimana cara keluarga memantau kesehatan lansia di
                                Waluya?</span>
                            <span class="faq-chevron">
                                <i data-lucide="chevron-down" class="chevron-icon"></i>
                            </span>
                        </button>
                        <div class="faq-answer">
                            <p>
                                Keluarga cukup membuat akun, mendaftarkan profil lansia, dan menunggu verifikasi dari
                                kader Posyandu setempat. Setelah terhubung, keluarga dapat langsung melihat riwayat
                                tensi darah, berat badan, gula darah, dan grafik perkembangan kesehatan lansia kapan
                                saja.
                            </p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question" type="button" aria-expanded="false">
                            <span class="faq-question-text">Siapa yang bertugas mencatat hasil pemeriksaan
                                kesehatan?</span>
                            <span class="faq-chevron">
                                <i data-lucide="chevron-down" class="chevron-icon"></i>
                            </span>
                        </button>
                        <div class="faq-answer">
                            <p>
                                Pencatatan hasil pemeriksaan rutin dilakukan langsung oleh kader Posyandu Lansia yang
                                terlatih saat pelaksanaan posyandu bulanan. Data yang tersimpan terverifikasi resmi
                                sehingga informasi kesehatan selalu akurat dan dapat dipercaya.
                            </p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question" type="button" aria-expanded="false">
                            <span class="faq-question-text">Apakah data medis dan kesehatan lansia terjamin
                                keamanannya?</span>
                            <span class="faq-chevron">
                                <i data-lucide="chevron-down" class="chevron-icon"></i>
                            </span>
                        </button>
                        <div class="faq-answer">
                            <p>
                                Tentu. Waluya menerapkan prinsip perlindungan privasi data kesehatan dengan akses
                                terbatas. Hanya pihak keluarga terdaftar dan kader Posyandu yang berwenang di wilayah
                                terkait yang memiliki hak akses untuk melihat rekam medis tersebut.
                            </p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question" type="button" aria-expanded="false">
                            <span class="faq-question-text">Apakah platform Waluya dapat digunakan secara gratis?</span>
                            <span class="faq-chevron">
                                <i data-lucide="chevron-down" class="chevron-icon"></i>
                            </span>
                        </button>
                        <div class="faq-answer">
                            <p>
                                Ya, platform Waluya dapat diakses dan digunakan secara gratis oleh keluarga serta kader
                                Posyandu Lansia guna mendukung peningkatan kualitas layanan kesehatan lansia di
                                Indonesia.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="faq-footer-card">
                    <div class="faq-footer-icon-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m22 2-7 20-4-9-9-4Z" />
                            <path d="M22 2 11 13" />
                        </svg>

                    </div>
                    <div class="faq-footer-info">
                        <h4 class="faq-footer-title">Masih punya pertanyaan lain?</h4>
                        <p class="faq-footer-desc">Tim kader dan layanan bantuan kami siap membantu Anda dengan senang
                            hati.</p>
                    </div>
                    <a href="mailto:info@waluya.id" class="btn btn-faq-contact">
                        <i data-lucide="mail" class="btn-icon"></i>
                        Hubungi Kami
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== FOOTER ========== -->
    <footer class="footer">
        <div class="container">
            <div class="footer-top">
                <div class="footer-brand">
                    <a href="#beranda" class="footer-logo">
                        <span class="logo-icon">
                            <img src="https://i.pinimg.com/736x/98/0f/03/980f03116b6fc42b54afac95ac23904f.jpg" alt="">
                        </span>
                        <span class="logo-text">Waluya</span>
                    </a>
                    <p class="footer-brand-desc">
                        Platform digital untuk membantu pengelolaan dan pemantauan
                        kesehatan lansia di Posyandu. Memudahkan keluarga dan kader
                        dalam memantau kondisi kesehatan secara terstruktur.
                    </p>
                </div>

                <div class="footer-links">
                    <h4 class="footer-heading">Navigasi</h4>
                    <ul>
                        <li><a href="#beranda">Beranda</a></li>
                        <li><a href="#tentang">Tentang</a></li>
                        <li><a href="#layanan">Layanan</a></li>
                        <li><a href="#cara-kerja">Cara Kerja</a></li>
                        <li><a href="#faq">FAQ</a></li>
                        <li><a href="login.php">Login</a></li>
                    </ul>
                </div>

                <div class="footer-links">
                    <h4 class="footer-heading">Posyandu</h4>
                    <ul>
                        <li><a href="#">Posyandu Lansia</a></li>
                        <li><a href="#">Jadwal Pemeriksaan</a></li>
                        <li><a href="#">Panduan Kader</a></li>
                        <li><a href="#">Informasi Kesehatan</a></li>
                    </ul>
                </div>

                <div class="footer-links">
                    <h4 class="footer-heading">Kontak</h4>
                    <ul>
                        <li>
                            <i data-lucide="mail" class="footer-contact-icon"></i>
                            <a href="mailto:info@waluya.id">info@waluya.id</a>
                        </li>
                        <li>
                            <i data-lucide="phone" class="footer-contact-icon"></i>
                            <a href="tel:+6281234567890">+62 812 3456 7890</a>
                        </li>
                        <li>
                            <i data-lucide="map-pin" class="footer-contact-icon"></i>
                            <span>Jawa Tengah, Indonesia</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Waluya. Hak cipta dilindungi.</p>
                <div class="footer-social">
                    <a href="#" class="social-link" aria-label="Instagram">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="20" height="20" x="2" y="2" rx="5" ry="5" />
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
                            <line x1="17.5" x2="17.51" y1="6.5" y2="6.5" />
                        </svg>
                    </a>
                    <a href="#" class="social-link" aria-label="Facebook">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" />
                        </svg>
                    </a>
                    <a href="#" class="social-link" aria-label="Youtube">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path
                                d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17" />
                            <path d="m10 15 5-3-5-3z" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="assets/js/script.js"></script>
</body>

</html>