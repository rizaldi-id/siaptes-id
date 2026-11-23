<?php
// payment_checkout.php - Halaman Checkout Dual-Gateway (DOKU Checkout & Midtrans Snap)
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
    header('Location: upgrade.php?msg=' . urlencode('Tagihan pembayaran tidak ditemukan.'));
    exit;
}

$gateways = getAvailablePaymentGateways();
$txProvider = detectTransactionProvider($tx);
$gwMeta = $gateways[$txProvider] ?? $gateways['doku'];

// Penanganan pindah gateway 1-klik (DOKU <-> Midtrans) saat status masih pending
if ($tx['status'] === 'pending' && isset($_GET['switch'])) {
    $switchTarget = strtolower(trim((string)$_GET['switch']));
    if (
        in_array($switchTarget, ['doku', 'midtrans'], true)
        && $switchTarget !== $txProvider
        && !empty($gateways[$switchTarget]['configured'])
    ) {
        $newInv = createFreshProVipTransaction(
            $db,
            (int)$tx['user_id'],
            !empty($tx['package_id']) ? (int)$tx['package_id'] : null,
            (int)$tx['amount'],
            'Pengguna beralih metode ke ' . $gateways[$switchTarget]['name'],
            $switchTarget
        );
        header('Location: payment_checkout.php?invoice=' . urlencode($newInv) . '&autopay=1&switched=1');
        exit;
    }
}

$channels = getPaymentChannels($txProvider);
$channelMeta = $channels['QRIS'];
$qrisPayload = generateQrisPayload($tx['invoice_number'], (int)$tx['amount']);

$gatewayQrUrl = '';
$gatewayCheckoutUrl = '';
$gatewayError = '';
$isDokuSession = false;
$isMidtransSession = false;
$midtransSnapToken = null;

$remainingSeconds = getTransactionRemainingSeconds($tx, 86400);
$forceNewLink = (isset($_GET['new_link']) && $_GET['new_link'] === '1');
$autoOpenCheckout = (isset($_GET['autopay']) && $_GET['autopay'] === '1');

if ($tx['status'] === 'pending') {
    $invKey = $tx['invoice_number'];

    // 1. DOKU CHECKOUT
    if ($txProvider === 'doku' && isDokuConfigured()) {
        $dokuCheck = checkDokuOrderStatus($invKey);
        if (!empty($dokuCheck['paid'])) {
            activateUserProFromTransaction($db, $tx, 'Lunas Otomatis via DOKU (' . ($dokuCheck['channel'] ?: 'DOKU') . ')');
            header('Location: payment_checkout.php?invoice=' . urlencode($invKey) . '&paid=1');
            exit;
        }

        $isDokuExpiredOrFailed = isDokuTerminalFailureStatus($dokuCheck['status'] ?? '');
        $isLocalExpired = isTransactionLinkExpired($tx);

        if ($forceNewLink || $isDokuExpiredOrFailed || $isLocalExpired) {
            $newInv = createFreshProVipTransaction(
                $db,
                (int)$tx['user_id'],
                !empty($tx['package_id']) ? (int)$tx['package_id'] : null,
                (int)$tx['amount'],
                $forceNewLink ? 'Pengguna meminta link pembayaran DOKU baru' : 'Tagihan lama kedaluwarsa, diganti otomatis',
                'doku'
            );
            header('Location: payment_checkout.php?invoice=' . urlencode($newInv) . '&autopay=1&refreshed=1');
            exit;
        }

        if (!empty($tx['gateway_checkout_url'])) {
            $gatewayCheckoutUrl = $tx['gateway_checkout_url'];
            $isDokuSession = true;
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
    // 2. MIDTRANS SNAP
    elseif ($txProvider === 'midtrans' && isMidtransConfigured()) {
        $midCheck = checkMidtransOrderStatus($invKey);
        if (!empty($midCheck['paid'])) {
            activateUserProFromTransaction($db, $tx, 'Lunas Otomatis via Midtrans (' . ($midCheck['channel'] ?: 'Midtrans') . ')');
            header('Location: payment_checkout.php?invoice=' . urlencode($invKey) . '&paid=1');
            exit;
        }

        $isMidExpiredOrFailed = isMidtransTerminalFailureStatus($midCheck['status'] ?? '');
        $isLocalExpired = isTransactionLinkExpired($tx);

        if ($forceNewLink || $isMidExpiredOrFailed || $isLocalExpired) {
            $newInv = createFreshProVipTransaction(
                $db,
                (int)$tx['user_id'],
                !empty($tx['package_id']) ? (int)$tx['package_id'] : null,
                (int)$tx['amount'],
                $forceNewLink ? 'Pengguna meminta link pembayaran Midtrans baru' : 'Tagihan lama kedaluwarsa, diganti otomatis',
                'midtrans'
            );
            header('Location: payment_checkout.php?invoice=' . urlencode($newInv) . '&autopay=1&refreshed=1');
            exit;
        }

        if (!empty($tx['gateway_reference'])) {
            $midtransSnapToken = $tx['gateway_reference'];
            $gatewayCheckoutUrl = $tx['gateway_checkout_url'] ?? '';
            $isMidtransSession = true;
        } else {
            $midData = createMidtransSnapTransaction($invKey, (int)$tx['amount'], [
                'id' => $tx['user_id'],
                'name' => $tx['user_name'],
                'email' => $tx['user_email']
            ]);

            if (!empty($midData['token'])) {
                $midtransSnapToken = $midData['token'];
                $gatewayCheckoutUrl = $midData['redirect_url'] ?? '';
                $isMidtransSession = true;

                $db->prepare("UPDATE transactions SET gateway_reference = ?, gateway_checkout_url = ? WHERE id = ?")
                   ->execute([$midtransSnapToken, $gatewayCheckoutUrl, $tx['id']]);
                $tx['gateway_reference'] = $midtransSnapToken;
                $tx['gateway_checkout_url'] = $gatewayCheckoutUrl;
            } elseif (!empty($midData['error'])) {
                $gatewayError = $midData['error'];
            }
        }
        if (!empty($gatewayCheckoutUrl)) {
            $qrisPayload = $gatewayCheckoutUrl;
        }
    }
    // 3. Cadangan: MAYAR.ID
    elseif ($txProvider === 'mayar' && isMayarConfigured()) {
        if (!empty($tx['gateway_reference'])) {
            $gatewayQrUrl = $tx['gateway_qr_url'] ?? '';
            $gatewayCheckoutUrl = $tx['gateway_checkout_url'] ?? '';

            if (checkGatewayPaymentPaidStatus($invKey, $tx['gateway_reference'], 'mayar')) {
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
    }
    // 4. Cadangan: TRIPAY
    elseif ($txProvider === 'tripay' && isTripayConfigured()) {
        if (!empty($tx['gateway_reference'])) {
            $gatewayQrUrl = $tx['gateway_qr_url'] ?? '';
            $gatewayCheckoutUrl = $tx['gateway_checkout_url'] ?? '';

            if (checkGatewayPaymentPaidStatus($invKey, $tx['gateway_reference'], 'tripay')) {
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
        $isPaidViaApi = checkGatewayPaymentPaidStatus($tx['invoice_number'], $gatewayRef, $txProvider);
        $isLiveProviderConfigured = (
            ($txProvider === 'doku' && isDokuConfigured()) ||
            ($txProvider === 'midtrans' && isMidtransConfigured()) ||
            ($txProvider === 'mayar' && isMayarConfigured()) ||
            ($txProvider === 'tripay' && isTripayConfigured())
        );
        $isAdminInstant = (isAdmin() && !empty($_POST['admin_instant_verify']));

        if ($isAdminInstant || $isPaidViaApi || !$isLiveProviderConfigured) {
            $refNote = $gatewayRef ?: $tx['invoice_number'];
            $notes = $isAdminInstant
                ? 'Lunas via Verifikasi Cepat Admin (Testing)'
                : 'Lunas Otomatis via ' . $gwMeta['name'] . ' (' . $refNote . ')';
            activateUserProFromTransaction($db, $tx, $notes);

            header('Location: payment_checkout.php?invoice=' . urlencode($invoice) . '&paid=1');
            exit;
        } else {
            header('Location: payment_checkout.php?invoice=' . urlencode($invoice) . '&msg=' . urlencode('Pembayaran belum terkonfirmasi di server ' . $gwMeta['short_label'] . '. Pastikan Anda telah menyelesaikan transaksi Rp 29.000 pada halaman pembayaran ' . $gwMeta['short_label'] . ', lalu tunggu beberapa detik.'));
            exit;
        }
    }
}

$otherProvider = ($txProvider === 'midtrans') ? 'doku' : 'midtrans';
$canSwitchGateway = (!empty($gateways[$otherProvider]['configured']));

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
    margin-bottom: 0.45rem;
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
.switch-gw-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.65rem;
    flex-wrap: wrap;
    background: #f4f7fc;
    border: 1px solid #d7e2f1;
    border-radius: 10px;
    padding: 0.7rem 0.95rem;
    margin-bottom: 1.1rem;
    font-size: 0.81rem;
    color: #334155;
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
        <div class="ui-inline" style="font-size: 0.82rem; color: var(--core-text-muted);">
            <?= ui_icon('lock', 14) ?> <span><b><?= htmlspecialchars($gwMeta['name']) ?></b> (Aktivasi Otomatis 24 Jam)</span>
        </div>
    </div>

    <?php if ($tx['status'] === 'approved'): ?>
        <!-- TAMPILAN JIKA PEMBAYARAN SUDAH LUNAS / APPROVED -->
        <div class="card card-gold-top" style="text-align: center; padding: 3rem 2rem; margin-bottom: 2rem;">
            <div style="width: 72px; height: 72px; border-radius: 50%; background: #dcfce7; color: #15803d; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; box-shadow: 0 8px 20px rgba(22, 163, 74, 0.2);">
                <?= ui_icon('check', 34) ?>
            </div>
            <span class="badge badge-success" style="font-size: 0.85rem; padding: 0.4rem 1rem; margin-bottom: 0.75rem;">
                PEMBAYARAN TERKONFIRMASI OTOMATIS
            </span>
            <h1 style="font-size: 1.95rem; font-weight: 800; color: var(--core-navy); margin-bottom: 0.5rem;">
                Selamat! Akun Anda Kini Resmi PRO VIP
            </h1>
            <p style="color: var(--core-text-muted); max-width: 600px; margin: 0 auto 1.75rem; font-size: 0.96rem; line-height: 1.65;">
                Terima kasih <b><?= htmlspecialchars($tx['user_name']) ?></b>. Pembayaran sebesar <b>Rp <?= number_format((int)$tx['amount'], 0, ',', '.') ?></b> telah terverifikasi otomatis oleh sistem <?= htmlspecialchars($gwMeta['short_label']) ?> dan seluruh konten PRO di <b>siaptes.id</b> telah terbuka penuh.
            </p>

            <!-- Kwitansi Digital -->
            <div style="max-width: 520px; margin: 0 auto 2rem; background: #f8fafc; border: 1px solid var(--core-border); border-radius: var(--radius-md); padding: 1.25rem 1.5rem; text-align: left; font-size: 0.88rem;">
                <div style="display: flex; justify-content: space-between; padding: 0.45rem 0; border-bottom: 1px dashed var(--core-border);">
                    <span style="color: var(--core-text-muted);">Nomor Invoice</span>
                    <b style="color: var(--core-navy);"><?= htmlspecialchars($tx['invoice_number']) ?></b>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.45rem 0; border-bottom: 1px dashed var(--core-border);">
                    <span style="color: var(--core-text-muted);">Status Akun</span>
                    <span class="badge badge-gold"><?= ui_icon('crown', 12) ?> PRO VIP ALL-ACCESS (AKTIF)</span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.45rem 0; border-bottom: 1px dashed var(--core-border);">
                    <span style="color: var(--core-text-muted);">Metode Pembayaran</span>
                    <b><?= htmlspecialchars($gwMeta['name']) ?> (Otomatis)</b>
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
                    <?= ui_icon('play', 15) ?> Mulai Simulasi Tryout PRO <?= ui_icon('arrow-right', 15, 'icon-arrow') ?>
                </a>
                <a href="latihan.php" class="btn btn-primary" style="padding: 0.85rem 1.5rem;">
                    <?= ui_icon('book-open', 15) ?> Buka Bank Soal Lengkap
                </a>
                <button type="button" onclick="window.print()" class="btn btn-secondary">
                    <?= ui_icon('file-text', 15) ?> Cetak Kwitansi
                </button>
            </div>
        </div>

    <?php elseif ($tx['status'] === 'rejected'): ?>
        <!-- TAMPILAN JIKA DIBATALKAN / KEDALUWARSA -->
        <div class="card" style="text-align: center; padding: 3rem 2rem;">
            <div style="margin-bottom: 0.75rem;"><?= ui_icon_box('clock', 'gold', 24) ?></div>
            <h2 style="color: var(--core-navy); font-weight: 800; margin-bottom: 0.5rem;">Link Pembayaran Ini Sudah Tidak Aktif / Kedaluwarsa</h2>
            <p style="color: var(--core-text-muted); margin-bottom: 1.5rem; max-width: 540px; margin-left: auto; margin-right: auto;">
                Nomor Invoice <b><?= htmlspecialchars($tx['invoice_number']) ?></b> sudah dibatalkan atau melewati batas waktu pembayaran. Klik tombol di bawah untuk langsung membuat link pembayaran baru.
            </p>
            <div style="display: flex; justify-content: center; gap: 0.75rem; flex-wrap: wrap;">
                <form method="POST" action="upgrade.php" style="margin: 0;">
                    <input type="hidden" name="force_new" value="1">
                    <input type="hidden" name="gateway_provider" value="<?= htmlspecialchars($txProvider) ?>">
                    <?php if (!empty($tx['package_id'])): ?>
                        <input type="hidden" name="package_id" value="<?= (int)$tx['package_id'] ?>">
                    <?php endif; ?>
                    <button type="submit" class="btn btn-gold" style="padding: 0.85rem 1.75rem; font-size: 0.98rem;">
                        <?= ui_icon('zap', 15) ?> Buat Link <?= htmlspecialchars($gwMeta['short_label']) ?> Baru (Rp <?= number_format((int)$tx['amount'], 0, ',', '.') ?>) <?= ui_icon('arrow-right', 15, 'icon-arrow') ?>
                    </button>
                </form>
                <a href="upgrade.php" class="btn btn-secondary">Pilih Metode Pembayaran Lain</a>
            </div>
        </div>

    <?php else:
        $cdHours = str_pad((string)floor($remainingSeconds / 3600), 2, '0', STR_PAD_LEFT);
        $cdMins = str_pad((string)floor(($remainingSeconds % 3600) / 60), 2, '0', STR_PAD_LEFT);
        $cdSecs = str_pad((string)($remainingSeconds % 60), 2, '0', STR_PAD_LEFT);
    ?>
        <!-- TAMPILAN MENUNGGU PEMBAYARAN (AUTO-DETECT POLLING AKTIF) -->
        <div class="card" style="background: linear-gradient(135deg, var(--core-navy-dark) 0%, var(--core-navy) 100%); color: #fff; padding: 1.35rem 1.75rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; border-left: 5px solid var(--core-gold);">
            <div style="display: flex; align-items: center; gap: 0.85rem;">
                <span class="pulse-dot"></span>
                <div>
                    <div style="font-size: 0.75rem; color: #86efac; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">
                        SISTEM DETEKSI PEMBAYARAN OTOMATIS AKTIF (<?= strtoupper(htmlspecialchars($gwMeta['short_label'])) ?>)
                    </div>
                    <div style="font-size: 1.15rem; font-weight: 800; color: #fff;">
                        Invoice #<?= htmlspecialchars($tx['invoice_number']) ?> &bull; Tagihan: <span style="color: var(--core-gold);">Rp <?= number_format((int)$tx['amount'], 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 0.75rem; color: #cbd5e1;">Batas Waktu Pembayaran:</div>
                <div id="paymentCountdown" style="font-size: 1.25rem; font-weight: 800; color: var(--core-gold); font-variant-numeric: tabular-nums;">
                    <?= $cdHours ?> : <?= $cdMins ?> : <?= $cdSecs ?>
                </div>
            </div>
        </div>

        <?php if (!empty($_GET['refreshed'])): ?>
            <div class="gov-notice" style="border-left-color: #16a34a; background: #f0fdf4; margin-bottom: 1.25rem;">
                <div class="gov-notice-content">
                    <p class="ui-inline" style="margin: 0; color: #166534; font-weight: 600;"><?= ui_icon('check-circle', 16) ?> <span>Link pembayaran baru (#<?= htmlspecialchars($tx['invoice_number']) ?>) telah disiapkan otomatis. Silakan selesaikan pembayaran di bawah ini.</span></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($_GET['switched'])): ?>
            <div class="gov-notice" style="border-left-color: #0284c7; background: #eff6ff; margin-bottom: 1.25rem;">
                <div class="gov-notice-content">
                    <p class="ui-inline" style="margin: 0; color: #1e3a8a; font-weight: 600;"><?= ui_icon('refresh', 16) ?> <span>Berhasil beralih ke <b><?= htmlspecialchars($gwMeta['name']) ?></b> dengan invoice baru #<?= htmlspecialchars($tx['invoice_number']) ?>.</span></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($_GET['msg'])): ?>
            <div class="gov-notice" style="border-left-color: #f59e0b; background: #fffbeb; margin-bottom: 1.25rem;">
                <div class="gov-notice-content">
                    <p style="margin: 0; color: #92400e; font-weight: 600;"><?= htmlspecialchars($_GET['msg']) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="gateway-grid">
            <!-- KOLOM KIRI: PEMBAYARAN LANGSUNG VIA GATEWAY AKTIF -->
            <div class="card card-navy-top" style="padding: 1.75rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                    <span style="background: <?= htmlspecialchars($gwMeta['accent']) ?>; color: #fff; font-weight: 900; font-size: 0.82rem; padding: 5px 12px; border-radius: 6px; letter-spacing: 0.8px;">
                        <?= strtoupper(htmlspecialchars($gwMeta['name'])) ?>
                    </span>
                    <span class="badge badge-success">INSTAN &amp; AMAN</span>
                </div>

                <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--core-navy); margin: 0 0 0.4rem;">
                    Bayar Cepat Rp <?= number_format((int)$tx['amount'], 0, ',', '.') ?>
                </h2>
                <p style="font-size: 0.85rem; color: var(--core-text-muted); line-height: 1.6; margin-bottom: 1.1rem;">
                    Klik tombol di bawah untuk memilih metode pembayaran melalui <b><?= htmlspecialchars($gwMeta['name']) ?></b>. Akun langsung otomatis menjadi <b>PRO VIP</b> setelah bayar.
                </p>

                <?php if ($txProvider === 'doku' && !empty($gatewayCheckoutUrl) && $isDokuSession): ?>
                    <script src="<?= htmlspecialchars(getDokuCheckoutJsUrl()) ?>"></script>
                    <?php if ($autoOpenCheckout): ?>
                        <div class="ui-inline" style="background: #eef6fc; border: 1px solid #bfdbfe; color: #1e3a8a; border-radius: 8px; padding: 0.65rem 0.9rem; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.9rem; width: 100%;">
                            <?= ui_icon('zap', 15) ?> <span>Membuka jendela pembayaran DOKU otomatis... Jika tidak muncul, klik tombol di bawah.</span>
                        </div>
                    <?php endif; ?>

                    <button type="button" onclick="openDokuCheckout();" class="btn btn-primary" style="width: 100%; padding: 1.02rem; justify-content: center; font-size: 1.02rem; font-weight: 800; background: #e11d48; border-color: #e11d48; box-shadow: 0 4px 14px rgba(225, 29, 72, 0.3); margin-bottom: 0.65rem;">
                        <?= ui_icon('credit-card', 16) ?> Bayar Sekarang via DOKU (Rp <?= number_format((int)$tx['amount'], 0, ',', '.') ?>) <?= ui_icon('arrow-right', 16, 'icon-arrow') ?>
                    </button>

                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.1rem;">
                        <a href="<?= htmlspecialchars($gatewayCheckoutUrl) ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm" style="flex: 1 1 180px; justify-content: center; text-decoration: none;">
                            Buka di Tab Penuh DOKU ↗
                        </a>
                        <a href="payment_checkout.php?invoice=<?= urlencode($tx['invoice_number']) ?>&amp;new_link=1" class="btn btn-outline-navy btn-sm" style="flex: 1 1 160px; justify-content: center; text-decoration: none;">
                            <?= ui_icon('refresh', 14) ?> Buat Link Baru
                        </a>
                    </div>

                <?php elseif ($txProvider === 'midtrans' && !empty($midtransSnapToken) && $isMidtransSession): ?>
                    <script src="<?= htmlspecialchars(getMidtransSnapJsUrl()) ?>" data-client-key="<?= htmlspecialchars(MIDTRANS_CLIENT_KEY) ?>"></script>
                    <?php if ($autoOpenCheckout): ?>
                        <div class="ui-inline" style="background: #eef6fc; border: 1px solid #bfdbfe; color: #1e3a8a; border-radius: 8px; padding: 0.65rem 0.9rem; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.9rem; width: 100%;">
                            <?= ui_icon('zap', 15) ?> <span>Membuka jendela Midtrans Snap otomatis... Jika tidak muncul, klik tombol biru di bawah.</span>
                        </div>
                    <?php endif; ?>

                    <button type="button" onclick="payViaMidtransSnap();" class="btn btn-primary" style="width: 100%; padding: 1.02rem; justify-content: center; font-size: 1.02rem; font-weight: 800; background: #0284c7; border-color: #0284c7; box-shadow: 0 4px 14px rgba(2, 132, 199, 0.3); margin-bottom: 0.65rem;">
                        <?= ui_icon('credit-card', 16) ?> Bayar Sekarang via Midtrans (Rp <?= number_format((int)$tx['amount'], 0, ',', '.') ?>) <?= ui_icon('arrow-right', 16, 'icon-arrow') ?>
                    </button>

                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.1rem;">
                        <?php if (!empty($gatewayCheckoutUrl)): ?>
                            <a href="<?= htmlspecialchars($gatewayCheckoutUrl) ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm" style="flex: 1 1 180px; justify-content: center; text-decoration: none;">
                                Buka di Tab Penuh Midtrans ↗
                            </a>
                        <?php endif; ?>
                        <a href="payment_checkout.php?invoice=<?= urlencode($tx['invoice_number']) ?>&amp;new_link=1" class="btn btn-outline-navy btn-sm" style="flex: 1 1 160px; justify-content: center; text-decoration: none;">
                            <?= ui_icon('refresh', 14) ?> Buat Link Baru
                        </a>
                    </div>

                <?php elseif (!empty($gatewayCheckoutUrl)): ?>
                    <a href="<?= htmlspecialchars($gatewayCheckoutUrl) ?>" target="_blank" rel="noopener" class="btn btn-primary" style="width: 100%; padding: 1rem; justify-content: center; font-size: 1.02rem; font-weight: 800; background: #0284c7; text-decoration: none; display: flex; margin-bottom: 1.1rem;">
                        <?= ui_icon('credit-card', 16) ?> Bayar Sekarang via <?= htmlspecialchars($gwMeta['short_label']) ?> <?= ui_icon('arrow-right', 16, 'icon-arrow') ?>
                    </a>

                <?php else: ?>
                    <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-sm); padding: 0.9rem; font-size: 0.84rem; color: #92400e; margin-bottom: 1.1rem;">
                        <b>Informasi:</b> Halaman pembayaran <?= htmlspecialchars($gwMeta['short_label']) ?> belum berhasil dibuat<?= !empty($gatewayError) ? ' (' . htmlspecialchars($gatewayError) . ')' : '' ?>.
                        <div style="margin-top: 0.6rem;">
                            <a href="payment_checkout.php?invoice=<?= urlencode($tx['invoice_number']) ?>&amp;new_link=1" class="btn btn-gold btn-sm">
                                <?= ui_icon('refresh', 14) ?> Coba Buat Link Pembayaran Baru
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($canSwitchGateway): ?>
                    <div class="switch-gw-bar">
                        <span>
                            <?= $txProvider === 'doku'
                                ? 'Ingin bayar pakai <b>VA BCA / Mandiri / GoPay</b>?'
                                : 'Ingin bayar pakai <b>VA BJB / BSI / DOKU e-Wallet</b>?' ?>
                        </span>
                        <a href="payment_checkout.php?invoice=<?= urlencode($tx['invoice_number']) ?>&amp;switch=<?= htmlspecialchars($otherProvider) ?>" style="font-weight: 800; color: var(--core-navy); white-space: nowrap; text-decoration: none;">
                            Ganti ke <?= htmlspecialchars($gateways[$otherProvider]['short_label']) ?> →
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Pilihan Saluran Pembayaran Sesuai Gateway Aktif -->
                <?php foreach ($gwMeta['groups'] as $grpIdx => $grp): ?>
                    <div class="doku-method-box" <?= $grpIdx === count($gwMeta['groups']) - 1 ? 'style="margin-bottom: 0;"' : '' ?>>
                        <div class="doku-method-title">
                            <?= ui_icon($grpIdx === 0 ? 'landmark' : ($grpIdx === 1 ? 'smartphone' : 'building'), 15) ?>
                            <span><?= htmlspecialchars($grp['title']) ?></span>
                        </div>
                        <div class="doku-chip-list">
                            <?php foreach ($grp['chips'] as $chip): ?>
                                <span class="doku-chip"><?= htmlspecialchars($chip) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
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
                        Sistem mengecek status pembayaran ke <b><?= htmlspecialchars($gwMeta['short_label']) ?> secara otomatis setiap 3 detik</b>. Begitu transaksi selesai, akun Anda langsung aktif menjadi <b>PRO VIP secara otomatis</b>.
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
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem;">
                            <span style="color: var(--core-text-muted);">Gateway Aktif:</span>
                            <b><?= htmlspecialchars($gwMeta['name']) ?></b>
                        </div>
                        <?php if (!empty($tx['gateway_reference'])):
                            $refDisp = (strlen($tx['gateway_reference']) > 22)
                                ? substr($tx['gateway_reference'], 0, 18) . '...'
                                : $tx['gateway_reference'];
                        ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem;">
                            <span style="color: var(--core-text-muted);">Referensi <?= htmlspecialchars($gwMeta['short_label']) ?>:</span>
                            <code style="font-size: 0.78rem; color: var(--core-navy); font-weight: 700;" title="<?= htmlspecialchars($tx['gateway_reference']) ?>"><?= htmlspecialchars($refDisp) ?></code>
                        </div>
                        <?php endif; ?>
                        <div style="display: flex; justify-content: space-between; border-top: 1px dashed var(--core-border); padding-top: 0.4rem;">
                            <span style="color: var(--core-text-muted);">Total Tagihan:</span>
                            <b style="color: #b45309; font-size: 1rem;">Rp <?= number_format((int)$tx['amount'], 0, ',', '.') ?></b>
                        </div>
                    </div>

                    <?php if (!empty($gatewayError) && isAdmin()): ?>
                        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 0.75rem; border-radius: var(--radius-sm); font-size: 0.8rem; margin-bottom: 0.85rem;">
                            <b>Catatan Admin (<?= strtoupper(htmlspecialchars($txProvider)) ?> API):</b> <?= htmlspecialchars($gatewayError) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="verifyPaymentForm" onsubmit="return handleGatewayVerification(event);">
                        <input type="hidden" name="action" value="verify_payment">

                        <div id="gatewayCheckingBox" style="display: none; background: #eef6fc; border: 1px solid #bfd7ec; border-radius: var(--radius-sm); padding: 0.9rem; margin-bottom: 1rem; text-align: center;">
                            <div style="font-weight: 800; color: var(--core-navy); font-size: 0.88rem; margin-bottom: 0.2rem;" id="gatewayCheckingText">
                                Mengecek Status Pembayaran ke Server <?= htmlspecialchars($gwMeta['short_label']) ?>...
                            </div>
                            <div style="font-size: 0.76rem; color: var(--core-text-muted);">
                                Memverifikasi mutasi pembayaran #<?= htmlspecialchars($tx['invoice_number']) ?>
                            </div>
                        </div>

                        <button type="submit" id="btnVerifyNow" class="btn btn-gold" style="width: 100%; padding: 0.9rem; font-size: 0.96rem; justify-content: center; box-shadow: 0 4px 12px rgba(250, 176, 5, 0.35);">
                            <?= ui_icon('refresh', 15) ?> Saya Sudah Bayar — Cek Status &amp; Aktifkan PRO VIP
                        </button>

                        <?php if (isAdmin()): ?>
                            <div style="margin-top: 0.65rem; padding-top: 0.65rem; border-top: 1px dashed var(--core-border); text-align: center;">
                                <button type="submit" name="admin_instant_verify" value="1" class="btn btn-secondary btn-sm" style="width: 100%; font-size: 0.76rem; padding: 6px 10px;">
                                    <?= ui_icon('zap', 13) ?> [Mode Admin] Simulasikan Lunas Langsung (Uji Coba)
                                </button>
                            </div>
                        <?php endif; ?>
                    </form>

                    <?php
                    $waText = "Halo Admin SIAPTES.ID, saya butuh bantuan / konfirmasi pembayaran PRO VIP:\n\n"
                            . "• Gateway: " . $gwMeta['name'] . "\n"
                            . "• Invoice: #" . $tx['invoice_number'] . "\n"
                            . "• Nama: " . $tx['user_name'] . "\n"
                            . "• Email: " . $tx['user_email'] . "\n"
                            . "• Nominal: Rp " . number_format((int)$tx['amount'], 0, ',', '.') . "\n\n"
                            . "Mohon bantuannya. Terima kasih!";
                    $waUrl = "https://api.whatsapp.com/send?phone=" . ADMIN_WHATSAPP_NUMBER . "&text=" . urlencode($waText);
                    ?>
                    <div style="margin-top: 0.65rem; text-align: center;">
                        <a href="<?= $waUrl ?>" target="_blank" rel="noopener" class="btn btn-sm" style="width: 100%; background: #16a34a; color: #fff; font-weight: 700; font-size: 0.84rem; justify-content: center; padding: 0.65rem; border: none; text-decoration: none; display: flex; align-items: center; gap: 0.4rem;">
                            <?= ui_icon('smartphone', 14) ?> Bantuan Admin WhatsApp (0812-2150-0930)
                        </a>
                    </div>

                    <form method="POST" style="margin-top: 0.65rem; text-align: center;">
                        <input type="hidden" name="action" value="cancel_invoice">
                        <button type="submit" onclick="return confirm('Batalkan tagihan pembayaran ini?');" style="background: none; border: none; color: var(--core-text-muted); font-size: 0.78rem; text-decoration: underline; cursor: pointer;">
                            Batalkan Tagihan Ini
                        </button>
                    </form>
                </div>

                <!-- Panduan Cara Pembayaran -->
                <div class="card">
                    <h4 class="ui-inline" style="font-size: 0.95rem; font-weight: 800; color: var(--core-navy); margin-bottom: 0.65rem;">
                        <?= ui_icon('clipboard', 16) ?> Cara Membayar via <?= htmlspecialchars($gwMeta['short_label']) ?>:
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
const TX_INVOICE = <?= json_encode($tx['invoice_number']) ?>;

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

// Midtrans Snap Helper
function payViaMidtransSnap() {
    <?php if (!empty($midtransSnapToken) && $isMidtransSession): ?>
    const snapToken = <?= json_encode($midtransSnapToken) ?>;
    const fallbackRedirectUrl = <?= json_encode($gatewayCheckoutUrl) ?>;
    if (window.snap && typeof window.snap.pay === 'function') {
        window.snap.pay(snapToken, {
            onSuccess: function() {
                fetch('check_payment_status.php?invoice=' + encodeURIComponent(TX_INVOICE), { credentials: 'same-origin' })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data && data.status === 'approved') {
                            window.location.href = 'payment_checkout.php?invoice=' + encodeURIComponent(TX_INVOICE) + '&paid=1';
                        } else {
                            window.location.reload();
                        }
                    })
                    .catch(function() { window.location.reload(); });
            },
            onPending: function() {
                // Auto-polling 3 detik di halaman ini tetap berjalan untuk mendeteksi pelunasan VA/QRIS
            },
            onError: function() {
                alert('Terjadi kendala saat memproses pembayaran di Midtrans. Silakan coba lagi atau pilih metode lain.');
            },
            onClose: function() {}
        });
    } else if (fallbackRedirectUrl) {
        window.open(fallbackRedirectUrl, '_blank', 'noopener');
    } else {
        alert('Skrip Midtrans Snap belum termuat. Silakan refresh halaman atau klik "Buat Link Baru".');
    }
    <?php endif; ?>
}

// 1. Countdown timer nyata berdasarkan waktu pembuatan tagihan
(function() {
    const el = document.getElementById('paymentCountdown');
    if (!el) return;
    let remaining = <?= (int)$remainingSeconds ?>;
    setInterval(function() {
        if (remaining <= 0) {
            el.textContent = '00 : 00 : 00';
            return;
        }
        remaining--;
        const h = String(Math.floor(remaining / 3600)).padStart(2, '0');
        const m = String(Math.floor((remaining % 3600) / 60)).padStart(2, '0');
        const s = String(remaining % 60).padStart(2, '0');
        el.textContent = h + ' : ' + m + ' : ' + s;
    }, 1000);
})();

// 2. Auto-open Jendela Gateway bila user baru klik "Bayar Sekarang" (autopay=1)
<?php if ($tx['status'] === 'pending' && $autoOpenCheckout): ?>
window.addEventListener('load', function() {
    setTimeout(function() {
        <?php if ($isDokuSession): ?>
            openDokuCheckout();
        <?php elseif ($isMidtransSession): ?>
            payViaMidtransSnap();
        <?php endif; ?>
    }, 450);
});
<?php endif; ?>

// 3. AUTO-POLLING: Cek status pembayaran setiap 3 detik & self-heal jika kedaluwarsa
<?php if ($tx['status'] === 'pending'): ?>
(function() {
    setInterval(function() {
        fetch('check_payment_status.php?invoice=' + encodeURIComponent(TX_INVOICE), { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data && data.status === 'approved') {
                    window.location.href = 'payment_checkout.php?invoice=' + encodeURIComponent(TX_INVOICE) + '&paid=1';
                } else if (data && data.status === 'expired') {
                    window.location.href = 'payment_checkout.php?invoice=' + encodeURIComponent(TX_INVOICE) + '&new_link=1';
                }
            })
            .catch(function() {});
    }, 3000);
})();
<?php endif; ?>

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
    txt.innerText = 'Menghubungi Server <?= htmlspecialchars($gwMeta['short_label'], ENT_QUOTES) ?> untuk verifikasi pembayaran...';
    btn.disabled = true;
    btn.style.opacity = '0.65';
    btn.innerText = 'Mengecek Status Pembayaran...';

    setTimeout(function() {
        isSubmittingGateway = true;
        form.submit();
    }, 550);

    return false;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
