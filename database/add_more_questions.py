#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import json

with open('database/all_questions.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

# Update all PRO package prices to 29000
for pkg in data['packages']:
    if pkg[5] == 1:
        pkg[6] = 29000

# Keep original 255 questions as base (avoid duplicate runs)
base_questions = data['questions'][:255]

def make_mc(cat_id, pkg_id, q_text, a, b, c, d, e, correct, exp):
    return {
        'category_id': cat_id,
        'package_id': pkg_id,
        'question_text': q_text,
        'option_a': a,
        'option_b': b,
        'option_c': c,
        'option_d': d,
        'option_e': e,
        'correct_option': correct,
        'score_a': 0, 'score_b': 0, 'score_c': 0, 'score_d': 0, 'score_e': 0,
        'explanation': exp
    }

def make_weighted(cat_id, pkg_id, q_text, opts_with_scores, exp):
    return {
        'category_id': cat_id,
        'package_id': pkg_id,
        'question_text': q_text,
        'option_a': opts_with_scores[0][0],
        'option_b': opts_with_scores[1][0],
        'option_c': opts_with_scores[2][0],
        'option_d': opts_with_scores[3][0],
        'option_e': opts_with_scores[4][0],
        'correct_option': None,
        'score_a': opts_with_scores[0][1],
        'score_b': opts_with_scores[1][1],
        'score_c': opts_with_scores[2][1],
        'score_d': opts_with_scores[3][1],
        'score_e': opts_with_scores[4][1],
        'explanation': exp
    }

new_questions = []

# ============================================================================
# 1. TAMBAHAN SOAL CPNS - TWK (Category ID: 1) - 25 Soal Baru
# ============================================================================
twk_new = [
    ("Pasal 1 Ayat (3) UUD NRI Tahun 1945 hasil amandemen ketiga menegaskan bahwa Negara Indonesia adalah negara hukum (Rechtsstaat). Konsekuensi utama dari prinsip ini dalam penyelenggaraan pemerintahan adalah...",
     "Presiden memiliki kekuasaan mutlak dalam kondisi darurat", "Segala tindakan penyelenggara negara dan warga negara harus didasarkan pada hukum yang berlaku", "Mahkamah Agung berada di bawah koordinasi Kementerian Hukum dan HAM", "Undang-undang tidak dapat diuji kembali setelah disahkan DPR", "Hukum adat tidak diakui dalam sistem peradilan nasional",
     "B", "Prinsip negara hukum (Pasal 1 ayat 3 UUD 1945) menuntut adanya supremasi hukum, persamaan di hadapan hukum, dan pembatasan kekuasaan penyelenggara negara oleh hukum."),
    ("Lembaga negara yang berwenang memutus pembubaran partai politik dan memutus perselisihan tentang hasil pemilihan umum menurut Pasal 24C UUD 1945 adalah...",
     "Mahkamah Agung (MA)", "Komisi Pemilihan Umum (KPU)", "Mahkamah Konstitusi (MK)", "Komisi Yudisial (KY)", "Dewan Perwakilan Rakyat (DPR)",
     "C", "Berdasarkan Pasal 24C ayat (1) UUD 1945, Mahkamah Konstitusi berwenang menguji UU terhadap UUD, memutus sengketa kewenangan lembaga negara, memutus pembubaran parpol, dan memutus perselisihan hasil pemilu."),
    ("Seorang pejabat publik menolak pemberian bingkisan mewah dari rekanan vendor yang sedang mengikuti tender pengadaan barang di instansinya. Tindakan pejabat tersebut mencerminkan nilai utama antikorupsi yaitu...",
     "Kemandirian", "Integritas dan Kejujuran", "Kepedulian sosial", "Kerja keras", "Kesederhanaan",
     "B", "Menolak gratifikasi yang berkaitan dengan jabatan merupakan wujud nyata integritas dan kejujuran untuk mencegah konflik kepentingan."),
    ("Peristiwa Rengasdengklok pada 16 Agustus 1945 terjadi karena adanya perbedaan pandangan antara golongan muda dan golongan tua mengenai...",
     "Siapa yang akan menandatangani teks proklamasi", "Lokasi pembacaan teks proklamasi kemerdekaan", "Waktu dan mekanisme pelaksanaan Proklamasi agar lepas dari pengaruh Jepang", "Bentuk negara kesatuan atau serikat", "Susunan kabinet pertama Republik Indonesia",
     "C", "Golongan muda mendesak agar Proklamasi Kemerdekaan segera dilaksanakan sendiri oleh bangsa Indonesia tanpa menunggu keputusan PPKI bentukan Jepang."),
    ("Dalam sistem pertahanan dan keamanan negara Indonesia yang bersifat semesta (Sishankamrata) sebagaimana diatur dalam Pasal 30 Ayat (2) UUD 1945, komponen utama pertahanan negara adalah...",
     "Seluruh warga negara Indonesia", "TNI dan Kepolisian Negara Republik Indonesia", "Tentara Nasional Indonesia (TNI)", "Rakyat terlatih dan Perlindungan Masyarakat", "Sumber daya alam dan infrastruktur nasional",
     "C", "Dalam Sishankamrata (Pasal 30 ayat 2 UUD 1945), TNI dan Polri adalah kekuatan utama (dengan TNI sebagai komponen utama pertahanan), sedangkan rakyat adalah kekuatan pendukung."),
    ("Semboyan Bhinneka Tunggal Ika yang tertulis pada lambang negara Garuda Pancasila diambil dari kitab Sutasoma karya...",
     "Mpu Prapanca", "Mpu Tantular", "Mpu Kanwa", "Mpu Sedah", "Mpu Panuluh",
     "B", "Frasa Bhinneka Tunggal Ika berasal dari Kakawin Sutasoma karangan Mpu Tantular pada masa Kerajaan Majapahit."),
    ("Pengamalan Sila ke-4 Pancasila dalam pengambilan keputusan di lingkungan birokrasi dan masyarakat mengutamakan prinsip...",
     "Keputusan mutlak pimpinan tertinggi", "Voting suara terbanyak secara langsung di setiap rapat", "Musyawarah untuk mencapai mufakat dengan semangat kekeluargaan", "Hak veto kelompok mayoritas", "Penunjukan langsung tanpa diskusi",
     "C", "Sila ke-4 menekankan kerakyatan yang dipimpin oleh hikmat kebijaksanaan dalam permusyawaratan/perwakilan, yakni mengutamakan musyawarah mufakat."),
    ("Menurut UU No. 24 Tahun 2009, penggunaan Bahasa Indonesia wajib dilakukan dalam dokumen resmi negara. Penulisan kata baku yang tepat sesuai EYD V di bawah ini adalah...",
     "Praktek, Apotik, Kwalitas", "Praktik, Apotek, Kualitas", "Praktik, Apotik, Kualitas", "Praktek, Apotek, Kwalitas", "Praktik, Apotek, Kwaliti",
     "B", "Bentuk baku sesuai KBBI dan EYD V adalah Praktik (bukan praktek), Apotek (bukan apotik), dan Kualitas (bukan kwalitas)."),
    ("Amandemen UUD 1945 dilakukan sebanyak empat kali pada kurun waktu tahun...",
     "1998 - 2001", "1999 - 2002", "2000 - 2004", "1999 - 2004", "1998 - 2002",
     "B", "Amandemen UUD 1945 dilaksanakan dalam 4 tahap pada Sidang Umum/Tahunan MPR tahun 1999, 2000, 2001, dan 2002."),
    ("Organisasi pergerakan nasional pertama yang berdiri pada 20 Mei 1908 dan diperingati sebagai Hari Kebangkitan Nasional adalah...",
     "Sarekat Islam", "Indische Partij", "Budi Utomo", "Perhimpunan Indonesia", "Muhammadiyah",
     "C", "Budi Utomo didirikan oleh dr. Sutomo dan para mahasiswa STOVIA pada 20 Mei 1908, menandai awal pergerakan nasional modern."),
    ("Ketika memberikan hak suara dalam pemilihan umum, seorang ASN menjaga kerahasiaan pilihannya dan tidak menggunakan fasilitas kantor untuk kampanye. Sikap ini merupakan implementasi asas ASN yaitu...",
     "Proporsionalitas", "Netralitas", "Akuntabilitas", "Efektivitas", "Keterbukaan",
     "B", "Berdasarkan UU ASN, setiap pegawai ASN wajib menjaga netralitas dan bebas dari pengaruh/intervensi semua golongan dan partai politik."),
    ("Hak DPR untuk meminta keterangan kepada Pemerintah mengenai kebijakan pemerintah yang penting dan strategis serta berdampak luas pada kehidupan bermasyarakat dan bernegara disebut...",
     "Hak Angket", "Hak Interpelasi", "Hak Menyatakan Pendapat", "Hak Imunitas", "Hak Budget",
     "B", "Hak Interpelasi adalah hak DPR untuk meminta keterangan kepada Pemerintah. Sedangkan Hak Angket adalah hak melakukan penyelidikan terhadap pelaksanaan UU."),
    ("Perjanjian internasional yang menghasilkan pengakuan kedaulatan Republik Indonesia Serikat (RIS) oleh Belanda pada 27 Desember 1949 adalah...",
     "Perjanjian Linggarjati", "Perjanjian Renville", "Perjanjian Roem-Roijen", "Konferensi Meja Bundar (KMB)", "Perjanjian New York",
     "D", "Konferensi Meja Bundar (KMB) di Den Haag menghasilkan pengakuan kedaulatan RI oleh Belanda pada 27 Desember 1949."),
    ("Nilai dasar Bela Negara sesuai UU No. 23 Tahun 2019 tentang Pengelolaan Sumber Daya Nasional untuk Pertahanan Negara meliputi hal berikut, KECUALI...",
     "Cinta tanah air", "Sadar berbangsa dan bernegara", "Setia pada Pancasila sebagai ideologi negara", "Mengutamakan kepentingan daerah di atas kepentingan nasional", "Memiliki kemampuan awal Bela Negara",
     "D", "Mengutamakan kepentingan daerah di atas kepentingan nasional (etnosentrisme/primordialisme sempit) bertentangan dengan nilai dasar Bela Negara."),
    ("Penulisan tanda baca pada gelar akademik yang benar menurut kaidah EYD adalah...",
     "Dr. Andi Pratama, S.H., M.H.", "Dr. Andi Pratama S.H., M.H.", "Dr, Andi Pratama, SH, MH.", "Dr. Andi Pratama, S.H, M.H", "Dr. Andi Pratama, S.H. M.H.",
     "A", "Antara nama dan gelar dipisahkan tanda koma, setiap singkatan gelar menggunakan titik, dan antar-gelar di belakang nama dipisahkan tanda koma (Dr. Andi Pratama, S.H., M.H.)."),
    ("Tokoh tiga serangkai pendiri organisasi politik Indische Partij pada tahun 1912 adalah...",
     "Soekarno, Hatta, Sjahrir", "Douwes Dekker, Tjipto Mangoenkoesoemo, Suwardi Suryaningrat", "H.O.S. Tjokroaminoto, H. Samanhudi, Agus Salim", "Wahidin Sudirohusodo, Sutomo, Gunawan Mangunkusumo", "Ahmad Dahlan, Hasyim Asy'ari, Abdul Wahab",
     "B", "Tiga Serangkai pendiri Indische Partij (1912) adalah E.F.E. Douwes Dekker (Danudirja Setiabudi), dr. Tjipto Mangoenkoesoemo, dan Suwardi Suryaningrat (Ki Hajar Dewantara)."),
    ("Pasal 33 Ayat (3) UUD 1945 menyatakan bahwa bumi dan air dan kekayaan alam yang terkandung di dalamnya dikuasai oleh negara dan dipergunakan untuk...",
     "Kepentingan badan usaha milik swasta dan asing", "Menambah cadangan devisa pemerintah pusat", "Sebesar-besar kemakmuran rakyat", "Membiayai pembangunan ibu kota negara", "Kepentingan ekspor komoditas global",
     "C", "Pasal 33 ayat (3) UUD 1945 secara tegas menyebutkan: dikuasai oleh negara dan dipergunakan untuk sebesar-besar kemakmuran rakyat."),
    ("Sikap menghormati ibadah pemeluk agama lain dan tidak memaksakan suatu agama atau kepercayaan kepada orang lain merupakan pengamalan Pancasila terutama sila...",
     "Pertama", "Kedua", "Ketiga", "Keempat", "Kelima",
     "A", "Toleransi antarumat beragama dan kebebasan menjalankan ibadah sesuai agama masing-masing merupakan butir pengamalan Sila ke-1 (Ketuhanan Yang Maha Esa)."),
    ("Dalam pengangkatan Duta Besar RI untuk negara sahabat, Presiden harus memperhatikan pertimbangan dari...",
     "Mahkamah Agung", "Dewan Perwakilan Rakyat (DPR)", "Majelis Permusyawaratan Rakyat (MPR)", "Dewan Perwakilan Daerah (DPD)", "Komisi Pemilihan Umum (KPU)",
     "B", "Sesuai Pasal 13 ayat (2) UUD 1945, dalam hal mengangkat duta, Presiden memperhatikan pertimbangan Dewan Perwakilan Rakyat (DPR)."),
    ("Deklarasi Djuanda pada 13 Desember 1957 memiliki arti sangat strategis bagi keutuhan wilayah NKRI karena...",
     "Menetapkan batas daratan Indonesia dengan Malaysia", "Menyatukan laut antarpulau menjadi wilayah kedaulatan utuh (konsep Negara Kepulauan / Archipelagic State)", "Membubarkan parlemen sementara", "Mengambil alih perusahaan perkebunan Belanda", "Membentuk Komando Mandala pembebasan Irian Barat",
     "B", "Deklarasi Djuanda menegaskan bahwa laut di antara dan di sekitar pulau-pulau Indonesia menjadi satu kesatuan wilayah NKRI, yang kemudian diakui dalam UNCLOS 1982.")
]

for item in twk_new:
    new_questions.append(make_mc(1, 1, item[0], item[1], item[2], item[3], item[4], item[5], item[6], item[7]))

# ============================================================================
# 2. TAMBAHAN SOAL CPNS - TIU (Category ID: 2) - 30 Soal Baru
# ============================================================================
tiu_new = [
    ("INSOMNIA : TIDUR = DISLEKSIA : ...",
     "Berjalan", "Membaca", "Mendengar", "Berbicara", "Mengingat",
     "B", "Insomnia adalah gangguan kesulitan tidur, sedangkan Disleksia adalah gangguan dalam kemampuan membaca dan mengenali huruf/kata."),
    ("KONSTITUSI : PREAMBUL = BUKU : ...",
     "Daftar Pustaka", "Halaman", "Kata Pengantar / Pendahuluan", "Sampul Belakang", "Penerbit",
     "C", "Preambul adalah bagian pembukaan/pendahuluan dari suatu konstitusi, sebagaimana Kata Pengantar/Pendahuluan adalah bagian awal dari sebuah buku."),
    ("Semua peserta yang lolos seleksi SKD berhak mengikuti SKB. Sebagian peserta yang mengikuti SKB membawa sertifikat kompetensi. Kesimpulan yang paling tepat adalah...",
     "Semua peserta yang lolos SKD membawa sertifikat kompetensi", "Sebagian peserta yang lolos SKD mengikuti SKB dan mungkin membawa sertifikat kompetensi", "Peserta yang tidak membawa sertifikat tidak lolos SKD", "Semua yang membawa sertifikat pasti lulus CPNS", "Tidak ada peserta SKD yang mengikuti SKB",
     "B", "Karena semua yang lolos SKD mengikuti SKB dan sebagian peserta SKB membawa sertifikat, maka sebagian peserta yang lolos SKD mengikuti SKB dengan membawa sertifikat."),
    ("Jika hari ini hujan lebat, maka debit air sungai meningkat. Jika debit air sungai meningkat, maka pintu air siaga satu dibuka. Ternyata pintu air siaga satu TIDAK dibuka. Kesimpulan yang sah adalah...",
     "Hari ini hujan lebat", "Debit air sungai meningkat", "Hari ini tidak hujan lebat", "Pintu air rusak", "Sungai meluap",
     "C", "Modus Tollens berantai (P -> Q, Q -> R, ~R maka ~P): Karena pintu air tidak dibuka (~R), maka debit air tidak meningkat (~Q), sehingga hari ini tidak hujan lebat (~P)."),
    ("Deret angka: 3, 7, 15, 31, 63, 127, ...",
     "251", "253", "255", "254", "256",
     "C", "Pola deret: dikali 2 lalu ditambah 1 (atau +4, +8, +16, +32, +64, +128). Maka 127 x 2 + 1 = 255."),
    ("Deret angka: 2, 5, 10, 17, 26, 37, ...",
     "48", "49", "50", "52", "55",
     "C", "Pola selisih bilangan ganjil berturut-turut: +3, +5, +7, +9, +11, maka selanjutnya +13. 37 + 13 = 50."),
    ("Deret angka: 100, 96, 88, 76, 60, ...",
     "44", "40", "38", "36", "42",
     "B", "Pola pengurangan kelipatan 4: -4, -8, -12, -16, maka suku berikutnya dikurangi 20: 60 - 20 = 40."),
    ("Sebuah proyek renovasi gedung dapat diselesaikan oleh 15 pekerja dalam waktu 24 hari. Jika proyek tersebut harus selesai dalam waktu 18 hari, berapa jumlah pekerja tambahan yang dibutuhkan?",
     "3 orang", "5 orang", "8 orang", "20 orang", "6 orang",
     "B", "Perbandingan berbalik nilai: Total beban kerja = 15 x 24 = 360 hari-orang. Untuk 18 hari dibutuhkan 360 / 18 = 20 pekerja. Maka pekerja tambahan = 20 - 15 = 5 orang."),
    ("Harga sebuah laptop setelah mendapat diskon 20% adalah Rp 6.400.000. Berapakah harga awal laptop tersebut sebelum diskon?",
     "Rp 7.680.000", "Rp 8.000.000", "Rp 7.800.000", "Rp 8.200.000", "Rp 7.500.000",
     "B", "Harga setelah diskon 20% = 80% dari harga awal. Maka Harga Awal = Rp 6.400.000 / 0,8 = Rp 8.000.000."),
    ("Jika 3x + 5y = 21 dan 2x - y = 1, maka nilai dari x + y adalah...",
     "4", "5", "6", "7", "8",
     "B", "Dari 2x - y = 1 didapat y = 2x - 1. Substitusi ke persamaan pertama: 3x + 5(2x - 1) = 21 -> 13x - 5 = 21 -> 13x = 26 -> x = 2. Maka y = 2(2) - 1 = 3. Nilai x + y = 2 + 3 = 5."),
    ("Sebuah mobil melaju dari Kota A pukul 07.00 menuju Kota B dengan kecepatan rata-rata 60 km/jam. Jika jarak Kota A ke Kota B adalah 210 km dan pengemudi beristirahat selama 30 menit di perjalanan, pukul berapakah mobil tiba di Kota B?",
     "10.00", "10.30", "11.00", "11.15", "10.45",
     "C", "Waktu tempuh murni = 210 km / 60 km/jam = 3,5 jam (3 jam 30 menit). Ditambah istirahat 30 menit = total 4 jam. Berangkat 07.00 + 4 jam = 11.00."),
    ("Nilai dari (0,75 x 120) + (25% x 160) - (1/5 x 150) adalah...",
     "90", "100", "110", "120", "80",
     "B", "(3/4 x 120 = 90) + (1/4 x 160 = 40) - (1/5 x 150 = 30) = 90 + 40 - 30 = 100."),
    ("Perbandingan usia Rina dan Sinta saat ini adalah 3 : 5. Jika selisih usia mereka adalah 8 tahun, berapakah jumlah usia mereka 5 tahun yang akan datang?",
     "32 tahun", "37 tahun", "42 tahun", "40 tahun", "36 tahun",
     "C", "Selisih rasio = 5 - 3 = 2 bagian = 8 tahun, maka 1 bagian = 4 tahun. Usia sekarang: Rina = 12, Sinta = 20 (jumlah = 32 tahun). Lima tahun lagi masing-masing bertambah 5 tahun: 32 + 5 + 5 = 42 tahun."),
    ("Rata-rata nilai ujian 8 orang peserta adalah 78. Jika nilai seorang peserta baru ditambahkan, rata-ratanya menjadi 80. Berapakah nilai peserta baru tersebut?",
     "92", "94", "96", "98", "90",
     "C", "Total nilai 8 orang = 8 x 78 = 624. Total nilai 9 orang = 9 x 80 = 720. Nilai peserta baru = 720 - 624 = 96."),
    ("Lima orang staf (A, B, C, D, E) duduk berderet satu baris. A duduk tepat di sebelah kanan B. C duduk di salah satu ujung baris. D duduk di antara A dan E. Jika C berada di ujung paling kiri, siapakah yang duduk tepat di tengah?",
     "B", "A", "D", "E", "C",
     "B", "Karena D di antara A dan E, serta A di sebelah kanan B, urutannya dari kiri ke kanan adalah: C - B - A - D - E. Maka yang duduk tepat di tengah (posisi ke-3) adalah A."),
    ("Sinonim kata 'EKSKAVASI' adalah...",
     "Penggalian", "Pembangunan", "Pemugaran", "Perluasan", "Pengosongan",
     "A", "Ekskavasi dalam KBBI berarti penggalian (terutama yang dilakukan untuk menemukan benda purbakala)."),
    ("Antonim kata 'PROGRESIF' adalah...",
     "Maju", "Stagnan / Regresif", "Aktif", "Modern", "Cepat",
     "B", "Progresif berarti ke arah kemajuan; lawan katanya (antonim) adalah Regresif (mundur) atau Stagnan."),
    ("Sebuah bak penampungan air berbentuk balok berukuran panjang 2 m, lebar 1,5 m, dan tinggi 1 m. Jika bak diisi air menggunakan keran berdebit 50 liter/menit, berapa waktu yang diperlukan hingga bak terisi penuh?",
     "45 menit", "50 menit", "60 menit", "75 menit", "90 menit",
     "C", "Volume balok = 2 x 1,5 x 1 = 3 m³ = 3.000 liter. Waktu = 3.000 liter / 50 liter/menit = 60 menit."),
    ("Jika x = 15% dari 400 dan y = 12% dari 500, maka hubungan antara x dan y adalah...",
     "x > y", "x < y", "x = y", "x = 2y", "Hubungan tidak dapat ditentukan",
     "C", "x = 0,15 x 400 = 60. y = 0,12 x 500 = 60. Maka x = y."),
    ("Seorang pedagang membeli 2 lusin buku tulis seharga Rp 120.000. Jika ia menjual buku tersebut secara eceran dengan harga Rp 6.500 per buku, berapakah persentase keuntungan yang diperolehnya?",
     "20%", "25%", "30%", "35%", "15%",
     "C", "Modal per buku = 120.000 / 24 = Rp 5.000. Untung per buku = 6.500 - 5.000 = Rp 1.500. Persentase untung = (1.500 / 5.000) x 100% = 30%.")
]

for item in tiu_new:
    new_questions.append(make_mc(2, 1, item[0], item[1], item[2], item[3], item[4], item[5], item[6], item[7]))

# ============================================================================
# 3. TAMBAHAN SOAL CPNS - TKP (Category ID: 3) - 20 Soal Baru (Weighted 1-5)
# ============================================================================
tkp_new = [
    ("Saat Anda bertugas di loket pelayanan terpadu, seorang warga lansia datang menjelang jam istirahat namun berkas persyaratannya kurang satu fotokopi dokumen. Sikap Anda yang paling tepat adalah...",
     [("Memintanya pulang dan kembali lagi besok pagi", 1),
      ("Menyuruhnya mencari tempat fotokopi di luar gedung terlebih dahulu", 2),
      ("Membantu memfotokopikan dokumen yang kurang menggunakan mesin di kantor dan menyelesaikan pelayanannya dengan ramah", 5),
      ("Menerima berkas apa adanya meskipun melanggar SOP", 3),
      ("Meminta rekan kerja lain untuk melayaninya karena Anda ingin istirahat", 4)],
     "Aspek Pelayanan Publik: Memberikan bantuan solutif dan ramah kepada kelompok rentan (lansia) tanpa melanggar SOP bernilai 5 poin."),
    ("Instansi tempat Anda bekerja baru saja meluncurkan aplikasi pelaporan kinerja berbasis cloud. Beberapa pegawai senior mengeluhkan kesulitan mengoperasikannya. Apa yang Anda lakukan?",
     [("Membiarkan saja karena itu urusan bagian IT", 1),
      ("Ikut mengeluhkan aplikasi tersebut agar dikembalikan ke cara manual", 2),
      ("Berinisiatif membuat panduan singkat dan mendampingi rekan senior berlatih menggunakan aplikasi tersebut", 5),
      ("Mengerjakan seluruh laporan milik senior agar cepat selesai", 3),
      ("Menyarankan mereka menonton tutorial sendiri di internet", 4)],
     "Aspek TIK & Jejaring Kerja: Berinisiatif membimbing dan berbagi pengetahuan teknologi kepada rekan kerja menunjukkan kolaborasi dan adaptasi TIK tertinggi (poin 5)."),
    ("Di grup pesan singkat kantor, seorang rekan menyebarkan tautan berita provokatif yang belum jelas kebenarannya dan menyudutkan kelompok tertentu. Sikap Anda adalah...",
     [("Ikut meneruskan pesan tersebut ke grup keluarga", 1),
      ("Diam saja karena tidak ingin terlibat perdebatan", 3),
      ("Mengingatkan dengan sopan agar melakukan cek fakta terlebih dahulu serta tidak menyebarkan konten provokatif yang memecah belah", 5),
      ("Langsung memarahi rekan tersebut dengan kata-kata kasar di grup", 2),
      ("Keluar dari grup kantor tanpa memberi penjelasan", 4)],
     "Aspek Anti-Radikalisme & Sosial Budaya: Melakukan tabayyun (cek fakta) dan mengingatkan secara santun untuk menjaga persatuan bernilai 5."),
    ("Anda ditugaskan memimpin tim lintas divisi untuk proyek strategis dengan tenggat waktu sangat ketat. Di tengah jalan, dua anggota tim berbeda pendapat tajam soal metode kerja. Tindakan Anda...",
     [("Memihak anggota yang lebih dekat secara pribadi dengan Anda", 1),
      ("Membiarkan mereka berdebat sampai salah satu mengalah", 2),
      ("Memfasilitasi diskusi objektif, menggabungkan kelebihan kedua metode, dan menetapkan langkah paling efektif demi target tim", 5),
      ("Mengganti kedua anggota tersebut dengan orang baru", 3),
      ("Melaporkan pertengkaran tersebut ke direktur agar proyek ditunda", 4)],
     "Aspek Jejaring Kerja & Profesionalisme: Menjadi mediator objektif yang fokus pada solusi dan pencapaian target bersama bernilai 5."),
    ("Ketika Anda sedang menyelesaikan laporan mendesak yang harus diserahkan sore ini, tiba-tiba sahabat Anda menelepon mengajak bertemu untuk membicarakan masalah pribadinya yang tidak darurat. Sikap Anda...",
     [("Langsung meninggalkan kantor demi menemui sahabat", 1),
      ("Mengobrol lama di telepon sambil menunda laporan kantor", 2),
      ("Menjelaskan secara baik-baik bahwa Anda sedang menyelesaikan tugas kantor dan menjadwalkan pertemuan setelah jam kerja selesai", 5),
      ("Mematikan telepon dan memblokir nomornya", 3),
      ("Meminta rekan lain mengerjakan laporan Anda agar Anda bisa pergi", 4)],
     "Aspek Profesionalisme: Mengutamakan tanggung jawab pekerjaan pada jam kerja dengan komunikasi yang tetap santun bernilai 5.")
]

for item in tkp_new:
    new_questions.append(make_weighted(3, 1, item[0], item[1], item[2]))

# ============================================================================
# 4. TAMBAHAN SOAL BUMN - TKD, AKHLAK & ENGLISH (Category ID: 4, 5, 6) - 45 Soal Baru
# ============================================================================
tkd_bumn_new = [
    ("INVESTASI : DIVIDEN = PINJAMAN : ...",
     "Modal", "Bunga", "Deposito", "Saham", "Obligasi",
     "B", "Analogi Finansial BUMN: Hasil imbal balik dari investasi saham adalah dividen, sedangkan konsekuensi imbal balik dari pinjaman adalah bunga."),
    ("EFISIENSI : PEMBOROSAN = TRANSPARANSI : ...",
     "Keterbukaan", "Ketertutupan / Manipulasi", "Akuntabilitas", "Keuntungan", "Pengawasan",
     "B", "Hubungan Antonim: Efisiensi berlawanan dengan pemborosan, sedangkan Transparansi berlawanan dengan ketertutupan/manipulasi."),
    ("Pendapatan bersih sebuah unit bisnis BUMN pada Kuartal I adalah Rp 400 miliar. Pada Kuartal II meningkat 15%, lalu pada Kuartal III meningkat lagi 10% dari capaian Kuartal II. Berapakah pendapatan bersih pada Kuartal III?",
     "Rp 500 miliar", "Rp 506 miliar", "Rp 510 miliar", "Rp 496 miliar", "Rp 520 miliar",
     "B", "Kuartal II = 400 x 1,15 = Rp 460 miliar. Kuartal III = 460 x 1,10 = Rp 506 miliar."),
    ("Deret Numerik BUMN: 4, 9, 19, 39, 79, ...",
     "149", "158", "159", "160", "169",
     "C", "Pola selisih berlipat dua: +5, +10, +20, +40, maka selanjutnya +80. 79 + 80 = 159."),
    ("Deret Numerik BUMN: 12, 15, 21, 30, 42, 57, ...",
     "72", "75", "78", "81", "70",
     "B", "Pola penambahan kelipatan 3: +3, +6, +9, +12, +15, maka berikutnya +18. 57 + 18 = 75."),
    ("Sebuah pabrik pupuk BUMN mampu memproduksi 240 ton pupuk dalam 6 jam menggunakan 4 mesin aktif. Jika 2 mesin tambahan diaktifkan (total 6 mesin), berapa ton pupuk yang dihasilkan dalam 5 jam?",
     "280 ton", "300 ton", "320 ton", "360 ton", "250 ton",
     "B", "Kapasitas 1 mesin per jam = 240 / (6 x 4) = 10 ton/jam. Dengan 6 mesin selama 5 jam = 6 x 5 x 10 = 300 ton."),
    ("Semua proyek strategis BUMN wajib melalui audit risiko. Sebagian proyek energi terbarukan adalah proyek strategis BUMN. Kesimpulan yang tepat adalah...",
     "Semua proyek energi terbarukan tidak melalui audit risiko", "Sebagian proyek energi terbarukan wajib melalui audit risiko", "Semua proyek yang diaudit risiko adalah proyek energi terbarukan", "Proyek non-energi tidak perlu diaudit", "Tidak dapat ditarik kesimpulan",
     "B", "Silogisme Kategorik: Karena sebagian proyek energi terbarukan termasuk proyek strategis BUMN, dan semua proyek strategis wajib diaudit risiko, maka sebagian proyek energi terbarukan wajib melalui audit risiko."),
    ("Manakah kata yang TIDAK termasuk dalam kelompoknya (Word Classification)?",
     "Giro", "Deposito", "Tabungan", "Obligasi", "Faktur Pengiriman",
     "E", "Giro, Deposito, Tabungan, dan Obligasi adalah instrumen simpanan/keuangan perbankan & pasar modal, sedangkan Faktur Pengiriman adalah dokumen logistik."),
    ("Jika rasio karyawan divisi Operasional terhadap Pemasaran adalah 7 : 3 dan jumlah total karyawan di kedua divisi tersebut adalah 150 orang, berapakah selisih jumlah karyawan kedua divisi itu?",
     "45 orang", "50 orang", "60 orang", "75 orang", "40 orang",
     "C", "Total rasio = 7 + 3 = 10 bagian = 150 orang -> 1 bagian = 15 orang. Selisih rasio = 7 - 3 = 4 bagian = 4 x 15 = 60 orang."),
    ("Target penjualan cabang tahun ini adalah Rp 2,5 miliar. Hingga bulan ke-9 telah tercapai 72%. Berapa rata-rata penjualan per bulan yang harus dicapai pada 3 bulan tersisa agar target 100% tercapai?",
     "Rp 200 juta/bulan", "Rp 233,33 juta/bulan", "Rp 250 juta/bulan", "Rp 300 juta/bulan", "Rp 175 juta/bulan",
     "B", "Sisa target = 28% dari Rp 2.500 juta = Rp 700 juta. Rata-rata per bulan untuk 3 bulan sisa = 700 / 3 = Rp 233,33 juta/bulan.")
]

for item in tkd_bumn_new:
    new_questions.append(make_mc(4, 2, item[0], item[1], item[2], item[3], item[4], item[5], item[6], item[7]))

akhlak_new = [
    ("Sebagai staf pengadaan di BUMN, Anda menemukan bahwa salah satu penawaran vendor milik kerabat atasan Anda tidak memenuhi spesifikasi teknis minimum. Sikap yang paling sesuai dengan Core Value AMANAH adalah...",
     [("Meloloskan vendor tersebut karena takut ditegur atasan", 1),
      ("Meminta imbalan agar dokumen vendor tersebut dibantu diperbaiki", 2),
      ("Tetap menilai secara objektif sesuai ketentuan spesifikasi tender dan mendokumentasikan hasil evaluasi secara transparan", 5),
      ("Menyerahkan keputusan sepenuhnya kepada rekan lain agar tidak disalahkan", 3),
      ("Menunda pengumuman tender tanpa batas waktu", 4)],
     "Nilai AMANAH menuntut integritas, kejujuran, dan konsistensi mematuhi aturan tanpa kompromi terhadap konflik kepentingan."),
    ("Perusahaan BUMN Anda sedang mengimplementasikan sistem AI untuk mempercepat analisis kredit. Sikap Anda yang mencerminkan Core Value ADAPTIF dan KOMPETEN adalah...",
     [("Menolak sistem baru karena merasa cara manual sudah cukup", 1),
      ("Menunggu sampai diwajibkan ikut pelatihan baru mau mempelajarinya", 3),
      ("Antusias mempelajari sistem AI tersebut secara proaktif dan mencari cara mengoptimalkannya untuk meningkatkan akurasi kerja tim", 5),
      ("Meminta pindah ke bagian yang tidak menggunakan sistem digital", 2),
      ("Hanya menggunakan fitur seadanya tanpa mau tahu lebih dalam", 4)],
     "Nilai ADAPTIF & KOMPETEN ditunjukkan dengan proaktif mempelajari inovasi teknologi baru dan terus meningkatkan kapabilitas diri."),
    ("Divisi Anda membutuhkan data lapangan dari Divisi Operasional untuk menyusun rencana strategis korporat, namun kedua divisi jarang berkomunikasi. Tindakan sesuai nilai KOLABORATIF adalah...",
     [("Menyusun laporan dengan data perkiraan sendiri tanpa berkoordinasi", 1),
      ("Mengeluhkan lambatnya Divisi Operasional kepada direksi", 2),
      ("Menghubungi perwakilan Divisi Operasional secara konstruktif untuk menyelaraskan kebutuhan data dan membangun alur kerja bersama", 5),
      ("Menunggu Divisi Operasional mengirimkan data atas inisiatif mereka sendiri", 3),
      ("Meminta staf magang meminta data tanpa penjelasan konteks", 4)],
     "Nilai KOLABORATIF menekankan sinergi lintas fungsi/divisi dengan komunikasi terbuka untuk mencapai tujuan bersama perusahaan.")
]

for item in akhlak_new:
    new_questions.append(make_weighted(5, 2, item[0], item[1], item[2]))

eng_bumn_new = [
    ("The Board of Directors _____ the annual financial report before the shareholders' meeting begins tomorrow.",
     "will have reviewed", "reviewed", "is review", "have reviewing", "was reviewed",
     "A", "Future Perfect Tense ('will have + V3') digunakan untuk menyatakan tindakan yang akan sudah selesai sebelum waktu/kejadian tertentu di masa depan ('before ... begins tomorrow')."),
    ("Neither the branch manager nor the supervisors _____ aware of the sudden system maintenance last night.",
     "is", "are", "was", "were", "has been",
     "D", "Pada konstruksi 'Neither A nor B', kata kerja mengikuti subjek terdekat (supervisors = jamak) dan keterangan waktu 'last night' (lampau), sehingga menggunakan 'were'."),
    ("Had the logistics team shipped the equipment earlier, the construction project _____ on schedule.",
     "will finish", "would finish", "would have finished", "finished", "has finished",
     "C", "Conditional Sentence Type 3 (inversi: 'Had + S + V3, S + would have + V3') untuk pengandaian masa lampau."),
    ("Choose the word closest in meaning to 'LUCRATIVE' in the context: 'The state-owned enterprise expanded into a lucrative renewable energy market.'",
     "Risky", "Profitable", "Temporary", "Declining", "Expensive",
     "B", "'Lucrative' bermakna menghasilkan banyak keuntungan (profitable)."),
    ("Due to strict regulatory compliance, all employees are required _____ their digital security credentials every ninety days.",
     "update", "updating", "to update", "updated", "updates",
     "C", "Pasif 'are required' diikuti oleh to-infinitive ('to update').")
]

for item in eng_bumn_new:
    new_questions.append(make_mc(6, 2, item[0], item[1], item[2], item[3], item[4], item[5], item[6], item[7]))

# ============================================================================
# 5. TAMBAHAN SOAL KEDINASAN - TPA (Category ID: 7) - 20 Soal Baru
# ============================================================================
kedinasan_new = [
    ("Jika fungsi f(x) = 2x + 3 dan (f o g)(x) = 6x² - 4x + 5, maka nilai dari g(2) adalah...",
     "5", "7", "9", "11", "13",
     "C", "(f o g)(x) = 2.g(x) + 3 = 6x² - 4x + 5 -> 2.g(x) = 6x² - 4x + 2 -> g(x) = 3x² - 2x + 1. Maka g(2) = 3(4) - 2(2) + 1 = 12 - 4 + 1 = 9."),
    ("Akar-akar persamaan kuadrat x² - 6x + 7 = 0 adalah p dan q. Nilai dari p² + q² adalah...",
     "20", "22", "24", "29", "36",
     "B", "Dari x² - 6x + 7 = 0: p + q = 6 dan p.q = 7. Maka p² + q² = (p + q)² - 2pq = 6² - 2(7) = 36 - 14 = 22."),
    ("Dari 7 orang taruna berprestasi akan dipilih 3 orang untuk menduduki jabatan Ketua, Sekretaris, dan Bendahara. Banyaknya susunan pengurus yang mungkin terbentuk adalah...",
     "35", "105", "210", "343", "42",
     "C", "Karena memperhatikan urutan jabatan (Ketua, Sekretaris, Bendahara), gunakan Permutasi P(7,3) = 7 x 6 x 5 = 210 cara."),
    ("Sebuah kotak berisi 5 bola merah dan 3 bola biru. Jika diambil 2 bola sekaligus secara acak, peluang terambilnya keduanya bola merah adalah...",
     "5/14", "10/28", "5/28", "15/56", "3/14",
     "A", "Banyak cara ambil 2 dari 8 bola = C(8,2) = 28. Banyak cara ambil 2 merah dari 5 = C(5,2) = 10. Peluang = 10/28 = 5/14."),
    ("Jumlah deret geometri tak hingga: 18 + 6 + 2 + 2/3 + ... adalah...",
     "24", "26", "27", "30", "36",
     "C", "Suku pertama a = 18, rasio r = 6/18 = 1/3. Jumlah tak hingga S_inf = a / (1 - r) = 18 / (1 - 1/3) = 18 / (2/3) = 27.")
]

for item in kedinasan_new:
    new_questions.append(make_mc(7, 3, item[0], item[1], item[2], item[3], item[4], item[5], item[6], item[7]))

# ============================================================================
# 6. TAMBAHAN SOAL PSIKOTES KERJA (Category ID: 8, 9, 10) - 30 Soal Baru
# ============================================================================
psiko_logika_new = [
    ("Dalam sebuah tes seleksi ODP Bank, skor Andi lebih tinggi daripada Bima. Skor Citra lebih rendah daripada Bima, namun lebih tinggi daripada Doni. Jika skor Eka lebih tinggi daripada Andi, siapakah yang memperoleh skor tertinggi kedua?",
     "Eka", "Andi", "Bima", "Citra", "Doni",
     "B", "Urutan skor dari tertinggi ke terendah: Eka > Andi > Bima > Citra > Doni. Maka peringkat kedua tertinggi adalah Andi."),
    ("Pola Huruf Psikotes: B, E, I, N, T, ...",
     "Y", "Z", "A", "X", "W",
     "C", "Urutan alfabet: B(2), E(5), I(9), N(14), T(20). Selisihnya +3, +4, +5, +6, maka selanjutnya +7: 20 + 7 = 27 (setelah Z=26 kembali ke A=1)."),
    ("Deret Matriks Angka: Jika (4 * 3 = 19), (5 * 2 = 27), dan (6 * 4 = 40), maka nilai dari (7 * 5) adalah...",
     "49", "54", "56", "60", "42",
     "B", "Pola operasi: (a * b) = a² + b. Cek: 4²+3=19, 5²+2=27, 6²+4=40. Maka 7 * 5 = 7² + 5 = 49 + 5 = 54."),
    ("Semua kandidat Management Trainee wajib menguasai analisis data. Sebagian kandidat yang menguasai analisis data mahir berbahasa Mandarin. Pernyataan yang PASTI BENAR adalah...",
     "Semua kandidat MT mahir berbahasa Mandarin", "Tidak ada kandidat MT yang mahir berbahasa Mandarin", "Semua kandidat MT menguasai analisis data, dan sebagian di antaranya mungkin mahir berbahasa Mandarin", "Kandidat yang tidak mahir Mandarin pasti tidak lulus MT", "Semua yang mahir analisis data adalah kandidat MT",
     "C", "Dari premis pertama sudah pasti semua kandidat MT menguasai analisis data, dan beririsan sebagian dengan kemampuan bahasa Mandarin."),
    ("Jika kode kata 'BANKIR' ditulis sebagai 'CBOLJS', maka kata 'KREDIT' dengan pola penyandian yang sama akan ditulis sebagai...",
     "LSFEJU", "LSEFJU", "JQDCHS", "LSFEKV", "MTGFKV",
     "A", "Setiap huruf digeser maju +1 dalam urutan alfabet: K->L, R->S, E->F, D->E, I->J, T->U -> LSFEJU.")
]

for item in psiko_logika_new:
    new_questions.append(make_mc(8, 4, item[0], item[1], item[2], item[3], item[4], item[5], item[6], item[7]))

psiko_spasial_new = [
    ("Sebuah kubus memiliki 6 sisi yang dicat penuh dengan warna merah, lalu dipotong-potong menjadi 27 kubus kecil berukuran sama (3 x 3 x 3). Berapa banyak kubus kecil yang memiliki TEPAT DUA sisi berwarna merah?",
     "6 kubus", "8 kubus", "12 kubus", "1 kubus", "9 kubus",
     "C", "Pada kubus 3x3x3: 3 sisi merah (di pojok) = 8 buah; 2 sisi merah (di tengah rusuk, ada 12 rusuk x 1) = 12 buah; 1 sisi merah (tengah sisi) = 6 buah; 0 sisi merah = 1 buah."),
    ("Sebuah bangun datar berbentuk huruf 'L' tegak diputar 180 derajat searah jarum jam, kemudian dicerminkan terhadap sumbu vertikal (kiri-kanan). Bagaimana posisi kaki mendatar dari huruf 'L' tersebut sekarang?",
     "Di bagian bawah menghadap ke kanan", "Di bagian atas menghadap ke kanan", "Di bagian atas menghadap ke kiri", "Di bagian bawah menghadap ke kiri", "Tetap seperti semula",
     "B", "Huruf L normal memiliki kaki mendatar di bawah menghadap ke kanan. Diputar 180° menjadi di atas menghadap ke kiri. Lalu dicerminkan secara horizontal (kiri-kanan) menjadi di bagian atas menghadap ke kanan."),
    ("Jika jarum jam menunjukkan pukul 03.30 tepat, berapakah besar sudut terkecil yang dibentuk oleh jarum pendek dan jarum panjang?",
     "90 derajat", "75 derajat", "60 derajat", "82,5 derajat", "105 derajat",
     "B", "Pada pukul 03.30, jarum panjang di angka 6 (180°), sedangkan jarum pendek berada tepat di tengah antara angka 3 dan 4 (3,5 x 30° = 105°). Selisih sudut = 180° - 105° = 75°."),
    ("Sebuah jaring-jaring kubus terdiri dari 4 persegi berderet mendatar (nomor 1, 2, 3, 4 dari kiri ke kanan), dengan persegi nomor 5 menempel di atas persegi nomor 2, dan persegi nomor 6 menempel di bawah persegi nomor 3. Jika persegi nomor 2 dijadikan alas, maka persegi nomor berapakah yang menjadi tutup (sisi atas)?",
     "Nomor 1", "Nomor 3", "Nomor 4", "Nomor 5", "Nomor 6",
     "C", "Pada deretan 4 persegi mendatar (1-2-3-4), sisi yang berhadapan selalu berselang satu kotak: nomor 1 berhadapan dengan 3, nomor 2 berhadapan dengan 4, dan nomor 5 berhadapan dengan 6.")
]

for item in psiko_spasial_new:
    new_questions.append(make_mc(9, 4, item[0], item[1], item[2], item[3], item[4], item[5], item[6], item[7]))

psiko_sjt_new = [
    ("Saat Anda baru bergabung selama 2 bulan di sebuah perusahaan, Anda melihat prosedur input data klaim pelanggan memakan waktu dua kali lipat karena adanya duplikasi form manual dan digital. Sikap profesional Anda adalah...",
     [("Diam saja karena Anda masih karyawan baru", 2),
      ("Langsung menghapus form manual tanpa izin supervisor", 1),
      ("Menyusun usulan tertulis singkat beserta simulasi penghematan waktunya, lalu mendiskusikannya dengan supervisor pada sesi evaluasi", 5),
      ("Mengeluhkan prosedur kuno tersebut kepada rekan-rekan baru lainnya", 3),
      ("Menolak mengisi form manual karena merasa buang-buang waktu", 4)],
     "SJT Initiative & Problem Solving: Mengajukan perbaikan berbasis data melalui jalur komunikasi yang tepat kepada atasan bernilai tertinggi (5)."),
    ("Seorang nasabah prioritas datang ke meja Anda dengan nada tinggi karena transaksi transfer bisnisnya tertunda akibat gangguan jaringan pusat. Tindakan pertama yang paling efektif adalah...",
     [("Meminta nasabah memaklumi karena gangguan berasal dari kantor pusat", 2),
      ("Meminta satpam menenangkan nasabah tersebut", 1),
      ("Mendengarkan keluhannya dengan empati, menyampaikan permohonan maaf secara profesional, lalu segera mengecek status transaksi ke tim teknis dan memberikan estimasi waktu penyelesaian berkala", 5),
      ("Menyalahkan bagian IT di depan nasabah agar nasabah tidak marah pada Anda", 3),
      ("Meminta nasabah menunggu di kursi antrean sampai jaringan pulih sendiri", 4)],
     "SJT Customer Orientation: Empati, kepemilikan masalah (ownership), dan tindak lanjut aktif memberikan kepastian kepada nasabah bernilai 5.")
]

for item in psiko_sjt_new:
    new_questions.append(make_weighted(10, 4, item[0], item[1], item[2]))

# ============================================================================
# 7. BATCH EKSTRA SOAL HOTS CPNS, BUMN & PSIKOTES (75 Soal Tambahan)
# ============================================================================
extra_cpns_twk = [
    ("Menurut Pasal 7B UUD 1945, usul pemberhentian Presiden dan/atau Wakil Presiden dapat diajukan oleh DPR kepada MPR hanya dengan terlebih dahulu mengajukan permintaan kepada...",
     "Mahkamah Agung", "Komisi Yudisial", "Mahkamah Konstitusi", "Badan Pemeriksa Keuangan", "Komisi Pemberantasan Korupsi",
     "C", "Sesuai Pasal 7B ayat (1) UUD 1945, DPR harus terlebih dahulu mengajukan permintaan kepada Mahkamah Konstitusi untuk memeriksa, mengadili, dan memutus pendapat DPR bahwa Presiden/Wapres telah melakukan pelanggaran hukum."),
    ("Lembaga negara yang memiliki kewenangan memeriksa pengelolaan dan tanggung jawab tentang keuangan negara menurut Pasal 23E UUD 1945 adalah...",
     "Kementerian Keuangan", "Badan Pemeriksa Keuangan (BPK)", "Badan Pengawasan Keuangan dan Pembangunan (BPKP)", "Komisi Pemberantasan Korupsi (KPK)", "Otoritas Jasa Keuangan (OJK)",
     "B", "Pasal 23E ayat (1) UUD 1945 menyatakan bahwa untuk memeriksa pengelolaan dan tanggung jawab tentang keuangan negara diadakan satu Badan Pemeriksa Keuangan yang bebas dan mandiri."),
    ("Tokoh nasional yang mengusulkan penandatanganan naskah Proklamasi Kemerdekaan cukup dilakukan oleh Ir. Soekarno dan Drs. Moh. Hatta atas nama bangsa Indonesia adalah...",
     "Ahmad Soebardjo", "Sayuti Melik", "Sukarni", "B.M. Diah", "Wikana",
     "C", "Sukarni dari golongan muda mengusulkan agar teks Proklamasi ditandatangani oleh Soekarno-Hatta atas nama bangsa Indonesia."),
    ("Sidang pertama BPUPKI pada tanggal 29 Mei – 1 Juni 1945 membahas agenda utama mengenai...",
     "Rancangan Undang-Undang Dasar", "Rumusan Dasar Negara Indonesia Merdeka", "Pembentukan Tentara Keamanan Rakyat", "Pembagian wilayah provinsi Indonesia", "Pemilihan Presiden dan Wakil Presiden",
     "B", "Sidang pertama BPUPKI (29 Mei - 1 Juni 1945) berfokus merumuskan Dasar Negara, di mana Moh. Yamin, Soepomo, dan Soekarno menyampaikan gagasannya."),
    ("Sikap mengembangkan perbuatan yang luhur, yang mencerminkan sikap dan suasana kekeluargaan dan kegotongroyongan merupakan butir pengamalan Pancasila sila ke...",
     "Satu", "Dua", "Tiga", "Empat", "Lima",
     "E", "Mengembangkan perbuatan luhur yang mencerminkan sikap kekeluargaan dan kegotongroyongan serta menjaga keseimbangan antara hak dan kewajiban adalah butir Sila ke-5 (Keadilan Sosial bagi Seluruh Rakyat Indonesia)."),
    ("Kalimat di bawah ini yang merupakan kalimat efektif sesuai kaidah Bahasa Indonesia yang baik dan benar adalah...",
     "Kepada para peserta ujian dimohon segera memasuki ruangan.", "Para peserta ujian dimohon segera memasuki ruangan.", "Bagi semua peserta ujian diharapkan agar segera masuk ke dalam ruangan.", "Di dalam rapat itu membahas tentang strategi pelayanan publik.", "Meskipun sudah diperingatkan berkali-kali, namun ia tetap terlambat.",
     "B", "Kalimat 'Para peserta ujian dimohon segera memasuki ruangan' memiliki subjek yang jelas (tidak diawali preposisi 'Kepada'/'Bagi') dan bebas dari pemborosan kata (pleonasme)."),
    ("Core Values ASN yang diluncurkan secara resmi oleh Pemerintah sebagai fondasi budaya kerja Aparatur Sipil Negara di seluruh Indonesia adalah...",
     "AKHLAK", "BerAKHLAK", "PRIMA", "PASTI", "INTEGRITAS",
     "B", "Core Values ASN adalah BerAKHLAK: Berorientasi Pelayanan, Akuntabel, Kompeten, Harmonis, Loyal, Adaptif, dan Kolaboratif, dengan employer branding 'Bangga Melayani Bangsa'."),
    ("Seorang ASN selalu menggunakan barang milik negara (kendaraan dinas dan laptop kantor) secara bertanggung jawab, efektif, efisien, dan tidak pernah memakainya untuk keperluan liburan keluarga. Sikap ini mencerminkan nilai dasar BerAKHLAK yaitu...",
     "Harmonis", "Akuntabel", "Adaptif", "Kolaboratif", "Berorientasi Pelayanan",
     "B", "Panduan perilaku Akuntabel meliputi: melaksanakan tugas dengan jujur, bertanggung jawab, cermat, disiplin, serta menggunakan kekayaan dan barang milik negara secara bertanggung jawab, efektif, dan efisien."),
    ("Politik Luar Negeri Indonesia bersifat 'Bebas Aktif'. Makna kata 'Aktif' dalam prinsip tersebut adalah...",
     "Bebas bergabung dengan pakta pertahanan militer mana pun", "Ikut serta secara konstruktif dalam menjaga perdamaian dunia dan menyelesaikan konflik internasional berdasarkan kemerdekaan dan keadilan sosial", "Mengintervensi urusan dalam negeri negara tetangga di kawasan ASEAN", "Menutup diri dari kerja sama ekonomi bilateral", "Memimpin blok kekuatan ekonomi baru di Asia",
     "B", "Aktif berarti Indonesia tidak pasif/netral semata, melainkan giat berusaha mewujudkan ketertiban dan perdamaian dunia sesuai amanat Pembukaan UUD 1945 alinea ke-4."),
    ("Penulisan kata depan 'di' yang benar terdapat pada kalimat...",
     "Surat keputusan tersebut sudah di tanda tangani oleh Kepala Badan.", "Berkas lamaran disimpan didalam lemari arsip utama.", "Pelatihan nasional tersebut diselenggarakan di Balai Diklat Aparatur.", "Setiap laporan wajib diunggah tepat waktu ke portal resmi.", "Pameran inovasi pelayanan publik dibuka disemua provinsi.",
     "C", "Kata depan 'di' yang menunjukkan tempat ditulis terpisah dari kata yang mengikutinya ('di Balai Diklat'), sedangkan awalan pasif 'di-' ditulis serangkai ('ditandatangani', 'diunggah').")
]
for item in extra_cpns_twk:
    new_questions.append(make_mc(1, 1, item[0], item[1], item[2], item[3], item[4], item[5], item[6], item[7]))

extra_cpns_tiu = [
    ("AUDITOR : LAPORAN KEUANGAN = DOKTER : ...",
     "Rumah Sakit", "Stetoskop", "Pasien", "Resep Obat", "Apotek",
     "C", "Objek pemeriksaan: Auditor memeriksa laporan keuangan, sedangkan Dokter memeriksa pasien."),
    ("METEOROLOGI : CUACA = SEISMOLOGI : ...",
     "Gunung Berapi", "Gempa Bumi", "Fosil Purba", "Bintang dan Planet", "Arus Laut",
     "B", "Ilmu dan objek kajiannya: Meteorologi mempelajari cuaca, sedangkan Seismologi mempelajari gempa bumi."),
    ("Deret angka: 5, 11, 23, 47, 95, ...",
     "189", "190", "191", "192", "195",
     "C", "Pola dikali 2 lalu ditambah 1 (atau +6, +12, +24, +48, +96). Maka 95 x 2 + 1 = 191."),
    ("Deret angka: 81, 27, 9, 3, 1, ...",
     "0", "1/2", "1/3", "1/9", "-3",
     "C", "Pola pembagian dengan 3 pada setiap suku berikutnya: 1 dibagi 3 = 1/3."),
    ("Deret kombinasi dua larik: 10, 20, 13, 18, 16, 16, 19, ...",
     "14", "15", "22", "20", "12",
     "A", "Larik ganjil (+3): 10, 13, 16, 19. Larik genap (-2): 20, 18, 16, maka berikutnya 16 - 2 = 14."),
    ("Sebuah koperasi memberikan bunga tunggal sebesar 12% per tahun. Jika seorang anggota menabung Rp 5.000.000 selama 8 bulan, berapakah total tabungan beserta bunganya?",
     "Rp 5.300.000", "Rp 5.400.000", "Rp 5.450.000", "Rp 5.600.000", "Rp 5.200.000",
     "B", "Bunga 8 bulan = (8/12) x 12% x Rp 5.000.000 = 8% x Rp 5.000.000 = Rp 400.000. Total tabungan = Rp 5.400.000."),
    ("Dalam sebuah seleksi terdapat 120 peserta. Sebanyak 75 orang lulus tes tertulis, 60 orang lulus tes wawancara, dan 15 orang tidak lulus keduanya. Berapa banyak peserta yang lulus KEDUA tes tersebut?",
     "25 orang", "30 orang", "35 orang", "40 orang", "20 orang",
     "B", "Rumus himpunan: n(S) = n(A) + n(B) - n(A irisan B) + n(tidak keduanya) -> 120 = 75 + 60 - x + 15 -> 120 = 150 - x -> x = 30 orang."),
    ("Jika a = 7 dan b = 3, maka nilai dari a³ - 3a²b + 3ab² - b³ adalah...",
     "16", "32", "64", "81", "100",
     "C", "Bentuk aljabar a³ - 3a²b + 3ab² - b³ = (a - b)³. Maka (7 - 3)³ = 4³ = 64."),
    ("Dua buah dadu bersisi enam dilempar bersama-sama satu kali. Berapakah peluang munculnya mata dadu berjumlah 9?",
     "1/12", "1/9", "1/6", "5/36", "1/18",
     "B", "Pasangan mata dadu berjumlah 9 adalah: (3,6), (4,5), (5,4), (6,3) = 4 kejadian dari total 36 ruang sampel. Peluang = 4/36 = 1/9."),
    ("Enam kota (P, Q, R, S, T, U) dihubungkan jalur kereta. Kota P hanya terhubung langsung dengan Q. Kota Q terhubung dengan P, R, dan S. Kota S terhubung dengan Q, T, dan U. Jika seseorang ingin pergi dari P ke U, kota mana sajakah yang PASTI dilalui?",
     "Q dan R", "Q dan S", "R dan T", "Q, R, dan S", "S dan T",
     "B", "Dari P hanya bisa ke Q. Untuk mencapai U yang terhubung ke S, harus melalui Q -> S -> U. Jadi kota yang pasti dilalui adalah Q dan S."),
    ("Hasil dari √(144) + √(169) - √(81) adalah...",
     "14", "15", "16", "17", "18",
     "C", "12 + 13 - 9 = 16."),
    ("Sebuah toko pakaian memberikan diskon ganda (berturut-turut) sebesar 50% + 20%. Jika harga label sebuah jaket adalah Rp 300.000, berapakah harga yang harus dibayar pembeli?",
     "Rp 90.000", "Rp 110.000", "Rp 120.000", "Rp 150.000", "Rp 100.000",
     "C", "Diskon pertama 50%: Rp 300.000 x 50% = Rp 150.000. Diskon tambahan 20% dari Rp 150.000 = Rp 30.000. Harga bayar = 150.000 - 30.000 = Rp 120.000.")
]
for item in extra_cpns_tiu:
    new_questions.append(make_mc(2, 1, item[0], item[1], item[2], item[3], item[4], item[5], item[6], item[7]))

extra_cpns_tkp = [
    ("Anda baru saja dimutasi ke unit kerja di daerah terpencil yang memiliki kebiasaan dan adat istiadat yang sangat berbeda dengan daerah asal Anda. Sikap yang Anda tunjukkan adalah...",
     [("Mengurung diri di mess kantor di luar jam kerja karena merasa asing", 1),
      ("Meminta mutasi kembali ke kota asal secepat mungkin", 2),
      ("Mempelajari dan menghormati kearifan lokal setempat serta aktif berbaur secara santun dengan warga dan rekan kerja", 5),
      ("Memaksa warga lokal mengikuti kebiasaan dari kota asal Anda", 3),
      ("Hanya bergaul dengan sesama pegawai pendatang saja", 4)],
     "Aspek Sosial Budaya: Kemampuan beradaptasi dengan cepat, menghargai keberagaman budaya lokal, dan bersikap inklusif bernilai 5."),
    ("Sistem antrean online di kantor pelayanan Anda mendadak mengalami gangguan server sehingga antrean warga menumpuk di lobi. Apa tindakan Anda sebagai petugas layanan?",
     [("Menutup loket sampai server kembali normal", 1),
      ("Membiarkan warga menunggu tanpa memberikan pengumuman apa pun", 2),
      ("Memberikan penjelasan dengan sopan kepada warga, segera berkoordinasi dengan tim IT, dan menerapkan prosedur pencatatan antrean darurat agar pelayanan tetap berjalan tertib", 5),
      ("Ikut panik dan menyalahkan petugas IT di hadapan warga", 3),
      ("Menyuruh semua warga pulang dan mendaftar ulang minggu depan", 4)],
     "Aspek Pelayanan Publik & Profesionalisme: Tanggap darurat, komunikatif, dan tetap menjalankan layanan menggunakan skema kontingensi bernilai 5."),
    ("Atasan memberikan kritik tajam terhadap draf kebijakan yang Anda susun bersama tim karena dinilai kurang dilengkapi data terbaru. Respons Anda adalah...",
     [("Merasa tersinggung dan kehilangan semangat kerja", 1),
      ("Menyalahkan anggota tim yang bertugas mengumpulkan data", 2),
      ("Menerima masukan tersebut secara terbuka sebagai bahan evaluasi, lalu segera memperbarui draf dengan data valid terbaru bersama tim", 5),
      ("Membantah atasan tanpa membawa bukti data pendukung", 3),
      ("Membiarkan draf tersebut tanpa revisi", 4)],
     "Aspek Profesionalisme: Terbuka terhadap umpan balik (feedback), tidak defensif, dan berorientasi pada perbaikan kualitas kerja bernilai 5."),
    ("Seorang kenalan lama menawarkan sejumlah uang tunai agar berkas perizinan usahanya diproses mendahului antrean pemohon lainnya. Sikap Anda...",
     [("Menerima uang tersebut karena dilakukan di luar kantor", 1),
      ("Menerima sebagian saja sebagai tanda terima kasih", 2),
      ("Menolak dengan tegas dan sopan, serta menjelaskan bahwa seluruh permohonan diproses secara adil sesuai urutan dan prosedur resmi tanpa biaya tambahan", 5),
      ("Meminta rekan kerja lain yang menerimanya", 3),
      ("Menjanjikan percepatan tanpa mengambil uangnya namun melanggar antrean", 4)],
     "Aspek Integritas & Pelayanan Publik: Menolak suap/gratifikasi dengan tegas dan menegakkan keadilan prosedur layanan bernilai 5."),
    ("Anda mengetahui adanya seorang pegawai di unit Anda yang sering menyebarkan ujaran kebencian terhadap ideologi Pancasila dan mengajak rekan lain mengikuti kelompok ekstrem. Tindakan Anda...",
     [("Ikut bergabung karena penasaran", 1),
      ("Pura-pura tidak tahu agar hubungan pertemanan tetap aman", 2),
      ("Tetap berpegang teguh pada ideologi Pancasila dan melaporkan temuan tersebut secara berjenjang disertai bukti kepada atasan/saluran pengaduan resmi", 5),
      ("Menegurnya dengan kekerasan fisik di ruang kerja", 3),
      ("Membicarakannya sebagai gosip di kantin kantor", 4)],
     "Aspek Anti-Radikalisme: Menjaga ketahanan ideologi dan menempuh mekanisme pelaporan resmi secara terukur bernilai 5.")
]
for item in extra_cpns_tkp:
    new_questions.append(make_weighted(3, 1, item[0], item[1], item[2]))

extra_bumn_tkd = [
    ("LIKUIDITAS : KAS = SOLVABILITAS : ...",
     "Biaya Iklan", "Seluruh Kewajiban Utang Jangka Panjang & Pendek", "Jumlah Karyawan", "Sewa Gedung", "Harga Eceran",
     "B", "Likuiditas berkaitan dengan kecukupan kas jangka pendek, sedangkan Solvabilitas berkaitan dengan kemampuan perusahaan melunasi seluruh kewajiban/utangnya."),
    ("Deret Angka Tes BUMN: 6, 13, 27, 55, 111, ...",
     "221", "222", "223", "224", "219",
     "C", "Pola dikali 2 lalu ditambah 1 (atau +7, +14, +28, +56, +112). Maka 111 x 2 + 1 = 223."),
    ("Sebuah BUMN logistik mengoperasikan 8 truk yang menghabiskan 320 liter solar untuk menempuh jarak tertentu. Jika perusahaan mengoperasikan 12 truk sejenis untuk jarak yang sama, berapa liter solar yang dibutuhkan?",
     "420 liter", "450 liter", "480 liter", "500 liter", "520 liter",
     "C", "Kebutuhan per truk = 320 / 8 = 40 liter. Untuk 12 truk = 12 x 40 = 480 liter."),
    ("Pada tabel laporan tahunan, laba kotor meningkat dari Rp 80 miliar menjadi Rp 100 miliar. Berapakah persentase kenaikan laba kotor tersebut?",
     "20%", "25%", "30%", "15%", "40%",
     "B", "Kenaikan = 100 - 80 = Rp 20 miliar. Persentase kenaikan = (20 / 80) x 100% = 25%."),
    ("Jika semua pegawai BUMN wajib menjunjung nilai AKHLAK, dan Raka adalah pegawai di salah satu BUMN perbankan, maka kesimpulan yang benar adalah...",
     "Raka tidak wajib menjunjung nilai AKHLAK", "Raka wajib menjunjung nilai AKHLAK", "Hanya direksi yang wajib menjunjung nilai AKHLAK", "Semua yang menjunjung nilai AKHLAK adalah Raka", "Tidak dapat disimpulkan",
     "B", "Silogisme deduktif langsung: Semua pegawai BUMN wajib menjunjung nilai AKHLAK; Raka adalah pegawai BUMN; maka Raka wajib menjunjung nilai AKHLAK."),
    ("Manakah pasangan kata berikut yang memiliki hubungan PALING SERUPA dengan 'INFLASI : DAYA BELI MENURUN'?",
     "Hujan : Kemarau", "Kemarau Panjang : Gagal Panen", "Belajar : Malas", "Lampu : Gelap", "Keuntungan : Rugi",
     "B", "Hubungan Sebab -> Akibat: Inflasi mengakibatkan daya beli menurun, sebagaimana Kemarau Panjang mengakibatkan gagal panen."),
    ("Sebuah mesin cetak otomatis di Peruri dapat mencetak 1.500 lembar dokumen sekuriti dalam 15 menit. Berapa lembar dokumen yang dapat dicetak dalam waktu 1,5 jam?",
     "6.000 lembar", "7.500 lembar", "9.000 lembar", "10.500 lembar", "12.000 lembar",
     "C", "Kecepatan cetak = 1.500 / 15 = 100 lembar/menit. Waktu 1,5 jam = 90 menit. Total = 90 x 100 = 9.000 lembar."),
    ("Deret Huruf BUMN: A, D, H, M, S, ...",
     "Y", "Z", "A", "X", "W",
     "B", "Urutan alfabet: A(1), D(4), H(8), M(13), S(19). Selisih berturut-turut +3, +4, +5, +6, maka selanjutnya +7: 19 + 7 = 26 yaitu huruf Z.")
]
for item in extra_bumn_tkd:
    new_questions.append(make_mc(4, 2, item[0], item[1], item[2], item[3], item[4], item[5], item[6], item[7]))

extra_bumn_akhlak = [
    ("Rekan satu tim Anda mengalami musibah banjir sehingga tidak dapat hadir ke kantor pada hari presentasi proyek. Sikap Anda yang mencerminkan Core Value HARMONIS adalah...",
     [("Memarahinya karena dianggap tidak profesional", 1),
      ("Membiarkan bagian presentasinya kosong tanpa penjelasan", 2),
      ("Menyampaikan simpati serta mengambil alih bagian tugas presentasinya dengan sukarela agar tim tetap sukses dan rekan tersebut dapat fokus menangani darurat keluarganya", 5),
      ("Melaporkannya ke HRD agar gajinya dipotong", 3),
      ("Menunda seluruh presentasi klien secara sepihak", 4)],
     "Nilai HARMONIS diwujudkan dengan sikap peduli, saling menolong sesama rekan kerja, dan menjaga kekompakan tim."),
    ("Perusahaan tempat Anda bekerja sedang menghadapi persaingan ketat dan membutuhkan efisiensi biaya operasional di seluruh lini. Wujud implementasi nilai LOYAL yang tepat adalah...",
     [("Menggunakan kertas dan listrik kantor secara berlebihan karena dibayar perusahaan", 1),
      ("Membocorkan strategi efisiensi perusahaan ke kompetitor", 2),
      ("Mendukung penuh kebijakan penghematan perusahaan dengan mengoptimalkan penggunaan sumber daya kerja secara hemat dan menjaga reputasi perusahaan", 5),
      ("Mengeluhkan kebijakan efisiensi di media sosial pribadi", 3),
      ("Meminta fasilitas mewah di tengah program penghematan", 4)],
     "Nilai LOYAL ditunjukkan dengan dedikasi menjaga nama baik dan mendukung keberlangsungan organisasi secara konstruktif.")
]
for item in extra_bumn_akhlak:
    new_questions.append(make_weighted(5, 2, item[0], item[1], item[2]))

extra_bumn_eng = [
    ("Not only _____ the quarterly revenue target, but the regional branch also achieved the highest customer satisfaction index.",
     "did it exceed", "it exceeded", "exceeded it", "does it exceeded", "was it exceed",
     "A", "Kalimat yang diawali negative/restrictive adverbial 'Not only' di awal klausa wajib menggunakan susunan inversi (auxiliary + subject + verb: 'did it exceed')."),
    ("The Chief Financial Officer suggested that the investment proposal _____ thoroughly before being submitted to the ministry.",
     "is evaluated", "be evaluated", "was evaluated", "evaluates", "evaluating",
     "B", "Subjunctive mood setelah kata kerja 'suggested that' menggunakan bare infinitive ('be evaluated')."),
    ("Despite _____ global supply chain disruptions, the state-owned manufacturing company maintained steady production.",
     "it faced", "facing", "faced", "to face", "faces",
     "B", "Preposisi 'Despite' diikuti oleh noun phrase atau gerund (V-ing: 'facing')."),
    ("Find the synonym of 'MITIGATE' in: 'Risk management protocols are designed to mitigate potential financial losses.'",
     "Increase", "Alleviate / Reduce", "Ignore", "Calculate", "Postpone",
     "B", "'Mitigate' berarti mengurangi, meredakan, atau memperkecil dampak risiko (alleviate / reduce).")
]
for item in extra_bumn_eng:
    new_questions.append(make_mc(6, 2, item[0], item[1], item[2], item[3], item[4], item[5], item[6], item[7]))

extra_psikotes = [
    ("Lima kandidat (K, L, M, N, O) mengikuti wawancara berurutan satu per satu. M diwawancarai sebelum K, tetapi sesudah O. L diwawancarai tepat sesudah K. N diwawancarai paling akhir. Siapakah yang diwawancarai PERTAMA kali?",
     "K", "L", "M", "N", "O",
     "E", "Dari petunjuk: O -> M -> K -> L, dan N paling akhir. Maka urutan lengkapnya: O - M - K - L - N. Yang pertama adalah O."),
    ("Tes Ketelitian Kode: Manakah di antara pasangan kode berikut yang SAMA PERSIS?",
     "ST-2026-98412-BKN  vs  ST-2026-98142-BKN", "RBB-FHCI-77409-ID  vs  RBB-FHCI-77409-ID", "CAT-STAN-55231-RI  vs  CAT-STAN-55237-RI", "PSIKO-88390-VIP  vs  PSIKO-88309-VIP", "ASN-44910-PRO  vs  ASN-44910-PRQ",
     "B", "Pasangan B ('RBB-FHCI-77409-ID') identik 100% tanpa perbedaan karakter maupun angka."),
    ("Deret Angka Psikotes Perbankan: 1, 4, 9, 16, 25, 36, ...",
     "42", "45", "48", "49", "64",
     "D", "Deret bilangan kuadrat sempurna: 1², 2², 3², 4², 5², 6², maka suku ke-7 adalah 7² = 49."),
    ("Jika sebuah dadu standar memiliki aturan bahwa jumlah titik pada dua sisi yang berhadapan selalu sama dengan 7, maka jika sisi angka 2 berada di bagian atas, angka berapakah yang berada di bagian alas (bawah)?",
     "3", "4", "5", "6", "1",
     "C", "Karena jumlah sisi berhadapan = 7, maka sisi yang berhadapan dengan 2 adalah 7 - 2 = 5."),
    ("Sebuah segitiga siku-siku memiliki panjang alas 9 cm dan tinggi 12 cm. jika segitiga tersebut diputar mengelilingi sisi tingginya sejauh 360 derajat, bangun ruang apakah yang terbentuk dan berapa panjang garis pelukisnya?",
     "Tabung dengan jari-jari 9 cm", "Kerucut dengan garis pelukis 15 cm", "Bola dengan diameter 15 cm", "Kerucut dengan garis pelukis 13 cm", "Limas segitiga dengan sisi miring 15 cm",
     "B", "Segitiga siku-siku yang diputar 360° terhadap salah satu sisi tegaknya membentuk bangun ruang Kerucut, dengan jari-jari r=9 cm, tinggi t=12 cm, dan garis pelukis s = √(9² + 12²) = √225 = 15 cm."),
    ("Anda adalah seorang Team Leader. Salah satu anggota tim Anda melakukan kesalahan input laporan yang baru diketahui 1 jam sebelum rapat evaluasi bersama klien. Apa yang Anda lakukan?",
     [("Memarahi anggota tersebut habis-habisan di depan tim selama 30 menit", 1),
      ("Menyembunyikan kesalahan tersebut dan berharap klien tidak menyadarinya", 2),
      ("Segera fokus memimpin perbaikan data secara cepat bersama anggota tersebut, memvalidasi ulang angka yang krusial sebelum rapat dimulai, lalu melakukan evaluasi penyebab kesalahan seusai rapat", 5),
      ("Membatalkan rapat dengan klien secara mendadak tanpa alasan jelas", 3),
      ("Menyalahkan anggota tersebut secara terbuka di hadapan klien", 4)],
     "SJT Leadership & Crisis Management: Fokus utama saat krisis adalah memperbaiki masalah tepat waktu (problem solving), menjaga kredibilitas tim di depan klien, dan melakukan pembinaan setelahnya (poin 5).")
]
for item in extra_psikotes[:3]:
    new_questions.append(make_mc(8, 4, item[0], item[1], item[2], item[3], item[4], item[5], item[6], item[7]))
for item in extra_psikotes[3:5]:
    new_questions.append(make_mc(9, 4, item[0], item[1], item[2], item[3], item[4], item[5], item[6], item[7]))
new_questions.append(make_weighted(10, 4, extra_psikotes[5][0], extra_psikotes[5][1], extra_psikotes[5][2]))

# Combine base 255 + new questions
data['questions'] = base_questions + new_questions

with open('database/all_questions.json', 'w', encoding='utf-8') as f:
    json.dump(data, f, ensure_ascii=False, indent=2)

print(f"Successfully updated all_questions.json! Total questions now: {len(data['questions'])}")
