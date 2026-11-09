<?php
// tryout.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$db = getDB();

$selectedTrack = $_GET['track'] ?? 'all';

// Fetch all active packages with total questions
$pkgQuery = "
    SELECT p.*, COUNT(q.id) as question_count 
    FROM packages p 
    LEFT JOIN questions q ON p.id = q.package_id 
    WHERE p.is_active = 1 
";
if ($selectedTrack === 'trial') {
    $pkgQuery .= " AND p.is_premium = 0 ";
} elseif ($selectedTrack !== 'all') {
    $pkgQuery .= " AND (p.category_group = " . $db->quote($selectedTrack) . " OR p.category_group = 'all') ";
}
$pkgQuery .= " GROUP BY p.id ORDER BY p.id ASC";

$packages = $db->query($pkgQuery)->fetchAll();

// Fetch category compositions for each package
$compositions = $db->query("
    SELECT q.package_id, c.code, c.name, COUNT(q.id) as q_cnt 
    FROM questions q 
    JOIN categories c ON q.category_id = c.id 
    WHERE q.package_id IS NOT NULL 
    GROUP BY q.package_id, c.code, c.name
")->fetchAll();

$pkgMap = [];
foreach ($compositions as $comp) {
    $pkgMap[$comp['package_id']][] = $comp['code'] . ' (' . $comp['q_cnt'] . ' Soal)';
}

$page_title = "Simulasi Tryout CAT Terpadu - SIAP TES";
require_once __DIR__ . '/includes/header.php';
$isUserPremium = isPremiumUser();
?>

<div style="margin-bottom: 2rem;">
    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 0.25rem;">
        <span class="badge badge-navy">MODUL SIMULASI CAT</span>
        <span style="font-size: 0.8rem; color: var(--core-text-muted);">siaptes.id &bull; Sistem Tryout CAT Multi-Jalur</span>
    </div>
    <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--core-navy); margin-bottom: 0.5rem;">Simulasi Tryout Ujian Seleksi Terpadu</h1>
    <p style="color: var(--core-text-muted); max-width: 820px; font-size: 0.95rem;">
        Uji kompetensi Anda dalam sistem Computer Assisted Test (CAT) nyata dengan timer hitung mundur, navigasi interaktif, formula penilaian resmi, dan kalkulasi passing grade real-time.
    </p>

    <!-- Track Filter Buttons -->
    <div style="display: flex; gap: 0.5rem; margin-top: 1.25rem; flex-wrap: wrap;">
        <a href="tryout.php?track=all" class="btn btn-sm <?= $selectedTrack === 'all' ? 'btn-primary' : 'btn-outline' ?>" style="<?= $selectedTrack !== 'all' ? 'color: var(--core-navy) !important; border-color: var(--core-border);' : '' ?>">
            Semua Tryout
        </a>
        <a href="tryout.php?track=cpns" class="btn btn-sm <?= $selectedTrack === 'cpns' ? 'btn-primary' : 'btn-outline' ?>" style="<?= $selectedTrack !== 'cpns' ? 'color: var(--core-navy) !important; border-color: var(--core-border);' : '' ?>">
            🏛️ CPNS & PPPK
        </a>
        <a href="tryout.php?track=bumn" class="btn btn-sm <?= $selectedTrack === 'bumn' ? 'btn-primary' : 'btn-outline' ?>" style="<?= $selectedTrack !== 'bumn' ? 'color: var(--core-navy) !important; border-color: var(--core-border);' : '' ?>">
            🏢 BUMN (RBB FHCI)
        </a>
        <a href="tryout.php?track=kedinasan" class="btn btn-sm <?= $selectedTrack === 'kedinasan' ? 'btn-primary' : 'btn-outline' ?>" style="<?= $selectedTrack !== 'kedinasan' ? 'color: var(--core-navy) !important; border-color: var(--core-border);' : '' ?>">
            🎓 Sekolah Kedinasan
        </a>
        <a href="tryout.php?track=psikotes" class="btn btn-sm <?= $selectedTrack === 'psikotes' ? 'btn-primary' : 'btn-outline' ?>" style="<?= $selectedTrack !== 'psikotes' ? 'color: var(--core-navy) !important; border-color: var(--core-border);' : '' ?>">
            🧠 Psikotes Kerja
        </a>
        <a href="tryout.php?track=trial" class="btn btn-sm <?= $selectedTrack === 'trial' ? 'btn-gold' : 'btn-outline' ?>" style="<?= $selectedTrack !== 'trial' ? 'color: var(--core-gold-text) !important; border-color: var(--core-gold);' : '' ?>">
            🎁 Free Trial Gratis
        </a>
    </div>
</div>

<?php if (!$isUserPremium): ?>
    <div class="gov-notice" style="border-left-color: var(--core-gold); margin-bottom: 2rem;">
        <div class="gov-notice-icon">⭐</div>
        <div class="gov-notice-content" style="display: flex; justify-content: space-between; align-items: center; width: 100%; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h4>Status Akun Anda: Pengguna Baru (Free Trial)</h4>
                <p>
                    Anda dapat mencoba <b>Paket 5: Mini Tryout All-in-One Multi-Tes</b> secara gratis tanpa batas waktu. Aktifkan status <b>PRO VIP All-Access</b> untuk membuka seluruh paket tryout CPNS, BUMN, Kedinasan, dan Psikotes.
                </p>
            </div>
            <a href="upgrade.php" class="btn btn-gold btn-sm" style="white-space: nowrap;">
                ⭐ Upgrade ke PRO VIP
            </a>
        </div>
    </div>
<?php endif; ?>

<div class="grid-2">
    <?php foreach ($packages as $pkg): 
        $isPkgPremium = !empty($pkg['is_premium']);
        $canAccess = (!$isPkgPremium || $isUserPremium);
        $compositionList = $pkgMap[$pkg['id']] ?? [];
    ?>
        <div class="card <?= $isPkgPremium ? 'card-gold-top' : 'card-navy-top' ?>" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; gap: 0.5rem;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                            <?php if ($isPkgPremium): ?>
                                <span class="badge badge-gold">⭐ AKSES PRO VIP</span>
                            <?php else: ?>
                                <span class="badge badge-success">🎁 TRIAL PERCOBAAN GRATIS</span>
                            <?php endif; ?>
                        </div>
                        <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--core-navy);"><?= htmlspecialchars($pkg['title']) ?></h2>
                    </div>
                    <span class="badge badge-navy" style="font-size: 0.825rem; padding: 0.35rem 0.75rem; white-space: nowrap;">
                        ⏱️ <?= $pkg['duration_minutes'] ?> Menit
                    </span>
                </div>
                <p style="color: var(--core-text-muted); font-size: 0.885rem; line-height: 1.6; margin-bottom: 1.25rem;">
                    <?= htmlspecialchars($pkg['description']) ?>
                </p>

                <div style="background: var(--core-navy-subtle); border: 1px solid var(--core-border); border-radius: var(--radius-sm); padding: 0.75rem 1rem; margin-bottom: 1.25rem;">
                    <div style="font-size: 0.78rem; font-weight: 800; color: var(--core-navy); margin-bottom: 0.35rem; letter-spacing: 0.4px;">
                        KOMPOSISI SOAL: <?= $pkg['question_count'] ?> BUTIR SOAL
                    </div>
                    <div style="display: flex; gap: 0.5rem; font-size: 0.825rem; color: var(--core-text); flex-wrap: wrap;">
                        <?php if (!empty($compositionList)): ?>
                            <?php foreach ($compositionList as $cItem): ?>
                                <span class="badge badge-navy" style="font-size: 0.72rem;"><?= htmlspecialchars($cItem) ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span>Soal Terstandarisasi Multi-Materi</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--core-border); padding-top: 1.25rem;">
                <span style="font-size: 0.85rem; font-weight: 600;">
                    <?php if ($isPkgPremium): ?>
                        <span style="color: var(--core-gold-text);">🔒 Khusus Member PRO</span>
                    <?php else: ?>
                        <span style="color: var(--core-success);">🟢 Terbuka Bebas (Trial)</span>
                    <?php endif; ?>
                </span>

                <?php if ($canAccess): ?>
                    <a href="exam.php?action=start_package&package_id=<?= $pkg['id'] ?>" class="btn btn-primary" onclick="return confirm('Mulai sesi simulasi CAT sekarang? Timer ujian akan langsung dihitung!');">
                        Masuk Ujian CAT ➔
                    </a>
                <?php else: ?>
                    <a href="upgrade.php?package_id=<?= $pkg['id'] ?>" class="btn btn-gold">
                        🔒 Buka Akses PRO (Rp 29.000 via QRIS)
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card card-navy-top" style="margin-top: 2.5rem;">
    <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--core-navy); margin-bottom: 0.75rem; display: flex; align-items: center; gap: 8px;">
        <span>📋</span> Petunjuk Teknis Pelaksanaan Ujian CAT di SIAP TES:
    </h3>
    <ol style="padding-left: 1.25rem; font-size: 0.875rem; color: var(--core-text-muted); line-height: 1.8;">
        <li>Pastikan koneksi internet Anda stabil sebelum menekan tombol <b>Masuk Ujian CAT</b>.</li>
        <li>Timer ujian akan langsung berjalan secara otomatis begitu lembar soal pertama ditampilkan.</li>
        <li>Navigasi nomor soal di sebelah kanan: <b>Biru Tua (Sudah Dijawab)</b>, <b>Kuning (Ragu-ragu)</b>, dan <b>Putih (Belum Dijawab)</b>.</li>
        <li>Semua jawaban Anda tersimpan otomatis (Cloud Auto-save) setiap kali opsi diklik tanpa perlu menekan tombol konfirmasi.</li>
        <li>Bila waktu ujian habis, formulir akan otomatis terkunci dan sistem langsung menghitung kalkulasi skor serta evaluasi Passing Grade.</li>
    </ol>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
