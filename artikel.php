<?php
// artikel.php - Pusat Artikel, Berita Seleksi & Tips Lulus Ujian (SEO Traffic Hub)
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

// Pisahkan artikel utama (featured) jika sedang di tampilan awal tanpa filter
$featuredArticle = null;
$gridArticles = $articles;
if ($selectedCategory === '' && $searchQuery === '' && !empty($articles)) {
    $featuredArticle = $articles[0];
    $gridArticles = array_slice($articles, 1);
}

// Ambil 4 artikel terpopuler untuk sidebar/info
$popularArticles = $db->query("SELECT id, title, slug, category, views, reading_time, created_at FROM articles WHERE is_published = 1 ORDER BY views DESC LIMIT 4")->fetchAll();

$page_title = $selectedCategory ? "Artikel & Tips $selectedCategory" : "Artikel, Info Seleksi & Strategi Lulus CPNS, BUMN, Kedinasan & Psikotes";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Header Artikel & Informasi Seleksi -->
<div style="background: linear-gradient(135deg, var(--core-navy-dark) 0%, var(--core-navy) 65%, #114275 100%); color: #ffffff; border-radius: var(--radius-lg); padding: 2.75rem 2rem; margin-bottom: 2rem; border-bottom: 4px solid var(--core-gold); box-shadow: var(--shadow-lg); position: relative; overflow: hidden;">
    <div style="max-width: 820px; margin: 0 auto; text-align: center;">
        <div class="hero-pill" style="margin-bottom: 0.85rem;">
            📰 PUSAT INFORMASI, KISI-KISI & STRATEGI LULUS SELEKSI &bull; <b>SIAPTES.ID</b>
        </div>
        <h1 style="font-size: 2.1rem; font-weight: 800; line-height: 1.25; margin-bottom: 0.75rem;">
            Artikel & Panduan Pejuang <span style="color: var(--core-gold);">CPNS, BUMN, Kedinasan & Karier</span>
        </h1>
        <p style="color: #d1e2f2; font-size: 0.98rem; line-height: 1.65; margin-bottom: 1.5rem;">
            Temukan bedah kisi-kisi terbaru, trik cepat menjawab soal CAT tanpa rumus rumit, informasi pendaftaran instansi, hingga strategi menembus passing grade tertinggi.
        </p>

        <!-- Search Form -->
        <form action="artikel.php" method="GET" style="display: flex; gap: 0.5rem; max-width: 600px; margin: 0 auto; flex-wrap: wrap;">
            <?php if ($selectedCategory !== ''): ?>
                <input type="hidden" name="kategori" value="<?= htmlspecialchars($selectedCategory) ?>">
            <?php endif; ?>
            <input type="text" name="q" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Cari topik artikel... (misal: Passing Grade CPNS, AKHLAK BUMN, PKN STAN, TKP)" 
                   style="flex: 1; min-width: 240px; padding: 0.8rem 1.15rem; border-radius: var(--radius-sm); border: 2px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.96); color: var(--core-text); font-size: 0.92rem; font-family: inherit;">
            <button type="submit" class="btn btn-gold" style="padding: 0.8rem 1.5rem;">
                🔍 Cari Artikel
            </button>
            <?php if ($searchQuery !== '' || $selectedCategory !== ''): ?>
                <a href="artikel.php" class="btn btn-outline" style="padding: 0.8rem 1rem;">Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Bar Kategori & Tombol Admin -->
<div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
        <a href="artikel.php" class="btn btn-sm <?= $selectedCategory === '' ? 'btn-primary' : 'btn-secondary' ?>" style="border-radius: 999px;">
            🌐 Semua Topik
        </a>
        <?php foreach ($categories as $catKey => $catMeta): ?>
            <a href="artikel.php?kategori=<?= urlencode($catKey) ?>" 
               class="btn btn-sm <?= $selectedCategory === $catKey ? 'btn-primary' : 'btn-secondary' ?>" 
               style="border-radius: 999px;">
                <?= $catMeta['icon'] ?> <?= htmlspecialchars($catKey) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (isAdmin()): ?>
        <div style="display: flex; gap: 0.5rem;">
            <a href="admin/form_artikel.php" class="btn btn-gold btn-sm">✍️ + Tulis Artikel Baru</a>
            <a href="admin/artikel.php" class="btn btn-secondary btn-sm">⚙️ Kelola Artikel</a>
        </div>
    <?php endif; ?>
</div>

<?php if (empty($articles)): ?>
    <div class="card" style="text-align: center; padding: 3.5rem 2rem; margin-bottom: 3rem;">
        <div style="font-size: 3rem; margin-bottom: 0.75rem;">📭</div>
        <h3 style="font-size: 1.35rem; font-weight: 800; color: var(--core-navy); margin-bottom: 0.5rem;">Belum Ada Artikel yang Cocok</h3>
        <p style="color: var(--core-text-muted); max-width: 480px; margin: 0 auto 1.5rem;">
            Maaf, kami tidak menemukan artikel untuk pencarian <b>"<?= htmlspecialchars($searchQuery ?: $selectedCategory) ?>"</b>. Silakan coba kata kunci lain atau lihat semua topik.
        </p>
        <a href="artikel.php" class="btn btn-primary">Lihat Semua Artikel</a>
    </div>
<?php else: ?>

    <!-- ARTIKEL PILIHAN UTAMA (FEATURED) -->
    <?php if ($featuredArticle): 
        $fCat = $categories[$featuredArticle['category']] ?? ['icon' => '📌', 'badge_class' => 'badge-navy', 'color' => '#0b2d52'];
    ?>
        <div class="card card-gold-top" style="margin-bottom: 2.5rem; padding: 0; overflow: hidden; display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
            <!-- Visual Banner Left -->
            <div style="background: linear-gradient(135deg, <?= $fCat['color'] ?> 0%, #071d36 100%); color: #fff; padding: 2.5rem 2rem; display: flex; flex-direction: column; justify-content: space-between; position: relative; min-height: 260px;">
                <?php if (!empty($featuredArticle['cover_image'])): ?>
                    <img src="<?= htmlspecialchars($featuredArticle['cover_image']) ?>" alt="<?= htmlspecialchars($featuredArticle['title']) ?>" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0.35;">
                <?php endif; ?>
                <div style="position: relative; z-index: 1;">
                    <span class="badge badge-gold" style="margin-bottom: 0.75rem;">⭐ ARTIKEL PILIHAN UTAMA</span>
                    <div style="font-size: 3.2rem; margin: 0.5rem 0;"><?= $fCat['icon'] ?></div>
                    <div style="font-size: 0.85rem; color: #dbeafe; font-weight: 600; letter-spacing: 0.3px;">
                        <?= htmlspecialchars($featuredArticle['category']) ?> &bull; SIAPTES.ID
                    </div>
                </div>
                <div style="position: relative; z-index: 1; display: flex; gap: 1rem; font-size: 0.8rem; color: #cbd5e1; margin-top: 1.5rem; flex-wrap: wrap;">
                    <span>🗓️ <?= formatTanggalIndo($featuredArticle['created_at']) ?></span>
                    <span>⏱️ <?= (int)$featuredArticle['reading_time'] ?> Menit Baca</span>
                    <span>👁️ <?= number_format((int)$featuredArticle['views']) ?>x Dibaca</span>
                </div>
            </div>

            <!-- Content Right -->
            <div style="padding: 2.25rem 2rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                        <span class="badge <?= $fCat['badge_class'] ?>"><?= $fCat['icon'] ?> <?= htmlspecialchars($featuredArticle['category']) ?></span>
                        <span style="font-size: 0.8rem; color: var(--core-text-muted);">Oleh <b><?= htmlspecialchars($featuredArticle['author_name']) ?></b></span>
                    </div>
                    <h2 style="font-size: 1.55rem; font-weight: 800; color: var(--core-navy); line-height: 1.35; margin-bottom: 0.85rem;">
                        <a href="baca-artikel.php?slug=<?= urlencode($featuredArticle['slug']) ?>" style="color: inherit; text-decoration: none;">
                            <?= htmlspecialchars($featuredArticle['title']) ?>
                        </a>
                    </h2>
                    <p style="color: var(--core-text-muted); font-size: 0.95rem; line-height: 1.65; margin-bottom: 1.25rem;">
                        <?= htmlspecialchars($featuredArticle['excerpt']) ?>
                    </p>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--core-border); padding-top: 1rem; flex-wrap: wrap; gap: 0.75rem;">
                    <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                        <?php 
                        $tags = array_filter(array_map('trim', explode(',', (string)$featuredArticle['tags'])));
                        foreach (array_slice($tags, 0, 3) as $t): 
                        ?>
                            <span style="font-size: 0.72rem; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-weight: 600;">#<?= htmlspecialchars($t) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <a href="baca-artikel.php?slug=<?= urlencode($featuredArticle['slug']) ?>" class="btn btn-primary btn-sm">
                        Baca Selengkapnya ➔
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- GRID DAFTAR ARTIKEL -->
    <?php if (!empty($gridArticles)): ?>
        <div style="margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--core-navy); margin: 0;">
                <?= $selectedCategory ? 'Topik: ' . htmlspecialchars($selectedCategory) : ($searchQuery ? 'Hasil Pencarian: "' . htmlspecialchars($searchQuery) . '"' : 'Artikel & Strategi Terbaru') ?>
            </h2>
            <span style="font-size: 0.85rem; color: var(--core-text-muted);">Menampilkan <?= count($articles) ?> artikel</span>
        </div>

        <div class="grid-3" style="margin-bottom: 3rem;">
            <?php foreach ($gridArticles as $art): 
                $catMeta = $categories[$art['category']] ?? ['icon' => '📄', 'badge_class' => 'badge-navy', 'color' => '#0b2d52'];
            ?>
                <article class="card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.2s, box-shadow 0.2s;">
                    <div>
                        <!-- Card Cover / Visual Header -->
                        <a href="baca-artikel.php?slug=<?= urlencode($art['slug']) ?>" style="display: block; text-decoration: none;">
                            <div style="height: 145px; background: linear-gradient(135deg, <?= $catMeta['color'] ?> 0%, #0f172a 100%); position: relative; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between; color: #fff;">
                                <?php if (!empty($art['cover_image'])): ?>
                                    <img src="<?= htmlspecialchars($art['cover_image']) ?>" alt="<?= htmlspecialchars($art['title']) ?>" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0.45;">
                                <?php endif; ?>
                                <div style="position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: center;">
                                    <span class="badge" style="background: rgba(255,255,255,0.2); color: #fff; backdrop-filter: blur(4px); font-size: 0.72rem;">
                                        <?= $catMeta['icon'] ?> <?= htmlspecialchars($art['category']) ?>
                                    </span>
                                    <span style="font-size: 0.75rem; color: #e2e8f0; font-weight: 600;">⏱️ <?= (int)$art['reading_time'] ?> mnt</span>
                                </div>
                                <div style="position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: flex-end;">
                                    <span style="font-size: 2.1rem; line-height: 1;"><?= $catMeta['icon'] ?></span>
                                    <span style="font-size: 0.72rem; color: #cbd5e1;">👁️ <?= number_format((int)$art['views']) ?>x dibaca</span>
                                </div>
                            </div>
                        </a>

                        <!-- Card Body -->
                        <div style="padding: 1.35rem 1.35rem 1rem;">
                            <div style="font-size: 0.76rem; color: var(--core-text-muted); margin-bottom: 0.45rem;">
                                🗓️ <?= formatTanggalIndo($art['created_at']) ?> &bull; <?= htmlspecialchars($art['author_name']) ?>
                            </div>
                            <h3 style="font-size: 1.08rem; font-weight: 800; color: var(--core-navy); line-height: 1.4; margin-bottom: 0.65rem;">
                                <a href="baca-artikel.php?slug=<?= urlencode($art['slug']) ?>" style="color: inherit; text-decoration: none;">
                                    <?= htmlspecialchars($art['title']) ?>
                                </a>
                            </h3>
                            <p style="font-size: 0.86rem; color: var(--core-text-muted); line-height: 1.6; margin-bottom: 1rem;">
                                <?= htmlspecialchars(mb_strimwidth($art['excerpt'], 0, 145, '...')) ?>
                            </p>
                        </div>
                    </div>

                    <!-- Card Footer -->
                    <div style="padding: 0.85rem 1.35rem; border-top: 1px solid var(--core-border-light); background: #f8fafc; display: flex; justify-content: space-between; align-items: center;">
                        <a href="baca-artikel.php?slug=<?= urlencode($art['slug']) ?>" style="font-size: 0.85rem; font-weight: 700; color: var(--core-navy); text-decoration: none;">
                            Baca Artikel Selengkapnya →
                        </a>
                        <?php if (isAdmin()): ?>
                            <a href="admin/form_artikel.php?id=<?= $art['id'] ?>" style="font-size: 0.75rem; color: var(--core-blue); font-weight: 600;">✏️ Edit</a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>

<!-- Banner Konversi Pembaca Artikel ke Peserta Tryout -->
<div class="card card-navy-top" style="background: linear-gradient(135deg, #f8fafc 0%, #eef6fc 100%); padding: 2.25rem 2rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; flex-wrap: wrap;">
    <div style="max-width: 640px;">
        <span class="badge badge-gold" style="margin-bottom: 0.5rem;">🎯 DARI TEORI LANGSUNG KE PRAKTIK</span>
        <h3 style="font-size: 1.45rem; font-weight: 800; color: var(--core-navy); margin-bottom: 0.45rem;">
            Sudah Baca Strateginya? Uji Kemampuan Anda di Simulasi CAT Sekarang!
        </h3>
        <p style="color: var(--core-text-muted); font-size: 0.92rem; line-height: 1.6; margin: 0;">
            Membaca tips saja tidak cukup tanpa melatih kecepatan menjawab soal dengan timer asli. Coba <b>Paket Mini Tryout All-in-One (Trial Gratis)</b> atau latihan ratusan soal di <b>siaptes.id</b>.
        </p>
    </div>
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <a href="tryout.php" class="btn btn-primary">🚀 Mulai Simulasi Tryout Gratis</a>
        <a href="latihan.php" class="btn btn-secondary">📚 Buka Bank Soal</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
