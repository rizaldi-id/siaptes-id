<?php
// syarat-ketentuan.php
$page_title = "Syarat & Ketentuan Layanan";
$meta_description = "Syarat dan ketentuan resmi penggunaan layanan platform tryout dan simulasi ujian SIAP TES (siaptes.id).";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 920px; margin: 2rem auto; padding: 0 1rem;">
    <!-- Breadcrumb -->
    <div style="font-size: 0.85rem; color: var(--core-text-muted); margin-bottom: 1.25rem;">
        <a href="index.php" style="color: var(--core-navy); text-decoration: none;">Beranda</a> &rsaquo; <span>Syarat & Ketentuan</span>
    </div>

    <div class="card card-navy-top" style="padding: 2.5rem 2rem;">
        <div style="text-align: center; margin-bottom: 2rem; border-bottom: 1px solid var(--core-border); padding-bottom: 1.5rem;">
            <span class="badge badge-gold" style="margin-bottom: 0.5rem;">DOKUMEN LEGALITAS LAYANAN</span>
            <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--core-navy); margin: 0 0 0.5rem 0;">
                Syarat & Ketentuan Layanan
            </h1>
            <p style="color: var(--core-text-muted); font-size: 0.88rem; margin: 0;">
                Terakhir Diperbarui: <?= date('d F Y') ?> &bull; Berlaku untuk seluruh pengguna <b>siaptes.id</b>
            </p>
        </div>

        <div style="line-height: 1.8; color: var(--core-text); font-size: 0.95rem;">
            <h3 style="color: var(--core-navy); font-weight: 700; margin-top: 1.5rem; margin-bottom: 0.5rem;">1. Ketentuan Umum</h3>
            <p>
                Selamat datang di <b>SIAP TES</b> (dapat diakses melalui situs web resmi <b>https://siaptes.id</b>). Platform ini dikelola dan dioperasikan sebagai sarana edukasi daring, penyedia bank soal, dan simulasi ujian berbasis komputer (<i>Computer Assisted Test</i> / CAT) untuk seleksi nasional (CPNS, PPPK, Rekrutmen Bersama BUMN, Sekolah Kedinasan, serta Psikotes Kerja).
            </p>
            <p>
                Dengan mendaftar, mengakses, atau menggunakan layanan di <b>siaptes.id</b>, Anda menyatakan bahwa Anda telah membaca, memahami, dan menyetujui untuk terikat oleh Syarat dan Ketentuan ini. Jika Anda tidak menyetujui salah satu ketentuan, Anda dipersilakan untuk tidak menggunakan platform ini.
            </p>

            <h3 style="color: var(--core-navy); font-weight: 700; margin-top: 1.75rem; margin-bottom: 0.5rem;">2. Pendaftaran Akun & Keamanan</h3>
            <ul>
                <li>Pengguna wajib memberikan data yang valid, akurat, dan dapat dipertanggungjawabkan saat melakukan pendaftaran akun (nama, email aktif, dan kata sandi).</li>
                <li>Setiap akun bersifat personal dan tidak diperkenankan untuk dipindahtangankan, dijual, atau disewakan kepada pihak lain tanpa persetujuan tertulis dari pihak SIAP TES.</li>
                <li>Pengguna bertanggung jawab penuh atas kerahasiaan kredensial akun dan seluruh aktivitas yang terjadi dalam akun tersebut.</li>
            </ul>

            <h3 style="color: var(--core-navy); font-weight: 700; margin-top: 1.75rem; margin-bottom: 0.5rem;">3. Deskripsi Layanan & Mekanisme Penyerahan Produk Digital (Fulfillment)</h3>
            <ul>
                <li><b>Jenis Produk:</b> SIAP TES menyediakan produk digital non-fisik berupa akses ke modul simulasi ujian CAT interaktif, bank latihan soal lengkap beserta kunci jawaban dan pembahasan mendalam, analisis perankingan nasional, serta materi pembelajaran.</li>
                <li><b>Mekanisme Penyerahan (Instant Digital Fulfillment):</b> Seluruh produk bersifat digital dan diserahkan secara instan melalui sistem platform. Tidak ada pengiriman barang fisik melalui jasa kurir/ekspedisi.</li>
                <li><b>Aktivasi Akses PRO VIP:</b> Setelah pembayaran diverifikasi sukses melalui gateway pembayaran resmi (Midtrans QRIS), status akun pengguna akan otomatis ditingkatkan menjadi <b>PRO VIP All-Access</b> secara seketika (real-time) sehingga pengguna dapat langsung mengakses seluruh fitur premium saat itu juga.</li>
            </ul>

            <h3 style="color: var(--core-navy); font-weight: 700; margin-top: 1.75rem; margin-bottom: 0.5rem;">4. Kebijakan Harga & Pembayaran</h3>
            <ul>
                <li>Harga resmi paket keanggotaan PRO VIP All-Access adalah sebesar <b>Rp 29.000,- (Dua Puluh Sembilan Ribu Rupiah)</b> atau sesuai dengan promo yang tertera secara sah pada halaman peningkatan akun (<i>upgrade</i>).</li>
                <li>Pembayaran diproses secara aman menggunakan mitra <i>Payment Gateway</i> resmi berlisensi Bank Indonesia (<b>Midtrans</b>) melalui kanal QRIS Standar Nasional dan metode pembayaran sah lainnya.</li>
                <li>SIAP TES tidak pernah menyimpan nomor kartu kredit atau PIN/kata sandi rekening perbankan pengguna. Seluruh transmisi pembayaran dienkripsi menggunakan protokol keamanan standar industri (SSL/TLS).</li>
            </ul>

            <h3 style="color: var(--core-navy); font-weight: 700; margin-top: 1.75rem; margin-bottom: 0.5rem;">5. Hak Kekayaan Intelektual</h3>
            <p>
                Seluruh materi, susunan soal, sistem CAT, kode pemrograman, desain antarmuka, logo, merek, dan grafis pada <b>siaptes.id</b> dilindungi oleh Undang-Undang Hak Cipta Republik Indonesia. Pengguna dilarang keras untuk:
            </p>
            <ul>
                <li>Menggandakan, menyebarluaskan, memperjualbelikan, atau mendistribusikan bank soal dan pembahasan SIAP TES ke platform lain tanpa izin tertulis.</li>
                <li>Melakukan tindakan peretasan, rekayasa balik (<i>reverse engineering</i>), atau penggunaan perangkat lunak otomatis (bot/scraper) untuk mengambil data dari situs web ini.</li>
            </ul>

            <h3 style="color: var(--core-navy); font-weight: 700; margin-top: 1.75rem; margin-bottom: 0.5rem;">6. Batasan Tanggung Jawab & Penafian (Disclaimer)</h3>
            <p>
                Platform SIAP TES adalah media pelatihan dan simulasi mandiri independen yang dirancang untuk membantu kesiapan calon peserta ujian. SIAP TES bukan merupakan lembaga pemerintah dan tidak terafiliasi secara institusional dengan Badan Kepegawaian Negara (BKN), Kementerian PAN-RB, Forum Human Capital Indonesia (FHCI BUMN), atau instansi kedinasan manapun. Hasil latihan di platform ini merupakan tolok ukur kesiapan mandiri dan bukan jaminan kelulusan mutlak dalam seleksi resmi.
            </p>

            <h3 style="color: var(--core-navy); font-weight: 700; margin-top: 1.75rem; margin-bottom: 0.5rem;">7. Perubahan Ketentuan</h3>
            <p>
                SIAP TES berhak memperbarui atau memodifikasi Syarat dan Ketentuan ini sewaktu-waktu demi menyesuaikan dengan regulasi hukum atau peningkatan fitur layanan. Pembaruan akan berlaku segera setelah dipublikasikan pada halaman ini.
            </p>

            <h3 style="color: var(--core-navy); font-weight: 700; margin-top: 1.75rem; margin-bottom: 0.5rem;">8. Kontak & Bantuan Resmi</h3>
            <p>
                Jika Anda memiliki pertanyaan mengenai Syarat dan Ketentuan ini, silakan menghubungi tim layanan pelanggan kami melalui email: <a href="mailto:support@siaptes.id" style="color: var(--core-navy); font-weight: 700;">support@siaptes.id</a> atau melalui halaman <a href="kontak.php" style="color: var(--core-navy); font-weight: 700;">Kontak Kami</a>.
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
