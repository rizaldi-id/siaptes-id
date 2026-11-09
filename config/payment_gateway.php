<?php
// config/payment_gateway.php
// ============================================================================
// KONFIGURASI PAYMENT GATEWAY QRIS OTOMATIS - SIAP TES (siaptes.id)
// Harga Paket PRO VIP: Rp 29.000 (Khusus Pembayaran via QRIS Nasional)
// ============================================================================

// Harga Resmi Paket PRO VIP All-Access (Rp 29.000)
define('PRO_VIP_BASE_PRICE', 29000);
define('PRO_VIP_STRIKE_PRICE', 99000);

// ============================================================================
// PILIHAN PROVIDER GATEWAY QRIS OTOMATIS:
// - 'midtrans' : Otomatis via Midtrans QRIS / Snap API (Cek status real-time + Webhook)
// - 'tripay'   : Otomatis via Tripay.co.id QRIS API
// - 'builtin'  : Mode QRIS Dinamis Internal
// ============================================================================
define('GATEWAY_PROVIDER', getenv('GATEWAY_PROVIDER') ?: 'midtrans');

// --- 1. KONFIGURASI MIDTRANS (https://dashboard.midtrans.com) ---
// Catatan: Key tanpa awalan "SB-" adalah mode Production (Live = true)
define('MIDTRANS_MERCHANT_ID', getenv('MIDTRANS_MERCHANT_ID') ?: 'M376881517');
define('MIDTRANS_CLIENT_KEY', getenv('MIDTRANS_CLIENT_KEY') ?: 'Mid-client-pwyt9oN9uSI977Fd');
define('MIDTRANS_SERVER_KEY', getenv('MIDTRANS_SERVER_KEY') ?: 'Mid-server-uOXXsX~kvweD7ie4GHCjWGfO');

// Otomatis deteksi apakah Key adalah Sandbox (diawali SB-) atau Production (Live)
define('MIDTRANS_IS_PRODUCTION', (strpos(MIDTRANS_CLIENT_KEY, 'SB-') !== 0 && strpos(MIDTRANS_SERVER_KEY, 'SB-') !== 0));

// --- 2. KONFIGURASI TRIPAY QRIS (Opsional) ---
define('TRIPAY_IS_PRODUCTION', false);
define('TRIPAY_MERCHANT_CODE', getenv('TRIPAY_MERCHANT_CODE') ?: '');
define('TRIPAY_API_KEY', getenv('TRIPAY_API_KEY') ?: '');
define('TRIPAY_PRIVATE_KEY', getenv('TRIPAY_PRIVATE_KEY') ?: '');

// --- 3. IDENTITAS MERCHANT QRIS DISPLAY ---
define('QRIS_MERCHANT_NAME', 'SIAPTES.ID EDUKASI NASIONAL');
define('QRIS_NMID', 'ID1026000882910');
define('CUSTOM_QRIS_IMAGE_URL', '');

/**
 * Cek apakah MIDTRANS_SERVER_KEY sudah benar (diawali Mid-server- atau SB-Mid-server-)
 */
function isMidtransServerKeyValid(): bool {
    $key = trim(MIDTRANS_SERVER_KEY);
    return ($key !== '' && (strpos($key, 'Mid-server-') === 0 || strpos($key, 'SB-Mid-server-') === 0));
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
                'Scan <b>Kode QRIS SIAPTES.ID</b> yang tampil di layar ini (atau klik tombol <b>Bayar via Popup QRIS Midtrans</b>).',
                'Periksa nominal sebesar <b>Rp 29.000</b> dan selesaikan pembayaran dengan PIN Anda.',
                '<b>Otomatis Aktif:</b> Sistem akan mendeteksi pembayaran Anda secara otomatis dalam hitungan detik dan langsung mengaktifkan akun <b>PRO VIP</b>.'
            ]
        ]
    ];
}

/**
 * Generate string payload QRIS standar EMVCo (untuk dirender menjadi QR Code)
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
 * Buat Snap Token Midtrans (Khusus QRIS) — Standar bawaan semua akun Midtrans
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
        'enabled_payments' => ['other_qris', 'gopay'],
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
        CURLOPT_TIMEOUT => 12
    ]);
    $res = curl_exec($ch);
    curl_close($ch);

    if (!$res) return null;
    $data = json_decode($res, true);
    return $data['token'] ?? null;
}

/**
 * Buat transaksi QRIS ke Midtrans Core API (jika diaktifkan di akun Midtrans)
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
 * Cek status pembayaran langsung ke Server Midtrans / Tripay (Auto-Polling)
 * Mengembalikan true jika pembayaran sudah LUNAS (Settlement / Paid).
 */
function checkGatewayPaymentPaidStatus(string $invoiceNumber): bool {
    // 1. Jika menggunakan Midtrans dengan Server Key valid
    if (isMidtransServerKeyValid()) {
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

    // 2. Jika menggunakan Tripay
    if (!empty(TRIPAY_API_KEY)) {
        $url = (TRIPAY_IS_PRODUCTION ? 'https://tripay.co.id/api/transaction/check-status?reference=' : 'https://tripay.co.id/api-sandbox/transaction/check-status?reference=')
             . urlencode($invoiceNumber);
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
            if (!empty($data['success']) && (($data['data']['status'] ?? '') === 'PAID')) {
                return true;
            }
        }
    }

    return false;
}
