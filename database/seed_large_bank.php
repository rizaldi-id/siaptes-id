<?php
// database/seed_large_bank.php
require_once __DIR__ . '/../config/database.php';

$db = getDB();

echo "=== MEMULAI SEED BANK SOAL LENGKAP CPNS SKD ===\n";

$twkId = $db->query("SELECT id FROM categories WHERE code = 'TWK'")->fetchColumn();
$tiuId = $db->query("SELECT id FROM categories WHERE code = 'TIU'")->fetchColumn();
$tkpId = $db->query("SELECT id FROM categories WHERE code = 'TKP'")->fetchColumn();

if (!$twkId || !$tiuId || !$tkpId) {
    die("Kategori TWK, TIU, TKP belum ditemukan. Jalankan database/init.php terlebih dahulu.\n");
}

// Pastikan paket tersedia
$pkgAkbar = $db->query("SELECT id FROM packages WHERE id = 1")->fetchColumn();
if (!$pkgAkbar) {
    $db->prepare("INSERT INTO packages (id, title, description, duration_minutes, is_premium, price, is_active) VALUES (1, 'Tryout Akbar SKD CPNS 2024/2025 Paket 1', 'Simulasi resmi CAT BKN lengkap 110 butir soal: TWK (30 soal), TIU (35 soal), TKP (45 soal) dengan passing grade resmi BKN.', 100, 1, 49000, 1)")->execute();
    $pkgAkbar = 1;
}

$pkgMini = $db->query("SELECT id FROM packages WHERE id = 2")->fetchColumn();
if (!$pkgMini) {
    $db->prepare("INSERT INTO packages (id, title, description, duration_minutes, is_premium, price, is_active) VALUES (2, 'Mini Tryout SKD - Simulasi Cepat (30 Menit)', 'Paket uji coba gratis 30 butir soal (10 TWK, 10 TIU, 10 TKP) untuk melatih ritme dan kecepatan pengerjaan.', 30, 0, 0, 1)")->execute();
    $pkgMini = 2;
}

// Bersihkan data soal lama agar fresh dan terstruktur rapi sesuai standar resmi
$db->exec("DELETE FROM user_answers");
$db->exec("DELETE FROM exam_attempts");
$db->exec("DELETE FROM questions");

$qStmt = $db->prepare("
    INSERT INTO questions 
    (category_id, package_id, question_text, option_a, option_b, option_c, option_d, option_e, correct_option, score_a, score_b, score_c, score_d, score_e, explanation) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$count = 0;

// =========================================================================
// 1. BANK SOAL TWK (TES WAWASAN KEBANGSAAN) - 30 SOAL PAKET AKBAR + 10 SOAL MINI
// =========================================================================

$twkQuestions = [
    // --- TWK PAKET 1 (30 Soal Standar Ujian CAT) ---
    [
        $twkId, 1,
        "Pancasila sebagai dasar negara secara yuridis formal disahkan oleh Panitia Persiapan Kemerdekaan Indonesia (PPKI) pada tanggal...",
        "17 Agustus 1945", "18 Agustus 1945", "19 Agustus 1945", "22 Juni 1945", "1 Juni 1945",
        "B", 0, 0, 0, 0, 0,
        "Pancasila disahkan secara yuridis formal sebagai dasar negara oleh PPKI pada sidang tanggal 18 Agustus 1945 bersamaan dengan penetapan UUD 1945 serta pengangkatan Presiden dan Wakil Presiden."
    ],
    [
        $twkId, 1,
        "Berdasarkan naskah resmi Piagam Jakarta (Jakarta Charter) tanggal 22 Juni 1945, rumusan sila pertama Pancasila sebelum diubah pada sidang PPKI adalah...",
        "Ketuhanan Yang Maha Esa",
        "Ketuhanan dengan kewajiban menjalankan syariat Islam bagi pemeluk-pemeluknya",
        "Ketuhanan Yang Berkebudayaan",
        "Ketuhanan Yang Maha Esa dan Kemanusiaan",
        "Negara berdasar atas Ketuhanan dengan perlindungan agama",
        "B", 0, 0, 0, 0, 0,
        "Pada naskah asli Piagam Jakarta (22 Juni 1945), sila pertama berbunyi 'Ketuhanan dengan kewajiban menjalankan syariat Islam bagi pemeluk-pemeluknya'. Demi menjaga persatuan bangsa Indonesia timur, frasa tersebut diubah oleh PPKI pada 18 Agustus 1945 menjadi 'Ketuhanan Yang Maha Esa'."
    ],
    [
        $twkId, 1,
        "Sikap seorang aparatur sipil negara yang menolak secara tegas segala bentuk pemberian uang pelicin atau gratifikasi dalam proses perizinan merupakan pengamalan Pancasila, khususnya sila...",
        "Pertama", "Kedua", "Ketiga", "Keempat", "Kelima",
        "B", 0, 0, 0, 0, 0,
        "Sila Kedua 'Kemanusiaan yang Adil dan Beradab' menuntut sikap integritas, menjunjung tinggi nilai-nilai kebenaran, kejujuran moral, dan memperlakukan sesama secara adil tanpa penyalahgunaan wewenang."
    ],
    [
        $twkId, 1,
        "Mengutamakan musyawarah untuk mencapai mufakat dalam menyelesaikan perbedaan pendapat di forum rapat kantor merupakan butir pengamalan sila...",
        "Keadilan Sosial bagi Seluruh Rakyat Indonesia",
        "Persatuan Indonesia",
        "Kerakyatan yang Dipimpin oleh Hikmat Kebijaksanaan dalam Permusyawaratan/Perwakilan",
        "Kemanusiaan yang Adil dan Beradab",
        "Ketuhanan Yang Maha Esa",
        "C", 0, 0, 0, 0, 0,
        "Sila Keempat menekankan pengambilan keputusan bersama melalui musyawarah mufakat yang dilandasi akal sehat dan hati nurani yang luhur."
    ],
    [
        $twkId, 1,
        "Tidak bergaya hidup mewah, tidak boros, serta gemar menghargai hasil karya orang lain yang bermanfaat bagi kemajuan bersama merupakan wujud pengamalan sila...",
        "Sila ke-1", "Sila ke-2", "Sila ke-3", "Sila ke-4", "Sila ke-5",
        "E", 0, 0, 0, 0, 0,
        "Butir Sila Kelima 'Keadilan Sosial bagi Seluruh Rakyat Indonesia' mengamanatkan untuk tidak menggunakan hak milik yang bersifat pemborosan dan gaya hidup mewah, serta menghargai hasil karya orang lain."
    ],
    [
        $twkId, 1,
        "Menurut Pasal 1 ayat (2) UUD NRI 1945 pasca amandemen, kedaulatan berada di tangan rakyat dan dilaksanakan menurut...",
        "Ketetapan Majelis Permusyawaratan Rakyat",
        "Undang-Undang Dasar",
        "Presiden sebagai Kepala Negara",
        "Dewan Perwakilan Rakyat",
        "Musyawarah mufakat",
        "B", 0, 0, 0, 0, 0,
        "Pasal 1 ayat (2) UUD 1945 hasil amandemen ketiga berbunyi: 'Kedaulatan berada di tangan rakyat dan dilaksanakan menurut Undang-Undang Dasar.'"
    ],
    [
        $twkId, 1,
        "Lembaga negara yang berwenang menguji undang-undang terhadap Undang-Undang Dasar (Judicial Review) adalah...",
        "Mahkamah Agung", "Mahkamah Konstitusi", "Komisi Yudisial", "Badan Pemeriksa Keuangan", "Dewan Perwakilan Rakyat",
        "B", 0, 0, 0, 0, 0,
        "Sesuai Pasal 24C ayat (1) UUD 1945, Mahkamah Konstitusi berwenang mengadili pada tingkat pertama dan terakhir yang putusannya bersifat final untuk menguji undang-undang terhadap Undang-Undang Dasar."
    ],
    [
        $twkId, 1,
        "Presiden memberi grasi dan rehabilitasi dengan memperhatikan pertimbangan dari...",
        "Dewan Perwakilan Rakyat (DPR)", "Mahkamah Agung (MA)", "Mahkamah Konstitusi (MK)", "Komisi Yudisial (KY)", "Kejaksaan Agung",
        "B", 0, 0, 0, 0, 0,
        "Pasal 14 ayat (1) UUD 1945 menyatakan: 'Presiden memberi grasi dan rehabilitasi dengan memperhatikan pertimbangan Mahkamah Agung.'"
    ],
    [
        $twkId, 1,
        "Presiden memberi amnesti dan abolisi dengan memperhatikan pertimbangan dari lembaga...",
        "Dewan Perwakilan Rakyat (DPR)", "Mahkamah Agung (MA)", "Mahkamah Konstitusi (MK)", "Majelis Permusyawaratan Rakyat (MPR)", "Kementerian Hukum dan HAM",
        "A", 0, 0, 0, 0, 0,
        "Pasal 14 ayat (2) UUD 1945 menyatakan: 'Presiden memberi amnesti dan abolisi dengan memperhatikan pertimbangan Dewan Perwakilan Rakyat.'"
    ],
    [
        $twkId, 1,
        "Hak DPR untuk melakukan penyelidikan terhadap pelaksanaan suatu undang-undang atau kebijakan pemerintah yang penting, strategis, dan berdampak luas disebut hak...",
        "Interpelasi", "Angket", "Menyatakan Pendapat", "Imunitas", "Budget",
        "B", 0, 0, 0, 0, 0,
        "Hak Angket adalah hak DPR untuk melakukan penyelidikan terhadap pelaksanaan suatu undang-undang dan/atau kebijakan pemerintah yang berkaitan dengan hal penting, strategis, dan berdampak luas bagi kehidupan bermasyarakat, berbangsa, dan bernegara."
    ],
    [
        $twkId, 1,
        "Amandemen Undang-Undang Dasar Negara Republik Indonesia Tahun 1945 dilakukan sebanyak empat kali dalam kurun waktu tahun...",
        "1998 - 2001", "1999 - 2002", "1999 - 2004", "2000 - 2003", "1998 - 2002",
        "B", 0, 0, 0, 0, 0,
        "Amandemen UUD 1945 berlangsung empat kali: Sidang Umum MPR 1999 (I), Sidang Tahunan MPR 2000 (II), Sidang Tahunan MPR 2001 (III), dan Sidang Tahunan MPR 2002 (IV)."
    ],
    [
        $twkId, 1,
        "Pasal dalam UUD 1945 yang secara tegas mengatur hak warga negara atas jaminan penghidupan yang layak bagi kemanusiaan terdapat pada...",
        "Pasal 27 ayat (1)", "Pasal 27 ayat (2)", "Pasal 28 ayat (1)", "Pasal 30 ayat (1)", "Pasal 31 ayat (1)",
        "B", 0, 0, 0, 0, 0,
        "Pasal 27 ayat (2) UUD 1945 berbunyi: 'Tiap-tiap warga negara berhak atas pekerjaan dan penghidupan yang layak bagi kemanusiaan.'"
    ],
    [
        $twkId, 1,
        "Penetapan wilayah kelautan Indonesia menjadi satu kesatuan wilayah nusantara yang menghubungkan antarpulau dinyatakan pertama kali secara berani melalui...",
        "Perjanjian Roem-Royen 1949", "Deklarasi Djuanda 13 Desember 1957", "Konferensi Meja Bundar 1949", "Dekrit Presiden 5 Juli 1959", "Konvensi Jenewa 1958",
        "B", 0, 0, 0, 0, 0,
        "Deklarasi Djuanda dicetuskan pada 13 Desember 1957 oleh Perdana Menteri Djuanda Kartawidjaja, menyatakan bahwa laut di sekitar, di antara, dan di dalam kepulauan Indonesia menjadi satu kesatuan kedaulatan NKRI."
    ],
    [
        $twkId, 1,
        "Kongres Pemuda II yang melahirkan ikrar monumental 'Sumpah Pemuda' diselenggarakan pada tanggal...",
        "28 Oktober 1908", "20 Mei 1928", "28 Oktober 1928", "17 Agustus 1945", "1 Juni 1945",
        "C", 0, 0, 0, 0, 0,
        "Sumpah Pemuda diikrarkan pada Kongres Pemuda II tanggal 28 Oktober 1928 di Batavia (Jakarta), menegaskan satu tumpah darah, satu bangsa, dan satu bahasa persatuan: Indonesia."
    ],
    [
        $twkId, 1,
        "Organisasi pergerakan nasional pertama di Indonesia yang berorientasi modern dan berdiri pada tanggal 20 Mei 1908 adalah...",
        "Sarekat Islam", "Indische Partij", "Budi Utomo", "Perhimpunan Indonesia", "Muhammadiyah",
        "C", 0, 0, 0, 0, 0,
        "Budi Utomo didirikan pada 20 Mei 1908 oleh Dr. Soetomo dan para pelajar STOVIA atas dorongan Dr. Wahidin Soedirohoesodo, yang diperingati sebagai Hari Kebangkitan Nasional."
    ],
    [
        $twkId, 1,
        "Semboyan 'Bhinneka Tunggal Ika' berasal dari kitab kuno masa Kerajaan Majapahit, yaitu Kitab Sutasoma yang dikarang oleh pujangga bernama...",
        "Mpu Prapanca", "Mpu Tantular", "Mpu Sedah", "Mpu Panuluh", "Mpu Gandring",
        "B", 0, 0, 0, 0, 0,
        "Frasa 'Bhinneka Tunggal Ika tan hana dharma mangrwa' berasal dari Kakawin Sutasoma karangan Mpu Tantular pada masa kejayaan Kerajaan Majapahit di bawah Raja Hayam Wuruk."
    ],
    [
        $twkId, 1,
        "Berdasarkan UU No. 23 Tahun 2019 tentang Pengelolaan Sumber Daya Nasional untuk Pertahanan Negara, yang BUKAN merupakan nilai dasar Bela Negara adalah...",
        "Cinta tanah air",
        "Kesadaran berbangsa dan bernegara",
        "Setia pada Pancasila sebagai ideologi negara",
        "Mengutamakan kepentingan golongan tertentu",
        "Rela berkorban untuk bangsa dan negara",
        "D", 0, 0, 0, 0, 0,
        "5 Nilai Dasar Bela Negara: 1. Cinta tanah air, 2. Sadar berbangsa dan bernegara, 3. Setia pada Pancasila, 4. Rela berkorban, 5. Kemampuan awal bela negara. Pilihan D bertentangan dengan esensi bela negara."
    ],
    [
        $twkId, 1,
        "Sistem pertahanan dan keamanan negara yang dianut Indonesia menurut Pasal 30 ayat (2) UUD 1945 adalah sistem...",
        "Pertahanan Militer Terpusat",
        "Pertahanan dan Keamanan Rakyat Semesta (Sishankamrata)",
        "Pertahanan Teritorial Mandiri",
        "Pertahanan Wilayah Perbatasan",
        "Pertahanan Sipil Khusus",
        "B", 0, 0, 0, 0, 0,
        "Pasal 30 ayat (2) UUD 1945 menegaskan bahwa pertahanan dan keamanan negara dilaksanakan melalui sistem pertahanan dan keamanan rakyat semesta (Sishankamrata) dengan TNI dan Polri sebagai kekuatan utama, dan rakyat sebagai kekuatan pendukung."
    ],
    [
        $twkId, 1,
        "Core Values ASN yang diluncurkan oleh Presiden Joko Widodo pada 27 Juli 2021 disingkat dengan akronim...",
        "BERSIH", "AMANAH", "BerAKHLAK", "INTEGRITAS", "UNGGUL",
        "C", 0, 0, 0, 0, 0,
        "Core Values ASN adalah 'BerAKHLAK', yaitu singkatan dari: Berorientasi Pelayanan, Akuntabel, Kompeten, Harmonis, Loyal, Adaptif, dan Kolaboratif."
    ],
    [
        $twkId, 1,
        "Regulasi pokok yang mengatur tentang pemberantasan tindak pidana korupsi di Indonesia termuat dalam...",
        "UU No. 31 Tahun 1999 jo UU No. 20 Tahun 2001",
        "UU No. 28 Tahun 1999",
        "UU No. 5 Tahun 2014",
        "UU No. 30 Tahun 2002",
        "UU No. 19 Tahun 2019",
        "A", 0, 0, 0, 0, 0,
        "Tindak Pidana Korupsi secara substantif diatur dalam UU No. 31 Tahun 1999 yang telah diubah dengan UU No. 20 Tahun 2001."
    ],
    [
        $twkId, 1,
        "Salah satu bentuk ancaman non-militer berdimensi ideologi yang paling berbahaya bagi integrasi bangsa di era digital adalah...",
        "Serangan agresi bersenjata",
        "Penyebaran paham ekstremisme, intoleransi, dan radikalisme melalui media sosial",
        "Pencurian ikan di zona ekonomi eksklusif",
        "Pelanggaran wilayah udara oleh pesawat asing",
        "Bencana alam gempa bumi dan tsunami",
        "B", 0, 0, 0, 0, 0,
        "Ancaman ideologi non-militer kontemporer beroperasi melalui penyebaran propaganda intoleransi, radikalisme, dan hoaks pemecah belah persatuan di ruang digital."
    ],
    [
        $twkId, 1,
        "Tokoh nasional yang memimpin delegasi Republik Indonesia dalam Konferensi Meja Bundar (KMB) di Den Haag, Belanda pada tahun 1949 adalah...",
        "Ir. Soekarno", "Drs. Mohammad Hatta", "Sutan Sjahrir", "Mr. Mohammad Roem", "Dr. Soetomo",
        "B", 0, 0, 0, 0, 0,
        "Drs. Mohammad Hatta memimpin delegasi Indonesia dalam KMB (23 Agustus - 2 November 1949) yang menghasilkan pengakuan kedaulatan Indonesia oleh Belanda."
    ],
    [
        $twkId, 1,
        "Pemberian kewenangan daerah untuk mengatur dan mengurus kepentingan masyarakat setempat menurut prakarsa sendiri berdasarkan aspirasi rakyat disebut asas...",
        "Dekonsentrasi", "Tugas Pembantuan", "Desentralisasi / Otonomi Daerah", "Sentralisasi", "Medebewind",
        "C", 0, 0, 0, 0, 0,
        "Desentralisasi adalah penyerahan urusan pemerintahan oleh pemerintah pusat kepada daerah otonom berdasarkan asas otonomi."
    ],
    [
        $twkId, 1,
        "Penulisan kata serapan yang sesuai dengan pedoman Ejaan yang Disempurnakan (EYD V) berikut ini adalah...",
        "Analisa, kwalitas, praktek",
        "Analisis, kualitas, praktik",
        "Analisa, kualitas, praktek",
        "Analisis, kwalitas, praktik",
        "Analisa, kualitet, praktikum",
        "B", 0, 0, 0, 0, 0,
        "Bentuk baku menurut KBBI dan EYD V adalah: 'analisis' (bukan analisa), 'kualitas' (bukan kwalitas), dan 'praktik' (bukan praktek)."
    ],
    [
        $twkId, 1,
        "Kalimat berikut yang memenuhi syarat sebagai kalimat efektif adalah...",
        "Bagi seluruh peserta seleksi diharapkan untuk hadir tepat waktu.",
        "Rapat kerja tersebut membicarakan daripada anggaran instansi tahun 2026.",
        "Direktur meresmikan gedung baru pelayanan publik terpadu di pusat kota.",
        "Dalam musyawarah itu menghasilkan beberapa keputusan strategis.",
        "Meskipun hari hujan lebat, tetapi pelayanan kantor tetap dibuka.",
        "C", 0, 0, 0, 0, 0,
        "Opsi C memiliki subjek (Direktur), predikat (meresmikan), dan objek (gedung baru) yang lugas tanpa kerancuan konjungsi ganda atau preposisi perusak subjek."
    ],
    [
        $twkId, 1,
        "Berdasarkan UUD 1945 Pasal 23A, pajak dan pungutan lain yang bersifat memaksa untuk keperluan negara diatur dengan...",
        "Peraturan Pemerintah", "Keputusan Presiden", "Undang-Undang", "Peraturan Menteri Keuangan", "Instruksi Presiden",
        "C", 0, 0, 0, 0, 0,
        "Pasal 23A UUD 1945 berbunyi: 'Pajak dan pungutan lain yang bersifat memaksa untuk keperluan negara diatur dengan undang-undang.'"
    ],
    [
        $twkId, 1,
        "Peristiwa penculikan Ir. Soekarno dan Drs. Mohammad Hatta oleh para pemuda pejuang ke sebuah wilayah di Karawang sebelum proklamasi dikenal dengan peristiwa...",
        "Rengasdengklok", "Ambarawa", "Bandung Lautan Api", "Medan Area", "Puputan Margarana",
        "A", 0, 0, 0, 0, 0,
        "Peristiwa Rengasdengklok (16 Agustus 1945) bertujuan mendesak Soekarno dan Hatta agar segera memproklamasikan kemerdekaan Indonesia tanpa menunggu persetujuan Jepang."
    ],
    [
        $twkId, 1,
        "Makna alinea pertama Pembukaan UUD 1945 'Bahwa sesungguhnya kemerdekaan itu ialah hak segala bangsa...' mengandung dalil objektif bahwa...",
        "Bangsa Indonesia menentang segala bentuk penjajahan karena tidak sesuai dengan perikemanusiaan dan perikeadilan",
        "Kemerdekaan Indonesia semata-mata berkat perjuangan bangsa sendiri tanpa bantuan bangsa lain",
        "Pemerintah Indonesia menolak kerja sama internasional",
        "Setiap warga negara wajib menjadi anggota militer",
        "Hukum nasional berada di atas hukum moral dunia",
        "A", 0, 0, 0, 0, 0,
        "Alinea I Pembukaan UUD 1945 memuat dalil objektif bahwa penjajahan tidak sesuai dengan perikemanusiaan dan perikeadilan sehingga harus dihapuskan di muka bumi."
    ],
    [
        $twkId, 1,
        "Konsep Wawasan Nusantara sebagai geopolitik Indonesia menetapkan bahwa kepulauan Indonesia merupakan satu kesatuan di bidang...",
        "Politik, Ekonomi, Sosial Budaya, dan Pertahanan Keamanan (POLEKSOSBUDHANKAM)",
        "Hanya bidang militer dan pertahanan teritorial",
        "Hanya bidang perdagangan dan perikanan laut",
        "Birokrasi pemerintahan pusat dan daerah",
        "Kerjasama moneter regional ASEAN",
        "A", 0, 0, 0, 0, 0,
        "Wawasan Nusantara memandang kepulauan Indonesia sebagai satu kesatuan utuh dalam bidang Politik, Ekonomi, Sosial Budaya, serta Pertahanan dan Keamanan."
    ],
    [
        $twkId, 1,
        "Lembaga negara mandiri yang berwenang memeriksa pengelolaan dan tanggung jawab tentang keuangan negara menurut Pasal 23E UUD 1945 adalah...",
        "Komisi Pemberantasan Korupsi (KPK)", "Badan Pemeriksa Keuangan (BPK)", "Badan Pengawasan Keuangan dan Pembangunan (BPKP)", "Kementerian Keuangan", "Otoritas Jasa Keuangan (OJK)",
        "B", 0, 0, 0, 0, 0,
        "Pasal 23E ayat (1) UUD 1945 menyatakan: 'Untuk memeriksa pengelolaan dan tanggung jawab tentang keuangan negara diadakan satu Badan Pemeriksa Keuangan yang bebas dan mandiri.'"
    ]
];

// =========================================================================
// 2. BANK SOAL TIU (TES INTELEGENSIA UMUM) - 35 SOAL PAKET AKBAR
// =========================================================================

$tiuQuestions = [
    // 1-5: Analogi Kata
    [
        $tiuId, 1,
        "MOBILITAS : PINDAH = ESTIMASI : ...",
        "Perkiraan", "Pengurangan", "Kepastian", "Pengukuran", "Kecepatan",
        "A", 0, 0, 0, 0, 0,
        "Mobilitas berkaitan erat atau bersinonim dengan pindah/gerak. Estimasi bermakna perkiraan/taksiran."
    ],
    [
        $tiuId, 1,
        "APOTEKER : OBAT = KOKI : ...",
        "Dapur", "Restoran", "Masakan", "Pisau", "Pelayan",
        "C", 0, 0, 0, 0, 0,
        "Apoteker menghasilkan atau meracik obat. Koki menghasilkan atau meracik masakan."
    ],
    [
        $tiuId, 1,
        "KERTAS : BUKU = BATA : ...",
        "Semen", "Pasir", "Tembok", "Atap", "Pondasi",
        "C", 0, 0, 0, 0, 0,
        "Lembaran-lembaran kertas disusun membentuk buku. Kumpulan bata disusun membentuk tembok."
    ],
    [
        $tiuId, 1,
        "KERING : AIR = GELAP : ...",
        "Malam", "Lampu", "Cahaya", "Matahari", "Hitam",
        "C", 0, 0, 0, 0, 0,
        "Kondisi kering karena tidak adanya air. Kondisi gelap karena tidak adanya cahaya."
    ],
    [
        $tiuId, 1,
        "HUJAN : PAYUNG = KOROSI : ...",
        "Air", "Cat pelindung", "Karat", "Logam", "Udara",
        "B", 0, 0, 0, 0, 0,
        "Untuk mencegah dampak hujan digunakan payung. Untuk mencegah dampak korosi digunakan cat pelindung."
    ],

    // 6-10: Silogisme & Logika
    [
        $tiuId, 1,
        "Semua pegawai negeri sipil wajib mengikuti pelatihan integritas anti-korupsi.\nSebagian peserta rapat di aula kantor adalah pegawai negeri sipil.\nKesimpulan yang sah adalah...",
        "Semua peserta rapat di aula kantor wajib mengikuti pelatihan integritas anti-korupsi.",
        "Sebagian peserta rapat di aula kantor wajib mengikuti pelatihan integritas anti-korupsi.",
        "Hanya peserta rapat di aula kantor yang wajib mengikuti pelatihan integritas.",
        "Sebagian pegawai negeri sipil tidak perlu mengikuti pelatihan integritas.",
        "Tidak ada peserta rapat yang mengikuti pelatihan integritas.",
        "B", 0, 0, 0, 0, 0,
        "Silogisme kategori Partikular + Universal: Jika semua A adalah B, dan sebagian C adalah A, maka kesimpulannya adalah sebagian C adalah B."
    ],
    [
        $tiuId, 1,
        "Jika hasil audit BPK menyatakan wajar tanpa pengecualian (WTP), maka akuntabilitas instansi dinilai baik.\nAkuntabilitas instansi Kementerian X dinilai TIDAK baik.\nKesimpulan yang benar adalah...",
        "Hasil audit BPK Kementerian X tidak menyatakan wajar tanpa pengecualian (WTP).",
        "Hasil audit BPK Kementerian X tetap WTP dengan catatan.",
        "Kementerian X menolak diaudit oleh BPK.",
        "Instansi lain mendapatkan predikat WTP.",
        "Tidak dapat ditarik kesimpulan dari pernyataan di atas.",
        "A", 0, 0, 0, 0, 0,
        "Menggunakan kaidah penarikan kesimpulan Modus Tollens: P -> Q, ~Q, maka kesimpulannya ~P (Hasil audit BPK tidak WTP)."
    ],
    [
        $tiuId, 1,
        "Semua peneliti terbiasa membaca jurnal ilmiah bereputasi.\nBeberapa konsultan kebijakan publik adalah peneliti.\nKesimpulan yang tepat adalah...",
        "Semua konsultan kebijakan publik terbiasa membaca jurnal ilmiah bereputasi.",
        "Beberapa konsultan kebijakan publik terbiasa membaca jurnal ilmiah bereputasi.",
        "Tidak ada konsultan kebijakan publik yang membaca jurnal ilmiah.",
        "Semua peneliti adalah konsultan kebijakan publik.",
        "Hanya peneliti yang membaca jurnal ilmiah bereputasi.",
        "B", 0, 0, 0, 0, 0,
        "Jika beberapa konsultan adalah peneliti, dan semua peneliti membaca jurnal, maka beberapa konsultan kebijakan publik terbiasa membaca jurnal ilmiah bereputasi."
    ],
    [
        $tiuId, 1,
        "Tidak ada atlet olimpiade yang merokok.\nSemua pesepeda profesional dalam tim garuda adalah atlet olimpiade.\nKesimpulan yang sah adalah...",
        "Sebagian pesepeda tim garuda merokok saat libur latihan.",
        "Tidak ada pesepeda profesional dalam tim garuda yang merokok.",
        "Semua atlet olimpiade adalah pesepeda tim garuda.",
        "Beberapa yang merokok adalah pesepeda tim garuda.",
        "Merokok diperbolehkan bagi atlet selain pesepeda tim garuda.",
        "B", 0, 0, 0, 0, 0,
        "Silogisme Universal Negatif: Jika tidak ada A yang B, dan semua C adalah A, maka dipastikan tidak ada C yang B."
    ],
    [
        $tiuId, 1,
        "Semua dosen diwajibkan menulis artikel pengabdian masyarakat.\nPak Rahman bukan dosen.\nKesimpulan yang tepat adalah...",
        "Pak Rahman tidak diwajibkan menulis artikel pengabdian masyarakat.",
        "Pak Rahman boleh menulis artikel pengabdian masyarakat.",
        "Tidak dapat disimpulkan apakah Pak Rahman diwajibkan atau tidak menulis artikel pengabdian masyarakat.",
        "Pak Rahman pasti menolak menulis artikel pengabdian masyarakat.",
        "Hanya dosen yang boleh mengabdi kepada masyarakat.",
        "C", 0, 0, 0, 0, 0,
        "Premis hanya menyatakan kewajiban bagi dosen. Bagi bukan dosen, premis tidak memberikan informasi (Kekeliruan menolak anteseden). Maka simpulan tidak dapat dipastikan."
    ],

    // 11-15: Penalaran Analitis
    [
        $tiuId, 1,
        "Lima orang peserta wawancara (Agus, Budi, Citra, Dodi, Eka) antre berurutan menghadap tim penilai:\n- Agus dipanggil sebelum Budi.\n- Citra dipanggil tepat setelah Dodi.\n- Eka dipanggil paling awal.\n- Budi dipanggil tepat sebelum Dodi.\nSiapakah yang dipanggil pada urutan keempat?",
        "Agus", "Budi", "Citra", "Dodi", "Eka",
        "D", 0, 0, 0, 0, 0,
        "Analisis urutan: Urutan 1 = Eka. Karena Agus sebelum Budi, Budi tepat sebelum Dodi, dan Citra tepat setelah Dodi -> Urutannya: 1. Eka, 2. Agus, 3. Budi, 4. Dodi, 5. Citra. Maka urutan keempat adalah Dodi."
    ],
    [
        $tiuId, 1,
        "Dalam suatu tes kompetensi:\n- Nilai Rudi lebih tinggi dari Sinta.\n- Nilai Tari lebih rendah dari Sinta, tetapi lebih tinggi dari Umar.\n- Nilai Vina lebih tinggi dari Rudi.\nUrutan peserta dari yang nilainya paling tinggi adalah...",
        "Vina, Rudi, Sinta, Tari, Umar",
        "Vina, Sinta, Rudi, Tari, Umar",
        "Rudi, Vina, Sinta, Tari, Umar",
        "Vina, Rudi, Tari, Sinta, Umar",
        "Umar, Tari, Sinta, Rudi, Vina",
        "A", 0, 0, 0, 0, 0,
        "Berdasarkan informasi: Vina > Rudi > Sinta > Tari > Umar. Urutan dari tertinggi: Vina, Rudi, Sinta, Tari, Umar."
    ],
    [
        $tiuId, 1,
        "Enam orang staf (P, Q, R, S, T, U) duduk melingkar dalam rapat koordinasi:\n- P duduk berhadapan langsung dengan S.\n- Q duduk di sebelah kanan P.\n- T duduk tepat di antara P dan R.\nSiapakah yang duduk berhadapan langsung dengan Q?",
        "R", "S", "T", "U", "P",
        "D", 0, 0, 0, 0, 0,
        "Dalam susunan melingkar 6 posisi: Jika P di jam 12, maka S di jam 6 (berhadapan). Q di sebelah kanan P (jam 2). T di antara P dan R (jam 10 untuk T, jam 8 untuk R). Sisa posisi jam 4 ditempati U. Posisi berhadapan dengan jam 2 (Q) adalah jam 8 atau 4, di mana posisi berhadapan adalah jam 4 (U)."
    ],
    [
        $tiuId, 1,
        "Suatu panitia pengadaan mensyaratkan:\n- Jika memilih merk A, maka tidak boleh memilih merk B.\n- Merk C harus dipilih jika merk D dipilih.\n- Panitia memutuskan memilih merk B dan merk D.\nMerk lain yang juga terpilih adalah...",
        "Merk A", "Merk C", "Merk A dan C", "Tidak ada", "Merk E",
        "B", 0, 0, 0, 0, 0,
        "Karena merk D dipilih, maka syarat mengharuskan merk C juga dipilih. Merk B dipilih sehingga merk A tidak boleh dipilih. Maka merk lain yang terpilih adalah merk C."
    ],
    [
        $tiuId, 1,
        "Jadwal shift kerja piket 4 petugas (W, X, Y, Z) dari Senin hingga Kamis:\n- W piket sebelum Y.\n- Z piket pada hari Kamis.\n- X piket tepat pada hari Selasa.\nPetugas yang piket pada hari Rabu adalah...",
        "W", "X", "Y", "Z", "Tidak dapat ditentukan",
        "C", 0, 0, 0, 0, 0,
        "Selasa = X, Kamis = Z. Tersisa Senin dan Rabu untuk W dan Y. Karena W sebelum Y, maka Senin = W dan Rabu = Y."
    ],

    // 16-20: Berhitung Cepat (Numerik)
    [
        $tiuId, 1,
        "Nilai dari (87^2 - 13^2) / (87 - 13) adalah...",
        "74", "87", "100", "113", "120",
        "C", 0, 0, 0, 0, 0,
        "Rumus aljabar selisih kuadrat: (a^2 - b^2) / (a - b) = (a + b). Maka: 87 + 13 = 100."
    ],
    [
        $tiuId, 1,
        "Hasil dari 15% dari 240 ditambah 25% dari 160 adalah...",
        "72", "76", "80", "84", "88",
        "B", 0, 0, 0, 0, 0,
        "15% x 240 = 36. 25% x 160 = 40. Total = 36 + 40 = 76."
    ],
    [
        $tiuId, 1,
        "Hasil perhitungan dari: (2 1/3 + 3 1/2) x 6/7 adalah...",
        "4", "5", "6", "7", "8",
        "B", 0, 0, 0, 0, 0,
        "2 1/3 + 3 1/2 = 7/3 + 7/2 = (14 + 21)/6 = 35/6. Maka: (35/6) x (6/7) = 35/7 = 5."
    ],
    [
        $tiuId, 1,
        "Jika x = 0,375 dan y = 3/8, maka hubungan x dan y yang benar adalah...",
        "x > y", "x < y", "x = y", "x = 2y", "Hubungan tidak dapat ditentukan",
        "C", 0, 0, 0, 0, 0,
        "Pecahan 3/8 jika didesimalkan: 3 / 8 = 0,375. Sehingga nilai x = y."
    ],
    [
        $tiuId, 1,
        "Nilai dari akar kuadrat: sqrt(0,0064) + cbrt(0,125) adalah...",
        "0,58", "0,56", "0,08", "0,85", "0,13",
        "A", 0, 0, 0, 0, 0,
        "sqrt(0,0064) = 0,08. cbrt(0,125) = 0,5. Jumlah = 0,08 + 0,5 = 0,58."
    ],

    // 21-25: Deret Angka & Huruf
    [
        $tiuId, 1,
        "2, 5, 10, 17, 26, 37, ...",
        "48", "49", "50", "51", "52",
        "C", 0, 0, 0, 0, 0,
        "Pola beda bertingkat ganjil: +3, +5, +7, +9, +11, maka berikutnya +13. 37 + 13 = 50. (Atau n^2 + 1: 1^2+1, 2^2+1, 3^2+1, ..., 7^2+1 = 50)."
    ],
    [
        $tiuId, 1,
        "4, 9, 8, 18, 12, 27, 16, ...",
        "30", "32", "34", "36", "38",
        "D", 0, 0, 0, 0, 0,
        "Deret lompat dua larik: Larik 1 (posisi ganjil): 4, 8, 12, 16 (+4). Larik 2 (posisi genap): 9, 18, 27, ... (+9). Maka angka selanjutnya adalah 27 + 9 = 36."
    ],
    [
        $tiuId, 1,
        "1, 1, 2, 3, 5, 8, 13, 21, ...",
        "29", "31", "34", "36", "40",
        "C", 0, 0, 0, 0, 0,
        "Deret Fibonacci (suku berikutnya adalah jumlah dua suku sebelumnya): 13 + 21 = 34."
    ],
    [
        $tiuId, 1,
        "A, C, F, J, O, ...",
        "S", "T", "U", "V", "W",
        "C", 0, 0, 0, 0, 0,
        "Konversi abjad ke angka: A(1) +2 -> C(3) +3 -> F(6) +4 -> J(10) +5 -> O(15) +6 -> 21 = U."
    ],
    [
        $tiuId, 1,
        "3, 6, 12, 24, 48, ...",
        "60", "72", "84", "96", "108",
        "D", 0, 0, 0, 0, 0,
        "Deret geometri dengan rasio perkalian 2: 48 x 2 = 96."
    ],

    // 26-30: Soal Cerita Aritmetika Sosial & Kecepatan
    [
        $tiuId, 1,
        "Sebuah barang dibeli dengan harga Rp 200.000. Jika pedagang ingin memperoleh keuntungan sebesar 25%, berapakah harga jual barang tersebut?",
        "Rp 225.000", "Rp 240.000", "Rp 250.000", "Rp 260.000", "Rp 275.000",
        "C", 0, 0, 0, 0, 0,
        "Untung = 25% x Rp 200.000 = Rp 50.000. Harga jual = Rp 200.000 + Rp 50.000 = Rp 250.000."
    ],
    [
        $tiuId, 1,
        "Sebuah toko pakaian memberikan diskon ganda bertingkat: 20% + 10% untuk sehelai kemeja berharga Rp 100.000. Berapakah harga akhir yang harus dibayar pembeli?",
        "Rp 70.000", "Rp 72.000", "Rp 74.000", "Rp 76.000", "Rp 80.000",
        "B", 0, 0, 0, 0, 0,
        "Diskon 1: 100.000 - (20% x 100.000) = 80.000. Diskon 2: 80.000 - (10% x 80.000) = 80.000 - 8.000 = Rp 72.000."
    ],
    [
        $tiuId, 1,
        "Mobil A berangkat dari Kota P menuju Kota Q dengan kecepatan rata-rata 60 km/jam. Pada saat yang sama, Mobil B berangkat dari Kota Q menuju Kota P dengan kecepatan 40 km/jam. Jika jarak Kota P dan Kota Q adalah 200 km, setelah berapa jam kedua mobil akan berpapasan?",
        "1,5 jam", "2 jam", "2,5 jam", "3 jam", "3,5 jam",
        "B", 0, 0, 0, 0, 0,
        "Waktu berpapasan = Jarak / (V1 + V2) = 200 / (60 + 40) = 200 / 100 = 2 jam."
    ],
    [
        $tiuId, 1,
        "Sebuah proyek gedung direncanakan selesai dalam waktu 30 hari oleh 15 orang pekerja. Setelah bekerja selama 10 hari, pekerjaan terhenti selama 5 hari karena cuaca buruk. Berapa jumlah pekerja tambahan yang dibutuhkan agar proyek selesai tepat waktu?",
        "3 orang", "5 orang", "7 orang", "10 orang", "15 orang",
        "B", 0, 0, 0, 0, 0,
        "Sisa waktu semula = 20 hari untuk 15 pekerja -> Beban sisa = 20 x 15 = 300 orang-hari. Sisa waktu efektif setelah libur = 20 - 5 = 15 hari. Pekerja yang dibutuhkan = 300 / 15 = 20 orang. Tambahan pekerja = 20 - 15 = 5 orang."
    ],
    [
        $tiuId, 1,
        "Tiga tahun lalu, umur Ayah adalah 4 kali umur Budi. Jika saat ini jumlah umur Ayah dan Budi adalah 56 tahun, berapakah umur Budi saat ini?",
        "11 tahun", "12 tahun", "13 tahun", "14 tahun", "15 tahun",
        "C", 0, 0, 0, 0, 0,
        "Misal umur saat ini Ayah = A, Budi = B. A + B = 56 -> A = 56 - B. Tiga tahun lalu: A - 3 = 4(B - 3) -> 56 - B - 3 = 4B - 12 -> 53 - B = 4B - 12 -> 65 = 5B -> B = 13 tahun."
    ],

    // 31-35: Perbandingan Kuantitatif & Logika Gambar
    [
        $tiuId, 1,
        "Jika x = 2p + 5 dan y = 2q + 5 dengan kondisi p > q, maka pernyataan yang benar adalah...",
        "x < y", "x > y", "x = y", "x + y = 10", "Hubungan tidak dapat ditentukan",
        "B", 0, 0, 0, 0, 0,
        "Karena p > q, maka 2p > 2q. Menambahkan kedua ruas dengan 5 menghasilkan 2p + 5 > 2q + 5, sehingga x > y."
    ],
    [
        $tiuId, 1,
        "Sebuah bak air diisi oleh kran A dapat penuh dalam 20 menit, dan oleh kran B dapat penuh dalam 30 menit. Jika kedua kran dibuka bersamaan, berapa menit waktu yang dibutuhkan hingga bak penuh?",
        "10 menit", "12 menit", "15 menit", "18 menit", "25 menit",
        "B", 0, 0, 0, 0, 0,
        "1/T = 1/20 + 1/30 = (3 + 2)/60 = 5/60 = 1/12. Maka T = 12 menit."
    ],
    [
        $tiuId, 1,
        "Jika x adalah luas bujur sangkar dengan sisi 6 cm dan y adalah luas persegi panjang dengan panjang 9 cm dan lebar 4 cm, maka...",
        "x > y", "x < y", "x = y", "2x = y", "Hubungan tidak dapat ditentukan",
        "C", 0, 0, 0, 0, 0,
        "Luas bujur sangkar x = 6 x 6 = 36 cm^2. Luas persegi panjang y = 9 x 4 = 36 cm^2. Maka x = y."
    ],
    [
        $tiuId, 1,
        "Rata-rata nilai ujian matematika 39 siswa adalah 70. Jika nilai Ani digabungkan, maka rata-rata nilai kelas menjadi 70,5. Berapakah nilai ujian Ani?",
        "85", "88", "90", "92", "95",
        "C", 0, 0, 0, 0, 0,
        "Total nilai 39 siswa = 39 x 70 = 2730. Total nilai 40 siswa = 40 x 70,5 = 2820. Nilai Ani = 2820 - 2730 = 90."
    ],
    [
        $tiuId, 1,
        "Sebuah mobil menempuh jarak 120 km dengan 8 liter bensin. Berapa liter bensin yang dibutuhkan mobil tersebut untuk menempuh jarak 300 km dengan kondisi yang sama?",
        "15 liter", "18 liter", "20 liter", "22 liter", "25 liter",
        "C", 0, 0, 0, 0, 0,
        "Perbandingan senilai: Konsumsi per liter = 120 / 8 = 15 km/liter. Bensin untuk 300 km = 300 / 15 = 20 liter."
    ]
];

// =========================================================================
// 3. BANK SOAL TKP (TES KARAKTERISTIK PRIBADI) - 45 SOAL PAKET AKBAR
// Seluruh opsi A, B, C, D, E memiliki bobot penilaian 1, 2, 3, 4, 5!
// =========================================================================

$tkpQuestions = [
    [
        $tkpId, 1,
        "Saat jam pelayanan hampir selesai dan antrean loket masih cukup panjang, sistem aplikasi pusat tiba-tiba mengalami gangguan koneksi (offline). Banyak warga yang mulai resah dan mengeluh. Sikap Anda sebagai petugas loket adalah...",
        "Menutup loket dan meminta warga datang kembali besok saat sistem sudah diperbaiki oleh pusat.",
        "Menjelaskan situasi gangguan secara tenang dan ramah, lalu mencatat berkas warga secara manual sementara waktu agar mereka tidak menunggu sia-sia.",
        "Mengabaikan keluhan warga dan fokus menghubungi teknisi IT tanpa memberikan penjelasan kepada antrean.",
        "Menyalahkan pihak pusat di depan warga agar warga memahami bahwa ini bukan kesalahan loket.",
        "Meminta bantuan petugas keamanan untuk menenangkan warga dan meminta warga menunggu tanpa kepastian.",
        null, 1, 5, 3, 2, 4,
        "Aspek Pelayanan Publik & Manajemen Krisis: Memberikan penjelasan transparan dan inisiatif solusi cadangan (manual) mencerminkan orientasi pelayanan optimal kepada warga (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Seorang warga lansia datang ke kantor Anda dan tampak kebingungan dalam mengisi formulir digital melalui layar sentuh mandiri yang baru diterapkan. Tindakan Anda adalah...",
        "Membiarkannya mencoba sendiri agar terbiasa dengan kemajuan teknologi modern.",
        "Menyuruhnya meminta bantuan keluarganya di rumah dan kembali lagi nanti.",
        "Mendekatinya dengan sopan, menyapa dengan ramah, dan memandu pengisian formulir langkah demi langkah hingga tuntas.",
        "Mengisi formulir tersebut dengan cepat tanpa menjelaskan agar antrean cepat selesai.",
        "Memanggil petugas satpam untuk mendampingi warga lansia tersebut.",
        null, 1, 2, 5, 4, 3,
        "Aspek Pelayanan Publik & Empati: Ramah dan proaktif membantu kelompok rentan/lansia memahami sistem baru secara santun (Poin 5 untuk Opsi C)."
    ],
    [
        $tkpId, 1,
        "Atasan Anda menugaskan Anda memimpin tim kerja lintas divisi yang anggotanya memiliki latar belakang suku, agama, dan usia yang sangat beragam. Langkah awal yang Anda lakukan adalah...",
        "Membuat aturan ketat tanpa meminta masukan dari anggota tim agar wibawa pemimpin terjaga.",
        "Mengadakan pertemuan santai untuk saling mengenal, menyelaraskan tujuan bersama, dan memberi ruang bagi setiap anggota untuk menyampaikan gagasan.",
        "Menyerahkan pembagian tugas kepada anggota yang paling senior.",
        "Memilih anggota dari suku yang sama sebagai wakil agar komunikasi berjalan lebih nyaman.",
        "Fokus hanya pada target kerja tanpa memedulikan dinamika hubungan antarpribadi dalam tim.",
        null, 3, 5, 3, 1, 2,
        "Aspek Jejaring Kerja & Sosial Budaya: Membangun sinergi inklusif dan menghargai keragaman sebagai kekuatan kolaborasi tim (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Instansi Anda meluncurkan program digitalisasi arsip berbasis cloud storage. Sebagian rekan kerja senior merasa enggan belajar dan meminta Anda tetap mengizinkan penggunaan berkas fisik. Sikap Anda adalah...",
        "Mendukung usulan rekan senior agar suasana kantor tetap kondusif tanpa konflik.",
        "Melaporkan rekan-rekan senior tersebut kepada pimpinan agar mereka ditegur keras.",
        "Mendampingi rekan senior dengan sabar, menunjukkan kemudahan sistem baru, dan membantu mereka sampai terbiasa menggunakan aplikasi cloud.",
        "Membiarkan mereka dan mengerjakan seluruh tugas arsip sendirian.",
        "Mengabaikan mereka dan fokus hanya pada berkas milik Anda sendiri.",
        null, 2, 2, 5, 3, 1,
        "Aspek TIK & Kolaborasi: Mengedukasi rekan kerja dengan empati dan mendorong adaptasi digital bersama (Poin 5 untuk Opsi C)."
    ],
    [
        $tkpId, 1,
        "Anda mengetahui bahwa seorang rekan kerja di kantor sering menyebarkan konten di media sosial pribadinya yang berisi ujaran kebencian berbau SARA dan mengajak untuk tidak mengakui konstitusi negara. Sikap Anda adalah...",
        "Menyukai dan membagikan unggahan tersebut karena itu hak kebebasan berpendapat.",
        "Mendiamkan saja karena urusan media sosial adalah privasi masing-masing individu.",
        "Mengingatkan rekan tersebut secara pribadi mengenai kode etik netralitas ASN dan kesetiaan pada Pancasila, serta melaporkan ke pembina kepegawaian bila terus berlanjut.",
        "Langsung memusuhi dan memprovokasi rekan lain untuk mengucilkannya.",
        "Menyindirnya di grup obrolan kantor tanpa bukti yang jelas.",
        null, 1, 2, 5, 2, 3,
        "Aspek Anti-Radikalisme & Integritas: ASN wajib menjaga integritas ideologi kebangsaan dan melakukan pencegahan dini terhadap radikalisme (Poin 5 untuk Opsi C)."
    ],
    [
        $tkpId, 1,
        "Di tengah beban pekerjaan kantor yang sedang menumpuk menjelang audit tahunan, seorang teman akrab Anda meminta bantuan untuk menyelesaikan tugas pribadinya. Tindakan Anda adalah...",
        "Meninggalkan tugas kantor dan membantunya karena menjaga persahabatan adalah hal utama.",
        "Menolak dengan ketus karena Anda sedang sangat sibuk.",
        "Menjelaskan dengan ramah bahwa Anda sedang fokus menyelesaikan prioritas tugas kantor, dan bersedia membantunya saat jam istirahat atau setelah jam kerja selesai.",
        "Menerima tugas teman tersebut lalu menyuruh rekan magang yang mengerjakannya.",
        "Mengabaikan pesannya dan pura-pura tidak mendengar permintaannya.",
        null, 2, 1, 5, 2, 2,
        "Aspek Profesionalisme: Mengutamakan tanggung jawab kedinasan dengan tetap menjaga komunikasi personal secara santun (Poin 5 untuk Opsi C)."
    ],
    [
        $tkpId, 1,
        "Anda ditempatkan di unit kerja yang berjarak cukup jauh dari keluarga dan berada di daerah dengan adat istiadat yang berbeda dari daerah asal Anda. Respon Anda adalah...",
        "Mengeluh setiap hari di media sosial dan mengajukan mutasi secepatnya.",
        "Membuka diri, mempelajari kearifan lokal setempat, menghormati adat istiadat, dan membangun hubungan baik dengan masyarakat setempat.",
        "Hanya bergaul dengan sesama pegawai pendatang dan menghindari interaksi dengan warga lokal.",
        "Menganggap adat istiadat setempat kuno dan berusaha mengubahnya dengan budaya modern.",
        "Menjalankan tugas seadanya dan hanya menghabiskan waktu di dalam kamar dinas.",
        null, 1, 5, 2, 1, 2,
        "Aspek Sosial Budaya: Kemampuan adaptasi kultural, menghargai keberagaman, dan integrasi sosial (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Ketika Anda sedang melayani masyarakat di loket, tiba-tiba seorang pengunjung menyelipkan amplop berisi uang ke dalam map berkasnya dengan harapan permohonannya diprioritaskan lebih cepat. Respon Anda adalah...",
        "Menerima amplop tersebut secara diam-diam karena pelayanan tetap berjalan seperti biasa.",
        "Mengembalikan amplop tersebut dengan sopan namun tegas, menjelaskan bahwa seluruh pelayanan bebas biaya dan diproses sesuai urutan antrean tanpa diskriminasi.",
        "Mengambil uangnya tetapi memperlambat proses berkasnya sebagai hukuman.",
        "Berteriak keras di depan umum agar warga tersebut merasa malu.",
        "Menyimpan amplop tersebut di laci kantor dan tidak menyentuhnya.",
        null, 1, 5, 2, 2, 2,
        "Aspek Integritas & Anti-Gratifikasi: Menolak suap secara tegas dan menjunjung tinggi transparansi birokrasi bersih (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Organisasi Anda sedang menghadapi masalah efisiensi kerja. Pimpinan meminta ide inovatif untuk memangkas birokrasi berbelit. Kontribusi Anda adalah...",
        "Menunggu ide dari pegawai senior yang lebih berpengalaman.",
        "Mengusulkan otomatisasi alur verifikasi berkas berbasis formulir digital mandiri dan penyederhanaan meja paraf dokumen.",
        "Mengusulkan penambahan jumlah staf agar pekerjaan dapat dibagi rata.",
        "Mengabaikan permintaan pimpinan karena hal tersebut bukan tugas pokok Anda.",
        "Mengeluhkan sistem yang ada tanpa memberikan usulan jalan keluar konkrit.",
        null, 2, 5, 3, 1, 1,
        "Aspek Kreativitas & Inovasi: Berinisiatif memberikan solusi konkret berbasis digitalisasi untuk efisiensi birokrasi (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Saat melakukan presentasi hasil kerja tim di depan pimpinan, seorang rekan Anda melakukan kesalahan penyampaian data yang membuat pimpinan meragukan hasil tim. Sikap Anda adalah...",
        "Langsung memotong pembicaraan dan menyalahkan rekan tersebut di depan pimpinan.",
        "Mendiamkan kesalahan tersebut agar rekan Anda menanggung risikonya sendiri.",
        "Menunggu rekan tersebut selesai berbicara, lalu dengan santun mengklarifikasi dan melengkapi data yang benar atas nama tim tanpa menjatuhkannya.",
        "Menertawakan kesalahannya bersama anggota tim lain.",
        "Mundur dari presentasi dan membiarkan rapat berjalan tanpa kesimpulan.",
        null, 2, 2, 5, 1, 1,
        "Aspek Kerjasama Tim & Komunikasi Profesional: Menjaga kehormatan tim dan mengklarifikasi data secara elegan (Poin 5 untuk Opsi C)."
    ],
    // Butir 11-20
    [
        $tkpId, 1,
        "Anda diminta menjadi notulis dalam rapat penting yang membahas kebijakan strategis instansi. Namun, saat rapat berlangsung, materi bahasan sangat cepat dan kompleks. Sikap Anda adalah...",
        "Mencatat hal-hal yang Anda mengerti saja dan mengabaikan bagian sulit.",
        "Merekam jalannya rapat atas izin pimpinan, mencatat poin-poin krusial, dan memverifikasi kembali hasil notula kepada narasumber sebelum diserahkan.",
        "Berhenti mencatat dan meminta rekan lain menggantikan tugas Anda di tengah rapat.",
        "Menyela pembicaraan setiap dua menit untuk meminta pembicara mengulang kata-katanya.",
        "Membuat kesimpulan sendiri berdasarkan perkiraan Anda tanpa konfirmasi.",
        null, 2, 5, 1, 2, 2,
        "Aspek Profesionalisme & Akurasi: Pemanfaatan alat bantu rekaman dan verifikasi teliti mencerminkan etos kerja unggul (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Seorang pemohon izin mengamuk dan membanting meja di ruang pelayanan karena merasa persyaratan yang diminta terlalu banyak. Sikap Anda menghadapinya adalah...",
        "Ikut membentaknya agar pemohon tersebut takut dan tunduk.",
        "Memanggil aparat kepolisian untuk segera memenjarakannya.",
        "Tetap tenang, tidak terpancing emosi, mempersilakannya duduk, mendengarkan keluhannya dengan seksama, lalu memberikan penjelasan aturan dengan bahasa yang mudah dipahami.",
        "Meninggalkan meja pelayanan dan membiarkannya meluapkan emosi sendirian.",
        "Membuat video kejadian tersebut dan menyebarkannya ke media sosial pribadi.",
        null, 1, 2, 5, 2, 1,
        "Aspek Pengendalian Diri & Pelayanan Publik: Tetap berkepala dingin dan meredakan situasi tegang dengan komunikasi empatik (Poin 5 untuk Opsi C)."
    ],
    [
        $tkpId, 1,
        "Anda mendapat kesempatan beasiswa pendidikan lanjutan dari instansi ke luar negeri selama 2 tahun. Namun, beberapa rekan kerja merasa iri dan menyebarkan desas-desus bahwa Anda lolos karena jalur koneksi. Respon Anda adalah...",
        "Membalas dengan melabrak rekan-rekan tersebut di ruang kerja umum.",
        "Membatalkan beasiswa tersebut demi menjaga keharmonisan pertemanan di kantor.",
        "Fokus mempersiapkan studi sebaik mungkin, membuktikan kompetensi dengan prestasi nyata, dan tetap bersikap ramah serta profesional kepada seluruh rekan kerja.",
        "Menyebarkan aib rekan-rekan tersebut di media sosial.",
        "Menyombongkan diri kepada rekan yang iri bahwa Anda memang lebih hebat.",
        null, 2, 1, 5, 1, 1,
        "Aspek Kematangan Emosi & Pengembangan Diri: Tetap berfokus pada prestasi kerja tanpa terdistraksi konflik destruktif (Poin 5 untuk Opsi C)."
    ],
    [
        $tkpId, 1,
        "Sebagai seorang staf TI, Anda mendapati bahwa salah satu komputer pegawai terinfeksi malware dan berpotensi mencuri data rahasia instansi. Langkah prioritas Anda adalah...",
        "Membiarkannya hingga pegawai tersebut mengajukan komplain resmi.",
        "Segera memutuskan koneksi jaringan komputer tersebut dari server, melakukan pembersihan sistem, dan memperbarui patch keamanan seluruh perangkat kantor.",
        "Memarahi pegawai yang komputernya terkena virus di depan semua orang.",
        "Menghapus seluruh file pegawai tersebut tanpa membuat cadangan data.",
        "Mencari tahu siapa yang pertama kali menyebarkan virus tersebut untuk dipermalukan.",
        null, 1, 5, 2, 2, 1,
        "Aspek TIK & Mitigasi Risiko: Tindakan cepat mengisolasi ancaman siber dan mengamankan integritas data negara (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Instansi Anda mengadakan kegiatan gotong royong membersihkan lingkungan kantor pada hari Sabtu pagi di luar jam kerja resmi. Anda telah memiliki rencana santai di rumah. Tindakan Anda adalah...",
        "Mengabaikan kegiatan tersebut karena hari Sabtu adalah hari libur hak pegawai.",
        "Hadir sebentar untuk presensi kehadiran lalu segera pulang diam-diam.",
        "Menyesuaikan jadwal pribadi dan hadir berpartisipasi aktif dalam kegiatan gotong royong bersama seluruh jajaran pegawai untuk mempererat kekeluargaan.",
        "Membayar orang lain untuk menggantikan kehadiran Anda.",
        "Membuat surat keterangan sakit palsu agar terbebas dari sanksi sosial.",
        null, 2, 2, 5, 1, 1,
        "Aspek Komitmen Organisasi & Harmonisasi: Menjunjung tinggi kebersamaan dan rasa memiliki terhadap lingkungan instansi (Poin 5 untuk Opsi C)."
    ],
    [
        $tkpId, 1,
        "Dalam rapat penentuan program kerja, atasan memilih gagasan rekan kerja Anda yang menurut Anda memiliki beberapa kelemahan, sementara usulan Anda tidak dipilih. Sikap Anda adalah...",
        "Mogok kerja dan tidak mau terlibat dalam pelaksanaan program tersebut.",
        "Menerima keputusan rapat dengan lapang dada, mendukung program terpilih dengan sepenuh hati, dan memberikan masukan konstruktif untuk menutup celah kelemahan program.",
        "Menceritakan kelemahan program tersebut kepada unit kerja lain agar program tersebut gagal.",
        "Menyindir atasan dan rekan kerja Anda di belakang mereka.",
        "Menjalankan tugas setengah hati agar atasan menyadari kesalahannya.",
        null, 1, 5, 1, 1, 2,
        "Aspek Kerjasama & Kedewasaan Berdemokrasi: Menghargai keputusan mufakat dan loyal mendukung kesuksesan bersama (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Seorang warga mengeluhkan pelayanan di kantor Anda melalui postingan viral di media sosial. Atasan menugaskan Anda untuk merespon aduan tersebut. Sikap Anda adalah...",
        "Membuat klarifikasi defensif dan mengancam pembuat konten dengan UU ITE.",
        "Menyampaikan permohonan maaf atas ketidaknyamanan yang dialami, mengonfirmasi detail permasalahan secara santun, dan menyampaikan langkah perbaikan yang langsung dilakukan instansi.",
        "Menghapus kolom komentar akun instansi agar isu tidak semakin meluas.",
        "Menyerang balik pengunggah dengan mencari-cari kesalahan pribadinya.",
        "Mendiamkan unggahan tersebut sampai publik lupa dengan sendirinya.",
        null, 2, 5, 2, 1, 1,
        "Aspek Keterbukaan Publik & Citra Institusi: Respon cepat, empati, dan berorientasi penyelesaian masalah publik secara terbuka (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Anda ditugaskan melakukan perjalanan dinas dengan biaya transportasi dan akomodasi yang telah ditentukan sistem pagu. Dalam praktiknya, Anda berhasil menghemat biaya tiket dan hotel. Tindakan Anda terhadap kelebihan anggaran adalah...",
        "Mengantongi sisa uang perjalanan dinas sebagai keuntungan pribadi.",
        "Membuat kwitansi fiktif dengan nilai setinggi pagu maksimal.",
        "Mengembalikan sisa dana riil yang tidak terpakai ke kas negara sesuai bukti pengeluaran yang sah dan akuntabel.",
        "Menggunakan sisa uang untuk mentraktir makan malam rekan kantor lain.",
        "Membagikan sisa uang kepada sopir dan petugas hotel.",
        null, 1, 1, 5, 2, 2,
        "Aspek Akuntabilitas & Integritas Keuangan: Mengembalikan kelebihan anggaran negara adalah kewajiban integritas ASN (Poin 5 untuk Opsi C)."
    ],
    [
        $tkpId, 1,
        "Saat sedang menyelesaikan laporan penting yang harus dikumpulkan sore ini, laptop kantor Anda tiba-tiba mati total dan tidak bisa dinyalakan. Tindakan cepat Anda adalah...",
        "Panik dan menangis di meja kerja.",
        "Melaporkan ke atasan bahwa laporan tidak bisa selesai hari ini karena faktor tak terduga.",
        "Segera berkoordinasi dengan tim IT untuk pemeriksaan, meminjam komputer cadangan atau menggunakan laptop pribadi, lalu mengakses data cadangan di cloud untuk merampungkan laporan.",
        "Menyalahkan bagian pengadaan barang kantor yang memberikan laptop usang.",
        "Pulang ke rumah lebih awal karena tidak bisa bekerja tanpa laptop.",
        null, 1, 2, 5, 1, 1,
        "Aspek Pemecahan Masalah & Tanggung Jawab: Proaktif mencari alternatif sarana kerja demi menjaga tenggat waktu (Poin 5 untuk Opsi C)."
    ],
    [
        $tkpId, 1,
        "Anda mendengar seorang rekan sekantor melontarkan lelucon yang merendahkan suku dan kebiasaan rekan kerja lain yang berasal dari wilayah minoritas. Sikap Anda adalah...",
        "Ikut tertawa bersama rekan-rekan lain agar dianggap akrab.",
        "Menegur rekan tersebut secara bijak bahwa lelucon bernuansa diskriminasi suku tidak pantas di lingkungan kerja berakhlak, serta merangkul rekan yang menjadi sasaran lelucon.",
        "Merekam pembicaraan tersebut dan melaporkannya ke akun gosip viral.",
        "Mendiamkan saja karena itu hanya lelucon santai pengusir penat.",
        "Menjauhi kedua belah pihak agar tidak terlibat masalah.",
        null, 1, 5, 2, 2, 2,
        "Aspek Keberagaman & Harmoni Sosial: Menolak diskriminasi SARA dan merawat lingkungan kerja yang inklusif serta saling menghargai (Poin 5 untuk Opsi B)."
    ],
    // Butir 21-30
    [
        $tkpId, 1,
        "Anda ditunjuk menjadi koordinator pelayanan publik terpadu satu pintu. Banyak masyarakat mengeluhkan waktu tunggu yang tidak pasti. Inovasi yang Anda terapkan adalah...",
        "Meminta masyarakat untuk memaklumi karena petugas juga manusia yang bisa lelah.",
        "Mengembangkan papan informasi antrean digital secara real-time dan notifikasi WhatsApp otomatis saat berkas selesai diproses.",
        "Mengurangi jam pelayanan agar petugas lebih fokus bekerja.",
        "Memasang spanduk imbauan agar masyarakat tidak banyak bertanya.",
        "Menghapus nomor antrean dan melayani siapa yang datang duluan secara fisik.",
        null, 2, 5, 2, 1, 1,
        "Aspek Pelayanan Prima & Pemanfaatan Teknologi: Membangun sistem kepastian informasi antrean yang transparan bagi publik (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Ketika instansi Anda mengalami perombakan struktur organisasi, posisi jabatan Anda digeser ke bidang tugas baru yang belum pernah Anda kuasai sebelumnya. Respon Anda adalah...",
        "Mengajukan protes keras kepada Badan Kepegawaian Daerah atas perombakan tersebut.",
        "Menerima posisi baru sebagai amanah, segera mempelajari regulasi dan proses bisnis bidang tersebut, serta belajar dari rekan-rekan kerja yang telah lama di bidang itu.",
        "Bekerja setengah hati dan berharap segera dimutasi kembali ke bidang asal.",
        "Meminta bawahan mengerjakan seluruh pekerjaan teknis tanpa mau tahu prosesnya.",
        "Menolak masuk kantor selama beberapa minggu sebagai bentuk penolakan.",
        null, 2, 5, 2, 2, 1,
        "Aspek Adaptabilitas & Kemauan Belajar: Kesiapan menerima tantangan baru dengan proaktif meningkatkan kompetensi (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Anda mengetahui bahwa terdapat kebocoran dokumen draf kebijakan rahasia instansi ke media sosial yang memicu polemik publik. Sikap Anda sebagai bagian dari instansi tersebut adalah...",
        "Ikut berkomentar di media sosial memberikan opini pribadi yang membenarkan kebocoran.",
        "Segera melapor ke pimpinan dan unit pengawas internal mengenai sumber informasi kebocoran serta menjaga kerahasiaan informasi kedinasan yang Anda ketahui.",
        "Mendiamkan saja karena bukan Anda yang membocorkan dokumen tersebut.",
        "Menjual dokumen pelengkap lainnya kepada pihak media untuk mencari keuntungan.",
        "Menyudutkan pimpinan di depan rekan kerja lainnya atas insiden kebocoran itu.",
        null, 2, 5, 3, 1, 1,
        "Aspek Loyalitas & Etika Kerahasiaan ASN: Menjaga rahasia jabatan dan mendukung pengusutan internal secara tertib (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Dalam sebuah kegiatan sosialisasi program bansos di pedesaan, seorang warga menuduh bahwa instansi Anda melakukan pemotongan bantuan dana secara sepihak. Respon Anda adalah...",
        "Memarahi warga tersebut dan mencoret namanya dari daftar penerima bantuan.",
        "Mengajaknya berbicara dengan tenang, memperlihatkan transparansi data penyaluran perbankan, dan menjelaskan alur resmi pencairan tanpa potongan sedikitpun.",
        "Menyerahkan penanganan kepada kepala desa dan langsung meninggalkan lokasi sosialisasi.",
        "Mengancam akan melaporkan warga tersebut atas pencemaran nama baik.",
        "Mengabaikan pertanyaannya dan melanjutkan sosialisasi seolah tidak terjadi apa-apa.",
        null, 1, 5, 2, 1, 2,
        "Aspek Akuntabilitas & Komunikasi Publik: Menyajikan data transparan untuk menjawab keraguan masyarakat secara persuasif (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Anda dihadapkan pada dua tugas penting dengan batas waktu pengumpulan yang sama pada pukul 16.00 WIB hari ini. Satu tugas dari atasan langsung, dan satu tugas dari kepala dinas. Langkah Anda adalah...",
        "Mengerjakan tugas kepala dinas saja dan mengabaikan tugas atasan langsung.",
        "Menganalisis urgensi dan estimasi waktu kedua tugas, mengomunikasikan situasi secara jujur kepada kedua pihak, serta mengatur skala prioritas pembagian waktu kerja.",
        "Pasrah dan tidak mengerjakan keduanya karena merasa terbebani.",
        "Menyerahkan kedua tugas tersebut kepada pegawai magang.",
        "Mengerjakan separuh-separuh secara asal-asalan agar keduanya selesai.",
        null, 2, 5, 1, 2, 2,
        "Aspek Manajemen Prioritas & Komunikasi Atasan: Mengelola beban kerja dengan komunikasi transparan dan pembagian waktu terukur (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Seorang rekan kerja satu ruangan Anda sering mengajak mengobrol hal-hal pribadi saat jam pelayanan sedang padat sehingga mengganggu konsentrasi kerja Anda. Sikap Anda adalah...",
        "Melayaninya mengobrol sepanjang hari agar tidak merusak hubungan pertemanan.",
        "Menegurnya dengan nada membentak di depan warga yang sedang dilayani.",
        "Mengatakan dengan sopan bahwa Anda harus fokus melayani warga terlebih dahulu, dan mengajaknya melanjutkan obrolan saat jam istirahat siang nanti.",
        "Melaporkan kelakuannya ke Badan Kepegawaian secara diam-diam tanpa menegurnya terlebih dahulu.",
        "Mengabaikannya secara dingin tanpa berkata sepatah katapun.",
        null, 2, 1, 5, 2, 2,
        "Aspek Batas Profesional & Ketegasan Santun: Menjaga komitmen tugas dinas dengan tetap bersikap hormat kepada rekan (Poin 5 untuk Opsi C)."
    ],
    [
        $tkpId, 1,
        "Anda terpilih mengikuti uji kompetensi jabatan fungsional. Beberapa hari sebelum ujian, seorang oknum mengaku memiliki bocoran soal resmi dan menawarkannya dengan imbalan sejumlah uang. Sikap Anda adalah...",
        "Membeli bocoran soal tersebut demi kepastian lulus nilai tinggi.",
        "Menolak tawaran tersebut dengan tegas, tetap percaya diri belajar mandiri, dan melaporkan tindakan oknum tersebut kepada panitia pengawas seleksi.",
        "Meminta diskon harga kepada oknum tersebut.",
        "Membeli bersama rekan-rekan lain agar biaya lebih murah.",
        "Mendiamkannya saja tanpa melapor karena takut diancam.",
        null, 1, 5, 1, 1, 3,
        "Aspek Integritas Tinggi & Berani Menolak Kecurangan: ASN harus menolak tegas praktik curang dan aktif melaporkan pelanggaran (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Saat menyusun laporan pertanggungjawaban kegiatan, Anda menemukan adanya kelebihan pengadaan ATK yang tidak terpakai namun bernilai cukup besar. Sikap Anda adalah...",
        "Membawa pulang sisa ATK tersebut untuk keperluan keluarga di rumah.",
        "Menjualnya ke toko alat tulis di dekat kantor.",
        "Mendata sisa ATK secara transparan dalam berita acara inventaris kantor dan mengembalikannya ke bagian gudang perlengkapan dinas.",
        "Membiarkannya rusak di sudut ruangan.",
        "Membagikannya secara rahasia kepada teman-teman dekat saja.",
        null, 1, 1, 5, 2, 2,
        "Aspek Akuntabilitas Aset Milik Negara: Mencatat dan mengembalikan kelebihan barang dinas secara tertib administrasi (Poin 5 untuk Opsi C)."
    ],
    [
        $tkpId, 1,
        "Pemerintah menerapkan kebijakan efisiensi anggaran perjalanan dinas dan rapat di hotel berbintang. Sebagian pegawai mengeluhkan berkurangnya uang saku perjalanan. Sikap Anda adalah...",
        "Mendukung kebijakan tersebut sebagai langkah tepat optimalisasi anggaran negara, serta memaksimalkan rapat secara daring (virtual conference) yang efisien.",
        "Ikut mengeluh dan mengajak rekan lain membuat petisi penolakan.",
        "Mencari celah manipulasi rincian biaya perjalanan lain untuk menutupi kekurangan.",
        "Bekerja malas-malasan karena uang saku perjalanan dinas berkurang.",
        "Menolak setiap penugasan luar kota yang ditugaskan oleh pimpinan.",
        null, 5, 2, 1, 1, 1,
        "Aspek Kepatuhan Kebijakan & Adaptasi Efisiensi: Mendukung penghematan belanja negara melalui teknologi komunikasi (Poin 5 untuk Opsi A)."
    ],
    [
        $tkpId, 1,
        "Seorang pegawai baru yang belum memahami kultur kerja instansi melakukan kesalahan input data yang berdampak pada mundurnya jadwal pencairan insentif. Sikap Anda adalah...",
        "Memarahinya habis-habisan karena telah merugikan banyak orang.",
        "Membantunya memperbaiki kesalahan input data, menjelaskan prosedur yang benar, dan menyemangatinya agar lebih cermat ke depannya.",
        "Menyebarkan kesalahan pegawai baru tersebut ke unit kerja lain.",
        "Menuntut pimpinan agar pegawai baru tersebut segera dipecat.",
        "Mendiamkannya dan membiarkannya kebingungan membetulkan data sendirian.",
        null, 1, 5, 1, 1, 2,
        "Aspek Bimbingan Rekan Kerja & Solutif: Menumbuhkan budaya saling mendukung dan memperbaiki kesalahan secara konstruktif (Poin 5 untuk Opsi B)."
    ],
    // Butir 31-45
    [
        $tkpId, 1,
        "Anda sedang melayani antrean perpanjangan dokumen ketika tiba-tiba waktu istirahat salat Jumat telah tiba. Masih ada satu warga terakhir di depan loket yang dokumennya tinggal sedikit lagi selesai. Sikap Anda adalah...",
        "Langsung berdiri menutup loket tanpa peduli pada warga tersebut.",
        "Menyelesaikan dokumen warga terakhir tersebut dengan cepat dan tuntas terlebih dahulu, lalu segera bersiap menunaikan ibadah dengan tenang.",
        "Menyuruh warga tersebut menunggu di loket selama Anda pergi beribadah.",
        "Memarahi warga tersebut mengapa baru datang menjelang salat Jumat.",
        "Menyerahkan dokumen yang belum selesai tersebut kepada satpam.",
        null, 1, 5, 2, 1, 2,
        "Aspek Pelayanan Paripurna & Pengaturan Waktu: Tuntaskan sisa tugas yang tinggal sedikit agar warga tidak menunggu lama (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Dalam rangka menyukseskan reformasi birokrasi, instansi Anda menerapkan Indikator Kinerja Individu (IKI) berbasis aplikasi dengan target harian ketat. Respon Anda adalah...",
        "Membuat laporan kegiatan fiktif di aplikasi agar target harian selalu tercapai 100%.",
        "Menyusun rencana kerja harian secara disiplin, mencatat realisasi tugas dengan jujur, dan berupaya melampaui standar kinerja yang ditetapkan.",
        "Mengeluhkan sistem IKI kepada atasan karena menambah beban administrasi.",
        "Mengerjakan target minimal saja tanpa ada inisiatif penambahan mutu kerja.",
        "Meminta rekan lain mengisi laporan harian Anda.",
        null, 1, 5, 2, 3, 1,
        "Aspek Kinerja Unggul & Akuntabilitas: Disiplin mengejar target riil dan menjaga kejujuran pelaporan kinerja (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Instansi Anda mengadakan program pertukaran pegawai antarprovinsi untuk memperkuat integrasi nasional. Anda terpilih mewakili provinsi Anda ke provinsi lain selama 6 bulan. Sikap Anda adalah...",
        "Mencari alasan keluarga agar penugasan pertukaran dibatalkan.",
        "Menyambut kesempatan ini dengan antusias untuk memperluas jaringan profesional, belajar praktik baik di daerah lain, dan membagikan pengalaman positif.",
        "Menerima tugas dengan syarat diberikan fasilitas hotel mewah.",
        "Berangkat dengan rasa terpaksa dan mengurung diri di penginapan selama di sana.",
        "Mengajukan pengunduran diri dari status pegawai negeri.",
        null, 1, 5, 2, 2, 1,
        "Aspek Wawasan Kebangsaan & Mobilitas Kerja: Menjadikan pertukaran daerah sebagai wadah pengabdian dan pengayaan kompetensi (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Anda menemukan sebuah celah kerentanan keamanan pada situs web layanan masyarakat milik instansi yang memungkinkan kebocoran NIK warga. Tindakan Anda adalah...",
        "Menjual informasi celah tersebut kepada peretas luar.",
        "Segera mendokumentasikan celah tersebut dan melaporkannya kepada tim keamanan siber/IT instansi untuk penutupan lubang keamanan sesegera mungkin.",
        "Menceritakan celah tersebut di media sosial pribadi untuk membuktikan kehebatan Anda.",
        "Memanfaatkan celah tersebut untuk mengunduh data warga.",
        "Mendiamkannya karena menganggap itu bukan tanggung jawab bagian Anda.",
        null, 1, 5, 1, 1, 2,
        "Aspek Keamanan Data & Tanggung Jawab Digital: Menjaga kerahasiaan data publik dan segera berkoordinasi dengan tim teknis (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Masyarakat mengeluhkan rumitnya alur pengurusan bantuan UMKM yang mengharuskan datang ke tiga kantor dinas berbeda. Sikap Anda sebagai staf perencana adalah...",
        "Menyuruh masyarakat mengikuti aturan yang sudah baku sejak bertahun-tahun lalu.",
        "Mengusulkan integrasi loket layanan terpadu satu atap secara digital sehingga berkas cukup diunggah di satu pintu portal pelayanan.",
        "Menyalahkan instansi lain yang tidak mau mengalah dalam urusan kewenangan.",
        "Mengabaikan keluhan masyarakat karena UMKM bukan urusan utama Anda.",
        "Menyarankan masyarakat menggunakan jasa calo agar urusan lebih cepat.",
        null, 2, 5, 2, 1, 1,
        "Aspek Orientasi Kepuasan Publik: Terbuka pada debirokratisasi dan integrasi layanan publik terpadu (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Seorang rekan kerja Anda mengalami musibah bencana alam dan rumahnya mengalami kerusakan cukup parah. Sikap Anda bersama rekan kantor adalah...",
        "Mengucapkan duka cita di grup WhatsApp tanpa melakukan tindakan nyata apapun.",
        "Menginisiasi penggalangan dana sukarela dan bantuan logistik darurat di lingkungan kantor, serta mengatur jadwal kunjungan untuk memberikan dukungan moril.",
        "Menganggap musibah itu bukan urusan kantor.",
        "Menuntut rekan tersebut agar tetap hadir bekerja tepat waktu tanpa memedulikan kondisinya.",
        "Menjadikan musibah rekan tersebut sebagai bahan pembicaraan di waktu makan siang.",
        null, 2, 5, 1, 1, 1,
        "Aspek Kepedulian Sosial & Solidaritas Tim: Menggalang empati dan dukungan nyata bagi rekan yang tertimpa musibah (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Saat menghadiri seminar kedinasan di luar kota, Anda bertemu dengan pejabat dari instansi kementerian lain yang memiliki bidang fokus mirip dengan instansi Anda. Sikap Anda adalah...",
        "Menghindari kontak dan fokus mendengarkan seminar saja.",
        "Berkenalan secara santun, bertukar kontak profesional, dan berdiskusi mengenai peluang kolaborasi program kerja antar-instansi ke depan.",
        "Menyombongkan capaian instansi Anda sendiri di depan pejabat tersebut.",
        "Meminta bantuan pribadi yang tidak berkaitan dengan kedinasan.",
        "Segera pergi saat sesi istirahat jejaring dimulai.",
        null, 2, 5, 2, 1, 1,
        "Aspek Jejaring Kerja & Sinergi Antar-Lembaga: Memanfaatkan forum resmi untuk memperluas kolaborasi produktif (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Sebagai pimpinan rapat, terjadi perdebatan panas antara dua kubu anggota tim yang sama-sama bersikukuh dengan argumennya hingga suasana menjadi tidak kondusif. Sikap Anda adalah...",
        "Meninggalkan ruang rapat dan membiarkan mereka menyelesaikan perdebatan sendiri.",
        "Menenangkan kedua belah pihak secara berwibawa, menskors rapat sejenak untuk mendinginkan suasana, lalu membedah kelebihan dan kekurangan masing-masing opsi secara objektif berbasis data.",
        "Mendukung salah satu pihak secara subjektif tanpa mendengarkan pihak lain.",
        "Menghukum kedua belah pihak dengan pembatalan seluruh program kerja.",
        "Mengadu domba kedua kubu agar ada yang mengalah.",
        null, 1, 5, 2, 1, 1,
        "Aspek Kepemimpinan & Manajemen Konflik: Menjadi penengah yang netral, menenangkan emosi, dan mengarahkan pada solusi objektif (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Suatu hari Anda diminta mengerjakan tugas yang membutuhkan ketelitian tinggi dalam angka statistik, bidang yang biasanya menjadi kelemahan Anda. Sikap Anda adalah...",
        "Menolak tugas tersebut dan meminta tugas lain yang lebih mudah.",
        "Mempelajari panduan analisis data dengan sungguh-sungguh, berkonsultasi dengan rekan yang ahli dalam statistik, dan memeriksa ulang hasil pekerjaan berulang kali sebelum diserahkan.",
        "Mengerjakan dengan cepat tanpa memeriksa keakuratan data angka.",
        "Menyerahkan seluruh pengerjaan tugas kepada rekan kerja yang ahli statistik.",
        "Menunda pekerjaan sampai mendekati tenggat waktu akhir.",
        null, 1, 5, 2, 2, 1,
        "Aspek Tanggung Jawab & Peningkatan Diri: Gigih mengatasi kelemahan diri dengan belajar dan memeriksa akurasi secara berlapis (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Anda mengetahui ada indikasi praktik pungutan liar (pungli) kecil di lingkungan parkir kantor yang dilakukan oleh pihak tertentu. Tindakan Anda adalah...",
        "Mendiamkannya karena nilainya kecil dan tidak berkaitan langsung dengan tugas meja Anda.",
        "Mengumpulkan bukti faktual secara cermat dan melaporkannya kepada unit kepatuhan internal / Tim Saber Pungli instansi untuk ditindaklanjuti.",
        "Ikut meminta bagian dari pungutan parkir tersebut.",
        "Menyerang para pelaku secara fisik di area parkir.",
        "Menulis aduan anonim di media sosial tanpa melapor ke pihak internal terlebih dahulu.",
        null, 2, 5, 1, 1, 3,
        "Aspek Integritas & Keberanian Melaporkan Penyimpangan: Pengumpulan bukti sah dan pelaporan lewat saluran resmi (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Pimpinan instansi meminta seluruh pegawai membiasakan diri menggunakan transportasi umum ke kantor satu hari dalam seminggu untuk mengurangi polusi udara. Sikap Anda adalah...",
        "Tetap menggunakan kendaraan pribadi dan mencari alasan parkir di luar kantor.",
        "Mendukung dan mematuhi imbauan tersebut dengan antusias, mencari rute transportasi umum terbaik dari rumah, serta mengajak rekan kerja lain berpartisipasi.",
        "Mengkritik kebijakan pimpinan di depan umum karena merepotkan pegawai.",
        "Hanya naik transportasi umum jika pimpinan melakukan inspeksi langsung.",
        "Meminta uang pengganti transportasi khusus kepada bagian keuangan.",
        null, 2, 5, 2, 2, 1,
        "Aspek Kepatuhan & Kesadaran Lingkungan Sosial: Menjadi teladan kepatuhan terhadap kebijakan publik yang berwawasan lingkungan (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Instansi Anda menyelenggarakan program pelatihan bahasa asing dan analisis data secara gratis di luar jam kerja untuk meningkatkan daya saing pegawai. Respon Anda adalah...",
        "Menolak ikut karena waktu di luar jam kerja adalah hak bersantai.",
        "Mendaftarkan diri dan mengikuti pelatihan tersebut dengan konsisten demi memperluas wawasan dan meningkatkan mutu pelayanan kepada masyarakat.",
        "Mendaftar hanya untuk mendapatkan sertifikat tanda keikutsertaan tanpa hadir belajar.",
        "Menyuruh rekan lain yang ikut agar Anda bisa menyontek materi nantinya.",
        "Menganggap materi pelatihan tidak berguna bagi tugas birokrasi sehari-hari.",
        null, 2, 5, 2, 1, 1,
        "Aspek Pembelajar Berkelanjutan (Continuous Learning): Aktif mengasah keterampilan baru demi peningkatan mutu layanan birokrasi (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Di sebuah forum diskusi daring publik, instansi Anda dikritik oleh seorang tokoh masyarakat mengenai keterbukaan informasi publik. Sikap Anda sebagai pengelola kehumasan adalah...",
        "Memblokir akun tokoh masyarakat tersebut dari akun resmi instansi.",
        "Menyampaikan apresiasi atas masukan kritis tersebut, memberikan penjelasan mengenai kanal data terbuka resmi yang telah tersedia, dan membuka ruang audiensi klarifikasi.",
        "Membalas kritik dengan kata-kata kasar dan sarkasme.",
        "Membiarkan kritik tersebut tanpa tanggapan sedikitpun.",
        "Menyewa buzzer media sosial untuk membela instansi secara membabi buta.",
        null, 1, 5, 1, 2, 1,
        "Aspek Keterbukaan Informasi & Etika Humas Pemerintah: Menghadapi kritik konstruktif dengan elegan, berbasis fakta, dan edukatif (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Anda mendapati rekan kerja satu tim mengalami demotivasi dan penurunan performa yang drastis karena masalah pribadi di keluarganya. Tindakan Anda adalah...",
        "Mengadukan kelemahan kinerjanya kepada pimpinan agar rekan tersebut diberi sanksi.",
        "Mengajaknya berbincang hangat saat istirahat, mendengarkan keluh kesahnya dengan empati, memberikan dukungan moril, dan menawarkan bantuan pada tugas yang mendesak.",
        "Mengabaikannya dan membiarkan kinerjanya terus memburuk.",
        "Menyindirnya di depan tim agar dia segera sadar akan tugasnya.",
        "Menjauhinya agar Anda tidak tertular energi negatifnya.",
        null, 2, 5, 2, 1, 1,
        "Aspek Empati Sosial & Kohesi Tim: Memberikan dukungan manusiawi dan saling menguatkan dalam tim kerja (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 1,
        "Setelah melalui proses evaluasi seleksi ketat selama 1 tahun, Anda akhirnya dinyatakan lulus dan diangkat menjadi Pegawai Negeri Sipil resmi. Komitmen utama yang Anda tanamkan dalam hati adalah...",
        "Mencari jabatan strategis dan memanfaatkan status ASN untuk keuntungan materi keluarga.",
        "Bekerja profesional dengan integritas tinggi, melayani masyarakat dengan sepenuh hati, setia pada Pancasila dan NKRI, serta menjaga nama baik kehormatan korps ASN.",
        "Bekerja santai karena status ASN sudah terjamin hingga masa pensiun.",
        "Mengikuti arus birokrasi lama tanpa perlu berinovasi.",
        "Menjadikan status ASN sebagai sarana untuk pamer di lingkungan pertemanan.",
        null, 1, 5, 2, 2, 1,
        "Aspek Core Values ASN BerAKHLAK & Integritas Pengabdian Bangsa: Menjalankan amanah pengabdian sebagai pelayan masyarakat berintegritas tinggi (Poin 5 untuk Opsi B)."
    ]
];

// =========================================================================
// 4. BANK SOAL KHUSUS MINI TRYOUT (30 SOAL PAKET 2 - FREE TRIAL)
// 10 Butir TWK + 10 Butir TIU + 10 Butir TKP
// =========================================================================

$miniQuestions = [
    // 10 TWK MINI
    [
        $twkId, 2,
        "Lambang negara Republik Indonesia adalah Garuda Pancasila dengan semboyan Bhinneka Tunggal Ika yang diatur dalam UUD 1945 Pasal...",
        "Pasal 35", "Pasal 36", "Pasal 36A", "Pasal 36B", "Pasal 36C",
        "C", 0, 0, 0, 0, 0,
        "Pasal 36A UUD 1945 menyatakan: 'Lambang Negara ialah Garuda Pancasila dengan semboyan Bhinneka Tunggal Ika.'"
    ],
    [
        $twkId, 2,
        "Bendera Negara Indonesia adalah Sang Merah Putih sebagaimana diatur dalam UUD 1945 Pasal...",
        "Pasal 35", "Pasal 36", "Pasal 36A", "Pasal 36B", "Pasal 37",
        "A", 0, 0, 0, 0, 0,
        "Pasal 35 UUD 1945 berbunyi: 'Bendera Negara Indonesia ialah Sang Merah Putih.'"
    ],
    [
        $twkId, 2,
        "Bahasa Negara ialah Bahasa Indonesia sebagaimana ditegaskan dalam UUD 1945 Pasal...",
        "Pasal 34", "Pasal 35", "Pasal 36", "Pasal 36A", "Pasal 36B",
        "C", 0, 0, 0, 0, 0,
        "Pasal 36 UUD 1945 menegaskan: 'Bahasa Negara ialah Bahasa Indonesia.'"
    ],
    [
        $twkId, 2,
        "Lagu kebangsaan ialah Indonesia Raya yang diatur secara konstitusional dalam UUD 1945 Pasal...",
        "Pasal 36", "Pasal 36A", "Pasal 36B", "Pasal 36C", "Pasal 37",
        "C", 0, 0, 0, 0, 0,
        "Pasal 36B UUD 1945 menyatakan: 'Lagu Kebangsaan ialah Indonesia Raya.'"
    ],
    [
        $twkId, 2,
        "Badan Penyelidik Usaha-Usaha Persiapan Kemerdekaan Indonesia (BPUPKI) dibentuk pada tanggal 1 Maret 1945 dan dipimpin oleh ketua bernama...",
        "Ir. Soekarno", "Drs. Mohammad Hatta", "K.R.T. Radjiman Wedyodiningrat", "Mr. Soepomo", "Achmad Soebardjo",
        "C", 0, 0, 0, 0, 0,
        "BPUPKI diketuai oleh K.R.T. Radjiman Wedyodiningrat didampingi Raden Pandji Soeroso dan Ichibangase Yosio."
    ],
    [
        $twkId, 2,
        "Gagasan dasar negara Indonesia Merdeka yang disampaikan oleh Ir. Soekarno pada sidang BPUPKI tanggal 1 Juni 1945 dikenal dengan istilah...",
        "Piagam Jakarta", "Pancasila", "Panca Dharma", "Dwi Tunggal", "Trisakti",
        "B", 0, 0, 0, 0, 0,
        "Pada tanggal 1 Juni 1945, Ir. Soekarno menyampaikan pidato rumusan dasar negara yang ia beri nama Pancasila, yang kini diperingati sebagai Hari Lahir Pancasila."
    ],
    [
        $twkId, 2,
        "Sikap bangga dan mencintai produk-produk buatan anak bangsa merupakan wujud nyata pengamalan Pancasila, terutama sila...",
        "Pertama", "Kedua", "Ketiga", "Keempat", "Kelima",
        "C", 0, 0, 0, 0, 0,
        "Cinta dan bangga menggunakan produk dalam negeri adalah cerminan rasa nasionalisme dan patriotisme pada Sila Ketiga (Persatuan Indonesia)."
    ],
    [
        $twkId, 2,
        "Hak asasi manusia diatur secara komprehensif dalam UUD 1945 hasil amandemen pada Bab XA, yaitu dari...",
        "Pasal 27 sampai 28", "Pasal 28A sampai 28J", "Pasal 29 sampai 30", "Pasal 31 sampai 34", "Pasal 35 sampai 36",
        "B", 0, 0, 0, 0, 0,
        "Bab XA UUD 1945 secara khusus memuat jaminan Hak Asasi Manusia mulai dari Pasal 28A sampai Pasal 28J."
    ],
    [
        $twkId, 2,
        "Pasal UUD 1945 yang menegaskan bahwa 'Negara berdasar atas Ketuhanan Yang Maha Esa' adalah...",
        "Pasal 28E ayat (1)", "Pasal 29 ayat (1)", "Pasal 29 ayat (2)", "Pasal 31 ayat (1)", "Pasal 34 ayat (1)",
        "B", 0, 0, 0, 0, 0,
        "Pasal 29 ayat (1) UUD 1945 menyatakan: 'Negara berdasar atas Ketuhanan Yang Maha Esa.'"
    ],
    [
        $twkId, 2,
        "Toleransi antarsuku dan pemeluk agama yang berbeda dalam kehidupan bermasyarakat sesuai dengan pengamalan semboyan...",
        "Tut Wuri Handayani", "Bhinneka Tunggal Ika", "Ing Madya Mangun Karsa", "Mitreka Satata", "Jalesveva Jayamahe",
        "B", 0, 0, 0, 0, 0,
        "Semboyan 'Bhinneka Tunggal Ika' bermakna berbeda-beda tetapi tetap satu jua, menjadi perekat persatuan di tengah kemajemukan bangsa."
    ],

    // 10 TIU MINI
    [
        $tiuId, 2,
        "KULMINASI : TITIK PUNCAK = NADIR : ...",
        "Titik terendah", "Titik tengah", "Keseimbangan", "Ketinggian", "Kecepatan",
        "A", 0, 0, 0, 0, 0,
        "Kulminasi berlawanan dengan nadir (atau bermakna titik puncak), sedangkan nadir bermakna titik paling rendah/dasar."
    ],
    [
        $tiuId, 2,
        "GELOMBANG : LAUT = GEMPA : ...",
        "Banjir", "Tanah", "Angin", "Langit", "Hujan",
        "B", 0, 0, 0, 0, 0,
        "Gelombang terjadi pada media perairan laut. Gempa terjadi pada media batuan kerak bumi/tanah."
    ],
    [
        $tiuId, 2,
        "Semua dokter di rumah sakit memakai jas putih.\nSebagian pria di koridor rumah sakit adalah dokter.\nKesimpulan yang sah adalah...",
        "Semua pria di koridor rumah sakit memakai jas putih.",
        "Sebagian pria di koridor rumah sakit memakai jas putih.",
        "Hanya dokter yang berada di koridor rumah sakit.",
        "Tidak ada pria di koridor yang memakai jas putih.",
        "Semua dokter adalah pria di koridor rumah sakit.",
        "B", 0, 0, 0, 0, 0,
        "Sebagian pria di koridor adalah dokter, dan semua dokter berjas putih -> Sebagian pria di koridor memakai jas putih."
    ],
    [
        $tiuId, 2,
        "Jika x = 12^2 - 8^2 dan y = 80, maka...",
        "x > y", "x < y", "x = y", "x = 2y", "Hubungan tidak dapat ditentukan",
        "C", 0, 0, 0, 0, 0,
        "12^2 - 8^2 = (12 - 8)(12 + 8) = 4 x 20 = 80. Maka x = 80 dan y = 80 -> x = y."
    ],
    [
        $tiuId, 2,
        "5, 10, 20, 40, 80, ...",
        "120", "140", "150", "160", "180",
        "D", 0, 0, 0, 0, 0,
        "Pola perkalian 2: 80 x 2 = 160."
    ],
    [
        $tiuId, 2,
        "1, 4, 9, 16, 25, 36, ...",
        "42", "45", "48", "49", "54",
        "D", 0, 0, 0, 0, 0,
        "Pola bilangan kuadrat: 1^2, 2^2, 3^2, 4^2, 5^2, 6^2, maka selanjutnya 7^2 = 49."
    ],
    [
        $tiuId, 2,
        "Seorang pedagang membeli sepeda motor bekas seharga Rp 8.000.000, lalu memperbaikinya dengan biaya Rp 1.000.000. Jika motor dijual seharga Rp 10.800.000, berapa persen keuntungan yang didapat?",
        "15%", "18%", "20%", "22%", "25%",
        "C", 0, 0, 0, 0, 0,
        "Modal total = 8.000.000 + 1.000.000 = 9.000.000. Untung = 10.800.000 - 9.000.000 = 1.800.000. Persen untung = (1.800.000 / 9.000.000) x 100% = 20%."
    ],
    [
        $tiuId, 2,
        "Kereta api melaju dengan kecepatan konstan 90 km/jam. Berapa meter jarak yang ditempuh kereta tersebut dalam waktu 20 detik?",
        "300 meter", "450 meter", "500 meter", "600 meter", "750 meter",
        "C", 0, 0, 0, 0, 0,
        "Kecepatan dalam m/s = 90 x (1000/3600) = 25 m/s. Jarak = 25 m/s x 20 detik = 500 meter."
    ],
    [
        $tiuId, 2,
        "Jika 6 orang penjahit dapat menyelesaikan 18 potong pakaian dalam waktu 3 hari, berapa potong pakaian yang dapat diselesaikan oleh 4 orang penjahit dalam waktu 5 hari?",
        "16 potong", "18 potong", "20 potong", "22 potong", "24 potong",
        "C", 0, 0, 0, 0, 0,
        "Kapasitas 1 penjahit per hari = 18 / (6 x 3) = 18 / 18 = 1 potong/hari. Maka 4 penjahit dalam 5 hari = 4 x 5 x 1 = 20 potong."
    ],
    [
        $tiuId, 2,
        "Nilai dari (0,25 x 40) + (0,5 x 80) adalah...",
        "40", "45", "50", "55", "60",
        "C", 0, 0, 0, 0, 0,
        "0,25 x 40 = 10. 0,5 x 80 = 40. Total = 10 + 40 = 50."
    ],

    // 10 TKP MINI
    [
        $tkpId, 2,
        "Seorang warga datang meminta surat rekomendasi mendesak di luar jam kerja saat Anda sedang bersiap pulang kantor. Sikap Anda adalah...",
        "Menolak dan menyuruhnya datang besok pagi.",
        "Melayaninya dengan ramah dan menyelesaikan surat rekomendasi tersebut karena keadaannya mendesak, baru kemudian pulang.",
        "Meminta imbalan uang lembur kepadanya.",
        "Menyuruhnya menemui satpam.",
        "Memarahinya karena datang terlambat.",
        null, 1, 5, 2, 2, 1,
        "Aspek Dedikasi Pelayanan Publik: Membantu warga yang dalam keadaan mendesak mencerminkan komitmen pelayanan ASN (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 2,
        "Kantor Anda menerapkan tanda tangan elektronik (TTE) resmi untuk mempercepat disposisi berkas. Rekan kerja mengeluhkan cara pakainya. Sikap Anda adalah...",
        "Ikut mengeluh bersama rekan kerja.",
        "Mengajarkan rekan kerja cara menggunakan TTE langkah demi langkah hingga mahir.",
        "Menyuruh rekan kerja membaca buku manual sendiri.",
        "Menertawakan rekan yang gagap teknologi.",
        "Mengusulkan kembali ke sistem tanda tangan basah manual.",
        null, 2, 5, 3, 1, 1,
        "Aspek Pemanfaatan TIK & Kerjasama Tim: Membantu sesama pegawai beradaptasi dengan inovasi digital (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 2,
        "Anda dipindahkan ke unit kerja baru yang anggotanya terbiasa makan siang bersama di warung lokal dekat kantor. Sikap Anda adalah...",
        "Menolak ikut karena merasa makanan warung kurang higienis.",
        "Ikut bergabung dengan gembira untuk mengakrabkan diri dan mengenal rekan-rekan baru lebih dekat.",
        "Ikut hanya jika ditraktir oleh kepala kantor.",
        "Makan sendiri di meja kerja setiap hari.",
        "Menyindir kebiasaan mereka yang suka kumpul.",
        null, 1, 5, 2, 2, 1,
        "Aspek Adaptasi Sosial & Keakraban Tim: Membuka diri dan menyatu dengan budaya positif lingkungan kerja (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 2,
        "Seorang pengunjung memberikan kotak kue mahal sebagai tanda terima kasih setelah permohonannya selesai diproses tanpa kendala. Sikap Anda adalah...",
        "Menerima kue tersebut dan memakannya sendiri di rumah.",
        "Mengucapkan terima kasih dengan sopan, namun menjelaskan bahwa sudah menjadi kewajiban Anda melayani tanpa menerima imbalan apapun, dan menyarankan untuk dinikmati bersama keluarga di rumah.",
        "Meminta agar kuenya diganti dengan uang tunai saja.",
        "Membuang kue tersebut ke tempat sampah di depan pengunjung.",
        "Membagikan kue tersebut secara diam-diam kepada atasan.",
        null, 2, 5, 1, 1, 2,
        "Aspek Integritas & Menolak Gratifikasi: Tolak pemberian dengan santun dan berikan edukasi bahwa pelayanan ASN bebas imbalan (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 2,
        "Ketika Anda sedang dikejar tenggat waktu laporan dinas, koneksi internet kantor tiba-tiba terputus. Tindakan Anda adalah...",
        "Berhenti bekerja dan tidur siang.",
        "Menghubungkan komputer ke tethering ponsel pribadi sementara waktu agar pengiriman laporan tidak terlambat.",
        "Menyalahkan provider internet kantor di media sosial.",
        "Menunggu internet menyala kembali tanpa inisiatif.",
        "Pulang lebih awal karena merasa tidak bisa bekerja.",
        null, 1, 5, 2, 2, 1,
        "Aspek Inisiatif & Solusi Cepat: Berinisiatif menggunakan sarana alternatif demi menjaga batas waktu kerja (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 2,
        "Anda mengetahui bahwa terdapat rumor di kantor yang menyudutkan salah seorang rekan kerja Anda terkait isu SARA. Tindakan Anda adalah...",
        "Ikut menyebarkan rumor tersebut agar ramai.",
        "Tidak ikut terprovokasi, menghentikan penyebaran desas-desus, dan mengajak rekan lain fokus pada kinerja profesional.",
        "Menertawakan rekan yang menjadi korban rumor.",
        "Menjauhi rekan tersebut karena takut tertular masalah.",
        "Membuat akun anonim untuk memanaskan situasi.",
        null, 1, 5, 1, 2, 1,
        "Aspek Toleransi & Anti-Perpecahan: Menghentikan rumor destruktif dan menjaga keutuhan lingkungan kerja (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 2,
        "Atasan Anda memberikan instruksi kerja yang kurang jelas dan tampak terburu-buru. Tindakan Anda adalah...",
        "Mengerjakan asal-asalan sesuai tebakan Anda sendiri.",
        "Meminta konfirmasi dan klarifikasi secara sopan kepada atasan mengenai poin-poin yang belum jelas sebelum mulai bekerja.",
        "Mendiamkan tugas tersebut hingga atasan bertanya kembali.",
        "Mengeluhkan ketidakjelasan atasan kepada rekan kantor lain.",
        "Meminta bawahan Anda yang mengerjakannya.",
        null, 2, 5, 1, 2, 1,
        "Aspek Komunikasi Efektif dengan Pimpinan: Mengklarifikasi instruksi kerja demi mencegah kekeliruan hasil (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 2,
        "Seorang warga tunarungu datang ke loket pelayanan dan kesulitan menyampaikan maksud kedatangannya. Sikap Anda adalah...",
        "Menyuruhnya berbicara lebih keras.",
        "Menyediakan kertas dan pena untuk berkomunikasi secara tertulis dengan ramah dan sabar hingga tuntas.",
        "Menyuruh warga tersebut pulang karena tidak mengerti bahasanya.",
        "Memanggil satpam untuk mengusirnya.",
        "Mengabaikannya dan melayani antrean di belakangnya.",
        null, 1, 5, 1, 1, 1,
        "Aspek Pelayanan Inklusif Ramah Disabilitas: Berinisiatif menyediakan media komunikasi alternatif bagi kelompok rentan (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 2,
        "Rekan kerja satu tim Anda melakukan kesalahan kecil dalam pengetikan nama di sertifikat penerima penghargaan. Tindakan Anda adalah...",
        "Memarahinya di depan umum agar tidak ceroboh lagi.",
        "Membantunya mencetak ulang dan memperbaiki dokumen tersebut dengan cepat sebelum acara penyerahan dimulai.",
        "Membiarkannya agar ditegur oleh pimpinan acara.",
        "Menyalahkan pimpinan yang memilih rekan tersebut.",
        "Menertawakan kesalahan tersebut.",
        null, 2, 5, 1, 1, 1,
        "Aspek Kerjasama Tim & Tanggap Darurat: Saling menopang dan menyelesaikan kendala bersama demi kesuksesan agenda instansi (Poin 5 untuk Opsi B)."
    ],
    [
        $tkpId, 2,
        "Instansi Anda menugaskan Anda mengikuti pelatihan bela negara dan kedisiplinan fisik di markas militer selama 1 minggu. Sikap Anda adalah...",
        "Mencari surat sakit agar terbebas dari latihan fisik.",
        "Mengikuti pelatihan tersebut dengan penuh semangat untuk meningkatkan ketahanan fisik, disiplin, dan rasa cinta tanah air.",
        "Mengeluh setiap hari selama pelatihan.",
        "Kabur dari asrama pelatihan saat malam hari.",
        "Mengikuti latihan dengan malas-malasan.",
        null, 1, 5, 2, 1, 1,
        "Aspek Bela Negara & Ketahanan Fisik Mental: Menghayati pembinaan disiplin sebagai penguatan karakter ASN berintegritas (Poin 5 untuk Opsi B)."
    ]
];

// Insert TWK
foreach ($twkQuestions as $q) {
    $qStmt->execute($q);
    $count++;
}
echo "✓ Berhasil memasukkan " . count($twkQuestions) . " butir soal TWK (Paket Akbar & Mini).\n";

// Insert TIU
foreach ($tiuQuestions as $q) {
    $qStmt->execute($q);
    $count++;
}
echo "✓ Berhasil memasukkan " . count($tiuQuestions) . " butir soal TIU (Paket Akbar & Mini).\n";

// Insert TKP
foreach ($tkpQuestions as $q) {
    $qStmt->execute($q);
    $count++;
}
echo "✓ Berhasil memasukkan " . count($tkpQuestions) . " butir soal TKP (Paket Akbar & Mini).\n";

// Insert Mini Questions
foreach ($miniQuestions as $q) {
    $qStmt->execute($q);
    $count++;
}
echo "✓ Berhasil memasukkan " . count($miniQuestions) . " butir soal Paket Mini Tryout (Free Trial).\n";

echo "=== SELESAI: Total $count Butir Soal Terstandarisasi BKN Telah Dimasukkan ===\n";
