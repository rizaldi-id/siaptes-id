<?php
// riwayat.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();
$db = getDB();
$user = currentUser();

$attempts = $db->query("
    SELECT ea.*, p.title as package_title, p.category_group
    FROM exam_attempts ea
    LEFT JOIN packages p ON ea.package_id = p.id
    WHERE ea.user_id = {$user['id']} AND ea.status = 'completed'
    ORDER BY ea.end_time DESC
")->fetchAll();

$page_title = "Riwayat Simulasi Tryout & Skor - SIAP TES";
require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 2rem;">
    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 0.25rem;">
        <span class="badge badge-navy">LOG REKAPITULASI</span>
        <span style="font-size: 0.8rem; color: var(--core-text-muted);">siaptes.id &bull; Transkrip Evaluasi Kompetensi</span>
    </div>
    <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--core-navy); margin-bottom: 0.5rem;">Transkrip & Riwayat Tryout</h1>
    <p style="color: var(--core-text-muted); max-width: 820px; font-size: 0.95rem;">
        Pantau riwayat perkembangan skor Anda dan tinjau kembali pembahasan kunci jawaban untuk setiap simulasi yang telah Anda selesaikan di platform <b>siaptes.id</b>.
    </p>
</div>

<div class="card card-navy-top">
    <?php if (empty($attempts)): ?>
        <div style="text-align: center; padding: 3rem 1rem;">
            <p style="font-size: 1.1rem; color: var(--core-text-muted); margin-bottom: 1rem;">Anda belum pernah menyelesaikan sesi simulasi tryout atau latihan.</p>
            <a href="tryout.php" class="btn btn-primary">Mulai Simulasi Tryout Sekarang ➔</a>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Waktu Selesai</th>
                        <th>Paket / Mode Ujian</th>
                        <th>Durasi Pengerjaan</th>
                        <th>Total Skor</th>
                        <th>Status Kelulusan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attempts as $row): 
                        $durMinutes = round(($row['duration_seconds'] ?? 0) / 60);
                    ?>
                        <tr>
                            <td style="font-size: 0.875rem; color: var(--core-text-muted);">
                                <?= date('d M Y, H:i', strtotime($row['end_time'])) ?> WIB
                            </td>
                            <td>
                                <b style="color: var(--core-navy);"><?= htmlspecialchars($row['package_title'] ?? 'Latihan Mandiri (' . strtoupper($row['mode']) . ')') ?></b>
                            </td>
                            <td style="font-size: 0.85rem; color: var(--core-text);">
                                ⏱️ <?= $durMinutes ?> Menit
                            </td>
                            <td>
                                <span style="font-size: 1.15rem; font-weight: 800; color: var(--core-navy);"><?= $row['total_score'] ?></span>
                            </td>
                            <td>
                                <?php if ($row['mode'] === 'tryout'): ?>
                                    <?php if ($row['is_passed']): ?>
                                        <span class="badge badge-success">Lulus PG ✔</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Di Bawah PG ✖</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge badge-navy">Latihan Mandiri</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="hasil.php?attempt_id=<?= $row['id'] ?>" class="btn btn-outline btn-sm">
                                    Buka Pembahasan ➔
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
