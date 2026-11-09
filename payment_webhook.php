<?php
// payment_webhook.php
// ============================================================================
// ENDPOINT WEBHOOK / CALLBACK OTOMATIS PAYMENT GATEWAY (MIDTRANS & TRIPAY)
// URL Webhook di Dashboard Midtrans / Tripay: https://siaptes.id/payment_webhook.php
// ============================================================================
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/payment_gateway.php';

header('Content-Type: application/json');

$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody, true);

if (!$payload || !is_array($payload)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON payload']);
    exit;
}

$db = getDB();
$now = date('Y-m-d H:i:s');

// ----------------------------------------------------------------------------
// 1. PENANGANAN CALLBACK WEBHOOK MIDTRANS (QRIS / GoPay / Snap)
// ----------------------------------------------------------------------------
if (isset($payload['order_id']) && isset($payload['transaction_status'])) {
    $orderId = trim($payload['order_id']);
    $statusCode = (string)($payload['status_code'] ?? '');
    $grossAmount = (string)($payload['gross_amount'] ?? '');
    $signatureKey = (string)($payload['signature_key'] ?? '');
    $txStatus = $payload['transaction_status'];
    $fraudStatus = $payload['fraud_status'] ?? 'accept';

    // Verifikasi Signature Key Midtrans jika Server Key sudah diisi dan valid
    if (isMidtransServerKeyValid()) {
        $expectedSig = hash('sha512', $orderId . $statusCode . $grossAmount . MIDTRANS_SERVER_KEY);
        if (!hash_equals($expectedSig, $signatureKey)) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Invalid Midtrans signature']);
            exit;
        }
    }

    $stmt = $db->prepare("SELECT * FROM transactions WHERE invoice_number = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $tx = $stmt->fetch();

    if (!$tx) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Invoice not found']);
        exit;
    }

    if ($txStatus === 'settlement' || ($txStatus === 'capture' && $fraudStatus === 'accept')) {
        // Pembayaran QRIS Sukses -> Update transaksi & aktifkan PRO VIP otomatis!
        $db->prepare("UPDATE transactions SET status = 'approved', notes = ?, updated_at = ? WHERE id = ?")
           ->execute(['Lunas Otomatis via Webhook Midtrans QRIS', $now, $tx['id']]);
        $db->prepare("UPDATE users SET is_premium = 1 WHERE id = ?")
           ->execute([$tx['user_id']]);

        echo json_encode(['status' => 'ok', 'message' => 'PRO VIP activated automatically']);
        exit;
    } elseif (in_array($txStatus, ['cancel', 'deny', 'expire'])) {
        $db->prepare("UPDATE transactions SET status = 'rejected', updated_at = ? WHERE id = ?")
           ->execute([$now, $tx['id']]);
        echo json_encode(['status' => 'ok', 'message' => 'Transaction marked as expired/cancelled']);
        exit;
    }

    echo json_encode(['status' => 'ok', 'message' => 'Pending status received']);
    exit;
}

// ----------------------------------------------------------------------------
// 2. PENANGANAN CALLBACK WEBHOOK TRIPAY (QRIS)
// ----------------------------------------------------------------------------
if (isset($payload['merchant_ref']) && isset($payload['status'])) {
    $merchantRef = trim($payload['merchant_ref']);
    $tripayStatus = strtoupper(trim($payload['status']));
    $callbackSig = $_SERVER['HTTP_X_CALLBACK_SIGNATURE'] ?? '';

    if (!empty(TRIPAY_PRIVATE_KEY)) {
        $expectedSig = hash_hmac('sha256', $rawBody, TRIPAY_PRIVATE_KEY);
        if (!hash_equals($expectedSig, $callbackSig)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid Tripay signature']);
            exit;
        }
    }

    $stmt = $db->prepare("SELECT * FROM transactions WHERE invoice_number = ? LIMIT 1");
    $stmt->execute([$merchantRef]);
    $tx = $stmt->fetch();

    if (!$tx) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Invoice not found']);
        exit;
    }

    if ($tripayStatus === 'PAID') {
        $db->prepare("UPDATE transactions SET status = 'approved', notes = ?, updated_at = ? WHERE id = ?")
           ->execute(['Lunas Otomatis via Webhook Tripay QRIS', $now, $tx['id']]);
        $db->prepare("UPDATE users SET is_premium = 1 WHERE id = ?")
           ->execute([$tx['user_id']]);

        echo json_encode(['success' => true, 'message' => 'PRO VIP activated automatically']);
        exit;
    } elseif (in_array($tripayStatus, ['EXPIRED', 'FAILED'])) {
        $db->prepare("UPDATE transactions SET status = 'rejected', updated_at = ? WHERE id = ?")
           ->execute([$now, $tx['id']]);
    }

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Unrecognized webhook format']);
