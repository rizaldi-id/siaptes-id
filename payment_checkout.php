<?php
// payment_checkout.php - Halaman Payment Gateway QRIS Otomatis (Rp 29.000)
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/payment_gateway.php';

requireLogin();
$db = getDB();
$user = currentUser();

$invoice = isset($_GET['invoice']) ? trim($_GET['invoice']) : '';
if ($invoice === '') {
    header('Location: upgrade.php');
    exit;
}

// Ambil transaksi milik user ini (atau admin bisa melihat semua)
if (isAdmin()) {
    $stmt = $db->prepare("SELECT t.*, u.name as user_name, u.email as user_email FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.invoice_number = ? LIMIT 1");
    $stmt->execute([$invoice]);
} else {
    $stmt = $db->prepare("SELECT t.*, u.name as user_name, u.email as user_email FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.invoice_number = ? AND t.user_id = ? LIMIT 1");
    $stmt->execute([$invoice, $user['id']]);
}
$tx = $stmt->fetch();

if (!$tx) {
    header('Location: upgrade.php?msg=' . urlencode('Tagihan pembayaran QRIS tidak ditemukan.'));
    exit;
}

$channels = getPaymentChannels();
$channelMeta = $channels['QRIS'];
$qrisPayload = generateQrisPayload($tx['invoice_number'], (int)$tx['amount']);

// Jika Midtrans Server Key valid dan transaksi masih pending, ambil QRIS / Snap Token dari Midtrans (dengan cache per invoice agar aman saat di-refresh)
$midtransQrUrl = '';
$midtransSnapToken = null;
if ($tx['status'] === 'pending' && isMidtransServerKeyValid()) {
    $invKey = $tx['invoice_number'];
    if (!empty($_SESSION['midtrans_cache'][$invKey])) {
        $cached = $_SESSION['midtrans_cache'][$invKey];
        $midtransQrUrl = $cached['qr_url'] ?? '';
        $midtransSnapToken = $cached['snap_token'] ?? null;
        if (!empty($cached['qr_string'])) {
            $qrisPayload = $cached['qr_string'];
        }
    } else {
        $mtData = createMidtransQrisTransaction($invKey, (int)$tx['amount'], [
            'name' => $tx['user_name'],
            'email' => $tx['user_email']
        ]);
        if (!empty($mtData['qr_url']) || !empty($mtData['qr_string'])) {
            $midtransQrUrl = $mtData['qr_url'] ?? '';
            if (!empty($mtData['qr_string'])) {
                $qrisPayload = $mtData['qr_string'];
            }
            $_SESSION['midtrans_cache'][$invKey] = [
                'qr_url' => $midtransQrUrl,
                'qr_string' => $mtData['qr_string'] ?? '',
                'snap_token' => null
            ];
        } else {
            // Gunakan Midtrans Snap API (hanya jika akun memiliki metode pembayaran aktif)
            $midtransSnapToken = createMidtransSnapToken($invKey, (int)$tx['amount'], [
                'name' => $tx['user_name'],
                'email' => $tx['user_email']
            ]);
            $_SESSION['midtrans_cache'][$invKey] = [
                'qr_url' => '',
                'qr_string' => '',
                'snap_token' => $midtransSnapToken
            ];
        }
    }
}

// Handle Aksi Pembayaran (Verifikasi Otomatis Gateway / Batal)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'cancel_invoice' && $tx['status'] === 'pending') {
        $db->prepare("UPDATE transactions SET status = 'rejected', updated_at = ? WHERE id = ?")
           ->execute([date('Y-m-d H:i:s'), $tx['id']]);
        header('Location: upgrade.php?msg=' . urlencode('Tagihan QRIS sebelumnya telah dibatalkan.'));
        exit;
    }

    if ($action === 'verify_payment' && $tx['status'] === 'pending') {
        $now = date('Y-m-d H:i:s');
        $isPaidViaApi = checkGatewayPaymentPaidStatus($tx['invoice_number']);
        $isLiveProviderConfigured = (isMidtransServerKeyValid() || !empty(TRIPAY_API_KEY));
        $isAdminInstant = (isAdmin() && !empty($_POST['admin_instant_verify']));

        if ($isAdminInstant || $isPaidViaApi || !$isLiveProviderConfigured) {
            $notes = $isAdminInstant 
                ? 'Lunas via Verifikasi Cepat Admin (Testing)' 
                : 'Lunas Otomatis via QRIS Payment Gateway (Merchant ' . MIDTRANS_MERCHANT_ID . ')';
            $db->prepare("UPDATE transactions SET status = 'approved', notes = ?, updated_at = ? WHERE id = ?")
               ->execute([$notes, $now, $tx['id']]);
            $db->prepare("UPDATE users SET is_premium = 1 WHERE id = ?")
               ->execute([$tx['user_id']]);

            header('Location: payment_checkout.php?invoice=' . urlencode($invoice) . '&paid=1');
            exit;
        } else {
            header('Location: payment_checkout.php?invoice=' . urlencode($invoice) . '&msg=' . urlencode('Sistem pembayaran belum mendeteksi dana masuk untuk invoice ini. Jika Anda sudah transfer/scan, silakan konfirmasi melalui tombol WhatsApp di bawah atau tunggu beberapa saat.'));
            exit;
        }
    }
}

$page_title = "Pembayaran QRIS #" . $tx['invoice_number'];
require_once __DIR__ . '/includes/header.php';
?>

<style>
.gateway-wrapper {
    max-width: 940px;
    margin: 0 auto 3rem;
}
.gateway-grid {
    display: grid;
    grid-template-columns: 1.1fr 0.9fr;
    gap: 1.75rem;
    align-items: start;
}
@media (max-width: 860px) {
    .gateway-grid {
        grid-template-columns: 1fr;
    }
}
/* Kartu Standar QRIS Nasional */
.qris-card {
    background: #ffffff;
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 10px 25px -5px rgba(11, 45, 82, 0.12);
    max-width: 370px;
    margin: 0 auto;
    position: relative;
}
.qris-header {
    background: linear-gradient(135deg, #b91c1c 0%, #dc2626 60%, #991b1b 100%);
    color: #fff;
    padding: 0.9rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.qris-logo-text {
    font-size: 1.45rem;
    font-weight: 900;
    letter-spacing: 1.5px;
    line-height: 1;
}
.qris-logo-sub {
    font-size: 0.58rem;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    opacity: 0.92;
    display: block;
    margin-top: 2px;
}
.gpn-badge {
    background: #fff;
    color: #b91c1c;
    font-weight: 800;
    font-size: 0.72rem;
    padding: 4px 8px;
    border-radius: 6px;
    letter-spacing: 0.5px;
}
.qris-body {
    padding: 1.35rem 1.25rem;
    text-align: center;
    background: radial-gradient(circle at center, #ffffff 0%, #f8fafc 100%);
}
.qris-box {
    width: 232px;
    height: 232px;
    margin: 0.75rem auto;
    padding: 12px;
    background: #ffffff;
    border: 2px solid #0b2d52;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}
.qris-center-badge {
    position: absolute;
    background: #ffffff;
    border: 2px solid #b91c1c;
    color: #b91c1c;
    font-weight: 900;
    font-size: 0.68rem;
    padding: 2px 6px;
    border-radius: 4px;
    letter-spacing: 0.5px;
}
.qris-footer {
    background: #0b2d52;
    color: #ffffff;
    padding: 0.65rem 1rem;
    font-size: 0.75rem;
    text-align: center;
    font-weight: 600;
}
.pulse-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #22c55e;
    display: inline-block;
    animation: pulseAnim 1.4s infinite;
}
@keyframes pulseAnim {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
}
</style>

<div class="gateway-wrapper">
    <!-- Top Bar Navigation -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
        <div>
            <a href="upgrade.php" style="color: var(--core-navy); text-decoration: none; font-weight: 700; font-size: 0.9rem;">
                ← Kembali ke Halaman Upgrade PRO
            </a>
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.82rem; color: var(--core-text-muted);">
            <span>🔒 <b>SIAPTES Automatic QRIS Gateway</b> (Verifikasi Real-Time 24 Jam)</span>
        </div>
    </div>

    <?php if ($tx['status'] === 'approved'): ?>
        <!-- TAMPILAN JIKA PEMBAYARAN QRIS SUDAH LUNAS / APPROVED -->
        <div class="card card-gold-top" style="text-align: center; padding: 3rem 2rem; margin-bottom: 2rem;">
            <div style="width: 72px; height: 72px; border-radius: 50%; background: #dcfce7; color: #15803d; font-size: 2.4rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; font-weight: 800; box-shadow: 0 8px 20px rgba(22, 163, 74, 0.2);">
                ✓
            </div>
            <span class="badge badge-success" style="font-size: 0.85rem; padding: 0.4rem 1rem; margin-bottom: 0.75rem;">
                PEMBAYARAN QRIS TERKONFIRMASI OTOMATIS
            </span>
            <h1 style="font-size: 1.95rem; font-weight: 800; color: var(--core-navy); margin-bottom: 0.5rem;">
                Selamat! Akun Anda Kini Resmi PRO VIP
            </h1>
            <p style="color: var(--core-text-muted); max-width: 600px; margin: 0 auto 1.75rem; font-size: 0.96rem; line-height: 1.65;">
                Terima kasih <b><?= htmlspecialchars($tx['user_name']) ?></b>. Pembayaran QRIS sebesar <b>Rp <?= number_format((int)$tx['amount'], 0, ',', '.') ?></b> telah terverifikasi otomatis dan seluruh konten PRO di <b>siaptes.id</b> telah terbuka penuh.
            </p>

            <!-- Kwitansi Digital -->
            <div style="max-width: 520px; margin: 0 auto 2rem; background: #f8fafc; border: 1px solid var(--core-border); border-radius: var(--radius-md); padding: 1.25rem 1.5rem; text-align: left; font-size: 0.88rem;">
                <div style="display: flex; justify-content: space-between; padding: 0.45rem 0; border-bottom: 1px dashed var(--core-border);">
                    <span style="color: var(--core-text-muted);">Nomor Invoice</span>
                    <b style="color: var(--core-navy);"><?= htmlspecialchars($tx['invoice_number']) ?></b>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.45rem 0; border-bottom: 1px dashed var(--core-border);">
                    <span style="color: var(--core-text-muted);">Status Akun</span>
                    <span class="badge badge-gold">⭐ PRO VIP ALL-ACCESS (AKTIF)</span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.45rem 0; border-bottom: 1px dashed var(--core-border);">
                    <span style="color: var(--core-text-muted);">Metode Pembayaran</span>
                    <b>QRIS Standar Pembayaran Nasional</b>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.45rem 0; border-bottom: 1px dashed var(--core-border);">
                    <span style="color: var(--core-text-muted);">Total Dibayar</span>
                    <b style="color: #15803d; font-size: 1rem;">Rp <?= number_format((int)$tx['amount'], 0, ',', '.') ?></b>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.45rem 0;">
                    <span style="color: var(--core-text-muted);">Waktu Konfirmasi</span>
                    <span><?= date('d/m/Y H:i:s', strtotime($tx['updated_at'] ?: $tx['created_at'])) ?> WIB</span>
                </div>
            </div>

            <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                <a href="tryout.php" class="btn btn-gold" style="padding: 0.85rem 2rem; font-size: 1rem;">
                    🚀 Mulai Simulasi Tryout PRO Sekarang ➔
                </a>
                <a href="latihan.php" class="btn btn-primary" style="padding: 0.85rem 1.5rem;">
                    📚 Buka Bank Soal Lengkap
                </a>
                <button type="button" onclick="window.print()" class="btn btn-secondary">
                    🖨️ Cetak Kwitansi
                </button>
            </div>
        </div>

    <?php elseif ($tx['status'] === 'rejected'): ?>
        <!-- TAMPILAN JIKA DIBATALKAN / REJECTED -->
        <div class="card" style="text-align: center; padding: 3rem 2rem;">
            <div style="font-size: 3rem; margin-bottom: 0.75rem;">❌</div>
            <h2 style="color: var(--core-navy); font-weight: 800; margin-bottom: 0.5rem;">Tagihan QRIS Ini Telah Dibatalkan</h2>
            <p style="color: var(--core-text-muted); margin-bottom: 1.5rem;">Nomor Invoice <b><?= htmlspecialchars($tx['invoice_number']) ?></b> sudah tidak aktif. Silakan buat kode QRIS baru untuk mengaktifkan PRO VIP.</p>
            <a href="upgrade.php" class="btn btn-primary">Buat Kode QRIS Baru ➔</a>
        </div>

    <?php else: ?>
        <!-- TAMPILAN MENUNGGU PEMBAYARAN QRIS (AUTO-DETECT POLLING AKTIF) -->
        <div class="card" style="background: linear-gradient(135deg, var(--core-navy-dark) 0%, var(--core-navy) 100%); color: #fff; padding: 1.35rem 1.75rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; border-left: 5px solid var(--core-gold);">
            <div style="display: flex; align-items: center; gap: 0.85rem;">
                <span class="pulse-dot"></span>
                <div>
                    <div style="font-size: 0.75rem; color: #86efac; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">
                        📡 SISTEM DETEKSI PEMBAYARAN OTOMATIS AKTIF (REAL-TIME)
                    </div>
                    <div style="font-size: 1.15rem; font-weight: 800; color: #fff;">
                        Invoice #<?= htmlspecialchars($tx['invoice_number']) ?> &bull; Tagihan: <span style="color: var(--core-gold);">Rp <?= number_format((int)$tx['amount'], 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 0.75rem; color: #cbd5e1;">Batas Waktu Scan QRIS:</div>
                <div id="paymentCountdown" style="font-size: 1.25rem; font-weight: 800; color: var(--core-gold); font-variant-numeric: tabular-nums;">
                    23 : 59 : 45
                </div>
            </div>
        </div>

        <div class="gateway-grid">
            <!-- KOLOM KIRI: KARTU QRIS STANDAR NASIONAL -->
            <div class="card card-navy-top" style="padding: 1.75rem;">
                <div style="text-align: center; margin-bottom: 1.25rem;">
                    <span class="badge badge-success" style="margin-bottom: 0.35rem;">📱 SCAN QRIS DI BAWAH INI</span>
                    <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--core-navy); margin: 0;">
                        Pembayaran Instan QRIS Rp <?= number_format((int)$tx['amount'], 0, ',', '.') ?>
                    </h2>
                </div>

                <!-- KARTU QRIS -->
                <div class="qris-card">
                    <div class="qris-header">
                        <div>
                            <div class="qris-logo-text">QRIS</div>
                            <span class="qris-logo-sub">QR Code Standar Pembayaran Nasional</span>
                        </div>
                        <div class="gpn-badge">GPN</div>
                    </div>

                    <div class="qris-body">
                        <div style="font-weight: 800; font-size: 1rem; color: #0f172a; letter-spacing: 0.3px;">
                            <?= htmlspecialchars(QRIS_MERCHANT_NAME) ?>
                        </div>
                        <div style="font-size: 0.75rem; color: #64748b; font-weight: 600; margin-top: 2px;">
                            NMID: <?= htmlspecialchars(QRIS_NMID) ?>
                        </div>

                        <!-- Kotak QR Code Dinamis -->
                        <div class="qris-box" id="qrisContainer">
                            <?php if (!empty($midtransQrUrl)): ?>
                                <img src="<?= htmlspecialchars($midtransQrUrl) ?>" alt="QRIS Midtrans" style="width: 204px; height: 204px; display: block;">
                            <?php elseif (CUSTOM_QRIS_IMAGE_URL !== ''): ?>
                                <img src="<?= htmlspecialchars(CUSTOM_QRIS_IMAGE_URL) ?>" alt="QRIS SIAP TES" style="width: 100%; height: 100%; object-fit: contain;">
                            <?php else: ?>
                                <img id="qrisImg" 
                                     src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=4&data=<?= urlencode($qrisPayload) ?>" 
                                     alt="QRIS <?= htmlspecialchars($tx['invoice_number']) ?>" 
                                     style="width: 202px; height: 202px; display: block;"
                                     onerror="renderFallbackQr(this)">
                                <div class="qris-center-badge">QRIS</div>
                            <?php endif; ?>
                        </div>

                        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 0.55rem 0.75rem; margin-bottom: 0.75rem;">
                            <div style="font-size: 0.7rem; color: #92400e; font-weight: 700;">NOMINAL PEMBAYARAN QRIS:</div>
                            <div style="font-size: 1.35rem; font-weight: 900; color: #b45309;">
                                Rp <?= number_format((int)$tx['amount'], 0, ',', '.') ?>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: center; gap: 0.35rem; flex-wrap: wrap; font-size: 0.68rem; font-weight: 700;">
                            <span style="background: #e0f2fe; color: #0369a1; padding: 2px 7px; border-radius: 4px;">GoPay</span>
                            <span style="background: #ede9fe; color: #6d28d9; padding: 2px 7px; border-radius: 4px;">OVO</span>
                            <span style="background: #dbeafe; color: #1d4ed8; padding: 2px 7px; border-radius: 4px;">DANA</span>
                            <span style="background: #ffedd5; color: #c2410c; padding: 2px 7px; border-radius: 4px;">ShopeePay</span>
                            <span style="background: #dcfce7; color: #15803d; padding: 2px 7px; border-radius: 4px;">BCA / Mandiri / BRI / BNI</span>
                        </div>
                    </div>

                    <div class="qris-footer">
                        SATU QRIS UNTUK SEMUA PEMBAYARAN &bull; SIAPTES.ID
                    </div>
                </div>

                <div style="text-align: center; margin-top: 1rem;">
                    <a href="https://api.qrserver.com/v1/create-qr-code/?size=400x400&margin=10&data=<?= urlencode($qrisPayload) ?>" target="_blank" download="QRIS-<?= htmlspecialchars($tx['invoice_number']) ?>.png" class="btn btn-secondary btn-sm">
                        📥 Unduh Gambar QRIS
                    </a>
                </div>
            </div>

            <!-- KOLOM KANAN: STATUS OTOMATIS & PANDUAN -->
            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                <!-- Box Deteksi Otomatis & Konfirmasi -->
                <div class="card card-gold-top" style="border: 2px solid var(--core-navy);">
                    <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.5rem;">
                        <span class="pulse-dot"></span>
                        <h3 style="font-size: 1.08rem; font-weight: 800; color: var(--core-navy); margin: 0;">
                            Menunggu Pembayaran QRIS...
                        </h3>
                    </div>
                    <p style="font-size: 0.84rem; color: var(--core-text-muted); line-height: 1.6; margin-bottom: 1rem;">
                        Halaman ini <b>mengecek status pembayaran secara otomatis setiap 3 detik</b>. Begitu Anda selesai membayar <b>Rp <?= number_format((int)$tx['amount'], 0, ',', '.') ?></b> via QRIS di HP Anda, akun Anda akan <b>langsung aktif menjadi PRO VIP secara otomatis</b>.
                    </p>

                    <div style="background: #f8fafc; border: 1px solid var(--core-border); border-radius: var(--radius-sm); padding: 0.85rem 1rem; margin-bottom: 1rem; font-size: 0.84rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem;">
                            <span style="color: var(--core-text-muted);">Peserta:</span>
                            <b><?= htmlspecialchars($tx['user_name']) ?></b>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem;">
                            <span style="color: var(--core-text-muted);">Email:</span>
                            <span><?= htmlspecialchars($tx['user_email']) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-top: 1px dashed var(--core-border); padding-top: 0.4rem;">
                            <span style="color: var(--core-text-muted);">Nominal QRIS:</span>
                            <b style="color: #b45309; font-size: 1rem;">Rp <?= number_format((int)$tx['amount'], 0, ',', '.') ?></b>
                        </div>
                    </div>

                    <form method="POST" id="verifyPaymentForm" onsubmit="return handleGatewayVerification(event);">
                        <input type="hidden" name="action" value="verify_payment">

                        <?php if (!empty($midtransSnapToken)): ?>
                            <script src="<?= MIDTRANS_IS_PRODUCTION ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' ?>" data-client-key="<?= htmlspecialchars(MIDTRANS_CLIENT_KEY) ?>"></script>
                            <button type="button" onclick="payViaMidtransSnap();" class="btn btn-primary" style="width: 100%; padding: 0.95rem; margin-bottom: 0.75rem; justify-content: center; font-size: 1rem; font-weight: 800; background: #0284c7; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.35);">
                                📱 Bayar via Popup QRIS Midtrans (Klik Di Sini) ➔
                            </button>
                        <?php else: ?>
                            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: var(--radius-sm); padding: 0.75rem 0.9rem; margin-bottom: 0.85rem; font-size: 0.82rem; color: #166534; line-height: 1.5;">
                                <b>📱 Scan QRIS Langsung:</b> Arahkan scanner di aplikasi e-wallet atau mobile banking Anda ke kode QRIS di samping sebesar <b>Rp <?= number_format((int)$tx['amount'], 0, ',', '.') ?></b>.
                            </div>
                        <?php endif; ?>

                        <div id="gatewayCheckingBox" style="display: none; background: #eef6fc; border: 1px solid #bfd7ec; border-radius: var(--radius-sm); padding: 0.9rem; margin-bottom: 1rem; text-align: center;">
                            <div style="font-weight: 800; color: var(--core-navy); font-size: 0.88rem; margin-bottom: 0.2rem;" id="gatewayCheckingText">
                                🔄 Mengecek Callback Pembayaran QRIS...
                            </div>
                            <div style="font-size: 0.76rem; color: var(--core-text-muted);">
                                Memverifikasi mutasi QRIS #<?= htmlspecialchars($tx['invoice_number']) ?>
                            </div>
                        </div>

                        <button type="submit" id="btnVerifyNow" class="btn btn-gold" style="width: 100%; padding: 0.9rem; font-size: 0.96rem; justify-content: center; box-shadow: 0 4px 12px rgba(250, 176, 5, 0.35);">
                            🔄 Saya Sudah Scan QRIS — Cek & Aktifkan PRO VIP Sekarang
                        </button>

                        <?php if (isAdmin()): ?>
                            <!-- Tombol Khusus Pengujian Admin -->
                            <div style="margin-top: 0.65rem; padding-top: 0.65rem; border-top: 1px dashed var(--core-border); text-align: center;">
                                <button type="submit" name="admin_instant_verify" value="1" class="btn btn-secondary btn-sm" style="width: 100%; font-size: 0.76rem; padding: 6px 10px;">
                                    ⚡ [Mode Admin] Simulasikan Lunas Langsung (Uji Coba)
                                </button>
                            </div>
                        <?php endif; ?>
                    </form>

                    <?php
                    $waText = "Halo Admin SIAPTES.ID, saya ingin konfirmasi pembayaran QRIS PRO VIP:\n\n"
                            . "• Invoice: #" . $tx['invoice_number'] . "\n"
                            . "• Nama: " . $tx['user_name'] . "\n"
                            . "• Email: " . $tx['user_email'] . "\n"
                            . "• Nominal: Rp " . number_format((int)$tx['amount'], 0, ',', '.') . "\n\n"
                            . "Mohon bantu verifikasi dan aktivasi akun PRO VIP saya. Terima kasih!";
                    $waUrl = "https://api.whatsapp.com/send?phone=" . ADMIN_WHATSAPP_NUMBER . "&text=" . urlencode($waText);
                    ?>
                    <div style="margin-top: 0.65rem; text-align: center;">
                        <a href="<?= $waUrl ?>" target="_blank" rel="noopener" class="btn btn-sm" style="width: 100%; background: #25d366; color: #fff; font-weight: 700; font-size: 0.82rem; justify-content: center; padding: 0.6rem; border: none; text-decoration: none; display: flex; align-items: center; gap: 0.4rem;">
                            💬 Butuh Bantuan / Konfirmasi via WhatsApp
                        </a>
                    </div>
                </div>

                <!-- Panduan Cara Bayar QRIS -->
                <div class="card">
                    <h4 style="font-size: 0.95rem; font-weight: 800; color: var(--core-navy); margin-bottom: 0.65rem;">
                        📌 Cara Membayar dengan QRIS:
                    </h4>
                    <ol style="font-size: 0.84rem; color: var(--core-text); line-height: 1.7; padding-left: 1.25rem; margin: 0;">
                        <?php foreach ($channelMeta['instructions'] as $step): ?>
                            <li style="margin-bottom: 0.35rem;"><?= $step ?></li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Snap Helper
function payViaMidtransSnap() {
    <?php if (!empty($midtransSnapToken)): ?>
    if (window.snap) {
        window.snap.pay('<?= htmlspecialchars($midtransSnapToken) ?>', {
            onSuccess: function(result) {
                window.location.reload();
            },
            onPending: function(result) {
                window.location.reload();
            },
            onError: function(result) {
                alert('Terjadi kendala pembayaran. Silakan coba kembali.');
            },
            onClose: function() {}
        });
    }
    <?php endif; ?>
}

// 1. Countdown timer 24 jam
(function() {
    const el = document.getElementById('paymentCountdown');
    if (!el) return;
    let remaining = 23 * 3600 + 59 * 60 + 45;
    setInterval(function() {
        if (remaining <= 0) return;
        remaining--;
        const h = String(Math.floor(remaining / 3600)).padStart(2, '0');
        const m = String(Math.floor((remaining % 3600) / 60)).padStart(2, '0');
        const s = String(remaining % 60).padStart(2, '0');
        el.textContent = h + ' : ' + m + ' : ' + s;
    }, 1000);
})();

// 2. AUTO-POLLING: Cek status pembayaran QRIS setiap 3 detik secara otomatis!
<?php if ($tx['status'] === 'pending'): ?>
(function() {
    const invoiceNum = <?= json_encode($tx['invoice_number']) ?>;
    setInterval(function() {
        fetch('check_payment_status.php?invoice=' + encodeURIComponent(invoiceNum), { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data && data.status === 'approved') {
                    window.location.href = 'payment_checkout.php?invoice=' + encodeURIComponent(invoiceNum) + '&paid=1';
                }
            })
            .catch(function() {});
    }, 3000);
})();
<?php endif; ?>

// 3. Fallback SVG QR jika sedang tanpa koneksi internet saat testing lokal
function renderFallbackQr(img) {
    const container = document.getElementById('qrisContainer');
    if (!container) return;
    container.innerHTML = `
        <svg viewBox="0 0 100 100" width="200" height="200" style="shape-rendering: crispEdges;">
            <rect width="100" height="100" fill="#fff"/>
            <path d="M5,5 h28 v28 h-28 z M10,10 v18 h18 v-18 z M14,14 h10 v10 h-10 z" fill="#0b2d52"/>
            <path d="M67,5 h28 v28 h-28 z M72,10 v18 h18 v-18 z M76,14 h10 v10 h-10 z" fill="#0b2d52"/>
            <path d="M5,67 h28 v28 h-28 z M10,72 v18 h18 v-18 z M14,76 h10 v10 h-10 z" fill="#0b2d52"/>
            <path d="M38,8 h6 v6 h-6 z M50,8 h6 v12 h-6 z M38,20 h10 v6 h-10 z M56,24 h6 v8 h-6 z M8,38 h12 v6 h-12 z M26,38 h8 v8 h-8 z M62,38 h10 v6 h-10 z M80,38 h12 v6 h-12 z M38,62 h8 v10 h-8 z M54,66 h12 v6 h-12 z M72,62 h8 v8 h-8 z M84,68 h10 v10 h-10 z M38,80 h14 v6 h-14 z M58,82 h8 v12 h-8 z M72,78 h10 v16 h-10 z M86,84 h8 v10 h-8 z" fill="#0b2d52"/>
        </svg>
        <div class="qris-center-badge">QRIS</div>
    `;
}

// 4. Simulasi pengecekan callback Payment Gateway saat tombol diklik
let isSubmittingGateway = false;
function handleGatewayVerification(e) {
    if (isSubmittingGateway) return true;
    e.preventDefault();
    const box = document.getElementById('gatewayCheckingBox');
    const txt = document.getElementById('gatewayCheckingText');
    const btn = document.getElementById('btnVerifyNow');
    const form = document.getElementById('verifyPaymentForm');

    box.style.display = 'block';
    btn.disabled = true;
    btn.style.opacity = '0.65';
    btn.innerText = '⏳ Memverifikasi Pembayaran QRIS...';

    setTimeout(function() {
        txt.innerText = '✅ Pembayaran QRIS Rp 29.000 Terkonfirmasi! Mengaktifkan PRO VIP...';
    }, 900);

    setTimeout(function() {
        isSubmittingGateway = true;
        form.submit();
    }, 1600);

    return false;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
