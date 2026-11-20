<?php
// artikel.php - Pusat Artikel, Info Seleksi & Panduan Belajar (Clean Editorial Layout)
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/articles_helper.php';

$db = getDB();
ensureArticlesTable($db);

$categories = getArticleCategories();
$selectedCategory = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

// Build query
$where = ["is_published = 1"];
$params = [];

if ($selectedCategory !== '' && isset($categories[$selectedCategory])) {
    $where[] = "category = ?";
    $params[] = $selectedCategory;
}

if ($searchQuery !== '') {
    $where[] = "(title LIKE ? OR excerpt LIKE ? OR content LIKE ? OR tags LIKE ?)";
    $like = '%' . $searchQuery . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = implode(' AND ', $where);
$stmt = $db->prepare("SELECT * FROM articles WHERE $whereSql ORDER BY is_featured DESC, created_at DESC, id DESC");
$stmt->execute($params);
$articles = $stmt->fetchAll();

// Pisahkan artikel utama (featured) saat tampilan awal tanpa filter
$featuredArticle = null;
$feedArticles = $articles;
if ($selectedCategory === '' && $searchQuery === '' && !empty($articles)) {
    $featuredArticle = $articles[0];
    $feedArticles = array_slice($articles, 1);
}

// 4 artikel terpopuler untuk sidebar
$popularArticles = $db->query("SELECT id, title, slug, category, views, reading_time, created_at FROM articles WHERE is_published = 1 ORDER BY views DESC LIMIT 4")->fetchAll();

$page_title = $selectedCategory ? "Artikel $selectedCategory" : "Artikel & Panduan Seleksi CPNS, BUMN, Kedinasan & Psikotes";
$meta_description = "Kumpulan panduan belajar, bedah kisi-kisi, dan strategi lulus seleksi CPNS, BUMN, Sekolah Kedinasan, dan Psikotes Kerja di SIAP TES.";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Editorial Header: Bersih, Fokus, Mudah Dipindai -->
<header class="ed-header">
    <div class="ed-header-copy">
        <div class="ed-eyebrow">Pustaka &amp; Panduan · siaptes.id</div>
        <h1>Artikel &amp; Strategi Seleksi</h1>
        <p>Bedah kisi-kisi terbaru, strategi manajemen waktu CAT, dan panduan praktis persiapan CPNS, BUMN, Kedinasan, serta Psikotes.</p>
    </div>

    <form action="artikel.php" method="GET" class="ed-search-form" role="search">
        <?php if ($selectedCategory !== ''): ?>
            <input type="hidden" name="kategori" value="<?= htmlspecialchars($selectedCategory) ?>">
        <?php endif; ?>
        <input
            type="search"
            name="q"
            value="<?= htmlspecialchars($searchQuery) ?>"
            placeholder="Cari topik, misal: Passing Grade CPNS, AKHLAK..."
            aria-label="Cari artikel"
            class="ed-search-input"
        >
        <button type="submit" class="btn btn-primary btn-sm">Cari</button>
        <?php if ($searchQuery !== '' || $selectedCategory !== ''): ?>
            <a href="artikel.php" class="btn btn-outline btn-sm">Reset</a>
        <?php endif; ?>
    </form>
</header>

<!-- Bar Kategori Minimalis -->
<div class="ed-filter-bar">
    <nav class="ed-cat-nav" aria-label="Filter kategori artikel">
        <a href="artikel.php" class="ed-cat-pill <?= $selectedCategory === '' ? 'active' : '' ?>">Semua Topik</a>
        <?php foreach ($categories as $catKey => $catMeta): ?>
            <a href="artikel.php?kategori=<?= urlencode($catKey) ?>" class="ed-cat-pill <?= $selectedCategory === $catKey ? 'active' : '' ?>">
                <?= htmlspecialchars($catKey) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if (isAdmin()): ?>
        <div class="ed-admin-actions">
            <a href="admin/form_artikel.php" class="btn btn-primary btn-sm">+ Tulis Artikel</a>
            <a href="admin/artikel.php" class="btn btn-outline btn-sm">Kelola</a>
        </div>
    <?php endif; ?>
</div>

<?php if (empty($articles)): ?>
    <div class="ed-empty">
        <h3>Tidak ada artikel yang cocok</h3>
        <p>Kami belum menemukan artikel untuk kata kunci <b>"<?= htmlspecialchars($searchQuery ?: $selectedCategory) ?>"</b>. Coba kata kunci lain atau lihat seluruh topik.</p>
        <a href="artikel.php" class="btn btn-primary btn-sm">Lihat Semua Artikel</a>
    </div>
<?php else: ?>

    <!-- Artikel Pilihan Utama (Editorial Feature) -->
    <?php if ($featuredArticle):
        $fCat = $categories[$featuredArticle['category']] ?? ['color' => '#2d4f8e'];
        $hasCover = !empty($featuredArticle['cover_image']);
    ?>
        <article class="ed-featured <?= $hasCover ? 'has-cover' : '' ?>">
            <?php if ($hasCover): ?>
                <a href="baca-artikel.php?slug=<?= urlencode($featuredArticle['slug']) ?>" class="ed-featured-cover">
                    <img src="<?= htmlspecialchars($featuredArticle['cover_image']) ?>" alt="<?= htmlspecialchars($featuredArticle['title']) ?>">
                </a>
            <?php endif; ?>
            <div class="ed-featured-body">
                <div class="ed-meta-row">
                    <span class="ed-kicker" style="--cat-color: <?= htmlspecialchars($fCat['color']) ?>;">Pilihan Utama</span>
                    <span class="ed-dot">&middot;</span>
                    <a href="artikel.php?kategori=<?= urlencode($featuredArticle['category']) ?>" class="ed-cat-link"><?= htmlspecialchars($featuredArticle['category']) ?></a>
                    <span class="ed-dot">&middot;</span>
                    <span><?= (int)$featuredArticle['reading_time'] ?> menit baca</span>
                </div>
                <h2>
                    <a href="baca-artikel.php?slug=<?= urlencode($featuredArticle['slug']) ?>">
                        <?= htmlspecialchars($featuredArticle['title']) ?>
                    </a>
                </h2>
                <p class="ed-excerpt"><?= htmlspecialchars($featuredArticle['excerpt']) ?></p>
                <div class="ed-card-foot">
                    <div class="ed-byline">
                        <span><?= htmlspecialchars($featuredArticle['author_name']) ?></span>
                        <span class="ed-dot">&middot;</span>
                        <span><?= formatTanggalIndo($featuredArticle['created_at']) ?></span>
                        <span class="ed-dot">&middot;</span>
                        <span><?= number_format((int)$featuredArticle['views']) ?> dibaca</span>
                    </div>
                    <a href="baca-artikel.php?slug=<?= urlencode($featuredArticle['slug']) ?>" class="ed-read-link">Baca selengkapnya →</a>
                </div>
            </div>
        </article>
    <?php endif; ?>

    <!-- Main 2-Column Editorial Layout (Feed + Sidebar Terpopuler) -->
    <div class="ed-main-grid">
        <section class="ed-feed-col">
            <div class="ed-section-bar">
                <h2>
                    <?php if ($selectedCategory !== ''): ?>
                        Topik: <?= htmlspecialchars($selectedCategory) ?>
                    <?php elseif ($searchQuery !== ''): ?>
                        Hasil pencarian: "<?= htmlspecialchars($searchQuery) ?>"
                    <?php else: ?>
                        Artikel Terbaru
                    <?php endif; ?>
                </h2>
                <span class="ed-count"><?= count($articles) ?> artikel</span>
            </div>

            <?php if (!empty($feedArticles)): ?>
                <div class="ed-list">
                    <?php foreach ($feedArticles as $art):
                        $catMeta = $categories[$art['category']] ?? ['color' => '#2d4f8e'];
                        $artHasCover = !empty($art['cover_image']);
                    ?>
                        <article class="ed-card <?= $artHasCover ? 'has-thumb' : '' ?>">
                            <div class="ed-card-content">
                                <div class="ed-meta-row">
                                    <a href="artikel.php?kategori=<?= urlencode($art['category']) ?>" class="ed-cat-link" style="--cat-color: <?= htmlspecialchars($catMeta['color']) ?>;">
                                        <?= htmlspecialchars($art['category']) ?>
                                    </a>
                                    <span class="ed-dot">&middot;</span>
                                    <span><?= (int)$art['reading_time'] ?> menit baca</span>
                                    <span class="ed-dot">&middot;</span>
                                    <span><?= formatTanggalIndo($art['created_at']) ?></span>
                                </div>
                                <h3>
                                    <a href="baca-artikel.php?slug=<?= urlencode($art['slug']) ?>">
                                        <?= htmlspecialchars($art['title']) ?>
                                    </a>
                                </h3>
                                <p class="ed-excerpt"><?= htmlspecialchars(mb_strimwidth($art['excerpt'], 0, 175, '...')) ?></p>
                                <div class="ed-card-foot">
                                    <div class="ed-byline">
                                        <span><?= htmlspecialchars($art['author_name']) ?></span>
                                        <span class="ed-dot">&middot;</span>
                                        <span><?= number_format((int)$art['views']) ?> dibaca</span>
                                        <?php if (isAdmin()): ?>
                                            <span class="ed-dot">&middot;</span>
                                            <a href="admin/form_artikel.php?id=<?= $art['id'] ?>" class="ed-edit-link">Edit</a>
                                        <?php endif; ?>
                                    </div>
                                    <a href="baca-artikel.php?slug=<?= urlencode($art['slug']) ?>" class="ed-read-link">Baca →</a>
                                </div>
                            </div>
                            <?php if ($artHasCover): ?>
                                <a href="baca-artikel.php?slug=<?= urlencode($art['slug']) ?>" class="ed-card-thumb">
                                    <img src="<?= htmlspecialchars($art['cover_image']) ?>" alt="<?= htmlspecialchars($art['title']) ?>" loading="lazy">
                                </a>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="ed-single-note">Seluruh artikel yang tersedia sudah ditampilkan pada pilihan utama di atas.</p>
            <?php endif; ?>
        </section>

        <!-- Sidebar Editorial: Terpopuler & Latihan Terkait -->
        <aside class="ed-sidebar">
            <?php if (!empty($popularArticles)): ?>
                <div class="ed-side-box">
                    <h3 class="ed-side-title">Paling Banyak Dibaca</h3>
                    <ol class="ed-popular-list">
                        <?php foreach ($popularArticles as $idx => $pop): ?>
                            <li>
                                <span class="ed-pop-num"><?= sprintf('%02d', $idx + 1) ?></span>
                                <div class="ed-pop-body">
                                    <a href="baca-artikel.php?slug=<?= urlencode($pop['slug']) ?>">
                                        <?= htmlspecialchars($pop['title']) ?>
                                    </a>
                                    <div class="ed-pop-meta">
                                        <?= htmlspecialchars($pop['category']) ?> &middot; <?= (int)$pop['reading_time'] ?> mnt
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            <?php endif; ?>

            <div class="ed-side-box ed-side-practice">
                <h3 class="ed-side-title">Lanjut ke Latihan</h3>
                <p>Terapkan strategi yang Anda baca langsung di simulasi berwaktu atau coba tes potensi gratis tanpa login.</p>
                <div class="ed-side-btns">
                    <a href="tryout.php" class="btn btn-primary btn-sm">Simulasi Tryout CAT</a>
                    <a href="tes-potensi.php" class="btn btn-outline btn-sm">Tes Kemampuan &amp; Potensi</a>
                </div>
            </div>
        </aside>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
