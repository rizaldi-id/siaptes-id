<?php
// baca-artikel.php - Halaman Baca Artikel Editorial (Bersih, Fokus & Nyaman Dibaca)
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/articles_helper.php';

$db = getDB();
ensureArticlesTable($db);

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($slug !== '') {
    $stmt = $db->prepare("SELECT * FROM articles WHERE slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    $article = $stmt->fetch();
} elseif ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM articles WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $article = $stmt->fetch();
} else {
    $article = false;
}

if (!$article || (!$article['is_published'] && !isAdmin())) {
    header('Location: artikel.php?msg=' . urlencode('Artikel yang Anda cari tidak ditemukan atau belum dipublikasikan.'));
    exit;
}

// Tambah tayangan (views) untuk artikel tayang & pengunjung non-bot
$uaArticle = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
$isBotArticle = ($uaArticle !== '' && preg_match('/bot|crawl|spider|slurp|lighthouse|headlesschrome/i', $uaArticle));
if (!$isBotArticle && !empty($article['is_published'])) {
    $db->prepare("UPDATE articles SET views = views + 1 WHERE id = ?")->execute([$article['id']]);
    $article['views'] = (int)$article['views'] + 1;
}
$GLOBALS['tracked_article_slug'] = (string)$article['slug'];

$categories = getArticleCategories();
$catMeta = $categories[$article['category']] ?? ['color' => '#2d4f8e'];

// Artikel terkait
$stmtRel = $db->prepare("SELECT id, title, slug, category, reading_time, views, created_at FROM articles WHERE is_published = 1 AND id != ? ORDER BY (category = ?) DESC, views DESC LIMIT 4");
$stmtRel->execute([$article['id'], $article['category']]);
$relatedArticles = $stmtRel->fetchAll();

$page_title = $article['title'];
$meta_description = $article['excerpt'];
require_once __DIR__ . '/includes/header.php';

// Format isi artikel
$rawContent = trim($article['content']);
$hasHtmlTags = ($rawContent !== strip_tags($rawContent));
$renderedContent = $hasHtmlTags ? $rawContent : '<p>' . preg_replace("/\n\s*\n/", "</p><p>", nl2br(htmlspecialchars($rawContent))) . '</p>';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$currentUrl = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'siaptes.id') . $_SERVER['REQUEST_URI'];
$shareText = $article['title'] . ' — ' . $currentUrl;
?>

<!-- Indikator Progres Baca -->
<div class="reading-progress-bar" id="readingProgressBar"></div>

<!-- Breadcrumb Minimalis -->
<div class="ed-breadcrumb-bar">
    <nav class="ed-breadcrumb" aria-label="Breadcrumb">
        <a href="index.php">Beranda</a>
        <span>/</span>
        <a href="artikel.php">Artikel</a>
        <span>/</span>
        <a href="artikel.php?kategori=<?= urlencode($article['category']) ?>"><?= htmlspecialchars($article['category']) ?></a>
    </nav>
    <?php if (isAdmin()): ?>
        <div class="ed-admin-actions">
            <a href="admin/form_artikel.php?id=<?= $article['id'] ?>" class="btn btn-outline btn-sm">Edit Artikel</a>
            <a href="admin/artikel.php" class="btn btn-outline btn-sm">Kelola</a>
        </div>
    <?php endif; ?>
</div>

<div class="ed-read-layout">
    <!-- Kolom Utama Artikel -->
    <main class="ed-article-sheet">
        <header class="ed-article-head">
            <div class="ed-article-topbar">
                <div class="ed-meta-row">
                    <a href="artikel.php?kategori=<?= urlencode($article['category']) ?>" class="ed-cat-link" style="--cat-color: <?= htmlspecialchars($catMeta['color']) ?>;">
                        <?= htmlspecialchars($article['category']) ?>
                    </a>
                    <?php if (!$article['is_published']): ?>
                        <span class="badge badge-danger">DRAFT</span>
                    <?php endif; ?>
                    <span class="ed-dot">&middot;</span>
                    <span><?= (int)$article['reading_time'] ?> menit baca</span>
                </div>

                <!-- Pengaturan Kenyamanan Baca -->
                <div class="comfort-toolbar" aria-label="Pengaturan kenyamanan baca">
                    <button type="button" class="comfort-btn" onclick="adjustArticleFont(-0.08)" title="Perkecil Teks">A-</button>
                    <button type="button" class="comfort-btn" onclick="resetArticleFont()" title="Ukuran Normal">A</button>
                    <button type="button" class="comfort-btn" onclick="adjustArticleFont(0.08)" title="Perbesar Teks">A+</button>
                    <button type="button" class="comfort-btn" id="articleWarmBtn" onclick="toggleArticleWarmMode()" title="Mode Kertas Hangat">Hangat</button>
                </div>
            </div>

            <h1 class="ed-article-title"><?= htmlspecialchars($article['title']) ?></h1>

            <?php if (!empty($article['excerpt'])): ?>
                <p class="ed-article-lead"><?= htmlspecialchars($article['excerpt']) ?></p>
            <?php endif; ?>

            <div class="ed-article-byline">
                <span>Ditulis oleh <b><?= htmlspecialchars($article['author_name']) ?></b></span>
                <span class="ed-dot">&middot;</span>
                <span><?= formatTanggalIndo($article['created_at']) ?></span>
                <span class="ed-dot">&middot;</span>
                <span><?= number_format((int)$article['views']) ?> kali dibaca</span>
            </div>

            <?php if (!empty($article['cover_image'])): ?>
                <figure class="ed-article-cover">
                    <img src="<?= htmlspecialchars($article['cover_image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>">
                </figure>
            <?php endif; ?>
        </header>

        <!-- Konten Utama Artikel -->
        <div class="ed-prose" id="articleProse">
            <?= $renderedContent ?>
        </div>

        <!-- Tag & Bagikan -->
        <footer class="ed-article-footer">
            <?php if (!empty($article['tags'])): ?>
                <div class="ed-tags-row">
                    <?php foreach (array_filter(array_map('trim', explode(',', (string)$article['tags']))) as $tag): ?>
                        <a href="artikel.php?q=<?= urlencode($tag) ?>" class="ed-tag">#<?= htmlspecialchars($tag) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="ed-share-bar">
                <span>Bagikan panduan ini</span>
                <div class="ed-share-btns">
                    <a href="https://api.whatsapp.com/send?text=<?= urlencode($shareText) ?>" target="_blank" rel="noopener" class="ed-share-btn">WhatsApp</a>
                    <a href="https://t.me/share/url?url=<?= urlencode($currentUrl) ?>&text=<?= urlencode($article['title']) ?>" target="_blank" rel="noopener" class="ed-share-btn">Telegram</a>
                    <button type="button" class="ed-share-btn" onclick="copyArticleUrl(this, '<?= htmlspecialchars($currentUrl, ENT_QUOTES) ?>')">Salin tautan</button>
                </div>
            </div>

            <!-- Langkah Berikutnya (Subtle, Non-Pushy) -->
            <div class="ed-next-step">
                <div>
                    <h4>Siap menguji pemahaman Anda?</h4>
                    <p>Latih kecepatan dan akurasi Anda melalui simulasi CAT berwaktu atau coba Tes Kemampuan &amp; Potensi gratis tanpa login.</p>
                </div>
                <div class="ed-next-actions">
                    <a href="tryout.php" class="btn btn-primary btn-sm">Mulai Simulasi CAT</a>
                    <a href="tes-potensi.php" class="btn btn-outline btn-sm">Tes Potensi Gratis</a>
                </div>
            </div>
        </footer>
    </main>

    <!-- Sidebar Kanan: Daftar Isi Otomatis + Artikel Terkait -->
    <aside class="ed-read-sidebar">
        <!-- Daftar Isi (tampil otomatis jika artikel memiliki subjudul h2/h3) -->
        <div class="ed-side-box ed-toc-box hidden" id="articleTocBox">
            <h3 class="ed-side-title">Daftar Isi</h3>
            <nav id="articleTocList" class="ed-toc-list" aria-label="Daftar isi artikel"></nav>
        </div>

        <?php if (!empty($relatedArticles)): ?>
            <div class="ed-side-box">
                <h3 class="ed-side-title">Bacaan Terkait</h3>
                <div class="ed-related-list">
                    <?php foreach ($relatedArticles as $rel): ?>
                        <a href="baca-artikel.php?slug=<?= urlencode($rel['slug']) ?>" class="ed-related-item">
                            <span class="ed-rel-cat"><?= htmlspecialchars($rel['category']) ?></span>
                            <strong><?= htmlspecialchars($rel['title']) ?></strong>
                            <small><?= (int)$rel['reading_time'] ?> menit baca &middot; <?= number_format((int)$rel['views']) ?> dibaca</small>
                        </a>
                    <?php endforeach; ?>
                </div>
                <a href="artikel.php" class="ed-side-all">Lihat semua artikel →</a>
            </div>
        <?php endif; ?>

        <div class="ed-side-box">
            <h3 class="ed-side-title">Jelajahi Topik</h3>
            <div class="ed-topic-list">
                <?php foreach ($categories as $catKey => $cMeta): ?>
                    <a href="artikel.php?kategori=<?= urlencode($catKey) ?>"><?= htmlspecialchars($catKey) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>
</div>

<script>
// Progres baca otomatis di atas layar
window.addEventListener('scroll', function() {
    const bar = document.getElementById('readingProgressBar');
    if (!bar) return;
    const scrollTop = window.scrollY || document.documentElement.scrollTop;
    const docHeight = document.documentElement.scrollHeight - window.innerHeight;
    const pct = docHeight > 0 ? Math.min(100, Math.max(0, (scrollTop / docHeight) * 100)) : 0;
    bar.style.width = pct + '%';
}, { passive: true });

// Kontrol ukuran teks & mode kertas hangat
let articleFontScale = parseFloat(localStorage.getItem('siaptes_read_scale') || '1') || 1;
function applyArticleFontScale() {
    articleFontScale = Math.max(0.88, Math.min(1.28, Math.round(articleFontScale * 100) / 100));
    document.documentElement.style.setProperty('--read-scale', String(articleFontScale));
    localStorage.setItem('siaptes_read_scale', String(articleFontScale));
}
function adjustArticleFont(delta) {
    articleFontScale += delta;
    applyArticleFontScale();
}
function resetArticleFont() {
    articleFontScale = 1;
    applyArticleFontScale();
}
function toggleArticleWarmMode() {
    const isWarm = document.body.classList.toggle('warm-reading-mode');
    localStorage.setItem('siaptes_warm_mode', isWarm ? '1' : '0');
    const btn = document.getElementById('articleWarmBtn');
    if (btn) btn.classList.toggle('active', isWarm);
}
function copyArticleUrl(btn, url) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(function() {
            btn.textContent = 'Tautan tersalin';
            setTimeout(function() { btn.textContent = 'Salin tautan'; }, 2200);
        });
    }
}

// Bangun Daftar Isi otomatis dari h2/h3 pada konten artikel
(function initArticleEnhancements() {
    applyArticleFontScale();
    if (localStorage.getItem('siaptes_warm_mode') === '1') {
        document.body.classList.add('warm-reading-mode');
        const btn = document.getElementById('articleWarmBtn');
        if (btn) btn.classList.add('active');
    }

    const prose = document.getElementById('articleProse');
    const tocBox = document.getElementById('articleTocBox');
    const tocList = document.getElementById('articleTocList');
    if (!prose || !tocBox || !tocList) return;

    const headings = prose.querySelectorAll('h2, h3');
    if (headings.length < 2) return;

    headings.forEach(function(h, idx) {
        const text = (h.textContent || '').trim();
        if (!text) return;
        if (!h.id) h.id = 'bagian-' + (idx + 1);
        const link = document.createElement('a');
        link.href = '#' + h.id;
        link.textContent = text;
        if (h.tagName === 'H3') link.className = 'is-sub';
        tocList.appendChild(link);
    });
    if (tocList.children.length >= 2) {
        tocBox.classList.remove('hidden');
    }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
