<?php
// config/payment_gateway.php
// ============================================================================
// KONFIGURASI PAYMENT GATEWAY QRIS OTOMATIS - SIAP TES (siaptes.id)
// Harga Paket PRO VIP: Rp 29.000 (Aktivasi Otomatis Real-Time)
// ============================================================================

// Muat konfigurasi kredensial lokal (tidak ter-commit ke GitHub jika dibuat)
if (file_exists(__DIR__ . '/payment_local.php')) {
    require_once __DIR__ . '/payment_local.php';
}

// Harga Resmi Paket PRO VIP All-Access (Rp 29.000)
if (!defined('PRO_VIP_BASE_PRICE')) define('PRO_VIP_BASE_PRICE', 29000);
if (!defined('PRO_VIP_STRIKE_PRICE')) define('PRO_VIP_STRIKE_PRICE', 99000);

// ============================================================================
// PILIHAN PROVIDER GATEWAY QRIS OTOMATIS:
// - 'tripay'   : Otomatis via Tripay.co.id Closed Payment API (Webhook + Auto-Polling)
// - 'midtrans' : Otomatis via Midtrans QRIS / Snap API
// - 'builtin'  : Mode QRIS Dinamis Internal
// ============================================================================
if (!defined('GATEWAY_PROVIDER')) {
    define('GATEWAY_PROVIDER', getenv('GATEWAY_PROVIDER') ?: 'tripay');
}

// --- 1. KONFIGURASI TRIPAY (https://tripay.co.id) ---
// Ubah TRIPAY_IS_PRODUCTION ke true saat menggunakan kredensial Live/Production Tripay
if (!defined('TRIPAY_IS_PRODUCTION')) {
    $envProd = getenv('TRIPAY_IS_PRODUCTION');
    define('TRIPAY_IS_PRODUCTION', ($envProd !== false) ? filter_var($envProd, FILTER_VALIDATE_BOOLEAN) : false);
}
if (!defined('TRIPAY_MERCHANT_CODE')) define('TRIPAY_MERCHANT_CODE', getenv('TRIPAY_MERCHANT_CODE') ?: '');
if (!defined('TRIPAY_API_KEY')) define('TRIPAY_API_KEY', getenv('TRIPAY_API_KEY') ?: '');
if (!defined('TRIPAY_PRIVATE_KEY')) define('TRIPAY_PRIVATE_KEY', getenv('TRIPAY_PRIVATE_KEY') ?: '');
if (!defined('TRIPAY_QRIS_CHANNEL')) define('TRIPAY_QRIS_CHANNEL', getenv('TRIPAY_QRIS_CHANNEL') ?: ''); // Opsional: kosongkan untuk deteksi otomatis QRIS/QRISC

// --- 2. KONFIGURASI MIDTRANS (Opsional / Cadangan) ---
if (!defined('MIDTRANS_MERCHANT_ID')) define('MIDTRANS_MERCHANT_ID', getenv('MIDTRANS_MERCHANT_ID') ?: '');
if (!defined('MIDTRANS_CLIENT_KEY')) define('MIDTRANS_CLIENT_KEY', getenv('MIDTRANS_CLIENT_KEY') ?: '');
if (!defined('MIDTRANS_SERVER_KEY')) define('MIDTRANS_SERVER_KEY', getenv('MIDTRANS_SERVER_KEY') ?: '');
if (!defined('MIDTRANS_IS_PRODUCTION')) {
    define('MIDTRANS_IS_PRODUCTION', (strpos(MIDTRANS_CLIENT_KEY, 'SB-') !== 0 && strpos(MIDTRANS_SERVER_KEY, 'SB-') !== 0));
}

// --- 3. IDENTITAS MERCHANT QRIS DISPLAY ---
if (!defined('QRIS_MERCHANT_NAME')) define('QRIS_MERCHANT_NAME', 'SIAPTES.ID EDUKASI NASIONAL');
if (!defined('QRIS_NMID')) define('QRIS_NMID', 'ID1026000882910');
if (!defined('CUSTOM_QRIS_IMAGE_URL')) define('CUSTOM_QRIS_IMAGE_URL', '');

// --- 4. KONTAK DUKUNGAN & KONFIRMASI (WhatsApp) ---
if (!defined('ADMIN_WHATSAPP_NUMBER')) define('ADMIN_WHATSAPP_NUMBER', getenv('ADMIN_WHATSAPP_NUMBER') ?: '6281234567890');

/**
 * Cek apakah konfigurasi Tripay lengkap (Merchant Code, API Key, Private Key)
 */
function isTripayConfigured(): bool {
    return (trim(TRIPAY_MERCHANT_CODE) !== '' && trim(TRIPAY_API_KEY) !== '' && trim(TRIPAY_PRIVATE_KEY) !== '');
}

/**
 * Base URL API Tripay (Production vs Sandbox)
 */
function getTripayBaseUrl(): string {
    return TRIPAY_IS_PRODUCTION ? 'https://tripay.co.id/api' : 'https://tripay.co.id/api-sandbox';
}

/**
 * Cek apakah MIDTRANS_SERVER_KEY sudah benar (diawali Mid-server- atau SB-Mid-server-)
 */
function isMidtransServerKeyValid(): bool {
    $key = trim(MIDTRANS_SERVER_KEY);
    return ($key !== '' && (strpos($key, 'Mid-server-') === 0 || strpos($key, 'SB-Mid-server-') === 0));
}

/**
 * Aktivasi Idempoten: Menandai transaksi Lunas ('approved') dan langsung mengaktifkan PRO VIP ('is_premium = 1').
 * Digunakan bersama oleh Webhook Tripay, Auto-Polling, dan Verifikasi Manual agar konsisten 100%.
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
 * Ambil base URL situs yang sedang berjalan (untuk callback_url & return_url)
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
 * Saluran Pembayaran: Fokus 100% via QRIS Nasional (Rp 29.000)
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
                'Buka aplikasi pembayaran di HP Anda (<b>GoPay, OVO, DANA, ShopeePay, LinkAja</b>, atau <b>Mobile Banking BCA, Mandiri, BRI, BNI, BSI</b>).',
                'Pilih menu <b>Bayar / Scan QRIS</b>.',
                'Scan <b>Kode QRIS</b> yang tampil di layar ini.',
                'Periksa nominal sebesar <b>Rp 29.000</b> dan selesaikan pembayaran dengan PIN Anda.',
                '<b>Otomatis Aktif:</b> Sistem Tripay akan langsung mengonfirmasi pembayaran dalam hitungan detik dan otomatis mengaktifkan akun <b>PRO VIP</b> Anda.'
            ]
        ]
    ];
}

/**
 * Generate string payload QRIS standar EMVCo fallback (saat API belum dikonfigurasi)
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
 * Ambil daftar channel pembayaran aktif di akun Merchant Tripay
 */
function getTripayPaymentChannels(): array {
    static $cachedChannels = null;
    if ($cachedChannels !== null) {
        return $cachedChannels;
    }
    if (!isTripayConfigured()) {
        return $cachedChannels = [];
    }

    $url = getTripayBaseUrl() . '/merchant/payment-channel';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . TRIPAY_API_KEY
        ],
        CURLOPT_TIMEOUT => 8
    ]);
    $res = curl_exec($ch);
    curl_close($ch);

    if (!$res) {
        return $cachedChannels = [];
    }
    $data = json_decode($res, true);
    if (!empty($data['success']) && !empty($data['data']) && is_array($data['data'])) {
        return $cachedChannels = $data['data'];
    }
    return $cachedChannels = [];
}

/**
 * Deteksi kode channel QRIS yang aktif pada akun Tripay (prioritas: TRIPAY_QRIS_CHANNEL -> QRISC -> QRIS -> QRIS2 -> QRIS_SHOPEEPAY)
 */
function getTripayActiveQrisMethod(): string {
    if (trim(TRIPAY_QRIS_CHANNEL) !== '') {
        return trim(TRIPAY_QRIS_CHANNEL);
    }

    $channels = getTripayPaymentChannels();
    $preferred = ['QRISC', 'QRIS', 'QRIS2', 'QRIS_SHOPEEPAY'];
    $activeCodes = [];
    foreach ($channels as $ch) {
        if (!empty($ch['active']) && !empty($ch['code'])) {
            $activeCodes[] = strtoupper($ch['code']);
        }
    }

    foreach ($preferred as $code) {
        if (in_array($code, $activeCodes, true)) {
            return $code;
        }
    }

    // Jika tidak ada QRIS terdeteksi, kembalikan channel aktif pertama jika ada, atau default 'QRIS'
    return !empty($activeCodes) ? $activeCodes[0] : 'QRIS';
}

/**
 * Buat transaksi Closed Payment di Tripay (otomatis menghasilkan qr_url, qr_string, dan checkout_url)
 */
function createTripayQrisTransaction(string $invoiceNumber, int $amount, array $user, string $method = ''): ?array {
    if (!isTripayConfigured()) {
        return null;
    }

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
        'order_items' => [
            [
                'sku' => 'PRO-VIP-29K',
                'name' => 'Akses Penuh PRO VIP SIAPTES.ID',
                'price' => $amount,
                'quantity' => 1
            ]
        ],
        'callback_url' => $baseUrl . '/payment_webhook.php',
        'return_url' => $baseUrl . '/payment_checkout.php?invoice=' . urlencode($invoiceNumber),
        'expired_time' => (time() + (24 * 60 * 60)),
        'signature' => $signature
    ];

    $url = getTripayBaseUrl() . '/transaction/create';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . TRIPAY_API_KEY
        ],
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_TIMEOUT => 12
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if (!$res) {
        return ['error' => $err ?: 'Tidak dapat menghubungi server Tripay.'];
    }

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

/**
 * Buat Snap Token Midtrans (Cadangan bila GATEWAY_PROVIDER === 'midtrans')
 */
function createMidtransSnapToken(string $invoiceNumber, int $amount, array $user): ?string {
    if (!isMidtransServerKeyValid()) {
        return null;
    }

    $snapUrl = MIDTRANS_IS_PRODUCTION
        ? 'https://app.midtrans.com/snap/v1/transactions'
        : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

    $payload = [
        'transaction_details' => [
            'order_id' => $invoiceNumber,
            'gross_amount' => $amount
        ],
        'customer_details' => [
            'first_name' => $user['name'],
            'email' => $user['email']
        ],
        'item_details' => [
            [
                'id' => 'PRO-VIP-29K',
                'price' => $amount,
                'quantity' => 1,
                'name' => 'Akses Penuh PRO VIP SIAPTES.ID'
            ]
        ]
    ];

    $ch = curl_init($snapUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':')
        ],
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
    curl_setopt_array($chCheck, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5
    ]);
    $resCheck = curl_exec($chCheck);
    curl_close($chCheck);
    if ($resCheck) {
        $checkData = json_decode($resCheck, true);
        if (isset($checkData['enabled_payments']) && empty($checkData['enabled_payments'])) {
            return null;
        }
    }

    return $token;
}

/**
 * Buat transaksi QRIS ke Midtrans Core API (Cadangan)
 */
function createMidtransQrisTransaction(string $invoiceNumber, int $amount, array $user): ?array {
    if (!isMidtransServerKeyValid()) {
        return null;
    }

    $baseUrl = MIDTRANS_IS_PRODUCTION 
        ? 'https://api.midtrans.com/v2/charge' 
        : 'https://api.sandbox.midtrans.com/v2/charge';

    $payload = [
        'payment_type' => 'qris',
        'transaction_details' => [
            'order_id' => $invoiceNumber,
            'gross_amount' => $amount
        ],
        'customer_details' => [
            'first_name' => $user['name'],
            'email' => $user['email']
        ],
        'item_details' => [
            [
                'id' => 'PRO-VIP-29K',
                'price' => $amount,
                'quantity' => 1,
                'name' => 'Akses Penuh PRO VIP SIAPTES.ID'
            ]
        ]
    ];

    $ch = curl_init($baseUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':')
        ],
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
                if ($act['name'] === 'generate-qr-code') {
                    $qrUrl = $act['url'];
                    break;
                }
            }
        }
        return [
            'qr_string' => $data['qr_string'] ?? '',
            'qr_url' => $qrUrl
        ];
    }
    return null;
}

/**
 * Cek status pembayaran langsung ke Server Tripay / Midtrans (Auto-Polling & Manual Check)
 * Mengembalikan true jika pembayaran sudah LUNAS (PAID / Settlement).
 */
function checkGatewayPaymentPaidStatus(string $invoiceNumber, ?string $gatewayRef = null): bool {
    // 1. Cek via Tripay API (Prioritas utama)
    if (isTripayConfigured()) {
        // 1a. Cek detail langsung menggunakan Reference Tripay jika tersedia
        if (!empty($gatewayRef)) {
            $url = getTripayBaseUrl() . '/transaction/detail?' . http_build_query(['reference' => $gatewayRef]);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . TRIPAY_API_KEY
                ],
                CURLOPT_TIMEOUT => 10
            ]);
            $res = curl_exec($ch);
            curl_close($ch);
            if ($res) {
                $data = json_decode($res, true);
                if (!empty($data['success']) && strtoupper($data['data']['status'] ?? '') === 'PAID') {
                    return true;
                }
            }
        }

        // 1b. Cek daftar transaksi Tripay menggunakan merchant_ref (invoiceNumber)
        $listUrl = getTripayBaseUrl() . '/merchant/transactions?' . http_build_query([
            'merchant_ref' => $invoiceNumber,
            'per_page' => 10
        ]);
        $chList = curl_init($listUrl);
        curl_setopt_array($chList, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . TRIPAY_API_KEY
            ],
            CURLOPT_TIMEOUT => 10
        ]);
        $resList = curl_exec($chList);
        curl_close($chList);
        if ($resList) {
            $listData = json_decode($resList, true);
            if (!empty($listData['success']) && !empty($listData['data']) && is_array($listData['data'])) {
                foreach ($listData['data'] as $item) {
                    if (($item['merchant_ref'] ?? '') === $invoiceNumber && strtoupper($item['status'] ?? '') === 'PAID') {
                        return true;
                    }
                }
            }
        }
    }

    // 2. Cadangan: Cek via Midtrans jika GATEWAY_PROVIDER === 'midtrans'
    if (GATEWAY_PROVIDER === 'midtrans' && isMidtransServerKeyValid()) {
        $url = (MIDTRANS_IS_PRODUCTION ? 'https://api.midtrans.com/v2/' : 'https://api.sandbox.midtrans.com/v2/')
             . urlencode($invoiceNumber) . '/status';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':')
            ],
            CURLOPT_TIMEOUT => 10
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        if ($res) {
            $data = json_decode($res, true);
            $txStatus = $data['transaction_status'] ?? '';
            $fraudStatus = $data['fraud_status'] ?? 'accept';
            if ($txStatus === 'settlement' || ($txStatus === 'capture' && $fraudStatus === 'accept')) {
                return true;
            }
        }
    }

    return false;
}
