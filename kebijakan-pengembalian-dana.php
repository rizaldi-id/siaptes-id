<?php
// kebijakan-pengembalian-dana.php
$page_title = "Kebijakan Pengembalian Dana & Pembatalan";
$meta_description = "Kebijakan pengembalian dana (refund) dan pembatalan transaksi pada platform SIAP TES (siaptes.id).";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 920px; margin: 2rem auto; padding: 0 1rem;">
    <!-- Breadcrumb -->
    <div style="font-size: 0.85rem; color: var(--core-text-muted); margin-bottom: 1.25rem;">
        <a href="index.php" style="color: var(--core-navy); text-decoration: none;">Beranda</a> &rsaquo; <span>Kebijakan Pengembalian Dana</span>
    </div>

    <div class="card card-navy-top" style="padding: 2.5rem 2rem;">
        <div style="text-align: center; margin-bottom: 2rem; border-bottom: 1px solid var(--core-border); padding-bottom: 1.5rem;">
            <span class="badge badge-gold" style="margin-bottom: 0.5rem;">TRANSPARANSI KONSUMEN</span>
            <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--core-navy); margin: 0 0 0.5rem 0;">
                Kebijakan Pengembalian Dana (Refund) & Pembatalan
            </h1>
            <p style="color: var(--core-text-muted); font-size: 0.88rem; margin: 0;">
                Terakhir Diperbarui: <?= date('d F Y') ?> &bull; Panduan Resmi Transaksi Pembayaran <b>siaptes.id</b>
            </p>
        </div>

        <div style="line-height: 1.8; color: var(--core-text); font-size: 0.95rem;">
            <h3 style="color: var(--core-navy); font-weight: 700; margin-top: 1.5rem; margin-bottom: 0.5rem;">1. Sifat Produk Digital & Akses Instan</h3>
            <p>
                Layanan yang disediakan oleh <b>SIAP TES</b> (<b>https://siaptes.id</b>) adalah <b>Produk Digital / Konten Non-Fisik Daring (Digital Goods)</b> berupa hak akses penuh ke bank latihan soal CAT, materi pembahasan, modul tryout simulasi seleksi nasional, dan analitik performa.
            </p>
            <p>
                Karena akses ke produk diserahkan secara instan dan seketika saat pembayaran terkonfirmasi berhasil oleh gateway pembayaran, maka pada prinsip umumnya <b>transaksi yang telah berhasil tidak dapat dibatalkan atau dikembalikan (<i>non-refundable</i>)</b> kecuali memenuhi syarat-syarat khusus sebagaimana dijelaskan di bawah ini.
            </p>

            <h3 style="color: var(--core-navy); font-weight: 700; margin-top: 1.75rem; margin-bottom: 0.5rem;">2. Kondisi yang Berhak Mendapatkan Pengembalian Dana (Refund)</h3>
            <p>SIAP TES dengan senang hati memberikan pengembalian dana 100% kepada pengguna dalam situasi berikut:</p>
            <ol>
                <li>
                    <b>Pembayaran Ganda (Double Payment):</b> Pengguna secara tidak sengaja terpotong saldo atau membayar lebih dari satu kali untuk nomor invoice/pesanan yang sama.
                </li>
                <li>
                    <b>Kendala Teknis Server yang Tidak Terselesaikan:</b> Pembayaran pengguna telah sukses terdebet dari rekening/e-wallet pengguna dan terkonfirmasi oleh Midtrans, namun sistem gagal mengaktifkan status akun PRO VIP dan tim teknis kami tidak dapat mengaktifkan hak akses tersebut dalam waktu 1x24 jam sejak laporan diterima.
                </li>
                <li>
                    <b>Salah Nominal Pembayaran:</b> Terjadi kelebihan transfer di luar nominal tagihan resmi Rp 29.000,- yang tertera pada sistem.
                </li>
            </ol>

            <h3 style="color: var(--core-navy); font-weight: 700; margin-top: 1.75rem; margin-bottom: 0.5rem;">3. Kondisi yang Tidak Memenuhi Syarat Refund</h3>
            <p>Pengembalian dana tidak dapat disetujui dalam kondisi:</p>
            <ul>
                <li>Pengguna berubah pikiran setelah akun PRO VIP berhasil diaktifkan dan modul ujian telah dapat diakses/dikerjakan.</li>
                <li>Pengguna tidak lulus dalam ujian resmi kedinasan/CPNS/BUMN yang sebenarnya (karena platform SIAP TES adalah media latihan mandiri, bukan lembaga penentu kelulusan instansi negara).</li>
                <li>Akun pengguna dinonaktifkan atau dibekukan akibat pelanggaran berat terhadap Syarat & Ketentuan (seperti membocorkan soal secara ilegal atau mencoba meretas sistem).</li>
            </ul>

            <h3 style="color: var(--core-navy); font-weight: 700; margin-top: 1.75rem; margin-bottom: 0.5rem;">4. Prosedur & Tata Cara Pengajuan Refund</h3>
            <p>
                Untuk mengajukan pengembalian dana, pengguna wajib mengirimkan permohonan maksimal dalam waktu <b>3x24 jam</b> sejak tanggal transaksi dengan melampirkan:
            </p>
            <ul>
                <li>Nomor Invoice transaksi (contoh: <code>INV-QRIS-2026xxxx-xxxxx</code>).</li>
                <li>Alamat email akun yang terdaftar di <b>siaptes.id</b>.</li>
                <li>Tangkapan layar (screenshot) bukti pembayaran resmi dari aplikasi m-banking atau e-wallet.</li>
                <li>Informasi rekening bank atau nomor e-wallet tujuan pengembalian dana (harus atas nama yang sama dengan pembayar).</li>
            </ul>
            <p>Kirimkan data permohonan tersebut ke:</p>
            <div style="background: #f8fafc; border: 1px solid var(--core-border); border-radius: var(--radius-sm); padding: 1rem 1.25rem;">
                <div>📧 <b>Email Helpdesk:</b> <a href="mailto:support@siaptes.id" style="color: var(--core-navy); font-weight: 700;">support@siaptes.id</a> (Subjek: Permohonan Refund - [Nomor Invoice])</div>
                <div>💬 <b>WhatsApp Customer Care:</b> +62 812-3456-7890</div>
            </div>

            <h3 style="color: var(--core-navy); font-weight: 700; margin-top: 1.75rem; margin-bottom: 0.5rem;">5. Waktu Pemrosesan Pengembalian Dana</h3>
            <p>
                Setelah tim verifikasi kami menelaah dan menyetujui permohonan refund Anda, dana akan dikembalikan ke rekening/e-wallet Anda dalam jangka waktu <b>1 hingga 3 hari kerja</b> melalui mekanisme transfer bank atau pembatalan transaksi resmi mitra payment gateway (Midtrans).
            </p>

            <h3 style="color: var(--core-navy); font-weight: 700; margin-top: 1.75rem; margin-bottom: 0.5rem;">6. Pembatalan Transaksi yang Masih Berstatus Pending</h3>
            <p>
                Apabila Anda telah membuat kode QRIS namun belum melakukan pembayaran dan ingin membatalkannya, Anda dapat langsung menekan tombol <b>"Batalkan Tagihan"</b> pada halaman checkout pembayaran atau cukup membiarkannya hingga batas waktu pembayaran 24 jam kedaluwarsa secara otomatis tanpa dikenakan biaya apapun.
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
