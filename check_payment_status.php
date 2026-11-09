<?php
// check_payment_status.php
// Endpoint JSON untuk pengecekan otomatis (Auto-Polling) setiap 3 detik di halaman QRIS Checkout
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/payment_gateway.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'unauthorized']);
    exit;
}

$invoice = isset($_GET['invoice']) ? trim($_GET['invoice']) : '';
if ($invoice === '') {
    echo json_encode(['status' => 'invalid']);
    exit;
}

$db = getDB();
$user = currentUser();

$stmt = $db->prepare("SELECT * FROM transactions WHERE invoice_number = ? AND (user_id = ? OR ? = 1) LIMIT 1");
$stmt->execute([$invoice, $user['id'], isAdmin() ? 1 : 0]);
$tx = $stmt->fetch();

if (!$tx) {
    echo json_encode(['status' => 'not_found']);
    exit;
}

// Jika di database sudah approved (misal karena Webhook baru saja masuk)
if ($tx['status'] === 'approved') {
    // Pastikan user is_premium = 1
    $db->prepare("UPDATE users SET is_premium = 1 WHERE id = ?")->execute([$tx['user_id']]);
    echo json_encode([
        'status' => 'approved',
        'invoice' => $tx['invoice_number'],
        'amount' => (int)$tx['amount']
    ]);
    exit;
}

// Jika masih pending, cek langsung ke API Midtrans / Tripay bila API Key sudah dikonfigurasi
if ($tx['status'] === 'pending' && checkGatewayPaymentPaidStatus($tx['invoice_number'])) {
    $now = date('Y-m-d H:i:s');
    $db->prepare("UPDATE transactions SET status = 'approved', notes = ?, updated_at = ? WHERE id = ?")
       ->execute(['Lunas Otomatis via API Gateway QRIS', $now, $tx['id']]);
    $db->prepare("UPDATE users SET is_premium = 1 WHERE id = ?")
       ->execute([$tx['user_id']]);

    echo json_encode([
        'status' => 'approved',
        'invoice' => $tx['invoice_number'],
        'amount' => (int)$tx['amount']
    ]);
    exit;
}

echo json_encode([
    'status' => $tx['status'],
    'invoice' => $tx['invoice_number']
]);
