<?php
// admin/tambah_soal.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();
$db = getDB();

$editId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$question = null;

if ($editId > 0) {
    $question = $db->query("SELECT * FROM questions WHERE id = $editId")->fetch();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = intval($_POST['category_id'] ?? 0);
    $package_id = !empty($_POST['package_id']) ? intval($_POST['package_id']) : null;
    $question_text = trim($_POST['question_text'] ?? '');
    $option_a = trim($_POST['option_a'] ?? '');
    $option_b = trim($_POST['option_b'] ?? '');
    $option_c = trim($_POST['option_c'] ?? '');
    $option_d = trim($_POST['option_d'] ?? '');
    $option_e = trim($_POST['option_e'] ?? '');
    $correct_option = $_POST['correct_option'] ?? null;
    $explanation = trim($_POST['explanation'] ?? '');

    $score_a = intval($_POST['score_a'] ?? 0);
    $score_b = intval($_POST['score_b'] ?? 0);
    $score_c = intval($_POST['score_c'] ?? 0);
    $score_d = intval($_POST['score_d'] ?? 0);
    $score_e = intval($_POST['score_e'] ?? 0);

    if (empty($question_text) || empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d) || empty($option_e)) {
        $error = 'Harap isi teks pertanyaan dan kelima opsi jawaban.';
    } else {
        if ($editId > 0) {
            $stmt = $db->prepare("
                UPDATE questions SET 
                    category_id = ?, package_id = ?, question_text = ?, 
                    option_a = ?, option_b = ?, option_c = ?, option_d = ?, option_e = ?, 
                    correct_option = ?, score_a = ?, score_b = ?, score_c = ?, score_d = ?, score_e = ?, 
                    explanation = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $category_id, $package_id, $question_text,
                $option_a, $option_b, $option_c, $option_d, $option_e,
                $correct_option, $score_a, $score_b, $score_c, $score_d, $score_e,
                $explanation, $editId
            ]);
            header("Location: soal.php?msg=Soal berhasil diperbarui");
            exit;
        } else {
            $stmt = $db->prepare("
                INSERT INTO questions (
                    category_id, package_id, question_text, 
                    option_a, option_b, option_c, option_d, option_e, 
                    correct_option, score_a, score_b, score_c, score_d, score_e, 
                    explanation
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $category_id, $package_id, $question_text,
                $option_a, $option_b, $option_c, $option_d, $option_e,
                $correct_option, $score_a, $score_b, $score_c, $score_d, $score_e,
                $explanation
            ]);
            header("Location: soal.php?msg=Soal baru berhasil ditambahkan");
            exit;
        }
    }
}

$categories = $db->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
$packages = $db->query("SELECT * FROM packages ORDER BY id ASC")->fetchAll();

$page_title = ($editId ? "Edit Soal" : "Tambah Soal Baru") . " - CPNS";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h1 style="font-size: 1.75rem; font-weight: 800; color: var(--gray-900);">
            <?= $editId ? 'Edit Butir Soal' : 'Tambah Soal Baru' ?>
        </h1>
        <a href="soal.php" class="btn btn-secondary btn-sm">⬅ Batal / Kembali</a>
    </div>

    <?php if ($error): ?>
        <div style="background: #fee2e2; color: #b91c1c; padding: 0.75rem 1rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" action="">
            <div class="grid-2">
                <div class="form-group">
                    <label>Kategori Soal *</label>
                    <select name="category_id" id="category_select" class="form-control" required onchange="toggleCategoryType()">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" data-code="<?= $cat['code'] ?>" <?= ($question && $question['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= $cat['code'] ?> - <?= $cat['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Penugasan Paket Tryout (Opsional)</label>
                    <select name="package_id" class="form-control">
                        <option value="">-- Bank Soal Umum (Latihan) --</option>
                        <?php foreach ($packages as $pkg): ?>
                            <option value="<?= $pkg['id'] ?>" <?= ($question && $question['package_id'] == $pkg['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pkg['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Teks Pertanyaan *</label>
                <textarea name="question_text" class="form-control" rows="4" required placeholder="Tuliskan butir soal di sini..."><?= htmlspecialchars($question['question_text'] ?? '') ?></textarea>
            </div>

            <!-- Opsi Jawaban A - E -->
            <div style="margin-top: 1.5rem; margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem;">Opsi Jawaban & Pembobotan</h3>

                <?php foreach (['a', 'b', 'c', 'd', 'e'] as $opt): 
                    $optUpper = strtoupper($opt);
                ?>
                    <div style="background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: var(--radius-sm); padding: 0.85rem; margin-bottom: 0.75rem;">
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <span style="font-weight: 700; font-size: 1.1rem; color: var(--primary); width: 24px;"><?= $optUpper ?>.</span>
                            <div style="flex: 1;">
                                <input type="text" name="option_<?= $opt ?>" class="form-control" required placeholder="Teks opsi <?= $optUpper ?>" value="<?= htmlspecialchars($question['option_' . $opt] ?? '') ?>">
                            </div>
                            
                            <!-- Bobot TKP (1-5) -->
                            <div class="tkp-score-box" style="display: flex; align-items: center; gap: 0.35rem; width: 140px;">
                                <label style="font-size: 0.8rem; font-weight: 600; margin: 0;">Poin TKP:</label>
                                <select name="score_<?= $opt ?>" class="form-control" style="padding: 0.4rem;">
                                    <?php for ($p = 1; $p <= 5; $p++): ?>
                                        <option value="<?= $p ?>" <?= ($question && $question['score_' . $opt] == $p) ? 'selected' : ($p == 3 ? 'selected' : '') ?>>
                                            <?= $p ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Kunci Jawaban untuk TWK & TIU -->
            <div class="form-group" id="twk_tiu_key_box">
                <label>Kunci Jawaban Benar (Untuk TWK & TIU, bernilai +5) *</label>
                <select name="correct_option" class="form-control" style="max-width: 200px;">
                    <?php foreach (['A', 'B', 'C', 'D', 'E'] as $k): ?>
                        <option value="<?= $k ?>" <?= ($question && $question['correct_option'] === $k) ? 'selected' : '' ?>>
                            Opsi <?= $k ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small style="color: var(--gray-600); display: block; margin-top: 0.25rem;">
                    *Untuk kategori TKP, nilai dihitung otomatis berdasarkan poin tiap opsi (1 sampai 5).
                </small>
            </div>

            <div class="form-group" style="margin-top: 1.5rem;">
                <label>Pembahasan Soal</label>
                <textarea name="explanation" class="form-control" rows="3" placeholder="Tuliskan pembahasan dan dasar acuan materi..."><?= htmlspecialchars($question['explanation'] ?? '') ?></textarea>
            </div>

            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    💾 <?= $editId ? 'Simpan Perubahan Soal' : 'Simpan Butir Soal Baru' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleCategoryType() {
    const sel = document.getElementById('category_select');
    const selectedOption = sel.options[sel.selectedIndex];
    const catCode = selectedOption.getAttribute('data-code');

    const keyBox = document.getElementById('twk_tiu_key_box');
    const tkpBoxes = document.querySelectorAll('.tkp-score-box');

    if (catCode === 'TKP') {
        keyBox.style.display = 'none';
        tkpBoxes.forEach(box => box.style.display = 'flex');
    } else {
        keyBox.style.display = 'block';
        tkpBoxes.forEach(box => box.style.display = 'none');
    }
}

toggleCategoryType();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
