<?php
// sync_db.php - Tool sinkronisasi cepat database SIAP TES ke MySQL & SQLite
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');

$db = getDB();
$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

// Hitung kapasitas bank soal di database/all_questions.json
$jsonPath = __DIR__ . '/database/all_questions.json';
$jsonTotal = 0;
if (file_exists($jsonPath)) {
    @ini_set('memory_limit', '256M');
    $tmpJson = json_decode(file_get_contents($jsonPath), true);
    if (is_array($tmpJson) && isset($tmpJson['questions'])) {
        $jsonTotal = count($tmpJson['questions']);
    }
    unset($tmpJson);
}

$syncMessage = '';
if (isset($_POST['sync'])) {
    require_once __DIR__ . '/database/seed_siaptes.php';
    $newTotal = (int)$db->query("SELECT COUNT(*) FROM questions")->fetchColumn();
    $addedNow = isset($seedAddedCount) ? (int)$seedAddedCount : 0;
    $syncMessage = "✅ Berhasil disinkronkan! Ditambahkan <b>" . number_format($addedNow) . " soal baru</b> — total sekarang <b>" . number_format($newTotal) . " butir soal</b> di database aktif (" . strtoupper($driver) . ").";
}

$qCount = (int)$db->query("SELECT COUNT(*) FROM questions")->fetchColumn();
$cCount = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$pCount = (int)$db->query("SELECT COUNT(*) FROM packages")->fetchColumn();
$catBreakdown = $db->query("
    SELECT c.id, c.code, c.name, c.category_group, c.passing_grade, COUNT(q.id) as total_q
    FROM categories c
    LEFT JOIN questions q ON q.category_id = c.id
    GROUP BY c.id
    ORDER BY c.id ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sinkronisasi Database SIAP TES</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f6f8fb; color: #1e293b; padding: 1.5rem; margin: 0; }
        .box { max-width: 780px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: 16px; box-shadow: 0 6px 24px rgba(30,41,59,0.07); border: 1px solid #e3e9f2; border-top: 4px solid #2d4f8e; }
        h1 { margin-top: 0; font-size: 1.45rem; color: #1e293b; }
        .btn { display: inline-block; padding: 0.8rem 1.5rem; background: #2d4f8e; color: #fff; font-weight: 700; text-decoration: none; border-radius: 10px; border: none; cursor: pointer; font-size: 0.96rem; }
        .btn:hover { background: #233f75; }
        .success { background: #e7f6f1; color: #1a6853; padding: 1rem; border-radius: 10px; margin: 1rem 0; border: 1px solid #c2e8db; }
        .info { background: #eef3fb; color: #2d4f8e; padding: 0.85rem 1rem; border-radius: 10px; margin: 1rem 0; font-size: 0.9rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; font-size: 0.88rem; }
        th, td { padding: 0.6rem 0.75rem; border-bottom: 1px solid #eef2f7; text-align: left; }
        th { background: #f6f9fd; color: #4b5e76; font-size: 0.76rem; text-transform: uppercase; }
    </style>
</head>
<body>
<div class="box">
    <h1>🔄 Status Sinkronisasi Bank Soal SIAP TES</h1>
    <div class="info">
        Koneksi Web Aktif: <b><?= strtoupper($driver) ?></b> &bull;
        Paket Bank Soal Siap Impor (<code>all_questions.json</code>): <b><?= number_format($jsonTotal) ?> butir soal</b>
    </div>

    <?php if ($syncMessage !== ''): ?>
        <div class="success"><?= $syncMessage ?></div>
    <?php endif; ?>

    <p>Jumlah Data Saat Ini di Database Aktif:</p>
    <ul>
        <li>Kategori Ujian: <b><?= $cCount ?></b></li>
        <li>Paket Tryout: <b><?= $pCount ?></b></li>
        <li>Bank Soal Terpasang: <b><?= number_format($qCount) ?> butir soal</b></li>
    </ul>

    <form method="POST" style="margin: 1.25rem 0;">
        <button type="submit" name="sync" class="btn">
            ⚡ Sinkronkan Bank Soal Terbaru (<?= number_format($jsonTotal ?: 7010) ?> Butir Soal) Sekarang
        </button>
    </form>

    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Kategori</th>
                    <th>Grup</th>
                    <th>Passing Grade</th>
                    <th style="text-align:right;">Jumlah Soal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($catBreakdown as $cb): ?>
                <tr>
                    <td><b><?= htmlspecialchars($cb['code']) ?></b></td>
                    <td><?= htmlspecialchars($cb['name']) ?></td>
                    <td><?= htmlspecialchars(strtoupper((string)$cb['category_group'])) ?></td>
                    <td><?= (int)$cb['passing_grade'] ?></td>
                    <td style="text-align:right;"><b><?= number_format((int)$cb['total_q']) ?></b></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap;">
        <a href="admin/soal.php" style="color: #2d4f8e; text-decoration: none; font-weight: 700;">← Kelola Bank Soal (Admin)</a>
        <a href="tryout.php" style="color: #23866b; text-decoration: none; font-weight: 700;">🚀 Buka Halaman Tryout CAT</a>
    </div>
</div>
</body>
</html>
