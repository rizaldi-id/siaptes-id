<?php
// tes-potensi.php — Menu Gratis: Tes Kemampuan & Potensi (Tanpa Login)
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$allowedTabs = ['hub', 'buta-warna', 'iq', 'minat-karier', 'game-kognitif'];
$initialTab = isset($_GET['tes']) ? strtolower(trim((string)$_GET['tes'])) : 'hub';
if (!in_array($initialTab, $allowedTabs, true)) {
    $initialTab = 'hub';
}

$metaMap = [
    'hub' => [
        'title' => 'Tes Kemampuan & Potensi Gratis (Buta Warna, IQ, Minat Karier & Game Kognitif)',
        'desc'  => 'Coba gratis tanpa login di SIAP TES: Tes Buta Warna 18 pelat, Tes IQ 30 soal, Tes Minat Karier RIASEC Holland Code, serta 10 Game Kognitif & Asesmen interaktif.'
    ],
    'buta-warna' => [
        'title' => 'Tes Buta Warna Online Gratis (18 Pelat) - Tes Kemampuan & Potensi',
        'desc'  => 'Skrining awal persepsi warna gratis dengan 18 pelat pseudoisokromatik SVG orisinal dan review instan di SIAP TES.'
    ],
    'iq' => [
        'title' => 'Tes IQ Online Gratis (30 Soal & Pembahasan) - Tes Kemampuan & Potensi',
        'desc'  => 'Latihan Tes IQ gratis 30 soal 25 menit mencakup domain Figural, Numerik, Verbal, Logis, dan Spasial dengan pembahasan langsung.'
    ],
    'minat-karier' => [
        'title' => 'Tes Minat Karier RIASEC Gratis - Tes Kemampuan & Potensi',
        'desc'  => 'Kenali 3 tema minat karier terkuat Anda (Holland Code R-I-A-S-E-C) beserta rekomendasi profesi dan jalur seleksi CPNS, BUMN, Kedinasan.'
    ],
    'game-kognitif' => [
        'title' => 'Game Kognitif & Latihan Asesmen Gim Gratis - Tes Kemampuan & Potensi',
        'desc'  => 'Mainkan 4 gim kognitif cepat dan 6 latihan asesmen kerja berbasis gim secara gratis tanpa login di SIAP TES.'
    ]
];

$page_title = $metaMap[$initialTab]['title'];
$meta_description = $metaMap[$initialTab]['desc'];
$potensiAssetVer = @filemtime(__DIR__ . '/assets/js/tes-potensi.js') ?: '20261120';
$loggedUser = currentUser();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Header: Tes Kemampuan & Potensi (100% Gratis Tanpa Login) -->
<section class="tp-hero">
    <div class="tp-hero-pill">
        <?= ui_icon('sparkles', 14) ?> 100% GRATIS &bull; TANPA LOGIN &bull; HASIL INSTAN
    </div>
    <h1>Tes Kemampuan &amp; Potensi</h1>
    <p>
        Kenali potensi kognitif, persepsi warna, arah minat karier, dan ketangkasan berpikir Anda secara gratis.
        Dirancang interaktif, nyaman di mata, dan langsung bisa dicoba siapa saja tanpa perlu mendaftar.
    </p>
    <div class="tp-hero-stats">
        <span class="ui-inline"><?= ui_icon('eye', 14) ?> 18 Pelat Buta Warna</span>
        <span class="ui-inline"><?= ui_icon('brain', 14) ?> 30 Soal Tes IQ</span>
        <span class="ui-inline"><?= ui_icon('compass', 14) ?> RIASEC 18 / 72 Item</span>
        <span class="ui-inline"><?= ui_icon('gamepad', 14) ?> 10 Game Kognitif</span>
    </div>
</section>

<!-- Navigasi Tab Modul -->
<nav class="tp-tabs-wrap" aria-label="Pilih Modul Tes Kemampuan dan Potensi" role="tablist">
    <button type="button" role="tab" class="tp-tab-btn <?= $initialTab === 'hub' ? 'active' : '' ?>" data-tab="hub" aria-selected="<?= $initialTab === 'hub' ? 'true' : 'false' ?>" onclick="TP.openTab('hub', true)">
        <?= ui_icon('home', 16) ?> Ringkasan
    </button>
    <button type="button" role="tab" class="tp-tab-btn <?= $initialTab === 'buta-warna' ? 'active' : '' ?>" data-tab="buta-warna" aria-selected="<?= $initialTab === 'buta-warna' ? 'true' : 'false' ?>" onclick="TP.openTab('buta-warna', true)">
        <?= ui_icon('eye', 16) ?> Tes Buta Warna
    </button>
    <button type="button" role="tab" class="tp-tab-btn <?= $initialTab === 'iq' ? 'active' : '' ?>" data-tab="iq" aria-selected="<?= $initialTab === 'iq' ? 'true' : 'false' ?>" onclick="TP.openTab('iq', true)">
        <?= ui_icon('brain', 16) ?> Tes IQ Gratis
    </button>
    <button type="button" role="tab" class="tp-tab-btn <?= $initialTab === 'minat-karier' ? 'active' : '' ?>" data-tab="minat-karier" aria-selected="<?= $initialTab === 'minat-karier' ? 'true' : 'false' ?>" onclick="TP.openTab('minat-karier', true)">
        <?= ui_icon('compass', 16) ?> Minat Karier
    </button>
    <button type="button" role="tab" class="tp-tab-btn <?= $initialTab === 'game-kognitif' ? 'active' : '' ?>" data-tab="game-kognitif" aria-selected="<?= $initialTab === 'game-kognitif' ? 'true' : 'false' ?>" onclick="TP.openTab('game-kognitif', true)">
        <?= ui_icon('gamepad', 16) ?> Game Kognitif
    </button>
</nav>

<!-- Area Utama Interaktif -->
<section id="potensiStage" class="tp-stage" aria-live="polite">
    <div class="card tp-loading">
        <div class="tp-spinner"></div>
        <p>Memuat modul Tes Kemampuan &amp; Potensi...</p>
    </div>
</section>

<!-- Ajakan Eksplorasi Lanjutan ke Ekosistem SIAP TES -->
<section class="tp-explore-cta">
    <div class="tp-explore-text">
        <span class="tp-badge-indigo">SIAP MELANGKAH LEBIH JAUH?</span>
        <h3>Lanjutkan Persiapan Seleksi Resmi Anda di SIAP TES</h3>
        <p>
            Setelah mencoba tes potensi gratis di atas, uji kesiapan Anda menghadapi ujian sesungguhnya melalui
            <b>Simulasi CAT CPNS (110 Soal Standar BKN)</b>, <b>Rekrutmen BUMN</b>, <b>Sekolah Kedinasan</b>, serta <b>7.000+ Bank Soal &amp; Psikotes Kerja</b>.
        </p>
    </div>
    <div class="tp-explore-actions">
        <a href="tryout.php" class="btn btn-primary"><?= ui_icon('play', 15) ?> Coba Simulasi Tryout CAT</a>
        <a href="latihan.php" class="btn btn-outline"><?= ui_icon('book-open', 15) ?> Jelajahi Bank Soal</a>
        <?php if (!$loggedUser): ?>
            <a href="register.php" class="btn btn-gold"><?= ui_icon('sparkles', 15) ?> Buat Akun Gratis</a>
        <?php endif; ?>
    </div>
</section>

<script>
window.POTENSI_INITIAL_TAB = <?= json_encode($initialTab) ?>;
window.POTENSI_DATA_BASE = 'assets/data';
window.POTENSI_ASSET_VER = <?= json_encode((string)$potensiAssetVer) ?>;
</script>
<script src="assets/js/tes-potensi.js?v=<?= urlencode((string)$potensiAssetVer) ?>"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
