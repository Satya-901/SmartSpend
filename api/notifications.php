<?php
/**
 * Notifications API Endpoint
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();
header('Content-Type: application/json; charset=utf-8');

$userId = current_user_id();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$db = get_db();

if ($action === 'list') {
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY is_read ASC, created_at DESC LIMIT 50");
    $stmt->execute([$userId]);
    $notifs = $stmt->fetchAll();

    foreach ($notifs as &$n) {
        $n['time_ago'] = format_relative_day(substr($n['created_at'], 0, 10)) . ' ' . date('h:i A', strtotime($n['created_at']));
    }

    json_response(true, 'Notifications loaded.', ['notifications' => $notifs]);
}

if ($action === 'mark_read') {
    require_csrf_token();
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    json_response(true, 'Notification marked as read.');
}

if ($action === 'mark_all_read') {
    require_csrf_token();
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$userId]);
    json_response(true, 'All notifications marked as read.');
}

if ($action === 'delete') {
    require_csrf_token();
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    json_response(true, 'Notification deleted.');
}

json_response(false, 'Invalid request action.', [], 400);

