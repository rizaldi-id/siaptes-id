<?php
// admin/soal.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();
$db = getDB();

// Handle Delete Question
if (isset($_GET['delete'])) {
    $delId = intval($_GET['delete']);
    $db->prepare("DELETE FROM questions WHERE id = ?")->execute([$delId]);
    header("Location: soal.php?msg=" . urlencode("Soal berhasil dihapus"));
    exit;
}

// Filter Category, Search & Pagination (efisien untuk 7.000+ bank soal)
$filter_cat = isset($_GET['category']) ? intval($_GET['category']) : 0;
$search_q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$perPage = 50;
$page = max(1, intval($_GET['page'] ?? 1));

$whereParts = [];
$params = [];
if ($filter_cat > 0) {
    $whereParts[] = "q.category_id = ?";
    $params[] = $filter_cat;
}
if ($search_q !== '') {
    $whereParts[] = "(q.question_text LIKE ? OR q.option_a LIKE ? OR q.option_b LIKE ?)";
    $like = '%' . $search_q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
$whereSql = !empty($whereParts) ? (" WHERE " . implode(" AND ", $whereParts)) : "";

$cntStmt = $db->prepare("SELECT COUNT(*) FROM questions q" . $whereSql);
$cntStmt->execute($params);
$totalFiltered = (int)$cntStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalFiltered / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$dataStmt = $db->prepare("
    SELECT q.*, c.code as cat_code, c.name as cat_name, p.title as package_title
    FROM questions q
    JOIN categories c ON q.category_id = c.id
    LEFT JOIN packages p ON q.package_id = p.id
    $whereSql
    ORDER BY q.id DESC
    LIMIT $perPage OFFSET $offset
");
$dataStmt->execute($params);
$questions = $dataStmt->fetchAll();

$categories = $db->query("
    SELECT c.*, COUNT(q.id) as q_cnt
    FROM categories c
    LEFT JOIN questions q ON q.category_id = c.id
    GROUP BY c.id
    ORDER BY c.id ASC
")->fetchAll();

$page_title = "Kelola Bank Soal Multi-Kategori";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.75rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--core-text);">Bank Soal Multi-Kategori</h1>
        <p style="color: var(--core-text-muted);">
            Menampilkan <b><?= number_format($totalFiltered) ?></b> butir soal (Halaman <?= $page ?> dari <?= $totalPages ?>).
        </p>
    </div>
    <div style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
        <a href="index.php" class="btn btn-secondary btn-sm">⬅ Kembali ke Admin</a>
        <a href="../sync_db.php" class="btn btn-outline-navy btn-sm">🔄 Sinkronkan Bank Soal</a>
        <a href="tambah_soal.php" class="btn btn-primary btn-sm">+ Tambah Soal Baru</a>
    </div>
</div>

<!-- Filter Kategori & Pencarian -->
<div class="card" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem;">
    <form method="GET" action="" style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
        <label style="font-weight: 700; font-size: 0.88rem; color: var(--core-text);">Kategori:</label>
        <select name="category" class="form-control" style="width: auto; min-width: 240px;" onchange="this.form.submit()">
            <option value="0">Semua Kategori</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $filter_cat == $cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['code']) ?> - <?= htmlspecialchars($cat['name']) ?> (<?= number_format((int)$cat['q_cnt']) ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="q" value="<?= htmlspecialchars($search_q) ?>" class="form-control" placeholder="Cari kata kunci soal..." style="flex: 1; min-width: 200px;">
        <button type="submit" class="btn btn-primary btn-sm">🔍 Cari</button>

        <?php if ($filter_cat > 0 || $search_q !== ''): ?>
            <a href="soal.php" class="btn btn-secondary btn-sm">Reset</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 60px;">No</th>
                    <th style="width: 120px;">Kategori</th>
                    <th>Teks Pertanyaan</th>
                    <th style="width: 115px;">Kunci/Bobot</th>
                    <th style="width: 155px;">Sumber</th>
                    <th style="width: 135px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($questions)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--gray-600); padding: 2rem;">
                            Belum ada soal yang cocok dengan filter ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($questions as $i => $q):
                        $isWeighted = ((int)$q['score_a'] + (int)$q['score_b'] + (int)$q['score_c'] + (int)$q['score_d'] + (int)$q['score_e']) > 0;
                    ?>
                        <tr>
                            <td><?= $offset + $i + 1 ?></td>
                            <td><span class="badge badge-<?= strtolower($q['cat_code']) ?>"><?= htmlspecialchars($q['cat_code']) ?></span></td>
                            <td>
                                <div style="font-size: 0.92rem; font-weight: 600; margin-bottom: 0.25rem; color: var(--core-text);">
                                    <?= htmlspecialchars(mb_strimwidth($q['question_text'], 0, 115, '...')) ?>
                                </div>
                                <div style="font-size: 0.78rem; color: var(--core-text-muted);">
                                    A: <?= htmlspecialchars(mb_strimwidth((string)$q['option_a'], 0, 34, '...')) ?> |
                                    B: <?= htmlspecialchars(mb_strimwidth((string)$q['option_b'], 0, 34, '...')) ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($isWeighted): ?>
                                    <span class="badge badge-gold">Skala 1-5</span>
                                <?php else: ?>
                                    <span style="font-weight: 800; color: var(--core-success); font-size: 1rem;"><?= htmlspecialchars((string)$q['correct_option']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 0.82rem; color: var(--core-text-muted);">
                                <?= htmlspecialchars($q['package_title'] ?? 'Bank Soal Umum') ?>
                            </td>
                            <td style="text-align: center;">
                                <div style="display: flex; gap: 0.45rem; justify-content: center;">
                                    <a href="tambah_soal.php?id=<?= $q['id'] ?>" class="btn btn-secondary btn-sm" style="padding: 0.28rem 0.6rem; font-size: 0.76rem;">Edit</a>
                                    <a href="soal.php?delete=<?= $q['id'] ?>" class="btn btn-danger btn-sm" style="padding: 0.28rem 0.6rem; font-size: 0.76rem;" onclick="return confirm('Hapus butir soal ini?');">Hapus</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1):
        $baseParams = ['category' => $filter_cat];
        if ($search_q !== '') $baseParams['q'] = $search_q;
    ?>
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--core-border-light);">
            <span style="font-size: 0.84rem; color: var(--core-text-muted);">
                Menampilkan <?= number_format($offset + 1) ?>–<?= number_format(min($offset + $perPage, $totalFiltered)) ?> dari <?= number_format($totalFiltered) ?> soal
            </span>
            <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                <?php if ($page > 1): ?>
                    <a href="soal.php?<?= htmlspecialchars(http_build_query($baseParams + ['page' => $page - 1])) ?>" class="btn btn-secondary btn-sm">← Sebelumnya</a>
                <?php endif; ?>
                <span class="btn btn-primary btn-sm" style="cursor: default;">Hal <?= $page ?> / <?= $totalPages ?></span>
                <?php if ($page < $totalPages): ?>
                    <a href="soal.php?<?= htmlspecialchars(http_build_query($baseParams + ['page' => $page + 1])) ?>" class="btn btn-secondary btn-sm">Berikutnya →</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
