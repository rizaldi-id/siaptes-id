<?php
// database/seed_siaptes.php
require_once __DIR__ . '/../config/database.php';

$db = getDB();

echo "=== MEMULAI SINKRONISASI DATABASE SIAP TES (siaptes.id) ===\n";

// 1. Baca data dari all_questions.json
$jsonPath = __DIR__ . '/all_questions.json';
if (!file_exists($jsonPath)) {
    die("Error: File $jsonPath tidak ditemukan. Jalankan build_all_data.py terlebih dahulu.\n");
}
$data = json_decode(file_get_contents($jsonPath), true);

// 2. Bersihkan tabel lama
$db->exec("DELETE FROM user_answers");
$db->exec("DELETE FROM exam_attempts");
$db->exec("DELETE FROM questions");
$db->exec("DELETE FROM packages");
$db->exec("DELETE FROM categories");

// Reset SQLite sequence if sqlite
try {
    $db->exec("DELETE FROM sqlite_sequence WHERE name IN ('categories', 'packages', 'questions', 'exam_attempts', 'user_answers')");
} catch (Exception $e) {}

// 3. Masukkan Kategori
$catStmt = $db->prepare("
    INSERT INTO categories (id, code, name, category_group, description, passing_grade)
    VALUES (?, ?, ?, ?, ?, ?)
");
foreach ($data['categories'] as $cat) {
    $catStmt->execute([
        $cat[0], $cat[1], $cat[2], $cat[3], $cat[4], $cat[5]
    ]);
}
echo "✓ " . count($data['categories']) . " Kategori Multi-Tes berhasil diinisialisasi.\n";

// 4. Masukkan Paket Tryout
$pkgStmt = $db->prepare("
    INSERT INTO packages (id, title, category_group, description, duration_minutes, is_premium, price, is_active)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
foreach ($data['packages'] as $pkg) {
    $pkgStmt->execute([
        $pkg[0], $pkg[1], $pkg[2], $pkg[3], $pkg[4], $pkg[5], $pkg[6], $pkg[7]
    ]);
}
echo "✓ " . count($data['packages']) . " Paket Simulasi Berhasil Ditambahkan.\n";

// 5. Masukkan Seluruh Butir Soal (255 Soal)
$qStmt = $db->prepare("
    INSERT INTO questions 
    (category_id, package_id, question_text, option_a, option_b, option_c, option_d, option_e, correct_option, score_a, score_b, score_c, score_d, score_e, explanation)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$db->beginTransaction();
$totalQ = 0;
foreach ($data['questions'] as $q) {
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
    $totalQ++;
}
$db->commit();

echo "✓ " . $totalQ . " Butir Soal Autentik Multi-Tes Berhasil Diimpor ke Database!\n";
echo "=== SELESAI SINKRONISASI SIAP TES ===\n";
