<?php
// includes/header.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/visitor_tracker.php';
require_once __DIR__ . '/icons.php';

try {
    trackSiteVisit(getDB());
} catch (Throwable $e) {}

$user = currentUser();
$base_url = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base_url === '/admin' || $base_url === '/views') {
    $root_url = '..';
} else {
    $root_url = '.';
}
$currentScript = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - ' : '' ?>SIAP TES | Portal Tryout & Latihan CPNS, BUMN, Kedinasan & Psikotes - siaptes.id</title>
    <meta name="description" content="<?= isset($meta_description) && $meta_description !== '' ? htmlspecialchars($meta_description) : 'SIAP TES (siaptes.id) - Satu platform terpadu untuk latihan soal, info seleksi, dan simulasi CAT CPNS, Rekrutmen Bersama BUMN, Sekolah Kedinasan, dan Psikotes Kerja.' ?>">
    <?php $logoVer = @filemtime(__DIR__ . '/../assets/img/favicon.svg') ?: '20261120'; ?>
    <!-- Browser Toolbar / Tab Favicon & App Icons -->
    <link rel="icon" type="image/svg+xml" href="<?= $root_url ?>/assets/img/favicon.svg?v=<?= $logoVer ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $root_url ?>/assets/img/favicon-32x32.png?v=<?= $logoVer ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= $root_url ?>/assets/img/favicon-16x16.png?v=<?= $logoVer ?>">
    <link rel="shortcut icon" href="<?= $root_url ?>/favicon.ico?v=<?= $logoVer ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= $root_url ?>/assets/img/apple-touch-icon.png?v=<?= $logoVer ?>">
    <link rel="manifest" href="<?= $root_url ?>/site.webmanifest">
    <meta name="theme-color" content="#2d4f8e">
    <meta property="og:image" content="https://siaptes.id/assets/img/android-chrome-512x512.png">
    <!-- Google Font: Plus Jakarta Sans for Modern Indonesian EdTech Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php $cssVer = @filemtime(__DIR__ . '/../assets/css/style.css') ?: '20261120'; ?>
    <link rel="stylesheet" href="<?= $root_url ?>/assets/css/style.css?v=<?= $cssVer ?>">
</head>
<body>

<!-- Top Portal Bar (SIAP TES National Portal Ribbon) -->
<div class="gov-topbar">
    <div class="gov-topbar-container">
        <div class="gov-topbar-left">
            <span class="gov-flag" title="Republik Indonesia">
                <span class="gov-flag-red"></span>
                <span class="gov-flag-white"></span>
            </span>
            <span>PORTAL PERSIAPAN UJIAN & SELEKSI TERPADU RI &bull; <b>SIAPTES.ID</b></span>
        </div>
        <div class="gov-topbar-right">
            <span><span class="gov-status-dot"></span>PLATFORM AKTIF 24/7</span>
            <span style="opacity: 0.6;">|</span>
            <span>CPNS &bull; BUMN &bull; KEDINASAN &bull; PSIKOTES</span>
        </div>
    </div>
</div>

<!-- Main Navigation Bar (Coretax DJP Navy & Gold) -->
<nav class="navbar">
    <div class="nav-container">
        <a href="<?= $root_url ?>/index.php" class="nav-brand">
            <div class="brand-emblem" title="SIAP TES - siaptes.id">
                <img src="<?= $root_url ?>/assets/img/logo.svg?v=<?= $logoVer ?>" alt="Logo SIAP TES" width="42" height="42">
            </div>
            <div class="brand-text">
                <div class="brand-title">SIAP<span>TES</span><small class="brand-domain">.ID</small></div>
                <div class="brand-subtitle">Simulasi Seleksi Karier Terpadu</div>
            </div>
        </a>

        <button type="button" class="nav-toggle" id="navToggleBtn" aria-label="Buka Menu Navigasi" aria-expanded="false" onclick="toggleMobileNav()">
            <svg class="icon-menu" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
            <svg class="icon-close" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>

        <ul class="nav-menu" id="mainNavMenu">
            <li><a href="<?= $root_url ?>/index.php" class="<?= $currentScript === 'index.php' ? 'active' : '' ?>">Beranda</a></li>
            <li><a href="<?= $root_url ?>/tes-potensi.php" class="nav-free-link <?= $currentScript === 'tes-potensi.php' ? 'active' : '' ?>">Tes Kemampuan &amp; Potensi</a></li>
            <li><a href="<?= $root_url ?>/latihan.php" class="<?= $currentScript === 'latihan.php' ? 'active' : '' ?>">Bank Soal</a></li>
            <li><a href="<?= $root_url ?>/tryout.php" class="<?= $currentScript === 'tryout.php' ? 'active' : '' ?>">Tryout CAT</a></li>
            <li><a href="<?= $root_url ?>/artikel.php" class="<?= in_array($currentScript, ['artikel.php', 'baca-artikel.php']) ? 'active' : '' ?>">Artikel</a></li>
            <li><a href="<?= $root_url ?>/upgrade.php" class="nav-pro-link <?= $currentScript === 'upgrade.php' ? 'active' : '' ?>"><?= ui_icon('crown', 14) ?> PRO VIP</a></li>
            
            <?php if ($user): ?>
                <li><a href="<?= $root_url ?>/riwayat.php" class="<?= $currentScript === 'riwayat.php' ? 'active' : '' ?>">Riwayat</a></li>
                <?php if ($user['role'] === 'admin'): ?>
                    <li><a href="<?= $root_url ?>/admin/index.php" class="nav-admin-link <?= ($base_url === '/admin' && $currentScript !== 'statistik.php') ? 'active' : '' ?>">Admin</a></li>
                    <li><a href="<?= $root_url ?>/admin/statistik.php" class="nav-stats-link <?= ($base_url === '/admin' && $currentScript === 'statistik.php') ? 'active' : '' ?>"><?= ui_icon('bar-chart', 14) ?> Statistik</a></li>
                <?php endif; ?>
                <li class="nav-user-item">
                    <div class="user-badge-nav">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar">
                        <?php else: ?>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="color: var(--core-navy);"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        <?php endif; ?>
                        <span><?= htmlspecialchars(explode(' ', $user['name'])[0]) ?></span>
                        <?php if (!empty($user['is_premium']) || $user['role'] === 'admin'): ?>
                            <span class="badge badge-gold" style="font-size:0.64rem; padding: 2px 7px;">PRO</span>
                        <?php else: ?>
                            <span class="nav-trial-badge">TRIAL</span>
                        <?php endif; ?>
                    </div>
                </li>
                <li><a href="<?= $root_url ?>/logout.php" class="btn btn-sm nav-logout-btn">Keluar</a></li>
            <?php else: ?>
                <li class="nav-auth-btns"><a href="<?= $root_url ?>/login.php" class="btn btn-sm nav-login-btn">Masuk</a></li>
                <li class="nav-auth-btns"><a href="<?= $root_url ?>/register.php" class="btn btn-sm nav-register-btn">Daftar Gratis</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
<script>
function toggleMobileNav() {
    const menu = document.getElementById('mainNavMenu');
    const btn = document.getElementById('navToggleBtn');
    if (!menu || !btn) return;
    const isOpen = menu.classList.toggle('open');
    btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    const iconMenu = btn.querySelector('.icon-menu');
    const iconClose = btn.querySelector('.icon-close');
    if (iconMenu && iconClose) {
        iconMenu.style.display = isOpen ? 'none' : 'block';
        iconClose.style.display = isOpen ? 'block' : 'none';
    }
}
</script>

<?php if (empty($full_bleed_layout)): ?>
<div class="container">
<?php endif; ?>
<?php if (isset($_GET['msg'])): ?>
    <?php if (!empty($full_bleed_layout)): ?><div class="container" style="padding-top: 1.25rem;"><?php endif; ?>
    <div style="background: #eef6fc; color: var(--core-navy); padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; border: 1px solid #bfd7ec; border-left: 4px solid var(--core-navy); display: flex; align-items: center; gap: 10px; font-size: 0.9rem; font-weight: 600;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="color: var(--core-navy); flex-shrink: 0;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
        <span><?= htmlspecialchars($_GET['msg']) ?></span>
    </div>
    <?php if (!empty($full_bleed_layout)): ?></div><?php endif; ?>
<?php endif; ?>
