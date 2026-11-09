<?php
// admin/index.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();
$db = getDB();

// Handle Approve / Reject Transaction from Admin Panel
if (isset($_GET['tx_action']) && isset($_GET['tx_id'])) {
    $txId = (int)$_GET['tx_id'];
    $txAction = $_GET['tx_action'];
    $stmtTx = $db->prepare("SELECT * FROM transactions WHERE id = ?");
    $stmtTx->execute([$txId]);
    $targetTx = $stmtTx->fetch();

    if ($targetTx) {
        if ($txAction === 'approve') {
            $db->prepare("UPDATE transactions SET status = 'approved', updated_at = ? WHERE id = ?")
               ->execute([date('Y-m-d H:i:s'), $txId]);
            $db->prepare("UPDATE users SET is_premium = 1 WHERE id = ?")
               ->execute([$targetTx['user_id']]);
            header('Location: index.php?msg=' . urlencode('Pembayaran QRIS ' . $targetTx['invoice_number'] . ' berhasil disetujui & akun PRO VIP diaktifkan!'));
            exit;
        } elseif ($txAction === 'reject') {
            $db->prepare("UPDATE transactions SET status = 'rejected', updated_at = ? WHERE id = ?")
               ->execute([date('Y-m-d H:i:s'), $txId]);
            header('Location: index.php?msg=' . urlencode('Tagihan ' . $targetTx['invoice_number'] . ' telah dibatalkan.'));
            exit;
        }
    }
}

// Statistics
$totalQuestions = $db->query("SELECT COUNT(*) FROM questions")->fetchColumn();
$totalUsers = $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalPackages = $db->query("SELECT COUNT(*) FROM packages")->fetchColumn();
$totalAttempts = $db->query("SELECT COUNT(*) FROM exam_attempts WHERE status = 'completed'")->fetchColumn();

$totalPremiumUsers = $db->query("SELECT COUNT(*) FROM users WHERE is_premium = 1 AND role = 'user'")->fetchColumn();
$totalTransactions = $db->query("SELECT COUNT(*) FROM transactions WHERE status = 'approved'")->fetchColumn();
$totalRevenue = $db->query("SELECT SUM(amount) FROM transactions WHERE status = 'approved'")->fetchColumn() ?: 0;

// Recent transactions
$recentTransactions = $db->query("
    SELECT t.*, u.name as user_name, u.email as user_email
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    ORDER BY (t.status = 'pending') DESC, t.created_at DESC
    LIMIT 15
")->fetchAll();

// Registered users in database
$registeredUsers = $db->query("
    SELECT id, name, email, role, is_premium, google_id, created_at
    FROM users
    ORDER BY id DESC
    LIMIT 25
")->fetchAll();

// Recent attempts
$recentAttempts = $db->query("
    SELECT ea.*, u.name as user_name, u.email as user_email, p.title as package_title
    FROM exam_attempts ea
    JOIN users u ON ea.user_id = u.id
    LEFT JOIN packages p ON ea.package_id = p.id
    WHERE ea.status = 'completed'
    ORDER BY ea.end_time DESC
    LIMIT 10
")->fetchAll();

$page_title = "Panel Administrasi";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 0.25rem;">
            <span class="badge badge-navy">PORTAL ADMINISTRATOR</span>
            <span style="font-size: 0.8rem; color: var(--core-text-muted);">siaptes.id Pusat Kontrol</span>
        </div>
        <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--core-navy);">Dashboard Administrasi SIAP TES</h1>
        <p style="color: var(--core-text-muted); font-size: 0.95rem;">Kelola bank soal multi-kategori, paket tryout (CPNS, BUMN, Kedinasan, Psikotes), dan transaksi peserta.</p>
    </div>
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <a href="artikel.php" class="btn btn-gold btn-sm">📰 Kelola Artikel SEO</a>
        <a href="soal.php" class="btn btn-primary btn-sm">📋 Kelola Bank Soal</a>
        <a href="paket.php" class="btn btn-secondary btn-sm">📦 Kelola Paket Tryout</a>
    </div>
</div>

<div class="grid-3" style="margin-bottom: 2rem;">
    <div class="stat-card twk">
        <div class="stat-label">BANK SOAL AKTIF</div>
        <div class="stat-val"><?= $totalQuestions ?></div>
        <p style="font-size: 0.825rem; color: var(--core-text-muted); margin-top: 0.25rem;"><a href="soal.php" style="color: var(--core-navy); font-weight: 700;">Kelola / Tambah Soal →</a></p>
    </div>
    <div class="stat-card tiu">
        <div style="color: var(--gray-600); font-size: 0.85rem; font-weight: 600;">MEMBER PRO VIP (BERBAYAR)</div>
        <div class="stat-val"><?= $totalPremiumUsers ?> <span style="font-size: 0.95rem; font-weight: 400; color: var(--gray-600);">/ <?= $totalUsers ?> Peserta</span></div>
        <p style="font-size: 0.85rem; color: var(--gray-600); margin-top: 0.25rem;">Total Pendapatan: <b>Rp <?= number_format($totalRevenue, 0, ',', '.') ?></b></p>
    </div>
    <div class="stat-card tkp">
        <div style="color: var(--gray-600); font-size: 0.85rem; font-weight: 600;">PAKET SIMULASI AKTIF</div>
        <div class="stat-val"><?= $totalPackages ?></div>
        <p style="font-size: 0.85rem; color: var(--gray-600); margin-top: 0.25rem;"><a href="paket.php">Kelola Paket Trial & Pro →</a></p>
    </div>
</div>

<div class="card" style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0;">Transaksi Upgrade PRO VIP Terbaru</h2>
        <span class="badge" style="background: #d1fae5; color: #065f46; font-size: 0.8rem; font-weight: 700;">
            Total: <?= $totalTransactions ?> Transaksi Berhasil
        </span>
    </div>

    <?php if (empty($recentTransactions)): ?>
        <p style="color: var(--gray-600);">Belum ada riwayat transaksi upgrade.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Nama Pengguna</th>
                        <th>Email</th>
                        <th>Metode Bayar</th>
                        <th>Nominal</th>
                        <th>Waktu Transaksi</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTransactions as $tx): ?>
                        <tr>
                            <td><b style="color: var(--primary);"><?= htmlspecialchars($tx['invoice_number']) ?></b></td>
                            <td><?= htmlspecialchars($tx['user_name']) ?></td>
                            <td style="font-size: 0.85rem; color: var(--gray-600);"><?= htmlspecialchars($tx['user_email']) ?></td>
                            <td><?= htmlspecialchars($tx['payment_method']) ?></td>
                            <td><b>Rp <?= number_format($tx['amount'], 0, ',', '.') ?></b></td>
                            <td style="font-size: 0.85rem; color: var(--gray-600);"><?= date('d/m/Y H:i', strtotime($tx['created_at'])) ?></td>
                            <td>
                                <span class="badge badge-success">Lunas / Aktif</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Daftar Akun Peserta di Database -->
<div class="card" style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
        <div>
            <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0;">Daftar Akun Peserta Terdaftar di Database (Tabel `users`)</h2>
            <p style="font-size: 0.82rem; color: var(--core-text-muted); margin: 0.2rem 0 0;">Setiap pendaftar baru (via Formulir Email maupun Google OAuth) otomatis tercatat di sini dengan status awal Trial Gratis.</p>
        </div>
        <span class="badge badge-navy">Total: <?= $totalUsers ?> Peserta</span>
    </div>

    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Lengkap</th>
                    <th>Email</th>
                    <th>Metode Daftar</th>
                    <th>Status Akses</th>
                    <th>Tanggal Daftar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registeredUsers as $uRow): ?>
                    <tr>
                        <td><b>#<?= (int)$uRow['id'] ?></b></td>
                        <td><b><?= htmlspecialchars($uRow['name']) ?></b></td>
                        <td><?= htmlspecialchars($uRow['email']) ?></td>
                        <td>
                            <?php if (!empty($uRow['google_id'])): ?>
                                <span class="badge" style="background: #e0f2fe; color: #0369a1;">Google OAuth</span>
                            <?php else: ?>
                                <span class="badge" style="background: #f1f5f9; color: #475569;">Form Email & Sandi</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($uRow['role'] === 'admin'): ?>
                                <span class="badge badge-navy">ADMIN</span>
                            <?php elseif (!empty($uRow['is_premium'])): ?>
                                <span class="badge badge-gold">⭐ PRO VIP (Lunas Rp 29.000)</span>
                            <?php else: ?>
                                <span class="badge" style="background: #f1f5f9; color: #64748b;">Trial Gratis (Belum Bayar)</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size: 0.82rem; color: var(--gray-600);">
                            <?= !empty($uRow['created_at']) ? date('d/m/Y H:i', strtotime($uRow['created_at'])) : '-' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem;">10 Aktivitas Simulasi Ujian Terbaru</h2>
    
    <?php if (empty($recentAttempts)): ?>
        <p style="color: var(--gray-600);">Belum ada riwayat aktivitas ujian peserta.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Peserta</th>
                        <th>Paket / Latihan</th>
                        <th>Waktu Selesai</th>
                        <th>TWK</th>
                        <th>TIU</th>
                        <th>TKP</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentAttempts as $at): ?>
                        <tr>
                            <td>
                                <b><?= htmlspecialchars($at['user_name']) ?></b><br>
                                <span style="font-size: 0.75rem; color: var(--gray-600);"><?= htmlspecialchars($at['user_email']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($at['package_title'] ?? 'Latihan Mandiri') ?></td>
                            <td style="font-size: 0.85rem; color: var(--gray-600);">
                                <?= date('d/m/Y H:i', strtotime($at['end_time'])) ?>
                            </td>
                            <td><?= $at['score_twk'] ?></td>
                            <td><?= $at['score_tiu'] ?></td>
                            <td><?= $at['score_tkp'] ?></td>
                            <td><b><?= $at['total_score'] ?></b></td>
                            <td>
                                <?php if ($at['is_passed']): ?>
                                    <span class="badge badge-success">Lulus</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Tidak Lulus</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="../hasil.php?attempt_id=<?= $at['id'] ?>" class="btn btn-outline btn-sm">Review</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
