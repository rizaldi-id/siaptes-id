<?php
// includes/articles_helper.php
require_once __DIR__ . '/../config/database.php';

/**
 * Pastikan tabel articles tersedia (mendukung MySQL & SQLite otomatis)
 * dan isi dengan artikel awal jika masih kosong.
 */
function ensureArticlesTable(PDO $db) {
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'mysql') {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `articles` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `title` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(255) NOT NULL UNIQUE,
                `category` VARCHAR(80) NOT NULL DEFAULT 'CPNS & PPPK',
                `excerpt` TEXT NOT NULL,
                `content` LONGTEXT NOT NULL,
                `cover_image` VARCHAR(500) DEFAULT NULL,
                `author_name` VARCHAR(120) DEFAULT 'Tim Redaksi SIAP TES',
                `reading_time` INT DEFAULT 5,
                `views` INT DEFAULT 0,
                `is_featured` TINYINT DEFAULT 0,
                `is_published` TINYINT DEFAULT 1,
                `tags` VARCHAR(255) DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } else {
        $db->exec("
            CREATE TABLE IF NOT EXISTS articles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                slug TEXT UNIQUE NOT NULL,
                category TEXT NOT NULL DEFAULT 'CPNS & PPPK',
                excerpt TEXT NOT NULL,
                content TEXT NOT NULL,
                cover_image TEXT NULL,
                author_name TEXT DEFAULT 'Tim Redaksi SIAP TES',
                reading_time INTEGER DEFAULT 5,
                views INTEGER DEFAULT 0,
                is_featured INTEGER DEFAULT 0,
                is_published INTEGER DEFAULT 1,
                tags TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");
    }

    // Cek apakah sudah ada artikel, jika kosong maka seed artikel awal
    $count = (int)$db->query("SELECT COUNT(*) FROM articles")->fetchColumn();
    if ($count === 0) {
        seedInitialArticles($db);
    }
}

/**
 * Buat slug URL-friendly dari judul artikel
 */
function createArticleSlug(string $title): string {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
    $slug = trim($slug, '-');
    return $slug ?: 'artikel-' . time();
}

/**
 * Daftar kategori artikel resmi di SIAP TES
 */
function getArticleCategories(): array {
    return [
        'CPNS & PPPK' => [
            'label' => 'CPNS & PPPK (ASN)',
            'icon'  => '🏛️',
            'badge_class' => 'badge-twk',
            'color' => '#0b2d52'
        ],
        'Rekrutmen BUMN' => [
            'label' => 'Rekrutmen Bersama BUMN',
            'icon'  => '🏢',
            'badge_class' => 'badge-tkd_bumn',
            'color' => '#0284c7'
        ],
        'Sekolah Kedinasan' => [
            'label' => 'Sekolah Kedinasan',
            'icon'  => '🎓',
            'badge_class' => 'badge-kedinasan_tpa',
            'color' => '#be123c'
        ],
        'Psikotes & Karier' => [
            'label' => 'Psikotes & Karier',
            'icon'  => '🧠',
            'badge_class' => 'badge-psiko_logika',
            'color' => '#7c3aed'
        ],
        'Tips & Trik CAT' => [
            'label' => 'Tips & Strategi CAT',
            'icon'  => '💡',
            'badge_class' => 'badge-gold',
            'color' => '#d97706'
        ],
    ];
}

/**
 * Format tanggal ke Bahasa Indonesia
 */
function formatTanggalIndo(string $datetime): string {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $ts = strtotime($datetime);
    if (!$ts) return $datetime;
    $tgl = date('j', $ts);
    $bln = $bulan[(int)date('n', $ts)] ?? date('M', $ts);
    $thn = date('Y', $ts);
    return "$tgl $bln $thn";
}

/**
 * Seed 6 artikel berkualitas tinggi untuk SEO & penarik traffic organik
 */
function seedInitialArticles(PDO $db) {
    $articles = [
        [
            'title' => 'Panduan Lengkap & Strategi Lulus SKD CPNS Sistem CAT BKN: Kuasai Passing Grade TWK, TIU, dan TKP Sekali Tes',
            'slug' => 'panduan-lengkap-strategi-lulus-skd-cpns-sistem-cat-bkn',
            'category' => 'CPNS & PPPK',
            'excerpt' => 'Pelajari rincian nilai ambang batas (passing grade) SKD CPNS terbaru beserta strategi jitu menaklukkan 110 soal TWK, TIU, dan TKP dalam waktu 100 menit agar masuk 3 besar perankingan formasi.',
            'cover_image' => '',
            'author_name' => 'Tim Akademik SIAP TES',
            'reading_time' => 6,
            'views' => 1420,
            'is_featured' => 1,
            'is_published' => 1,
            'tags' => 'CPNS, SKD CAT BKN, Passing Grade CPNS, TWK TIU TKP, Tips Lulus CPNS',
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
            'content' => '
<p>Seleksi Kompetensi Dasar (SKD) menggunakan sistem <b>Computer Assisted Test (CAT) BKN</b> merupakan gerbang pertama sekaligus paling krusial bagi setiap pejuang Calon Pegawai Negeri Sipil (CPNS). Setiap tahunnya, jutaan pelamar gugur bukan karena kurang pintar, melainkan karena <b>salah mengatur strategi waktu</b> dan tidak memahami pola soal berbasis penalaran (HOTS).</p>

<h3>1. Memahami Peta Komposisi Soal & Passing Grade SKD</h3>
<p>Dalam ujian SKD CPNS standar resmi KemenPAN-RB, Anda akan dihadapkan pada total <b>110 butir soal</b> yang harus diselesaikan dalam waktu <b>100 menit</b>. Artinya, rata-rata waktu yang Anda miliki hanyalah <b>54 detik per soal</b>.</p>
<ul>
    <li><b>Tes Wawasan Kebangsaan (TWK):</b> 30 Soal | Passing Grade: <b>65 Poin</b> (Benar +5, Salah/Kosong 0). Menguji Nasionalisme, Integritas, Bela Negara, Pilar Negara, dan Bahasa Negara.</li>
    <li><b>Tes Intelegensia Umum (TIU):</b> 35 Soal | Passing Grade: <b>80 Poin</b> (Benar +5, Salah/Kosong 0). Menguji kemampuan Verbal (Analogi, Silogisme, Analitis), Numerik (Berhitung, Deret, Perbandingan, Soal Cerita), dan Figural.</li>
    <li><b>Tes Karakteristik Pribadi (TKP):</b> 45 Soal | Passing Grade: <b>166 Poin</b> (Bobot berskala 1 s.d. 5, Kosong 0). Menguji Pelayanan Publik, Jejaring Kerja, Sosial Budaya, TIK, Profesionalisme, dan Anti-Radikalisme.</li>
</ul>

<div class="article-callout">
    <b>💡 Catatan Penting:</b> Sekadar memenuhi Passing Grade belum menjamin Anda lolos ke tahap SKB! Anda harus masuk dalam peringkat <b>3 kali jumlah kebutuhan formasi</b>. Targetkan skor total minimal <b>410–440+ poin</b> agar posisi Anda aman.
</div>

<h3>2. Strategi Urutan Pengerjaan Soal yang Efektif</h3>
<p>Banyak peserta kehabisan waktu saat berkutat di soal hitungan TIU atau bacaan panjang TWK. Gunakan teknik <b>Sapuan Tiga Tahap</b> berikut saat simulasi di <b>siaptes.id</b>:</p>
<ol>
    <li><b>Tahap 1: Amankan Poin TKP atau TIU Mudah Lebih Dulu.</b> Soal TKP tidak memiliki jawaban bernilai nol selama diisi. Gunakan teknik <i>skimming</i> (baca kalimat terakhir/intinya dulu) untuk mengidentifikasi aspek yang diuji.</li>
    <li><b>Tahap 2: Kerjakan TIU Pola Cepat (Deret, Analogi, Figural).</b> Soal deret angka dan silogisme dapat diselesaikan dalam 20–30 detik jika Anda sudah terbiasa berlatih di Bank Soal SIAP TES.</li>
    <li><b>Tahap 3: Selesaikan TWK Penalaran & Soal Cerita Analitis.</b> Gunakan fitur tombol <b>"Ragu-ragu" (Kuning)</b> jika menemukan soal yang memakan waktu lebih dari 1 menit, lalu kembali lagi di 15 menit terakhir.</li>
</ol>

<h3>3. Mengapa Simulasi Rutin Sangat Menentukan?</h3>
<p>Otak manusia membutuhkan adaptasi terhadap tekanan timer hitung mundur. Dengan rutin mengerjakan <b>Simulasi CAT di SIAP TES (siaptes.id)</b>, Anda akan terbiasa mengenali kata kunci soal dalam hitungan detik, mengontrol kepanikan, dan mengevaluasi kelemahan Anda melalui fitur pembahasan lengkap.</p>'
        ],
        [
            'title' => 'Bedah Soal Rekrutmen Bersama BUMN (RBB): Rahasia Taklukkan TKD, Core Values AKHLAK, dan Bahasa Inggris',
            'slug' => 'bedah-soal-rekrutmen-bersama-bumn-tkd-akhlak-bahasa-inggris',
            'category' => 'Rekrutmen BUMN',
            'excerpt' => 'Ingin berkarier di perusahaan BUMN papan atas seperti Mandiri, BRI, Pertamina, atau Telkom? Simak bedah materi Tes Kemampuan Dasar (TKD), AKHLAK FHCI, dan TBI beserta trik menjawabnya.',
            'cover_image' => '',
            'author_name' => 'Tim Redaksi SIAP TES',
            'reading_time' => 5,
            'views' => 985,
            'is_featured' => 0,
            'is_published' => 1,
            'tags' => 'Rekrutmen Bersama BUMN, RBB FHCI, TKD BUMN, Core Values AKHLAK, Tes Bahasa Inggris BUMN',
            'created_at' => date('Y-m-d H:i:s', strtotime('-4 days')),
            'content' => '
<p><b>Rekrutmen Bersama BUMN (RBB)</b> yang diselenggarakan oleh Forum Human Capital Indonesia (FHCI) selalu menjadi magnet bagi ratusan ribu pencari kerja di Indonesia. Berbeda dengan seleksi CPNS, seleksi BUMN memiliki karakteristik soal yang sangat menekankan pada <b>kecepatan logika bisnis</b> dan <b>kesesuaian budaya kerja korporasi (Core Values AKHLAK)</b>.</p>

<h3>1. Struktur Tes Online Tahap 1 & Tahap 2 RBB BUMN</h3>
<p>Agar tidak salah sasaran saat belajar, pahami komponen utama yang diujikan beserta nilai ambang batasnya:</p>
<ul>
    <li><b>Tes Kemampuan Dasar (TKD):</b> Terdiri dari 100 soal dalam 73 menit (Passing Grade: 58). Meliputi <i>Verbal Logical Reasoning</i>, <i>Number Sequence</i>, <i>Word Classification</i>, dan <i>Diagrammatic Reasoning</i>.</li>
    <li><b>Tes Core Values AKHLAK:</b> Terdiri dari 90 soal dalam 30 menit (Passing Grade: 65). Mengukur penerapan 6 nilai utama BUMN: <b>A</b>manah, <b>K</b>ompeten, <b>H</b>armonis, <b>L</b>oyal, <b>A</b>daptif, dan <b>K</b>olaboratif.</li>
    <li><b>Tes Bahasa Inggris (TBI) & Learning Agility:</b> Menguji kemampuan membaca teks bisnis (Reading Comprehension), tata bahasa (Structure & Written Expression), serta ketangkasan adaptasi di lingkungan kerja baru.</li>
</ul>

<h3>2. Kunci Menjawab Soal Core Values AKHLAK</h3>
<p>Banyak peserta terjebak memilih jawaban yang "terlihat baik secara pribadi" namun kurang tepat secara prinsip korporasi. Hafalkan indikator perilaku berikut:</p>
<ul>
    <li><b>Amanah:</b> Memegang teguh kepercayaan, jujur melaporkan kesalahan, menolak gratifikasi, dan menepati janji kepada klien.</li>
    <li><b>Kompeten:</b> Terus belajar meningkatkan kapabilitas diri, membantu orang lain belajar, dan menyelesaikan tugas dengan kualitas terbaik.</li>
    <li><b>Harmonis:</b> Menghargai perbedaan latar belakang, suka menolong rekan kerja yang kesulitan, dan membangun lingkungan kerja kondusif.</li>
    <li><b>Loyal:</b> Menjaga nama baik perusahaan, rela berkorban untuk tujuan organisasi yang lebih besar selama tidak melanggar hukum.</li>
    <li><b>Adaptif:</b> Cepat menyesuaikan diri dengan teknologi baru, proaktif melakukan inovasi, dan tidak terpaku pada cara lama.</li>
    <li><b>Kolaboratif:</b> Membuka ruang kerja sama lintas divisi dan menggerakkan pemanfaatan sumber daya untuk tujuan bersama.</li>
</ul>

<div class="article-callout">
    <b>🚀 Tips Praktis:</b> Di platform <b>siaptes.id</b>, kami telah menyediakan paket khusus <b>Simulasi Rekrutmen Bersama BUMN</b> lengkap dengan skor terpisah dan pembahasan tiap butir soal.
</div>'
        ],
        [
            'title' => 'Info & Kisi-Kisi SPMB Sekolah Kedinasan (PKN STAN, STIS, IPDN, STIN): Syarat, Tahapan Seleksi, dan Tips Lolos',
            'slug' => 'info-kisi-kisi-spmb-sekolah-kedinasan-pkn-stan-stis-ipdn',
            'category' => 'Sekolah Kedinasan',
            'excerpt' => 'Kuliah gratis ikatan dinas dan lulus langsung diangkat menjadi CPNS! Simak perbandingan tahapan ujian masuk PKN STAN, Politeknik Statistika STIS, IPDN, Poltekip/Poltekim, serta materi TPA yang wajib dikuasai.',
            'cover_image' => '',
            'author_name' => 'Tim Akademik SIAP TES',
            'reading_time' => 6,
            'views' => 860,
            'is_featured' => 0,
            'is_published' => 1,
            'tags' => 'Sekolah Kedinasan, PKN STAN, STIS, IPDN, TPA Kedinasan, Ikatan Dinas',
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
            'content' => '
<p>Sekolah Kedinasan berstatus <b>Ikatan Dinas</b> tetap menjadi primadona bagi lulusan SMA/SMK/MA sederajat. Selain biaya pendidikan yang ditanggung negara, lulusannya akan langsung diangkat menjadi Aparatur Sipil Negara (ASN/CPNS) di kementerian atau lembaga terkait.</p>

<h3>1. Daftar Instansi Sekolah Kedinasan Favorit</h3>
<ul>
    <li><b>PKN STAN (Kementerian Keuangan):</b> Fokus pada manajemen keuangan negara, akuntansi sektor publik, kebendaharaan, dan kepabeanan.</li>
    <li><b>Politeknik Statistika STIS (Badan Pusat Statistik):</b> Sangat cocok bagi Anda yang menyukai matematika terapan, statistika, dan komputasi data.</li>
    <li><b>IPDN (Kementerian Dalam Negeri):</b> Mencetak kader kepamongprajaan untuk pemerintahan daerah dan pusat.</li>
    <li><b>Poltekip & Poltekim (Kemenkumham), STIN (BIN), STMKG (BMKG), dan Sekolah Kedinasan Kemenhub.</b></li>
</ul>

<h3>2. Dua Tahapan Ujian Akademik yang Wajib Dihadapi</h3>
<p>Mayoritas sekolah kedinasan menggunakan <b>SKD CAT BKN</b> (TWK, TIU, TKP) sebagai saringan tahap awal. Namun, instansi seperti <b>PKN STAN dan STIS</b> memiliki ujian lanjutan berupa <b>Tes Potensi Akademik (TPA) & Matematika Terapan</b> yang tingkat kesulitannya jauh di atas soal TIU reguler.</p>

<p>Topik Matematika & TPA Kedinasan yang paling sering keluar meliputi:</p>
<ol>
    <li>Aljabar fungsi, persamaan kuadrat, dan sistem pertidaksamaan.</li>
    <li>Peluang, kombinatorika, permutasi, dan statistika deskriptif.</li>
    <li>Geometri bidang datar dan ruang tiga dimensi.</li>
    <li>Barisan dan deret aritmatika/geometri bertingkat.</li>
    <li>Penalaran analitis kompleks (penyusunan jadwal/posisi bersyarat).</li>
</ol>

<p>Jangan tunggu pendaftaran dibuka baru mulai belajar! Gunakan fitur <b>Bank Soal Mandiri kategori TPA & Matematika Kedinasan</b> di <b>siaptes.id</b> untuk melatih kecepatan berhitung Anda setiap hari.</p>'
        ],
        [
            'title' => '7 Jenis Soal Psikotes Kerja Paling Sering Keluar di Bank & Perusahaan Multinasional Beserta Cara Cepat Menjawabnya',
            'slug' => '7-jenis-soal-psikotes-kerja-bank-perusahaan-multinasional',
            'category' => 'Psikotes & Karier',
            'excerpt' => 'Melamar program Management Trainee (MT), ODP Perbankan (BCA, Mandiri, BRI, BNI), atau perusahaan swasta? Kenali 7 tipe soal psikotes kerja mulai dari Logika Deduktif, Spasial Gambar, hingga Situational Judgement Test (SJT).',
            'cover_image' => '',
            'author_name' => 'Tim Redaksi SIAP TES',
            'reading_time' => 5,
            'views' => 745,
            'is_featured' => 0,
            'is_published' => 1,
            'tags' => 'Psikotes Kerja, ODP Bank, Management Trainee, Tes Spasial, Situational Judgement Test',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'content' => '
<p>Dalam proses rekrutmen perusahaan perbankan (program ODP/MDP), <i>Management Trainee</i> (MT) perusahaan multinasional, maupun <i>startup unicorn</i>, tahapan <b>Psikotes & Asesmen Kognitif</b> sering kali menggugurkan hingga 70% kandidat di tahap awal.</p>

<h3>Tipe Soal Psikotes yang Wajib Anda Kuasai</h3>
<ol>
    <li><b>Tes Logika Deduktif & Silogisme:</b> Menguji kemampuan menarik kesimpulan yang sah dari dua atau lebih premis tanpa terpengaruh opini pribadi.</li>
    <li><b>Tes Rotasi & Visualisasi Spasial (2D/3D):</b> Anda diminta menentukan bentuk jaring-jaring kubus apabila dilipat, atau bayangan bangun ruang yang diputar 90°/180°. <i>Tips: Perhatikan satu sisi patokan (anchor face) dan posisi sisi di sebelahnya!</i></li>
    <li><b>Tes Deret Angka & Matriks Numerik:</b> Menemukan pola tersembunyi (penjumlahan, perkalian, bilangan prima, Fibonacci, atau pola lompat dua suku).</li>
    <li><b>Tes Analogi Verbal & Padanan Hubungan Kata:</b> Mengukur keluasan kosakata dan ketajaman melihat relasi sebab-akibat, fungsi, atau bagian-keseluruhan.</li>
    <li><b>Situational Judgement Test (SJT):</b> Studi kasus di tempat kerja—misalnya menghadapi klien yang marah, konflik dengan rekan satu tim, atau tenggat waktu yang berbenturan.</li>
    <li><b>Tes Ketelitian & Konsentrasi Cepat:</b> Membandingkan pasangan kode angka/huruf dalam waktu sangat singkat untuk melihat daya tahan fokus di bawah tekanan.</li>
    <li><b>Tes Kepribadian Kerja (Work Personality):</b> Mengukur konsistensi karakter kepemimpinan, inisiatif, dan daya juang (grit).</li>
</ol>

<div class="article-callout">
    <b>🧠 Latihan Sekarang:</b> Semua tipe soal Psikotes Logika, Spasial, dan SJT di atas sudah tersedia lengkap dengan pembahasan langkah demi langkah di menu <b>Bank Soal & Simulasi Tryout SIAP TES</b>!
</div>'
        ],
        [
            'title' => 'Manajemen Waktu 100 Menit untuk 110 Soal CAT: Urutan Pengerjaan Soal Agar Tidak Kehabisan Waktu',
            'slug' => 'manajemen-waktu-100-menit-110-soal-cat-urutan-pengerjaan',
            'category' => 'Tips & Trik CAT',
            'excerpt' => 'Banyak peserta pintar gagal lulus hanya karena 15 soal terakhir belum sempat terjawab saat timer habis. Terapkan rumus pembagian waktu 25-35-30-10 menit berikut ini saat tryout!',
            'cover_image' => '',
            'author_name' => 'Tim Akademik SIAP TES',
            'reading_time' => 4,
            'views' => 630,
            'is_featured' => 0,
            'is_published' => 1,
            'tags' => 'Manajemen Waktu CAT, Tips Tryout, Strategi Ujian CAT, CPNS BUMN',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'content' => '
<p>Pernahkah Anda merasa soal ujian sebenarnya bisa Anda kerjakan, tetapi tiba-tiba layar ujian tertutup otomatis karena waktu habis sementara masih ada belasan soal yang kosong? Dalam ujian berbasis <b>Computer Assisted Test (CAT)</b>, kemampuan mengatur ritme waktu sama pentingnya dengan penguasaan materi.</p>

<h3>Rumus Alokasi Waktu 25 – 35 – 30 – 10 Menit</h3>
<p>Untuk ujian standar 100 menit (110 soal), terapkan disiplin batas waktu (<i>time-boxing</i>) berikut:</p>
<ul>
    <li><b>25 Menit Pertama (30 Soal TWK):</b> Jangan membaca wacana berulang-ulang! Baca kalimat pertanyaan terlebih dahulu, baru cari kata kuncinya di paragraf. Maksimal 50 detik per soal.</li>
    <li><b>35 Menit Kedua (35 Soal TIU):</b> Selesaikan soal figural, deret, dan verbal terlebih dahulu. Jika ada soal cerita perbandingan/kombinatorika yang rumit, tandai <b>Ragu-ragu</b> dan lewati dulu.</li>
    <li><b>30 Menit Ketiga (45 Soal TKP):</b> Fokus pada kata kunci tindakan aktif dan solutif. Jangan biarkan satu pun soal TKP kosong karena nilai terendah sekalipun tetap menyumbang 1 poin.</li>
    <li><b>10 Menit Terakhir (Review Soal Ragu-Ragu):</b> Buka panel navigasi nomor soal di sebelah kanan layar untuk memeriksa soal berwarna kuning (ragu-ragu) atau putih (belum dijawab).</li>
</ul>

<p>Latih disiplin waktu ini menggunakan fitur <b>Timer Real-Time</b> di halaman Simulasi Tryout <b>siaptes.id</b> minimal 2 kali seminggu hingga Anda menemukan ritme ternyaman Anda.</p>'
        ],
        [
            'title' => 'Cara Menghitung Skor TKP dan Strategi Memilih Jawaban Bobot 5 pada Soal Studi Kasus Pelayanan Publik & Anti-Radikalisme',
            'slug' => 'cara-menghitung-skor-tkp-strategi-jawaban-bobot-5',
            'category' => 'CPNS & PPPK',
            'excerpt' => 'Sering terjebak antara dua pilihan jawaban TKP yang sama-sama terlihat benar? Kenali ciri khas opsi bernilai 5 poin untuk masing-masing dari 6 aspek TKP sesuai kisi-kisi resmi.',
            'cover_image' => '',
            'author_name' => 'Tim Akademik SIAP TES',
            'reading_time' => 5,
            'views' => 910,
            'is_featured' => 0,
            'is_published' => 1,
            'tags' => 'Skor TKP Bobot 5, Tips TKP CPNS, Pelayanan Publik, Anti Radikalisme',
            'created_at' => date('Y-m-d H:i:s'),
            'content' => '
<p>Tes Karakteristik Pribadi (TKP) menyumbang porsi soal terbanyak dalam SKD CPNS, yaitu <b>45 soal</b> dengan skor maksimal mencapai <b>225 poin</b> (45 × 5). Namun, banyak peserta hanya mendapatkan skor di kisaran 155–165 karena terkecoh oleh dua opsi yang mirip.</p>

<h3>Kunci Memilih Opsi Bobot 5 Berdasarkan 6 Aspek TKP</h3>
<ul>
    <li><b>1. Pelayanan Publik:</b> Jika soal menyebut kata <i>pelanggan, masyarakat, pasien, pemohon, atau antrean</i>, pilih jawaban yang menunjukkan sikap <b>ramah, cekatan, memberikan solusi tuntas sesuai SOP</b>, dan mendahulukan kepentingan publik.</li>
    <li><b>2. Jejaring Kerja (Networking):</b> Jika soal berkaitan dengan <i>rekan kerja, tim lintas divisi, atau mitra</i>, pilih opsi yang <b>terbuka bekerja sama, komunikatif, dan bersinergi</b>.</li>
    <li><b>3. Sosial Budaya:</b> Jika soal menyinggung <i>perbedaan adat, kebiasaan daerah baru, atau keberagaman</i>, pilih sikap <b>cepat beradaptasi, menghormati kearifan lokal, dan bersikap netral/inklusif</b>.</li>
    <li><b>4. Teknologi Informasi & Komunikasi (TIK):</b> Pilih jawaban yang memanfaatkan <b>digitalisasi untuk efisiensi kerja</b> sekaligus menjaga keamanan data/siber instansi.</li>
    <li><b>5. Profesionalisme:</b> Tetap fokus menyelesaikan tanggung jawab pekerjaan secara tuntas meskipun ada gangguan pribadi atau tekanan beban kerja tinggi.</li>
    <li><b>6. Anti-Radikalisme:</b> Ketika dihadapkan pada isu provokasi, hoaks, atau paham ekstrem, pilih tindakan <b>klarifikasi fakta (tabayyun), berkoordinasi dengan pihak berwenang/atasan</b>, dan tidak main hakim sendiri.</li>
</ul>

<div class="article-callout">
    <b>🎯 Uji Kemampuan TKP Anda:</b> Di setiap pembahasan soal TKP pada <b>siaptes.id</b>, kami mencantumkan rincian bobot skor (5, 4, 3, 2, 1) beserta alasan mengapa opsi tersebut mendapatkan poin tertinggi!
</div>'
        ]
    ];

    $stmt = $db->prepare("
        INSERT INTO articles (title, slug, category, excerpt, content, cover_image, author_name, reading_time, views, is_featured, is_published, tags, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($articles as $a) {
        $stmt->execute([
            $a['title'],
            $a['slug'],
            $a['category'],
            $a['excerpt'],
            trim($a['content']),
            $a['cover_image'],
            $a['author_name'],
            $a['reading_time'],
            $a['views'],
            $a['is_featured'],
            $a['is_published'],
            $a['tags'],
            $a['created_at'],
            $a['created_at']
        ]);
    }
}
