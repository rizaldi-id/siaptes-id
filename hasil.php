<?php
// hasil.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();
$db = getDB();
$user = currentUser();

$attempt_id = intval($_GET['attempt_id'] ?? 0);
$attempt = $db->query("
    SELECT ea.*, p.title as package_title, p.duration_minutes
    FROM exam_attempts ea
    LEFT JOIN packages p ON ea.package_id = p.id
    WHERE ea.id = $attempt_id AND (ea.user_id = {$user['id']} OR '{$user['role']}' = 'admin')
")->fetch();

if (!$attempt) {
    header('Location: riwayat.php?msg=Hasil ujian tidak ditemukan');
    exit;
}

// Fetch all questions, answers, and explanations
// Fetch all questions, answers, and explanations
$results = $db->query("
    SELECT ua.*, 
           q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, q.option_e, 
           q.correct_option, q.explanation,
           q.score_a, q.score_b, q.score_c, q.score_d, q.score_e,
           c.code as cat_code, c.name as cat_name, c.passing_grade as cat_pg
    FROM user_answers ua
    JOIN questions q ON ua.question_id = q.id
    JOIN categories c ON q.category_id = c.id
    WHERE ua.attempt_id = $attempt_id
    ORDER BY ua.id ASC
")->fetchAll();

// Dynamic category grouping
$catSummaries = [];
foreach ($results as $item) {
    $code = $item['cat_code'];
    if (!isset($catSummaries[$code])) {
        $catSummaries[$code] = [
            'code' => $code,
            'name' => $item['cat_name'],
            'pg' => $item['cat_pg'] ?? 65,
            'score' => 0,
            'total_q' => 0,
            'max_score' => 0
        ];
    }
    $catSummaries[$code]['score'] += (int)$item['score_obtained'];
    $catSummaries[$code]['total_q']++;
    $catSummaries[$code]['max_score'] += 5;
}

$page_title = "Hasil & Evaluasi Skor - SIAP TES";
require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 0.25rem;">
                <span class="badge badge-navy">EVALUASI SIMULASI TES</span>
                <span style="font-size: 0.8rem; color: var(--core-text-muted);">Sistem Otentik siaptes.id</span>
            </div>
            <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--core-navy);">
                Hasil & Evaluasi: <?= htmlspecialchars($attempt['package_title'] ?? 'Latihan Soal Mandiri') ?>
            </h1>
            <p style="color: var(--core-text-muted); font-size: 0.875rem;">
                Selesai pada: <?= date('d M Y, H:i', strtotime($attempt['end_time'] ?? 'now')) ?> WIB &bull; Durasi Sesi: <?= round(($attempt['duration_seconds'] ?? 0) / 60) ?> Menit
            </p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="riwayat.php" class="btn btn-secondary btn-sm">📁 Riwayat Skor</a>
            <a href="tryout.php" class="btn btn-primary btn-sm">🚀 Simulasi Lainnya</a>
        </div>
    </div>
</div>

<!-- Kartu Ringkasan Nilai & Passing Grade Coretax Style -->
<div class="card" style="border-top: 5px solid <?= $attempt['is_passed'] ? 'var(--core-success)' : 'var(--core-danger)' ?>; margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="font-size: 0.825rem; font-weight: 700; color: var(--core-text-muted); text-transform: uppercase; letter-spacing: 0.5px;">TOTAL AKUMULASI SKOR</div>
            <div style="font-size: 3.25rem; font-weight: 800; color: var(--core-navy); line-height: 1.1;">
                <?= $attempt['total_score'] ?>
            </div>
        </div>

        <div>
            <?php if ($attempt['mode'] === 'tryout'): ?>
                <?php if ($attempt['is_passed']): ?>
                    <div style="background: #d1fae5; color: #065f46; padding: 0.75rem 1.5rem; border-radius: var(--radius-md); font-weight: 700; font-size: 1.1rem; border: 1px solid #a7f3d0; text-align: right;">
                        🎉 MEMENUHI STANDAR KELULUSAN
                    </div>
                <?php else: ?>
                    <div style="background: #fee2e2; color: #991b1b; padding: 0.75rem 1.5rem; border-radius: var(--radius-md); font-weight: 700; font-size: 1.1rem; border: 1px solid #fecaca; text-align: right;">
                        ⚠️ BELUM MEMENUHI PASSING GRADE
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div style="background: #e0f2fe; color: #0369a1; padding: 0.75rem 1.5rem; border-radius: var(--radius-md); font-weight: 700; font-size: 1rem;">
                    Mode Latihan Mandiri Selesai
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Rincian Nilai Per Kategori Dinamis -->
    <div class="grid-3">
        <?php foreach ($catSummaries as $cCode => $cData): 
            $pct = ($cData['max_score'] > 0) ? round(($cData['score'] / $cData['max_score']) * 100) : 0;
            $passedSub = ($pct >= 60);
        ?>
            <div style="border: 1px solid var(--core-border); border-radius: var(--radius-sm); padding: 1rem; background: var(--gray-50);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="badge badge-<?= strtolower($cCode) ?>"><?= $cCode ?></span>
                    <span style="font-size: 0.78rem; color: var(--core-text-muted);"><?= $cData['total_q'] ?> Butir Soal</span>
                </div>
                <div style="font-size: 1.1rem; font-weight: 700; color: var(--core-navy); margin-top: 0.35rem;">
                    <?= htmlspecialchars($cData['name']) ?>
                </div>
                <div style="font-size: 2rem; font-weight: 800; margin-top: 0.35rem; color: var(--core-navy);">
                    <?= $cData['score'] ?> <span style="font-size: 0.9rem; font-weight: 600; color: var(--core-text-muted);">/ <?= $cData['max_score'] ?></span>
                </div>
                <div style="font-size: 0.85rem; font-weight: 600; margin-top: 0.25rem;">
                    Akurasi: <?= $pct ?>% &bull; <?= $passedSub ? '<span style="color:var(--core-success);">Optimal ✔</span>' : '<span style="color:var(--core-danger);">Perlu Ditingkatkan ✖</span>' ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Pembahasan Soal Lengkap -->
<div style="margin-top: 2.5rem;">
    <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--core-navy); margin-bottom: 1.25rem;">
        Kunci Jawaban & Pembahasan Lengkap
    </h2>

    <?php foreach ($results as $index => $item): 
        $isWeighted = ($item['score_a'] > 0 || $item['score_b'] > 0);
        $isCorrect = (!$isWeighted && $item['selected_option'] !== null && $item['selected_option'] === $item['correct_option']);
        $isSkipped = empty($item['selected_option']);
    ?>
        <div class="card" style="margin-bottom: 1.5rem; border-left: 4px solid <?= $isWeighted ? '#f59e0b' : ($isCorrect ? 'var(--core-success)' : ($isSkipped ? 'var(--gray-300)' : 'var(--core-danger)')) ?>;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <span style="font-weight: 700; color: var(--core-navy);">Soal No. <?= $index + 1 ?></span>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <span class="badge badge-<?= strtolower($item['cat_code']) ?>"><?= $item['cat_code'] ?> - <?= $item['cat_name'] ?></span>
                    <span style="font-weight: 700; font-size: 0.9rem; padding: 2px 8px; border-radius: var(--radius-sm); background: var(--gray-100); color: var(--core-navy);">
                        Skor Diperoleh: +<?= $item['score_obtained'] ?>
                    </span>
                </div>
            </div>

            <div style="font-size: 1rem; line-height: 1.6; margin-bottom: 1rem; white-space: pre-line;">
                <?= htmlspecialchars($item['question_text']) ?>
            </div>

            <!-- Opsi Jawaban -->
            <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1rem;">
                <?php 
                $options = [
                    'A' => ['text' => $item['option_a'], 'score' => $item['score_a']],
                    'B' => ['text' => $item['option_b'], 'score' => $item['score_b']],
                    'C' => ['text' => $item['option_c'], 'score' => $item['score_c']],
                    'D' => ['text' => $item['option_d'], 'score' => $item['score_d']],
                    'E' => ['text' => $item['option_e'], 'score' => $item['score_e']],
                ];

                foreach ($options as $optKey => $optData): 
                    $isSelected = ($item['selected_option'] === $optKey);
                    $isTheKey = (!$isWeighted && $item['correct_option'] === $optKey);
                    
                    $bgColor = '#fff';
                    $borderColor = 'var(--gray-200)';
                    if ($isSelected) {
                        $borderColor = 'var(--core-navy)';
                        $bgColor = 'var(--core-navy-subtle)';
                    }
                    if ($isTheKey) {
                        $borderColor = 'var(--core-success)';
                        $bgColor = '#ecfdf5';
                    }
                ?>
                    <div style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.65rem 0.85rem; border: 1.5px solid <?= $borderColor ?>; background: <?= $bgColor ?>; border-radius: var(--radius-sm); font-size: 0.9rem;">
                        <span style="font-weight: 700; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: <?= $isSelected ? 'var(--core-navy)' : 'var(--gray-200)' ?>; color: <?= $isSelected ? '#fff' : 'var(--core-navy)' ?>; flex-shrink: 0; font-size: 0.8rem;">
                            <?= $optKey ?>
                        </span>
                        <div style="flex: 1;">
                            <?= htmlspecialchars($optData['text']) ?>
                        </div>
                        <?php if ($isSelected): ?>
                            <span style="font-size: 0.75rem; font-weight: 700; color: var(--core-navy);">[Jawaban Anda]</span>
                        <?php endif; ?>
                        <?php if ($isTheKey): ?>
                            <span style="font-size: 0.75rem; font-weight: 700; color: var(--core-success);">[Kunci Benar]</span>
                        <?php endif; ?>
                        <?php if ($isWeighted && $optData['score'] > 0): ?>
                            <span style="font-size: 0.75rem; font-weight: 700; color: var(--core-gold-text); background: var(--core-gold-subtle); padding: 2px 6px; border-radius: 3px;">(Bobot: <?= $optData['score'] ?> Poin)</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Box Penjelasan / Pembahasan -->
            <?php if (isPremiumUser() || $index < 3): ?>
                <div style="background: var(--gray-100); border-left: 3px solid var(--primary); padding: 0.85rem 1rem; border-radius: var(--radius-sm); font-size: 0.9rem;">
                    <b style="color: var(--primary); display: block; margin-bottom: 0.25rem;">📝 Pembahasan Soal:</b>
                    <div style="color: var(--gray-700); line-height: 1.5;">
                        <?= nl2br(htmlspecialchars($item['explanation'] ?: 'Tidak ada pembahasan tambahan.')) ?>
                    </div>
                </div>
            <?php else: ?>
                <div style="background: #fffbeb; border: 1px dashed #f59e0b; border-left: 4px solid #f59e0b; padding: 0.9rem 1.15rem; border-radius: var(--radius-sm); font-size: 0.88rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
                    <div style="color: #92400e;">
                        <b>🔒 Pembahasan Lengkap Khusus Member PRO VIP</b><br>
                        <span>Buka seluruh pembahasan trik cepat dan semua paket tryout hanya dengan <b>Rp 29.000 via QRIS</b>.</span>
                    </div>
                    <a href="upgrade.php" class="btn btn-gold btn-sm">📱 Buka PRO Rp 29.000 (QRIS) ➔</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
