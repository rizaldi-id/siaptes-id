<?php
// config/payment_local.example.php
// Salin file ini menjadi config/payment_local.php di server cPanel / lokal Anda (JANGAN di-commit ke Git).
// Isi kredensial DOKU dan/atau Midtrans sesuai akun merchant Anda.

// Provider default ketika user belum memilih metode ('doku' atau 'midtrans')
define('GATEWAY_PROVIDER', 'doku');

// --- 1. KREDENSIAL DOKU CHECKOUT (https://dashboard.doku.com) ---
define('DOKU_IS_PRODUCTION', true);
define('DOKU_CLIENT_ID', '');
define('DOKU_SECRET_KEY', '');

// --- 2. KREDENSIAL MIDTRANS SNAP (https://dashboard.midtrans.com) ---
// Untuk Sandbox gunakan key berawalan SB-Mid-client-... / SB-Mid-server-... dan set MIDTRANS_IS_PRODUCTION = false.
// Untuk Production gunakan key Mid-client-... / Mid-server-... dan set MIDTRANS_IS_PRODUCTION = true.
define('MIDTRANS_IS_PRODUCTION', true);
define('MIDTRANS_MERCHANT_ID', '');
define('MIDTRANS_CLIENT_KEY', '');
define('MIDTRANS_SERVER_KEY', '');

// --- 3. WHATSAPP ADMIN ---
define('ADMIN_WHATSAPP_NUMBER', '6281221500930');
