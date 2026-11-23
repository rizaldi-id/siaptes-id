<?php
// upgrade.php - Halaman Aktivasi PRO VIP Rp 29.000 (DOKU Checkout + Midtrans Snap)
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/payment_gateway.php';

requireLogin();
$db = getDB();
$user = currentUser();
$isUserPremium = isPremiumUser();

$package_id = isset($_GET['package_id']) ? intval($_GET['package_id']) : 0;
$selectedPackage = null;
if ($package_id > 0) {
    $stmtPkg = $db->prepare("SELECT * FROM packages WHERE id = ? AND is_active = 1");
    $stmtPkg->execute([$package_id]);
    $selectedPackage = $stmtPkg->fetch();
}

$gateways = getAvailablePaymentGateways();

// Cek apakah user memiliki tagihan yang masih berstatus 'pending'
$stmtPending = $db->prepare("SELECT * FROM transactions WHERE user_id = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1");
$stmtPending->execute([$user['id']]);
$pendingTx = $stmtPending->fetch();

// Validasi otomatis tagihan pending sesuai provider asalnya agar tidak mengarah ke link expired
if ($pendingTx && !$isUserPremium) {
    $shouldExpirePending = isTransactionLinkExpired($pendingTx);
    $pendingProvider = detectTransactionProvider($pendingTx);

    if ($pendingProvider === 'midtrans' && isMidtransServerKeyValid()) {
        $midCheck = checkMidtransOrderStatus($pendingTx['invoice_number']);
        if (!empty($midCheck['paid'])) {
            activateUserProFromTransaction($db, $pendingTx, 'Lunas Otomatis via Midtrans (' . ($midCheck['channel'] ?: 'Midtrans') . ')');
            $isUserPremium = true;
            $pendingTx = null;
        } elseif (isMidtransTerminalFailureStatus($midCheck['status'] ?? '')) {
            $shouldExpirePending = true;
        }
    } elseif ($pendingProvider === 'doku' && isDokuConfigured()) {
        $dokuCheck = checkDokuOrderStatus($pendingTx['invoice_number']);
        if (!empty($dokuCheck['paid'])) {
            activateUserProFromTransaction($db, $pendingTx, 'Lunas Otomatis via DOKU (' . ($dokuCheck['channel'] ?: 'DOKU') . ')');
            $isUserPremium = true;
            $pendingTx = null;
        } elseif (isDokuTerminalFailureStatus($dokuCheck['status'] ?? '')) {
            $shouldExpirePending = true;
        }
    }

    if ($pendingTx && $shouldExpirePending) {
        $db->prepare("UPDATE transactions SET status = 'rejected', notes = ?, updated_at = ? WHERE id = ?")
           ->execute(['Tagihan kedaluwarsa otomatis sebelum pembayaran diselesaikan', date('Y-m-d H:i:s'), $pendingTx['id']]);
        $pendingTx = null;
    }
}

// Handle pembuatan invoice Rp 29.000 -> langsung buka Payment Gateway pilihan user (DOKU / Midtrans)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $pkg_id = !empty($_POST['package_id']) ? intval($_POST['package_id']) : null;
    $finalAmount = PRO_VIP_BASE_PRICE; // Rp 29.000
    $forceNew = !empty($_POST['force_new']);
    $chosenGateway = strtolower(trim((string)($_POST['gateway_provider'] ?? GATEWAY_PROVIDER)));
    if (!in_array($chosenGateway, ['doku', 'midtrans'], true)) {
        $chosenGateway = 'doku';
    }

    if (empty($gateways[$chosenGateway]['configured'])) {
        $gwLabel = $gateways[$chosenGateway]['name'] ?? strtoupper($chosenGateway);
        header('Location: upgrade.php?msg=' . urlencode($gwLabel . ' belum diaktifkan. Silakan pilih metode pembayaran yang tersedia.'));
        exit;
    }

    // Gunakan ulang invoice pending hanya jika belum kedaluwarsa (< 12 jam), nominal cocok, dan gateway-nya sama
    if (
        !$forceNew
        && $pendingTx
        && (int)$pendingTx['amount'] === $finalAmount
        && detectTransactionProvider($pendingTx) === $chosenGateway
        && !isTransactionLinkExpired($pendingTx, 43200)
    ) {
        header('Location: payment_checkout.php?invoice=' . urlencode($pendingTx['invoice_number']) . '&autopay=1');
        exit;
    }

    // Buat invoice baru yang masih fresh pada gateway terpilih
    $invNumber = createFreshProVipTransaction(
        $db,
        (int)$user['id'],
        $pkg_id,
        $finalAmount,
        'Digantikan tagihan pembayaran baru',
        $chosenGateway
    );

    header('Location: payment_checkout.php?invoice=' . urlencode($invNumber) . '&autopay=1');
    exit;
}

// Tentukan gateway terpilih awal di form UI
$defaultGateway = 'doku';
if ($pendingTx && !empty($gateways[detectTransactionProvider($pendingTx)]['configured'])) {
    $defaultGateway = detectTransactionProvider($pendingTx);
} elseif (!empty($gateways['doku']['configured'])) {
    $defaultGateway = 'doku';
} elseif (!empty($gateways['midtrans']['configured'])) {
    $defaultGateway = 'midtrans';
}
$anyGatewayConfigured = (!empty($gateways['doku']['configured']) || !empty($gateways['midtrans']['configured']));

// Ambil riwayat transaksi user ini
$stmtHistory = $db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmtHistory->execute([$user['id']]);
$userTransactions = $stmtHistory->fetchAll();

$page_title = "Upgrade PRO VIP Rp 29.000 - SIAP TES";
require_once __DIR__ . '/includes/header.php';
?>

<style>
.gw-selector-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1.25rem;
}
.gw-option-card {
    display: block;
    position: relative;
    border: 1.5px solid #cbd5e1;
    border-radius: var(--radius-md);
    background: #ffffff;
    padding: 1rem 1.1rem;
    cursor: pointer;
    transition: border-color 0.16s ease, background-color 0.16s ease, box-shadow 0.16s ease;
}
.gw-option-card:hover:not(.is-disabled) {
    border-color: #94a3b8;
    background: #f8fafc;
}
.gw-option-card.is-selected {
    border: 2px solid var(--core-navy);
    background: #f0f6ff;
    box-shadow: 0 4px 14px rgba(45, 79, 142, 0.1);
}
.gw-option-card.is-disabled {
    opacity: 0.65;
    cursor: not-allowed;
    background: #f8fafc;
}
.gw-option-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.6rem;
    flex-wrap: wrap;
    margin-bottom: 0.45rem;
}
.gw-option-left {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}
.gw-option-radio {
    width: 17px;
    height: 17px;
    accent-color: var(--core-navy);
    margin: 0;
    cursor: pointer;
}
.gw-brand-pill {
    color: #fff;
    font-weight: 800;
    font-size: 0.74rem;
    padding: 3px 9px;
    border-radius: 6px;
    letter-spacing: 0.6px;
    text-transform: uppercase;
}
.gw-option-sub {
    font-size: 0.78rem;
    color: var(--core-text-muted);
    line-height: 1.5;
    margin: 0 0 0.55rem;
}
.gw-chip-row {
    display: flex;
    gap: 0.35rem;
    flex-wrap: wrap;
}
.gw-chip {
    background: #ffffff;
    border: 1px solid #dbe4ef;
    color: #334155;
    font-size: 0.71rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 5px;
}
</style>

<div style="max-width: 960px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 2.25rem;">
        <div style="display: inline-flex; align-items: center; gap: 6px; margin-bottom: 0.5rem;">
            <span class="badge badge-gold" style="font-size: 0.8rem; padding: 0.35rem 0.85rem;">
                <?= ui_icon('crown', 14) ?> ALL-ACCESS PASS SEUMUR HIDUP &bull; SIAPTES.ID
            </span>
        </div>
        <h1 style="font-size: 2.15rem; font-weight: 800; color: var(--core-navy); margin-top: 0.25rem; margin-bottom: 0.5rem;">
            Buka Seluruh Konten PRO Hanya <span style="color: #b45309;">Rp 29.000</span>
        </h1>
        <p style="color: var(--core-text-muted); font-size: 1rem; max-width: 720px; margin: 0 auto;">
            Pilih pembayaran instan melalui <b>DOKU Payment Gateway</b> atau <b>Midtrans Snap</b> (QRIS semua e-Wallet, GoPay, ShopeePay, Virtual Account BCA, Mandiri, BRI, BNI, BJB, BSI, hingga Indomaret/Alfamart). Akun Anda <b>otomatis aktif menjadi PRO VIP</b> begitu pembayaran terkonfirmasi.
        </p>
    </div>

    <!-- Peringatan jika ada Tagihan Pending yang Belum Dibayar -->
    <?php if ($pendingTx && !$isUserPremium):
        $pendingGwName = $gateways[detectTransactionProvider($pendingTx)]['name'] ?? 'Payment Gateway';
    ?>
        <div class="gov-notice" style="border-left-color: var(--core-gold); background: #fffbeb; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.85rem;">
                <div class="gov-notice-icon" style="background: #fef3c7; color: #b45309;"><?= ui_icon('credit-card', 18) ?></div>
                <div class="gov-notice-content">
                    <h4 style="color: #92400e;">Tagihan Anda Menunggu Pembayaran (<?= htmlspecialchars($pendingGwName) ?>)</h4>
                    <p style="color: #78350f; margin: 0;">
                        Invoice <b>#<?= htmlspecialchars($pendingTx['invoice_number']) ?></b> sebesar <b>Rp <?= number_format((int)$pendingTx['amount'], 0, ',', '.') ?></b> belum diselesaikan.
                    </p>
                </div>
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <a href="payment_checkout.php?invoice=<?= urlencode($pendingTx['invoice_number']) ?>&amp;autopay=1" class="btn btn-gold btn-sm">
                    <?= ui_icon('credit-card', 14) ?> Lanjutkan Pembayaran <?= ui_icon('arrow-right', 14, 'icon-arrow') ?>
                </a>
                <a href="payment_checkout.php?invoice=<?= urlencode($pendingTx['invoice_number']) ?>&amp;new_link=1" class="btn btn-secondary btn-sm">
                    <?= ui_icon('refresh', 14) ?> Buat Link Baru
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Jika sudah menjadi Member PRO VIP -->
    <?php if ($isUserPremium): ?>
        <div class="card card-gold-top" style="text-align: center; padding: 2.25rem; margin-bottom: 2.5rem;">
            <div style="margin-bottom: 0.65rem;"><?= ui_icon_box('crown', 'gold', 24) ?></div>
            <h3 style="color: var(--core-navy); font-size: 1.4rem; font-weight: 800; margin-top: 0.5rem; margin-bottom: 0.35rem;">
                Status Akun Anda: Member PRO VIP All-Access Aktif
            </h3>
            <p style="color: var(--core-text-muted); font-size: 0.95rem; max-width: 600px; margin: 0 auto 1.25rem;">
                Anda telah memiliki hak akses penuh tanpa batas ke seluruh paket tryout CPNS, BUMN, Kedinasan, Psikotes, kunci jawaban, dan pembahasan materi di <b>siaptes.id</b>.
            </p>
            <div style="display: flex; justify-content: center; gap: 0.75rem; flex-wrap: wrap;">
                <a href="tryout.php" class="btn btn-primary"><?= ui_icon('play', 15) ?> Mulai Simulasi Tryout PRO <?= ui_icon('arrow-right', 14, 'icon-arrow') ?></a>
                <a href="latihan.php" class="btn btn-secondary"><?= ui_icon('book-open', 15) ?> Buka Bank Soal Lengkap</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$isUserPremium): ?>
        <div class="grid-2" style="align-items: start; margin-bottom: 2.5rem;">
            <!-- Kolom Kiri: Manfaat & Rincian Paket PRO VIP -->
            <div class="card card-gold-top">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <span class="badge" style="background: var(--core-danger-light); color: var(--core-danger); font-weight: 800;">PROMO SPESIAL PEJUANG SELEKSI</span>
                    <span style="font-size: 0.8rem; color: var(--core-text-muted);">siaptes.id All-Access</span>
                </div>

                <h3 style="font-size: 1.35rem; font-weight: 800; color: var(--core-navy); margin-bottom: 0.5rem;">
                    Lisensi Penuh PRO VIP Seumur Hidup
                </h3>
                <p style="font-size: 0.875rem; color: var(--core-text-muted); margin-bottom: 1.25rem;">
                    Satu kali pembayaran untuk membuka seluruh paket simulasi CAT, ribuan bank soal lintas kategori, evaluasi passing grade, dan trik pembahasan cepat.
                </p>

                <?php if ($selectedPackage): ?>
                    <div class="ui-inline" style="background: #eef6fc; border-left: 4px solid var(--core-blue); padding: 0.75rem 1rem; border-radius: var(--radius-sm); margin-bottom: 1rem; font-size: 0.84rem; color: var(--core-navy);">
                        <?= ui_icon('layers', 15) ?> <span>Membuka paket: <b><?= htmlspecialchars($selectedPackage['title']) ?></b> + <b>Seluruh Konten PRO Lainnya!</b></span>
                    </div>
                <?php endif; ?>

                <div style="margin-bottom: 1.5rem; background: var(--core-navy-subtle); padding: 1.25rem; border-radius: var(--radius-sm); border: 1px solid var(--core-border);">
                    <div style="font-size: 0.85rem; color: var(--core-text-muted); text-decoration: line-through;">Rp <?= number_format(PRO_VIP_STRIKE_PRICE, 0, ',', '.') ?></div>
                    <div style="display: flex; align-items: baseline; gap: 0.5rem;">
                        <span style="font-size: 2.45rem; font-weight: 900; color: var(--core-navy);">Rp <?= number_format(PRO_VIP_BASE_PRICE, 0, ',', '.') ?></span>
                        <span style="font-size: 0.85rem; color: var(--core-text-muted);">/ sekali bayar (Seumur Hidup)</span>
                    </div>
                    <span class="ui-inline" style="font-size: 0.8rem; color: var(--core-success); font-weight: 700; margin-top: 0.25rem;"><?= ui_icon('zap', 14) ?> Aktivasi Otomatis &bull; DOKU Checkout &amp; Midtrans Snap</span>
                </div>

                <div style="font-size: 0.88rem; color: var(--core-text); display: flex; flex-direction: column; gap: 0.55rem;">
                    <div class="ui-inline" style="align-items: flex-start;"><span style="color: var(--core-success); margin-top: 2px;"><?= ui_icon('check-circle', 16) ?></span><span><b>CPNS / ASN:</b> Tryout CPNS 110 Soal Acak Berstandar CAT BKN</span></div>
                    <div class="ui-inline" style="align-items: flex-start;"><span style="color: var(--core-success); margin-top: 2px;"><?= ui_icon('check-circle', 16) ?></span><span><b>BUMN:</b> Simulasi TKD, Core Values AKHLAK &amp; Bahasa Inggris</span></div>
                    <div class="ui-inline" style="align-items: flex-start;"><span style="color: var(--core-success); margin-top: 2px;"><?= ui_icon('check-circle', 16) ?></span><span><b>Kedinasan:</b> Modul Khusus PKN STAN, STIS, IPDN &amp; Poltekip</span></div>
                    <div class="ui-inline" style="align-items: flex-start;"><span style="color: var(--core-success); margin-top: 2px;"><?= ui_icon('check-circle', 16) ?></span><span><b>Psikotes Kerja:</b> Latihan Penalaran Logika, Spasial &amp; SJT</span></div>
                    <div class="ui-inline" style="align-items: flex-start;"><span style="color: var(--core-success); margin-top: 2px;"><?= ui_icon('check-circle', 16) ?></span><span><b>Bank Soal Intensif:</b> Buka kuota latihan 10, 20, 30 hingga 50 soal sekaligus</span></div>
                    <div class="ui-inline" style="align-items: flex-start;"><span style="color: var(--core-success); margin-top: 2px;"><?= ui_icon('check-circle', 16) ?></span><span><b>Pembahasan Lengkap:</b> Kunci Jawaban + Trik Cepat Menjawab</span></div>
                </div>
            </div>

            <!-- Kolom Kanan: Pilih Gateway Pembayaran (DOKU atau Midtrans) -->
            <div class="card card-navy-top">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem; gap: 0.5rem;">
                    <h3 class="ui-inline" style="font-size: 1.2rem; font-weight: 800; color: var(--core-navy); margin: 0;">
                        <?= ui_icon('credit-card', 18) ?> Pilih Metode Pembayaran
                    </h3>
                    <span class="badge badge-success">OTOMATIS AKTIF</span>
                </div>
                <p style="font-size: 0.84rem; color: var(--core-text-muted); margin-bottom: 1rem;">
                    Pilih jalur pembayaran yang paling nyaman untuk Anda. Kedua jalur memproses verifikasi otomatis 24 jam:
                </p>

                <form method="POST" action="upgrade.php" id="gatewaySelectForm">
                    <input type="hidden" name="package_id" value="<?= $package_id ?>">

                    <div class="gw-selector-list">
                        <?php foreach ($gateways as $gwKey => $gw):
                            $isConfigured = !empty($gw['configured']);
                            $isChecked = ($gwKey === $defaultGateway && $isConfigured);
                        ?>
                            <label class="gw-option-card <?= $isChecked ? 'is-selected' : '' ?> <?= !$isConfigured ? 'is-disabled' : '' ?>" data-gw-card="<?= htmlspecialchars($gwKey) ?>">
                                <div class="gw-option-head">
                                    <div class="gw-option-left">
                                        <input
                                            type="radio"
                                            name="gateway_provider"
                                            value="<?= htmlspecialchars($gwKey) ?>"
                                            class="gw-option-radio"
                                            <?= $isChecked ? 'checked' : '' ?>
                                            <?= !$isConfigured ? 'disabled' : '' ?>
                                            onchange="updateGatewaySelection(this.value)"
                                        >
                                        <span class="gw-brand-pill" style="background: <?= htmlspecialchars($gw['accent']) ?>;">
                                            <?= htmlspecialchars($gw['short_label']) ?>
                                        </span>
                                        <b style="color: var(--core-navy); font-size: 0.94rem;"><?= htmlspecialchars($gw['name']) ?></b>
                                    </div>
                                    <?php if ($isConfigured): ?>
                                        <span class="badge badge-success" style="font-size: 0.68rem;">SIAP DIGUNAKAN</span>
                                    <?php else: ?>
                                        <span class="badge badge-navy" style="font-size: 0.68rem; background: #e2e8f0; color: #475569;">BELUM AKTIF</span>
                                    <?php endif; ?>
                                </div>
                                <p class="gw-option-sub"><?= htmlspecialchars($gw['subtitle']) ?></p>
                                <div class="gw-chip-row">
                                    <?php foreach ($gw['chips'] as $chip): ?>
                                        <span class="gw-chip"><?= htmlspecialchars($chip) ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php if (!$isConfigured && isAdmin()): ?>
                                    <div style="margin-top: 0.55rem; font-size: 0.74rem; color: #b45309; background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 0.35rem 0.6rem;">
                                        <b>Info Admin:</b> Lengkapi <?= $gwKey === 'midtrans' ? '<code>MIDTRANS_CLIENT_KEY</code> &amp; <code>MIDTRANS_SERVER_KEY</code>' : '<code>DOKU_CLIENT_ID</code> &amp; <code>DOKU_SECRET_KEY</code>' ?> di <code>config/payment_local.php</code> untuk mengaktifkan.
                                    </div>
                                <?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <!-- Detail Akun yang Akan Diupgrade -->
                    <div style="background: #f8fafc; border: 1px solid var(--core-border); border-radius: var(--radius-sm); padding: 0.9rem 1rem; margin-bottom: 1.25rem; font-size: 0.84rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem;">
                            <span style="color: var(--core-text-muted);">Akun Peserta:</span>
                            <b style="color: var(--core-navy);"><?= htmlspecialchars($user['name']) ?></b>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem;">
                            <span style="color: var(--core-text-muted);">Email Terdaftar:</span>
                            <span><?= htmlspecialchars($user['email']) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-top: 1px dashed var(--core-border); padding-top: 0.45rem; margin-top: 0.35rem;">
                            <span style="font-weight: 700; color: var(--core-navy);">Total Pembayaran:</span>
                            <b style="font-size: 1.05rem; color: #b45309;">Rp <?= number_format(PRO_VIP_BASE_PRICE, 0, ',', '.') ?></b>
                        </div>
                    </div>

                    <button type="submit" id="gwSubmitBtn" class="btn btn-gold" <?= !$anyGatewayConfigured ? 'disabled' : '' ?> style="width: 100%; font-size: 1.02rem; padding: 0.95rem 1.25rem; justify-content: center; box-shadow: 0 4px 14px rgba(223, 154, 44, 0.32);">
                        <?= ui_icon('zap', 16) ?> <span id="gwSubmitLabel">Bayar Sekarang via <?= htmlspecialchars($gateways[$defaultGateway]['short_label']) ?> (Rp 29.000)</span> <?= ui_icon('arrow-right', 16, 'icon-arrow') ?>
                    </button>
                    <p id="gwSubmitNote" style="font-size: 0.76rem; color: var(--core-text-muted); text-align: center; margin: 0.65rem 0 0;">
                        Jendela pembayaran <?= htmlspecialchars($gateways[$defaultGateway]['short_label']) ?> langsung terbuka otomatis &bull; Akun PRO VIP aktif detik itu juga setelah bayar.
                    </p>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Perbandingan Fitur: Akun Gratis vs Akun PRO VIP -->
    <div class="card card-gold-top" style="margin-bottom: 2.5rem; padding: 2rem;">
        <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--core-navy); margin-bottom: 1.25rem; text-align: center;">
            Perbandingan Hak Akses: Akun Trial (Gratis) vs PRO VIP (Rp 29.000)
        </h3>

        <div style="overflow-x: auto;">
            <table class="table" style="font-size: 0.92rem;">
                <thead>
                    <tr>
                        <th style="width: 45%;">Fitur Platform SIAP TES</th>
                        <th style="width: 25%; text-align: center;">Akun Baru (Trial Gratis)</th>
                        <th style="width: 30%; text-align: center; background: #fef3c7; color: #b45309; font-weight: 800;">
                            <span class="ui-inline"><?= ui_icon('crown', 14) ?> PRO VIP (Rp 29.000)</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><b>Akses Simulasi Tryout CAT</b></td>
                        <td style="text-align: center; color: var(--core-text-muted);">Hanya Paket Mini Trial</td>
                        <td style="text-align: center; font-weight: 700; color: #047857; background: #fffbeb;">
                            Semua Paket Tryout Terbuka (Full)
                        </td>
                    </tr>
                    <tr>
                        <td><b>Bank Soal Latihan Mandiri</b></td>
                        <td style="text-align: center; color: var(--core-text-muted);">Maksimal 5 Soal / Sesi</td>
                        <td style="text-align: center; font-weight: 700; color: #047857; background: #fffbeb;">
                            Bebas Pilih 10, 20, 30 s.d. Semua Soal
                        </td>
                    </tr>
                    <tr>
                        <td><b>Tryout CPNS 110 Soal (100 Menit &bull; Standar BKN)</b></td>
                        <td style="text-align: center; color: #dc2626;">Terkunci</td>
                        <td style="text-align: center; font-weight: 700; color: #047857; background: #fffbeb;">
                            Bebas Diakses Kapan Saja
                        </td>
                    </tr>
                    <tr>
                        <td><b>Tryout RBB BUMN, Kedinasan &amp; Psikotes</b></td>
                        <td style="text-align: center; color: #dc2626;">Terkunci</td>
                        <td style="text-align: center; font-weight: 700; color: #047857; background: #fffbeb;">
                            Bebas Diakses Kapan Saja
                        </td>
                    </tr>
                    <tr>
                        <td><b>Kunci &amp; Pembahasan Trik Cepat</b></td>
                        <td style="text-align: center; color: var(--core-text-muted);">Terbatas</td>
                        <td style="text-align: center; font-weight: 700; color: #047857; background: #fffbeb;">
                            Full Pembahasan &amp; Konsep Dasar
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Riwayat Tagihan / Invoice Peserta -->
    <?php if (!empty($userTransactions)): ?>
        <div class="card" style="margin-bottom: 2.5rem;">
            <h3 class="ui-inline" style="font-size: 1.15rem; font-weight: 800; color: var(--core-navy); margin-bottom: 1rem;">
                <?= ui_icon('file-text', 18) ?> Riwayat Pembayaran Anda
            </h3>
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No. Invoice</th>
                            <th>Metode</th>
                            <th>Nominal</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th style="text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($userTransactions as $utx): ?>
                            <tr>
                                <td><b><?= htmlspecialchars($utx['invoice_number']) ?></b></td>
                                <td><span class="badge badge-navy"><?= htmlspecialchars($utx['payment_method'] ?: 'Payment Gateway') ?></span></td>
                                <td><b>Rp <?= number_format((int)$utx['amount'], 0, ',', '.') ?></b></td>
                                <td style="font-size: 0.82rem; color: var(--core-text-muted);"><?= date('d/m/Y H:i', strtotime($utx['created_at'])) ?></td>
                                <td>
                                    <?php if ($utx['status'] === 'approved'): ?>
                                        <span class="badge badge-success">Lunas / PRO Aktif</span>
                                    <?php elseif ($utx['status'] === 'pending' && !isTransactionLinkExpired($utx)): ?>
                                        <span class="badge badge-gold">Menunggu Pembayaran</span>
                                    <?php elseif ($utx['status'] === 'pending'): ?>
                                        <span class="badge badge-danger">Kedaluwarsa</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Dibatalkan</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <?php if ($utx['status'] === 'pending' && isTransactionLinkExpired($utx)): ?>
                                        <a href="payment_checkout.php?invoice=<?= urlencode($utx['invoice_number']) ?>&amp;new_link=1" class="btn btn-gold btn-sm">
                                            <?= ui_icon('refresh', 13) ?> Buat Link Baru
                                        </a>
                                    <?php else: ?>
                                        <a href="payment_checkout.php?invoice=<?= urlencode($utx['invoice_number']) ?><?= $utx['status'] === 'pending' ? '&amp;autopay=1' : '' ?>" class="btn btn-secondary btn-sm">
                                            <?= $utx['status'] === 'pending' ? (ui_icon('credit-card', 13) . ' Bayar Sekarang') : (ui_icon('file-text', 13) . ' Kwitansi') ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function updateGatewaySelection(provider) {
    document.querySelectorAll('.gw-option-card').forEach(function(card) {
        const key = card.getAttribute('data-gw-card');
        card.classList.toggle('is-selected', key === provider);
    });
    const labelMap = { doku: 'DOKU', midtrans: 'Midtrans' };
    const nameLabel = labelMap[provider] || 'Gateway';
    const btnLbl = document.getElementById('gwSubmitLabel');
    const note = document.getElementById('gwSubmitNote');
    if (btnLbl) {
        btnLbl.textContent = 'Bayar Sekarang via ' + nameLabel + ' (Rp <?= number_format(PRO_VIP_BASE_PRICE, 0, ',', '.') ?>)';
    }
    if (note) {
        note.innerHTML = 'Jendela pembayaran <b>' + nameLabel + '</b> langsung terbuka otomatis &bull; Akun PRO VIP aktif detik itu juga setelah bayar.';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
