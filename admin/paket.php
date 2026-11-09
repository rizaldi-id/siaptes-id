<?php
// admin/paket.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();
$db = getDB();

// Handle Delete Package
if (isset($_GET['delete'])) {
    $delId = intval($_GET['delete']);
    $db->exec("DELETE FROM packages WHERE id = $delId");
    header("Location: paket.php?msg=Paket berhasil dihapus");
    exit;
}

// Handle Add/Edit Package
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pkgId = intval($_POST['package_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $duration = intval($_POST['duration_minutes'] ?? 100);
    $is_premium = isset($_POST['is_premium']) ? 1 : 0;
    $price = intval($_POST['price'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($title)) {
        $error = 'Judul paket wajib diisi.';
    } else {
        if ($pkgId > 0) {
            $stmt = $db->prepare("UPDATE packages SET title = ?, description = ?, duration_minutes = ?, is_premium = ?, price = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$title, $description, $duration, $is_premium, $price, $is_active, $pkgId]);
            header("Location: paket.php?msg=Paket berhasil diperbarui");
            exit;
        } else {
            $stmt = $db->prepare("INSERT INTO packages (title, description, duration_minutes, is_premium, price, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $duration, $is_premium, $price, $is_active]);
            header("Location: paket.php?msg=Paket baru berhasil ditambahkan");
            exit;
        }
    }
}

$packages = $db->query("
    SELECT p.*, COUNT(q.id) as question_count 
    FROM packages p 
    LEFT JOIN questions q ON p.id = q.package_id 
    GROUP BY p.id 
    ORDER BY p.id DESC
")->fetchAll();

$page_title = "Kelola Paket Tryout CPNS";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--gray-900);">Kelola Paket Tryout</h1>
        <p style="color: var(--gray-600);">Atur paket simulasi CAT CPNS, durasi pengerjaan, dan status aktif.</p>
    </div>
    <a href="index.php" class="btn btn-secondary btn-sm">⬅ Kembali ke Admin</a>
</div>

<?php if ($error): ?>
    <div style="background: #fee2e2; color: #b91c1c; padding: 0.75rem 1rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="grid-2">
    <!-- Form Tambah Paket -->
    <div class="card">
        <h2 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem;">+ Tambah / Edit Paket Tryout</h2>
        <form method="POST" action="">
            <input type="hidden" name="package_id" id="pkg_id" value="0">

            <div class="form-group">
                <label>Judul Paket Tryout *</label>
                <input type="text" name="title" id="pkg_title" class="form-control" placeholder="Contoh: Tryout SKD Akbar Gelombang 2" required>
            </div>

            <div class="form-group">
                <label>Deskripsi Singkat</label>
                <textarea name="description" id="pkg_description" class="form-control" rows="3" placeholder="Penjelasan paket simulasi..."></textarea>
            </div>

            <div class="form-group">
                <label>Durasi Ujian (Menit) *</label>
                <input type="number" name="duration_minutes" id="pkg_duration" class="form-control" value="100" min="5" max="300" required>
                <small style="color: var(--gray-600);">Standar durasi SKD CPNS resmi adalah 100 menit.</small>
            </div>

            <div class="form-group" style="background: var(--gray-50); border: 1px solid var(--gray-200); padding: 0.85rem; border-radius: var(--radius-sm);">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <input type="checkbox" name="is_premium" id="pkg_is_premium" value="1" style="width: 18px; height: 18px;" onchange="togglePriceInput()">
                    <label for="pkg_is_premium" style="margin-bottom: 0; cursor: pointer; font-weight: 700; color: #b45309;">
                        ⭐ Paket Khusus Premium / Berbayar
                    </label>
                </div>
                <div id="price_container" style="display: none; margin-top: 0.5rem;">
                    <label style="font-size: 0.85rem; font-weight: 600;">Harga Paket (Rp):</label>
                    <input type="number" name="price" id="pkg_price" class="form-control" value="49000" min="0" step="1000">
                    <small style="color: var(--gray-600);">Biaya buka akses paket ini untuk user biasa (Rp).</small>
                </div>
            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem;">
                <input type="checkbox" name="is_active" id="pkg_is_active" value="1" checked style="width: 18px; height: 18px;">
                <label for="pkg_is_active" style="margin-bottom: 0; cursor: pointer;">Status Aktif (Tampil untuk peserta)</label>
            </div>

            <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Simpan Paket</button>
                <button type="button" class="btn btn-secondary" onclick="resetForm()">Reset</button>
            </div>
        </form>
    </div>

    <!-- Daftar Paket -->
    <div class="card">
        <h2 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem;">Daftar Paket Terdaftar</h2>
        
        <?php if (empty($packages)): ?>
            <p style="color: var(--gray-600);">Belum ada paket tryout.</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($packages as $pkg): ?>
                    <div style="border: 1px solid var(--gray-200); border-radius: var(--radius-sm); padding: 1rem; background: var(--gray-50);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                            <div>
                                <?php if (!empty($pkg['is_premium'])): ?>
                                    <span class="badge" style="background: #fef3c7; color: #b45309; font-weight: 800; font-size: 0.75rem;">⭐ BERBAYAR (Rp <?= number_format($pkg['price'] ?: 49000, 0, ',', '.') ?>)</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #d1fae5; color: #065f46; font-weight: 800; font-size: 0.75rem;">🎁 TRIAL / GRATIS</span>
                                <?php endif; ?>
                                <h3 style="font-size: 1.05rem; font-weight: 700; margin-top: 0.25rem;"><?= htmlspecialchars($pkg['title']) ?></h3>
                            </div>
                            <span class="badge" style="background: var(--primary-light); color: var(--primary);">
                                <?= $pkg['duration_minutes'] ?> Menit
                            </span>
                        </div>
                        <p style="font-size: 0.85rem; color: var(--gray-600); margin-bottom: 0.75rem;">
                            <?= htmlspecialchars($pkg['description']) ?>
                        </p>
                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--gray-200); padding-top: 0.75rem; font-size: 0.825rem;">
                            <span>Status: <?= $pkg['is_active'] ? '<b style="color:var(--success);">Aktif</b>' : '<b style="color:var(--danger);">Nonaktif</b>' ?></span>
                            <div style="display: flex; gap: 0.5rem;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick='editPkg(<?= json_encode($pkg) ?>)'>Edit</button>
                                <a href="paket.php?delete=<?= $pkg['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus paket ini?');">Hapus</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function togglePriceInput() {
    const isPrem = document.getElementById('pkg_is_premium').checked;
    document.getElementById('price_container').style.display = isPrem ? 'block' : 'none';
}

function editPkg(pkg) {
    document.getElementById('pkg_id').value = pkg.id;
    document.getElementById('pkg_title').value = pkg.title;
    document.getElementById('pkg_description').value = pkg.description || '';
    document.getElementById('pkg_duration').value = pkg.duration_minutes;
    document.getElementById('pkg_is_premium').checked = (pkg.is_premium == 1);
    document.getElementById('pkg_price').value = pkg.price || 49000;
    document.getElementById('pkg_is_active').checked = (pkg.is_active == 1);
    togglePriceInput();
    window.scrollTo({top: 0, behavior: 'smooth'});
}

function resetForm() {
    document.getElementById('pkg_id').value = 0;
    document.getElementById('pkg_title').value = '';
    document.getElementById('pkg_description').value = '';
    document.getElementById('pkg_duration').value = 100;
    document.getElementById('pkg_is_premium').checked = false;
    document.getElementById('pkg_price').value = 49000;
    document.getElementById('pkg_is_active').checked = true;
    togglePriceInput();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
