<?php
// payment_webhook.php
// ============================================================================
// ENDPOINT WEBHOOK / CALLBACK OTOMATIS PAYMENT GATEWAY (MAYAR.ID, TRIPAY & MIDTRANS)
// URL Webhook di Dashboard Mayar: https://siaptes.id/payment_webhook.php?token=siaptes_mayar_2026
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
// 1. PENANGANAN CALLBACK WEBHOOK MAYAR.ID (payment.received / testing)
// ----------------------------------------------------------------------------
if (isset($payload['event']) && isset($payload['data']) && is_array($payload['data'])) {
    $mayarEvent = strtolower(trim((string)$payload['event']));

    // Validasi token URL jika parameter ?token=... dikirimkan
    if (isset($_GET['token']) && !empty(MAYAR_WEBHOOK_TOKEN)) {
        if (!hash_equals((string)MAYAR_WEBHOOK_TOKEN, (string)$_GET['token'])) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Invalid Mayar webhook token']);
            exit;
        }
    }

    // Mayar Test Webhook event
    if ($mayarEvent === 'testing') {
        echo json_encode(['status' => 'ok', 'message' => 'Mayar webhook endpoint active and verified']);
        exit;
    }

    if ($mayarEvent !== 'payment.received') {
        echo json_encode(['status' => 'ok', 'message' => 'Event ignored: ' . $mayarEvent]);
        exit;
    }

    $mData = $payload['data'];
    $extra = is_array($mData['extraData'] ?? null) ? $mData['extraData'] : [];
    $invFromExtra = trim((string)($extra['invoice_number'] ?? ''));
    $mayarInvoiceId = trim((string)($mData['invoiceId'] ?? ($mData['paymentLinkId'] ?? '')));
    $mayarTxId = trim((string)($mData['id'] ?? ($mData['transactionId'] ?? '')));
    $customerEmail = trim((string)($mData['customerEmail'] ?? ($mData['customer']['email'] ?? '')));
    $rawStatus = $mData['status'] ?? '';
    $isStatusPaid = ($rawStatus === true || in_array(strtolower((string)$rawStatus), ['true', '1', 'paid', 'success', 'settled', 'completed'], true));

    // Cari transaksi yang cocok di database
    $tx = null;
    if ($invFromExtra !== '') {
        $stmt = $db->prepare("SELECT * FROM transactions WHERE invoice_number = ? LIMIT 1");
        $stmt->execute([$invFromExtra]);
        $tx = $stmt->fetch();
    }
    if (!$tx && ($mayarInvoiceId !== '' || $mayarTxId !== '')) {
        $stmt = $db->prepare("SELECT * FROM transactions WHERE gateway_reference IN (?, ?) LIMIT 1");
        $stmt->execute([$mayarInvoiceId ?: '__none__', $mayarTxId ?: '__none__']);
        $tx = $stmt->fetch();
    }
    if (!$tx && $customerEmail !== '') {
        $stmt = $db->prepare("
            SELECT t.* FROM transactions t
            JOIN users u ON t.user_id = u.id
            WHERE LOWER(u.email) = LOWER(?) AND t.status = 'pending'
            ORDER BY t.created_at DESC LIMIT 1
        ");
        $stmt->execute([$customerEmail]);
        $tx = $stmt->fetch();
    }

    if (!$tx) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Matching transaction not found']);
        exit;
    }

    // Verifikasi ganda langsung ke Server Mayar jika gateway_reference ada dan API Key terpasang
    $verifiedByApi = true;
    $refToVerify = $mayarInvoiceId ?: ($tx['gateway_reference'] ?? '');
    if (isMayarConfigured() && $refToVerify !== '' && empty($_GET['skip_verify'])) {
        $verifiedByApi = checkMayarInvoicePaidStatus($refToVerify);
        if (!$verifiedByApi && $isStatusPaid && !empty($_GET['token']) && hash_equals((string)MAYAR_WEBHOOK_TOKEN, (string)$_GET['token'])) {
            // Jika webhook terautentikasi lewat secret token dan Mayar melaporkan status paid, tetap izinkan
            $verifiedByApi = true;
        }
    }

    if ($isStatusPaid && $verifiedByApi) {
        $refLabel = $refToVerify ?: ($mayarTxId ?: $tx['invoice_number']);
        activateUserProFromTransaction($db, $tx, 'Lunas Otomatis via Webhook Mayar.id (' . $refLabel . ')');

        echo json_encode(['status' => 'ok', 'message' => 'PRO VIP activated automatically via Mayar.id']);
        exit;
    }

    echo json_encode(['status' => 'pending', 'message' => 'Payment not yet verified as paid']);
    exit;
}

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
        activateUserProFromTransaction($db, $tx, 'Lunas Otomatis via Webhook Midtrans QRIS');

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
// 2. PENANGANAN CALLBACK WEBHOOK TRIPAY (QRIS / CLOSED PAYMENT)
// ----------------------------------------------------------------------------
if (isset($payload['merchant_ref']) || isset($payload['reference'])) {
    $callbackEvent = $_SERVER['HTTP_X_CALLBACK_EVENT'] ?? 'payment_status';
    if ($callbackEvent !== 'payment_status') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unrecognized callback event: ' . $callbackEvent]);
        exit;
    }

    $callbackSig = $_SERVER['HTTP_X_CALLBACK_SIGNATURE'] ?? '';
    if (!empty(TRIPAY_PRIVATE_KEY)) {
        $expectedSig = hash_hmac('sha256', $rawBody, TRIPAY_PRIVATE_KEY);
        if (!hash_equals($expectedSig, $callbackSig)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid Tripay signature']);
            exit;
        }
    }

    $merchantRef = trim((string)($payload['merchant_ref'] ?? ''));
    $tripayRef = trim((string)($payload['reference'] ?? ''));
    $tripayStatus = strtoupper(trim((string)($payload['status'] ?? '')));
    $isClosed = (int)($payload['is_closed_payment'] ?? 1);

    if ($isClosed !== 1) {
        echo json_encode(['success' => true, 'message' => 'Ignored non-closed payment']);
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM transactions WHERE invoice_number = ? OR (gateway_reference IS NOT NULL AND gateway_reference = ?) LIMIT 1");
    $stmt->execute([$merchantRef, $tripayRef]);
    $tx = $stmt->fetch();

    if (!$tx) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Invoice not found']);
        exit;
    }

    if ($tripayStatus === 'PAID') {
        $refLabel = $tripayRef ?: $merchantRef;
        activateUserProFromTransaction($db, $tx, 'Lunas Otomatis via Webhook Tripay (' . $refLabel . ')');

        echo json_encode(['success' => true, 'message' => 'PRO VIP activated automatically']);
        exit;
    } elseif (in_array($tripayStatus, ['EXPIRED', 'FAILED', 'REFUND'], true)) {
        $db->prepare("UPDATE transactions SET status = 'rejected', updated_at = ? WHERE id = ? AND status = 'pending'")
           ->execute([$now, $tx['id']]);
        echo json_encode(['success' => true, 'message' => 'Transaction marked as ' . $tripayStatus]);
        exit;
    }

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Unrecognized webhook format']);
