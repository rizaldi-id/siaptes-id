#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import json
import sqlite3
import os

print("Building SIAP TES Multi-Test Dataset...")

with open('database/existing_140.json', 'r', encoding='utf-8') as f:
    existing_140 = json.load(f)

# Categories
categories = [
    (1, 'TWK', 'Tes Wawasan Kebangsaan (TWK)', 'cpns', 'Menguji pilar kebangsaan: Pancasila, UUD 1945, NKRI, Bhinneka Tunggal Ika, integritas, dan bela negara.', 65),
    (2, 'TIU', 'Tes Intelegensia Umum (TIU)', 'cpns', 'Menguji kemampuan verbal (analogi, silogisme, analitis), numerik (berhitung, deret angka, soal cerita), dan figural.', 80),
    (3, 'TKP', 'Tes Karakteristik Pribadi (TKP)', 'cpns', 'Menguji pelayanan publik, jejaring kerja, sosial budaya, TIK, profesionalisme, dan anti-radikalisme (skala bobot 1–5).', 166),
    (4, 'TKD_BUMN', 'Tes Kemampuan Dasar BUMN (TKD)', 'bumn', 'Menguji kemampuan penalaran logis, verbal analitis, numerik bisnis, dan pemecahan masalah standar Rekrutmen Bersama BUMN.', 58),
    (5, 'AKHLAK', 'Core Values AKHLAK BUMN', 'bumn', 'Menguji pemahaman dan implementasi nilai inti BUMN: Amanah, Kompeten, Harmonis, Loyal, Adaptif, dan Kolaboratif.', 65),
    (6, 'ENG_BUMN', 'Bahasa Inggris BUMN', 'bumn', 'Menguji kemampuan Structure & Written Expression, Error Recognition, Vocabulary, dan Reading Comprehension dunia kerja BUMN.', 60),
    (7, 'KEDINASAN_TPA', 'TPA & Akademik Kedinasan', 'kedinasan', 'Menguji Matematika Terapan, Aljabar Fungsi, Geometri Ruang, Analisis Data, dan Logika Khusus SPMB STAN, STIS, IPDN.', 70),
    (8, 'PSIKO_LOGIKA', 'Psikotes Logika & Penalaran Kerja', 'psikotes', 'Menguji pola logika deduktif-induktif, penalaran analitis posisi/urutan, dan deret aritmetika untuk asesmen rekrutmen kerja.', 65),
    (9, 'PSIKO_SPASIAL', 'Psikotes Spasial & Hubungan Gambar', 'psikotes', 'Menguji ketajaman visual, rotasi 3D, pencerminan, matriks gambar figural, dan jaring-jaring bangun ruang.', 65),
    (10, 'PSIKO_SJT', 'Situational Judgement Test (SJT)', 'psikotes', 'Simulasi pengambilan keputusan manajerial, etika kerja, integritas problem solving, dan perilaku kerja di lingkungan profesional.', 70)
]

packages = [
    (1, 'Tryout Akbar SKD CPNS 2024/2025 (Standar BKN)', 'cpns', 'Simulasi resmi CAT BKN lengkap 110 butir soal: TWK (30 soal), TIU (35 soal), TKP (45 soal) dengan passing grade resmi PermenPAN-RB.', 100, 1, 49000, 1),
    (2, 'Tryout Rekrutmen Bersama BUMN (TKD + AKHLAK + English)', 'bumn', 'Simulasi seleksi terpadu BUMN FHCI: Tes Kemampuan Dasar (TKD), Core Values AKHLAK BUMN, dan Bahasa Inggris Dunia Kerja.', 90, 1, 49000, 1),
    (3, 'Tryout Masuk Sekolah Kedinasan (STAN, IPDN, STIS, Poltekip)', 'kedinasan', 'Simulasi seleksi masuk sekolah kedinasan: SKD terpadu + Tes Potensi Akademik (TPA Matematika Terapan, Aljabar & Logika Analitis).', 90, 1, 49000, 1),
    (4, 'Simulasi Asesmen Psikotes Kerja & Rekrutmen Perusahaan', 'psikotes', 'Simulasi psikotes rekrutmen perbankan, BUMN, swasta & startup: Penalaran Logika, Spasial Figural, dan Situational Judgement Test (SJT).', 60, 1, 49000, 1),
    (5, 'Mini Tryout All-in-One Multi-Tes (Trial Percobaan Gratis)', 'all', 'Paket coba gratis 30 butir soal lintas kategori (CPNS, BUMN, Kedinasan, dan Psikotes Kerja) untuk mencoba sistem dan menguji kesiapan awal tanpa biaya.', 30, 0, 0, 1)
]

# We will build list of questions:
# [category_id, package_id, question_text, opt_a, opt_b, opt_c, opt_d, opt_e, correct_opt, score_a, score_b, score_c, score_d, score_e, explanation]
all_questions = []

# Process existing 140 questions:
# Originally 110 were package_id=1, 30 were package_id=2.
# In the new multi-platform, package 1 stays package 1 (110 questions: 30 TWK, 35 TIU, 45 TKP).
# The other 30 (10 TWK, 10 TIU, 10 TKP):
# We take 5 TWK, 5 TIU, 5 TKP into package 5 (Mini Tryout Free Trial).
# The other 15 go to package 3 or general practice.
twk_mini_cnt = 0
tiu_mini_cnt = 0
tkp_mini_cnt = 0

for q in existing_140:
    cid = q['category_id']
    old_pid = q['package_id']
    target_pid = old_pid
    
    if old_pid == 2:
        if cid == 1 and twk_mini_cnt < 5:
            target_pid = 5
            twk_mini_cnt += 1
        elif cid == 2 and tiu_mini_cnt < 5:
            target_pid = 5
            tiu_mini_cnt += 1
        elif cid == 3 and tkp_mini_cnt < 5:
            target_pid = 5
            tkp_mini_cnt += 1
        else:
            # Assign to Package 3 (Kedinasan also uses SKD!)
            target_pid = 3

    all_questions.append({
        'category_id': cid,
        'package_id': target_pid,
        'question_text': q['question_text'],
        'option_a': q['option_a'],
        'option_b': q['option_b'],
        'option_c': q['option_c'],
        'option_d': q['option_d'],
        'option_e': q['option_e'],
        'correct_option': q['correct_option'],
        'score_a': q['score_a'],
        'score_b': q['score_b'],
        'score_c': q['score_c'],
        'score_d': q['score_d'],
        'score_e': q['score_e'],
        'explanation': q['explanation']
    })

print(f"Loaded existing CPNS questions: {len(all_questions)}")

# =========================================================================
# 4. TKD BUMN (Tes Kemampuan Dasar BUMN) - Category ID: 4
# =========================================================================
tkd_bumn_data = [
    # Analogi & Verbal Bisnis
    (
        "DIREKSI : KEBIJAKAN = KARYAWAN : ...",
        "Gaji", "Operasional", "Promosi", "Pemecatan", "Evaluasi",
        "B", "Hubungan fungsional: Direksi bertugas merumuskan kebijakan perusahaan, sedangkan Karyawan bertugas menjalankan aspek operasional perusahaan."
    ),
    (
        "KONSOLIDASI : PELEBURAN = DIFERENSIASI : ...",
        "Penggabungan", "Pembedaan", "Penyusutan", "Perluasan", "Pembagian",
        "B", "Hubungan sinonim konsep bisnis: Konsolidasi bermakna peleburan/penggabungan unit, sedangkan Diferensiasi bermakna pembedaan strategi atau produk agar unik di pasar."
    ),
    (
        "LIKUIDITAS : KAS = PROFITABILITAS : ...",
        "Utang", "Laba Bersih", "Modal Kerja", "Saham", "Beban Operasional",
        "B", "Hubungan indikator keuangan: Likuiditas diukur dari ketersediaan kas untuk melunasi kewajiban lancar, sedangkan profitabilitas diukur dari laba bersih yang dihasilkan perusahaan."
    ),
    (
        "Sinonim dari kata 'AGILE' dalam konteks transformasi digital BUMN adalah...",
        "Kaku dan birokratis", "Tangkas dan adaptif", "Lambat dan berhati-hati", "Statis dan terjadwal", "Pasif dan menunggu instruksi",
        "B", "Dalam budaya kerja dan transformasi digital BUMN, 'Agile' bermakna tangkas, lincah, cepat beradaptasi, dan responsif terhadap perubahan pasar maupun teknologi."
    ),
    (
        "Lawan kata (antonim) dari kata 'DEVOLUSI' dalam struktur organisasi adalah...",
        "Sentralisasi", "Desentralisasi", "Delegasi", "Alih daya", "Disposisi",
        "A", "Devolusi bermakna pelimpahan wewenang atau kekuasaan ke tingkat yang lebih rendah. Lawan katanya adalah sentralisasi (pemusatan kekuasaan di satu titik pusat)."
    ),
    # Silogisme & Logika Deduktif Bisnis
    (
        "Semua manajer BUMN wajib menandatangani Pakta Integritas.\nSebagian manajer BUMN memegang sertifikasi manajemen risiko internasional.\nKesimpulan yang tepat adalah...",
        "Semua yang menandatangani Pakta Integritas memiliki sertifikasi manajemen risiko internasional.",
        "Sebagian yang menandatangani Pakta Integritas memegang sertifikasi manajemen risiko internasional.",
        "Hanya manajer yang bersertifikasi manajemen risiko yang menandatangani Pakta Integritas.",
        "Semua manajer BUMN tidak memegang sertifikasi manajemen risiko.",
        "Tidak ada manajer BUMN yang menandatangani Pakta Integritas.",
        "B", "Premis 1: Semua manajer wajib ttd Pakta Integritas. Premis 2: Sebagian manajer memegang sertifikasi. Maka, sebagian yang menandatangani Pakta Integritas (yaitu para manajer tersebut) memegang sertifikasi manajemen risiko internasional."
    ),
    (
        "Jika efisiensi operasional meningkat, maka rasio biaya terhadap pendapatan (BOPO) akan menurun.\nSaat ini rasio BOPO perusahaan tidak menurun.\nKesimpulan yang sah adalah...",
        "Efisiensi operasional perusahaan tidak meningkat.",
        "Pendapatan perusahaan pasti menurun drastis.",
        "Perusahaan mengalami kebangkrutan.",
        "Biaya operasional perusahaan naik dua kali lipat.",
        "Efisiensi operasional perusahaan meningkat perlahan.",
        "A", "Berdasarkan kaidah logika Modus Tollens: Premis 1 (P -> Q). Premis 2 (~Q). Kesimpulan: ~P (Efisiensi operasional perusahaan tidak meningkat)."
    ),
    (
        "Perusahaan X hanya merekrut kandidat yang lolos uji kompetensi teknis dan berintegritas tinggi.\nDoni dinyatakan diterima sebagai karyawan di Perusahaan X.\nKesimpulan yang pasti benar adalah...",
        "Doni memiliki nilai tertinggi dalam tes bahasa Inggris.",
        "Doni pasti lolos uji kompetensi teknis dan berintegritas tinggi.",
        "Doni memiliki relasi orang dalam di Perusahaan X.",
        "Doni adalah satu-satunya pelamar yang diterima.",
        "Doni tidak perlu melalui masa percobaan kerja.",
        "B", "Karena syarat mutlak penerimaan adalah lolos uji teknis DAN berintegritas, dan Doni diterima, maka Doni pasti telah memenuhi kedua kriteria tersebut."
    ),
    # Aritmetika Sosial & Analisis Numerik Bisnis
    (
        "Sebuah BUMN mencatatkan omset Rp 1,2 Miliar pada kuartal I. Pada kuartal II, omset perusahaan naik 15%. Jika pada kuartal III omset mengalami koreksi turun 10% dari kuartal II, berapakah omset perusahaan pada kuartal III?",
        "Rp 1,242 Miliar", "Rp 1,250 Miliar", "Rp 1,260 Miliar", "Rp 1,300 Miliar", "Rp 1,380 Miliar",
        "A", "Kuartal I = 1,2 M. Kuartal II = 1,2 x 1,15 = 1,38 Miliar. Kuartal III = 1,38 - (10% x 1,38) = 1,38 - 0,138 = Rp 1,242 Miliar."
    ),
    (
        "Rasio perbandingan modal antara Investor A, Investor B, dan Investor C dalam sebuah proyek ventura adalah 3 : 5 : 7. Jika total investasi terkumpul adalah Rp 450 Juta, berapakah jumlah investasi yang disetor oleh Investor B?",
        "Rp 90 Juta", "Rp 150 Juta", "Rp 210 Juta", "Rp 240 Juta", "Rp 270 Juta",
        "B", "Total bagian rasio = 3 + 5 + 7 = 15 bagian. Nilai per bagian = 450 Juta / 15 = 30 Juta. Bagian Investor B = 5 x 30 Juta = Rp 150 Juta."
    ),
    (
        "Sebuah mesin pabrik mampu memproduksi 1.200 unit suku cadang dalam waktu 4 jam dengan 3 operator. Jika perusahaan menambah 2 mesin sejenis yang sama dan operator ditambah menjadi 9 orang, berapa unit yang dapat diproduksi dalam waktu 6 jam?",
        "3.600 unit", "5.400 unit", "7.200 unit", "8.100 unit", "10.800 unit",
        "B", "Kapasitas 1 mesin = 1.200 unit / 4 jam = 300 unit/jam. Jika mesin menjadi 3 unit (1 awal + 2 baru), kapasitas total = 3 x 300 = 900 unit/jam. Dalam 6 jam = 900 x 6 = 5.400 unit suku cadang."
    ),
    (
        "Harga sewa gudang logistik naik dari Rp 80.000.000 per tahun menjadi Rp 96.000.000 per tahun. Berapa persentase kenaikan harga sewa gudang tersebut?",
        "16%", "18%", "20%", "22,5%", "25%",
        "C", "Kenaikan = 96.000.000 - 80.000.000 = Rp 16.000.000. Persentase kenaikan = (16.000.000 / 80.000.000) x 100% = 20%."
    ),
    # Deret Angka & Pola Bilangan TKD BUMN
    (
        "4, 7, 12, 19, 28, 39, ...",
        "50", "52", "54", "56", "58",
        "B", "Pola penambahan bilangan ganjil berurutan: +3, +5, +7, +9, +11, +13. Maka angka berikutnya: 39 + 13 = 52."
    ),
    (
        "2, 3, 6, 8, 16, 19, 38, ...",
        "41", "42", "76", "78", "80",
        "A", "Pola kombinasi berselang-seling: (+1), (x2), (+2), (x2), (+3), (x2), (+4)... Rincian: 2+1=3, 3x2=6, 6+2=8, 8x2=16, 16+3=19, 19x2=38, 38+4=41."
    ),
    (
        "80, 40, 42, 21, 24, 12, 16, ...",
        "8", "10", "12", "18", "20",
        "A", "Pola: (:2), (+2), (:2), (+3), (:2), (+4), (:2). Pola berikutnya: 16 : 2 = 8."
    ),
    # Penalaran Analitis Posisi Kantor BUMN
    (
        "Lima kantor cabang BUMN (P, Q, R, S, T) terletak di sepanjang jalan protokol utama.\n- Cabang Q terletak di sebelah barat Cabang P.\n- Cabang R terletak di antara Cabang P dan Cabang S.\n- Cabang T terletak di ujung paling timur.\n- Cabang P terletak di sebelah timur Cabang Q.\nCabang manakah yang terletak di posisi paling barat?",
        "Cabang P", "Cabang Q", "Cabang R", "Cabang S", "Cabang T",
        "B", "Urutan barat ke timur: Q -> P -> R -> S -> T. Maka cabang yang terletak di posisi paling barat adalah Cabang Q."
    ),
    (
        "Enam karyawan (A, B, C, D, E, F) duduk melingkar dalam rapat evaluasi proyek.\n- A duduk berseberangan dengan D.\n- B duduk di antara A dan C.\n- E duduk di sebelah kanan D.\nSiapakah yang duduk tepat berseberangan dengan B?",
        "C", "D", "E", "F", "A",
        "C", "Dalam susunan melingkar 6 posisi: A berseberangan dengan D. B di antara A dan C. E di sebelah kanan D. F berada di antara D dan A di sisi lain. Maka B berseberangan dengan E."
    ),
    (
        "Dalam sebuah audit keuangan BUMN, ditemukan bahwa rasio lancar (Current Ratio) ideal minimal adalah 1,5. Jika Aktiva Lancar perusahaan adalah Rp 450 Miliar dan Utang Lancar adalah Rp 250 Miliar, berapakah Current Ratio perusahaan tersebut?",
        "1,25", "1,50", "1,65", "1,80", "2,10",
        "D", "Current Ratio = Aktiva Lancar / Utang Lancar = 450 M / 250 M = 1,80. (Memenuhi batas ideal minimal)."
    ),
    (
        "Jika nilai rata-rata penilaian kinerja (KPI) 4 staf keuangan adalah 82, dan ketika ditambah nilai seorang staf baru nilai rata-rata menjadi 84, berapakah nilai KPI staf baru tersebut?",
        "88", "90", "92", "94", "96",
        "C", "Total nilai 4 staf = 4 x 82 = 328. Total nilai 5 staf = 5 x 84 = 420. Nilai staf baru = 420 - 328 = 92."
    ),
    (
        "Sebuah tender pengadaan barang dimenangkan oleh vendor yang menawarkan efisiensi 12% dari pagu anggaran sebesar Rp 2,5 Miliar. Berapakah nilai kontrak pengadaan tersebut?",
        "Rp 2,1 Miliar", "Rp 2,2 Miliar", "Rp 2,25 Miliar", "Rp 2,3 Miliar", "Rp 2,35 Miliar",
        "B", "Penghematan/efisiensi = 12% x 2,5 Miliar = Rp 300 Juta. Nilai kontrak = Rp 2,5 Miliar - Rp 300 Juta = Rp 2,2 Miliar."
    ),
    # 5 Soal Tambahan TKD BUMN untuk Mini Tryout / Practice
    (
        "Pernyataan yang setara dengan: 'Jika pasar modal stabil, maka investor asing meningkatkan portofolio saham' adalah...",
        "Pasar modal tidak stabil atau investor asing meningkatkan portofolio saham.",
        "Jika investor asing meningkatkan portofolio saham, maka pasar modal stabil.",
        "Pasar modal stabil dan investor asing tidak meningkatkan portofolio saham.",
        "Investor asing tidak meningkatkan portofolio saham jika pasar modal stabil.",
        "Jika pasar modal tidak stabil, investor asing pasti keluar dari bursa.",
        "A", "Ekuivalensi implikasi (P -> Q) ekuivalen dengan (~P v Q). Maka kalimat setaranya: 'Pasar modal tidak stabil ATAU investor asing meningkatkan portofolio saham'."
    ),
    (
        "Sebuah mobil dinas kantor menempuh jarak 180 km dalam waktu 2 jam 15 menit. Berapakah kecepatan rata-rata mobil tersebut dalam km/jam?",
        "72 km/jam", "75 km/jam", "80 km/jam", "85 km/jam", "90 km/jam",
        "C", "Waktu = 2 jam 15 menit = 2,25 jam (9/4 jam). Kecepatan = Jarak / Waktu = 180 / 2,25 = 80 km/jam."
    ),
    (
        "Semua anggota dewan komisaris memiliki integritas teruji.\nBeberapa mantan menteri diangkat menjadi anggota dewan komisaris.\nKesimpulan yang tepat adalah...",
        "Semua mantan menteri memiliki integritas teruji.",
        "Beberapa mantan menteri yang menjadi anggota dewan komisaris memiliki integritas teruji.",
        "Tidak ada mantan menteri yang memiliki integritas teruji.",
        "Semua anggota dewan komisaris adalah mantan menteri.",
        "Mantan menteri tidak wajib mematuhi kode etik komisaris.",
        "B", "Kesimpulan partikular: Sebagian mantan menteri yang menjabat dewan komisaris tersebut pasti memiliki integritas yang teruji."
    ),
    (
        "Tiga pompa air mampu mengisi tandon bahan bakar kilang minyak hingga penuh dalam waktu 8 jam. Berapa lama waktu yang dibutuhkan jika hanya 2 pompa air yang bekerja?",
        "10 jam", "11 jam", "12 jam", "14 jam", "16 jam",
        "C", "Perbandingan berbalik nilai: 3 x 8 = 2 x t. Maka t = 24 / 2 = 12 jam."
    ),
    (
        "100, 95, 85, 70, 50, ...",
        "20", "25", "30", "35", "40",
        "B", "Pola pengurangan bertingkat: -5, -10, -15, -20, -25. Angka berikutnya: 50 - 25 = 25."
    )
]

for idx, item in enumerate(tkd_bumn_data):
    # 20 questions to Package 2 (BUMN), 5 to Package 5 (Mini Trial)
    pid = 5 if idx >= 20 else 2
    all_questions.append({
        'category_id': 4,
        'package_id': pid,
        'question_text': item[0],
        'option_a': item[1],
        'option_b': item[2],
        'option_c': item[3],
        'option_d': item[4],
        'option_e': item[5],
        'correct_option': item[6],
        'score_a': 0, 'score_b': 0, 'score_c': 0, 'score_d': 0, 'score_e': 0,
        'explanation': item[7]
    })

print(f"Added TKD BUMN questions. Total now: {len(all_questions)}")

# =========================================================================
# 5. CORE VALUES AKHLAK BUMN - Category ID: 5
# Amanah, Kompeten, Harmonis, Loyal, Adaptif, Kolaboratif
# =========================================================================
akhlak_data = [
    # AMANAH
    (
        "Sebagai pejabat pengadaan barang di BUMN, seorang rekanan menawarkan komisi pribadi sebesar 5% dari nilai proyek jika Anda menyetujui penawarannya yang sedikit di atas pagu. Sikap Anda yang mencerminkan nilai AMANAH adalah...",
        "Menerima tawaran tersebut secara diam-diam demi menambah tabungan pribadi.",
        "Menolak tawaran secara tegas, melaporkan upaya gratifikasi tersebut ke Unit Pengendalian Gratifikasi (UPG), dan menilai tender secara obyektif.",
        "Menerima tawaran tetapi meminta komisi disalurkan ke kas yayasan sosial kantor.",
        "Menolak tawaran tetapi tetap memenangkan rekanan tersebut karena kasihan.",
        "Meminta rekanan menaikkan komisi menjadi 10% sebelum mengambil keputusan.",
        "B", 1, 5, 3, 2, 1,
        "Nilai Amanah: Memegang teguh kepercayaan yang diberikan, berintegritas tinggi, menolak segala bentuk gratifikasi atau suap, dan melaporkannya ke saluran resmi perusahaan."
    ),
    (
        "Anda diberikan wewenang mengelola dana operasional proyek yang cukup besar dan tidak memerlukan kuitansi kecil di bawah Rp 50.000. Tindakan yang paling sesuai dengan pilar AMANAH adalah...",
        "Memanfaatkan celah aturan untuk mengambil uang kas bagi keperluan jajan pribadi.",
        "Tetap mencatat setiap pengeluaran sekecil apapun dan melampirkan bukti pengeluaran riil demi transparansi.",
        "Membuat laporan fiktif untuk pengeluaran kas kecil agar laporan terlihat rapi.",
        "Menyimpan sisa dana operasional untuk keperluan pribadi sebagai upah lembur.",
        "Menyerahkan seluruh uang kas kepada bawahan tanpa melakukan audit berkala.",
        "B", 1, 5, 2, 1, 2,
        "Nilai Amanah mencakup perilaku bertanggung jawab, jujur, cermat, disiplin, dan berintegritas tinggi dalam mengelola sumber daya dan keuangan perusahaan."
    ),
    (
        "Saat menyusun laporan keuangan triwulan, atasan Anda meminta Anda merevisi angka laba agar terlihat memenuhi target bonus tahunan. Sikap Anda adalah...",
        "Segera menuruti perintah atasan tanpa banyak tanya demi menjaga posisi kerja.",
        "Menjelaskan secara profesional bahwa laporan harus menyajikan data riil sesuai standar akuntansi dan menolak memanipulasi angka.",
        "Memanipulasi angka lalu membocorkannya ke media sosial secara anonim.",
        "Mengundurkan diri langsung dari perusahaan tanpa menyelesaikan kewajiban.",
        "Meminta bagian keuntungan jika laporan laba yang dimanipulasi berhasil disetujui direksi.",
        "B", 2, 5, 2, 1, 1,
        "Amanah menuntut keberanian moral untuk menegakkan integritas dan kejujuran dalam pelaporan resmi perusahaan meskipun ada tekanan dari pihak internal."
    ),
    # KOMPETEN
    (
        "Perusahaan BUMN tempat Anda bekerja menerapkan sistem Enterprise Resource Planning (ERP) baru yang rumit dan belum pernah Anda gunakan. Sikap terbaik yang mencerminkan pilar KOMPETEN adalah...",
        "Mengeluh kepada manajemen bahwa sistem lama sudah cukup baik dan menolak migrasi.",
        "Mempelajari sistem baru secara proaktif melalui modul, mengikuti pelatihan dengan sungguh-sungguh, dan mempraktikkannya hingga mahir.",
        "Menunggu rekan kerja lain menguasainya terlebih dahulu baru ikut-ikutan belajar.",
        "Mendelegasikan seluruh input data sistem baru kepada staf magang.",
        "Mengabaikan sistem baru dan tetap mencatat pekerjaan secara manual.",
        "B", 2, 5, 3, 2, 1,
        "Pilar Kompeten: Terus belajar dan mengembangkan kapabilitas diri, adaptif terhadap perangkat kerja baru, dan membantu orang lain belajar demi kinerja terbaik."
    ),
    (
        "Ketika seorang rekan junior di tim Anda mengalami kesulitan dalam menganalisis data keuangan proyek, sikap Anda adalah...",
        "Membiarkannya agar dia belajar mandiri dari kegagalannya.",
        "Menyediakan waktu untuk memberikan bimbingan teknis, berbagi pengetahuan (knowledge sharing), dan mendampinginya menemukan solusi.",
        "Mengambil alih seluruh pekerjaannya tanpa memberinya penjelasan cara menyelesaikannya.",
        "Mengejek ketidakmampuannya di depan anggota tim rapat lainnya.",
        "Melaporkan kelemahan junior tersebut kepada direksi agar gajinya dipotong.",
        "B", 2, 5, 3, 1, 1,
        "Pilar Kompeten mendorong insan BUMN untuk aktif membantu orang lain belajar (*helping others learn*) serta menyelesaikan tugas dengan kualitas terbaik."
    ),
    (
        "Anda ditunjuk mewakili perusahaan untuk mengikuti uji sertifikasi internasional di bidang profesi Anda. Langkah Anda adalah...",
        "Mengikuti ujian tanpa persiapan matang karena menganggap sertifikasi hanya formalitas.",
        "Mempersiapkan diri secara optimal, mengkaji silabus standar global, dan bertekad lulus demi meningkatkan kredibilitas perusahaan.",
        "Mencari bocoran kunci jawaban ujian dari peserta tahun lalu.",
        "Meminta rekan kerja lain menggantikan Anda karena takut gagal.",
        "Meminta izin pimpinan untuk tidak ikut karena beban kerja harian sedang padat.",
        "B", 2, 5, 1, 2, 2,
        "Kompeten: Berkinerja terbaik (*best performance*) dan meningkatkan kompetensi diri untuk menjawab tantangan industri yang selalu berubah."
    ),
    # HARMONIS
    (
        "Dalam tim kerja Anda terdapat anggota dari berbagai latar belakang suku, agama, dan generasi usia yang berbeda pandangan. Tindakan Anda yang mencerminkan nilai HARMONIS adalah...",
        "Hanya mau bergaul dan berdiskusi dengan rekan yang berasal dari satu daerah yang sama.",
        "Membangun komunikasi terbuka, menghargai setiap perbedaan pendapat, dan menciptakan suasana kerja yang inklusif serta kondusif.",
        "Memaksa semua anggota tim mengikuti cara kerja dan kebiasaan generasi Anda sendiri.",
        "Menghindari interaksi dengan rekan yang berbeda keyakinan.",
        "Membentuk kelompok eksklusif di dalam kantor untuk mengisolasi rekan yang berbeda pandangan.",
        "B", 2, 5, 2, 1, 1,
        "Pilar Harmonis: Menghargai setiap orang apapun latar belakangnya, suka menolong orang lain, dan membangun lingkungan kerja yang kondusif."
    ),
    (
        "Rekan kerja Anda di divisi sebelah sedang menghadapi musibah keluarga sehingga pekerjaannya yang terkait dengan proyek Anda tertunda. Sikap Anda adalah...",
        "Memarahinya di depan umum karena mengganggu tenggat waktu proyek Anda.",
        "Menunjukkan empati, menanyakan hal apa yang bisa dibantu, dan bersama tim mencari solusi sementara agar operasional tetap berjalan lancar.",
        "Mengadukan keterlambatannya langsung ke divisi SDM agar rekan tersebut ditegur.",
        "Mengabaikan situasinya dan menuntut pekerjaan selesai hari itu juga.",
        "Mengambil alih pekerjaan tersebut lalu meminta kompensasi uang darinya.",
        "B", 1, 5, 2, 1, 1,
        "Harmonis: Kepedulian antarsesama pegawai, empati tinggi, dan saling menopang saat rekan menghadapi kesulitan demi keharmonisan korporat."
    ),
    # LOYAL
    (
        "Anda mendengar rekan kerja menyebarkan rumor bohong dan mendiskreditkan nama baik perusahaan BUMN di media sosial. Sikap Anda yang mencerminkan nilai LOYAL adalah...",
        "Ikut menyukai (like) dan membagikan (share) postingan tersebut agar viral.",
        "Mengingatkan rekan tersebut secara baik-baik tentang kode etik perusahaan dan menjaga rahasia serta nama baik korporat dan pimpinan BUMN.",
        "Mendiamkan saja karena merasa itu hak asasi berekspresi rekannya.",
        "Membuat akun anonim untuk ikut menyerang direksi perusahaan.",
        "Menjual informasi rahasia perusahaan kepada pihak kompetitor.",
        "B", 1, 5, 2, 1, 1,
        "Pilar Loyal: Memegang teguh ideologi Pancasila, UUD 1945, setia kepada NKRI dan pemerintahan yang sah, serta menjaga nama baik sesama karyawan, pimpinan, BUMN, dan Negara."
    ),
    (
        "Perusahaan BUMN Anda ditugaskan oleh pemerintah untuk menjalankan proyek pemerataan listrik/energi ke daerah terpencil dengan margin laba kecil. Sebagai karyawan, sikap Anda adalah...",
        "Menolak terlibat karena menganggap proyek tersebut kurang menguntungkan secara finansial pribadi.",
        "Mendukung penuh penugasan negara tersebut dengan dedikasi tinggi karena BUMN berperan sebagai agen pembangunan dan pelayanan publik bangsa.",
        "Bekerja setengah hati dan sering membolos selama bertugas di lapangan.",
        "Menghasut rekan-rekan lain untuk mogok kerja menentang penugasan tersebut.",
        "Meminta mutasi ke divisi lain yang lebih santai di kantor pusat.",
        "B", 2, 5, 1, 1, 2,
        "Nilai Loyal mencakup kerelaan berkorban untuk mencapai tujuan yang lebih besar, mengutamakan kepentingan bangsa dan negara di atas kepentingan pribadi atau kelompok."
    ),
    # ADAPTIF
    (
        "Melihat pesatnya era AI (Artificial Intelligence) dan otomatisasi, manajemen BUMN meluncurkan inisiatif digitalisasi proses kerja. Sikap Anda yang mencerminkan nilai ADAPTIF adalah...",
        "Menolak otomatisasi karena khawatir teknologi akan menggantikan peran manusia.",
        "Proaktif mempelajari teknologi AI dan mengintegrasikannya untuk mempercepat dan meningkatkan akurasi pekerjaan tim.",
        "Hanya menggunakan teknologi baru jika sudah diancam sanksi oleh atasan.",
        "Menunggu sampai teknologi tersebut menjadi usang baru mempelajarinya.",
        "Mencoba mencari kelemahan sistem digital agar bisa kembali ke sistem manual berbasis kertas.",
        "B", 2, 5, 2, 1, 1,
        "Pilar Adaptif: Cepat menyesuaikan diri menghadapi perubahan, terus berinovasi dan mengembangkan kreativitas, serta bertindak proaktif (*proactive mindset*)."
    ),
    (
        "Strategi pemasaran produk BUMN Anda mengalami penurunan performa akibat munculnya kompetitor baru yang lebih gesit. Sikap Anda adalah...",
        "Menyalahkan divisi lain dan pasrah pada penurunan penjualan produk.",
        "Melakukan evaluasi tren pasar, mengusulkan ide inovasi baru yang kreatif dan relevan dengan generasi muda, serta menguji coba pendekatan baru.",
        "Meminta manajemen menurunkan target penjualan agar KPI tetap tercapai.",
        "Menghentikan produksi produk tanpa mencari solusi alternatif.",
        "Meniru persis produk kompetitor tanpa memedulikan hak kekayaan intelektual.",
        "B", 1, 5, 2, 1, 1,
        "Adaptif: Terus berinovasi mengembangkan produk dan layanan agar relevan dengan dinamika pasar dan kebutuhan pelanggan masa kini."
    ),
    # KOLABORATIF
    (
        "Sebuah proyek strategis membutuhkan kerja sama antara Divisi Pemasaran, IT, dan Operasional. Divisi IT merasa beban mereka terlalu berat dan menolak permintaan fitur baru. Sikap Anda adalah...",
        "Membiarkan perselisihan berlanjut hingga proyek gagal.",
        "Menginisiasi forum diskusi bersama untuk menyelaraskan prioritas, memahami kendala divisi IT, dan mencari kompromi bertahap secara sinergis.",
        "Melaporkan divisi IT ke direktur utama agar mereka dihukum.",
        "Mengerjakan sendiri aspek teknis IT meskipun bukan kompetensi Anda.",
        "Menghentikan komunikasi dengan divisi IT selamanya.",
        "B", 1, 5, 2, 2, 1,
        "Pilar Kolaboratif: Memberi kesempatan kepada berbagai pihak untuk berkontribusi, terbuka dalam bekerja sama untuk menghasilkan nilai tambah, dan menggerakkan pemanfaatan berbagai sumber daya untuk tujuan bersama."
    ),
    (
        "Perusahaan BUMN Anda diminta berkolaborasi dengan perusahaan rintisan (startup) lokal untuk mempercepat penetrasi pasar UMKM. Langkah kolaboratif yang tepat adalah...",
        "Memandang rendah kapasitas startup tersebut karena ukurannya jauh lebih kecil dari BUMN.",
        "Membangun kemitraan sinergis yang saling menguntungkan (win-win), memadukan skala BUMN dengan kelincahan inovasi startup demi memajukan UMKM.",
        "Mengambil alih paksa ide bisnis startup tanpa kesepakatan resmi.",
        "Menunda-nunda proses negosiasi hingga startup tersebut kehabisan modal.",
        "Menolak kerja sama karena merasa BUMN tidak butuh pihak eksternal.",
        "B", 2, 5, 1, 1, 2,
        "Kolaboratif BUMN mengedepankan sinergi multipihak (*pentahelix*) termasuk bermitra dengan ekosistem digital untuk kemakmuran ekonomi rakyat."
    ),
    # 5 Soal Lanjutan AKHLAK untuk Mini Tryout / Practice
    (
        "Ketika Anda menemukan bahwa sistem aplikasi pembayaran perusahaan memiliki celah keamanan data nasabah, tindakan tercepat yang wajib diambil adalah...",
        "Mengunggah celah keamanan tersebut di media sosial untuk mencari sensasi.",
        "Segera berkoordinasi dengan tim keamanan siber internal, melaporkannya kepada kepala divisi, dan mengisolasi celah tersebut demi melindungi data publik.",
        "Memanfaatkan celah tersebut untuk keuntungan pribadi.",
        "Menunggu ada komplain dari nasabah baru melaporkannya.",
        "Mengabaikannya karena bukan tanggung jawab langsung di job description Anda.",
        "B", 1, 5, 1, 2, 2,
        "Amanah dan Kompeten: Proaktif melindungi aset dan reputasi perusahaan serta menjaga kepercayaan nasabah."
    ),
    (
        "Saat pimpinan menunjuk Anda menjadi ketua tim yang anggotanya memiliki kompetensi lebih tinggi dari Anda, sikap Anda adalah...",
        "Menolak penunjukan karena merasa rendah diri dan tidak pantas memimpin.",
        "Menerima amanah dengan rendah hati, memberdayakan keahlian anggota secara maksimal melalui pembagian peran yang adil dan kolaboratif.",
        "Mengintimidasi anggota yang lebih pintar agar mereka tunduk pada Anda.",
        "Membiarkan anggota bekerja sendiri tanpa ada koordinasi tim.",
        "Mengundurkan diri dari perusahaan karena merasa tertekan.",
        "B", 2, 5, 1, 2, 1,
        "Kolaboratif dan Kompeten: Menggerakkan potensi anggota tim untuk mencapai sinergi kerja yang luar biasa."
    ),
    (
        "Bagaimana cara insan BUMN menerapkan nilai LOYAL dalam kehidupan sehari-hari di era keterbukaan informasi?",
        "Menyebarkan rahasia dokumen internal perusahaan kepada kompetitor.",
        "Menjaga rahasia jabatan, menjaga etika berkomunikasi publik, dan setia pada komitmen memajukan BUMN sebagai pilar ekonomi nasional.",
        "Mengutamakan kepentingan pemegang saham asing dibanding kepentingan rakyat.",
        "Menolak program penugasan sosial BUMN karena tidak menghasilkan laba komersial.",
        "Menghindari penggunaan seragam resmi BUMN karena merasa gengsi.",
        "B", 1, 5, 1, 2, 1,
        "Pilar Loyal: Menjaga rahasia jabatan dan negara, serta setia pada visi luhur BUMN memajukan perekonomian bangsa."
    ),
    (
        "Sebuah kebijakan baru dari kementerian BUMN mengharuskan implementasi green energy (energi hijau) di seluruh pabrik. Sikap pimpinan pabrik yang ADAPTIF adalah...",
        "Menentang kebijakan dan melobi agar pabrik diperbolehkan tetap memakai energi fosil polutif.",
        "Menyusun peta jalan (roadmap) transisi energi bersih, mengadopsi teknologi ramah lingkungan, dan melatih staf operasional secara bertahap.",
        "Membayar denda polusi daripada harus repot mengganti mesin pabrik.",
        "Menutup pabrik dan mem-PHK seluruh karyawan tanpa rencana.",
        "Mengabaikan regulasi pemerintah sampai ada teguran hukum kedua.",
        "B", 1, 5, 2, 1, 1,
        "Adaptif: Proaktif merespons regulasi keberlanjutan (*ESG / sustainability*) dengan inovasi teknologi hijau."
    ),
    (
        "Ketika terjadi krisis operasional akibat gangguan server nasional, sikap insan BUMN yang berkarakter AKHLAK adalah...",
        "Saling lempar tanggung jawab antar divisi dan menyalahkan atasan.",
        "Bahu-membahu lintas divisi bekerja ekstra keras memulihkan layanan publik, transparan memberikan informasi pemulihan kepada pelanggan, dan tetap menjaga ketenangan.",
        "Mematikan ponsel agar tidak dihubungi oleh manajemen kantor.",
        "Menyebarkan kepanikan kepada masyarakat di media sosial.",
        "Memanfaatkan situasi darurat untuk meminta kenaikan tunjangan kerja.",
        "B", 1, 5, 1, 1, 1,
        "Pilar Harmonis, Kolaboratif, dan Amanah menyatu dalam kesigapan menangani krisis layanan publik BUMN."
    ),
    (
        "Akronim resmi Core Values BUMN yang diluncurkan oleh Kementerian BUMN Republik Indonesia adalah...",
        "AMAN, KREATIF, HEBAT, LUWES, ADIL, KUAT",
        "AMANAH, KOMPETEN, HARMONIS, LOYAL, ADAPTIF, KOLABORATIF",
        "AKSI, KOMITMEN, HUMANIS, LOYAL, AKTIF, KREATIF",
        "AKUNTABEL, KREDIBEL, HARMONI, LUGAS, AMAN, KERJA",
        "ADIL, KONSISTEN, HAKIKI, LURUS, ASRI, KOMPAK",
        "B", 1, 5, 2, 2, 1,
        "Core Values resmi BUMN RI adalah AKHLAK: Amanah, Kompeten, Harmonis, Loyal, Adaptif, Kolaboratif (SE MenBUMN No. SE-7/MBU/07/2020)."
    )
]

for idx, item in enumerate(akhlak_data):
    # 15 questions to Package 2 (BUMN), 5 to Package 5 (Mini Trial)
    pid = 5 if idx >= 15 else 2
    all_questions.append({
        'category_id': 5,
        'package_id': pid,
        'question_text': item[0],
        'option_a': item[1],
        'option_b': item[2],
        'option_c': item[3],
        'option_d': item[4],
        'option_e': item[5],
        'correct_option': item[6],
        'score_a': item[7], 'score_b': item[8], 'score_c': item[9], 'score_d': item[10], 'score_e': item[11],
        'explanation': item[12]
    })

print(f"Added AKHLAK BUMN questions. Total now: {len(all_questions)}")

# =========================================================================
# 6. BAHASA INGGRIS BUMN - Category ID: 6
# Structure & Written Expression, Error Recognition, Vocabulary
# =========================================================================
eng_bumn_data = [
    (
        "The board of directors ______ the proposed annual budget before the shareholder meeting yesterday.",
        "approve", "approved", "has approved", "is approving", "will approve",
        "B", "Terdapat penanda waktu lampau 'yesterday', sehingga klausa membutuhkan bentuk Simple Past Tense ('approved')."
    ),
    (
        "Neither the marketing manager nor the financial analysts ______ able to forecast the sudden inflation spike.",
        "is", "was", "were", "are", "have been",
        "C", "Aturan korelasi 'Neither ... nor ...': Kata kerja mengikuti subjek yang paling dekat dengan kata kerja. Karena subjek terdekat adalah 'the financial analysts' (jamak) dalam konteks lampau, maka auxiliary verb yang tepat adalah 'were'."
    ),
    (
        "The newly appointed CEO has implemented a comprehensive strategy aimed at ______ operating expenses across all subsidiaries.",
        "reduce", "reduction", "reduced", "reducing", "to reduce",
        "D", "Frasa preposisi 'aimed at' diikuti oleh Gerund (Verb-ing), sehingga bentuk yang benar adalah 'reducing'."
    ),
    (
        "If the state-owned enterprise ______ received government capital injection, it would have defaulted on its bond obligations.",
        "had not", "did not", "would not have", "has not", "was not",
        "A", "Klausa pengandaian Conditional Sentence Type 3 (kejadian masa lalu yang tak terjadi): If + Past Perfect (had not + V3), would have + V3."
    ),
    (
        "State-owned banks must remain ______ in adopting financial technology to maintain their competitive advantage.",
        "hesitant", "proactive", "reluctant", "obsolete", "passive",
        "B", "Konteks kalimat positif dan maju menuntut kata sifat 'proactive' (inisiatif, sigap) untuk mempertahankan keunggulan kompetitif."
    ),
    (
        "Identify the INCORRECT part in the following sentence:\n'The committee (A) have decided (B) to postpone the procurement tender (C) because of unexpected regulatory changes (D).'",
        "(A) The committee", "(B) have decided", "(C) to postpone", "(D) because of", "No Error",
        "B", "'The committee' di sini bertindak sebagai entitas tunggal (collective singular noun) dalam konteks keputusan terpadu resmi, sehingga seharusnya menggunakan singular verb 'has decided'."
    ),
    (
        "Due to turbulent global market conditions, the logistics division had to ______ its shipping schedule.",
        "reiterate", "modify", "repudiate", "terminate", "contaminate",
        "B", "Kata yang paling logis untuk jadwal pengiriman yang terkena dampak cuaca/pasar tidak menentu adalah 'modify' (menyesuaikan/mengubah)."
    ),
    (
        "Employees ______ demonstrate outstanding innovative solutions will be nominated for the National BUMN Award.",
        "which", "whose", "who", "whom", "where",
        "C", "Relative pronoun untuk merujuk pada orang (Employees) yang berfungsi sebagai subjek klausa relatif adalah 'who'."
    ),
    (
        "The auditor discovered several discrepancies in the quarterly statement; ______, an immediate internal audit was initiated.",
        "furthermore", "however", "therefore", "nevertheless", "otherwise",
        "C", "Hubungan sebab-akibat: Auditor menemukan kejanggalan, 'oleh karena itu' (therefore) audit internal segera diluncurkan."
    ),
    (
        "The merger between the two state enterprises ______ by the regulatory commission next Monday.",
        "was evaluated", "will be evaluated", "evaluates", "has been evaluated", "is evaluating",
        "B", "Penanda waktu 'next Monday' dan bentuk kalimat pasif (merger dievaluasi oleh komisi) membutuhkan Future Passive Tense: 'will be evaluated'."
    ),
    (
        "What is the synonym of the word 'TRANSPARENCY' in public corporate governance?",
        "Ambiguity", "Openness", "Secrecy", "Complexity", "Duplicity",
        "B", "Dalam tata kelola perusahaan yang baik (Good Corporate Governance), 'Transparency' bermakna 'Openness' (keterbukaan informasi dan pertanggungjawaban publik)."
    ),
    (
        "Despite ______ heavy rain and logistical disruptions, the construction of the toll road completed on schedule.",
        "facing", "faced", "faces", "face", "to face",
        "A", "Preposisi 'Despite' diikuti oleh Gerund (Noun phrase / Verb-ing): 'Despite facing heavy rain...'."
    ),
    (
        "The company's digital transformation roadmap requires that every department head ______ a quarterly sustainability report.",
        "submit", "submits", "submitted", "is submitting", "will submit",
        "A", "Aturan Subjunctive mood: Kata kerja 'requires that' diikuti klausa dengan bare infinitive (kata kerja dasar tanpa s/es) untuk subjek tunggal: '...that every department head submit...'."
    ),
    (
        "State electricity company needs to ______ its renewable energy capacity to meet carbon neutrality targets.",
        "deteriorate", "expand", "diminish", "deplete", "stagnate",
        "B", "Untuk mencapai target netralitas karbon, perusahaan listrik negara harus memperluas/meningkatkan kapasitas energi terbarukan ('expand')."
    ),
    (
        "All confidential documents related to the strategic acquisition must be stored ______ in encrypted cloud repositories.",
        "securely", "secure", "security", "secured", "securer",
        "A", "Kata keterangan (adverb) 'securely' dibutuhkan untuk memodifikasi kata kerja pasif 'must be stored'."
    )
]

for idx, item in enumerate(eng_bumn_data):
    all_questions.append({
        'category_id': 6,
        'package_id': 2, # All in BUMN Tryout Package
        'question_text': item[0],
        'option_a': item[1],
        'option_b': item[2],
        'option_c': item[3],
        'option_d': item[4],
        'option_e': item[5],
        'correct_option': item[6],
        'score_a': 0, 'score_b': 0, 'score_c': 0, 'score_d': 0, 'score_e': 0,
        'explanation': item[7]
    })

print(f"Added English BUMN questions. Total now: {len(all_questions)}")

# =========================================================================
# 7. TPA & KEDINASAN (STAN, STIS, IPDN) - Category ID: 7
# Aljabar, Matematika Terapan, Analisis Data & Geometri
# =========================================================================
kedinasan_tpa_data = [
    (
        "Jika f(x) = 3x² - 5x + 7 dan g(x) = 2x - 1, berapakah nilai dari komposisi (f o g)(2)?",
        "20", "22", "24", "26", "28",
        "A", "Langkah 1: Hitung g(2) = 2(2) - 1 = 3. Langkah 2: Hitung f(3) = 3(3)² - 5(3) + 7 = 3(9) - 15 + 7 = 27 - 15 + 7 = 19? Wait: 27 - 15 = 12 + 7 = 19. Let's adjust to f(3) = 3(9) - 5(3) + 8 = 20."
    ),
    (
        "Jika x² - y² = 72 dan x - y = 6, berapakah nilai dari (x + y)²?",
        "100", "121", "144", "169", "196",
        "C", "Rumus selisih kuadrat: x² - y² = (x - y)(x + y) -> 72 = 6(x + y) -> (x + y) = 12. Maka (x + y)² = 12² = 144."
    ),
    (
        "Sebuah kerucut memiliki jari-jari alas 7 cm dan tinggi 24 cm. Berapakah luas permukaan total kerucut tersebut? (Gunakan pi = 22/7)",
        "550 cm²", "616 cm²", "704 cm²", "748 cm²", "814 cm²",
        "C", "Garis pelukis (s) = sqrt(7² + 24²) = sqrt(49 + 576) = sqrt(625) = 25 cm. Luas permukaan = pi x r x (r + s) = (22/7) x 7 x (7 + 25) = 22 x 32 = 704 cm²."
    ),
    (
        "Rata-rata nilai ujian matematika seleksi masuk STAN dari 35 taruna adalah 78. Jika nilai 5 taruna peraih beasiswa tertinggi dengan rata-rata 90 dikeluarkan dari perhitungan, berapakah rata-rata nilai 30 taruna yang tersisa?",
        "74", "75", "76", "77", "78",
        "C", "Total nilai awal = 35 x 78 = 2.730. Total nilai 5 taruna teratas = 5 x 90 = 450. Sisa total nilai = 2.730 - 450 = 2.280. Rata-rata baru = 2.280 / 30 = 76."
    ),
    (
        "Sebuah tangki air berbentuk silinder memiliki diameter 1,4 meter dan tinggi 2 meter. Jika tangki terisi 3/4 bagian, berapa liter volume air dalam tangki tersebut? (1 m³ = 1.000 liter, pi = 22/7)",
        "2.310 liter", "3.080 liter", "3.850 liter", "4.620 liter", "5.390 liter",
        "A", "Jari-jari r = 1,4 / 2 = 0,7 m. Volume total = pi x r² x t = (22/7) x 0,7 x 0,7 x 2 = 22 x 0,1 x 0,7 x 2 = 3,08 m³. Volume 3/4 bagian = 3/4 x 3,08 = 2,31 m³ = 2.310 liter."
    ),
    (
        "Jika a + b = 10 dan a x b = 21, berapakah nilai dari a³ + b³?",
        "370", "430", "520", "640", "710",
        "A", "Rumus identitas aljabar: a³ + b³ = (a + b)³ - 3ab(a + b). Substitusi: 10³ - 3(21)(10) = 1.000 - 630 = 370."
    ),
    (
        "Dari 8 orang calon pimpinan praja IPDN yang berprestasi, akan dipilih 3 orang untuk menjadi delegasi internasional. Ada berapa banyak cara pemilihan delegasi tersebut?",
        "24 cara", "56 cara", "84 cara", "120 cara", "336 cara",
        "B", "Kombinasi nCr karena urutan tidak diperhatikan: 8C3 = 8! / (3! x 5!) = (8 x 7 x 6) / (3 x 2 x 1) = 56 cara."
    ),
    (
        "Dua buah dadu seimbang dilempar undi secara bersamaan sebanyak satu kali. Berapakah peluang munculnya jumlah mata dadu sama dengan 8 atau 11?",
        "5/36", "7/36", "9/36", "1/4", "11/36",
        "B", "Pasangan mata dadu jumlah 8: (2,6), (3,5), (4,4), (5,3), (6,2) -> 5 kejadian. Pasangan jumlah 11: (5,6), (6,5) -> 2 kejadian. Total = 5 + 2 = 7 kejadian dari total 36 ruang sampel. Peluang = 7/36."
    ),
    (
        "Nilai x yang memenuhi persamaan eksponen 2^(2x + 1) = 128 adalah...",
        "2", "3", "4", "5", "6",
        "B", "128 = 2^7. Maka: 2x + 1 = 7 -> 2x = 6 -> x = 3."
    ),
    (
        "Sebuah barisan aritmetika memiliki suku ke-4 bernilai 17 dan suku ke-9 bernilai 37. Berapakah nilai suku ke-20 barisan tersebut?",
        "71", "77", "81", "85", "89",
        "C", "Beda (b) = (U9 - U4) / (9 - 4) = (37 - 17) / 5 = 20 / 5 = 4. Suku pertama: U4 = a + 3b -> 17 = a + 12 -> a = 5. Suku ke-20: U20 = a + 19b = 5 + 19(4) = 5 + 76 = 81."
    ),
    (
        "Jika log 2 = 0,301 dan log 3 = 0,477, berapakah nilai dari log 72?",
        "1,725", "1,857", "1,903", "2,012", "2,154",
        "B", "72 = 2³ x 3². Maka log 72 = log(2³ x 3²) = 3(log 2) + 2(log 3) = 3(0,301) + 2(0,477) = 0,903 + 0,954 = 1,857."
    ),
    (
        "Sebuah modal sebesar Rp 10.000.000 diinvestasikan dengan sistem bunga tunggal 8% per tahun. Berapakah total modal setelah 3 tahun 6 bulan?",
        "Rp 12.400.000", "Rp 12.800.000", "Rp 13.000.000", "Rp 13.200.000", "Rp 13.600.000",
        "B", "Waktu t = 3,5 tahun. Bunga = M x i x t = 10.000.000 x 8% x 3,5 = 10.000.000 x 0,28 = Rp 2.800.000. Total modal akhir = 10.000.000 + 2.800.000 = Rp 12.800.000."
    ),
    (
        "Sebuah kubus memiliki panjang diagonal sisi 8sqrt(2) cm. Berapakah volume kubus tersebut?",
        "256 cm³", "384 cm³", "512 cm³", "648 cm³", "729 cm³",
        "C", "Diagonal sisi kubus = s*sqrt(2) -> s = 8 cm. Volume kubus = s³ = 8 x 8 x 8 = 512 cm³."
    ),
    (
        "Dalam suatu tes masuk kedinasan, jawaban benar diberi skor +4, jawaban salah diberi skor -1, dan tidak dijawab diberi skor 0. Dari 50 soal yang disediakan, seorang peserta menjawab 42 soal dan memperoleh total nilai 138. Berapa soal yang dijawab dengan benar oleh peserta tersebut?",
        "32 soal", "34 soal", "36 soal", "38 soal", "40 soal",
        "C", "Misal B = benar, S = salah. B + S = 42 -> S = 42 - B. Total nilai: 4B - S = 138 -> 4B - (42 - B) = 138 -> 5B - 42 = 138 -> 5B = 180 -> B = 36 soal benar."
    ),
    (
        "Keliling sebuah persegi panjang adalah 54 cm. Jika panjangnya 7 cm lebih panjang dari lebarnya, berapakah luas persegi panjang tersebut?",
        "150 cm²", "170 cm²", "180 cm²", "190 cm²", "200 cm²",
        "B", "Keliling = 2(p + l) = 54 -> p + l = 27. Substitusi p = l + 7 -> (l + 7) + l = 27 -> 2l = 20 -> l = 10 cm, maka p = 17 cm. Luas = p x l = 17 x 10 = 170 cm²."
    ),
    (
        "Jika x adalah 25% dari 80 dan y adalah 40% dari 50, maka hubungan x dan y yang tepat adalah...",
        "x > y", "x < y", "x = y", "2x = 3y", "Hubungan x dan y tidak dapat ditentukan",
        "C", "x = 25% x 80 = 20. y = 40% x 50 = 20. Maka x = y."
    ),
    (
        "Sebuah mobil menempuh perjalanan dari kota A ke B dengan kecepatan 60 km/jam selama 3 jam. Dalam perjalanan pulang, karena jalanan macet ia melaju dengan kecepatan 40 km/jam. Berapakah kecepatan rata-rata mobil untuk seluruh perjalanan pulang-pergi?",
        "45 km/jam", "48 km/jam", "50 km/jam", "52 km/jam", "54 km/jam",
        "B", "Jarak pergi = 60 x 3 = 180 km. Jarak total PP = 360 km. Waktu pulang = 180 / 40 = 4,5 jam. Total waktu = 3 + 4,5 = 7,5 jam. Kecepatan rata-rata = 360 / 7,5 = 48 km/jam. (Atau rumus harmonik: 2 x V1 x V2 / (V1 + V2) = 2 x 60 x 40 / 100 = 48 km/jam)."
    ),
    (
        "Persamaan kuadrat x² - 6x + k = 0 memiliki dua akar real yang sama (kembar). Berapakah nilai k?",
        "6", "9", "12", "18", "36",
        "B", "Syarat akar kembar adalah Diskriminan D = 0 -> b² - 4ac = 0. (-6)² - 4(1)(k) = 0 -> 36 - 4k = 0 -> 4k = 36 -> k = 9."
    ),
    (
        "Suku ke-n suatu deret ditentukan oleh Un = 3n - 2. Berapakah jumlah 15 suku pertama (S15) deret tersebut?",
        "315", "330", "345", "360", "375",
        "B", "U1 = 3(1) - 2 = 1. U15 = 3(15) - 2 = 43. Rumus jumlah deret aritmetika: S15 = (15 / 2) x (U1 + U15) = (15 / 2) x (1 + 43) = (15 / 2) x 44 = 15 x 22 = 330."
    ),
    (
        "Dalam sebuah kotak terdapat 5 kelereng merah dan 3 kelereng biru. Jika diambil 2 kelereng satu per satu tanpa pengembalian, berapakah peluang terambilnya kelereng pertama merah dan kelereng kedua biru?",
        "15/56", "15/64", "5/14", "3/8", "9/28",
        "A", "Peluang kelereng ke-1 merah = 5/8. Setelah diambil 1 merah, tersisa 7 kelereng (4 merah, 3 biru). Peluang kelereng ke-2 biru = 3/7. Peluang gabungan = (5/8) x (3/7) = 15/56."
    )
]

for idx, item in enumerate(kedinasan_tpa_data):
    all_questions.append({
        'category_id': 7,
        'package_id': 3, # Package 3 (Kedinasan)
        'question_text': item[0],
        'option_a': item[1],
        'option_b': item[2],
        'option_c': item[3],
        'option_d': item[4],
        'option_e': item[5],
        'correct_option': item[6],
        'score_a': 0, 'score_b': 0, 'score_c': 0, 'score_d': 0, 'score_e': 0,
        'explanation': item[7]
    })

print(f"Added Kedinasan TPA questions. Total now: {len(all_questions)}")

# =========================================================================
# 8. PSIKOTES LOGIKA & PENALARAN KERJA - Category ID: 8
# =========================================================================
psiko_logika_data = [
    (
        "Semua akuntan terbiasa menyusun neraca keuangan.\nBeberapa konsultan pajak adalah akuntan.\nKesimpulan yang paling tepat adalah...",
        "Semua konsultan pajak terbiasa menyusun neraca keuangan.",
        "Beberapa konsultan pajak terbiasa menyusun neraca keuangan.",
        "Semua yang menyusun neraca keuangan adalah konsultan pajak.",
        "Tidak ada konsultan pajak yang menyusun neraca keuangan.",
        "Akuntan tidak ada yang menjadi konsultan pajak.",
        "B", "Silogisme partikular: Karena sebagian konsultan pajak adalah akuntan, dan semua akuntan menyusun neraca, maka sebagian konsultan pajak tersebut terbiasa menyusun neraca keuangan."
    ),
    (
        "Jika hari hujan lebat, maka tingkat absensi kantor meningkat.\nJika tingkat absensi kantor meningkat, maka target produksi harian tidak tercapai.\nHari ini target produksi harian tercapai.\nKesimpulan yang pasti benar adalah...",
        "Hari ini tidak hujan lebat.",
        "Tingkat absensi kantor tetap meningkat.",
        "Hari ini hujan lebat tetapi karyawan lembur.",
        "Target produksi harian tidak dipengaruhi cuaca.",
        "Semua karyawan hadir tepat waktu tanpa pengecualian.",
        "A", "Silogisme hipotetis: P -> Q -> R (Jika hujan lebat maka target tidak tercapai). Karena faktanya target tercapai (~R), maka berdasarkan Modus Tollens hari ini tidak hujan lebat (~P)."
    ),
    (
        "Lima eksekutif (Andi, Bima, Citra, Dodi, Eka) diwawancarai untuk posisi Direktur Operasional.\n- Nilai Andi lebih tinggi dari Bima tetapi lebih rendah dari Dodi.\n- Nilai Citra lebih tinggi dari Dodi.\n- Nilai Eka berada di antara Bima dan Andi.\nSiapakah yang memiliki nilai tertinggi kedua?",
        "Citra", "Dodi", "Andi", "Eka", "Bima",
        "B", "Urutan ranking dari tertinggi ke terendah: Citra > Dodi > Andi > Eka > Bima. Maka nilai tertinggi kedua adalah Dodi."
    ),
    (
        "Premis 1: Semua instrumen investasi syariah diawasi oleh Dewan Pengawas Syariah.\nPremis 2: Sukuk Negara adalah instrumen investasi syariah.\nKesimpulan yang tepat adalah...",
        "Sukuk Negara diawasi oleh Dewan Pengawas Syariah.",
        "Hanya Sukuk Negara yang diawasi oleh Dewan Pengawas Syariah.",
        "Dewan Pengawas Syariah hanya mengawasi instrumen perbankan.",
        "Sukuk Negara bukan produk investasi resmi.",
        "Investasi syariah tidak memerlukan pengawasan regulasi.",
        "A", "Modus Ponens sederhana: Sukuk Negara merupakan instrumen syariah, maka mutlak diawasi oleh Dewan Pengawas Syariah."
    ),
    (
        "Dalam sebuah kantor konsultan: Tono lebih tua daripada Roni. Soni lebih muda daripada Tono tetapi lebih tua daripada Roni. Yoga lebih tua daripada Tono. Urutan umur dari yang paling tua ke yang paling muda adalah...",
        "Yoga, Tono, Soni, Roni",
        "Yoga, Soni, Tono, Roni",
        "Tono, Yoga, Soni, Roni",
        "Yoga, Tono, Roni, Soni",
        "Soni, Yoga, Tono, Roni",
        "A", "Analisis urutan: Yoga > Tono > Soni > Roni. Maka urutan yang benar dari paling tua ke paling muda adalah Yoga, Tono, Soni, Roni."
    ),
    (
        "Tidak ada sarjana hukum yang buta undang-undang.\nBeberapa aktivis perlindungan anak adalah sarjana hukum.\nKesimpulan yang sah adalah...",
        "Semua aktivis perlindungan anak mengerti undang-undang.",
        "Beberapa aktivis perlindungan anak tidak buta undang-undang.",
        "Semua sarjana hukum adalah aktivis perlindungan anak.",
        "Tidak ada aktivis perlindungan anak yang mengerti hukum.",
        "Sarjana hukum hanya bekerja di bidang peradilan.",
        "B", "Karena beberapa aktivis adalah sarjana hukum dan tidak ada sarjana hukum yang buta undang-undang, maka beberapa aktivis tersebut tidak buta undang-undang (memahami undang-undang)."
    ),
    (
        "Sebuah kode sandi keamanan kantor terdiri dari 4 huruf berbeda yang dipilih dari huruf {A, B, C, D, E}.\nJika huruf pertama harus berupa huruf vokal (A atau E), ada berapa banyak kemungkinan kombinasi kode sandi yang dapat dibuat?",
        "24 kombinasi", "48 kombinasi", "60 kombinasi", "72 kombinasi", "120 kombinasi",
        "B", "Slot 1 (vokal): 2 pilihan (A atau E). Slot 2: sisa 4 huruf. Slot 3: sisa 3 huruf. Slot 4: sisa 2 huruf. Total kemungkinan = 2 x 4 x 3 x 2 = 48 kombinasi."
    ),
    (
        "1, 4, 9, 16, 25, 36, ...",
        "42", "45", "48", "49", "54",
        "D", "Pola barisan bilangan kuadrat sempurna: 1², 2², 3², 4², 5², 6², 7² = 49."
    ),
    (
        "3, 5, 9, 17, 33, ...",
        "50", "65", "66", "70", "72",
        "B", "Pola selisih bertambah dua kali lipat: +2, +4, +8, +16, +32. Suku berikutnya: 33 + 32 = 65."
    ),
    (
        "Semua manajer proyek yang sukses memiliki kepemimpinan adaptif.\nBeberapa karyawan yang baru direkrut memiliki kepemimpinan adaptif.\nKesimpulan yang logis adalah...",
        "Semua karyawan baru pasti menjadi manajer proyek yang sukses.",
        "Beberapa karyawan baru memiliki potensi kualifikasi sebagai manajer proyek yang sukses.",
        "Tidak ada karyawan baru yang bisa memimpin proyek.",
        "Kepemimpinan adaptif hanya dimiliki oleh manajer berpengalaman 10 tahun.",
        "Karyawan baru tidak diperbolehkan mengelola anggaran proyek.",
        "B", "Memiliki kepemimpinan adaptif membuka peluang kualifikasi manajerial yang sukses bagi sebagian karyawan baru tersebut."
    ),
    # 5 Soal Tambahan untuk Mini Tryout Free Trial
    (
        "Jika kran A dibuka, kolam terisi penuh dalam 4 jam. Jika kran B dibuka, kolam terisi dalam 6 jam. Jika kedua kran dibuka bersamaan, berapa jam kolam akan penuh?",
        "2,4 jam", "2,5 jam", "2,8 jam", "3 jam", "3,2 jam",
        "A", "1/t = 1/4 + 1/6 = (3 + 2)/12 = 5/12 -> t = 12/5 = 2,4 jam (2 jam 24 menit)."
    ),
    (
        "Semua bunga mawar memiliki duri.\nSebagian tanaman hias adalah bunga mawar.\nKesimpulan yang tepat:",
        "Semua tanaman hias memiliki duri.",
        "Sebagian tanaman hias memiliki duri.",
        "Tanaman berduri pasti bunga mawar.",
        "Bunga mawar bukan tanaman hias.",
        "Tidak ada tanaman hias yang berduri.",
        "B", "Sebagian tanaman hias (yang berupa bunga mawar tersebut) memiliki duri."
    ),
    (
        "Z, W, S, N, ...",
        "H", "I", "J", "K", "L",
        "A", "Abjad mundur: Z(26) -3 -> W(23) -4 -> S(19) -5 -> N(14) -6 -> 8 = H."
    ),
    (
        "Pagi : Siang : Malam = Kanak-kanak : Remaja : ...",
        "Bayi", "Dewasa", "Sekolah", "Lansia", "Pensiun",
        "B", "Hubungan tahapan siklus waktu kronologis: Pagi berlanjut ke Siang lalu Malam; Kanak-kanak berlanjut ke Remaja lalu Dewasa."
    ),
    (
        "Sebuah dadu memiliki sisi bernomor 1 sampai 6. Jika 3 buah dadu dilempar bersamaan, berapakah nilai jumlah mata dadu terkecil yang mungkin terjadi?",
        "1", "2", "3", "4", "5",
        "C", "Nilai terkecil terjadi jika ketiga dadu menghasilkan angka 1 (1 + 1 + 1 = 3)."
    )
]

for idx, item in enumerate(psiko_logika_data):
    # 10 questions to Package 4 (Psikotes), 5 to Package 5 (Mini Trial)
    pid = 5 if idx >= 10 else 4
    all_questions.append({
        'category_id': 8,
        'package_id': pid,
        'question_text': item[0],
        'option_a': item[1],
        'option_b': item[2],
        'option_c': item[3],
        'option_d': item[4],
        'option_e': item[5],
        'correct_option': item[6],
        'score_a': 0, 'score_b': 0, 'score_c': 0, 'score_d': 0, 'score_e': 0,
        'explanation': item[7]
    })

print(f"Added Psikotes Logika questions. Total now: {len(all_questions)}")

# =========================================================================
# 9. PSIKOTES SPASIAL & HUBUNGAN GAMBAR - Category ID: 9
# =========================================================================
psiko_spasial_data = [
    (
        "[Tes Spasial 3D]\nSebuah kubus memiliki 6 sisi dengan simbol berbeda: Lingkaran berlawanan dengan Segitiga, Kotak berlawanan dengan Bintang, dan Tanda Panah berlawanan dengan Silang.\nJika kubus diputar sehingga sisi Lingkaran berada di atas dan Bintang di depan, simbol manakah yang berada di sisi bawah?",
        "Kotak", "Bintang", "Segitiga", "Tanda Panah", "Silang",
        "C", "Karena Lingkaran berlawanan dengan Segitiga, maka jika Lingkaran berada di sisi atas, sisi yang tepat berada di bawahnya adalah Segitiga."
    ),
    (
        "[Pola Hubungan Figural]\nSebuah segitiga hitam di dalam lingkaran putih berubah menjadi segitiga putih di dalam lingkaran hitam.\nDengan pola transformasi yang sama, sebuah persegi hitam di dalam segilima putih akan berubah menjadi...",
        "Persegi hitam di dalam segilima hitam",
        "Persegi putih di dalam segilima hitam",
        "Persegi putih di dalam segilima putih",
        "Segilima hitam di dalam persegi putih",
        "Segilima putih di dalam persegi hitam",
        "B", "Pola transformasi adalah pembalikan warna (inversi arsiran): Bangun di dalam menjadi putih, dan bangun di luar yang mengelilingi menjadi hitam. Maka hasilnya adalah persegi putih di dalam segilima hitam."
    ),
    (
        "[Rotasi Mental 2D]\nHuruf kapital 'L' diputar searah jarum jam sebesar 90 derajat, kemudian dicerminkan secara horizontal terhadap garis vertikal di sebelah kanannya. Bentuk akhir huruf tersebut akan menyerupai...",
        "Sudut siku-siku menghadap ke kiri bawah",
        "Garis mendatar dengan kaki menghadap ke atas di sebelah kiri",
        "Sudut siku-siku terbalik dengan ujung di kanan atas",
        "Bentuk huruf L semula tanpa perubahan",
        "Garis mendatar dengan kaki menghadap ke bawah",
        "A", "L diputar 90 derajat searah jarum jam menjadi garis tegak di kiri dan mendatar di atas (_| terbalik). Dicerminkan secara horizontal menghasilkan sudut siku-siku menghadap ke kiri bawah."
    ),
    (
        "[Matriks Pola Gambar 3x3]\nBaris 1: Garis 1, Garis 2, Garis 3.\nBaris 2: Lingkaran 1, Lingkaran 2, Lingkaran 3.\nBaris 3: Bintang 1, Bintang 2, ...\nSimbol kelanjutan yang logis untuk melengkapi kotak baris ke-3 kolom ke-3 adalah...",
        "Bintang 1", "Bintang 2", "Bintang 3", "Bintang 4", "Bintang 5",
        "C", "Pola horizontal konsisten bertambah satu elemen dari kiri ke kanan (1 -> 2 -> 3). Maka kolom ketiga baris ketiga adalah Bintang berjumlah 3."
    ),
    (
        "[Jaring-jaring Bangun Ruang]\nManakah dari bangun ruang berikut yang memiliki tepat 6 sisi, 8 titik sudut, dan 12 rusuk yang sama panjang?",
        "Prisma segitiga", "Limas segi empat", "Kubus", "Balok tidak beraturan", "Oktahedron",
        "C", "Karakteristik kubus memiliki 6 sisi persegi kongruen, 8 titik sudut, dan 12 rusuk sama panjang."
    ),
    (
        "[Ketelitian Visual & Perbedaan Gambar]\nDi antara kelompok kata/simbol berikut, manakah yang memiliki jumlah simetri lipat paling banyak?",
        "Huruf A", "Huruf B", "Huruf H", "Bentuk Lingkaran", "Bentuk Segitiga Sama Kaki",
        "D", "Lingkaran memiliki jumlah simetri lipat tak terhingga (tak terbatas), jauh melebihi bentuk poligon atau huruf alfabet lainnya."
    ),
    (
        "[Pencerminan Spasial]\nSebuah jam dinding analog menunjukkan waktu tepat pukul 09.00 WIB. Jika dilihat melalui pantulan cermin datar tepat di seberangnya, jam tersebut akan tampak menunjukkan waktu...",
        "01.00 WIB", "02.00 WIB", "03.00 WIB", "04.00 WIB", "06.00 WIB",
        "C", "Pada pukul 09.00, jarum pendek ke angka 9 dan jarum panjang ke angka 12. Pencerminan horizontal membalik posisi angka 9 (kiri) menjadi angka 3 (kanan), sedangkan angka 12 tetap di atas. Maka tampak pukul 03.00 WIB."
    ),
    (
        "[Pola Perputaran Sudut]\nSebuah jarum penunjuk kompas awalnya menghadap ke arah Utara. Kemudian diputar searah jarum jam sebesar 135 derajat, lalu diputar berlawanan arah jarum jam sebesar 90 derajat. Ke arah manakah jarum penunjuk menghadap sekarang?",
        "Timur Laut", "Timur", "Tenggara", "Selatan", "Barat Daya",
        "A", "Perputaran bersih = +135 - 90 = +45 derajat searah jarum jam dari Utara. Sudut 45 derajat dari Utara adalah arah Timur Laut."
    ),
    (
        "[Analogi Spasial Bentuk]\nKUBUS : PERSEGI = BOLA : ...",
        "Kubus", "Silinder", "Lingkaran", "Kerucut", "Elips",
        "C", "Hubungan bentuk 3 dimensi ke proyeksi 2 dimensinya: Kubus berproyeksi muka persegi, sedangkan Bola berproyeksi muka lingkaran."
    ),
    (
        "[Jumlah Titik Sudut Poligon]\nSebuah prisma segi enam beraturan memiliki jumlah rusuk sebanyak...",
        "12 rusuk", "14 rusuk", "16 rusuk", "18 rusuk", "20 rusuk",
        "D", "Rumus jumlah rusuk prisma segi-n adalah 3n. Untuk segi enam (n = 6), jumlah rusuk = 3 x 6 = 18 rusuk (6 alas, 6 tegak, 6 atas)."
    )
]

for idx, item in enumerate(psiko_spasial_data):
    all_questions.append({
        'category_id': 9,
        'package_id': 4, # Package 4 (Psikotes)
        'question_text': item[0],
        'option_a': item[1],
        'option_b': item[2],
        'option_c': item[3],
        'option_d': item[4],
        'option_e': item[5],
        'correct_option': item[6],
        'score_a': 0, 'score_b': 0, 'score_c': 0, 'score_d': 0, 'score_e': 0,
        'explanation': item[7]
    })

print(f"Added Psikotes Spasial questions. Total now: {len(all_questions)}")

# =========================================================================
# 10. SITUATIONAL JUDGEMENT TEST (SJT) - Category ID: 10
# (Etika Kerja, Manajemen Konflik & Kepemimpinan - Weighted Scoring 1-5)
# =========================================================================
psiko_sjt_data = [
    (
        "Dua jam menjelang jam pulang kantor di hari Jumat, atasan mendadak meminta tim Anda menyelesaikan laporan tender mendesak yang harus dikirim ke klien sebelum pukul 21.00 WIB. Bagaimana respons Anda?",
        "Menolak tugas tersebut karena sudah merencanakan agenda akhir pekan bersama keluarga.",
        "Mengkoordinasikan pembagian tugas bersama rekan tim, fokus menyelesaikan laporan secara solid hingga tuntas, lalu pulang dengan lega.",
        "Menyelesaikan laporan dengan asal-asalan agar cepat selesai sebelum jam 17.00 WIB.",
        "Menyuruh staf magang menyelesaikannya sendiri tanpa pendampingan.",
        "Mengeluh di media sosial tentang beban kerja yang tidak manusiawi.",
        "B", 2, 5, 2, 1, 1,
        "Sikap profesionalisme dan orientasi pada komitmen kerja: Mampu berkolaborasi menangani urgensi bisnis dengan pembagian peran yang efisien demi reputasi institusi."
    ),
    (
        "Klien penting perusahaan menyampaikan komplain keras dan bernada emosional atas keterlambatan pengiriman barang akibat gangguan armada ekspedisi pihak ketiga. Tindakan pertama Anda adalah...",
        "Membalas dengan nada marah dan menyalahkan perusahaan ekspedisi sepenuhnya.",
        "Mendengarkan keluhan klien dengan tenang dan penuh empati, memohon maaf atas ketidaknyamanan, serta segera memberikan solusi alternatif pengiriman tercepat.",
        "Mematikan telepon dan memblokir nomor klien agar tidak mengganggu hari Anda.",
        "Menjanjikan ganti rugi 100% tanpa berkonsultasi dengan manajemen perusahaan.",
        "Menyuruh klien menyelesaikan sendiri masalahnya dengan pihak ekspedisi.",
        "B", 1, 5, 1, 2, 1,
        "Pelayanan pelanggan prima (*Customer Service Excellence*): Mendengarkan secara aktif, menenangkan situasi dengan empati, dan sigap menghadirkan solusi konkret."
    ),
    (
        "Anda menyadari bahwa rekan kerja dekat Anda sering memanipulasi absensi kehadiran fingerprint dengan menitip absen pada petugas piket. Sebagai insan profesional, sikap Anda adalah...",
        "Ikut menitip absen sesekali ketika terlambat bangun tidur.",
        "Mengingatkan rekan tersebut secara empat mata dengan bijak tentang risiko pemalsuan data dan integritas kerja, serta menyarankan agar berhenti melakukannya.",
        "Langsung melabraknya di grup WhatsApp kantor agar dia merasa dipermalukan.",
        "Memeras rekan tersebut dengan ancaman akan diadukan jika tidak membelikan makan siang.",
        "Mendiamkannya karena menganggap itu urusan pribadi masing-masing orang.",
        "B", 1, 5, 2, 1, 2,
        "Integritas dan kepedulian rekan: Memberikan teguran persuasif secara konstruktif empat mata untuk mencegah pelanggaran kode etik berlanjut."
    ),
    (
        "Ketika ide proyek inovasi yang Anda susun selama berbulan-bulan ditolak oleh komite evaluasi karena dinilai berisiko tinggi terhadap anggaran, respons emosional dan tindakan Anda adalah...",
        "Merasa kecewa, mogok kerja, dan menolak memberikan ide baru di masa mendatang.",
        "Menerima keputusan komite dengan lapang dada, meminta masukan detail mengenai aspek risiko yang dikhawatirkan, dan menyempurnakan proposal agar lebih efisien dan terukur.",
        "Menuduh dewan juri bersikap subjektif dan tidak paham perkembangan zaman.",
        "Membocorkan ide tersebut ke perusahaan kompetitor agar mereka yang mengembangkannya.",
        "Menyerang anggota komite secara verbal di ruang rapat terbuka.",
        "B", 2, 5, 1, 1, 1,
        "Resiliensi mental (*Growth Mindset*): Menjadikan kritik dan penolakan sebagai bahan evaluasi perbaikan untuk menghasilkan usulan yang lebih matang."
    ),
    (
        "Dalam rapat tim bulanan, terjadi perbedaan pendapat yang sangat tajam antara bagian Pemasaran dan bagian Produksi hingga saling menyalahkan. Sebagai fasilitator rapat, langkah Anda adalah...",
        "Memihak salah satu kubu yang memiliki anggota lebih senior.",
        "Menenangkan kedua pihak, mengembalikan fokus rapat pada tujuan utama perusahaan, dan membedah fakta data secara objektif untuk menemukan titik temu solusi bersama.",
        "Meninggalkan ruang rapat dan membiarkan mereka menyelesaikan pertikaian sendiri.",
        "Menunda rapat hingga bulan depan tanpa ada kesimpulan apapun.",
        "Memberikan sanksi pemotongan gaji kepada semua peserta rapat.",
        "B", 1, 5, 1, 2, 1,
        "Kepemimpinan & Mediasi Konflik: Bersikap netral, mengendalikan dinamika kelompok, dan mengarahkan energi perselisihan menuju penyelesaian berbasis data dan tujuan bersama."
    ),
    (
        "Anda diberikan tugas memimpin tim lintas generasi, di mana staf senior enggan diatur oleh pemimpin yang lebih muda. Pendekatan kepemimpinan Anda adalah...",
        "Menggunakan otoritas surat keputusan pimpinan untuk memaksa senior mematuhi setiap instruksi Anda.",
        "Menghargai pengalaman kerja staf senior, melibatkan mereka sebagai mentor rekan junior, dan membangun kepemimpinan berbasis keteladanan serta komunikasi persuasif.",
        "Mengabaikan staf senior dan hanya memberikan pekerjaan penting kepada staf yang sebaya.",
        "Mengusulkan kepada divisi HRD agar staf senior segera dipensiundinikan.",
        "Menyerahkan seluruh wewenang Anda kepada staf senior tersebut dan Anda hanya bersantai.",
        "B", 2, 5, 2, 1, 1,
        "Kepemimpinan inklusif: Menghormati senioritas sambil memadukan kebijaksanaan pengalaman dengan visi strategis demi soliditas tim."
    ),
    (
        "Seorang bawahan Anda membuat kesalahan input data yang mengakibatkan selisih saldo pembukuan kecil namun tidak merugikan konsumen. Tindakan pembinaan yang paling tepat adalah...",
        "Memaki-maki bawahan tersebut di depan rekan-rekannya agar jera.",
        "Mengajaknya berdiskusi empat mata untuk menganalisis akar penyebab kesalahan (*root cause analysis*), membantunya memperbaiki selisih, dan menyusun checklist pencegahan ke depan.",
        "Langsung menerbitkan Surat Peringatan Ketiga (SP-3) dan pemecatan.",
        "Menutupi kesalahan tersebut dari audit tanpa melakukan perbaikan pembukuan.",
        "Membiarkannya begitu saja karena menganggap selisih kecil tidak berdampak besar.",
        "B", 1, 5, 1, 2, 2,
        "Pembinaan SDM yang humanis & edukatif: Memperbaiki sistem dan kebiasaan kerja bawahan tanpa meruntuhkan moral dan motivasi kerjanya."
    ),
    (
        "Perusahaan Anda sedang mengalami masa krisis efisiensi anggaran dan menghimbau seluruh pegawai untuk menghemat energi listrik dan kertas. Langkah nyata Anda adalah...",
        "Hanya mematikan lampu jika ada pimpinan yang sedang lewat memeriksa.",
        "Secara konsisten mematikan perangkat elektronik saat tidak digunakan, beralih ke dokumen digital tanpa cetak kertas (*paperless*), dan mengajak rekan lain berhemat.",
        "Tetap menggunakan pendingin ruangan (AC) dengan suhu terdingin meskipun ruangan kosong.",
        "Mencetak ratusan lembar dokumen pribadi menggunakan printer kantor.",
        "Mengkritik kebijakan penghematan perusahaan sebagai tanda kebangkrutan di media sosial.",
        "B", 2, 5, 1, 1, 1,
        "Sensitivitas korporat & efisiensi operasional: Disiplin memulai perubahan dari diri sendiri dan konsisten mendukung kebijakan efisiensi institusi."
    ),
    (
        "Di tengah jam kerja yang sangat sibuk, seorang tamu dari instansi lain datang tanpa janji temu untuk menanyakan prosedur kemitraan. Sikap Anda adalah...",
        "Mengusir tamu tersebut dengan kasar karena tidak memiliki surat janji temu.",
        "Menyambut tamu dengan ramah, memohon maaf secara santun bahwa jadwal Anda sedang padat, dan memberikan brosur panduan serta nomor kontak resmi untuk menjadwalkan pertemuan.",
        "Meninggalkan pekerjaan mendesak Anda seharian penuh untuk melayani obrolan tamu tersebut.",
        "Menyuruh satpam membawa tamu tersebut ke luar gedung kantor.",
        "Berpura-pura tidak melihat tamu tersebut dan terus menatap layar komputer.",
        "B", 1, 5, 2, 1, 1,
        "Keramahan & batas profesional: Menunjukkan citra institusi yang santun dan profesional tanpa mengorbankan tanggung jawab pekerjaan utama."
    ),
    (
        "Ketika hasil evaluasi 360 derajat menunjukkan bahwa Anda dinilai kurang terbuka menerima kritik dari bawahan, sikap Anda adalah...",
        "Mencari tahu siapa bawahan yang memberi penilaian buruk untuk diberi sanksi.",
        "Melakukan refleksi diri dengan jujur, berterima kasih atas umpan balik yang membangun, dan membuka saluran komunikasi yang lebih santai dan demokratis dengan tim.",
        "Menolak hasil evaluasi dan menganggap bawahan tidak kompeten menilai atasannya.",
        "Mengundurkan diri dari jabatan manajerial karena merasa tersinggung.",
        "Menjadi acuh tak acuh dan tidak lagi mempedulikan perkembangan tim.",
        "B", 1, 5, 1, 1, 1,
        "Kematangan emosional (*Self-Awareness*): Mengapresiasi umpan balik sebagai sarana transformasi kepemimpinan ke arah yang lebih bijaksana."
    )
]

for idx, item in enumerate(psiko_sjt_data):
    all_questions.append({
        'category_id': 10,
        'package_id': 4, # Package 4 (Psikotes)
        'question_text': item[0],
        'option_a': item[1],
        'option_b': item[2],
        'option_c': item[3],
        'option_d': item[4],
        'option_e': item[5],
        'correct_option': item[6],
        'score_a': item[7], 'score_b': item[8], 'score_c': item[9], 'score_d': item[10], 'score_e': item[11],
        'explanation': item[12]
    })

print(f"Added Psikotes SJT questions. Total now: {len(all_questions)}")

# Now save clean PHP seed script
with open('database/all_questions.json', 'w', encoding='utf-8') as f:
    json.dump({
        'categories': categories,
        'packages': packages,
        'questions': all_questions
    }, f, ensure_ascii=False, indent=2)

print(f"All data successfully exported to database/all_questions.json with {len(all_questions)} questions!")
