<?php
// sync_db.php - Tool sinkronisasi cepat database SIAP TES ke MySQL & SQLite
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sinkronisasi Database SIAP TES</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #0b2d52; padding: 2rem; }
        .box { max-width: 650px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border-top: 4px solid #0b2d52; }
        h1 { margin-top: 0; font-size: 1.4rem; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; background: #fab005; color: #0b2d52; font-weight: bold; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; font-size: 1rem; }
        .success { background: #dcfce7; color: #166534; padding: 1rem; border-radius: 4px; margin-top: 1rem; }
        .info { background: #e0f2fe; color: #0369a1; padding: 0.85rem; border-radius: 4px; margin: 1rem 0; font-size: 0.9rem; }
    </style>
</head>
<body>
<div class="box">
    <h1>🔄 Status Sinkronisasi Database SIAP TES</h1>
    <?php
    $db = getDB();
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "<div class='info'>Koneksi Web Aktif Menggunakan: <b>" . strtoupper($driver) . "</b></div>";

    if (isset($_POST['sync'])) {
        require_once __DIR__ . '/database/seed_siaptes.php';
        $newTotal = $db->query("SELECT COUNT(*) FROM questions")->fetchColumn();
        echo "<div class='success'>✅ Berhasil disinkronkan! Total <b>" . number_format($newTotal) . " butir soal</b> dan 10 kategori telah tersedia di dalam database aktif (" . strtoupper($driver) . ").</div>";
    }

    $qCount = $db->query("SELECT COUNT(*) FROM questions")->fetchColumn();
    $cCount = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $pCount = $db->query("SELECT COUNT(*) FROM packages")->fetchColumn();
    ?>
    <p>Jumlah Data Saat Ini di Database Web:</p>
    <ul>
        <li>Kategori Ujian: <b><?= $cCount ?></b></li>
        <li>Paket Tryout: <b><?= $pCount ?></b></li>
        <li>Bank Soal: <b><?= number_format($qCount) ?> butir soal</b></li>
    </ul>

    <form method="POST">
        <button type="submit" name="sync" class="btn">⚡ Klik Di Sini untuk Sinkronkan 3.800+ Bank Soal Sekarang</button>
    </form>
    <br>
    <a href="latihan.php" style="color: #0284c7; text-decoration: none; font-weight: bold;">← Kembali ke Halaman Bank Soal</a>
</div>
</body>
</html>
