<?php
// ============================================
// Session + auth helpers
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_role() {
    return $_SESSION['role'] ?? null;
}

function require_login($role = null) {
    if (!is_logged_in()) {
        header("Location: /lms/login.php");
        exit;
    }
    if ($role && current_role() !== $role) {
        header("Location: /lms/login.php");
        exit;
    }
}

function gen_class_code($length = 6) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no confusing 0/O/1/I
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

function push_notification($conn, $user_id, $message) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $message);
    $stmt->execute();
}

function unread_notif_count($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) c FROM notifications WHERE user_id=? AND is_read=0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['c'];
}

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
