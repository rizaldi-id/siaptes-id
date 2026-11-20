<?php
// index.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/articles_helper.php';
require_once __DIR__ . '/includes/tryout_blueprint.php';

$db = getDB();
ensureArticlesTable($db);

// Fetch 3 latest published articles for homepage SEO & engagement
$latestArticles = $db->query("
    SELECT id, title, slug, category, excerpt, author_name, reading_time, views, created_at 
    FROM articles 
    WHERE is_published = 1 
    ORDER BY is_featured DESC, created_at DESC, id DESC 
    LIMIT 3
")->fetchAll();
$articleCats = getArticleCategories();

// Count statistics
$totalQuestions = $db->query("SELECT COUNT(*) FROM questions")->fetchColumn();
$totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalAttempts = $db->query("SELECT COUNT(*) FROM exam_attempts WHERE status = 'completed'")->fetchColumn();

// Fetch active packages
$packages = $db->query("
    SELECT p.*, (SELECT COUNT(*) FROM questions WHERE package_id = p.id) as question_count 
    FROM packages p 
    WHERE is_active = 1 
    ORDER BY p.id ASC
")->fetchAll();

$page_title = "Satu Platform untuk Semua Tes: CPNS, BUMN, Kedinasan & Psikotes";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section SIAP TES (siaptes.id) -->
<div class="hero">
    <div class="hero-pill">
        🇮🇩 SATU PLATFORM, SIAP SEMUA SELEKSI NASIONAL &amp; KARIER &bull; <b>SIAPTES.ID</b>
    </div>
    <h1>Belajar Lebih Tenang &amp; Terarah Bersama <span>SIAP TES</span></h1>
    <p>
        Platform persiapan dan simulasi ujian <b>Computer Assisted Test (CAT)</b> yang nyaman dibaca dan mudah digunakan.
        Satu akun untuk latihan soal dan tryout <b>CPNS / PPPK</b>, <b>Rekrutmen Bersama BUMN</b>, <b>Sekolah Kedinasan</b>, serta <b>Psikotes Kerja</b>.
    </p>
    <div style="display: flex; gap: 0.85rem; justify-content: center; flex-wrap: wrap;">
        <a href="tryout.php" class="btn btn-primary" style="font-size: 1rem; padding: 0.85rem 1.75rem;">
            🚀 Mulai Simulasi Tryout
        </a>
        <a href="tes-potensi.php" class="btn btn-outline" style="font-size: 1rem; padding: 0.85rem 1.55rem; background: #eaf7f2; color: #196b52; border-color: #b7e3d4;">
            ✨ Tes Kemampuan &amp; Potensi (Gratis)
        </a>
        <a href="latihan.php" class="btn btn-outline" style="font-size: 1rem; padding: 0.85rem 1.55rem;">
            📚 Jelajahi Bank Soal
        </a>
    </div>

    <!-- Calm Trust Pills -->
    <div class="hero-trust-row">
        <span class="hero-trust-item"><b>✓</b> Standar BKN &amp; MenPAN-RB</span>
        <span class="hero-trust-item"><b>✓</b> Standar BUMN (FHCI)</span>
        <span class="hero-trust-item"><b>✓</b> Kisi-kisi Kedinasan &amp; Psikotes</span>
        <span class="hero-trust-item"><b>✓</b> Pembahasan Trik Cepat</span>
    </div>
</div>

<!-- Official Announcement Notice -->
<div class="gov-notice">
    <div class="gov-notice-icon">📢</div>
    <div class="gov-notice-content">
        <h4>Pemberitahuan Sistem Seleksi Terpadu siaptes.id:</h4>
        <p>
            Platform <b>SIAP TES</b> kini mencakup 4 pilar seleksi utama: <b>CPNS (SKD & SKB)</b>, <b>BUMN (TKD, Core Values AKHLAK & English)</b>, <b>Sekolah Kedinasan (STAN, IPDN, STIS)</b>, dan <b>Psikotes Kerja Profesional</b>.
            Pengguna baru dapat langsung mencoba <b>Paket Mini Tryout All-in-One (Trial Bebas Biaya)</b> atau aktivasi <b>Akses PRO VIP</b> untuk membuka seluruh fitur tanpa batas.
        </p>
    </div>
</div>

<!-- FITUR GRATIS TANPA LOGIN: TES KEMAMPUAN & POTENSI -->
<section class="tp-home-strip">
    <div class="tp-home-head">
        <div>
            <span class="tp-badge-emerald">✨ 100% GRATIS · TANPA LOGIN · LANGSUNG COBA</span>
            <h2>Tes Kemampuan &amp; Potensi Gratis</h2>
            <p>Ingin cek persepsi warna, menguji penalaran IQ, memetakan minat karier, atau pemanasan lewat game kognitif? Coba langsung tanpa perlu daftar akun!</p>
        </div>
        <a href="tes-potensi.php" class="btn btn-primary btn-sm">Buka Semua Tes Gratis →</a>
    </div>
    <div class="tp-home-grid">
        <a href="tes-potensi.php?tes=buta-warna" class="tp-home-card">
            <span class="tp-hc-ico">🎨</span>
            <strong>Tes Buta Warna</strong>
            <span>18 pelat pseudoisokromatik SVG orisinal dengan hasil &amp; review instan.</span>
            <em>Coba Gratis →</em>
        </a>
        <a href="tes-potensi.php?tes=iq" class="tp-home-card">
            <span class="tp-hc-ico">🧠</span>
            <strong>Tes IQ Online (30 Soal)</strong>
            <span>Figural, Numerik, Verbal, Logis &amp; Spasial (25 menit) dengan pembahasan langsung.</span>
            <em>Coba Gratis →</em>
        </a>
        <a href="tes-potensi.php?tes=minat-karier" class="tp-home-card">
            <span class="tp-hc-ico">🧭</span>
            <strong>Minat Karier (RIASEC)</strong>
            <span>Temukan Top-3 Holland Code &amp; rekomendasi jalur karier/seleksi yang cocok.</span>
            <em>Coba Gratis →</em>
        </a>
        <a href="tes-potensi.php?tes=game-kognitif" class="tp-home-card">
            <span class="tp-hc-ico">🎮</span>
            <strong>10 Game Kognitif</strong>
            <span>Kejar Angka, Memori Grid, Sprint Pola, Balon Risiko, Sinyal Berhenti &amp; lainnya.</span>
            <em>Main Gratis →</em>
        </a>
    </div>
</section>

<!-- 4 PILAR UTAMA UJIAN YANG DIDUKUNG -->
<div style="margin-bottom: 3.5rem;">
    <div style="text-align: center; max-width: 760px; margin: 0 auto 2rem;">
        <span class="badge badge-navy" style="margin-bottom: 0.5rem;">CAKUPAN LENGKAP DALAM 1 PLATFORM</span>
        <h2 style="font-size: 2rem; font-weight: 800; color: var(--core-navy); margin-top: 0.5rem; margin-bottom: 0.75rem;">
            Pilih Jalur Impian Anda di SIAP TES
        </h2>
        <p style="color: var(--core-text-muted); font-size: 0.95rem;">
            Tak perlu lagi membeli banyak bimbel berbeda dengan biaya mahal. Semua modul dan latihan terlengkap telah kami satukan di domain resmi <b>siaptes.id</b>:
        </p>
    </div>

    <div class="grid-2" style="gap: 1.5rem;">
        <!-- Track 1: CPNS & ASN -->
        <div class="card card-navy-top" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.4rem;">
                    <span class="badge badge-twk">🏛️ CPNS & PPPK (ASN)</span>
                    <span style="font-size: 0.78rem; font-weight: 700; color: var(--core-navy);">Standar CAT BKN</span>
                </div>
                <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--core-navy); margin-bottom: 0.5rem;">
                    Seleksi Kompetensi Dasar (SKD) & SKB
                </h3>
                <p style="color: var(--core-text-muted); font-size: 0.885rem; line-height: 1.6; margin-bottom: 1rem;">
                    Meliputi Tes Wawasan Kebangsaan (TWK passing grade 65), Tes Intelegensia Umum (TIU passing grade 80), dan Tes Karakteristik Pribadi (TKP skala bobot 1–5, passing grade 166). Total 110 soal dalam 100 menit persis sistem asli BKN.
                </p>
            </div>
            <div style="border-top: 1px solid var(--core-border); padding-top: 0.75rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                <span style="font-size: 0.8rem; color: var(--core-text-muted);">110 Soal &bull; Timer 100 Menit</span>
                <a href="tryout.php" class="btn btn-primary btn-sm">Ikuti Tryout CPNS ➔</a>
            </div>
        </div>

        <!-- Track 2: BUMN -->
        <div class="card card-navy-top" style="border-top-color: var(--core-blue); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.4rem;">
                    <span class="badge badge-tkd_bumn">🏢 REKRUTMEN BERSAMA BUMN</span>
                    <span style="font-size: 0.78rem; font-weight: 700; color: var(--core-blue);">Standar RBB FHCI</span>
                </div>
                <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--core-navy); margin-bottom: 0.5rem;">
                    TKD, Core Values AKHLAK & Bahasa Inggris
                </h3>
                <p style="color: var(--core-text-muted); font-size: 0.885rem; line-height: 1.6; margin-bottom: 1rem;">
                    Simulasi terpadu seleksi BUMN nasional: Tes Kemampuan Dasar (TKD logika bisnis & numerik), Core Values AKHLAK (Amanah, Kompeten, Harmonis, Loyal, Adaptif, Kolaboratif) dengan kasus kontekstual, dan English for Business.
                </p>
            </div>
            <div style="border-top: 1px solid var(--core-border); padding-top: 0.75rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                <span style="font-size: 0.8rem; color: var(--core-text-muted);">TKD + AKHLAK + English</span>
                <a href="tryout.php" class="btn btn-primary btn-sm">Ikuti Tryout BUMN ➔</a>
            </div>
        </div>

        <!-- Track 3: Kedinasan -->
        <div class="card card-navy-top" style="border-top-color: #9c435f; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.4rem;">
                    <span class="badge badge-kedinasan_tpa">🎓 SEKOLAH KEDINASAN</span>
                    <span style="font-size: 0.78rem; font-weight: 700; color: #9c435f;">SPMB STAN, STIS, IPDN</span>
                </div>
                <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--core-text); margin-bottom: 0.5rem;">
                    SKD Kedinasan & TPA Akademik Terapan
                </h3>
                <p style="color: var(--core-text-muted); font-size: 0.885rem; line-height: 1.65; margin-bottom: 1rem;">
                    Paket khusus seleksi calon praja dan taruna kedinasan: PKN STAN, Politeknik Statistika STIS, IPDN, Poltekip, Poltekim, dan STIN. Dilengkapi modul Matematika Terapan, Aljabar Fungsi, Geometri Ruang, dan Analisis Data.
                </p>
            </div>
            <div style="border-top: 1px solid var(--core-border-light); padding-top: 0.75rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                <span style="font-size: 0.8rem; color: var(--core-text-muted);">SKD + TPA Kedinasan</span>
                <a href="tryout.php?track=kedinasan" class="btn btn-primary btn-sm">Ikuti Tryout Kedinasan ➔</a>
            </div>
        </div>

        <!-- Track 4: Psikotes Kerja -->
        <div class="card card-navy-top" style="border-top-color: #5b52a8; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.4rem;">
                    <span class="badge badge-psiko_logika">🧠 PSIKOTES KERJA & ASESMEN</span>
                    <span style="font-size: 0.78rem; font-weight: 700; color: #5b52a8;">Rekrutmen Korporat</span>
                </div>
                <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--core-text); margin-bottom: 0.5rem;">
                    Logika, Spasial Gambar & Situational Judgement
                </h3>
                <p style="color: var(--core-text-muted); font-size: 0.885rem; line-height: 1.65; margin-bottom: 1rem;">
                    Latihan psikotes komprehensif untuk seleksi kerja perbankan (BCA, Mandiri, BRI, BNI), multinasional, startup, dan instansi. Menguji logika deduktif, visualisasi spasial 3D, rotasi gambar, dan Situational Judgement Test (SJT).
                </p>
            </div>
            <div style="border-top: 1px solid var(--core-border-light); padding-top: 0.75rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                <span style="font-size: 0.8rem; color: var(--core-text-muted);">Logika + Spasial + SJT</span>
                <a href="tryout.php?track=psikotes" class="btn btn-primary btn-sm">Ikuti Tryout Psikotes ➔</a>
            </div>
        </div>
    </div>
</div>

<!-- Statistik Platform -->
<div class="grid-3" style="margin-bottom: 3.5rem;">
    <div class="stat-card twk">
        <div class="stat-label">TOTAL BANK SOAL MULTI-TES</div>
        <div class="stat-val"><?= number_format($totalQuestions) ?>+ <span style="font-size: 0.95rem; font-weight: 500; color: var(--core-text-muted);">Butir Soal</span></div>
        <p style="font-size: 0.825rem; color: var(--core-text-muted); margin-top: 0.25rem;">CPNS, BUMN, Kedinasan & Psikotes Kerja</p>
    </div>
    <div class="stat-card tiu">
        <div class="stat-label">SESI SIMULASI DISELESAIKAN</div>
        <div class="stat-val"><?= number_format($totalAttempts) ?> <span style="font-size: 0.95rem; font-weight: 500; color: var(--core-text-muted);">Sesi Selesai</span></div>
        <p style="font-size: 0.825rem; color: var(--core-text-muted); margin-top: 0.25rem;">Peserta aktif menguji kecepatan & akurasi setiap hari</p>
    </div>
    <div class="stat-card tkp">
        <div class="stat-label">PESERTA TERDAFTAR DI SIAPTES.ID</div>
        <div class="stat-val"><?= number_format($totalUsers) ?> <span style="font-size: 0.95rem; font-weight: 500; color: var(--core-text-muted);">Member Aktif</span></div>
        <p style="font-size: 0.825rem; color: var(--core-text-muted); margin-top: 0.25rem;">Terhubung dalam portal persiapan karier terintegrasi</p>
    </div>
</div>

<!-- SECTION: MENGAPA HARUS MEMILIH SIAP TES? -->
<div style="margin-bottom: 3.5rem;">
    <div style="text-align: center; max-width: 760px; margin: 0 auto 2.5rem;">
        <span class="badge badge-gold" style="padding: 0.4rem 0.9rem; margin-bottom: 0.5rem;">KEUNGGULAN UTAMA PLATFORM</span>
        <h2 style="font-size: 2rem; font-weight: 800; color: var(--core-navy); margin-top: 0.5rem; margin-bottom: 0.75rem;">
            Kenapa Pejuang Karier Memilih Berlatih di SIAP TES?
        </h2>
        <p style="color: var(--core-text-muted); font-size: 1rem; line-height: 1.6;">
            Persaingan memperebutkan kursi CPNS, BUMN, dan Kedinasan sangat ketat dengan ratusan ribu pendaftar. Berikut alasan kuat mengapa platform <b>siaptes.id</b> adalah partner terbaik Anda:
        </p>
    </div>

    <div class="grid-3">
        <!-- Benefit 1 -->
        <div class="card card-navy-top" style="display: flex; flex-direction: column;">
            <div style="font-size: 2.2rem; margin-bottom: 0.75rem;">🎯</div>
            <h3 style="font-size: 1.15rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--core-navy);">4 Jalur Seleksi dalam 1 Akun</h3>
            <p style="font-size: 0.885rem; color: var(--core-text-muted); line-height: 1.6;">
                Hemat hingga jutaan rupiah! Anda tidak perlu membeli paket langganan terpisah untuk CPNS, BUMN, STAN, atau psikotes perbankan. Semuanya ada di dalam satu platform <b>siaptes.id</b>.
            </p>
        </div>

        <!-- Benefit 2 -->
        <div class="card card-navy-top" style="display: flex; flex-direction: column;">
            <div style="font-size: 2.2rem; margin-bottom: 0.75rem;">🖥️</div>
            <h3 style="font-size: 1.15rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--core-navy);">Simulasi CAT 99% Presisi Ujian Asli</h3>
            <p style="font-size: 0.885rem; color: var(--core-text-muted); line-height: 1.6;">
                Navigasi nomor soal, penanda ragu-ragu kuning, timer hitung mundur akurat, serta fitur autosave instan ke cloud server. Membiasakan mental Anda agar tidak kaget di hari ujian resmi.
            </p>
        </div>

        <!-- Benefit 3 -->
        <div class="card card-navy-top" style="display: flex; flex-direction: column;">
            <div style="font-size: 2.2rem; margin-bottom: 0.75rem;">🎁</div>
            <h3 style="font-size: 1.15rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--core-navy);">Tersedia Paket Percobaan Gratis (Trial)</h3>
            <p style="font-size: 0.885rem; color: var(--core-text-muted); line-height: 1.6;">
                Kami percaya pada kualitas sistem kami. Pengguna baru dapat langsung mengerjakan <b>Mini Tryout All-in-One</b> gratis tanpa syarat kartu kredit untuk merasakan pengalaman ujian CAT.
            </p>
        </div>

        <!-- Benefit 4 -->
        <div class="card card-navy-top" style="display: flex; flex-direction: column;">
            <div style="font-size: 2.2rem; margin-bottom: 0.75rem;">⚖️</div>
            <h3 style="font-size: 1.15rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--core-navy);">Evaluasi Passing Grade Real-Time</h3>
            <p style="font-size: 0.885rem; color: var(--core-text-muted); line-height: 1.6;">
                Formula kalkulasi skor resmi: pembobotan TKP (1–5), AKHLAK BUMN, dan penalti/poin standar. Begitu ujian selesai, sistem langsung menampilkan status kelulusan Anda.
            </p>
        </div>

        <!-- Benefit 5 -->
        <div class="card card-navy-top" style="display: flex; flex-direction: column;">
            <div style="font-size: 2.2rem; margin-bottom: 0.75rem;">💡</div>
            <h3 style="font-size: 1.15rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--core-navy);">Kunci & Pembahasan Trik Cepat</h3>
            <p style="font-size: 0.885rem; color: var(--core-text-muted); line-height: 1.6;">
                Bukan sekadar memberi tahu pilihan mana yang benar! Setiap butir soal dilengkapi analisis konsep, trik matematika cepat tanpa rumus berbelit-belit, dan dasar hukum regulasi.
            </p>
        </div>

        <!-- Benefit 6 -->
        <div class="card card-navy-top" style="display: flex; flex-direction: column;">
            <div style="font-size: 2.2rem; margin-bottom: 0.75rem;">📱</div>
            <h3 style="font-size: 1.15rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--core-navy);">Fleksibel di Smartphone, Tablet & Laptop</h3>
            <p style="font-size: 0.885rem; color: var(--core-text-muted); line-height: 1.6;">
                Antarmuka modern berbasis web responsif berkecepatan tinggi yang dapat diakses kapan saja melalui smartphone di sela-sela aktivitas harian atau di laptop saat tryout intensif.
            </p>
        </div>
    </div>
</div>

<!-- SECTION: TESTIMONIAL & PROOF -->
<div class="card" style="background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%); border: 1px solid var(--core-border); padding: 2.5rem 2rem; margin-bottom: 3.5rem;">
    <div style="text-align: center; margin-bottom: 2rem;">
        <span class="badge badge-gold" style="padding: 0.35rem 0.85rem; font-weight: 700;">TESTIMONI ALUMNI SIAP TES</span>
        <h2 style="font-size: 1.75rem; font-weight: 800; color: var(--core-navy); margin-top: 0.5rem;">
            Cerita Mereka yang Berhasil Meraih Impian
        </h2>
    </div>

    <div class="grid-3">
        <div style="background: #ffffff; border-radius: var(--radius-md); padding: 1.5rem; box-shadow: var(--shadow); border: 1px solid var(--core-border); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="color: var(--core-gold); margin-bottom: 0.75rem; font-size: 1.1rem;">★★★★★</div>
                <p style="font-size: 0.925rem; color: var(--core-text); font-style: italic; line-height: 1.6; margin-bottom: 1.25rem;">
                    "Simulasi CAT di siaptes.id bener-bener ngebantu ngatur ritme waktu ujian. Pas ujian SKD di BKN kemarin udah gak gugup karena sistemnya persis. Skor SKD saya 442 dan lolos formasi impian!"
                </p>
            </div>
            <div style="border-top: 1px solid var(--core-border-light); padding-top: 0.75rem; display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 38px; height: 38px; border-radius: 50%; background: var(--core-navy); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem;">
                    AN
                </div>
                <div>
                    <b style="font-size: 0.9rem; color: var(--core-navy); display: block;">Anisa Nurul F.</b>
                    <span style="font-size: 0.8rem; color: var(--core-text-muted);">Lolos CPNS Kementerian Keuangan RI</span>
                </div>
            </div>
        </div>

        <div style="background: #ffffff; border-radius: var(--radius-md); padding: 1.5rem; box-shadow: var(--shadow); border: 1px solid var(--core-border); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="color: var(--core-gold); margin-bottom: 0.75rem; font-size: 1.1rem;">★★★★★</div>
                <p style="font-size: 0.925rem; color: var(--core-text); font-style: italic; line-height: 1.6; margin-bottom: 1.25rem;">
                    "Soal Core Values AKHLAK BUMN dan TKD-nya sangat aplikatif dengan kasus nyata dunia kerja. Pembahasan trik bahasa Inggrisnya juga praktis banget. Alhamdulillah lolos Rekrutmen Bersama BUMN di Bank Mandiri!"
                </p>
            </div>
            <div style="border-top: 1px solid var(--core-border-light); padding-top: 0.75rem; display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 38px; height: 38px; border-radius: 50%; background: var(--core-blue); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem;">
                    DP
                </div>
                <div>
                    <b style="font-size: 0.9rem; color: var(--core-navy); display: block;">Dimas Prasetyo</b>
                    <span style="font-size: 0.8rem; color: var(--core-text-muted);">Lolos RBB BUMN - Bank Mandiri</span>
                </div>
            </div>
        </div>

        <div style="background: #ffffff; border-radius: var(--radius-md); padding: 1.5rem; box-shadow: var(--shadow); border: 1px solid var(--core-border); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="color: var(--core-gold); margin-bottom: 0.75rem; font-size: 1.1rem;">★★★★★</div>
                <p style="font-size: 0.925rem; color: var(--core-text); font-style: italic; line-height: 1.6; margin-bottom: 1.25rem;">
                    "Latihan TPA Matematika Terapan dan Silogisme di sini bikin kemampuan logika analitis saya melonjak drastis. Berkat latihan rutin di SIAP TES, saya berhasil lolos SPMB PKN STAN!"
                </p>
            </div>
            <div style="border-top: 1px solid var(--core-border-light); padding-top: 0.75rem; display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 38px; height: 38px; border-radius: 50%; background: #be123c; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem;">
                    FR
                </div>
                <div>
                    <b style="font-size: 0.9rem; color: var(--core-navy); display: block;">Fajar Ramadhan</b>
                    <span style="font-size: 0.8rem; color: var(--core-text-muted);">Taruna PKN STAN</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Daftar Paket Tryout Pilihan -->
<div style="margin-bottom: 3.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="font-size: 1.45rem; font-weight: 800; color: var(--core-navy);">Paket Simulasi Tryout Aktif</h2>
            <p style="color: var(--core-text-muted); font-size: 0.925rem;">Pilih simulasi berwaktu sesuai target karier yang ingin Anda capai.</p>
        </div>
        <a href="tryout.php" class="btn btn-outline-navy btn-sm">Lihat Semua <?= count($packages) ?> Paket Tryout →</a>
    </div>

    <div class="grid-2">
        <?php 
        $isUserPrem = isPremiumUser();
        foreach ($packages as $pkg): 
            $isPkgPrem = !empty($pkg['is_premium']);
            $canPlay = (!$isPkgPrem || $isUserPrem);
            $pkgMeta = getPackageDisplayMeta($db, $pkg);
        ?>
            <div class="card <?= $isPkgPrem ? 'card-gold-top' : 'card-navy-top' ?>" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem; gap: 0.5rem; flex-wrap: wrap;">
                        <div>
                            <?php if ($isPkgPrem): ?>
                                <span class="badge badge-gold" style="margin-bottom: 0.25rem;">⭐ PRO VIP</span>
                            <?php else: ?>
                                <span class="badge badge-success" style="margin-bottom: 0.25rem;">🎁 TRIAL PERCOBAAN GRATIS</span>
                            <?php endif; ?>
                            <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--core-navy);"><?= htmlspecialchars($pkg['title']) ?></h3>
                        </div>
                        <span class="badge badge-navy" style="font-size: 0.8rem; padding: 0.35rem 0.65rem; white-space: nowrap;">
                            ⏱️ <?= $pkg['duration_minutes'] ?> Menit
                        </span>
                    </div>
                    <p style="color: var(--core-text-muted); font-size: 0.885rem; line-height: 1.6; margin-bottom: 1.25rem;">
                        <?= htmlspecialchars($pkg['description']) ?>
                    </p>
                    <div style="background: var(--core-navy-subtle); padding: 0.55rem 0.85rem; border-radius: var(--radius-sm); font-size: 0.8rem; font-weight: 700; color: var(--core-navy); margin-bottom: 1rem;">
                        📊 <?= (int)$pkgMeta['question_count'] ?> Butir Soal Acak
                        <?php if ($pkgMeta['bank_pool_count'] > $pkgMeta['question_count']): ?>
                            <span style="font-weight: 600; color: var(--core-text-muted);">(dari <?= number_format($pkgMeta['bank_pool_count']) ?> Bank Soal)</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--core-border); padding-top: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                    <span style="font-size: 0.85rem; font-weight: 600;">
                        <?= $isPkgPrem ? '<span style="color:var(--core-gold-text);">🔒 Khusus Member PRO</span>' : '<span style="color:var(--core-success);">🟢 Bebas Akses (Trial)</span>' ?>
                    </span>
                    <?php if ($canPlay): ?>
                        <a href="exam.php?action=start_package&package_id=<?= $pkg['id'] ?>" class="btn btn-primary btn-sm" onclick="return confirm('Mulai sesi tryout sekarang? Timer ujian akan langsung dihitung!');">
                            Mulai Simulasi ➔
                        </a>
                    <?php else: ?>
                        <a href="upgrade.php?package_id=<?= $pkg['id'] ?>" class="btn btn-gold btn-sm">
                            Aktivasi PRO (Rp 29.000 via DOKU)
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- SECTION: ARTIKEL & STRATEGI TERBARU (SEO TRAFFIC MAGNET) -->
<?php if (!empty($latestArticles)): ?>
<div style="margin-bottom: 3.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <span class="badge badge-navy" style="margin-bottom: 0.4rem;">📰 WAWASAN & STRATEGI PEJUANG SELEKSI</span>
            <h2 style="font-size: 1.55rem; font-weight: 800; color: var(--core-navy); margin: 0;">Artikel, Kisi-Kisi & Tips Lulus Terbaru</h2>
            <p style="color: var(--core-text-muted); font-size: 0.925rem; margin-top: 0.25rem;">Pelajari panduan strategi menjawab soal CAT dan informasi seleksi terbaru di siaptes.id.</p>
        </div>
        <a href="artikel.php" class="btn btn-outline-navy btn-sm">Baca Semua Artikel & Tips →</a>
    </div>

    <div class="grid-3">
        <?php foreach ($latestArticles as $art): 
            $cMeta = $articleCats[$art['category']] ?? ['icon' => '📄', 'badge_class' => 'badge-navy', 'color' => '#0b2d52'];
        ?>
            <article class="card card-navy-top" style="border-top-color: <?= $cMeta['color'] ?>; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem; flex-wrap: wrap; gap: 0.4rem;">
                        <span class="badge <?= $cMeta['badge_class'] ?>"><?= $cMeta['icon'] ?> <?= htmlspecialchars($art['category']) ?></span>
                        <span style="font-size: 0.76rem; color: var(--core-text-muted);">⏱️ <?= (int)$art['reading_time'] ?> mnt baca</span>
                    </div>
                    <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--core-navy); line-height: 1.4; margin-bottom: 0.6rem;">
                        <a href="baca-artikel.php?slug=<?= urlencode($art['slug']) ?>" style="color: inherit; text-decoration: none;">
                            <?= htmlspecialchars($art['title']) ?>
                        </a>
                    </h3>
                    <p style="font-size: 0.86rem; color: var(--core-text-muted); line-height: 1.6; margin-bottom: 1rem;">
                        <?= htmlspecialchars(mb_strimwidth($art['excerpt'], 0, 130, '...')) ?>
                    </p>
                </div>
                <div style="border-top: 1px solid var(--core-border-light); padding-top: 0.75rem; display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; flex-wrap: wrap; gap: 0.4rem;">
                    <span style="color: var(--core-text-muted);">🗓️ <?= formatTanggalIndo($art['created_at']) ?></span>
                    <a href="baca-artikel.php?slug=<?= urlencode($art['slug']) ?>" style="font-weight: 700; color: var(--core-navy); text-decoration: none;">
                        Baca Selengkapnya ➔
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- SECTION: CALL TO ACTION (CTA BANNER) -->
<div class="hero cta-banner" style="margin-bottom: 2rem;">
    <div class="hero-pill" style="margin-bottom: 1rem;">
        🌟 INVESTASI KARIER TERBAIK DI SIAPTES.ID
    </div>
    <h2 style="font-size: 2rem; font-weight: 800; margin-bottom: 0.75rem;">Siap Mengamankan Posisi Impian Anda Tahun Ini?</h2>
    <p style="font-size: 1rem; max-width: 660px; margin: 0 auto 1.85rem; line-height: 1.7;">
        Mulai latihan lebih awal dengan ritme belajar yang nyaman dan terukur bersama ribuan pejuang seleksi di seluruh Indonesia.
    </p>
    <div style="display: flex; justify-content: center; gap: 0.85rem; flex-wrap: wrap;">
        <a href="register.php" class="btn btn-gold" style="font-size: 1rem; padding: 0.85rem 1.85rem;">
            Daftar Akun Gratis Sekarang
        </a>
        <a href="tryout.php" class="btn btn-outline" style="font-size: 1rem; padding: 0.85rem 1.85rem;">
            Coba Tryout Trial Bebas Biaya
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
