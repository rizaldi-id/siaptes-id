<?php
// jalur.php — Halaman Terpadu Per Jalur Seleksi (Tryout, Bank Soal & Artikel)
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/tracks_helper.php';
require_once __DIR__ . '/includes/articles_helper.php';
require_once __DIR__ . '/includes/tryout_blueprint.php';
require_once __DIR__ . '/includes/icons.php';

// Jika dipanggil lewat pencarian langsung ?search=...
if (isset($_GET['search']) && !isset($_GET['kategori'])) {
    header('Location: ' . resolvePortalSearchRedirect((string)$_GET['search']));
    exit;
}

$db = getDB();
ensureArticlesTable($db);

$tracks = getSelectionTracks();
$kategori = strtolower(trim((string)($_GET['kategori'] ?? 'cpns')));
if (!isset($tracks[$kategori])) {
    $kategori = 'cpns';
}
$track = $tracks[$kategori];

$validTabs = ['tryout', 'bank', 'artikel'];
$tabExplicit = isset($_GET['tab']) && in_array(strtolower(trim((string)$_GET['tab'])), $validTabs, true);
$activeTab = $tabExplicit ? strtolower(trim((string)$_GET['tab'])) : 'tryout';
$initialQuery = trim((string)($_GET['q'] ?? ''));

$isUserPremium = isPremiumUser();
$trackCounts = getTrackQuestionCounts($db);
$currentStats = $trackCounts[$kategori] ?? ['questions' => 0, 'packages' => 1];

// 1. Ambil Paket Simulasi Tryout relevan dengan jalur terpilih
$pkgGroups = $track['package_groups'];
$pkgPlaceholders = implode(',', array_fill(0, count($pkgGroups), '?'));
$pkgStmt = $db->prepare("
    SELECT p.*, COUNT(q.id) AS question_count
    FROM packages p
    LEFT JOIN questions q ON p.id = q.package_id
    WHERE p.is_active = 1 AND p.category_group IN ($pkgPlaceholders)
    GROUP BY p.id
    ORDER BY CASE WHEN p.category_group = ? THEN 0 WHEN p.category_group = 'all' THEN 2 ELSE 1 END, p.id ASC
");
$pkgParams = array_merge($pkgGroups, [$kategori]);
$pkgStmt->execute($pkgParams);
$trackPackages = $pkgStmt->fetchAll(PDO::FETCH_ASSOC);

// Mapping komposisi per paket
$compositions = $db->query("
    SELECT q.package_id, c.code, c.name, COUNT(q.id) AS q_cnt
    FROM questions q
    JOIN categories c ON q.category_id = c.id
    WHERE q.package_id IS NOT NULL
    GROUP BY q.package_id, c.code, c.name
")->fetchAll(PDO::FETCH_ASSOC);
$pkgMap = [];
foreach ($compositions as $comp) {
    $pkgMap[$comp['package_id']][] = $comp['code'] . ' (' . $comp['q_cnt'] . ' Soal)';
}

// 2. Ambil Kategori Bank Soal relevan dengan jalur terpilih
$bankGroups = $track['bank_groups'];
$bankPlaceholders = implode(',', array_fill(0, count($bankGroups), '?'));
$bankStmt = $db->prepare("
    SELECT c.*, COUNT(q.id) AS question_count
    FROM categories c
    LEFT JOIN questions q ON c.id = q.category_id
    WHERE c.category_group IN ($bankPlaceholders)
    GROUP BY c.id
    ORDER BY c.id ASC
");
$bankStmt->execute($bankGroups);
$bankCategories = $bankStmt->fetchAll(PDO::FETCH_ASSOC);

// Urutkan agar kategori utama jalur (mis. KEDINASAN_TPA pada kedinasan) tampil lebih dulu
usort($bankCategories, static function (array $a, array $b) use ($bankGroups): int {
    $posA = array_search($a['category_group'], $bankGroups, true);
    $posB = array_search($b['category_group'], $bankGroups, true);
    $posA = ($posA === false) ? 99 : $posA;
    $posB = ($posB === false) ? 99 : $posB;
    if ($posA !== $posB) {
        return $posA <=> $posB;
    }
    return ((int)$a['id']) <=> ((int)$b['id']);
});

// 3. Ambil Artikel relevan dengan jalur terpilih
$artCats = $track['article_categories'];
$artMetaAll = getArticleCategories();
$artPlaceholders = implode(',', array_fill(0, count($artCats), '?'));
$artStmt = $db->prepare("
    SELECT id, title, slug, category, excerpt, author_name, reading_time, views, is_featured, created_at
    FROM articles
    WHERE is_published = 1 AND category IN ($artPlaceholders)
    ORDER BY is_featured DESC, created_at DESC, id DESC
    LIMIT 18
");
$artStmt->execute($artCats);
$trackArticles = $artStmt->fetchAll(PDO::FETCH_ASSOC);

usort($trackArticles, static function (array $a, array $b) use ($artCats): int {
    $posA = array_search($a['category'], $artCats, true);
    $posB = array_search($b['category'], $artCats, true);
    $posA = ($posA === false) ? 99 : $posA;
    $posB = ($posB === false) ? 99 : $posB;
    if ($posA !== $posB) {
        return $posA <=> $posB;
    }
    return ((int)$b['id']) <=> ((int)$a['id']);
});
$trackArticles = array_slice($trackArticles, 0, 10);

// Jika pencarian dikirim tanpa ?tab= eksplisit dan tab 'tryout' tidak cocok sedangkan 'bank'/'artikel' cocok, arahkan otomatis
if ($initialQuery !== '' && !$tabExplicit) {
    $qLower = strtolower($initialQuery);
    $matchTryout = 0;
    foreach ($trackPackages as $pkg) {
        $hay = strtolower($pkg['title'] . ' ' . $pkg['description']);
        if (strpos($hay, $qLower) !== false) {
            $matchTryout++;
        }
    }
    if ($matchTryout === 0) {
        $matchBank = 0;
        foreach ($bankCategories as $bc) {
            $hay = strtolower($bc['code'] . ' ' . $bc['name'] . ' ' . $bc['description']);
            if (strpos($hay, $qLower) !== false) {
                $matchBank++;
            }
        }
        if ($matchBank > 0) {
            $activeTab = 'bank';
        } else {
            foreach ($trackArticles as $ta) {
                $hay = strtolower($ta['title'] . ' ' . $ta['excerpt'] . ' ' . $ta['category']);
                if (strpos($hay, $qLower) !== false) {
                    $activeTab = 'artikel';
                    break;
                }
            }
        }
    }
}

$page_title = $track['title'] . ' - Tryout, Bank Soal & Artikel';
$meta_description = $track['hero_desc'];
$full_bleed_layout = true;
require_once __DIR__ . '/includes/header.php';
?>

<!-- Full-Bleed Deep-Navy Track Header -->
<section class="pjk-hub-header">
    <div class="container">
        <nav class="pjk-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php" class="pjk-crumb-link">
                <?= ui_icon('home', 13) ?> <span>Beranda</span>
            </a>
            <?= ui_icon('chevron-right', 13) ?>
            <span>Jalur Seleksi</span>
            <?= ui_icon('chevron-right', 13) ?>
            <span class="pjk-crumb-current"><?= htmlspecialchars($track['short_title']) ?></span>
        </nav>

        <div class="pjk-hub-hero-row">
            <div class="pjk-hub-hero-copy">
                <span class="pjk-hub-badge">
                    <?= ui_icon($track['icon'], 14) ?> <?= htmlspecialchars($track['badge_label']) ?>
                </span>
                <h1><?= htmlspecialchars($track['title']) ?></h1>
                <p><?= htmlspecialchars($track['hero_desc']) ?></p>
            </div>

            <!-- Switcher Jalur Ringkas di Kanan Header Desktop -->
            <div class="pjk-hub-switcher" aria-label="Pilih jalur seleksi lain">
                <?php foreach ($tracks as $tSlug => $tCfg): ?>
                    <a href="jalur.php?kategori=<?= urlencode($tSlug) ?>&amp;tab=<?= urlencode($activeTab) ?>"
                       class="pjk-switch-pill <?= $tSlug === $kategori ? 'active' : '' ?>">
                        <?= ui_icon($tCfg['icon'], 14) ?>
                        <span><?= htmlspecialchars($tCfg['short_title']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- 3 Floating Category Tabs Overlapping Header & White Body -->
<div class="pjk-hub-tabs-wrap">
    <div class="container">
        <div class="pjk-hub-tabs" role="tablist" aria-label="Kategori konten <?= htmlspecialchars($track['short_title']) ?>">
            <a href="jalur.php?kategori=<?= urlencode($kategori) ?>&amp;tab=tryout"
               role="tab"
               aria-selected="<?= $activeTab === 'tryout' ? 'true' : 'false' ?>"
               data-hub-tab="tryout"
               onclick="return switchHubTab('tryout', this);"
               class="pjk-hub-tab <?= $activeTab === 'tryout' ? 'active' : '' ?>">
                <span class="pjk-tab-icon tone-tryout"><?= ui_icon('monitor', 20) ?></span>
                <span class="pjk-tab-copy">
                    <strong>Simulasi Tryout CAT</strong>
                    <span><?= count($trackPackages) ?> paket simulasi berwaktu &amp; evaluasi skor</span>
                </span>
            </a>

            <a href="jalur.php?kategori=<?= urlencode($kategori) ?>&amp;tab=bank"
               role="tab"
               aria-selected="<?= $activeTab === 'bank' ? 'true' : 'false' ?>"
               data-hub-tab="bank"
               onclick="return switchHubTab('bank', this);"
               class="pjk-hub-tab <?= $activeTab === 'bank' ? 'active' : '' ?>">
                <span class="pjk-tab-icon tone-bank"><?= ui_icon('book-open', 20) ?></span>
                <span class="pjk-tab-copy">
                    <strong>Bank Soal Mandiri</strong>
                    <span><?= number_format($currentStats['questions']) ?>+ soal latihan bebas kuota</span>
                </span>
            </a>

            <a href="jalur.php?kategori=<?= urlencode($kategori) ?>&amp;tab=artikel"
               role="tab"
               aria-selected="<?= $activeTab === 'artikel' ? 'true' : 'false' ?>"
               data-hub-tab="artikel"
               onclick="return switchHubTab('artikel', this);"
               class="pjk-hub-tab <?= $activeTab === 'artikel' ? 'active' : '' ?>">
                <span class="pjk-tab-icon tone-artikel"><?= ui_icon('file-text', 20) ?></span>
                <span class="pjk-tab-copy">
                    <strong>Artikel &amp; Panduan</strong>
                    <span><?= count($trackArticles) ?> panduan kisi-kisi &amp; strategi lolos</span>
                </span>
            </a>
        </div>
    </div>
</div>

<!-- Main 2-Column Content Area (Filter + Content on Left, Sticky Summary & Quick Links on Right) -->
<section class="pjk-hub-body">
    <div class="container">
        <div class="pjk-hub-grid">
            <!-- Left Main Column -->
            <div class="pjk-hub-main">
                <div class="pjk-hub-top-bar">
                    <div>
                        <h2 id="hubSectionTitle">
                            <?php if ($activeTab === 'bank'): ?>
                                Bank Soal &amp; Latihan <?= htmlspecialchars($track['short_title']) ?>
                            <?php elseif ($activeTab === 'artikel'): ?>
                                Artikel &amp; Panduan <?= htmlspecialchars($track['short_title']) ?>
                            <?php else: ?>
                                Simulasi Tryout CAT <?= htmlspecialchars($track['short_title']) ?>
                            <?php endif; ?>
                        </h2>
                        <p id="hubFilterStatus" class="pjk-hub-subtitle">
                            Menampilkan modul khusus jalur <b><?= htmlspecialchars($track['short_title']) ?></b>
                        </p>
                    </div>
                </div>

                <!-- Live Filter Search Box inside Track Hub -->
                <div class="pjk-hub-filter-box">
                    <span class="pjk-hub-filter-icon"><?= ui_icon('search', 17) ?></span>
                    <input
                        type="search"
                        id="hubSearchInput"
                        value="<?= htmlspecialchars($initialQuery) ?>"
                        placeholder="Cari modul, subtes, atau panduan di <?= htmlspecialchars($track['short_title']) ?>..."
                        aria-label="Filter modul dalam jalur <?= htmlspecialchars($track['short_title']) ?>"
                        oninput="filterHubItems()"
                    >
                    <button type="button" id="hubClearBtn" class="pjk-hub-clear-btn" onclick="clearHubSearch()" style="display: <?= $initialQuery !== '' ? 'inline-flex' : 'none' ?>;">
                        Reset
                    </button>
                </div>

                <!-- PANE 1: SIMULASI TRYOUT CAT -->
                <div id="hub-pane-tryout" class="pjk-hub-pane <?= $activeTab === 'tryout' ? 'active' : '' ?>">
                    <?php if (!$isUserPremium): ?>
                        <div class="gov-notice" style="border-left-color: var(--core-gold); margin-bottom: 1.5rem;">
                            <div class="gov-notice-icon"><?= ui_icon('crown', 18) ?></div>
                            <div class="gov-notice-content" style="display: flex; justify-content: space-between; align-items: center; width: 100%; flex-wrap: wrap; gap: 0.85rem;">
                                <div>
                                    <h4>Akses Paket Trial Gratis &amp; Member PRO VIP</h4>
                                    <p>Anda dapat mengerjakan <b>Mini Tryout All-in-One</b> secara gratis kapan saja, atau aktivasi <b>PRO VIP</b> untuk membuka simulasi penuh jalur <?= htmlspecialchars($track['short_title']) ?>.</p>
                                </div>
                                <a href="upgrade.php" class="btn btn-gold btn-sm" style="white-space: nowrap;">
                                    <?= ui_icon('crown', 14) ?> Upgrade PRO VIP
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="pjk-hub-cards">
                        <?php foreach ($trackPackages as $pkg):
                            $isPkgPremium = !empty($pkg['is_premium']);
                            $canAccess = (!$isPkgPremium || $isUserPremium);
                            $pkgMeta = getPackageDisplayMeta($db, $pkg, $pkgMap[$pkg['id']] ?? []);
                            $compositionList = $pkgMeta['composition_labels'];
                            $searchStr = strtolower($pkg['title'] . ' ' . $pkg['description'] . ' ' . implode(' ', $compositionList) . ' ' . $pkgMeta['passing_label']);
                        ?>
                            <article class="card <?= $isPkgPremium ? 'card-gold-top' : 'card-navy-top' ?> hub-filter-item" data-tab-group="tryout" data-search-text="<?= htmlspecialchars($searchStr) ?>">
                                <div class="pjk-pkg-head">
                                    <div>
                                        <div class="pjk-pkg-badges">
                                            <?php if ($isPkgPremium): ?>
                                                <span class="badge badge-gold"><?= ui_icon('crown', 12) ?> AKSES PRO VIP</span>
                                            <?php else: ?>
                                                <span class="badge badge-success"><?= ui_icon('gift', 12) ?> TRIAL PERCOBAAN GRATIS</span>
                                            <?php endif; ?>
                                            <span class="badge badge-navy"><?= ui_icon('shuffle', 12) ?> Soal Diacak Setiap Sesi</span>
                                        </div>
                                        <h3 class="pjk-pkg-title"><?= htmlspecialchars($pkg['title']) ?></h3>
                                    </div>
                                    <span class="badge badge-navy pjk-pkg-duration">
                                        <?= ui_icon('clock', 13) ?> <?= (int)$pkg['duration_minutes'] ?> Menit
                                    </span>
                                </div>

                                <p class="pjk-pkg-desc"><?= htmlspecialchars($pkg['description']) ?></p>

                                <div class="pjk-pkg-comp">
                                    <div class="pjk-pkg-comp-title">
                                        <?php if ($pkgMeta['bank_pool_count'] > $pkgMeta['question_count']): ?>
                                            <?= (int)$pkgMeta['question_count'] ?> Soal yang diacak dari <?= number_format($pkgMeta['bank_pool_count']) ?> Bank Soal
                                        <?php else: ?>
                                            <?= (int)$pkgMeta['question_count'] ?> Soal
                                        <?php endif; ?>
                                    </div>
                                    <div class="pjk-pkg-comp-pills">
                                        <?php if (!empty($compositionList)): ?>
                                            <?php foreach ($compositionList as $cItem): ?>
                                                <span class="badge badge-navy"><?= htmlspecialchars($cItem) ?></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="badge badge-navy">Soal Terstandarisasi</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ui-inline pjk-pkg-passing">
                                        <?= ui_icon('target', 13) ?> <span><?= htmlspecialchars($pkgMeta['passing_label']) ?></span>
                                    </div>
                                </div>

                                <div class="pjk-pkg-foot">
                                    <span class="pjk-pkg-access">
                                        <?php if ($isPkgPremium): ?>
                                            <span class="ui-inline" style="color: var(--core-gold-text);"><?= ui_icon('lock', 14) ?> Khusus Member PRO</span>
                                        <?php else: ?>
                                            <span class="ui-inline" style="color: var(--core-success);"><?= ui_icon('check-circle', 14) ?> Terbuka Bebas (Trial)</span>
                                        <?php endif; ?>
                                    </span>

                                    <?php if ($canAccess): ?>
                                        <a href="exam.php?action=start_package&amp;package_id=<?= (int)$pkg['id'] ?>"
                                           class="btn btn-primary btn-sm"
                                           onclick="return confirm('Mulai sesi simulasi CAT sekarang? Timer ujian akan langsung dihitung!');">
                                            Masuk Ujian CAT <?= ui_icon('arrow-right', 14, 'icon-arrow') ?>
                                        </a>
                                    <?php else: ?>
                                        <a href="upgrade.php?package_id=<?= (int)$pkg['id'] ?>" class="btn btn-gold btn-sm">
                                            <?= ui_icon('lock', 14) ?> Buka Akses PRO VIP (Rp 29.000)
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($kategori === 'psikotes'): ?>
                        <div class="card card-navy-top" style="margin-top: 1.25rem; background: #f4fbf8; border-color: #cbe8dc; border-top-color: #166534;">
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                                <div>
                                    <span class="badge badge-success" style="margin-bottom: 0.4rem;">BONUS GRATIS TANPA LOGIN</span>
                                    <h3 style="font-size: 1.08rem; font-weight: 800; color: var(--core-navy); margin-bottom: 0.25rem;">
                                        Tes IQ 30 Soal, Buta Warna, Minat RIASEC &amp; 10 Game Kognitif
                                    </h3>
                                    <p style="font-size: 0.86rem; color: var(--core-text-muted); margin: 0;">
                                        Lengkapi persiapan psikotes perusahaan dengan latihan ketangkasan kognitif &amp; tes potensi gratis di siaptes.id.
                                    </p>
                                </div>
                                <a href="tes-potensi.php" class="btn btn-primary btn-sm">
                                    Buka Tes Gratis <?= ui_icon('arrow-right', 14, 'icon-arrow') ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- PANE 2: BANK SOAL MANDIRI -->
                <div id="hub-pane-bank" class="pjk-hub-pane <?= $activeTab === 'bank' ? 'active' : '' ?>">
                    <div class="pjk-bank-grid">
                        <?php foreach ($bankCategories as $cat):
                            $noteText = $track['bank_category_notes'][$cat['code']] ?? $cat['name'];
                            $searchStr = strtolower($cat['code'] . ' ' . $cat['name'] . ' ' . $cat['description'] . ' ' . $noteText);
                        ?>
                            <article class="card card-navy-top pjk-bank-card hub-filter-item" data-tab-group="bank" data-search-text="<?= htmlspecialchars($searchStr) ?>">
                                <div>
                                    <div class="pjk-bank-head">
                                        <span class="badge badge-<?= htmlspecialchars(strtolower($cat['code'])) ?>"><?= htmlspecialchars($cat['code']) ?></span>
                                        <span class="pjk-bank-count"><?= number_format((int)$cat['question_count']) ?> Soal</span>
                                    </div>
                                    <h3 class="pjk-bank-title"><?= htmlspecialchars($cat['name']) ?></h3>
                                    <div class="pjk-bank-note"><?= htmlspecialchars($noteText) ?></div>
                                    <p class="pjk-bank-desc"><?= htmlspecialchars($cat['description']) ?></p>
                                </div>

                                <form method="GET" action="exam.php" class="pjk-bank-form">
                                    <input type="hidden" name="action" value="start_practice">
                                    <input type="hidden" name="category_id" value="<?= (int)$cat['id'] ?>">
                                    <div class="pjk-bank-quota-row">
                                        <label for="limit-<?= (int)$cat['id'] ?>">Kuota Soal:</label>
                                        <select id="limit-<?= (int)$cat['id'] ?>" name="limit" class="form-control">
                                            <option value="5">5 Butir (Trial Gratis)</option>
                                            <option value="10" <?= $isUserPremium ? 'selected' : '' ?>>10 Butir (PRO VIP)</option>
                                            <option value="20">20 Butir (PRO VIP)</option>
                                            <option value="30">30 Butir (PRO VIP)</option>
                                            <option value="50">50 Butir (PRO VIP)</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm" style="width: 100%;">
                                        Mulai Latihan <?= htmlspecialchars($cat['code']) ?> <?= ui_icon('arrow-right', 14, 'icon-arrow') ?>
                                    </button>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- PANE 3: ARTIKEL & PANDUAN -->
                <div id="hub-pane-artikel" class="pjk-hub-pane <?= $activeTab === 'artikel' ? 'active' : '' ?>">
                    <?php if (empty($trackArticles)): ?>
                        <div class="card">
                            <p style="margin: 0; color: var(--core-text-muted);">Belum ada artikel khusus pada kategori ini. Kunjungi <a href="artikel.php">halaman seluruh artikel</a>.</p>
                        </div>
                    <?php else: ?>
                        <div class="pjk-article-list">
                            <?php foreach ($trackArticles as $art):
                                $cMeta = $artMetaAll[$art['category']] ?? ['badge_class' => 'badge-navy', 'color' => '#2d4f8e'];
                                $searchStr = strtolower($art['title'] . ' ' . $art['excerpt'] . ' ' . $art['category']);
                            ?>
                                <article class="card pjk-art-row hub-filter-item" style="border-left: 4px solid <?= htmlspecialchars($cMeta['color']) ?>;" data-tab-group="artikel" data-search-text="<?= htmlspecialchars($searchStr) ?>">
                                    <div class="pjk-art-meta">
                                        <span class="badge <?= htmlspecialchars($cMeta['badge_class']) ?>"><?= htmlspecialchars($art['category']) ?></span>
                                        <span class="ui-inline"><?= ui_icon('clock', 12) ?> <?= (int)$art['reading_time'] ?> menit baca</span>
                                        <span class="ui-inline"><?= ui_icon('calendar', 12) ?> <?= formatTanggalIndo($art['created_at']) ?></span>
                                    </div>
                                    <h3>
                                        <a href="baca-artikel.php?slug=<?= urlencode($art['slug']) ?>">
                                            <?= htmlspecialchars($art['title']) ?>
                                        </a>
                                    </h3>
                                    <p><?= htmlspecialchars(mb_strimwidth($art['excerpt'], 0, 185, '...')) ?></p>
                                    <div class="pjk-art-foot">
                                        <span><?= number_format((int)$art['views']) ?> pembaca</span>
                                        <a href="baca-artikel.php?slug=<?= urlencode($art['slug']) ?>" class="ui-inline pjk-art-read">
                                            Baca Panduan <?= ui_icon('arrow-right', 13, 'icon-arrow') ?>
                                        </a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Empty State when Search Filter matches 0 items in Active Tab -->
                <div id="hubSearchEmpty" class="card pjk-hub-empty" style="display: none;">
                    <h3>Tidak ada modul yang cocok di tab ini</h3>
                    <p>Coba kata kunci lain, pilih tab lain di atas, atau reset filter pencarian.</p>
                    <button type="button" class="btn btn-outline-navy btn-sm" onclick="clearHubSearch()">Tampilkan Semua Modul</button>
                </div>
            </div>

            <!-- Right Sticky Sidebar (Mirip Panel Ringkasan & "Paling Sering Dicari" pada pajak.go.id) -->
            <aside class="pjk-hub-aside">
                <div class="pjk-side-card">
                    <div class="pjk-side-head">
                        <?= ui_icon_box($track['icon'], 'navy', 20) ?>
                        <div>
                            <div class="pjk-side-kicker">Ringkasan Jalur</div>
                            <h3><?= htmlspecialchars($track['short_title']) ?></h3>
                        </div>
                    </div>

                    <ul class="pjk-side-stats">
                        <li>
                            <?= ui_icon('book-open', 15) ?>
                            <span><b><?= number_format($currentStats['questions']) ?>+</b> butir bank soal tersedia</span>
                        </li>
                        <li>
                            <?= ui_icon('monitor', 15) ?>
                            <span><b><?= count($trackPackages) ?> paket</b> simulasi CAT aktif</span>
                        </li>
                        <li>
                            <?= ui_icon('target', 15) ?>
                            <span><?= htmlspecialchars($track['passing_summary']) ?></span>
                        </li>
                    </ul>

                    <div class="pjk-side-divider"></div>

                    <div class="pjk-side-quick">
                        <h4>Paling Sering Dicari</h4>
                        <p>Akses cepat materi &amp; simulasi yang paling banyak dibuka peserta <?= htmlspecialchars($track['short_title']) ?>:</p>
                        <div class="pjk-quick-list">
                            <?php foreach ($track['quick_links'] as $qLink): ?>
                                <a href="<?= htmlspecialchars($qLink['url']) ?>" class="pjk-quick-pill">
                                    <span><?= htmlspecialchars($qLink['label']) ?></span>
                                    <?= ui_icon('arrow-up-right', 15) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Jalur Seleksi Lainnya -->
                <div class="pjk-side-card pjk-side-tracks">
                    <h4>Jalur Seleksi Lainnya</h4>
                    <div class="pjk-side-track-list">
                        <?php foreach ($tracks as $tSlug => $tCfg):
                            $tStats = $trackCounts[$tSlug] ?? ['questions' => 0];
                        ?>
                            <a href="jalur.php?kategori=<?= urlencode($tSlug) ?>" class="pjk-side-track-item <?= $tSlug === $kategori ? 'active' : '' ?>">
                                <span class="pjk-side-track-left">
                                    <?= ui_icon($tCfg['icon'], 16) ?>
                                    <span><?= htmlspecialchars($tCfg['short_title']) ?></span>
                                </span>
                                <span class="pjk-side-track-meta"><?= number_format($tStats['questions']) ?>+ soal</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Akses Tes Gratis -->
                <div class="pjk-side-card pjk-side-free">
                    <span class="tp-badge-emerald" style="margin-bottom: 0.5rem;">100% GRATIS · TANPA LOGIN</span>
                    <h4>Tes Kemampuan &amp; Potensi</h4>
                    <p>Coba Tes Buta Warna, Tes IQ 30 Soal, Minat Karier RIASEC, &amp; 10 Game Kognitif secara langsung.</p>
                    <a href="tes-potensi.php" class="btn btn-outline-navy btn-sm" style="width: 100%; margin-top: 0.35rem;">
                        Buka Tes Gratis <?= ui_icon('arrow-right', 14, 'icon-arrow') ?>
                    </a>
                </div>
            </aside>
        </div>
    </div>
</section>

<script>
let currentHubTab = <?= json_encode($activeTab) ?>;
const hubTrackSlug = <?= json_encode($kategori) ?>;
const hubTrackTitle = <?= json_encode($track['short_title']) ?>;
const tabTitles = {
    tryout: 'Simulasi Tryout CAT ' + hubTrackTitle,
    bank: 'Bank Soal & Latihan ' + hubTrackTitle,
    artikel: 'Artikel & Panduan ' + hubTrackTitle
};

function switchHubTab(tabName, linkEl) {
    currentHubTab = tabName;

    document.querySelectorAll('.pjk-hub-tab').forEach(function (el) {
        const isActive = el.getAttribute('data-hub-tab') === tabName;
        el.classList.toggle('active', isActive);
        el.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    document.querySelectorAll('.pjk-hub-pane').forEach(function (pane) {
        pane.classList.toggle('active', pane.id === 'hub-pane-' + tabName);
    });

    document.querySelectorAll('.pjk-switch-pill').forEach(function (pill) {
        try {
            const url = new URL(pill.href, window.location.origin);
            url.searchParams.set('tab', tabName);
            pill.href = url.pathname + url.search;
        } catch (e) {}
    });

    const heading = document.getElementById('hubSectionTitle');
    if (heading && tabTitles[tabName]) {
        heading.textContent = tabTitles[tabName];
    }

    if (window.history && window.history.replaceState) {
        try {
            const cur = new URL(window.location.href);
            cur.searchParams.set('kategori', hubTrackSlug);
            cur.searchParams.set('tab', tabName);
            window.history.replaceState({}, '', cur.pathname + cur.search);
        } catch (e) {}
    }

    filterHubItems();
    return false;
}

function filterHubItems() {
    const input = document.getElementById('hubSearchInput');
    const clearBtn = document.getElementById('hubClearBtn');
    const emptyBox = document.getElementById('hubSearchEmpty');
    const statusEl = document.getElementById('hubFilterStatus');
    const q = input ? input.value.trim().toLowerCase() : '';

    if (clearBtn) {
        clearBtn.style.display = q.length > 0 ? 'inline-flex' : 'none';
    }

    let visibleCount = 0;
    const activePane = document.getElementById('hub-pane-' + currentHubTab);
    if (activePane) {
        activePane.querySelectorAll('.hub-filter-item').forEach(function (item) {
            const hay = item.getAttribute('data-search-text') || '';
            const match = q === '' || hay.indexOf(q) !== -1;
            item.style.display = match ? '' : 'none';
            if (match) {
                visibleCount++;
            }
        });
    }

    if (emptyBox) {
        emptyBox.style.display = (q !== '' && visibleCount === 0) ? 'block' : 'none';
    }

    if (statusEl) {
        if (q === '') {
            statusEl.innerHTML = 'Menampilkan modul khusus jalur <b>' + escapeHubHtml(hubTrackTitle) + '</b>';
        } else {
            statusEl.innerHTML = 'Ditemukan <b>' + visibleCount + '</b> hasil untuk kata kunci "<b>' + escapeHubHtml(input.value.trim()) + '</b>"';
        }
    }
}

function clearHubSearch() {
    const input = document.getElementById('hubSearchInput');
    if (input) {
        input.value = '';
    }
    filterHubItems();
    if (window.history && window.history.replaceState) {
        try {
            const cur = new URL(window.location.href);
            cur.searchParams.delete('q');
            window.history.replaceState({}, '', cur.pathname + cur.search);
        } catch (e) {}
    }
}

function escapeHubHtml(str) {
    return str.replace(/[&<>"']/g, function (m) {
        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];
    });
}

document.addEventListener('DOMContentLoaded', function () {
    filterHubItems();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
