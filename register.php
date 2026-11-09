<?php
// register.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Harap lengkapi semua kolom.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Kata sandi minimal 6 karakter.';
    } elseif ($password !== $password_confirm) {
        $error = 'Konfirmasi kata sandi tidak cocok.';
    } else {
        $db = getDB();
        // Check if email already registered
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existingUser = $stmt->fetch();

        if ($existingUser) {
            if (!empty($existingUser['google_id'])) {
                // Jika sebelumnya daftar via Google, izinkan mengatur password agar bisa login biasa juga
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $upd = $db->prepare("UPDATE users SET name = ?, password = ? WHERE id = ?");
                $upd->execute([$name, $hashed, $existingUser['id']]);

                $_SESSION['user_id'] = $existingUser['id'];
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $existingUser['email'];
                $_SESSION['user_role'] = $existingUser['role'];
                $_SESSION['user_avatar'] = $existingUser['avatar'] ?? null;

                header('Location: index.php?msg=Kata sandi berhasil diatur! Kini Anda dapat login menggunakan Google maupun Email & Kata Sandi.');
                exit;
            } else {
                $error = 'Email sudah terdaftar. Silakan masuk di halaman Login.';
            }
        } else {
            $now = date('Y-m-d H:i:s');
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role, is_premium, created_at) VALUES (?, ?, ?, 'user', 0, ?)");
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt->execute([$name, strtolower($email), $hashed, $now]);

            // Auto-login after registration
            $newId = $db->lastInsertId();
            $_SESSION['user_id'] = $newId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = 'user';

            header('Location: index.php?msg=Pendaftaran berhasil! Selamat datang di SIAP TES (siaptes.id).');
            exit;
        }
    }
}

$page_title = "Daftar Akun Baru - SIAP TES";
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 520px; margin: 2.5rem auto;">
    <div class="card card-gold-top" style="padding: 2.25rem;">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <div style="display: inline-flex; align-items: center; justify-content: center; width: 56px; height: 56px; border-radius: var(--radius-sm); background: linear-gradient(135deg, var(--core-navy) 0%, #073b64 100%); color: var(--core-gold); margin-bottom: 0.85rem; box-shadow: 0 4px 10px rgba(0, 43, 73, 0.25);">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            <h2 style="font-size: 1.45rem; font-weight: 800; color: var(--core-navy); margin-bottom: 0.25rem;">Daftar Akun SIAP TES</h2>
            <p style="color: var(--core-text-muted); font-size: 0.85rem;">
                Akses Semua Latihan CPNS, BUMN, Kedinasan & Psikotes di <b>siaptes.id</b>
            </p>
        </div>

        <div style="background: var(--core-navy-subtle); border-left: 4px solid var(--core-navy); border-radius: var(--radius-sm); padding: 0.85rem 1rem; margin-bottom: 1.5rem; font-size: 0.825rem; color: var(--core-navy);">
            <p style="font-weight: 700; margin-bottom: 0.35rem; display: flex; align-items: center; gap: 6px;">
                <span>✨</span> Hak Istimewa Akun Member SIAP TES:
            </p>
            <ul style="padding-left: 1.25rem; margin: 0; line-height: 1.6; color: var(--core-text);">
                <li>Akses instan Paket Coba Gratis (Trial) tanpa dipungut biaya</li>
                <li>Simulasi CAT dengan timer resmi: CPNS, BUMN, Kedinasan & Psikotes</li>
                <li>Penyimpanan riwayat skor, grafik perkembangan, dan evaluasi soal otomatis</li>
            </ul>
        </div>

        <a href="google-login.php" style="display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; padding: 0.75rem 1rem; background: #ffffff; border: 1.5px solid var(--core-border); border-radius: var(--radius-sm); color: var(--core-navy); font-weight: 700; font-size: 0.925rem; text-decoration: none; transition: all 0.2s; box-shadow: var(--shadow-sm);">
            <svg width="20" height="20" viewBox="0 0 24 24">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/>
            </svg>
            Daftar Cepat dengan Akun Google
        </a>

        <div style="display: flex; align-items: center; margin: 1.25rem 0; color: var(--core-text-dim); font-size: 0.78rem;">
            <div style="flex: 1; height: 1px; background: var(--core-border);"></div>
            <span style="padding: 0 0.75rem; text-transform: uppercase; font-weight: 700;">atau isi formulir pendaftaran</span>
            <div style="flex: 1; height: 1px; background: var(--core-border);"></div>
        </div>

        <?php if ($error): ?>
            <div style="background: var(--core-danger-light); color: var(--core-danger); padding: 0.75rem 1rem; border-radius: var(--radius-sm); margin-bottom: 1rem; font-size: 0.85rem; font-weight: 600; border-left: 4px solid var(--core-danger);">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Nama Lengkap (Sesuai KTP / Ijazah)</label>
                <input type="text" name="name" class="form-control" placeholder="Contoh: Siti Rahmawati, S.Pd" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Alamat Email Aktif</label>
                <input type="email" name="email" class="form-control" placeholder="nama@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Kata Sandi Akun (Minimal 6 karakter)</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <div class="form-group">
                <label>Konfirmasi Ulang Kata Sandi</label>
                <input type="password" name="password_confirm" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-gold" style="width: 100%; margin-top: 0.5rem; padding: 0.75rem;">
                Daftar Akun Peserta CAT ➔
            </button>
        </form>

        <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--core-border); text-align: center; font-size: 0.85rem; color: var(--core-text-muted);">
            Sudah memiliki akun? <a href="login.php" style="font-weight: 700; color: var(--core-navy);">Masuk ke Portal</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
