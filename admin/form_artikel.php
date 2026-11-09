<?php
// admin/form_artikel.php - Form Tambah & Edit Artikel SEO
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/articles_helper.php';

requireAdmin();
$db = getDB();
ensureArticlesTable($db);

$categories = getArticleCategories();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = ($id > 0);
$error = '';

$article = [
    'title' => '',
    'slug' => '',
    'category' => 'CPNS & PPPK',
    'excerpt' => '',
    'content' => '',
    'cover_image' => '',
    'author_name' => 'Tim Redaksi SIAP TES',
    'reading_time' => 5,
    'is_featured' => 0,
    'is_published' => 1,
    'tags' => ''
];

if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        header('Location: artikel.php?msg=' . urlencode('Artikel tidak ditemukan.'));
        exit;
    }
    $article = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $customSlug = trim($_POST['slug'] ?? '');
    $category = trim($_POST['category'] ?? 'CPNS & PPPK');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $cover_image = trim($_POST['cover_image'] ?? '');
    $author_name = trim($_POST['author_name'] ?? 'Tim Redaksi SIAP TES');
    $reading_time = max(1, (int)($_POST['reading_time'] ?? 5));
    $tags = trim($_POST['tags'] ?? '');
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    // Jika ringkasan dikosongkan, ambil otomatis dari 180 karakter pertama isi artikel
    if ($excerpt === '' && $content !== '') {
        $excerpt = mb_strimwidth( trim(preg_replace('/\s+/', ' ', strip_tags($content))), 0, 180, '...' );
    }

    $slug = $customSlug !== '' ? createArticleSlug($customSlug) : createArticleSlug($title);

    // Pastikan slug unik
    $stmtCheck = $db->prepare("SELECT id FROM articles WHERE slug = ? AND id != ?");
    $stmtCheck->execute([$slug, $id]);
    if ($stmtCheck->fetch()) {
        $slug .= '-' . rand(10, 99);
    }

    if ($title === '' || $content === '') {
        $error = 'Judul dan Isi Artikel wajib diisi.';
    } else {
        if ($is_featured) {
            $db->exec("UPDATE articles SET is_featured = 0");
        }

        $now = date('Y-m-d H:i:s');
        if ($isEdit) {
            $stmtUpd = $db->prepare("
                UPDATE articles 
                SET title = ?, slug = ?, category = ?, excerpt = ?, content = ?, cover_image = ?, 
                    author_name = ?, reading_time = ?, is_featured = ?, is_published = ?, tags = ?, updated_at = ?
                WHERE id = ?
            ");
            $stmtUpd->execute([
                $title, $slug, $category, $excerpt, $content, $cover_image,
                $author_name, $reading_time, $is_featured, $is_published, $tags, $now, $id
            ]);
            header('Location: artikel.php?msg=' . urlencode('Artikel "' . $title . '" berhasil diperbarui!'));
            exit;
        } else {
            $stmtIns = $db->prepare("
                INSERT INTO articles (title, slug, category, excerpt, content, cover_image, author_name, reading_time, views, is_featured, is_published, tags, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?)
            ");
            $stmtIns->execute([
                $title, $slug, $category, $excerpt, $content, $cover_image,
                $author_name, $reading_time, $is_featured, $is_published, $tags, $now, $now
            ]);
            header('Location: artikel.php?msg=' . urlencode('Artikel baru "' . $title . '" berhasil diterbitkan!'));
            exit;
        }
    }

    // Pertahankan input jika ada error
    $article = compact('title', 'slug', 'category', 'excerpt', 'content', 'cover_image', 'author_name', 'reading_time', 'is_featured', 'is_published', 'tags');
}

$page_title = $isEdit ? "Edit Artikel" : "Tulis Artikel Baru";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.75rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <span class="badge badge-navy" style="margin-bottom: 0.25rem;">EDITOR ARTIKEL SIAPTES.ID</span>
        <h1 style="font-size: 1.75rem; font-weight: 800; color: var(--core-navy);">
            <?= $isEdit ? '✏️ Edit Artikel' : '✍️ Tulis Artikel & Topik Baru' ?>
        </h1>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="artikel.php" class="btn btn-secondary btn-sm">← Kembali ke Daftar Artikel</a>
        <?php if ($isEdit): ?>
            <a href="../baca-artikel.php?slug=<?= urlencode($article['slug']) ?>" target="_blank" class="btn btn-outline btn-sm" style="color: var(--core-navy); border-color: var(--core-navy);">👁️ Lihat Artikel</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger" style="margin-bottom: 1.5rem;"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" class="card card-navy-top" style="max-width: 960px; margin: 0 auto 2.5rem;">
    <div class="form-group">
        <label style="font-weight: 700; color: var(--core-navy);">Judul Artikel (Buat Menarik & Mengandung Kata Kunci Pencarian Google) *</label>
        <input type="text" name="title" id="titleInput" class="form-control" required 
               placeholder="Contoh: Jadwal & Syarat Pendaftaran CPNS Terbaru Beserta Tips Lulus SKD Sekali Tes"
               value="<?= htmlspecialchars($article['title']) ?>" oninput="autoSlug(this.value)">
    </div>

    <div class="grid-3" style="gap: 1rem; margin-bottom: 1rem;">
        <div class="form-group" style="margin-bottom: 0;">
            <label style="font-weight: 700; color: var(--core-navy);">Kategori Topik *</label>
            <select name="category" class="form-control">
                <?php foreach ($categories as $catKey => $cMeta): ?>
                    <option value="<?= htmlspecialchars($catKey) ?>" <?= $article['category'] === $catKey ? 'selected' : '' ?>>
                        <?= $cMeta['icon'] ?> <?= htmlspecialchars($catKey) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label style="font-weight: 700; color: var(--core-navy);">Nama Penulis</label>
            <input type="text" name="author_name" class="form-control" value="<?= htmlspecialchars($article['author_name']) ?>">
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label style="font-weight: 700; color: var(--core-navy);">Estimasi Waktu Baca (Menit)</label>
            <input type="number" name="reading_time" class="form-control" min="1" max="60" value="<?= (int)$article['reading_time'] ?>">
        </div>
    </div>

    <div class="grid-2" style="gap: 1rem; margin-bottom: 1rem;">
        <div class="form-group" style="margin-bottom: 0;">
            <label style="font-weight: 700; color: var(--core-navy);">Slug URL (Otomatis untuk SEO)</label>
            <input type="text" name="slug" id="slugInput" class="form-control" placeholder="otomatis-dari-judul-artikel" value="<?= htmlspecialchars($article['slug']) ?>">
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label style="font-weight: 700; color: var(--core-navy);">URL Gambar Sampul / Cover (Opsional)</label>
            <input type="url" name="cover_image" class="form-control" placeholder="Kosongkan untuk memakai banner visual otomatis" value="<?= htmlspecialchars($article['cover_image']) ?>">
        </div>
    </div>

    <div class="form-group">
        <label style="font-weight: 700; color: var(--core-navy);">Ringkasan Singkat / Meta Description (Tampil di Kartu Depan & Google Search)</label>
        <textarea name="excerpt" class="form-control" rows="2" placeholder="Tulis 1-2 kalimat pembuka yang membuat pembaca penasaran ingin klik (jika dikosongkan akan diambil otomatis dari paragraf pertama)..."><?= htmlspecialchars($article['excerpt']) ?></textarea>
    </div>

    <div class="form-group">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.45rem; flex-wrap: wrap; gap: 0.5rem;">
            <label style="font-weight: 700; color: var(--core-navy); margin: 0;">Isi Konten Artikel Lengkap *</label>
            <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                <button type="button" class="btn btn-secondary btn-sm" style="padding: 3px 8px; font-size: 0.76rem;" onclick="insertFormat('<h3>Subjudul Bagian</h3>\n')">+ Subjudul</button>
                <button type="button" class="btn btn-secondary btn-sm" style="padding: 3px 8px; font-size: 0.76rem;" onclick="insertFormat('<b>Teks Penting</b>')">+ Huruf Tebal</button>
                <button type="button" class="btn btn-secondary btn-sm" style="padding: 3px 8px; font-size: 0.76rem;" onclick="insertFormat('<ul>\n  <li>Poin pertama</li>\n  <li>Poin kedua</li>\n</ul>\n')">+ Daftar Poin</button>
                <button type="button" class="btn btn-secondary btn-sm" style="padding: 3px 8px; font-size: 0.76rem;" onclick="insertFormat('<div class=&quot;article-callout&quot;>\n  <b>💡 Tips Penting:</b> Tulis tips atau rangkuman penting di sini.\n</div>\n')">+ Kotak Tips</button>
            </div>
        </div>
        <textarea name="content" id="contentArea" class="form-control" rows="14" required placeholder="Anda dapat langsung mengetik paragraf seperti biasa (baris baru otomatis menjadi paragraf rapi), atau gunakan tombol format di atas untuk menambahkan Subjudul, Poin, dan Kotak Tips..."><?= htmlspecialchars($article['content']) ?></textarea>
        <small style="color: var(--core-text-muted); display: block; margin-top: 0.35rem;">
            💡 Tips: Anda bisa mengetik teks biasa (Enter 2x untuk ganti paragraf) maupun menggunakan tag HTML untuk pemformatan lanjutan.
        </small>
    </div>

    <div class="form-group">
        <label style="font-weight: 700; color: var(--core-navy);">Kata Kunci / Tags SEO (Pisahkan dengan koma)</label>
        <input type="text" name="tags" class="form-control" placeholder="Contoh: CPNS 2026, Passing Grade SKD, Tips TIU, BUMN, PKN STAN" value="<?= htmlspecialchars($article['tags']) ?>">
    </div>

    <div style="display: flex; gap: 2rem; margin: 1.25rem 0; padding: 1rem; background: #f8fafc; border-radius: var(--radius-sm); border: 1px solid var(--core-border); flex-wrap: wrap;">
        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 700; color: var(--core-navy); cursor: pointer;">
            <input type="checkbox" name="is_published" value="1" <?= !empty($article['is_published']) ? 'checked' : '' ?> style="width: 18px; height: 18px;">
            🟢 Tayangkan Langsung ke Publik (Published)
        </label>

        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 700; color: var(--core-navy); cursor: pointer;">
            <input type="checkbox" name="is_featured" value="1" <?= !empty($article['is_featured']) ? 'checked' : '' ?> style="width: 18px; height: 18px;">
            ⭐ Jadikan Artikel Pilihan Utama (Tampil Paling Besar di Atas)
        </label>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 0.75rem; border-top: 1px solid var(--core-border); padding-top: 1.25rem;">
        <a href="artikel.php" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-gold" style="padding: 0.75rem 2rem; font-size: 1rem;">
            💾 <?= $isEdit ? 'Simpan Perubahan Artikel' : 'Terbitkan Artikel Sekarang' ?>
        </button>
    </div>
</form>

<script>
const isEditMode = <?= $isEdit ? 'true' : 'false' ?>;
function autoSlug(val) {
    if (isEditMode) return;
    const slug = val.toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
    document.getElementById('slugInput').value = slug;
}

function insertFormat(snippet) {
    const area = document.getElementById('contentArea');
    const start = area.selectionStart || area.value.length;
    const end = area.selectionEnd || area.value.length;
    const before = area.value.substring(0, start);
    const after = area.value.substring(end);
    area.value = before + snippet + after;
    area.focus();
    area.selectionStart = area.selectionEnd = start + snippet.length;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
