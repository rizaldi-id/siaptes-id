<?php
// google-callback.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/google_oauth.php';

$error = null;
$userData = null;

// Skenario 1: Mode Simulasi Pengujian
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['is_simulation'])) {
    $submittedState = $_POST['state'] ?? '';
    if (empty($_SESSION['oauth_state']) || $submittedState !== $_SESSION['oauth_state']) {
        $error = "Validasi keamanan CSRF token gagal. Silakan coba lagi.";
    } else {
        $simName = trim($_POST['sim_name'] ?? 'Peserta Google');
        $simEmail = trim($_POST['sim_email'] ?? 'peserta.google@gmail.com');
        
        $userData = [
            'google_id' => 'sim_' . md5($simEmail),
            'name'      => $simName,
            'email'     => $simEmail,
            'avatar'    => 'https://ui-avatars.com/api/?name=' . urlencode($simName) . '&background=4285F4&color=fff&size=128'
        ];
    }
}
// Skenario 2: Live Google OAuth 2.0 Callback
elseif (isset($_GET['error'])) {
    $errCode = $_GET['error'];
    $errDesc = $_GET['error_description'] ?? '';
    $error = "Login Google dibatalkan atau ditolak oleh Google (" . htmlspecialchars($errCode) . "). " . htmlspecialchars($errDesc);
}
elseif (isset($_GET['code'])) {
    $code = $_GET['code'];
    $state = $_GET['state'] ?? '';

    if (empty($_SESSION['oauth_state']) || $state !== $_SESSION['oauth_state']) {
        $error = "Validasi state gagal (Kemungkinan sesi kedaluwarsa atau percobaan CSRF). Silakan coba login kembali.";
    } else {
        try {
            $tokenData = exchangeGoogleCodeForToken($code);
            if (!empty($tokenData['access_token'])) {
                $profile = getGoogleUserProfile($tokenData['access_token']);
                
                if (!empty($profile['email'])) {
                    $userData = [
                        'google_id' => $profile['sub'] ?? ('g_' . md5($profile['email'])),
                        'name'      => $profile['name'] ?? explode('@', $profile['email'])[0],
                        'email'     => $profile['email'],
                        'avatar'    => $profile['picture'] ?? null
                    ];
                } else {
                    $error = "Gagal membaca informasi profil dari akun Google.";
                }
            } else {
                $errDetail = $tokenData['error_description'] ?? ($tokenData['error'] ?? 'Gagal memperoleh token.');
                $error = "Autentikasi Google gagal: " . $errDetail;
            }
        } catch (Exception $e) {
            $error = "Kesalahan koneksi ke server Google: " . $e->getMessage();
        }
    }
} else {
    $error = "Permintaan callback Google tidak valid atau parameter tidak lengkap.";
}

// Jika ada error, tampilkan pesan dan arahkan kembali
if ($error || !$userData) {
    $page_title = "Login Google Gagal";
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div style="max-width: 560px; margin: 3rem auto;">
        <div class="card card-navy-top" style="text-align: center; padding: 2.25rem;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
            <h2 style="font-size: 1.35rem; color: #b91c1c; margin-bottom: 0.75rem; font-weight: 800;">Login Google Tidak Berhasil</h2>
            <p style="color: var(--gray-600); font-size: 0.9rem; margin-bottom: 1.25rem;">
                <?= htmlspecialchars($error ?: 'Terjadi kesalahan saat memproses akun Google.') ?>
            </p>
            <div style="background: var(--core-navy-subtle); border: 1px solid var(--core-border); border-radius: var(--radius-sm); padding: 0.85rem; text-align: left; font-size: 0.8rem; color: var(--core-text-muted); margin-bottom: 1.5rem;">
                <b style="color: var(--core-navy);">💡 Catatan Konfigurasi Google Cloud Console:</b><br>
                Pastikan alamat <b>Authorized redirect URI</b> berikut sudah terdaftar di Google Cloud Console Anda:<br>
                <code style="display: block; background: #fff; padding: 0.4rem 0.6rem; border-radius: 4px; margin-top: 0.35rem; font-weight: 700; color: var(--core-navy); word-break: break-all; border: 1px solid var(--core-border);">
                    <?= htmlspecialchars(getGoogleRedirectUri()) ?>
                </code>
            </div>
            <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap;">
                <a href="google-login.php" class="btn btn-primary btn-sm">Coba Login Google Lagi</a>
                <a href="login.php" class="btn btn-outline btn-sm" style="color: var(--core-navy) !important; border-color: var(--core-border);">Login Biasa (Email & Sandi)</a>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Proses sinkronisasi akun Google ke Database (MySQL / SQLite)
$db = getDB();
$googleId = $userData['google_id'];
$email = $userData['email'];
$name = $userData['name'];
$avatar = $userData['avatar'];

// 1. Cari apakah akun sudah pernah terdaftar (berdasarkan email atau google_id)
$stmt = $db->prepare("SELECT * FROM users WHERE google_id = ? OR email = ?");
$stmt->execute([$googleId, $email]);
$user = $stmt->fetch();

if ($user) {
    // Akun sudah ada -> Update google_id & avatar bila belum ada
    $updateStmt = $db->prepare("
        UPDATE users 
        SET google_id = COALESCE(google_id, ?), 
            avatar = COALESCE(avatar, ?)
        WHERE id = ?
    ");
    $updateStmt->execute([$googleId, $avatar, $user['id']]);

    // Set Session Login
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_avatar'] = $avatar ?: ($user['avatar'] ?? null);

    header('Location: index.php?msg=Selamat datang kembali, ' . urlencode($user['name']) . '! Berhasil masuk via Google.');
    exit;
} else {
    // Akun baru -> Buat akun otomatis (Role: user, Free trial)
    // Beri random hashed password agar akun tetap aman jika diakses via form password
    $randomPass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

    $insertStmt = $db->prepare("
        INSERT INTO users (name, email, password, role, is_premium, google_id, avatar)
        VALUES (?, ?, ?, 'user', 0, ?, ?)
    ");
    $insertStmt->execute([$name, $email, $randomPass, $googleId, $avatar]);
    $newUserId = $db->lastInsertId();

    // Set Session Login
    $_SESSION['user_id'] = $newUserId;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = 'user';
    $_SESSION['user_avatar'] = $avatar;

    header('Location: index.php?msg=Pendaftaran berhasil dengan Akun Google! Selamat datang di SIAP TES (siaptes.id).');
    exit;
}
