<?php
// upgrade.php - Halaman Aktivasi PRO VIP Rp 29.000 Khusus Pembayaran QRIS Otomatis
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

// Cek apakah user memiliki tagihan QRIS yang masih berstatus 'pending'
$stmtPending = $db->prepare("SELECT * FROM transactions WHERE user_id = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1");
$stmtPending->execute([$user['id']]);
$pendingTx = $stmtPending->fetch();

// Handle pembuatan invoice QRIS Rp 29.000 -> arahkan ke Payment Gateway (payment_checkout.php)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $pkg_id = !empty($_POST['package_id']) ? intval($_POST['package_id']) : null;
    $finalAmount = PRO_VIP_BASE_PRICE; // Rp 29.000
    $payment_method = 'QRIS';
    $notes = 'Aktivasi PRO VIP All-Access via QRIS Nasional';

    // Jika sudah ada invoice pending dengan nominal Rp 29.000, langsung arahkan ke invoice tersebut
    if ($pendingTx && (int)$pendingTx['amount'] === $finalAmount) {
        header("Location: payment_checkout.php?invoice=" . urlencode($pendingTx['invoice_number']));
        exit;
    }

    // Batalkan invoice pending lama bila ada
    $db->prepare("UPDATE transactions SET status = 'rejected' WHERE user_id = ? AND status = 'pending'")
       ->execute([$user['id']]);

    // Buat nomor invoice baru
    $invNumber = 'INV-QRIS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
    $now = date('Y-m-d H:i:s');

    // Simpan dengan status 'pending' (Akun BELUM menjadi PRO sebelum QRIS dibayar & terkonfirmasi!)
    $stmt = $db->prepare("
        INSERT INTO transactions (invoice_number, user_id, package_id, type, amount, payment_method, status, notes, created_at, updated_at)
        VALUES (?, ?, ?, 'lifetime_premium', ?, ?, 'pending', ?, ?, ?)
    ");
    $stmt->execute([$invNumber, $user['id'], $pkg_id, $finalAmount, $payment_method, $notes, $now, $now]);

    // Arahkan ke halaman Payment Gateway QRIS
    header("Location: payment_checkout.php?invoice=" . urlencode($invNumber));
    exit;
}

// Ambil riwayat transaksi user ini
$stmtHistory = $db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmtHistory->execute([$user['id']]);
$userTransactions = $stmtHistory->fetchAll();

$page_title = "Upgrade PRO VIP Rp 29.000 via QRIS - SIAP TES";
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 940px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 2.25rem;">
        <div style="display: inline-flex; align-items: center; gap: 6px; margin-bottom: 0.5rem;">
            <span class="badge badge-gold" style="font-size: 0.8rem; padding: 0.35rem 0.85rem;">
                ⭐ ALL-ACCESS PASS SEUMUR HIDUP &bull; SIAPTES.ID
            </span>
        </div>
        <h1 style="font-size: 2.15rem; font-weight: 800; color: var(--core-navy); margin-top: 0.25rem; margin-bottom: 0.5rem;">
            Buka Seluruh Konten PRO Hanya <span style="color: #b45309;">Rp 29.000</span> via QRIS
        </h1>
        <p style="color: var(--core-text-muted); font-size: 1rem; max-width: 680px; margin: 0 auto;">
            Pembayaran praktis cukup <b>Scan QRIS</b> dari aplikasi bank atau e-wallet apa saja. Begitu pembayaran terkonfirmasi, akun Anda <b>otomatis berubah menjadi PRO VIP</b> detik itu juga!
        </p>
    </div>

    <!-- Peringatan jika ada Tagihan QRIS Pending yang Belum Dibayar -->
    <?php if ($pendingTx && !$isUserPremium): ?>
        <div class="gov-notice" style="border-left-color: var(--core-gold); background: #fffbeb; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.85rem;">
                <div class="gov-notice-icon" style="background: #fef3c7; color: #b45309;">📱</div>
                <div class="gov-notice-content">
                    <h4 style="color: #92400e;">Tagihan QRIS Anda Menunggu Pembayaran</h4>
                    <p style="color: #78350f; margin: 0;">
                        Invoice <b>#<?= htmlspecialchars($pendingTx['invoice_number']) ?></b> sebesar <b>Rp <?= number_format((int)$pendingTx['amount'], 0, ',', '.') ?></b> via <b>QRIS</b> belum diselesaikan.
                    </p>
                </div>
            </div>
            <a href="payment_checkout.php?invoice=<?= urlencode($pendingTx['invoice_number']) ?>" class="btn btn-gold btn-sm">
                📱 Tampilkan Kode QRIS & Bayar Sekarang ➔
            </a>
        </div>
    <?php endif; ?>

    <!-- Jika sudah menjadi Member PRO VIP -->
    <?php if ($isUserPremium): ?>
        <div class="card card-gold-top" style="text-align: center; padding: 2.25rem; margin-bottom: 2.5rem;">
            <span style="font-size: 2.75rem;">👑</span>
            <h3 style="color: var(--core-navy); font-size: 1.4rem; font-weight: 800; margin-top: 0.5rem; margin-bottom: 0.35rem;">
                Status Akun Anda: Member PRO VIP All-Access Aktif
            </h3>
            <p style="color: var(--core-text-muted); font-size: 0.95rem; max-width: 600px; margin: 0 auto 1.25rem;">
                Anda telah memiliki hak akses penuh tanpa batas ke seluruh paket tryout CPNS, BUMN, Kedinasan, Psikotes, kunci jawaban, dan pembahasan materi di <b>siaptes.id</b>.
            </p>
            <div style="display: flex; justify-content: center; gap: 0.75rem; flex-wrap: wrap;">
                <a href="tryout.php" class="btn btn-primary">🚀 Mulai Simulasi Tryout PRO ➔</a>
                <a href="latihan.php" class="btn btn-secondary">📚 Buka Bank Soal Lengkap</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Kotak Pembayaran QRIS Rp 29.000 -->
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
                    Satu kali pembayaran untuk membuka seluruh paket simulasi CAT, ratusan bank soal lintas kategori, evaluasi passing grade, dan trik pembahasan cepat.
                </p>

                <?php if ($selectedPackage): ?>
                    <div style="background: #eef6fc; border-left: 4px solid var(--core-blue); padding: 0.75rem 1rem; border-radius: var(--radius-sm); margin-bottom: 1rem; font-size: 0.84rem; color: var(--core-navy);">
                        📦 Membuka paket: <b><?= htmlspecialchars($selectedPackage['title']) ?></b> + <b>Seluruh Konten PRO Lainnya!</b>
                    </div>
                <?php endif; ?>

                <div style="margin-bottom: 1.5rem; background: var(--core-navy-subtle); padding: 1.25rem; border-radius: var(--radius-sm); border: 1px solid var(--core-border);">
                    <div style="font-size: 0.85rem; color: var(--core-text-muted); text-decoration: line-through;">Rp <?= number_format(PRO_VIP_STRIKE_PRICE, 0, ',', '.') ?></div>
                    <div style="display: flex; align-items: baseline; gap: 0.5rem;">
                        <span style="font-size: 2.45rem; font-weight: 900; color: var(--core-navy);">Rp <?= number_format(PRO_VIP_BASE_PRICE, 0, ',', '.') ?></span>
                        <span style="font-size: 0.85rem; color: var(--core-text-muted);">/ sekali bayar (Seumur Hidup)</span>
                    </div>
                    <span style="font-size: 0.8rem; color: var(--core-success); font-weight: 700;">⚡ Bebas biaya admin &bull; Langsung aktif otomatis setelah Scan QRIS</span>
                </div>

                <div style="font-size: 0.88rem; color: var(--core-text); line-height: 1.95;">
                    <div>✅ <b>CPNS / ASN:</b> Tryout Akbar SKD 110 Butir Berstandar CAT BKN</div>
                    <div>✅ <b>BUMN:</b> Simulasi TKD, Core Values AKHLAK & Bahasa Inggris</div>
                    <div>✅ <b>Kedinasan:</b> Modul Khusus PKN STAN, STIS, IPDN & Poltekip</div>
                    <div>✅ <b>Psikotes Kerja:</b> Latihan Penalaran Logika, Spasial & SJT</div>
                    <div>✅ <b>Bank Soal Intensif:</b> Buka kuota latihan 10, 20, 30 hingga 50 soal sekaligus</div>
                    <div>✅ <b>Pembahasan Lengkap:</b> Kunci Jawaban + Trik Cepat Menjawab</div>
                </div>
            </div>

            <!-- Kolom Kanan: Checkout Instan QRIS Rp 29.000 -->
            <div class="card card-navy-top">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--core-navy); margin: 0;">
                        📱 Pembayaran Instan via QRIS
                    </h3>
                    <span class="badge badge-success">OTOMATIS AKTIF</span>
                </div>
                <p style="font-size: 0.84rem; color: var(--core-text-muted); margin-bottom: 1.25rem;">
                    Sistem pembayaran terintegrasi dengan <b>QRIS Standar Pembayaran Nasional</b>. Cukup scan menggunakan salah satu aplikasi di HP Anda:
                </p>

                <form method="POST" action="upgrade.php">
                    <input type="hidden" name="package_id" value="<?= $package_id ?>">
                    <input type="hidden" name="payment_method" value="QRIS">

                    <!-- Kotak Metode QRIS -->
                    <div style="border: 2px solid var(--core-navy); background: #eef6fc; border-radius: var(--radius-md); padding: 1.15rem; margin-bottom: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem;">
                            <div style="display: flex; align-items: center; gap: 0.6rem;">
                                <span style="background: #dc2626; color: #fff; font-weight: 900; font-size: 0.85rem; padding: 4px 10px; border-radius: 6px; letter-spacing: 1px;">QRIS</span>
                                <b style="color: var(--core-navy); font-size: 0.96rem;">QRIS Nasional (Semua Bank & E-Wallet)</b>
                            </div>
                            <span style="font-weight: 800; color: #15803d; font-size: 0.85rem;">Rp 29.000</span>
                        </div>
                        <div style="font-size: 0.78rem; color: var(--core-text-muted); line-height: 1.5; margin-bottom: 0.75rem;">
                            Mendukung pembayaran langsung dari seluruh aplikasi perbankan dan dompet digital di Indonesia:
                        </div>
                        <div style="display: flex; gap: 0.4rem; flex-wrap: wrap; font-size: 0.72rem; font-weight: 700;">
                            <span style="background: #fff; border: 1px solid #cbd5e1; color: #0369a1; padding: 3px 8px; border-radius: 4px;">GoPay</span>
                            <span style="background: #fff; border: 1px solid #cbd5e1; color: #6d28d9; padding: 3px 8px; border-radius: 4px;">OVO</span>
                            <span style="background: #fff; border: 1px solid #cbd5e1; color: #1d4ed8; padding: 3px 8px; border-radius: 4px;">DANA</span>
                            <span style="background: #fff; border: 1px solid #cbd5e1; color: #c2410c; padding: 3px 8px; border-radius: 4px;">ShopeePay</span>
                            <span style="background: #fff; border: 1px solid #cbd5e1; color: #0b2d52; padding: 3px 8px; border-radius: 4px;">BCA Mobile</span>
                            <span style="background: #fff; border: 1px solid #cbd5e1; color: #0b2d52; padding: 3px 8px; border-radius: 4px;">Livin' Mandiri</span>
                            <span style="background: #fff; border: 1px solid #cbd5e1; color: #0b2d52; padding: 3px 8px; border-radius: 4px;">BRImo</span>
                            <span style="background: #fff; border: 1px solid #cbd5e1; color: #0b2d52; padding: 3px 8px; border-radius: 4px;">BNI Mobile</span>
                        </div>
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
                            <span style="font-weight: 700; color: var(--core-navy);">Total Pembayaran QRIS:</span>
                            <b style="font-size: 1.05rem; color: #b45309;">Rp 29.000</b>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-gold" style="width: 100%; font-size: 1.02rem; padding: 0.95rem; justify-content: center; box-shadow: 0 4px 14px rgba(250, 176, 5, 0.35);">
                        📱 Buat Kode QRIS & Bayar Rp 29.000 Sekarang ➔
                    </button>
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
                            ⭐ PRO VIP (Rp 29.000)
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
                        <td><b>Tryout Akbar SKD CPNS 110 Butir (100 Menit)</b></td>
                        <td style="text-align: center; color: #dc2626;">✖ Terkunci</td>
                        <td style="text-align: center; font-weight: 700; color: #047857; background: #fffbeb;">
                            ✔ Bebas Diakses Kapan Saja
                        </td>
                    </tr>
                    <tr>
                        <td><b>Tryout RBB BUMN, Kedinasan & Psikotes</b></td>
                        <td style="text-align: center; color: #dc2626;">✖ Terkunci</td>
                        <td style="text-align: center; font-weight: 700; color: #047857; background: #fffbeb;">
                            ✔ Bebas Diakses Kapan Saja
                        </td>
                    </tr>
                    <tr>
                        <td><b>Kunci & Pembahasan Trik Cepat</b></td>
                        <td style="text-align: center; color: var(--core-text-muted);">Terbatas</td>
                        <td style="text-align: center; font-weight: 700; color: #047857; background: #fffbeb;">
                            Full Pembahasan & Konsep Dasar
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Riwayat Tagihan / Invoice Peserta -->
    <?php if (!empty($userTransactions)): ?>
        <div class="card" style="margin-bottom: 2.5rem;">
            <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--core-navy); margin-bottom: 1rem;">
                🧾 Riwayat Pembayaran QRIS Anda
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
                                <td><span class="badge badge-navy">QRIS Nasional</span></td>
                                <td><b>Rp <?= number_format((int)$utx['amount'], 0, ',', '.') ?></b></td>
                                <td style="font-size: 0.82rem; color: var(--core-text-muted);"><?= date('d/m/Y H:i', strtotime($utx['created_at'])) ?></td>
                                <td>
                                    <?php if ($utx['status'] === 'approved'): ?>
                                        <span class="badge badge-success">Lunas / PRO Aktif</span>
                                    <?php elseif ($utx['status'] === 'pending'): ?>
                                        <span class="badge badge-gold">Menunggu Scan QRIS</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Dibatalkan</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <a href="payment_checkout.php?invoice=<?= urlencode($utx['invoice_number']) ?>" class="btn btn-secondary btn-sm">
                                        <?= $utx['status'] === 'pending' ? '📱 Buka QRIS' : '📄 Kwitansi' ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
