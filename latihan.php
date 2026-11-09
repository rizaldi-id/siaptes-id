<?php
// latihan.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$db = getDB();

$selectedGroup = $_GET['group'] ?? 'all';

// Fetch categories with question counts
$query = "
    SELECT c.*, COUNT(q.id) as question_count 
    FROM categories c 
    LEFT JOIN questions q ON c.id = q.category_id 
";
if ($selectedGroup !== 'all') {
    $query .= " WHERE c.category_group = " . $db->quote($selectedGroup);
}
$query .= " GROUP BY c.id ORDER BY c.id ASC";

$categories = $db->query($query)->fetchAll();

$page_title = "Bank Soal & Latihan Mandiri - SIAP TES";
require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 2rem;">
    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 0.25rem;">
        <span class="badge badge-navy">MODUL MANDIRI</span>
        <span style="font-size: 0.8rem; color: var(--core-text-muted);">siaptes.id &bull; Pendalaman Kompetensi Fleksibel</span>
    </div>
    <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--core-navy); margin-bottom: 0.5rem;">Bank Soal & Latihan Mandiri</h1>
    <p style="color: var(--core-text-muted); max-width: 820px; font-size: 0.95rem;">
        Perdalam kompetensi pada pilar materi yang ingin Anda kuasai. Anda bebas memilih kuota butir soal untuk latihan fleksibel kapan saja dengan kunci dan pembahasan lengkap.
    </p>

    <!-- Track Filter Buttons -->
    <div style="display: flex; gap: 0.5rem; margin-top: 1.25rem; flex-wrap: wrap;">
        <a href="latihan.php?group=all" class="btn btn-sm <?= $selectedGroup === 'all' ? 'btn-primary' : 'btn-outline' ?>" style="<?= $selectedGroup !== 'all' ? 'color: var(--core-navy) !important; border-color: var(--core-border);' : '' ?>">
            Semua Modul (10 Kategori)
        </a>
        <a href="latihan.php?group=cpns" class="btn btn-sm <?= $selectedGroup === 'cpns' ? 'btn-primary' : 'btn-outline' ?>" style="<?= $selectedGroup !== 'cpns' ? 'color: var(--core-navy) !important; border-color: var(--core-border);' : '' ?>">
            🏛️ CPNS / ASN (TWK, TIU, TKP)
        </a>
        <a href="latihan.php?group=bumn" class="btn btn-sm <?= $selectedGroup === 'bumn' ? 'btn-primary' : 'btn-outline' ?>" style="<?= $selectedGroup !== 'bumn' ? 'color: var(--core-navy) !important; border-color: var(--core-border);' : '' ?>">
            🏢 BUMN (TKD, AKHLAK, English)
        </a>
        <a href="latihan.php?group=kedinasan" class="btn btn-sm <?= $selectedGroup === 'kedinasan' ? 'btn-primary' : 'btn-outline' ?>" style="<?= $selectedGroup !== 'kedinasan' ? 'color: var(--core-navy) !important; border-color: var(--core-border);' : '' ?>">
            🎓 Kedinasan (TPA & Aljabar)
        </a>
        <a href="latihan.php?group=psikotes" class="btn btn-sm <?= $selectedGroup === 'psikotes' ? 'btn-primary' : 'btn-outline' ?>" style="<?= $selectedGroup !== 'psikotes' ? 'color: var(--core-navy) !important; border-color: var(--core-border);' : '' ?>">
            🧠 Psikotes (Logika, Spasial, SJT)
        </a>
    </div>
</div>

<div class="grid-3">
    <?php foreach ($categories as $cat): ?>
        <div class="card card-navy-top" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <span class="badge badge-<?= strtolower($cat['code']) ?>"><?= $cat['code'] ?></span>
                    <span style="font-size: 0.8rem; font-weight: 700; color: var(--core-navy);"><?= $cat['question_count'] ?> Soal Tersedia</span>
                </div>
                <h2 style="font-size: 1.15rem; font-weight: 800; color: var(--core-navy); margin-bottom: 0.5rem;"><?= htmlspecialchars($cat['name']) ?></h2>
                <p style="color: var(--core-text-muted); font-size: 0.875rem; line-height: 1.6; margin-bottom: 1.25rem;">
                    <?= htmlspecialchars($cat['description']) ?>
                </p>
            </div>

            <div style="border-top: 1px solid var(--core-border); padding-top: 1rem;">
                <form method="GET" action="exam.php" style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <input type="hidden" name="action" value="start_practice">
                    <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">

                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <label style="font-size: 0.825rem; font-weight: 700; color: var(--core-navy); flex-shrink: 0;">Pilih Kuota:</label>
                        <select name="limit" class="form-control" style="padding: 0.4rem 0.6rem; font-size: 0.875rem;">
                            <option value="5">5 Butir Soal (Trial Gratis)</option>
                            <option value="10" <?= isPremiumUser() ? 'selected' : '' ?>>10 Butir Soal (⭐ PRO VIP)</option>
                            <option value="20">20 Butir Soal (⭐ PRO VIP)</option>
                            <option value="30">30 Butir Soal (⭐ PRO VIP)</option>
                            <option value="50">Semua Soal Tersedia (⭐ PRO VIP)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm" style="width: 100%;">
                        Mulai Latihan <?= $cat['code'] ?> ➔
                    </button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card card-navy-top" style="margin-top: 2.5rem;">
    <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--core-navy); margin-bottom: 0.5rem;">💡 Tips Sukses Latihan Mandiri di SIAP TES:</h3>
    <ul style="padding-left: 1.25rem; font-size: 0.9rem; color: var(--core-text-muted); line-height: 1.8;">
        <li><b>CPNS & Kedinasan:</b> Asah kecepatan berhitung dan penalaran analitis di bagian <b>TIU</b>, serta biasakan memilih opsi berintegritas tinggi dengan poin 5 pada soal <b>TKP</b>.</li>
        <li><b>BUMN:</b> Pelajari implementasi nilai <b>AKHLAK</b> pada studi kasus riil korporat dan biasakan membaca cepat kalimat bisnis pada tes <b>Bahasa Inggris</b>.</li>
        <li><b>Psikotes Kerja:</b> Latih ketajaman visual pada soal <b>Spasial 3D</b> dan pahami penalaran silogisme untuk memecahkan pola logika deduktif dengan efisien.</li>
        <li>Setelah menyelesaikan setiap sesi latihan, evaluasi pembahasan soal untuk memperkuat pemahaman konsep dasar.</li>
    </ul>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
