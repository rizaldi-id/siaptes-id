<?php
// baca-artikel.php - Halaman Baca Detail Artikel & Panduan Seleksi (SEO Optimized)
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

// Jika tidak ditemukan atau masih draft (kecuali admin)
if (!$article || (!$article['is_published'] && !isAdmin())) {
    header('Location: artikel.php?msg=' . urlencode('Artikel yang Anda cari tidak ditemukan atau belum dipublikasikan.'));
    exit;
}

// Tambah jumlah tayangan (views) hanya untuk artikel tayang & pengunjung non-bot
$uaArticle = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
$isBotArticle = ($uaArticle !== '' && preg_match('/bot|crawl|spider|slurp|lighthouse|headlesschrome/i', $uaArticle));
if (!$isBotArticle && !empty($article['is_published'])) {
    $db->prepare("UPDATE articles SET views = views + 1 WHERE id = ?")->execute([$article['id']]);
    $article['views'] = (int)$article['views'] + 1;
}
$GLOBALS['tracked_article_slug'] = (string)$article['slug'];

$categories = getArticleCategories();
$catMeta = $categories[$article['category']] ?? ['icon' => '📄', 'badge_class' => 'badge-navy', 'color' => '#0b2d52'];

// Artikel terkait (kategori sama atau terbaru lainnya)
$stmtRel = $db->prepare("SELECT id, title, slug, category, reading_time, views, created_at FROM articles WHERE is_published = 1 AND id != ? ORDER BY (category = ?) DESC, views DESC LIMIT 4");
$stmtRel->execute([$article['id'], $article['category']]);
$relatedArticles = $stmtRel->fetchAll();

$page_title = $article['title'];
$meta_description = $article['excerpt'];
require_once __DIR__ . '/includes/header.php';

// Format isi artikel: jika user menulis teks biasa tanpa tag HTML <p>, otomatis ubah baris baru menjadi paragraf rapi
$rawContent = trim($article['content']);
$hasHtmlTags = ($rawContent !== strip_tags($rawContent));
$renderedContent = $hasHtmlTags ? $rawContent : '<p>' . preg_replace("/\n\s*\n/", "</p><p>", nl2br(htmlspecialchars($rawContent))) . '</p>';
?>

<style>
.article-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 2rem;
    align-items: start;
}
@media (max-width: 960px) {
    .article-layout {
        grid-template-columns: 1fr;
    }
}
.article-body {
    font-size: calc(1.04rem * var(--read-scale));
    line-height: 1.88;
    color: var(--core-text);
    max-width: 72ch;
}
.article-body p {
    margin-bottom: 1.35rem;
}
.article-body h2, .article-body h3 {
    color: var(--core-text);
    font-weight: 800;
    margin-top: 2rem;
    margin-bottom: 0.75rem;
    line-height: 1.35;
}
.article-body h3 {
    font-size: calc(1.26rem * var(--read-scale));
}
.article-body ul, .article-body ol {
    margin-bottom: 1.4rem;
    padding-left: 1.5rem;
}
.article-body li {
    margin-bottom: 0.6rem;
}
.article-callout {
    background: var(--core-navy-subtle);
    border-left: 4px solid var(--core-navy);
    padding: 1.15rem 1.35rem;
    border-radius: var(--radius-sm);
    margin: 1.65rem 0;
    color: var(--core-text);
    font-size: calc(0.95rem * var(--read-scale));
}
.article-hero-editorial {
    background: linear-gradient(145deg, #f3f7fd 0%, var(--core-card-bg) 65%, #f3faf7 100%);
    padding: 2.25rem 2.25rem 1.85rem;
    border-bottom: 1px solid var(--core-border-light);
    position: relative;
}
@media (max-width: 640px) {
    .article-hero-editorial {
        padding: 1.5rem 1.15rem 1.35rem;
    }
}
</style>

<!-- Indikator Progres Baca -->
<div class="reading-progress-bar" id="readingProgressBar"></div>

<!-- Breadcrumb Navigasi -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem; font-size: 0.85rem; color: var(--core-text-muted);">
    <div>
        <a href="index.php" style="color: var(--core-navy); text-decoration: none; font-weight: 600;">Beranda</a>
        <span style="margin: 0 6px;">/</span>
        <a href="artikel.php" style="color: var(--core-navy); text-decoration: none; font-weight: 600;">Artikel & Info Seleksi</a>
        <span style="margin: 0 6px;">/</span>
        <a href="artikel.php?kategori=<?= urlencode($article['category']) ?>" style="color: var(--core-blue); text-decoration: none; font-weight: 600;"><?= htmlspecialchars($article['category']) ?></a>
    </div>
    <?php if (isAdmin()): ?>
        <div style="display: flex; gap: 0.5rem;">
            <a href="admin/form_artikel.php?id=<?= $article['id'] ?>" class="btn btn-gold btn-sm">✏️ Edit Artikel Ini</a>
            <a href="admin/artikel.php" class="btn btn-secondary btn-sm">⚙️ Semua Artikel</a>
        </div>
    <?php endif; ?>
</div>

<div class="article-layout">
    <!-- Kolom Utama Artikel -->
    <main class="card" style="padding: 0; overflow: hidden;">
        <!-- Editorial Clean Header (Ramah Mata Pembaca) -->
        <div class="article-hero-editorial">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap;">
                <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                    <span class="badge <?= $catMeta['badge_class'] ?>"><?= $catMeta['icon'] ?> <?= htmlspecialchars($article['category']) ?></span>
                    <?php if (!$article['is_published']): ?>
                        <span class="badge badge-danger">DRAFT</span>
                    <?php endif; ?>
                    <span style="font-size: 0.8rem; color: var(--core-text-muted); font-weight: 600;">⏱️ <?= (int)$article['reading_time'] ?> menit baca</span>
                </div>

                <!-- Kontrol Kenyamanan Baca -->
                <div class="comfort-toolbar" aria-label="Pengaturan Kenyamanan Baca">
                    <span style="padding-left: 4px; font-weight: 600;">Teks:</span>
                    <button type="button" class="comfort-btn" onclick="adjustArticleFont(-0.08)" title="Perkecil Teks">A-</button>
                    <button type="button" class="comfort-btn" onclick="resetArticleFont()" title="Ukuran Normal">A</button>
                    <button type="button" class="comfort-btn" onclick="adjustArticleFont(0.08)" title="Perbesar Teks">A+</button>
                    <button type="button" class="comfort-btn" id="articleWarmBtn" onclick="toggleArticleWarmMode()" title="Mode Kertas Hangat">📖 Hangat</button>
                </div>
            </div>

            <h1 style="font-size: 1.92rem; font-weight: 800; line-height: 1.32; margin-bottom: 1rem; color: var(--core-text); letter-spacing: -0.35px;">
                <?= htmlspecialchars($article['title']) ?>
            </h1>

            <div style="display: flex; gap: 1.2rem; align-items: center; font-size: 0.82rem; color: var(--core-text-muted); flex-wrap: wrap;">
                <span>✍️ Oleh <b style="color: var(--core-text);"><?= htmlspecialchars($article['author_name']) ?></b></span>
                <span>🗓️ <?= formatTanggalIndo($article['created_at']) ?></span>
                <span>👁️ <?= number_format((int)$article['views']) ?>x dibaca</span>
            </div>

            <?php if (!empty($article['cover_image'])): ?>
                <div style="margin-top: 1.35rem; border-radius: 12px; overflow: hidden; max-height: 360px;">
                    <img src="<?= htmlspecialchars($article['cover_image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                </div>
            <?php endif; ?>
        </div>

        <!-- Isi Konten Artikel -->
        <div style="padding: 2.15rem;">
            <!-- Lead / Ringkasan -->
            <div style="font-size: calc(1.03rem * var(--read-scale)); font-weight: 600; color: var(--core-text); background: var(--core-navy-subtle); padding: 1.15rem 1.35rem; border-radius: var(--radius-sm); border-left: 4px solid var(--core-navy); margin-bottom: 1.85rem; line-height: 1.7;">
                <?= htmlspecialchars($article['excerpt']) ?>
            </div>

            <!-- Body Artikel -->
            <div class="article-body">
                <?= $renderedContent ?>
            </div>

            <!-- Tags SEO -->
            <?php if (!empty($article['tags'])): ?>
                <div style="margin-top: 2rem; padding-top: 1.25rem; border-top: 1px solid var(--core-border); display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                    <span style="font-size: 0.82rem; font-weight: 700; color: var(--core-text-muted);">Topik Terkait:</span>
                    <?php foreach (array_filter(array_map('trim', explode(',', $article['tags']))) as $tag): ?>
                        <a href="artikel.php?q=<?= urlencode($tag) ?>" style="font-size: 0.78rem; background: #f1f5f9; color: var(--core-navy); padding: 4px 10px; border-radius: 999px; text-decoration: none; font-weight: 600;">
                            #<?= htmlspecialchars($tag) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Share Artikel (Traffic Booster) -->
            <div style="margin-top: 1.5rem; padding: 1.1rem 1.25rem; background: #f8fafc; border: 1px solid var(--core-border); border-radius: var(--radius-sm); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <span style="font-size: 0.88rem; font-weight: 700; color: var(--core-navy);">📢 Bagikan artikel bermanfaat ini ke teman pejuang seleksi:</span>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <?php
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $currentUrl = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'siaptes.id') . $_SERVER['REQUEST_URI'];
                    $shareText = $article['title'] . ' - Baca selengkapnya di SIAP TES (siaptes.id): ' . $currentUrl;
                    ?>
                    <a href="https://api.whatsapp.com/send?text=<?= urlencode($shareText) ?>" target="_blank" rel="noopener" class="btn btn-sm" style="background: #16a34a; color: #fff;">
                        💬 Bagikan ke WhatsApp
                    </a>
                    <a href="https://t.me/share/url?url=<?= urlencode($currentUrl) ?>&text=<?= urlencode($article['title']) ?>" target="_blank" rel="noopener" class="btn btn-sm" style="background: #0284c7; color: #fff;">
                        ✈️ Telegram
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($currentUrl, ENT_QUOTES) ?>'); this.innerText='✅ Link Tersalin!';">
                        🔗 Salin Link
                    </button>
                </div>
            </div>

            <!-- In-Article Conversion CTA Box -->
            <div class="dark-surface" style="margin-top: 2rem; background: linear-gradient(135deg, #243b6b 0%, #31508f 65%, #2c667b 100%); color: #fff; padding: 1.9rem; border-radius: var(--radius-md);">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; flex-wrap: wrap;">
                    <div style="max-width: 520px;">
                        <span class="badge" style="background: rgba(255,255,255,0.16); color: #f8fafc; border: 1px solid rgba(255,255,255,0.25); margin-bottom: 0.55rem;">🚀 SIMULASI CAT SIAPTES.ID</span>
                        <h3 style="font-size: 1.28rem; font-weight: 800; margin-bottom: 0.4rem; color: #fff;">
                            Siap Praktikkan Materi Ini di Simulasi Ujian?
                        </h3>
                        <p style="font-size: 0.88rem; color: #dce7f5; line-height: 1.65; margin: 0;">
                            Uji pemahaman Anda dengan sistem CAT berwaktu, penilaian passing grade otomatis, dan pembahasan lengkap untuk CPNS, BUMN, Kedinasan &amp; Psikotes.
                        </p>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <a href="tryout.php" class="btn btn-gold">Mulai Tryout Gratis ➔</a>
                        <a href="latihan.php" class="btn btn-outline btn-sm" style="text-align: center;">Latihan Bank Soal</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Sidebar Kanan -->
    <aside style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- Widget Kartu Tryout Cepat -->
        <div class="card card-gold-top">
            <span class="badge badge-success" style="margin-bottom: 0.5rem;">🎁 FREE TRIAL TERSEDIA</span>
            <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--core-navy); margin-bottom: 0.5rem;">
                Coba Simulasi CAT Tanpa Biaya!
            </h3>
            <p style="font-size: 0.85rem; color: var(--core-text-muted); line-height: 1.6; margin-bottom: 1rem;">
                Rasakan pengalaman ujian CAT sesungguhnya lengkap dengan timer, navigasi soal, dan evaluasi skor instan di <b>siaptes.id</b>.
            </p>
            <a href="tryout.php" class="btn btn-primary" style="width: 100%; justify-content: center; margin-bottom: 0.5rem;">
                🚀 Coba Tryout Sekarang
            </a>
            <?php if (!isLoggedIn()): ?>
                <a href="register.php" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: center;">
                    Daftar Akun Gratis
                </a>
            <?php endif; ?>
        </div>

        <!-- Widget Artikel Terkait / Populer -->
        <div class="card card-navy-top">
            <h3 style="font-size: 1.08rem; font-weight: 800; color: var(--core-navy); margin-bottom: 1rem;">
                🔥 Artikel & Tips Lainnya
            </h3>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($relatedArticles as $rel): ?>
                    <div style="border-bottom: 1px solid var(--core-border-light); padding-bottom: 0.85rem;">
                        <span style="font-size: 0.72rem; font-weight: 700; color: var(--core-blue); display: block; margin-bottom: 0.2rem;">
                            <?= htmlspecialchars($rel['category']) ?>
                        </span>
                        <a href="baca-artikel.php?slug=<?= urlencode($rel['slug']) ?>" style="font-size: 0.9rem; font-weight: 700; color: var(--core-navy); text-decoration: none; line-height: 1.4; display: block; margin-bottom: 0.3rem;">
                            <?= htmlspecialchars($rel['title']) ?>
                        </a>
                        <span style="font-size: 0.74rem; color: var(--core-text-muted);">
                            ⏱️ <?= (int)$rel['reading_time'] ?> mnt &bull; 👁️ <?= number_format((int)$rel['views']) ?>x dibaca
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
            <a href="artikel.php" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: center; margin-top: 1rem;">
                Lihat Semua Artikel →
            </a>
        </div>

        <!-- Widget Kategori Topik -->
        <div class="card">
            <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--core-navy); margin-bottom: 0.85rem;">
                📂 Kategori Artikel
            </h3>
            <div style="display: flex; flex-direction: column; gap: 0.45rem;">
                <?php foreach ($categories as $catKey => $cMeta): ?>
                    <a href="artikel.php?kategori=<?= urlencode($catKey) ?>" style="display: flex; justify-content: space-between; align-items: center; padding: 0.55rem 0.75rem; border-radius: var(--radius-sm); background: #f8fafc; color: var(--core-navy); text-decoration: none; font-size: 0.85rem; font-weight: 600;">
                        <span><?= $cMeta['icon'] ?> <?= htmlspecialchars($catKey) ?></span>
                        <span>➔</span>
                    </a>
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

// Kontrol ukuran teks & mode kertas hangat (tersimpan otomatis)
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
(function initArticleComfort() {
    applyArticleFontScale();
    if (localStorage.getItem('siaptes_warm_mode') === '1') {
        document.body.classList.add('warm-reading-mode');
        const btn = document.getElementById('articleWarmBtn');
        if (btn) btn.classList.add('active');
    }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
