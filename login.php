<?php
// login.php - Halaman Masuk & Daftar Akun Baru Terpadu (Tersimpan Otomatis ke Database)
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$activeTab = (isset($_GET['tab']) && $_GET['tab'] === 'register') ? 'register' : 'login';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $formAction = $_POST['form_action'] ?? 'login';
    $db = getDB();

    if ($formAction === 'register') {
        $activeTab = 'register';
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            $error = 'Harap lengkapi Nama Lengkap, Email, dan Kata Sandi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format alamat email tidak valid.';
        } elseif (strlen($password) < 6) {
            $error = 'Kata sandi minimal harus 6 karakter.';
        } elseif ($password !== $password_confirm) {
            $error = 'Konfirmasi kata sandi tidak cocok.';
        } else {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $existingUser = $stmt->fetch();

            if ($existingUser) {
                if (!empty($existingUser['google_id'])) {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $upd = $db->prepare("UPDATE users SET name = ?, password = ? WHERE id = ?");
                    $upd->execute([$name, $hashed, $existingUser['id']]);

                    $_SESSION['user_id'] = $existingUser['id'];
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $existingUser['email'];
                    $_SESSION['user_role'] = $existingUser['role'];
                    $_SESSION['user_avatar'] = $existingUser['avatar'] ?? null;

                    header('Location: index.php?msg=' . urlencode('Kata sandi berhasil disimpan ke database! Selamat datang, ' . $name . '.'));
                    exit;
                } else {
                    $error = 'Email ini sudah terdaftar di database. Silakan masuk melalui tab Masuk Akun.';
                }
            } else {
                // Simpan akun baru ke tabel users di database dengan status Trial (is_premium = 0)
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $now = date('Y-m-d H:i:s');
                $ins = $db->prepare("
                    INSERT INTO users (name, email, password, role, is_premium, created_at)
                    VALUES (?, ?, ?, 'user', 0, ?)
                ");
                $ins->execute([$name, $email, $hashed, $now]);
                $newUserId = $db->lastInsertId();

                // Langsung login setelah akun tersimpan di database
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'user';
                $_SESSION['user_avatar'] = null;

                header('Location: index.php?msg=' . urlencode('Akun baru berhasil dibuat & tersimpan! Anda dapat mencoba Trial Gratis atau Upgrade PRO VIP (Rp 29.000 via QRIS).'));
                exit;
            }
        }
    } else {
        // PROSES LOGIN AKUN
        $activeTab = 'login';
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (!empty($email) && !empty($password)) {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_avatar'] = $user['avatar'] ?? null;

                if ($user['role'] === 'admin') {
                    header('Location: admin/index.php');
                } else {
                    header('Location: index.php?msg=' . urlencode('Selamat datang kembali, ' . $user['name'] . '!'));
                }
                exit;
            } elseif ($user && !empty($user['google_id'])) {
                $error = 'Email ini terdaftar via Google. Klik tombol "Masuk dengan Akun Google" di atas, atau gunakan tab "Buat Akun Baru" dengan email yang sama untuk membuat kata sandi.';
            } else {
                $error = 'Email atau kata sandi tidak ditemukan. Belum punya akun? Klik tab "Buat Akun Baru" di atas.';
            }
        } else {
            $error = 'Harap isi Email dan Kata Sandi.';
        }
    }
}

$page_title = "Masuk & Buat Akun Baru - SIAP TES";
require_once __DIR__ . '/includes/header.php';
?>

<style>
.auth-tabs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    background: #f1f5f9;
    padding: 4px;
    border-radius: var(--radius-sm);
    margin-bottom: 1.5rem;
    border: 1px solid var(--core-border);
}
.auth-tab-btn {
    padding: 0.65rem 0.75rem;
    text-align: center;
    font-weight: 800;
    font-size: 0.88rem;
    border-radius: 6px;
    cursor: pointer;
    border: none;
    background: transparent;
    color: var(--core-text-muted);
    transition: all 0.2s;
    font-family: inherit;
}
.auth-tab-btn.active {
    background: var(--core-navy);
    color: #ffffff;
    box-shadow: 0 2px 6px rgba(11, 45, 82, 0.2);
}
</style>

<div style="max-width: 500px; margin: 2.5rem auto;">
    <div class="card card-navy-top" style="padding: 2.25rem;">
        <div style="text-align: center; margin-bottom: 1.35rem;">
            <div style="display: inline-flex; align-items: center; justify-content: center; width: 52px; height: 52px; border-radius: var(--radius-sm); background: linear-gradient(135deg, var(--core-navy) 0%, #073b64 100%); color: var(--core-gold); margin-bottom: 0.75rem; box-shadow: 0 4px 10px rgba(0, 43, 73, 0.25);">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            <h2 style="font-size: 1.45rem; font-weight: 800; color: var(--core-navy); margin-bottom: 0.25rem;">Portal Akun SIAP TES</h2>
            <p style="color: var(--core-text-muted); font-size: 0.84rem;">
                Masuk atau Buat Akun Baru untuk Simulasi CPNS, BUMN, Kedinasan & Psikotes
            </p>
        </div>

        <!-- Tab Switcher: Masuk Akun vs Buat Akun Baru -->
        <div class="auth-tabs">
            <button type="button" id="tabBtnLogin" class="auth-tab-btn <?= $activeTab === 'login' ? 'active' : '' ?>" onclick="switchAuthTab('login')">
                🔑 Masuk Akun
            </button>
            <button type="button" id="tabBtnRegister" class="auth-tab-btn <?= $activeTab === 'register' ? 'active' : '' ?>" onclick="switchAuthTab('register')">
                ✨ Buat Akun Baru
            </button>
        </div>

        <!-- Tombol Login / Daftar Cepat via Google -->
        <a href="google-login.php" style="display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; padding: 0.75rem 1rem; background: #ffffff; border: 1.5px solid var(--core-border); border-radius: var(--radius-sm); color: var(--core-navy); font-weight: 700; font-size: 0.92rem; text-decoration: none; transition: all 0.2s; box-shadow: var(--shadow-sm);">
            <svg width="20" height="20" viewBox="0 0 24 24">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/>
            </svg>
            <span id="googleBtnText"><?= $activeTab === 'register' ? 'Daftar Cepat dengan Akun Google' : 'Masuk dengan Akun Google' ?></span>
        </a>

        <div style="display: flex; align-items: center; margin: 1.2rem 0; color: var(--core-text-dim); font-size: 0.76rem;">
            <div style="flex: 1; height: 1px; background: var(--core-border);"></div>
            <span style="padding: 0 0.75rem; text-transform: uppercase; font-weight: 700;">atau gunakan email & kata sandi</span>
            <div style="flex: 1; height: 1px; background: var(--core-border);"></div>
        </div>

        <?php if ($error): ?>
            <div style="background: var(--core-danger-light); color: var(--core-danger); padding: 0.75rem 1rem; border-radius: var(--radius-sm); margin-bottom: 1rem; font-size: 0.85rem; font-weight: 600; border-left: 4px solid var(--core-danger);">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- FORM 1: MASUK AKUN -->
        <div id="panelLogin" style="display: <?= $activeTab === 'login' ? 'block' : 'none' ?>;">
            <form method="POST" action="login.php">
                <input type="hidden" name="form_action" value="login">
                <div class="form-group">
                    <label>Alamat Email Terdaftar</label>
                    <input type="email" name="email" class="form-control" placeholder="nama@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Kata Sandi Akun</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem; padding: 0.8rem;">
                    Masuk ke Portal CAT ➔
                </button>
            </form>

            <div style="margin-top: 1.35rem; padding-top: 1.15rem; border-top: 1px solid var(--core-border); text-align: center; font-size: 0.85rem; color: var(--core-text-muted);">
                Belum punya akun? <a href="javascript:void(0)" onclick="switchAuthTab('register')" style="font-weight: 800; color: var(--core-navy);">Klik di Sini untuk Buat Akun Baru</a>
            </div>
        </div>

        <!-- FORM 2: BUAT AKUN BARU (LANGSUNG TERSIMPAN KE DATABASE) -->
        <div id="panelRegister" style="display: <?= $activeTab === 'register' ? 'block' : 'none' ?>;">
            <form method="POST" action="login.php?tab=register">
                <input type="hidden" name="form_action" value="register">
                <div class="form-group">
                    <label>Nama Lengkap Peserta *</label>
                    <input type="text" name="name" class="form-control" placeholder="Contoh: Budi Santoso" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Alamat Email Aktif *</label>
                    <input type="email" name="email" class="form-control" placeholder="nama@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Buat Kata Sandi (Minimal 6 karakter) *</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>

                <div class="form-group">
                    <label>Ulangi Kata Sandi *</label>
                    <input type="password" name="password_confirm" class="form-control" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-gold" style="width: 100%; margin-top: 0.5rem; padding: 0.8rem;">
                    ✨ Buat Akun Baru Sekarang ➔
                </button>
            </form>

            <div style="margin-top: 1.35rem; padding-top: 1.15rem; border-top: 1px solid var(--core-border); text-align: center; font-size: 0.85rem; color: var(--core-text-muted);">
                Sudah memiliki akun? <a href="javascript:void(0)" onclick="switchAuthTab('login')" style="font-weight: 800; color: var(--core-navy);">Masuk dengan Akun Anda</a>
            </div>
        </div>
    </div>

    <!-- Info Demo Login Coretax Style -->
    <div style="background: #ffffff; border: 1px solid var(--core-border); padding: 1rem 1.25rem; border-radius: var(--radius-sm); font-size: 0.825rem; color: var(--core-text-muted); box-shadow: var(--shadow-sm);">
        <p style="font-weight: 700; color: var(--core-navy); margin-bottom: 0.35rem; display: flex; align-items: center; gap: 6px;">
            <span style="color: var(--core-gold);">●</span> Akun Demo Tersedia:
        </p>
        <p style="margin-bottom: 0.2rem;">&bull; <b>Administrator:</b> <code>admin@cpns.go.id</code> / Sandi: <code>admin123</code></p>
        <p style="margin: 0;">&bull; <b>Peserta PRO VIP:</b> <code>peserta@cpns.go.id</code> / Sandi: <code>peserta123</code></p>
    </div>
</div>

<script>
function switchAuthTab(tab) {
    const isReg = (tab === 'register');
    document.getElementById('panelLogin').style.display = isReg ? 'none' : 'block';
    document.getElementById('panelRegister').style.display = isReg ? 'block' : 'none';
    document.getElementById('tabBtnLogin').classList.toggle('active', !isReg);
    document.getElementById('tabBtnRegister').classList.toggle('active', isReg);
    document.getElementById('googleBtnText').innerText = isReg ? 'Daftar Cepat dengan Akun Google' : 'Masuk dengan Akun Google';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
