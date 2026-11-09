<?php
// database/seed.php
require_once __DIR__ . '/init.php';

$db = getDB();

// 1. Seed Categories
$catCheck = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
if ($catCheck == 0) {
    $stmt = $db->prepare("INSERT INTO categories (code, name, description, passing_grade) VALUES (?, ?, ?, ?)");
    $stmt->execute(['TWK', 'Tes Wawasan Kebangsaan', 'Menguji penguasaan pengetahuan dan kemampuan mengimplementasikan nasionalisme, integritas, bela negara, pilar negara, dan bahasa Indonesia.', 65]);
    $stmt->execute(['TIU', 'Tes Intelegensia Umum', 'Menguji kemampuan verbal (analogi, silogisme, analitis), kemampuan numerik (berhitung, deret angka, perbandingan kuantitatif, soal cerita), dan kemampuan figural.', 80]);
    $stmt->execute(['TKP', 'Tes Karakteristik Pribadi', 'Menguji pelayanan publik, jejaring kerja, sosial budaya, teknologi informasi dan komunikasi, profesionalisme, dan anti radikalisme.', 166]);
    echo "Categories seeded.\n";
}

// 2. Seed Admin & Demo User
$userCheck = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($userCheck == 0) {
    $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    // admin@cpns.go.id / admin123
    $stmt->execute(['Administrator CPNS', 'admin@cpns.go.id', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);
    // peserta@cpns.go.id / peserta123
    $stmt->execute(['Budi Santoso (Calon ASN)', 'peserta@cpns.go.id', password_hash('peserta123', PASSWORD_DEFAULT), 'user']);
    echo "Users seeded (admin: admin@cpns.go.id / admin123, user: peserta@cpns.go.id / peserta123).\n";
}

// 3. Seed Tryout Package
$pkgCheck = $db->query("SELECT COUNT(*) FROM packages")->fetchColumn();
if ($pkgCheck == 0) {
    $stmt = $db->prepare("INSERT INTO packages (title, description, duration_minutes, is_active) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        'Tryout Akbar SKD CPNS 2024/2025 Paket 1',
        'Simulasi CAT BKN lengkap dengan passing grade standar resmi pemerintah: TWK (65), TIU (80), TKP (166). Total durasi 100 menit.',
        100,
        1
    ]);
    $stmt->execute([
        'Mini Tryout SKD - Simulasi Cepat (30 Menit)',
        'Latihan simulasi cepat 30 menit untuk melatih manajemen waktu pengerjaan soal ujian.',
        30,
        1
    ]);
    echo "Packages seeded.\n";
}

// 4. Seed Questions
$qCheck = $db->query("SELECT COUNT(*) FROM questions")->fetchColumn();
if ($qCheck == 0) {
    $twkId = $db->query("SELECT id FROM categories WHERE code = 'TWK'")->fetchColumn();
    $tiuId = $db->query("SELECT id FROM categories WHERE code = 'TIU'")->fetchColumn();
    $tkpId = $db->query("SELECT id FROM categories WHERE code = 'TKP'")->fetchColumn();
    $pkgId = $db->query("SELECT id FROM packages LIMIT 1")->fetchColumn();

    $qStmt = $db->prepare("
        INSERT INTO questions 
        (category_id, package_id, question_text, option_a, option_b, option_c, option_d, option_e, correct_option, score_a, score_b, score_c, score_d, score_e, explanation) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $questions = [
        // ==================== TWK ====================
        [
            $twkId, $pkgId,
            "Pancasila sebagai dasar negara secara yuridis formal disahkan oleh Panitia Persiapan Kemerdekaan Indonesia (PPKI) pada tanggal...",
            "17 Agustus 1945",
            "18 Agustus 1945",
            "19 Agustus 1945",
            "22 Juni 1945",
            "1 Juni 1945",
            "B", 0, 0, 0, 0, 0,
            "Pancasila disahkan sebagai dasar negara oleh PPKI pada tanggal 18 Agustus 1945 bersamaan dengan pengesahan UUD 1945."
        ],
        [
            $twkId, $pkgId,
            "Berdasarkan UUD 1945 Pasal 14 ayat (1), Presiden memberi grasi dan rehabilitasi dengan memperhatikan pertimbangan dari...",
            "Dewan Perwakilan Rakyat (DPR)",
            "Mahkamah Agung (MA)",
            "Mahkamah Konstitusi (MK)",
            "Komisi Yudisial (KY)",
            "Menteri Hukum dan HAM",
            "B", 0, 0, 0, 0, 0,
            "Pasal 14 ayat (1) UUD 1945 menyatakan: 'Presiden memberi grasi dan rehabilitasi dengan memperhatikan pertimbangan Mahkamah Agung.'"
        ],
        [
            $twkId, $pkgId,
            "Sikap yang mencerminkan pengamalan sila ketiga Pancasila 'Persatuan Indonesia' dalam kehidupan bermasyarakat adalah...",
            "Menghargai hasil karya orang lain",
            "Rela berkorban untuk kepentingan bangsa dan negara di atas kepentingan pribadi/golongan",
            "Menjunjung tinggi hak asasi manusia",
            "Memajukan perbuatan yang luhur yang mencerminkan sikap kekeluargaan",
            "Menghormati kebebasan beribadah sesuai agama masing-masing",
            "B", 0, 0, 0, 0, 0,
            "Butir Sila ke-3 antara lain: menempatkan persatuan, kesatuan, serta kepentingan dan keselamatan bangsa dan negara sebagai kepentingan bersama di atas kepentingan pribadi atau golongan, serta rela berkorban untuk kepentingan negara dan bangsa."
        ],
        [
            $twkId, $pkgId,
            "Penetapan wilayah perairan Indonesia menjadi satu kesatuan laut nusantara yang menghubungkan antarpulau dinyatakan pertama kali secara tegas melalui...",
            "Perjanjian Linggarjati 1946",
            "Deklarasi Djuanda 13 Desember 1957",
            "Konferensi Meja Bundar 1949",
            "Dekrit Presiden 5 Juli 1959",
            "UNCLOS 1982",
            "B", 0, 0, 0, 0, 0,
            "Deklarasi Djuanda dicetuskan pada 13 Desember 1957 oleh PM Djuanda Kartawidjaja, menyatakan bahwa batas laut teritorial Indonesia ditarik 12 mil dari garis yang menghubungkan titik terluar pulau-pulau Indonesia."
        ],
        [
            $twkId, $pkgId,
            "Penyelenggaraan negara yang bersih dan bebas dari Korupsi, Kolusi, dan Nepotisme (KKN) diatur dalam regulasi nasional yaitu...",
            "UU No. 28 Tahun 1999",
            "UU No. 31 Tahun 1999",
            "UU No. 20 Tahun 2001",
            "UU No. 30 Tahun 2002",
            "UU No. 5 Tahun 2014",
            "A", 0, 0, 0, 0, 0,
            "UU No. 28 Tahun 1999 secara khusus mengatur tentang Penyelenggaraan Negara yang Bersih dan Bebas dari Korupsi, Kolusi, dan Nepotisme."
        ],

        // ==================== TIU ====================
        [
            $tiuId, $pkgId,
            "MOBILITAS : GERAK = ESTIMASI : ...",
            "Perkiraan",
            "Kecepatan",
            "Kestabilan",
            "Penetapan",
            "Kekuatan",
            "A", 0, 0, 0, 0, 0,
            "Mobilitas bersinonim atau memiliki hubungan makna dengan gerak. Sedangkan estimasi bermakna perkiraan/taksiran."
        ],
        [
            $tiuId, $pkgId,
            "Semua atlet memiliki kondisi fisik yang prima.\nSebagian mahasiswa Universitas Gadjah Mada adalah atlet.\nKesimpulan yang sah adalah...",
            "Semua mahasiswa Universitas Gadjah Mada memiliki kondisi fisik yang prima.",
            "Sebagian mahasiswa Universitas Gadjah Mada memiliki kondisi fisik yang prima.",
            "Hanya mahasiswa atlet yang memiliki kondisi fisik prima.",
            "Sebagian atlet bukan mahasiswa Universitas Gadjah Mada.",
            "Semua atlet adalah mahasiswa Universitas Gadjah Mada.",
            "B", 0, 0, 0, 0, 0,
            "Silogisme kategori: Sebagian mahasiswa UGM adalah atlet, dan semua atlet fisik prima -> Sebagian mahasiswa UGM berfisik prima."
        ],
        [
            $tiuId, $pkgId,
            "Tentukan angka berikutnya pada deret berikut:\n3, 6, 12, 21, 33, ...",
            "42",
            "45",
            "48",
            "51",
            "54",
            "C", 0, 0, 0, 0, 0,
            "Pola selisih antar angka:\n6 - 3 = +3\n12 - 6 = +6\n21 - 12 = +9\n33 - 21 = +12\nBerikutnya bertambah +15 -> 33 + 15 = 48."
        ],
        [
            $tiuId, $pkgId,
            "Sebuah pekerjaan dapat diselesaikan oleh 12 orang pekerja dalam waktu 20 hari. Jika pekerjaan tersebut ingin diselesaikan dalam waktu 15 hari, berapa jumlah pekerja tambahan yang dibutuhkan?",
            "4 orang",
            "6 orang",
            "8 orang",
            "16 orang",
            "20 orang",
            "A", 0, 0, 0, 0, 0,
            "Perbandingan berbalik nilai: Total beban kerja = 12 × 20 = 240 orang-hari. Jika ingin selesai dalam 15 hari, pekerja yang dibutuhkan = 240 / 15 = 16 orang. Tambahan pekerja = 16 - 12 = 4 orang."
        ],
        [
            $tiuId, $pkgId,
            "Jika x = 25% dari 120 dan y = 1/5 dari 150, maka hubungan antara x dan y adalah...",
            "x > y",
            "x < y",
            "x = y",
            "2x = 3y",
            "Hubungan x dan y tidak dapat ditentukan",
            "C", 0, 0, 0, 0, 0,
            "x = 0,25 × 120 = 30.\ny = (1/5) × 150 = 30.\nMaka x = y."
        ],

        // ==================== TKP ====================
        [
            $tkpId, $pkgId,
            "Ketika Anda sedang melayani masyarakat di loket pelayanan publik 10 menit sebelum jam istirahat siang, datang seorang warga lansia yang tergesa-gesa memerlukan pengesahan dokumen penting untuk pengobatan. Sikap Anda adalah...",
            "Memintanya datang kembali setelah jam istirahat selesai karena loket segera ditutup.",
            "Tetap melayaninya dengan ramah dan menyelesaikan dokumen tersebut dengan tuntas meski waktu istirahat Anda berkurang beberapa menit.",
            "Melayaninya dengan sedikit mengeluh agar warga tersebut tahu bahwa jam istirahat sudah hampir tiba.",
            "Menyarankannya untuk meminta bantuan petugas keamanan di luar loket.",
            "Meminta rekan kerja lain yang sedang tidak sibuk untuk melayani warga tersebut.",
            null, 1, 5, 2, 3, 4,
            "Aspek Pelayanan Publik & Integritas: Memberikan pelayanan optimal kepada masyarakat terutama kelompok rentan/mendesak mencerminkan dedikasi ASN (Poin 5 untuk Opsi B)."
        ],
        [
            $tkpId, $pkgId,
            "Atasan di instansi Anda mengumumkan kebijakan baru berupa penggunaan sistem absensi digital dan pelaporan kinerja harian berbasis aplikasi web yang belum pernah Anda gunakan sebelumnya. Respon Anda adalah...",
            "Menolak secara halus karena metode lama dengan tanda tangan manual masih sangat efektif.",
            "Menunggu rekan lain mencobanya terlebih dahulu sebelum saya ikut menggunakan.",
            "Mempelajari sistem baru tersebut secara proaktif melalui tutorial dan bertanya kepada tim IT agar dapat segera memanfaatkannya dengan lancar.",
            "Menggunakan aplikasi seadanya hanya jika ditegur oleh atasan.",
            "Mengusulkan agar sistem baru tersebut ditunda sampai seluruh pegawai mengikuti pelatihan resmi.",
            null, 1, 2, 5, 3, 4,
            "Aspek Teknologi Informasi & Adaptabilitas: ASN harus cepat beradaptasi dengan inovasi digital untuk efisiensi birokrasi (Poin 5 untuk Opsi C)."
        ],
        [
            $tkpId, $pkgId,
            "Dalam suatu kelompok kerja proyek instansi, seorang rekan tim Anda sering terlambat menyelesaikan bagian tugasnya sehingga menghambat kemajuan proyek secara keseluruhan. Tindakan Anda adalah...",
            "Mengeluarkan rekan tersebut dari kelompok kerja dan melaporkan ke pimpinan instansi.",
            "Mengajaknya berbicara empat mata secara baik-baik, menanyakan kendala yang dihadapinya, serta mencari solusi bersama agar tugas lekas selesai.",
            "Mengerjakan seluruh bagian tugas rekan tersebut sendiri tanpa memberitahunya.",
            "Menyindir kelakuannya di grup percakapan kantor agar rekan lain ikut menilainya.",
            "Membiarkannya saja karena masing-masing orang memiliki tanggung jawab tersendiri.",
            null, 2, 5, 3, 1, 2,
            "Aspek Jejaring Kerja & Kerjasama Tim: Mengedepankan komunikasi solutif dan empati profesional untuk keberhasilan tim (Poin 5 untuk Opsi B)."
        ],
        [
            $tkpId, $pkgId,
            "Anda menerima informasi hoaks di grup pesan instan yang bernada provokatif dan berpotensi memecah belah persatuan antarumat beragama di lingkungan Anda. Respon Anda yang paling tepat adalah...",
            "Ikut menyebarkan ke grup lain agar orang lain waspada.",
            "Mendiamkan saja dan menghapus pesan tersebut dari ponsel Anda.",
            "Memeriksa kebenaran informasi melalui sumber berita kredibel/resmi, lalu memberikan klarifikasi sopan di grup agar warga tidak terprovokasi.",
            "Langsung keluar dari grup pesan instan tersebut karena merasa terganggu.",
            "Memarahi orang yang mengirim pesan tersebut dengan kata-kata keras.",
            null, 1, 2, 5, 2, 3,
            "Aspek Anti-Radikalisme & Sosial Budaya: Menjaga kerukunan, mencegah perpecahan, dan mengedukasi masyarakat secara bijak (Poin 5 untuk Opsi C)."
        ],
        [
            $tkpId, $pkgId,
            "Instansi tempat Anda bekerja menugaskan Anda ke daerah terpencil di luar pulau yang minim fasilitas perkotaan selama 1 tahun. Sikap Anda dalam menerima penugasan tersebut adalah...",
            "Menerima penugasan tersebut sebagai tantangan baru dan peluang mengabdi serta memperluas wawasan kebangsaan.",
            "Mencari alasan kesehatan agar penugasan dibatalkan atau dialihkan ke rekan lain.",
            "Menerima tugas tersebut namun meminta tunjangan tambahan yang besar terlebih dahulu.",
            "Menolak tugas karena merasa tidak terbiasa tinggal di daerah terpencil.",
            "Menerima penugasan dengan terpaksa demi menjaga status kepegawaian.",
            null, 5, 1, 3, 1, 2,
            "Aspek Profesionalisme & Pengabdian: Kesediaan ditempatkan di seluruh wilayah NKRI adalah kewajiban integritas ASN (Poin 5 untuk Opsi A)."
        ]
    ];

    foreach ($questions as $q) {
        $qStmt->execute($q);
    }
    echo "Questions seeded successfully (" . count($questions) . " sample questions with real CPNS weights).\n";
}
