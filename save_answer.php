<?php
// save_answer.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = currentUser();
$db = getDB();

$answer_id = intval($_POST['answer_id'] ?? 0);
$selected_option = isset($_POST['selected_option']) ? trim($_POST['selected_option']) : null;
$is_doubt = isset($_POST['is_doubt']) ? intval($_POST['is_doubt']) : null;

if ($answer_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid answer id']);
    exit;
}

// Ensure the answer belongs to current user's active attempt
$check = $db->prepare("
    SELECT ua.id 
    FROM user_answers ua
    JOIN exam_attempts ea ON ua.attempt_id = ea.id
    WHERE ua.id = ? AND ea.user_id = ? AND ea.status = 'in_progress'
");
$check->execute([$answer_id, $user['id']]);
if (!$check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Attempt not found or finished']);
    exit;
}

// Build update query
$params = [];
$updates = [];

if ($selected_option !== null) {
    $updates[] = "selected_option = ?";
    $params[] = $selected_option;
}

if ($is_doubt !== null) {
    $updates[] = "is_doubt = ?";
    $params[] = $is_doubt;
}

if (!empty($updates)) {
    $params[] = $answer_id;
    $sql = "UPDATE user_answers SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
}

echo json_encode(['success' => true]);
exit;
