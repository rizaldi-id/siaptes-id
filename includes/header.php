<?php
// includes/header.php
require_once __DIR__ . '/auth.php';
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
    <!-- Google Font: Plus Jakarta Sans for Modern Indonesian Gov-Tech Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $root_url ?>/assets/css/style.css">
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
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            <div class="brand-text">
                <div class="brand-title">SIAP<span>TES</span><small style="font-size:0.75rem; color: #a5d8ff; font-weight:700;">.ID</small></div>
                <div class="brand-subtitle">Simulasi Seleksi Karier Terpadu</div>
            </div>
        </a>

        <ul class="nav-menu">
            <li><a href="<?= $root_url ?>/index.php" class="<?= $currentScript === 'index.php' ? 'active' : '' ?>">Beranda</a></li>
            <li><a href="<?= $root_url ?>/latihan.php" class="<?= $currentScript === 'latihan.php' ? 'active' : '' ?>">Bank Soal Mandiri</a></li>
            <li><a href="<?= $root_url ?>/tryout.php" class="<?= $currentScript === 'tryout.php' ? 'active' : '' ?>">Simulasi Tryout</a></li>
            <li><a href="<?= $root_url ?>/artikel.php" class="<?= in_array($currentScript, ['artikel.php', 'baca-artikel.php']) ? 'active' : '' ?>">Artikel & Info</a></li>
            <li><a href="<?= $root_url ?>/upgrade.php" class="<?= $currentScript === 'upgrade.php' ? 'active' : '' ?>" style="color: var(--core-gold); font-weight: 700;">⭐ Akses PRO VIP</a></li>
            
            <?php if ($user): ?>
                <li><a href="<?= $root_url ?>/riwayat.php" class="<?= $currentScript === 'riwayat.php' ? 'active' : '' ?>">Riwayat Skor</a></li>
                <?php if ($user['role'] === 'admin'): ?>
                    <li><a href="<?= $root_url ?>/admin/index.php" style="color: #67e8f9; font-weight:700;">Panel Pengawas</a></li>
                <?php endif; ?>
                <li>
                    <div class="user-badge-nav">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar">
                        <?php else: ?>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="color: var(--core-gold);"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        <?php endif; ?>
                        <span><?= htmlspecialchars(explode(' ', $user['name'])[0]) ?></span>
                        <?php if (!empty($user['is_premium']) || $user['role'] === 'admin'): ?>
                            <span class="badge badge-gold" style="font-size:0.65rem; padding: 2px 6px;">PRO VIP</span>
                        <?php else: ?>
                            <span class="badge" style="background: rgba(255,255,255,0.2); color:#ffffff; font-size:0.65rem; padding: 2px 6px;">TRIAL</span>
                        <?php endif; ?>
                    </div>
                </li>
                <li><a href="<?= $root_url ?>/logout.php" class="btn btn-secondary btn-sm" style="background: rgba(255,255,255,0.15); color: #fff !important; border-color: rgba(255,255,255,0.25);">Keluar</a></li>
            <?php else: ?>
                <li><a href="<?= $root_url ?>/login.php" class="btn btn-outline btn-sm">Masuk Akun</a></li>
                <li><a href="<?= $root_url ?>/register.php" class="btn btn-gold btn-sm">Daftar Akun Gratis</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<div class="container">
<?php if (isset($_GET['msg'])): ?>
    <div style="background: #eef6fc; color: var(--core-navy); padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; border: 1px solid #bfd7ec; border-left: 4px solid var(--core-navy); display: flex; align-items: center; gap: 10px; font-size: 0.9rem; font-weight: 600;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="color: var(--core-navy); flex-shrink: 0;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
        <span><?= htmlspecialchars($_GET['msg']) ?></span>
    </div>
<?php endif; ?>
