<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function currentUser() {
    if (!isLoggedIn()) return null;
    require_once __DIR__ . '/../config/database.php';
    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, email, role, is_premium, avatar, google_id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? 'User',
            'email' => $_SESSION['user_email'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'user',
            'is_premium' => 0,
            'avatar' => $_SESSION['user_avatar'] ?? null,
            'google_id' => null
        ];
    }
    return $user;
}

function isPremiumUser() {
    $u = currentUser();
    if (!$u) return false;
    if ($u['role'] === 'admin') return true;
    return !empty($u['is_premium']);
}

function isAdmin() {
    $u = currentUser();
    return ($u && isset($u['role']) && $u['role'] === 'admin');
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php?msg=Silakan login terlebih dahulu');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    $u = currentUser();
    if (!$u || $u['role'] !== 'admin') {
        header('Location: ../index.php?msg=Akses ditolak');
        exit;
    }
}
