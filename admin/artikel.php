<?php
// admin/artikel.php - Kelola Artikel & Berita SEO (Admin Panel)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/articles_helper.php';

requireAdmin();
$db = getDB();
ensureArticlesTable($db);

// Handle aksi cepat: hapus, toggle publish, set featured
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action === 'delete') {
        $db->prepare("DELETE FROM articles WHERE id = ?")->execute([$id]);
        header('Location: artikel.php?msg=' . urlencode('Artikel berhasil dihapus.'));
        exit;
    } elseif ($action === 'toggle_publish') {
        $db->prepare("UPDATE articles SET is_published = CASE WHEN is_published = 1 THEN 0 ELSE 1 END WHERE id = ?")->execute([$id]);
        header('Location: artikel.php?msg=' . urlencode('Status publikasi artikel berhasil diperbarui.'));
        exit;
    } elseif ($action === 'set_featured') {
        $db->exec("UPDATE articles SET is_featured = 0");
        $db->prepare("UPDATE articles SET is_featured = 1, is_published = 1 WHERE id = ?")->execute([$id]);
        header('Location: artikel.php?msg=' . urlencode('Artikel berhasil dijadikan Artikel Pilihan Utama (Featured).'));
        exit;
    }
}

$articles = $db->query("SELECT * FROM articles ORDER BY is_featured DESC, created_at DESC, id DESC")->fetchAll();
$totalArticles = count($articles);
$totalViews = (int)$db->query("SELECT SUM(views) FROM articles")->fetchColumn();
$publishedCount = (int)$db->query("SELECT COUNT(*) FROM articles WHERE is_published = 1")->fetchColumn();

$page_title = "Kelola Artikel & Berita SEO";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 0.25rem;">
            <span class="badge badge-navy">MANAJEMEN KONTEN & SEO</span>
            <span style="font-size: 0.8rem; color: var(--core-text-muted);">Penarik Traffic Organik siaptes.id</span>
        </div>
        <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--core-navy);">Kelola Artikel & Berita Seleksi</h1>
        <p style="color: var(--core-text-muted); font-size: 0.95rem;">Tambahkan artikel topik terbaru seputar CPNS, BUMN, Kedinasan, dan Psikotes untuk mendatangkan pengunjung dari Google.</p>
    </div>
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <a href="index.php" class="btn btn-secondary btn-sm">← Kembali ke Dashboard Admin</a>
        <a href="../artikel.php" class="btn btn-outline btn-sm" style="color: var(--core-navy); border-color: var(--core-navy);">👁️ Lihat Halaman Artikel Publik</a>
        <a href="form_artikel.php" class="btn btn-gold btn-sm">✍️ + Tulis Artikel Baru</a>
    </div>
</div>

<!-- Ringkasan Statistik Artikel -->
<div class="grid-3" style="margin-bottom: 2rem;">
    <div class="stat-card twk">
        <div class="stat-label">TOTAL ARTIKEL TERDAFTAR</div>
        <div class="stat-val"><?= $totalArticles ?> <span style="font-size: 0.95rem; font-weight: 500; color: var(--core-text-muted);">Artikel</span></div>
        <p style="font-size: 0.825rem; color: var(--core-text-muted); margin-top: 0.25rem;"><?= $publishedCount ?> Tayang Publik &bull; <?= $totalArticles - $publishedCount ?> Draft</p>
    </div>
    <div class="stat-card tiu">
        <div class="stat-label">TOTAL KUNJUNGAN BACA (VIEWS)</div>
        <div class="stat-val"><?= number_format($totalViews) ?> <span style="font-size: 0.95rem; font-weight: 500; color: var(--core-text-muted);">Pembaca</span></div>
        <p style="font-size: 0.825rem; color: var(--core-text-muted); margin-top: 0.25rem;">Akumulasi klik pembaca pada seluruh artikel</p>
    </div>
    <div class="stat-card tkp">
        <div class="stat-label">AKSI CEPAT</div>
        <div style="margin-top: 0.5rem;">
            <a href="form_artikel.php" class="btn btn-primary btn-sm" style="width: 100%; justify-content: center;">+ Tambah Topik Artikel Baru</a>
        </div>
        <p style="font-size: 0.8rem; color: var(--core-text-muted); margin-top: 0.45rem;">Tips: Buat judul yang sering dicari peserta di Google</p>
    </div>
</div>

<!-- Tabel Daftar Artikel -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
        <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--core-navy); margin: 0;">Daftar Semua Artikel</h2>
        <span style="font-size: 0.85rem; color: var(--core-text-muted);">Klik bintang (⭐) untuk menjadikan Artikel Pilihan Utama di bagian atas halaman</span>
    </div>

    <?php if (empty($articles)): ?>
        <p style="color: var(--core-text-muted); padding: 2rem 0; text-align: center;">Belum ada artikel. Klik tombol <b>+ Tulis Artikel Baru</b> untuk mulai membuat artikel pertama Anda.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50px;">Utama</th>
                        <th>Judul & Ringkasan Artikel</th>
                        <th>Kategori</th>
                        <th>Dibaca</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($articles as $art): ?>
                        <tr>
                            <td style="text-align: center;">
                                <?php if ($art['is_featured']): ?>
                                    <span title="Artikel Pilihan Utama" style="font-size: 1.25rem;">⭐</span>
                                <?php else: ?>
                                    <a href="artikel.php?action=set_featured&id=<?= $art['id'] ?>" title="Jadikan Artikel Utama" style="text-decoration: none; font-size: 1.15rem; opacity: 0.35;">☆</a>
                                <?php endif; ?>
                            </td>
                            <td style="max-width: 400px;">
                                <a href="../baca-artikel.php?slug=<?= urlencode($art['slug']) ?>" target="_blank" style="font-weight: 800; color: var(--core-navy); text-decoration: none; font-size: 0.95rem; display: block; margin-bottom: 0.2rem;">
                                    <?= htmlspecialchars($art['title']) ?>
                                </a>
                                <span style="font-size: 0.78rem; color: var(--core-text-muted); display: block;">
                                    <?= htmlspecialchars(mb_strimwidth($art['excerpt'], 0, 100, '...')) ?>
                                </span>
                                <?php if (!empty($art['tags'])): ?>
                                    <span style="font-size: 0.72rem; color: var(--core-blue);">🏷️ <?= htmlspecialchars($art['tags']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-navy"><?= htmlspecialchars($art['category']) ?></span>
                            </td>
                            <td>
                                <b><?= number_format((int)$art['views']) ?></b>x
                            </td>
                            <td>
                                <?php if ($art['is_published']): ?>
                                    <a href="artikel.php?action=toggle_publish&id=<?= $art['id'] ?>" class="badge badge-success" style="text-decoration: none;" title="Klik untuk ubah ke Draft">
                                        Tayang
                                    </a>
                                <?php else: ?>
                                    <a href="artikel.php?action=toggle_publish&id=<?= $art['id'] ?>" class="badge badge-danger" style="text-decoration: none;" title="Klik untuk Tayangkan">
                                        Draft
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 0.82rem; color: var(--core-text-muted); white-space: nowrap;">
                                <?= date('d/m/Y', strtotime($art['created_at'])) ?>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="../baca-artikel.php?slug=<?= urlencode($art['slug']) ?>" target="_blank" class="btn btn-secondary btn-sm" title="Lihat Artikel">👁️</a>
                                <a href="form_artikel.php?id=<?= $art['id'] ?>" class="btn btn-primary btn-sm">✏️ Edit</a>
                                <a href="artikel.php?action=delete&id=<?= $art['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus artikel ini?');">🗑️</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
