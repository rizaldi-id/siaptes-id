<?php
// google-login.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/google_oauth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Generate state anti-CSRF token
if (empty($_SESSION['oauth_state'])) {
    $_SESSION['oauth_state'] = bin2hex(random_bytes(16));
}

// Jika Google Client ID dan Secret sudah diisi (dan bukan mode info/simulasi), langsung redirect ke Google
if (isGoogleOAuthConfigured() && empty($_GET['sim'])) {
    $authUrl = getGoogleAuthUrl($_SESSION['oauth_state']);
    header('Location: ' . $authUrl);
    exit;
}

// Jika belum dikonfigurasi, tampilkan panduan aktivasi dan mode simulasi uji coba langsung
$page_title = "Daftar / Masuk dengan Google";
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 680px; margin: 2.5rem auto;">
    <div class="card card-navy-top" style="padding: 2.5rem;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: var(--radius-sm); background: var(--core-navy-subtle); margin-bottom: 1rem; border: 1.5px solid var(--core-border);">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/>
                </svg>
            </div>
            <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--core-navy);">Otentikasi Akun Google</h2>
            <p style="color: var(--core-text-muted); font-size: 0.9rem; margin-top: 0.25rem;">
                Integrasi SSO Google Sign-In pada Platform Terpadu <b>SIAP TES</b> (<b>siaptes.id</b>)
            </p>
        </div>

        <!-- Opsi 1: Mode Simulasi Uji Coba (Ready to use) -->
        <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: var(--radius-md); padding: 1.5rem; margin-bottom: 2rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                <span style="font-weight: 700; color: #166534; font-size: 1rem;">⚡ Opsi 1: Mode Uji Coba Cepat (Langsung Dicoba)</span>
                <span class="badge" style="background: #22c55e; color: white; font-size: 0.75rem;">Siap Digunakan</span>
            </div>
            <p style="color: #14532d; font-size: 0.875rem; margin-bottom: 1rem; line-height: 1.5;">
                Anda bisa langsung mencoba alur registrasi & login akun Google menggunakan profil simulasi Google tanpa harus menunggu setup API key:
            </p>

            <form method="POST" action="google-callback.php" style="display: flex; flex-direction: column; gap: 0.75rem;">
                <input type="hidden" name="is_simulation" value="1">
                <input type="hidden" name="state" value="<?= htmlspecialchars($_SESSION['oauth_state']) ?>">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600; color: #166534;">Nama Akun Google:</label>
                        <input type="text" name="sim_name" class="form-control" value="Ahmad Farhan, S.Kom" required style="background: white;">
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600; color: #166534;">Email Akun Google:</label>
                        <input type="email" name="sim_email" class="form-control" value="ahmad.farhan.asn@gmail.com" required style="background: white;">
                    </div>
                </div>

                <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                    <button type="submit" class="btn" style="background: #16a34a; color: white; flex: 1; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                        Simulasikan Masuk dengan Google Sekarang
                    </button>
                </div>
            </form>
        </div>

        <!-- Opsi 2: Konfigurasi Nyata Google Cloud OAuth -->
        <div style="background: #f8fafc; border: 1px solid var(--gray-300); border-radius: var(--radius-md); padding: 1.5rem;">
            <div style="font-weight: 700; color: var(--gray-800); margin-bottom: 0.5rem; font-size: 1rem;">
                🔑 Opsi 2: Hubungkan Google Cloud Console Asli
            </div>
            <p style="color: var(--gray-600); font-size: 0.875rem; margin-bottom: 1rem; line-height: 1.5;">
                Jika Anda ingin menghubungkan akun Google asli secara langsung dari <code>accounts.google.com</code>:
            </p>

            <ol style="font-size: 0.85rem; color: var(--gray-700); padding-left: 1.25rem; line-height: 1.7; margin-bottom: 1.25rem;">
                <li>Buka <a href="https://console.cloud.google.com/apis/credentials" target="_blank" style="font-weight: 600; text-decoration: underline;">Google Cloud Console Credentials</a>.</li>
                <li>Buat <b>OAuth client ID</b> dengan tipe <b>Web application</b>.</li>
                <li>Pada bagian <b>Authorized redirect URIs</b>, tambahkan alamat URL ini:<br>
                    <code style="display: block; background: #e2e8f0; padding: 0.4rem 0.6rem; border-radius: 4px; margin: 0.35rem 0; word-break: break-all; font-weight: 600; color: #0f172a;">
                        <?= htmlspecialchars(getGoogleRedirectUri()) ?>
                    </code>
                </li>
                <li>Buka file <code>config/google_oauth.php</code> dan masukkan <b>Client ID</b> serta <b>Client Secret</b> Anda:
                    <pre style="background: #1e293b; color: #f8fafc; padding: 0.6rem; border-radius: 4px; font-size: 0.8rem; margin-top: 0.4rem;">
define('GOOGLE_CLIENT_ID', 'ID_DARI_GOOGLE_CLOUD');
define('GOOGLE_CLIENT_SECRET', 'SECRET_DARI_GOOGLE_CLOUD');</pre>
                </li>
            </ol>
            
            <p style="font-size: 0.8rem; color: var(--gray-500); margin: 0;">
                Setelah kuncinya dimasukkan, tombol "Daftar dengan Google" akan otomatis membuka form otentikasi resmi Google.
            </p>
        </div>

        <div style="margin-top: 1.5rem; text-align: center;">
            <a href="register.php" style="color: var(--gray-600); font-size: 0.9rem; text-decoration: underline;">Kembali ke Pendaftaran Reguler</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
