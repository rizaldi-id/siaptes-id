<?php
// kebijakan-privasi.php
$page_title = "Kebijakan Privasi";
$meta_description = "Kebijakan privasi dan perlindungan data pribadi pengguna pada platform SIAP TES (siaptes.id).";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 920px; margin: 2rem auto; padding: 0 1rem;">
    <!-- Breadcrumb -->
    <div style="font-size: 0.85rem; color: var(--core-text-muted); margin-bottom: 1.25rem;">
        <a href="index.php" style="color: var(--core-navy); text-decoration: none;">Beranda</a> &rsaquo; <span>Kebijakan Privasi</span>
    </div>

    <div class="card card-navy-top" style="padding: 2.5rem 2rem;">
        <div style="text-align: center; margin-bottom: 2rem; border-bottom: 1px solid var(--core-border); padding-bottom: 1.5rem;">
            <span class="badge badge-gold" style="margin-bottom: 0.5rem;">PERLINDUNGAN DATA PRIBADI</span>
            <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--core-navy); margin: 0 0 0.5rem 0;">
                Kebijakan Privasi
            </h1>
            <p style="color: var(--core-text-muted); font-size: 0.88rem; margin: 0;">
                Terakhir Diperbarui: <?= date('d F Y') ?> &bull; Kepatuhan UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi (UU PDP)
            </p>
        </div>

        <div style="line-height: 1.8; color: var(--core-text); font-size: 0.95rem;">
            <h3 style="color: var(--core-navy); font-weight: 700; margin-top: 1.5rem; margin-bottom: 0.5rem;">1. Komitmen Privasi Kami</h3>
            <p>
                <b>SIAP TES</b> (<b>https://siaptes.id</b>) berkomitmen penuh untuk melindungi dan menghormati hak privasi setiap pengguna kami. Kebijakan Privasi ini menjelaskan dasar pengumpulan, pengolahan, penyimpanan, dan perlindungan data pribadi yang Anda berikan kepada kami ketika menggunakan platform edukasi daring dan simulasi ujian CAT kami.
            </p>

            <h3 style="color: var(--core-navy); font-weight: 700; margin-top: 1.75rem; margin-bottom: 0.5rem;">2. Informasi yang Kami Kumpulkan</h3>
            <p>Kami hanya mengumpulkan data yang diperlukan untuk penyelenggaraan layanan:</p>
            <ul>
                <li><b>Data Identitas Akun:</b> Nama lengkap, alamat surat elektronik (email), dan kata sandi yang telah dienkripsi secara aman (<i>hashed</i>).</li>
                <li><b>Data Riwayat Pembelajaran & Ujian:</b> Skor nilai latihan, riwayat tryout, waktu pengerjaan soal, paket materi yang diakses, serta status keanggotaan (Free Trial / PRO VIP).</li>
                <li><b>Data Transaksi Pembayaran:</b> Nomor invoice pembayaran, nominal transaksi, metode pembayaran (QRIS), waktu transaksi, dan status penyelesaian pembayaran yang dilaporkan oleh mitra payment gateway resmi kami (Midtrans). Kami <i>tidak pernah</i> menyimpan nomor kartu kredit atau kredensial perbankan sensitif pengguna.</li>
                <li><b>Data Teknis:</b> Alamat Protokol Internet (IP Address), jenis peramban (browser), dan cap waktu aktivitas untuk keperluan audit keamanan sistem dan pencegahan kecurangan.</li>
            </ul>

            <h3 style="color: var(--core-navy); font-weight: 700; margin-top: 1.75rem; margin-bottom: 0.5rem;">3. Tujuan Penggunaan Informasi</h3>
            <p>Informasi yang kami kumpulkan digunakan secara eksklusif untuk:</p>
            <ul>
                <li>Memfasilitasi pembuatan akun dan autentikasi login pengguna ke platform.</li>
                <li>Memproses transaksi pembayaran dan mengaktifkan akses paket PRO VIP secara otomatis.</li>
                <li>Menampilkan riwayat pengerjaan, skor perolehan, grafik perkembangan belajar, dan sertifikat hasil tryout.</li>
                <li>Memberikan layanan bantuan teknis (<i>customer support</i>) apabila pengguna mengalami kendala akses.</li>
                <li>Meningkatkan kualitas materi soal, performa antarmuka sistem, dan stabilitas server.</li>
            </ul>

            <h3 style="color: var(--core-navy); font-weight: 700; margin-top: 1.75rem; margin-bottom: 0.5rem;">4. Pembagian Informasi kepada Pihak Ketiga</h3>
            <p>
                <b>Kami tidak pernah menjual, menyewakan, atau memperdagangkan data pribadi Anda kepada pihak manapun untuk keperluan pemasaran pihak ketiga.</b>
            </p>
            <p>
                Data hanya dapat diteruskan secara terbatas kepada mitra tepercaya dalam lingkup pemrosesan layanan resmi:
            </p>
            <ul>
                <li><b>Mitra Gerbang Pembayaran Resmi (Midtrans / PT Midtrans):</b> Untuk keperluan validasi transaksi pembayaran QRIS secara aman sesuai regulasi Bank Indonesia.</li>
                <li><b>Kewajiban Hukum:</b> Apabila diwajibkan oleh peraturan perundang-undangan Republik Indonesia yang sah atau perintah pengadilan yang berkekuatan hukum tetap.</li>
            </ul>

            <h3 style="color: var(--core-navy); font-weight: 700; margin-top: 1.75rem; margin-bottom: 0.5rem;">5. Keamanan & Penyimpanan Data</h3>
            <p>
                SIAP TES menerapkan langkah-langkah pengamanan teknis dan organisasi berstandar industri, termasuk enkripsi data berlapis melalui protokol HTTPS/SSL (<i>Transport Layer Security</i>), proteksi basis data dengan firewall, serta kontrol akses ketat untuk mencegah akses tanpa hak, pengungkapan, atau modifikasi data pribadi yang tidak sah.
            </p>

            <h3 style="color: var(--core-navy); font-weight: 700; margin-top: 1.75rem; margin-bottom: 0.5rem;">6. Hak Pengguna</h3>
            <p>
                Sesuai dengan ketentuan Undang-Undang Pelindungan Data Pribadi (UU PDP), Anda berhak untuk mengakses informasi akun Anda, memperbarui atau memperbaiki ketidakakuratan data, serta mengajukan permohonan penghapusan akun dan data terkait melalui permohonan resmi ke saluran bantuan kami.
            </p>

            <h3 style="color: var(--core-navy); font-weight: 700; margin-top: 1.75rem; margin-bottom: 0.5rem;">7. Kontak Pengelola Data</h3>
            <p>
                Untuk pertanyaan, klarifikasi, atau permintaan pelaksanaan hak atas data pribadi Anda, silakan hubungi Tim Kepatuhan Privasi kami melalui:
            </p>
            <div style="background: #f8fafc; border: 1px solid var(--core-border); border-radius: var(--radius-sm); padding: 1rem 1.25rem; margin-top: 0.5rem;">
                <div><b>Pengelola Platform:</b> SIAP TES (siaptes.id)</div>
                <div><b>Email Layanan Privasi:</b> <a href="mailto:privacy@siaptes.id" style="color: var(--core-navy); font-weight: 700;">privacy@siaptes.id</a> / <a href="mailto:support@siaptes.id" style="color: var(--core-navy); font-weight: 700;">support@siaptes.id</a></div>
                <div><b>Layanan Pelanggan WhatsApp:</b> +62 812-3456-7890</div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
