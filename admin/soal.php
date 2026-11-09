<?php
// admin/soal.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();
$db = getDB();

// Handle Delete Question
if (isset($_GET['delete'])) {
    $delId = intval($_GET['delete']);
    $db->exec("DELETE FROM questions WHERE id = $delId");
    header("Location: soal.php?msg=Soal berhasil dihapus");
    exit;
}

// Filter Category
$filter_cat = isset($_GET['category']) ? intval($_GET['category']) : 0;
$query = "
    SELECT q.*, c.code as cat_code, c.name as cat_name, p.title as package_title
    FROM questions q
    JOIN categories c ON q.category_id = c.id
    LEFT JOIN packages p ON q.package_id = p.id
";
if ($filter_cat > 0) {
    $query .= " WHERE q.category_id = $filter_cat";
}
$query .= " ORDER BY q.id DESC";
$questions = $db->query($query)->fetchAll();

$categories = $db->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();

$page_title = "Kelola Bank Soal CPNS";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--gray-900);">Bank Soal SKD CPNS</h1>
        <p style="color: var(--gray-600);">Total <?= count($questions) ?> butir soal terdaftar.</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="index.php" class="btn btn-secondary btn-sm">⬅ Kembali ke Admin</a>
        <a href="tambah_soal.php" class="btn btn-primary btn-sm">+ Tambah Soal Baru</a>
    </div>
</div>

<!-- Filter Kategori -->
<div class="card" style="margin-bottom: 1.5rem; padding: 1rem;">
    <form method="GET" action="" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
        <label style="font-weight: 600; font-size: 0.9rem;">Filter Kategori:</label>
        <select name="category" class="form-control" style="width: auto; min-width: 220px;" onchange="this.form.submit()">
            <option value="0">Semua Kategori</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $filter_cat == $cat['id'] ? 'selected' : '' ?>>
                    <?= $cat['code'] ?> - <?= $cat['name'] ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($filter_cat > 0): ?>
            <a href="soal.php" class="btn btn-secondary btn-sm">Reset Filter</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th style="width: 100px;">Kategori</th>
                    <th>Teks Pertanyaan</th>
                    <th style="width: 120px;">Kunci/Bobot</th>
                    <th style="width: 160px;">Paket</th>
                    <th style="width: 140px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($questions)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--gray-600); padding: 2rem;">
                            Belum ada soal pada kategori ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($questions as $i => $q): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><span class="badge badge-<?= strtolower($q['cat_code']) ?>"><?= $q['cat_code'] ?></span></td>
                            <td>
                                <div style="font-size: 0.925rem; font-weight: 500; margin-bottom: 0.25rem;">
                                    <?= htmlspecialchars(mb_strimwidth($q['question_text'], 0, 100, '...')) ?>
                                </div>
                                <div style="font-size: 0.8rem; color: var(--gray-600);">
                                    Opsi A: <?= htmlspecialchars(mb_strimwidth($q['option_a'], 0, 30, '...')) ?> | 
                                    B: <?= htmlspecialchars(mb_strimwidth($q['option_b'], 0, 30, '...')) ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($q['cat_code'] === 'TKP'): ?>
                                    <span style="font-size: 0.8rem; color: var(--gray-700); font-weight: 600;">Skala 1-5</span>
                                <?php else: ?>
                                    <span style="font-weight: 700; color: var(--success); font-size: 1rem;"><?= $q['correct_option'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 0.85rem; color: var(--gray-600);">
                                <?= htmlspecialchars($q['package_title'] ?? 'Bank Umum') ?>
                            </td>
                            <td style="text-align: center;">
                                <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                    <a href="tambah_soal.php?id=<?= $q['id'] ?>" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Edit</a>
                                    <a href="soal.php?delete=<?= $q['id'] ?>" class="btn btn-danger btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" onclick="return confirm('Hapus butir soal ini?');">Hapus</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
