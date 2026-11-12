<?php
// kontak.php
require_once __DIR__ . '/config/payment_gateway.php';
$page_title = "Hubungi Kami & Pusat Bantuan";
$meta_description = "Layanan pelanggan resmi SIAP TES (siaptes.id). Hubungi tim dukungan teknis, pertanyaan akun, dan bantuan pembayaran.";
require_once __DIR__ . '/includes/header.php';

$waNumber = defined('ADMIN_WHATSAPP_NUMBER') ? ADMIN_WHATSAPP_NUMBER : '6281234567890';
?>

<div class="container" style="max-width: 960px; margin: 2rem auto; padding: 0 1rem;">
    <!-- Breadcrumb -->
    <div style="font-size: 0.85rem; color: var(--core-text-muted); margin-bottom: 1.25rem;">
        <a href="index.php" style="color: var(--core-navy); text-decoration: none;">Beranda</a> &rsaquo; <span>Pusat Bantuan & Kontak</span>
    </div>

    <div class="card card-navy-top" style="padding: 2.5rem 2rem; margin-bottom: 2rem;">
        <div style="text-align: center; max-width: 650px; margin: 0 auto 2.5rem;">
            <span class="badge badge-gold" style="margin-bottom: 0.5rem;">LAYANAN PELANGGAN 24/7</span>
            <h1 style="font-size: 1.95rem; font-weight: 800; color: var(--core-navy); margin: 0 0 0.5rem 0;">
                Hubungi Kami & Pusat Dukungan
            </h1>
            <p style="color: var(--core-text-muted); font-size: 0.95rem; line-height: 1.6; margin: 0;">
                Tim dukungan <b>SIAP TES</b> siap membantu Anda seputar aktivasi akun PRO VIP, kendala pengerjaan simulasi ujian, pertanyaan seputar paket soal, serta konfirmasi pembayaran.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <!-- Box 1: WhatsApp -->
            <div style="background: #f8fafc; border: 1px solid var(--core-border); border-radius: var(--radius-md); padding: 1.5rem; text-align: center;">
                <div style="font-size: 2.2rem; margin-bottom: 0.5rem;">💬</div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--core-navy); margin: 0 0 0.4rem 0;">WhatsApp Customer Care</h3>
                <p style="font-size: 0.84rem; color: var(--core-text-muted); line-height: 1.5; margin-bottom: 1rem;">
                    Respon cepat untuk aktivasi akun dan kendala teknis simulasi.
                </p>
                <a href="https://api.whatsapp.com/send?phone=<?= $waNumber ?>&text=<?= urlencode('Halo Admin SIAPTES.ID, saya ingin bertanya seputar layanan tryout / pembayaran.') ?>" 
                   target="_blank" rel="noopener" 
                   class="btn btn-sm" 
                   style="background: #25d366; color: #fff; font-weight: 700; width: 100%; justify-content: center; padding: 0.65rem;">
                    Chat via WhatsApp ➔
                </a>
            </div>

            <!-- Box 2: Email -->
            <div style="background: #f8fafc; border: 1px solid var(--core-border); border-radius: var(--radius-md); padding: 1.5rem; text-align: center;">
                <div style="font-size: 2.2rem; margin-bottom: 0.5rem;">📧</div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--core-navy); margin: 0 0 0.4rem 0;">Email Resmi</h3>
                <p style="font-size: 0.84rem; color: var(--core-text-muted); line-height: 1.5; margin-bottom: 1rem;">
                    Pertanyaan kerjasama, aduan privasi, dan permohonan refund.
                </p>
                <a href="mailto:support@siaptes.id" 
                   class="btn btn-primary btn-sm" 
                   style="width: 100%; justify-content: center; padding: 0.65rem;">
                    support@siaptes.id
                </a>
            </div>

            <!-- Box 3: Jam Operasional -->
            <div style="background: #f8fafc; border: 1px solid var(--core-border); border-radius: var(--radius-md); padding: 1.5rem; text-align: center;">
                <div style="font-size: 2.2rem; margin-bottom: 0.5rem;">⏰</div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--core-navy); margin: 0 0 0.4rem 0;">Jam Layanan Helpdesk</h3>
                <p style="font-size: 0.84rem; color: var(--core-text-muted); line-height: 1.5; margin-bottom: 0.5rem;">
                    <b>Senin &ndash; Minggu:</b><br>08.00 &ndash; 22.00 WIB
                </p>
                <div style="font-size: 0.78rem; color: #16a34a; font-weight: 700; margin-top: 0.5rem;">
                    ● Sistem Tryout Berjalan Otomatis 24/7
                </div>
            </div>
        </div>

        <!-- Detail Informasi Usaha / Legal Merchant -->
        <div style="border-top: 1px solid var(--core-border); padding-top: 2rem;">
            <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--core-navy); margin-bottom: 1rem;">
                🏢 Informasi Pengelola Platform
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; font-size: 0.9rem; line-height: 1.7;">
                <div>
                    <div style="color: var(--core-text-muted); font-size: 0.82rem;">Nama Platform:</div>
                    <div style="font-weight: 700; color: var(--core-navy);">SIAP TES (siaptes.id)</div>
                    
                    <div style="color: var(--core-text-muted); font-size: 0.82rem; margin-top: 0.65rem;">Jenis Layanan:</div>
                    <div>Platform Pembelajaran Daring & Simulasi Ujian CAT Terpadu</div>

                    <div style="color: var(--core-text-muted); font-size: 0.82rem; margin-top: 0.65rem;">Status Lisensi Sistem:</div>
                    <div>Hak Cipta Terdaftar & Dilindungi Undang-Undang RI</div>
                </div>

                <div>
                    <div style="color: var(--core-text-muted); font-size: 0.82rem;">Alamat Operasional:</div>
                    <div>Gedung Edukasi Digital Nusantara, Jakarta, Indonesia</div>

                    <div style="color: var(--core-text-muted); font-size: 0.82rem; margin-top: 0.65rem;">Mitra Pembayaran Resmi:</div>
                    <div>Midtrans (PT Midtrans) & Standar QRIS Bank Indonesia</div>

                    <div style="color: var(--core-text-muted); font-size: 0.82rem; margin-top: 0.65rem;">Tautan Legalitas:</div>
                    <div>
                        <a href="syarat-ketentuan.php" style="color: var(--core-navy); text-decoration: underline;">Syarat & Ketentuan</a> &bull; 
                        <a href="kebijakan-privasi.php" style="color: var(--core-navy); text-decoration: underline;">Kebijakan Privasi</a> &bull; 
                        <a href="kebijakan-pengembalian-dana.php" style="color: var(--core-navy); text-decoration: underline;">Kebijakan Refund</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
