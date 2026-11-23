<?php
// index.php — Portal Beranda Utama SIAP TES (Hero Nusantara + 4 Pilihan Kategori Tes)
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/tracks_helper.php';
require_once __DIR__ . '/includes/articles_helper.php';
require_once __DIR__ . '/includes/icons.php';

// Tangani pencarian dari hero search bar
if (isset($_GET['search']) && trim((string)$_GET['search']) !== '') {
    header('Location: ' . resolvePortalSearchRedirect((string)$_GET['search']));
    exit;
}

$db = getDB();
ensureArticlesTable($db);

$tracks = getSelectionTracks();
$trackCounts = getTrackQuestionCounts($db);

// 3 artikel terbaru untuk section "Info & Panduan Seleksi Terbaru"
$latestArticles = $db->query("
    SELECT id, title, slug, category, excerpt, author_name, reading_time, views, created_at
    FROM articles
    WHERE is_published = 1
    ORDER BY is_featured DESC, created_at DESC, id DESC
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);
$articleCats = getArticleCategories();

// Statistik platform ringkas
$totalQuestions = (int)$db->query("SELECT COUNT(*) FROM questions")->fetchColumn();
$totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalAttempts = (int)$db->query("SELECT COUNT(*) FROM exam_attempts WHERE status = 'completed'")->fetchColumn();

$page_title = "Tempat Belajar dan Latihan Tes Terlengkap: CPNS, BUMN, Kedinasan & Psikotes";
$meta_description = "Tempat belajar dan latihan tes terlengkap di siaptes.id. Cobain simulasi tryout CAT, ribuan bank soal, dan panduan lolos CPNS, BUMN, Kedinasan, serta Psikotes Kerja.";
$full_bleed_layout = true;
require_once __DIR__ . '/includes/header.php';
?>

<!-- HERO PORTAL SIAP TES: Search Bar + 2x2 Frosted-Glass Track Cards (Desktop & Mobile) -->
<section class="pjk-hero" aria-label="Pilihan Jalur Belajar SIAP TES">
    <div class="pjk-hero-bg" style="background-image: url('assets/img/hero-siaptes.jpg');"></div>
    <div class="pjk-hero-overlay"></div>

    <div class="container pjk-hero-inner">
        <div class="pjk-hero-grid">
            <!-- Kolom Kiri: Headline + Search Bar Berbingkai Emas -->
            <div class="pjk-hero-copy">
                <div class="pjk-hero-eyebrow">
                    <?= ui_icon('shield-check', 14) ?> <span>SIAPTES.ID &bull; CPNS &bull; BUMN &bull; KEDINASAN &bull; PSIKOTES</span>
                </div>
                <h1>Tempat Belajar dan Latihan Tes Terlengkap</h1>
                <p>
                    Mau kejar NIP CPNS, tembus BUMN, lolos Kedinasan, atau taklukin psikotes kerja? Langsung latihan pakai ribuan bank soal &amp; tryout CAT rasa ujian asli — simpel, hemat, tanpa ribet.
                </p>

                <form action="index.php" method="GET" class="pjk-search" role="search">
                    <input
                        type="search"
                        name="search"
                        placeholder="Lagi cari materi apa? Misal: Tryout CPNS, AKHLAK, TIU, STAN..."
                        aria-label="Cari informasi, tryout, atau bank soal"
                    >
                    <button type="submit" aria-label="Cari sekarang">
                        <?= ui_icon('search', 18) ?>
                    </button>
                </form>

                <div class="pjk-hero-hints">
                    <span>Populer:</span>
                    <a href="jalur.php?kategori=cpns&amp;tab=tryout">Tryout CAT CPNS</a>
                    <a href="jalur.php?kategori=bumn&amp;tab=bank">Bank Soal AKHLAK</a>
                    <a href="jalur.php?kategori=kedinasan&amp;tab=bank">TPA STAN/STIS</a>
                    <a href="tes-potensi.php">Tes IQ &amp; Buta Warna Gratis</a>
                </div>
            </div>

            <!-- Kolom Kanan: Grid 2x2 Kartu Pilihan Jenis Tes -->
            <div class="pjk-hero-tracks">
                <div class="pjk-tracks-label">Mau fokus persiapan tes yang mana?</div>
                <div class="pjk-cat-grid" id="pilih-jalur">
                    <?php foreach ($tracks as $slug => $cfg):
                        $cnt = $trackCounts[$slug]['questions'] ?? 0;
                    ?>
                        <a href="jalur.php?kategori=<?= urlencode($slug) ?>" class="pjk-cat-card">
                            <div class="pjk-cat-top">
                                <span class="pjk-cat-icon"><?= ui_icon($cfg['icon'], 24) ?></span>
                                <span class="pjk-cat-count"><?= number_format($cnt) ?>+ Soal</span>
                            </div>
                            <h2><?= htmlspecialchars($cfg['short_title']) ?></h2>
                            <div class="pjk-cat-foot">
                                <p><?= htmlspecialchars($cfg['card_subtitle']) ?></p>
                                <span class="pjk-cat-chevron"><?= ui_icon('chevron-right', 16) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ROUNDED WHITE EDITORIAL SHEET (Menimpa bagian bawah hero seperti pajak.go.id) -->
<div class="pjk-sheet">
    <div class="container">

        <!-- BAGIAN 1: INFO & PANDUAN SELEKSI TERBARU (ARTIKEL) -->
        <?php if (!empty($latestArticles)): ?>
            <section class="pjk-section-info" style="margin-bottom: 3rem;">
                <div class="pjk-section-head">
                    <div class="pjk-section-title">
                        <span class="pjk-section-icon"><?= ui_icon_box('file-text', 'navy', 20) ?></span>
                        <div>
                            <h2>Info &amp; Tips Lolos Terbaru</h2>
                            <p>Kisi-kisi terbaru, aturan passing grade, dan strategi atur waktu CAT biar persiapanmu makin matang.</p>
                        </div>
                    </div>
                    <a href="artikel.php" class="pjk-link-all">
                        Lihat semua Artikel <?= ui_icon('arrow-right', 15, 'icon-arrow') ?>
                    </a>
                </div>

                <div class="grid-3">
                    <?php foreach ($latestArticles as $art):
                        $cMeta = $articleCats[$art['category']] ?? ['badge_class' => 'badge-navy', 'color' => '#2d4f8e'];
                    ?>
                        <article class="card card-navy-top" style="border-top-color: <?= htmlspecialchars($cMeta['color']) ?>; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem; flex-wrap: wrap; gap: 0.4rem;">
                                    <span class="badge <?= htmlspecialchars($cMeta['badge_class']) ?>"><?= htmlspecialchars($art['category']) ?></span>
                                    <span class="ui-inline" style="font-size: 0.76rem; color: var(--core-text-muted);"><?= ui_icon('clock', 12) ?> <?= (int)$art['reading_time'] ?> mnt baca</span>
                                </div>
                                <h3 style="font-size: 1.08rem; font-weight: 800; color: var(--core-navy); line-height: 1.4; margin-bottom: 0.55rem;">
                                    <a href="baca-artikel.php?slug=<?= urlencode($art['slug']) ?>" style="color: inherit; text-decoration: none;">
                                        <?= htmlspecialchars($art['title']) ?>
                                    </a>
                                </h3>
                                <p style="font-size: 0.86rem; color: var(--core-text-muted); line-height: 1.6; margin-bottom: 1rem;">
                                    <?= htmlspecialchars(mb_strimwidth($art['excerpt'], 0, 128, '...')) ?>
                                </p>
                            </div>
                            <div style="border-top: 1px solid var(--core-border-light); padding-top: 0.75rem; display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; flex-wrap: wrap; gap: 0.4rem;">
                                <span class="ui-inline" style="color: var(--core-text-muted);"><?= ui_icon('calendar', 12) ?> <?= formatTanggalIndo($art['created_at']) ?></span>
                                <a href="baca-artikel.php?slug=<?= urlencode($art['slug']) ?>" class="ui-inline" style="font-weight: 700; color: var(--core-navy); text-decoration: none;">
                                    Baca Panduan <?= ui_icon('arrow-right', 13, 'icon-arrow') ?>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- BAGIAN 2: TES KEMAMPUAN & POTENSI GRATIS (Di bawah bagian utama sesuai instruksi) -->
        <section class="tp-home-strip" style="margin-bottom: 3rem;">
            <div class="tp-home-head">
                <div>
                    <span class="tp-badge-emerald"><?= ui_icon('sparkles', 13) ?> 100% GRATIS &bull; TANPA LOGIN &bull; LANGSUNG COBA</span>
                    <h2>Tes Kemampuan &amp; Potensi Gratis</h2>
                    <p>Mau cek buta warna, ukur skor IQ, cari tahu minat kariermu, atau pemanasan pakai game kognitif? Langsung coba gratis tanpa perlu daftar akun!</p>
                </div>
                <a href="tes-potensi.php" class="btn btn-primary btn-sm">
                    Buka Semua Tes Gratis <?= ui_icon('arrow-right', 14, 'icon-arrow') ?>
                </a>
            </div>
            <div class="tp-home-grid">
                <a href="tes-potensi.php?tes=buta-warna" class="tp-home-card">
                    <?= ui_icon_box('eye', 'rose', 20) ?>
                    <strong>Tes Buta Warna</strong>
                    <span>18 pelat pseudoisokromatik SVG orisinal dengan hasil &amp; review instan.</span>
                    <em>Coba Gratis <?= ui_icon('arrow-right', 13) ?></em>
                </a>
                <a href="tes-potensi.php?tes=iq" class="tp-home-card">
                    <?= ui_icon_box('brain', 'navy', 20) ?>
                    <strong>Tes IQ Online (30 Soal)</strong>
                    <span>Figural, Numerik, Verbal, Logis &amp; Spasial (25 menit) dengan pembahasan langsung.</span>
                    <em>Coba Gratis <?= ui_icon('arrow-right', 13) ?></em>
                </a>
                <a href="tes-potensi.php?tes=minat-karier" class="tp-home-card">
                    <?= ui_icon_box('compass', 'emerald', 20) ?>
                    <strong>Minat Karier (RIASEC)</strong>
                    <span>Temukan Top-3 Holland Code &amp; rekomendasi jalur karier/seleksi yang cocok.</span>
                    <em>Coba Gratis <?= ui_icon('arrow-right', 13) ?></em>
                </a>
                <a href="tes-potensi.php?tes=game-kognitif" class="tp-home-card">
                    <?= ui_icon_box('gamepad', 'plum', 20) ?>
                    <strong>10 Game Kognitif</strong>
                    <span>Kejar Angka, Memori Grid, Sprint Pola, Balon Risiko, Sinyal Berhenti &amp; lainnya.</span>
                    <em>Main Gratis <?= ui_icon('arrow-right', 13) ?></em>
                </a>
            </div>
        </section>

        <!-- BAGIAN 3: STATISTIK RINGKAS PLATFORM -->
        <section class="grid-3" style="margin-bottom: 3rem;">
            <div class="stat-card twk">
                <div class="stat-label">TOTAL BANK SOAL MULTI-TES</div>
                <div class="stat-val"><?= number_format($totalQuestions) ?>+ <span style="font-size: 0.95rem; font-weight: 500; color: var(--core-text-muted);">Butir Soal</span></div>
                <p style="font-size: 0.825rem; color: var(--core-text-muted); margin-top: 0.25rem;">CPNS, BUMN, Kedinasan &amp; Psikotes Kerja</p>
            </div>
            <div class="stat-card tiu">
                <div class="stat-label">SESI SIMULASI DISELESAIKAN</div>
                <div class="stat-val"><?= number_format($totalAttempts) ?> <span style="font-size: 0.95rem; font-weight: 500; color: var(--core-text-muted);">Sesi Selesai</span></div>
                <p style="font-size: 0.825rem; color: var(--core-text-muted); margin-top: 0.25rem;">Peserta aktif ukur kecepatan &amp; akurasi tiap hari</p>
            </div>
            <div class="stat-card tkp">
                <div class="stat-label">PESERTA TERDAFTAR DI SIAPTES.ID</div>
                <div class="stat-val"><?= number_format($totalUsers) ?> <span style="font-size: 0.95rem; font-weight: 500; color: var(--core-text-muted);">Member Aktif</span></div>
                <p style="font-size: 0.825rem; color: var(--core-text-muted); margin-top: 0.25rem;">Gabung belajar bareng di satu akun praktis</p>
            </div>
        </section>

        <!-- BAGIAN 4: CALL TO ACTION PENUTUP -->
        <div class="hero cta-banner" style="margin-bottom: 0;">
            <div class="hero-pill" style="margin-bottom: 1rem;">
                <?= ui_icon('sparkles', 13) ?> SIAPTES.ID &bull; TEMPAT BELAJAR DAN LATIHAN TES TERLENGKAP
            </div>
            <h2 style="font-size: 1.9rem; font-weight: 800; margin-bottom: 0.75rem;">Sudah Siap Gas Latihan Hari Ini?</h2>
            <p style="font-size: 0.98rem; max-width: 650px; margin: 0 auto 1.75rem; line-height: 1.7;">
                Nggak perlu nunggu jadwal tes mepet baru belajar. Pilih jalur targetmu sekarang dan mulai cicil latihan dari paket gratisnya dulu!
            </p>
            <div style="display: flex; justify-content: center; gap: 0.85rem; flex-wrap: wrap;">
                <?php if (!isLoggedIn()): ?>
                    <a href="register.php" class="btn btn-gold" style="font-size: 0.98rem; padding: 0.82rem 1.75rem;">
                        Daftar Akun Gratis Sekarang
                    </a>
                <?php endif; ?>
                <a href="jalur.php?kategori=cpns" class="btn btn-outline" style="font-size: 0.98rem; padding: 0.82rem 1.75rem;">
                    Mulai Pilih Jalur Belajar
                </a>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
