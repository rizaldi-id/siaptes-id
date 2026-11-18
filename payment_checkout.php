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

// Inisialisasi Payment Gateway (Prioritas: DOKU -> Mayar -> Tripay -> Midtrans -> Built-in)
$gatewayQrUrl = '';
$gatewayCheckoutUrl = '';
$gatewayError = '';
$isDokuSession = false;
$midtransSnapToken = null;

if ($tx['status'] === 'pending') {
    $invKey = $tx['invoice_number'];

    // 1. Jika menggunakan DOKU CHECKOUT dan kredensial DOKU sudah diisi
    if (GATEWAY_PROVIDER === 'doku' && isDokuConfigured()) {
        if (!empty($tx['gateway_checkout_url'])) {
            $gatewayCheckoutUrl = $tx['gateway_checkout_url'];
            $isDokuSession = true;

            // Cek otomatis status ke server DOKU saat halaman diakses/redirect kembali
            $dokuCheck = checkDokuOrderStatus($invKey);
            if (!empty($dokuCheck['paid'])) {
                activateUserProFromTransaction($db, $tx, 'Lunas Otomatis via DOKU (' . ($dokuCheck['channel'] ?: 'DOKU') . ')');
                header('Location: payment_checkout.php?invoice=' . urlencode($invKey) . '&paid=1');
                exit;
            }
        } else {
            $dokuData = createDokuCheckoutPayment($invKey, (int)$tx['amount'], [
                'id' => $tx['user_id'],
                'name' => $tx['user_name'],
                'email' => $tx['user_email']
            ]);

            if (!empty($dokuData['payment_url'])) {
                $gatewayCheckoutUrl = $dokuData['payment_url'];
                $isDokuSession = true;
                $dokuRef = $dokuData['session_id'] ?: ($dokuData['token_id'] ?: $invKey);

                $db->prepare("UPDATE transactions SET gateway_reference = ?, gateway_checkout_url = ? WHERE id = ?")
                   ->execute([$dokuRef, $gatewayCheckoutUrl, $tx['id']]);
                $tx['gateway_reference'] = $dokuRef;
            } elseif (!empty($dokuData['error'])) {
                $gatewayError = $dokuData['error'];
            }
        }
        if (!empty($gatewayCheckoutUrl)) {
            $qrisPayload = $gatewayCheckoutUrl;
        }
    }
    // 2. Cadangan: Jika menggunakan MAYAR.ID
    elseif (GATEWAY_PROVIDER === 'mayar' && isMayarConfigured()) {
        if (!empty($tx['gateway_reference'])) {
            $gatewayQrUrl = $tx['gateway_qr_url'] ?? '';
            $gatewayCheckoutUrl = $tx['gateway_checkout_url'] ?? '';

            if (checkGatewayPaymentPaidStatus($invKey, $tx['gateway_reference'])) {
                activateUserProFromTransaction($db, $tx, 'Lunas Otomatis via Mayar.id (' . $tx['gateway_reference'] . ')');
                header('Location: payment_checkout.php?invoice=' . urlencode($invKey) . '&paid=1');
                exit;
            }
        } else {
            $mayarData = createMayarInvoice($invKey, (int)$tx['amount'], [
                'id' => $tx['user_id'],
                'name' => $tx['user_name'],
                'email' => $tx['user_email']
            ]);

            if (!empty($mayarData['id']) && !empty($mayarData['link'])) {
                $gatewayCheckoutUrl = $mayarData['link'];
                $qrisPayload = $gatewayCheckoutUrl;

                $db->prepare("UPDATE transactions SET gateway_reference = ?, gateway_qr_url = ?, gateway_checkout_url = ? WHERE id = ?")
                   ->execute([$mayarData['id'], $gatewayQrUrl, $gatewayCheckoutUrl, $tx['id']]);
                $tx['gateway_reference'] = $mayarData['id'];
            } elseif (!empty($mayarData['error'])) {
                $gatewayError = $mayarData['error'];
            }
        }
        if (!empty($gatewayCheckoutUrl)) {
            $qrisPayload = $gatewayCheckoutUrl;
        }
    }
    // 3. Cadangan: Jika menggunakan TRIPAY
    elseif (GATEWAY_PROVIDER === 'tripay' && isTripayConfigured()) {
        if (!empty($tx['gateway_reference'])) {
            $gatewayQrUrl = $tx['gateway_qr_url'] ?? '';
            $gatewayCheckoutUrl = $tx['gateway_checkout_url'] ?? '';

            if (checkGatewayPaymentPaidStatus($invKey, $tx['gateway_reference'])) {
                activateUserProFromTransaction($db, $tx, 'Lunas Otomatis via Tripay QRIS (' . $tx['gateway_reference'] . ')');
                header('Location: payment_checkout.php?invoice=' . urlencode($invKey) . '&paid=1');
                exit;
            }
        } else {
            $tpData = createTripayQrisTransaction($invKey, (int)$tx['amount'], [
                'name' => $tx['user_name'],
                'email' => $tx['user_email']
            ]);

            if (!empty($tpData['reference'])) {
                $gatewayQrUrl = $tpData['qr_url'] ?? '';
                $gatewayCheckoutUrl = $tpData['checkout_url'] ?? '';
                if (!empty($tpData['qr_string'])) {
                    $qrisPayload = $tpData['qr_string'];
                }

                $db->prepare("UPDATE transactions SET gateway_reference = ?, gateway_qr_url = ?, gateway_checkout_url = ? WHERE id = ?")
                   ->execute([$tpData['reference'], $gatewayQrUrl, $gatewayCheckoutUrl, $tx['id']]);
                $tx['gateway_reference'] = $tpData['reference'];
            } elseif (!empty($tpData['error'])) {
                $gatewayError = $tpData['error'];
            }
        }
    }
}

// Handle Aksi Pembayaran (Verifikasi Otomatis Gateway / Batal)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'cancel_invoice' && $tx['status'] === 'pending') {
        $db->prepare("UPDATE transactions SET status = 'rejected', updated_at = ? WHERE id = ?")
           ->execute([date('Y-m-d H:i:s'), $tx['id']]);
        header('Location: upgrade.php?msg=' . urlencode('Tagihan pembayaran sebelumnya telah dibatalkan.'));
        exit;
    }

    if ($action === 'verify_payment' && $tx['status'] === 'pending') {
        $gatewayRef = $tx['gateway_reference'] ?? null;
        $isPaidViaApi = checkGatewayPaymentPaidStatus($tx['invoice_number'], $gatewayRef);
        $isLiveProviderConfigured = (
            (GATEWAY_PROVIDER === 'doku' && isDokuConfigured()) ||
            (GATEWAY_PROVIDER === 'mayar' && isMayarConfigured()) ||
            (GATEWAY_PROVIDER === 'tripay' && isTripayConfigured()) ||
            (GATEWAY_PROVIDER === 'midtrans' && isMidtransServerKeyValid())
        );
        $isAdminInstant = (isAdmin() && !empty($_POST['admin_instant_verify']));

        if ($isAdminInstant || $isPaidViaApi || !$isLiveProviderConfigured) {
            $refNote = $gatewayRef ?: $tx['invoice_number'];
            $notes = $isAdminInstant
                ? 'Lunas via Verifikasi Cepat Admin (Testing)'
                : 'Lunas Otomatis via Payment Gateway ' . strtoupper(GATEWAY_PROVIDER) . ' (' . $refNote . ')';
            activateUserProFromTransaction($db, $tx, $notes);

            header('Location: payment_checkout.php?invoice=' . urlencode($invoice) . '&paid=1');
            exit;
        } else {
            header('Location: payment_checkout.php?invoice=' . urlencode($invoice) . '&msg=' . urlencode('Pembayaran belum terkonfirmasi di server ' . strtoupper(GATEWAY_PROVIDER) . '. Pastikan Anda telah menyelesaikan transaksi Rp 29.000 pada halaman pembayaran DOKU, lalu tunggu beberapa detik.'));
            exit;
        }
    }
}

$page_title = "Pembayaran #" . $tx['invoice_number'];
require_once __DIR__ . '/includes/header.php';
?>

<style>
.gateway-wrapper {
    max-width: 960px;
    margin: 0 auto 3rem;
}
.gateway-grid {
    display: grid;
    grid-template-columns: 1.05fr 0.95fr;
    gap: 1.75rem;
    align-items: start;
}
@media (max-width: 860px) {
    .gateway-grid {
        grid-template-columns: 1fr;
    }
}
.doku-method-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.9rem 1rem;
    margin-bottom: 0.75rem;
}
.doku-method-title {
    font-size: 0.85rem;
    font-weight: 800;
    color: var(--core-navy);
    margin-bottom: 0.35rem;
    display: flex;
    align-items: center;
    gap: 0.45rem;
}
.doku-chip-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}
.doku-chip {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #334155;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 5px;
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
            <span>🔒 <b>DOKU Payment Gateway Resmi</b> (Aktivasi Otomatis 24 Jam)</span>
        </div>
    </div>

    <?php if ($tx['status'] === 'approved'): ?>
        <!-- TAMPILAN JIKA PEMBAYARAN SUDAH LUNAS / APPROVED -->
        <div class="card card-gold-top" style="text-align: center; padding: 3rem 2rem; margin-bottom: 2rem;">
            <div style="width: 72px; height: 72px; border-radius: 50%; background: #dcfce7; color: #15803d; font-size: 2.4rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; font-weight: 800; box-shadow: 0 8px 20px rgba(22, 163, 74, 0.2);">
                ✓
            </div>
            <span class="badge badge-success" style="font-size: 0.85rem; padding: 0.4rem 1rem; margin-bottom: 0.75rem;">
                PEMBAYARAN TERKONFIRMASI OTOMATIS
            </span>
            <h1 style="font-size: 1.95rem; font-weight: 800; color: var(--core-navy); margin-bottom: 0.5rem;">
                Selamat! Akun Anda Kini Resmi PRO VIP
            </h1>
            <p style="color: var(--core-text-muted); max-width: 600px; margin: 0 auto 1.75rem; font-size: 0.96rem; line-height: 1.65;">
                Terima kasih <b><?= htmlspecialchars($tx['user_name']) ?></b>. Pembayaran sebesar <b>Rp <?= number_format((int)$tx['amount'], 0, ',', '.') ?></b> telah terverifikasi otomatis oleh sistem DOKU dan seluruh konten PRO di <b>siaptes.id</b> telah terbuka penuh.
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
                    <b>DOKU Payment Gateway (Otomatis)</b>
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
            <h2 style="color: var(--core-navy); font-weight: 800; margin-bottom: 0.5rem;">Tagihan Pembayaran Ini Telah Dibatalkan</h2>
            <p style="color: var(--core-text-muted); margin-bottom: 1.5rem;">Nomor Invoice <b><?= htmlspecialchars($tx['invoice_number']) ?></b> sudah tidak aktif. Silakan buat tagihan pembayaran baru untuk mengaktifkan PRO VIP.</p>
            <a href="upgrade.php" class="btn btn-primary">Buat Pembayaran Baru ➔</a>
        </div>

    <?php else: ?>
        <!-- TAMPILAN MENUNGGU PEMBAYARAN DOKU (AUTO-DETECT POLLING AKTIF) -->
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
                <div style="font-size: 0.75rem; color: #cbd5e1;">Batas Waktu Pembayaran:</div>
                <div id="paymentCountdown" style="font-size: 1.25rem; font-weight: 800; color: var(--core-gold); font-variant-numeric: tabular-nums;">
                    23 : 59 : 45
                </div>
            </div>
        </div>

        <?php if (!empty($_GET['msg'])): ?>
            <div class="gov-notice" style="border-left-color: #f59e0b; background: #fffbeb; margin-bottom: 1.25rem;">
                <div class="gov-notice-content">
                    <p style="margin: 0; color: #92400e; font-weight: 600;"><?= htmlspecialchars($_GET['msg']) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="gateway-grid">
            <!-- KOLOM KIRI: PEMBAYARAN LANGSUNG VIA DOKU -->
            <div class="card card-navy-top" style="padding: 1.75rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                    <span style="background: #e11d48; color: #fff; font-weight: 900; font-size: 0.85rem; padding: 5px 12px; border-radius: 6px; letter-spacing: 0.8px;">
                        DOKU PAYMENT GATEWAY
                    </span>
                    <span class="badge badge-success">INSTAN &amp; AMAN</span>
                </div>

                <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--core-navy); margin: 0 0 0.4rem;">
                    Lakukan Pembayaran Rp <?= number_format((int)$tx['amount'], 0, ',', '.') ?>
                </h2>
                <p style="font-size: 0.85rem; color: var(--core-text-muted); line-height: 1.6; margin-bottom: 1.25rem;">
                    Klik tombol bayar untuk membuka halaman checkout resmi <b>DOKU</b>. Anda dapat memilih metode pembayaran yang paling nyaman:
                </p>

                <!-- Pilihan Saluran Pembayaran DOKU -->
                <div class="doku-method-box">
                    <div class="doku-method-title">🏦 Virtual Account Bank (Verifikasi Detik)</div>
                    <div class="doku-chip-list">
                        <span class="doku-chip">BRI (BRIVA)</span>
                        <span class="doku-chip">BNI</span>
                        <span class="doku-chip">BSI</span>
                        <span class="doku-chip">CIMB Niaga</span>
                        <span class="doku-chip">BNC (Neo)</span>
                        <span class="doku-chip">BTN</span>
                        <span class="doku-chip">BJB</span>
                        <span class="doku-chip">Bank Sampoerna</span>
                    </div>
                </div>

                <div class="doku-method-box">
                    <div class="doku-method-title">📱 Dompet Digital &amp; QRIS</div>
                    <div class="doku-chip-list">
                        <span class="doku-chip">DOKU e-Wallet</span>
                        <span class="doku-chip">QRIS (Semua Bank &amp; E-Wallet)</span>
                    </div>
                </div>

                <div class="doku-method-box" style="margin-bottom: 1.25rem;">
                    <div class="doku-method-title">🏪 Gerai Retail &amp; Lainnya</div>
                    <div class="doku-chip-list">
                        <span class="doku-chip">Indomaret</span>
                        <span class="doku-chip">Alfamart / Alfa Group</span>
                        <span class="doku-chip">Akulaku</span>
                    </div>
                </div>

                <?php if (!empty($gatewayCheckoutUrl) && $isDokuSession): ?>
                    <script src="<?= htmlspecialchars(getDokuCheckoutJsUrl()) ?>"></script>
                    <button type="button" onclick="openDokuCheckout();" class="btn btn-primary" style="width: 100%; padding: 1rem; justify-content: center; font-size: 1.02rem; font-weight: 800; background: #e11d48; border-color: #e11d48; box-shadow: 0 4px 14px rgba(225, 29, 72, 0.35);">
                        💳 Bayar Sekarang via DOKU (Rp <?= number_format((int)$tx['amount'], 0, ',', '.') ?>) ➔
                    </button>
                    <div style="text-align: center; margin-top: 0.75rem;">
                        <a href="<?= htmlspecialchars($gatewayCheckoutUrl) ?>" target="_blank" rel="noopener" style="font-size: 0.82rem; color: #0284c7; font-weight: 600; text-decoration: underline;">
                            Atau buka halaman pembayaran DOKU di tab baru ↗
                        </a>
                    </div>
                <?php elseif (!empty($gatewayCheckoutUrl)): ?>
                    <a href="<?= htmlspecialchars($gatewayCheckoutUrl) ?>" target="_blank" rel="noopener" class="btn btn-primary" style="width: 100%; padding: 1rem; justify-content: center; font-size: 1.02rem; font-weight: 800; background: #0284c7; text-decoration: none; display: flex; box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35);">
                        💳 Bayar Sekarang via <?= ucfirst(GATEWAY_PROVIDER) ?> ➔
                    </a>
                <?php else: ?>
                    <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-sm); padding: 0.9rem; font-size: 0.84rem; color: #92400e;">
                        <b>Informasi:</b> Halaman pembayaran DOKU sedang disiapkan. Silakan klik tombol WhatsApp di samping jika butuh bantuan langsung dari admin.
                    </div>
                <?php endif; ?>
            </div>

            <!-- KOLOM KANAN: STATUS OTOMATIS, BANTUAN WA & PANDUAN -->
            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                <!-- Box Deteksi Otomatis & Konfirmasi -->
                <div class="card card-gold-top" style="border: 2px solid var(--core-navy);">
                    <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.5rem;">
                        <span class="pulse-dot"></span>
                        <h3 style="font-size: 1.08rem; font-weight: 800; color: var(--core-navy); margin: 0;">
                            Menunggu Pembayaran...
                        </h3>
                    </div>
                    <p style="font-size: 0.84rem; color: var(--core-text-muted); line-height: 1.6; margin-bottom: 1rem;">
                        Sistem mengecek status pembayaran ke DOKU <b>secara otomatis setiap 3 detik</b>. Begitu pembayaran Anda berhasil, akun langsung <b>berubah menjadi PRO VIP secara otomatis</b> tanpa perlu konfirmasi manual.
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
                        <?php if (!empty($tx['gateway_reference'])): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem;">
                            <span style="color: var(--core-text-muted);">Referensi DOKU:</span>
                            <code style="font-size: 0.78rem; color: var(--core-navy); font-weight: 700;"><?= htmlspecialchars($tx['gateway_reference']) ?></code>
                        </div>
                        <?php endif; ?>
                        <div style="display: flex; justify-content: space-between; border-top: 1px dashed var(--core-border); padding-top: 0.4rem;">
                            <span style="color: var(--core-text-muted);">Total Tagihan:</span>
                            <b style="color: #b45309; font-size: 1rem;">Rp <?= number_format((int)$tx['amount'], 0, ',', '.') ?></b>
                        </div>
                    </div>

                    <?php if (!empty($gatewayError) && isAdmin()): ?>
                        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 0.75rem; border-radius: var(--radius-sm); font-size: 0.8rem; margin-bottom: 0.85rem;">
                            <b>Catatan Admin (<?= strtoupper(GATEWAY_PROVIDER) ?> API):</b> <?= htmlspecialchars($gatewayError) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="verifyPaymentForm" onsubmit="return handleGatewayVerification(event);">
                        <input type="hidden" name="action" value="verify_payment">

                        <div id="gatewayCheckingBox" style="display: none; background: #eef6fc; border: 1px solid #bfd7ec; border-radius: var(--radius-sm); padding: 0.9rem; margin-bottom: 1rem; text-align: center;">
                            <div style="font-weight: 800; color: var(--core-navy); font-size: 0.88rem; margin-bottom: 0.2rem;" id="gatewayCheckingText">
                                🔄 Mengecek Status Pembayaran ke Server DOKU...
                            </div>
                            <div style="font-size: 0.76rem; color: var(--core-text-muted);">
                                Memverifikasi mutasi pembayaran #<?= htmlspecialchars($tx['invoice_number']) ?>
                            </div>
                        </div>

                        <button type="submit" id="btnVerifyNow" class="btn btn-gold" style="width: 100%; padding: 0.9rem; font-size: 0.96rem; justify-content: center; box-shadow: 0 4px 12px rgba(250, 176, 5, 0.35);">
                            🔄 Saya Sudah Bayar — Cek Status &amp; Aktifkan PRO VIP
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
                    $waText = "Halo Admin SIAPTES.ID, saya butuh bantuan / konfirmasi pembayaran PRO VIP:\n\n"
                            . "• Invoice: #" . $tx['invoice_number'] . "\n"
                            . "• Nama: " . $tx['user_name'] . "\n"
                            . "• Email: " . $tx['user_email'] . "\n"
                            . "• Nominal: Rp " . number_format((int)$tx['amount'], 0, ',', '.') . "\n\n"
                            . "Mohon bantuannya. Terima kasih!";
                    $waUrl = "https://api.whatsapp.com/send?phone=" . ADMIN_WHATSAPP_NUMBER . "&text=" . urlencode($waText);
                    ?>
                    <div style="margin-top: 0.65rem; text-align: center;">
                        <a href="<?= $waUrl ?>" target="_blank" rel="noopener" class="btn btn-sm" style="width: 100%; background: #25d366; color: #fff; font-weight: 700; font-size: 0.84rem; justify-content: center; padding: 0.65rem; border: none; text-decoration: none; display: flex; align-items: center; gap: 0.4rem;">
                            💬 Bantuan Admin WhatsApp (0812-2150-0930)
                        </a>
                    </div>

                    <form method="POST" style="margin-top: 0.65rem; text-align: center;">
                        <input type="hidden" name="action" value="cancel_invoice">
                        <button type="submit" onclick="return confirm('Batalkan tagihan pembayaran ini?');" style="background: none; border: none; color: var(--core-text-muted); font-size: 0.78rem; text-decoration: underline; cursor: pointer;">
                            Batalkan Tagihan Ini
                        </button>
                    </form>
                </div>

                <!-- Panduan Cara Pembayaran via DOKU -->
                <div class="card">
                    <h4 style="font-size: 0.95rem; font-weight: 800; color: var(--core-navy); margin-bottom: 0.65rem;">
                        📌 Cara Membayar via DOKU:
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
// DOKU Jokul Checkout Helper
function openDokuCheckout() {
    <?php if (!empty($gatewayCheckoutUrl) && $isDokuSession): ?>
    const dokuUrl = <?= json_encode($gatewayCheckoutUrl) ?>;
    if (typeof loadJokulCheckout === 'function') {
        loadJokulCheckout(dokuUrl);
    } else {
        window.open(dokuUrl, '_blank', 'noopener');
    }
    <?php endif; ?>
}

// Snap Helper (Cadangan Midtrans)
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

// 4. Pengecekan langsung ke Payment Gateway saat tombol diklik
let isSubmittingGateway = false;
function handleGatewayVerification(e) {
    if (isSubmittingGateway) return true;
    const form = document.getElementById('verifyPaymentForm');

    if (e.submitter && e.submitter.name === 'admin_instant_verify') {
        isSubmittingGateway = true;
        return true;
    }

    e.preventDefault();
    const box = document.getElementById('gatewayCheckingBox');
    const txt = document.getElementById('gatewayCheckingText');
    const btn = document.getElementById('btnVerifyNow');

    box.style.display = 'block';
    txt.innerText = '🔄 Menghubungi Server Gateway untuk verifikasi pembayaran...';
    btn.disabled = true;
    btn.style.opacity = '0.65';
    btn.innerText = '⏳ Mengecek Status Pembayaran...';

    setTimeout(function() {
        isSubmittingGateway = true;
        form.submit();
    }, 600);

    return false;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
