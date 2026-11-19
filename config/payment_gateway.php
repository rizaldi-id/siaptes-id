<?php
// config/payment_gateway.php
// ============================================================================
// KONFIGURASI PAYMENT GATEWAY OTOMATIS - SIAP TES (siaptes.id)
// Provider Utama: DOKU Checkout (QRIS, Virtual Account, E-Wallet, Retail)
// Harga Paket PRO VIP: Rp 29.000 (Aktivasi Otomatis Real-Time)
// ============================================================================

// Muat konfigurasi kredensial lokal (opsional, tidak ter-commit ke GitHub jika dibuat)
if (file_exists(__DIR__ . '/payment_local.php')) {
    require_once __DIR__ . '/payment_local.php';
}

// Harga Resmi Paket PRO VIP All-Access (Rp 29.000)
if (!defined('PRO_VIP_BASE_PRICE')) define('PRO_VIP_BASE_PRICE', 29000);
if (!defined('PRO_VIP_STRIKE_PRICE')) define('PRO_VIP_STRIKE_PRICE', 99000);

// ============================================================================
// PILIHAN PROVIDER GATEWAY OTOMATIS:
// - 'doku'     : Otomatis via DOKU Checkout API (QRIS, VA Bank, E-Wallet, Retail)
// - 'mayar'    : Cadangan Mayar.id Headless API
// - 'tripay'   : Cadangan Tripay.co.id API
// - 'midtrans' : Cadangan Midtrans QRIS / Snap API
// - 'builtin'  : Mode QRIS Dinamis Internal
// ============================================================================
if (!defined('GATEWAY_PROVIDER')) {
    define('GATEWAY_PROVIDER', getenv('GATEWAY_PROVIDER') ?: 'doku');
}

// --- 1. KONFIGURASI DOKU CHECKOUT (https://dashboard.doku.com) ---
// Dapatkan Client ID & Secret Key di Dashboard DOKU -> Integration / Settings -> API Keys
if (!defined('DOKU_IS_PRODUCTION')) {
    $envDokuProd = getenv('DOKU_IS_PRODUCTION');
    define('DOKU_IS_PRODUCTION', ($envDokuProd !== false) ? filter_var($envDokuProd, FILTER_VALIDATE_BOOLEAN) : true);
}
if (!defined('DOKU_CLIENT_ID')) define('DOKU_CLIENT_ID', getenv('DOKU_CLIENT_ID') ?: '');
if (!defined('DOKU_SECRET_KEY')) define('DOKU_SECRET_KEY', getenv('DOKU_SECRET_KEY') ?: '');

// --- 2. KONFIGURASI MAYAR.ID (Cadangan) ---
if (!defined('MAYAR_IS_PRODUCTION')) {
    $envMayarProd = getenv('MAYAR_IS_PRODUCTION');
    define('MAYAR_IS_PRODUCTION', ($envMayarProd !== false) ? filter_var($envMayarProd, FILTER_VALIDATE_BOOLEAN) : true);
}
if (!defined('MAYAR_API_KEY')) define('MAYAR_API_KEY', getenv('MAYAR_API_KEY') ?: '');
if (!defined('MAYAR_WEBHOOK_TOKEN')) define('MAYAR_WEBHOOK_TOKEN', getenv('MAYAR_WEBHOOK_TOKEN') ?: 'siaptes_mayar_2026');

// --- 3. KONFIGURASI TRIPAY (Cadangan) ---
if (!defined('TRIPAY_IS_PRODUCTION')) {
    $envProd = getenv('TRIPAY_IS_PRODUCTION');
    define('TRIPAY_IS_PRODUCTION', ($envProd !== false) ? filter_var($envProd, FILTER_VALIDATE_BOOLEAN) : false);
}
if (!defined('TRIPAY_MERCHANT_CODE')) define('TRIPAY_MERCHANT_CODE', getenv('TRIPAY_MERCHANT_CODE') ?: '');
if (!defined('TRIPAY_API_KEY')) define('TRIPAY_API_KEY', getenv('TRIPAY_API_KEY') ?: '');
if (!defined('TRIPAY_PRIVATE_KEY')) define('TRIPAY_PRIVATE_KEY', getenv('TRIPAY_PRIVATE_KEY') ?: '');
if (!defined('TRIPAY_QRIS_CHANNEL')) define('TRIPAY_QRIS_CHANNEL', getenv('TRIPAY_QRIS_CHANNEL') ?: '');

// --- 4. KONFIGURASI MIDTRANS (Cadangan) ---
if (!defined('MIDTRANS_MERCHANT_ID')) define('MIDTRANS_MERCHANT_ID', getenv('MIDTRANS_MERCHANT_ID') ?: '');
if (!defined('MIDTRANS_CLIENT_KEY')) define('MIDTRANS_CLIENT_KEY', getenv('MIDTRANS_CLIENT_KEY') ?: '');
if (!defined('MIDTRANS_SERVER_KEY')) define('MIDTRANS_SERVER_KEY', getenv('MIDTRANS_SERVER_KEY') ?: '');
if (!defined('MIDTRANS_IS_PRODUCTION')) {
    define('MIDTRANS_IS_PRODUCTION', (strpos(MIDTRANS_CLIENT_KEY, 'SB-') !== 0 && strpos(MIDTRANS_SERVER_KEY, 'SB-') !== 0));
}

// --- 5. IDENTITAS MERCHANT QRIS DISPLAY ---
if (!defined('QRIS_MERCHANT_NAME')) define('QRIS_MERCHANT_NAME', 'SIAPTES.ID EDUKASI NASIONAL');
if (!defined('QRIS_NMID')) define('QRIS_NMID', 'ID1026000882910');
if (!defined('CUSTOM_QRIS_IMAGE_URL')) define('CUSTOM_QRIS_IMAGE_URL', '');

// --- 6. KONTAK DUKUNGAN & KONFIRMASI (WhatsApp) ---
if (!defined('ADMIN_WHATSAPP_NUMBER')) define('ADMIN_WHATSAPP_NUMBER', getenv('ADMIN_WHATSAPP_NUMBER') ?: '6281221500930');

/**
 * Cek apakah konfigurasi DOKU sudah lengkap (Client ID & Secret Key)
 */
function isDokuConfigured(): bool {
    return (trim(DOKU_CLIENT_ID) !== '' && trim(DOKU_SECRET_KEY) !== '');
}

/**
 * Base URL API DOKU (Production: api.doku.com, Sandbox: api-sandbox.doku.com)
 */
function getDokuBaseUrl(): string {
    return DOKU_IS_PRODUCTION ? 'https://api.doku.com' : 'https://api-sandbox.doku.com';
}

/**
 * URL Script Popup JS DOKU Checkout
 */
function getDokuCheckoutJsUrl(): string {
    return DOKU_IS_PRODUCTION
        ? 'https://jokul.doku.com/jokul-checkout-js/v1/jokul-checkout-1.0.0.js'
        : 'https://sandbox.doku.com/jokul-checkout-js/v1/jokul-checkout-1.0.0.js';
}

/**
 * Generate SHA-256 Base64 Digest untuk DOKU API
 */
function generateDokuDigest(string $rawBody): string {
    return base64_encode(hash('sha256', $rawBody, true));
}

/**
 * Generate HMAC-SHA256 Signature Header sesuai standar resmi DOKU Non-SNAP
 */
function generateDokuSignature(
    string $clientId,
    string $requestId,
    string $requestTimestamp,
    string $requestTarget,
    ?string $digest,
    string $secretKey
): string {
    $component = "Client-Id:" . $clientId . "\n"
               . "Request-Id:" . $requestId . "\n"
               . "Request-Timestamp:" . $requestTimestamp . "\n"
               . "Request-Target:" . $requestTarget;

    if ($digest !== null && $digest !== '') {
        $component .= "\nDigest:" . $digest;
    }

    $rawHmac = hash_hmac('sha256', $component, $secretKey, true);
    return "HMACSHA256=" . base64_encode($rawHmac);
}

/**
 * Cek apakah konfigurasi Mayar API Key sudah diisi
 */
function isMayarConfigured(): bool {
    return (trim(MAYAR_API_KEY) !== '');
}

function getMayarBaseUrl(): string {
    return MAYAR_IS_PRODUCTION ? 'https://api.mayar.id/hl/v1' : 'https://api.mayar.io/hl/v1';
}

function isTripayConfigured(): bool {
    return (trim(TRIPAY_MERCHANT_CODE) !== '' && trim(TRIPAY_API_KEY) !== '' && trim(TRIPAY_PRIVATE_KEY) !== '');
}

function getTripayBaseUrl(): string {
    return TRIPAY_IS_PRODUCTION ? 'https://tripay.co.id/api' : 'https://tripay.co.id/api-sandbox';
}

function isMidtransServerKeyValid(): bool {
    $key = trim(MIDTRANS_SERVER_KEY);
    return ($key !== '' && (strpos($key, 'Mid-server-') === 0 || strpos($key, 'SB-Mid-server-') === 0));
}

/**
 * Aktivasi Idempoten: Menandai transaksi Lunas ('approved') dan langsung mengaktifkan PRO VIP ('is_premium = 1').
 * Digunakan bersama oleh Webhook DOKU, Auto-Polling, dan Verifikasi Manual agar konsisten 100%.
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
 * Cek apakah tagihan pending sudah kedaluwarsa (default batas aman 23 jam sebelum limit 24 jam DOKU)
 */
function isTransactionLinkExpired(array $tx, int $ttlSeconds = 82800): bool {
    if (empty($tx['created_at'])) {
        return false;
    }
    $createdTs = strtotime((string)$tx['created_at']);
    if ($createdTs === false) {
        return false;
    }
    return (time() - $createdTs) >= $ttlSeconds;
}

/**
 * Hitung sisa waktu nyata (detik) menuju batas 24 jam pembayaran
 */
function getTransactionRemainingSeconds(array $tx, int $ttlSeconds = 86400): int {
    if (empty($tx['created_at'])) {
        return $ttlSeconds;
    }
    $createdTs = strtotime((string)$tx['created_at']);
    if ($createdTs === false) {
        return $ttlSeconds;
    }
    return max(0, ($createdTs + $ttlSeconds) - time());
}

/**
 * Cek apakah status order DOKU menandakan transaksi gagal / kedaluwarsa
 */
function isDokuTerminalFailureStatus(string $status): bool {
    return in_array(strtoupper(trim($status)), ['EXPIRED', 'FAILED', 'CANCELLED', 'VOIDED'], true);
}

/**
 * Batalkan tagihan pending lama milik user dan buat invoice DOKU baru yang masih fresh
 */
function createFreshProVipTransaction(
    PDO $db,
    int $userId,
    ?int $packageId = null,
    int $amount = PRO_VIP_BASE_PRICE,
    string $cancelNote = 'Digantikan tagihan pembayaran baru (kedaluwarsa)'
): string {
    $now = date('Y-m-d H:i:s');
    $db->prepare("UPDATE transactions SET status = 'rejected', notes = ?, updated_at = ? WHERE user_id = ? AND status = 'pending'")
       ->execute([$cancelNote, $now, $userId]);

    try {
        $randSuffix = strtoupper(bin2hex(random_bytes(3)));
    } catch (Throwable $e) {
        $randSuffix = strtoupper(substr(uniqid('', true), -6));
    }

    $invNumber = 'INV-DOKU-' . date('Ymd') . '-' . $randSuffix;
    $paymentMethod = 'DOKU Payment Gateway';
    $notes = 'Aktivasi PRO VIP All-Access via DOKU (VA / E-Wallet / QRIS / Retail)';

    $stmt = $db->prepare("
        INSERT INTO transactions (invoice_number, user_id, package_id, type, amount, payment_method, status, notes, created_at, updated_at)
        VALUES (?, ?, ?, 'lifetime_premium', ?, ?, 'pending', ?, ?, ?)
    ");
    $stmt->execute([$invNumber, $userId, $packageId, $amount, $paymentMethod, $notes, $now, $now]);

    return $invNumber;
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
 * Saluran Pembayaran: DOKU Checkout (QRIS, Virtual Account Bank, E-Wallet, Retail)
 */
function getPaymentChannels(): array {
    return [
        'QRIS' => [
            'code' => 'QRIS',
            'group' => 'DOKU Payment Gateway (QRIS, VA Bank, E-Wallet)',
            'name' => 'Pembayaran Instan DOKU (VA, E-Wallet & QRIS)',
            'subtitle' => 'Mendukung Virtual Account (BNI, BRI, BSI, CIMB, BNC, BTN), DOKU e-Wallet, Indomaret, Alfamart & QRIS',
            'badge' => 'AKTIVASI OTOMATIS 24 JAM',
            'badge_color' => '#16a34a',
            'fee' => 0,
            'icon' => '💳',
            'instructions' => [
                'Klik tombol <b>"Bayar Sekarang via DOKU"</b> (atau scan QR Code yang tampil di layar).',
                'Pilih metode pembayaran yang Anda inginkan di halaman DOKU: <b>QRIS</b>, <b>Virtual Account (BRI, BNI, BSI, CIMB, BNC, BTN)</b>, <b>DOKU e-Wallet</b>, atau <b>Alfamart / Indomaret</b>.',
                'Selesaikan pembayaran tepat sebesar <b>Rp 29.000</b>.',
                '<b>Otomatis Aktif:</b> Segera setelah transaksi berstatus sukses di DOKU, akun Anda akan <b>otomatis aktif menjadi PRO VIP</b> tanpa perlu konfirmasi manual.'
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
 * Buat Sesi Pembayaran DOKU Checkout API (POST /checkout/v1/payment)
 * Mengembalikan ['payment_url', 'token_id', 'session_id'] atau ['error' => ...]
 */
function createDokuCheckoutPayment(string $invoiceNumber, int $amount, array $user): ?array {
    if (!isDokuConfigured()) {
        return null;
    }

    $requestTarget = '/checkout/v1/payment';
    $requestId = 'req-' . preg_replace('/[^A-Za-z0-9]/', '', $invoiceNumber) . '-' . substr(uniqid(), -6);
    $requestTimestamp = gmdate('Y-m-d\TH:i:s\Z');
    $baseUrl = getSiteBaseUrl();
    $returnUrl = $baseUrl . '/payment_checkout.php?invoice=' . urlencode($invoiceNumber);

    $payload = [
        'order' => [
            'amount' => $amount,
            'invoice_number' => $invoiceNumber,
            'currency' => 'IDR',
            'callback_url' => $returnUrl,
            'callback_url_cancel' => $returnUrl,
            'auto_redirect' => true,
            'line_items' => [
                [
                    'id' => 'PRO-VIP-29K',
                    'name' => 'Akses Penuh PRO VIP All-Access SIAPTES.ID',
                    'quantity' => 1,
                    'price' => $amount
                ]
            ]
        ],
        'payment' => [
            'payment_due_date' => 1440 // 24 jam
        ],
        'customer' => [
            'id' => 'USR-' . ($user['id'] ?? '0'),
            'name' => mb_substr(trim((string)($user['name'] ?? 'Peserta SIAPTES')), 0, 100),
            'email' => trim((string)($user['email'] ?? 'peserta@siaptes.id')),
            'phone' => '081234567890',
            'country' => 'ID'
        ]
    ];

    $jsonBody = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $digest = generateDokuDigest($jsonBody);
    $signature = generateDokuSignature(
        DOKU_CLIENT_ID,
        $requestId,
        $requestTimestamp,
        $requestTarget,
        $digest,
        DOKU_SECRET_KEY
    );

    $url = getDokuBaseUrl() . $requestTarget;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Client-Id: ' . DOKU_CLIENT_ID,
            'Request-Id: ' . $requestId,
            'Request-Timestamp: ' . $requestTimestamp,
            'Signature: ' . $signature
        ],
        CURLOPT_POSTFIELDS => $jsonBody,
        CURLOPT_TIMEOUT => 15
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);

    if (!$res) {
        return ['error' => $err ?: 'Tidak dapat menghubungi server DOKU.'];
    }

    $data = json_decode($res, true);
    $paymentUrl = $data['response']['payment']['url'] ?? ($data['payment']['url'] ?? '');
    $tokenId = $data['response']['payment']['token_id'] ?? ($data['payment']['token_id'] ?? '');
    $sessionId = $data['response']['order']['session_id'] ?? ($data['order']['session_id'] ?? '');

    if ($paymentUrl !== '') {
        return [
            'payment_url' => $paymentUrl,
            'token_id' => $tokenId,
            'session_id' => $sessionId ?: $requestId
        ];
    }

    $errMsg = '';
    if (!empty($data['error']['message'])) {
        $errMsg = is_array($data['error']['message']) ? implode(', ', $data['error']['message']) : $data['error']['message'];
    } elseif (!empty($data['message'])) {
        $errMsg = is_array($data['message']) ? implode(', ', $data['message']) : $data['message'];
    }
    return ['error' => $errMsg ?: 'Gagal membuat sesi pembayaran DOKU.'];
}

/**
 * Cek Status Transaksi DOKU Secara Langsung (GET /orders/v1/status/{invoice_number})
 * Mengembalikan array ['paid' => bool, 'channel' => string, 'status' => string]
 */
function checkDokuOrderStatus(string $invoiceNumber): array {
    if (!isDokuConfigured() || trim($invoiceNumber) === '') {
        return ['paid' => false, 'channel' => '', 'status' => 'UNKNOWN'];
    }

    $requestTarget = '/orders/v1/status/' . rawurlencode($invoiceNumber);
    $requestId = 'status-' . preg_replace('/[^A-Za-z0-9]/', '', $invoiceNumber) . '-' . substr(uniqid(), -6);
    $requestTimestamp = gmdate('Y-m-d\TH:i:s\Z');

    // GET request tidak memakai Digest
    $signature = generateDokuSignature(
        DOKU_CLIENT_ID,
        $requestId,
        $requestTimestamp,
        $requestTarget,
        null,
        DOKU_SECRET_KEY
    );

    $url = getDokuBaseUrl() . $requestTarget;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Client-Id: ' . DOKU_CLIENT_ID,
            'Request-Id: ' . $requestId,
            'Request-Timestamp: ' . $requestTimestamp,
            'Signature: ' . $signature
        ],
        CURLOPT_TIMEOUT => 10
    ]);
    $res = curl_exec($ch);

    if (!$res) {
        return ['paid' => false, 'channel' => '', 'status' => 'ERROR'];
    }

    $data = json_decode($res, true);
    $txStatus = strtoupper(trim((string)($data['transaction']['status'] ?? '')));
    $channel = (string)($data['channel']['id'] ?? ($data['acquirer']['name'] ?? 'DOKU'));

    return [
        'paid' => ($txStatus === 'SUCCESS'),
        'channel' => $channel,
        'status' => $txStatus
    ];
}

// ============================================================================
// FUNGSI CADANGAN GATEWAY LAIN (Mayar / Tripay / Midtrans)
// ============================================================================

function createMayarInvoice(string $invoiceNumber, int $amount, array $user): ?array {
    if (!isMayarConfigured()) return null;
    $baseUrl = getSiteBaseUrl();
    $payload = [
        'name' => mb_substr(trim((string)($user['name'] ?? 'Peserta SIAPTES')), 0, 100),
        'email' => trim((string)($user['email'] ?? 'peserta@siaptes.id')),
        'mobile' => '081234567890',
        'redirectUrl' => $baseUrl . '/payment_checkout.php?invoice=' . urlencode($invoiceNumber),
        'description' => 'Pembayaran Paket PRO VIP All-Access SIAPTES.ID (' . $invoiceNumber . ')',
        'expiredAt' => gmdate('Y-m-d\TH:i:s\Z', time() + 86400),
        'items' => [['quantity' => 1, 'rate' => $amount, 'description' => 'Akses Penuh PRO VIP All-Access SIAPTES.ID']],
        'extraData' => ['noCustomer' => (string)($user['id'] ?? ''), 'idProd' => 'PRO-VIP-29K', 'invoice_number' => $invoiceNumber]
    ];
    $ch = curl_init(getMayarBaseUrl() . '/invoice/create');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', 'Authorization: Bearer ' . MAYAR_API_KEY],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 12
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    if (!$res) return ['error' => $err ?: 'Tidak dapat menghubungi server Mayar.id.'];
    $data = json_decode($res, true);
    if (!empty($data['data']['id']) && !empty($data['data']['link'])) {
        return ['id' => $data['data']['id'], 'transaction_id' => $data['data']['transactionId'] ?? '', 'link' => $data['data']['link'], 'qr_url' => ''];
    }
    return ['error' => 'Gagal membuat invoice Mayar.id.'];
}

function checkMayarInvoicePaidStatus(string $mayarInvoiceId): bool {
    if (!isMayarConfigured() || trim($mayarInvoiceId) === '') return false;
    $ch = curl_init(getMayarBaseUrl() . '/invoice/' . urlencode($mayarInvoiceId));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Accept: application/json', 'Authorization: Bearer ' . MAYAR_API_KEY], CURLOPT_TIMEOUT => 10]);
    $res = curl_exec($ch);
    if (!$res) return false;
    $data = json_decode($res, true);
    $status = strtolower(trim((string)($data['data']['status'] ?? '')));
    return in_array($status, ['paid', 'settled', 'success', 'completed'], true);
}

function createTripayQrisTransaction(string $invoiceNumber, int $amount, array $user, string $method = 'QRIS'): ?array {
    if (!isTripayConfigured()) return null;
    $baseUrl = getSiteBaseUrl();
    $payload = [
        'method' => $method,
        'merchant_ref' => $invoiceNumber,
        'amount' => $amount,
        'customer_name' => mb_substr(trim((string)($user['name'] ?? 'Peserta SIAPTES')), 0, 100),
        'customer_email' => trim((string)($user['email'] ?? 'peserta@siaptes.id')),
        'customer_phone' => '081234567890',
        'order_items' => [['sku' => 'PRO-VIP-29K', 'name' => 'Akses Penuh PRO VIP SIAPTES.ID', 'price' => $amount, 'quantity' => 1]],
        'callback_url' => $baseUrl . '/payment_webhook.php',
        'return_url' => $baseUrl . '/payment_checkout.php?invoice=' . urlencode($invoiceNumber),
        'expired_time' => (time() + 86400),
        'signature' => hash_hmac('sha256', TRIPAY_MERCHANT_CODE . $invoiceNumber . $amount, TRIPAY_PRIVATE_KEY)
    ];
    $ch = curl_init(getTripayBaseUrl() . '/transaction/create');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . TRIPAY_API_KEY], CURLOPT_POSTFIELDS => http_build_query($payload), CURLOPT_TIMEOUT => 12]);
    $res = curl_exec($ch);
    if (!$res) return ['error' => 'Gagal menghubungi Tripay.'];
    $data = json_decode($res, true);
    if (!empty($data['success']) && !empty($data['data'])) {
        $tx = $data['data'];
        return ['reference' => $tx['reference'] ?? '', 'qr_url' => $tx['qr_url'] ?? '', 'qr_string' => $tx['qr_string'] ?? '', 'checkout_url' => $tx['checkout_url'] ?? ''];
    }
    return ['error' => $data['message'] ?? 'Gagal membuat transaksi Tripay.'];
}

function createMidtransSnapToken(string $invoiceNumber, int $amount, array $user): ?string { return null; }
function createMidtransQrisTransaction(string $invoiceNumber, int $amount, array $user): ?array { return null; }

/**
 * Cek status pembayaran langsung ke Server DOKU (Prioritas utama) atau cadangan Gateway lain
 * Mengembalikan true jika pembayaran sudah LUNAS.
 */
function checkGatewayPaymentPaidStatus(string $invoiceNumber, ?string $gatewayRef = null): bool {
    // 1. Cek via DOKU Checkout API
    if (GATEWAY_PROVIDER === 'doku' && isDokuConfigured()) {
        $dokuStatus = checkDokuOrderStatus($invoiceNumber);
        if (!empty($dokuStatus['paid'])) {
            return true;
        }
    }

    // 2. Cek via Mayar.id
    if (GATEWAY_PROVIDER === 'mayar' && isMayarConfigured() && !empty($gatewayRef)) {
        if (checkMayarInvoicePaidStatus($gatewayRef)) {
            return true;
        }
    }

    // 3. Cek via Tripay
    if (GATEWAY_PROVIDER === 'tripay' && isTripayConfigured() && !empty($gatewayRef)) {
        $ch = curl_init(getTripayBaseUrl() . '/transaction/detail?' . http_build_query(['reference' => $gatewayRef]));
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . TRIPAY_API_KEY], CURLOPT_TIMEOUT => 10]);
        $res = curl_exec($ch);
        if ($res) {
            $data = json_decode($res, true);
            if (!empty($data['success']) && strtoupper($data['data']['status'] ?? '') === 'PAID') return true;
        }
    }

    return false;
}
