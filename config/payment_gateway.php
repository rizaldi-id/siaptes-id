<?php
// config/payment_gateway.php
// ============================================================================
// KONFIGURASI PAYMENT GATEWAY QRIS OTOMATIS - SIAP TES (siaptes.id)
// Harga Paket PRO VIP: Rp 29.000 (Aktivasi Otomatis Real-Time via Mayar.id)
// ============================================================================

// Muat konfigurasi kredensial lokal (opsional, tidak ter-commit ke GitHub jika dibuat)
if (file_exists(__DIR__ . '/payment_local.php')) {
    require_once __DIR__ . '/payment_local.php';
}

// Harga Resmi Paket PRO VIP All-Access (Rp 29.000)
if (!defined('PRO_VIP_BASE_PRICE')) define('PRO_VIP_BASE_PRICE', 29000);
if (!defined('PRO_VIP_STRIKE_PRICE')) define('PRO_VIP_STRIKE_PRICE', 99000);

// ============================================================================
// PILIHAN PROVIDER GATEWAY QRIS OTOMATIS:
// - 'mayar'    : Otomatis via Mayar.id Headless API (QRIS, E-Wallet, VA + Webhook)
// - 'tripay'   : Otomatis via Tripay.co.id API
// - 'midtrans' : Otomatis via Midtrans QRIS / Snap API
// - 'builtin'  : Mode QRIS Dinamis Internal
// ============================================================================
if (!defined('GATEWAY_PROVIDER')) {
    define('GATEWAY_PROVIDER', getenv('GATEWAY_PROVIDER') ?: 'mayar');
}

// --- 1. KONFIGURASI MAYAR.ID (https://web.mayar.id) ---
// Dapatkan API Key (Read & Write) di Dashboard Mayar -> Integrasi -> API Keys
if (!defined('MAYAR_IS_PRODUCTION')) {
    $envMayarProd = getenv('MAYAR_IS_PRODUCTION');
    define('MAYAR_IS_PRODUCTION', ($envMayarProd !== false) ? filter_var($envMayarProd, FILTER_VALIDATE_BOOLEAN) : true);
}
if (!defined('MAYAR_API_KEY')) define('MAYAR_API_KEY', getenv('MAYAR_API_KEY') ?: '');
if (!defined('MAYAR_WEBHOOK_TOKEN')) define('MAYAR_WEBHOOK_TOKEN', getenv('MAYAR_WEBHOOK_TOKEN') ?: 'siaptes_mayar_2026');

// --- 2. KONFIGURASI TRIPAY (Cadangan) ---
if (!defined('TRIPAY_IS_PRODUCTION')) {
    $envProd = getenv('TRIPAY_IS_PRODUCTION');
    define('TRIPAY_IS_PRODUCTION', ($envProd !== false) ? filter_var($envProd, FILTER_VALIDATE_BOOLEAN) : false);
}
if (!defined('TRIPAY_MERCHANT_CODE')) define('TRIPAY_MERCHANT_CODE', getenv('TRIPAY_MERCHANT_CODE') ?: '');
if (!defined('TRIPAY_API_KEY')) define('TRIPAY_API_KEY', getenv('TRIPAY_API_KEY') ?: '');
if (!defined('TRIPAY_PRIVATE_KEY')) define('TRIPAY_PRIVATE_KEY', getenv('TRIPAY_PRIVATE_KEY') ?: '');
if (!defined('TRIPAY_QRIS_CHANNEL')) define('TRIPAY_QRIS_CHANNEL', getenv('TRIPAY_QRIS_CHANNEL') ?: '');

// --- 3. KONFIGURASI MIDTRANS (Cadangan) ---
if (!defined('MIDTRANS_MERCHANT_ID')) define('MIDTRANS_MERCHANT_ID', getenv('MIDTRANS_MERCHANT_ID') ?: '');
if (!defined('MIDTRANS_CLIENT_KEY')) define('MIDTRANS_CLIENT_KEY', getenv('MIDTRANS_CLIENT_KEY') ?: '');
if (!defined('MIDTRANS_SERVER_KEY')) define('MIDTRANS_SERVER_KEY', getenv('MIDTRANS_SERVER_KEY') ?: '');
if (!defined('MIDTRANS_IS_PRODUCTION')) {
    define('MIDTRANS_IS_PRODUCTION', (strpos(MIDTRANS_CLIENT_KEY, 'SB-') !== 0 && strpos(MIDTRANS_SERVER_KEY, 'SB-') !== 0));
}

// --- 4. IDENTITAS MERCHANT QRIS DISPLAY ---
if (!defined('QRIS_MERCHANT_NAME')) define('QRIS_MERCHANT_NAME', 'SIAPTES.ID EDUKASI NASIONAL');
if (!defined('QRIS_NMID')) define('QRIS_NMID', 'ID1026000882910');
if (!defined('CUSTOM_QRIS_IMAGE_URL')) define('CUSTOM_QRIS_IMAGE_URL', '');

// --- 5. KONTAK DUKUNGAN & KONFIRMASI (WhatsApp) ---
if (!defined('ADMIN_WHATSAPP_NUMBER')) define('ADMIN_WHATSAPP_NUMBER', getenv('ADMIN_WHATSAPP_NUMBER') ?: '6281234567890');

/**
 * Cek apakah konfigurasi Mayar API Key sudah diisi
 */
function isMayarConfigured(): bool {
    return (trim(MAYAR_API_KEY) !== '');
}

/**
 * Base URL API Mayar (Production: api.mayar.id, Sandbox: api.mayar.io)
 */
function getMayarBaseUrl(): string {
    return MAYAR_IS_PRODUCTION ? 'https://api.mayar.id/hl/v1' : 'https://api.mayar.io/hl/v1';
}

/**
 * Cek apakah konfigurasi Tripay lengkap
 */
function isTripayConfigured(): bool {
    return (trim(TRIPAY_MERCHANT_CODE) !== '' && trim(TRIPAY_API_KEY) !== '' && trim(TRIPAY_PRIVATE_KEY) !== '');
}

/**
 * Base URL API Tripay
 */
function getTripayBaseUrl(): string {
    return TRIPAY_IS_PRODUCTION ? 'https://tripay.co.id/api' : 'https://tripay.co.id/api-sandbox';
}

/**
 * Cek apakah MIDTRANS_SERVER_KEY valid
 */
function isMidtransServerKeyValid(): bool {
    $key = trim(MIDTRANS_SERVER_KEY);
    return ($key !== '' && (strpos($key, 'Mid-server-') === 0 || strpos($key, 'SB-Mid-server-') === 0));
}

/**
 * Aktivasi Idempoten: Menandai transaksi Lunas ('approved') dan langsung mengaktifkan PRO VIP ('is_premium = 1').
 * Digunakan bersama oleh Webhook Mayar/Tripay, Auto-Polling, dan Verifikasi Manual agar konsisten 100%.
 */
function activateUserProFromTransaction(PDO $db, array $tx, string $notes): bool {
    $now = date('Y-m-d H:i:s');
    try {
        $startedTx = false;
        if (!$db->inTransaction()) {
            $db->beginTransaction();
            $startedTx = true;
        }

        if (($tx['status'] ?? '') !== 'approved') {
            $db->prepare("UPDATE transactions SET status = 'approved', notes = ?, updated_at = ? WHERE id = ?")
               ->execute([$notes, $now, $tx['id']]);
        }

        $db->prepare("UPDATE users SET is_premium = 1 WHERE id = ?")
           ->execute([$tx['user_id']]);

        if ($startedTx) {
            $db->commit();
        }
        return true;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        return false;
    }
}

/**
 * Ambil base URL situs yang sedang berjalan
 */
function getSiteBaseUrl(): string {
    $host = $_SERVER['HTTP_HOST'] ?? 'siaptes.id';
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? 80) == 443);
    $scheme = $isHttps ? 'https' : 'http';
    if (stripos($host, 'siaptes.id') !== false) {
        $scheme = 'https';
    }
    return $scheme . '://' . $host;
}

/**
 * Saluran Pembayaran: QRIS Nasional & E-Wallet via Mayar.id
 */
function getPaymentChannels(): array {
    return [
        'QRIS' => [
            'code' => 'QRIS',
            'group' => 'QRIS Standar Pembayaran Nasional',
            'name' => 'QRIS Instant (Semua Bank & E-Wallet)',
            'subtitle' => 'Scan via GoPay, OVO, DANA, ShopeePay, LinkAja, BCA Mobile, Livin\' Mandiri, BRImo, BNI Mobile',
            'badge' => 'AKTIVASI OTOMATIS 24 JAM',
            'badge_color' => '#16a34a',
            'fee' => 0,
            'icon' => '📱',
            'instructions' => [
                'Klik tombol <b>"Bayar via QRIS Mayar.id"</b> (atau scan QR Code yang tampil di layar menggunakan HP Anda).',
                'Pilih metode pembayaran <b>QRIS</b> (atau GoPay, OVO, DANA, ShopeePay, Virtual Account Bank) pada halaman pembayaran Mayar.',
                'Periksa nominal sebesar <b>Rp 29.000</b> dan selesaikan pembayaran dengan PIN Anda.',
                '<b>Otomatis Aktif:</b> Sistem akan memverifikasi pembayaran dari Mayar secara instan dan langsung mengaktifkan akun <b>PRO VIP</b> Anda.'
            ]
        ]
    ];
}

/**
 * Generate string payload QRIS standar EMVCo fallback (saat API Key belum diisi)
 */
function generateQrisPayload(string $invoiceNumber, int $amount): string {
    $merchant = substr(preg_replace('/[^A-Z0-9 .]/', '', strtoupper(QRIS_MERCHANT_NAME)), 0, 25);
    return "00020101021226590014ID.CO.SIAPTES.WWW011893600914" . QRIS_NMID . "0303UMI52048299530336054" 
        . str_pad((string)strlen((string)$amount), 2, '0', STR_PAD_LEFT) . $amount 
        . "5802ID59" . str_pad((string)strlen($merchant), 2, '0', STR_PAD_LEFT) . $merchant 
        . "6007JAKARTA62" . str_pad((string)(strlen($invoiceNumber) + 4), 2, '0', STR_PAD_LEFT) 
        . "01" . str_pad((string)strlen($invoiceNumber), 2, '0', STR_PAD_LEFT) . $invoiceNumber . "6304A1B2";
}

/**
 * Buat Invoice Pembayaran Otomatis di Mayar.id Headless API (mendukung QRIS, E-Wallet, VA)
 * Mengembalikan array ['id', 'transaction_id', 'link', 'qr_url'] atau ['error' => ...]
 */
function createMayarInvoice(string $invoiceNumber, int $amount, array $user): ?array {
    if (!isMayarConfigured()) {
        return null;
    }

    $baseUrl = getSiteBaseUrl();
    $expiredIso = gmdate('Y-m-d\TH:i:s\Z', time() + (24 * 60 * 60));

    $payload = [
        'name' => mb_substr(trim((string)($user['name'] ?? 'Peserta SIAPTES')), 0, 100),
        'email' => trim((string)($user['email'] ?? 'peserta@siaptes.id')),
        'mobile' => '081234567890',
        'redirectUrl' => $baseUrl . '/payment_checkout.php?invoice=' . urlencode($invoiceNumber),
        'description' => 'Pembayaran Paket PRO VIP All-Access SIAPTES.ID (' . $invoiceNumber . ')',
        'expiredAt' => $expiredIso,
        'items' => [
            [
                'quantity' => 1,
                'rate' => $amount,
                'description' => 'Akses Penuh PRO VIP All-Access SIAPTES.ID'
            ]
        ],
        'extraData' => [
            'noCustomer' => (string)($user['id'] ?? ''),
            'idProd' => 'PRO-VIP-29K',
            'invoice_number' => $invoiceNumber
        ]
    ];

    $url = getMayarBaseUrl() . '/invoice/create';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . MAYAR_API_KEY
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 12
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if (!$res) {
        return ['error' => $err ?: 'Tidak dapat menghubungi server Mayar.id.'];
    }

    $data = json_decode($res, true);
    if (!empty($data['data']['id']) && !empty($data['data']['link'])) {
        return [
            'id' => $data['data']['id'],
            'transaction_id' => $data['data']['transactionId'] ?? '',
            'link' => $data['data']['link'],
            'qr_url' => ''
        ];
    }

    $msg = $data['messages'] ?? ($data['message'] ?? 'Gagal membuat invoice Mayar.id.');
    if (is_array($msg)) $msg = json_encode($msg);
    return ['error' => (string)$msg];
}

/**
 * Cek status invoice Mayar secara langsung via API GET /invoice/{id}
 */
function checkMayarInvoicePaidStatus(string $mayarInvoiceId): bool {
    if (!isMayarConfigured() || trim($mayarInvoiceId) === '') {
        return false;
    }

    $url = getMayarBaseUrl() . '/invoice/' . urlencode($mayarInvoiceId);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . MAYAR_API_KEY
        ],
        CURLOPT_TIMEOUT => 10
    ]);
    $res = curl_exec($ch);
    curl_close($ch);

    if (!$res) {
        return false;
    }

    $data = json_decode($res, true);
    $status = strtolower(trim((string)($data['data']['status'] ?? '')));
    return in_array($status, ['paid', 'settled', 'success', 'completed'], true);
}

/**
 * Ambil daftar channel aktif Tripay (Cadangan)
 */
function getTripayPaymentChannels(): array {
    static $cachedChannels = null;
    if ($cachedChannels !== null) return $cachedChannels;
    if (!isTripayConfigured()) return $cachedChannels = [];

    $ch = curl_init(getTripayBaseUrl() . '/merchant/payment-channel');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . TRIPAY_API_KEY],
        CURLOPT_TIMEOUT => 8
    ]);
    $res = curl_exec($ch);
    curl_close($ch);

    if (!$res) return $cachedChannels = [];
    $data = json_decode($res, true);
    return (!empty($data['success']) && !empty($data['data'])) ? ($cachedChannels = $data['data']) : ($cachedChannels = []);
}

function getTripayActiveQrisMethod(): string {
    if (trim(TRIPAY_QRIS_CHANNEL) !== '') return trim(TRIPAY_QRIS_CHANNEL);
    $channels = getTripayPaymentChannels();
    $activeCodes = [];
    foreach ($channels as $ch) {
        if (!empty($ch['active']) && !empty($ch['code'])) $activeCodes[] = strtoupper($ch['code']);
    }
    foreach (['QRISC', 'QRIS', 'QRIS2', 'QRIS_SHOPEEPAY'] as $code) {
        if (in_array($code, $activeCodes, true)) return $code;
    }
    return !empty($activeCodes) ? $activeCodes[0] : 'QRIS';
}

function createTripayQrisTransaction(string $invoiceNumber, int $amount, array $user, string $method = ''): ?array {
    if (!isTripayConfigured()) return null;

    $selectedMethod = ($method !== '') ? $method : getTripayActiveQrisMethod();
    $baseUrl = getSiteBaseUrl();
    $signature = hash_hmac('sha256', TRIPAY_MERCHANT_CODE . $invoiceNumber . $amount, TRIPAY_PRIVATE_KEY);

    $payload = [
        'method' => $selectedMethod,
        'merchant_ref' => $invoiceNumber,
        'amount' => $amount,
        'customer_name' => mb_substr(trim((string)($user['name'] ?? 'Peserta SIAPTES')), 0, 100),
        'customer_email' => trim((string)($user['email'] ?? 'peserta@siaptes.id')),
        'customer_phone' => '081234567890',
        'order_items' => [['sku' => 'PRO-VIP-29K', 'name' => 'Akses Penuh PRO VIP SIAPTES.ID', 'price' => $amount, 'quantity' => 1]],
        'callback_url' => $baseUrl . '/payment_webhook.php',
        'return_url' => $baseUrl . '/payment_checkout.php?invoice=' . urlencode($invoiceNumber),
        'expired_time' => (time() + (24 * 60 * 60)),
        'signature' => $signature
    ];

    $ch = curl_init(getTripayBaseUrl() . '/transaction/create');
    curl_setopt_array($ch, [
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . TRIPAY_API_KEY],
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_TIMEOUT => 12
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if (!$res) return ['error' => $err ?: 'Tidak dapat menghubungi server Tripay.'];
    $data = json_decode($res, true);
    if (!empty($data['success']) && !empty($data['data'])) {
        $tx = $data['data'];
        return [
            'reference' => $tx['reference'] ?? '',
            'qr_url' => $tx['qr_url'] ?? '',
            'qr_string' => $tx['qr_string'] ?? '',
            'checkout_url' => $tx['checkout_url'] ?? '',
            'pay_code' => $tx['pay_code'] ?? '',
            'method' => $tx['payment_method'] ?? $selectedMethod,
            'status' => $tx['status'] ?? 'UNPAID'
        ];
    }
    return ['error' => $data['message'] ?? 'Gagal membuat transaksi Tripay.'];
}

function createMidtransSnapToken(string $invoiceNumber, int $amount, array $user): ?string {
    if (!isMidtransServerKeyValid()) return null;
    $snapUrl = MIDTRANS_IS_PRODUCTION ? 'https://app.midtrans.com/snap/v1/transactions' : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    $payload = [
        'transaction_details' => ['order_id' => $invoiceNumber, 'gross_amount' => $amount],
        'customer_details' => ['first_name' => $user['name'], 'email' => $user['email']],
        'item_details' => [['id' => 'PRO-VIP-29K', 'price' => $amount, 'quantity' => 1, 'name' => 'Akses Penuh PRO VIP SIAPTES.ID']]
    ];
    $ch = curl_init($snapUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', 'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':')],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 8
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    if (!$res) return null;
    $data = json_decode($res, true);
    $token = $data['token'] ?? null;
    if (!$token) return null;
    $chCheck = curl_init($snapUrl . '/' . $token);
    curl_setopt_array($chCheck, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
    $resCheck = curl_exec($chCheck);
    curl_close($chCheck);
    if ($resCheck) {
        $checkData = json_decode($resCheck, true);
        if (isset($checkData['enabled_payments']) && empty($checkData['enabled_payments'])) return null;
    }
    return $token;
}

function createMidtransQrisTransaction(string $invoiceNumber, int $amount, array $user): ?array {
    if (!isMidtransServerKeyValid()) return null;
    $baseUrl = MIDTRANS_IS_PRODUCTION ? 'https://api.midtrans.com/v2/charge' : 'https://api.sandbox.midtrans.com/v2/charge';
    $payload = [
        'payment_type' => 'qris',
        'transaction_details' => ['order_id' => $invoiceNumber, 'gross_amount' => $amount],
        'customer_details' => ['first_name' => $user['name'], 'email' => $user['email']],
        'item_details' => [['id' => 'PRO-VIP-29K', 'price' => $amount, 'quantity' => 1, 'name' => 'Akses Penuh PRO VIP SIAPTES.ID']]
    ];
    $ch = curl_init($baseUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', 'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':')],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 12
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    if (!$res) return null;
    $data = json_decode($res, true);
    if (!empty($data['status_code']) && in_array($data['status_code'], ['200', '201'])) {
        $qrUrl = '';
        if (!empty($data['actions'])) {
            foreach ($data['actions'] as $act) {
                if ($act['name'] === 'generate-qr-code') { $qrUrl = $act['url']; break; }
            }
        }
        return ['qr_string' => $data['qr_string'] ?? '', 'qr_url' => $qrUrl];
    }
    return null;
}

/**
 * Cek status pembayaran langsung ke Server Mayar / Tripay / Midtrans (Auto-Polling & Manual Check)
 * Mengembalikan true jika pembayaran sudah LUNAS.
 */
function checkGatewayPaymentPaidStatus(string $invoiceNumber, ?string $gatewayRef = null): bool {
    // 1. Cek via Mayar.id Headless API (Prioritas utama)
    if (GATEWAY_PROVIDER === 'mayar' && isMayarConfigured() && !empty($gatewayRef)) {
        if (checkMayarInvoicePaidStatus($gatewayRef)) {
            return true;
        }
    }

    // 2. Cek via Tripay API (jika aktif)
    if (GATEWAY_PROVIDER === 'tripay' && isTripayConfigured()) {
        if (!empty($gatewayRef)) {
            $ch = curl_init(getTripayBaseUrl() . '/transaction/detail?' . http_build_query(['reference' => $gatewayRef]));
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . TRIPAY_API_KEY], CURLOPT_TIMEOUT => 10]);
            $res = curl_exec($ch);
            curl_close($ch);
            if ($res) {
                $data = json_decode($res, true);
                if (!empty($data['success']) && strtoupper($data['data']['status'] ?? '') === 'PAID') return true;
            }
        }

        $chList = curl_init(getTripayBaseUrl() . '/merchant/transactions?' . http_build_query(['merchant_ref' => $invoiceNumber, 'per_page' => 10]));
        curl_setopt_array($chList, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . TRIPAY_API_KEY], CURLOPT_TIMEOUT => 10]);
        $resList = curl_exec($chList);
        curl_close($chList);
        if ($resList) {
            $listData = json_decode($resList, true);
            if (!empty($listData['success']) && !empty($listData['data']) && is_array($listData['data'])) {
                foreach ($listData['data'] as $item) {
                    if (($item['merchant_ref'] ?? '') === $invoiceNumber && strtoupper($item['status'] ?? '') === 'PAID') return true;
                }
            }
        }
    }

    // 3. Cadangan: Cek via Midtrans
    if (GATEWAY_PROVIDER === 'midtrans' && isMidtransServerKeyValid()) {
        $url = (MIDTRANS_IS_PRODUCTION ? 'https://api.midtrans.com/v2/' : 'https://api.sandbox.midtrans.com/v2/') . urlencode($invoiceNumber) . '/status';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':')],
            CURLOPT_TIMEOUT => 10
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        if ($res) {
            $data = json_decode($res, true);
            $txStatus = $data['transaction_status'] ?? '';
            $fraudStatus = $data['fraud_status'] ?? 'accept';
            if ($txStatus === 'settlement' || ($txStatus === 'capture' && $fraudStatus === 'accept')) return true;
        }
    }

    return false;
}
