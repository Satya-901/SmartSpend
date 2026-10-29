<?php
/**
 * Categories API Endpoint
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
    $type = trim($_GET['type'] ?? '');
    $categories = get_user_categories($userId, !empty($type) ? $type : null);
    json_response(true, 'Categories retrieved.', ['categories' => $categories]);
}

if ($action === 'add') {
    require_csrf_token();

    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? 'expense');
    $icon = trim($_POST['icon'] ?? 'fa-tags');
    $color = trim($_POST['color'] ?? '#FF9F43');
    $bgColor = trim($_POST['bg_color'] ?? '#FFF3E0');

    if (empty($name)) {
        json_response(false, 'Category name is required.', [], 400);
    }

    if (!in_array($type, ['expense', 'income'], true)) {
        json_response(false, 'Invalid category type.', [], 400);
    }

    // Ensure icon starts with fa- or fa-solid
    if (!str_starts_with($icon, 'fa-') && !str_starts_with($icon, 'fa ')) {
        $icon = 'fa-' . $icon;
    }

    $stmt = $db->prepare("
        INSERT INTO categories (user_id, name, type, icon, color, bg_color)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    if ($stmt->execute([$userId, $name, $type, $icon, $color, $bgColor])) {
        json_response(true, 'Custom category created successfully!', ['id' => $db->lastInsertId()]);
    } else {
        json_response(false, 'Failed to create category.', [], 500);
    }
}

if ($action === 'edit') {
    require_csrf_token();

    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? 'fa-tags');
    $color = trim($_POST['color'] ?? '#FF9F43');
    $bgColor = trim($_POST['bg_color'] ?? '#FFF3E0');

    $stmtCheck = $db->prepare("SELECT * FROM categories WHERE id = ? AND user_id = ?");
    $stmtCheck->execute([$id, $userId]);
    $cat = $stmtCheck->fetch();

    if (!$cat) {
        json_response(false, 'Category not found or is a system default that cannot be edited.', [], 403);
    }

    $stmt = $db->prepare("UPDATE categories SET name = ?, icon = ?, color = ?, bg_color = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$name, $icon, $color, $bgColor, $id, $userId]);

    json_response(true, 'Category updated successfully!');
}

if ($action === 'delete') {
    require_csrf_token();

    $id = (int)($_POST['id'] ?? 0);
    $stmtCheck = $db->prepare("SELECT * FROM categories WHERE id = ? AND user_id = ?");
    $stmtCheck->execute([$id, $userId]);
    $cat = $stmtCheck->fetch();

    if (!$cat) {
        json_response(false, 'Category not found or is a system default that cannot be deleted.', [], 403);
    }

    // Unlink from transactions
    $stmtUnlink = $db->prepare("UPDATE transactions SET category_id = NULL WHERE category_id = ? AND user_id = ?");
    $stmtUnlink->execute([$id, $userId]);

    $stmt = $db->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);

    json_response(true, 'Custom category deleted successfully.');
}

json_response(false, 'Invalid request action.', [], 400);

