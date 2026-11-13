<?php
// database/seed_siaptes.php
require_once __DIR__ . '/../config/database.php';

$db = getDB();
@set_time_limit(300);

$isCli = (php_sapi_name() === 'cli');
if ($isCli) {
    echo "=== MEMULAI SINKRONISASI DATABASE SIAP TES (siaptes.id) ===\n";
}

// 1. Baca data dari all_questions.json
$jsonPath = __DIR__ . '/all_questions.json';
if (!file_exists($jsonPath)) {
    die("Error: File $jsonPath tidak ditemukan.\n");
}
$data = json_decode(file_get_contents($jsonPath), true);

// 2. Pastikan Kategori tersedia (non-destruktif)
$existingCats = array_map('intval', $db->query("SELECT id FROM categories")->fetchAll(PDO::FETCH_COLUMN));
$catStmt = $db->prepare("
    INSERT INTO categories (id, code, name, category_group, description, passing_grade)
    VALUES (?, ?, ?, ?, ?, ?)
");
foreach ($data['categories'] as $cat) {
    if (!in_array((int)$cat[0], $existingCats, true)) {
        $catStmt->execute([$cat[0], $cat[1], $cat[2], $cat[3], $cat[4], $cat[5]]);
    }
}

// 3. Pastikan Paket Tryout tersedia
$existingPkgs = array_map('intval', $db->query("SELECT id FROM packages")->fetchAll(PDO::FETCH_COLUMN));
$pkgStmt = $db->prepare("
    INSERT INTO packages (id, title, category_group, description, duration_minutes, is_premium, price, is_active)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
foreach ($data['packages'] as $pkg) {
    if (!in_array((int)$pkg[0], array_map('intval', $existingPkgs), true)) {
        $pkgStmt->execute([$pkg[0], $pkg[1], $pkg[2], $pkg[3], $pkg[4], $pkg[5], $pkg[6], $pkg[7]]);
    }
}

// 4. Ambil hash soal yang sudah ada di DB agar tidak duplikat & menjaga riwayat ujian user
$existingHashes = [];
$stmtExist = $db->query("SELECT question_text FROM questions");
while ($row = $stmtExist->fetch(PDO::FETCH_ASSOC)) {
    $norm = mb_strtolower(preg_replace('/\s+/', ' ', trim((string)$row['question_text'])));
    $existingHashes[md5($norm)] = true;
}

// 5. Tambahkan seluruh butir soal baru secara aman dalam transaksi
$qStmt = $db->prepare("
    INSERT INTO questions 
    (category_id, package_id, question_text, option_a, option_b, option_c, option_d, option_e, correct_option, score_a, score_b, score_c, score_d, score_e, explanation)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$db->beginTransaction();
$addedQ = 0;
foreach ($data['questions'] as $q) {
    $norm = mb_strtolower(preg_replace('/\s+/', ' ', trim((string)$q['question_text'])));
    $hash = md5($norm);
    if (isset($existingHashes[$hash])) {
        continue;
    }
    $existingHashes[$hash] = true;

    $qStmt->execute([
        $q['category_id'],
        $q['package_id'],
        $q['question_text'],
        $q['option_a'],
        $q['option_b'],
        $q['option_c'],
        $q['option_d'],
        $q['option_e'],
        $q['correct_option'],
        $q['score_a'],
        $q['score_b'],
        $q['score_c'],
        $q['score_d'],
        $q['score_e'],
        $q['explanation']
    ]);
    $addedQ++;
}
$db->commit();

if ($isCli) {
    echo "✓ $addedQ Butir Soal Baru Berhasil Ditambahkan (Total sekarang: " . count($existingHashes) . " soal)!\n";
    echo "=== SELESAI SINKRONISASI SIAP TES ===\n";
}
