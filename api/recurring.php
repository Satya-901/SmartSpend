<?php
/**
 * Recurring Transactions API Endpoint
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

if ($action === 'add') {
    require_csrf_token();

    $title = trim($_POST['title'] ?? '');
    $type = trim($_POST['type'] ?? 'expense');
    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $amount = (float)($_POST['amount'] ?? 0);
    $frequency = trim($_POST['frequency'] ?? 'monthly');
    $startDate = trim($_POST['start_date'] ?? date('Y-m-d'));
    $nextDueDate = trim($_POST['next_due_date'] ?? $startDate);
    $paymentMethod = trim($_POST['payment_method'] ?? 'UPI');
    $notes = trim($_POST['notes'] ?? '');

    if (empty($title) || $amount <= 0) {
        json_response(false, 'Title and valid amount are required.', [], 400);
    }

    $stmt = $db->prepare("
        INSERT INTO recurring_transactions (user_id, title, type, category_id, amount, frequency, start_date, next_due_date, payment_method, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if ($stmt->execute([$userId, $title, $type, $categoryId, $amount, $frequency, $startDate, $nextDueDate, $paymentMethod, $notes])) {
        json_response(true, 'Recurring rule created successfully!');
    } else {
        json_response(false, 'Failed to create recurring rule.', [], 500);
    }
}

if ($action === 'process_due') {
    require_csrf_token();

    $id = (int)($_POST['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM recurring_transactions WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    $rec = $stmt->fetch();

    if (!$rec) {
        json_response(false, 'Recurring item not found.', [], 404);
    }

    $db->beginTransaction();
    try {
        // 1. Create transaction
        $payDate = date('Y-m-d');
        $desc = "Recurring: " . $rec['title'] . ($rec['notes'] ? " - " . $rec['notes'] : "");
        $stmtTx = $db->prepare("
            INSERT INTO transactions (user_id, category_id, type, amount, date, time, payment_method, notes, recurring_id)
            VALUES (?, ?, ?, ?, ?, CURTIME(), ?, ?, ?)
        ");
        $stmtTx->execute([$userId, $rec['category_id'], $rec['type'], $rec['amount'], $payDate, $rec['payment_method'], $desc, $id]);

        // 2. Advance next due date
        $currentDue = $rec['next_due_date'];
        $frequency = $rec['frequency'];
        $nextDate = match ($frequency) {
            'daily' => date('Y-m-d', strtotime('+1 day', strtotime($currentDue))),
            'weekly' => date('Y-m-d', strtotime('+1 week', strtotime($currentDue))),
            'monthly' => date('Y-m-d', strtotime('+1 month', strtotime($currentDue))),
            'quarterly' => date('Y-m-d', strtotime('+3 months', strtotime($currentDue))),
            'yearly' => date('Y-m-d', strtotime('+1 year', strtotime($currentDue))),
            default => date('Y-m-d', strtotime('+1 month', strtotime($currentDue))),
        };

        $stmtUp = $db->prepare("UPDATE recurring_transactions SET next_due_date = ? WHERE id = ?");
        $stmtUp->execute([$nextDate, $id]);

        $db->commit();
        json_response(true, "Transaction recorded for '{$rec['title']}'. Next due: " . date('d M Y', strtotime($nextDate)));
    } catch (Exception $e) {
        $db->rollBack();
        json_response(false, 'Failed to process recurring item: ' . $e->getMessage(), [], 500);
    }
}

if ($action === 'toggle_active') {
    require_csrf_token();

    $id = (int)($_POST['id'] ?? 0);
    $stmt = $db->prepare("UPDATE recurring_transactions SET is_active = NOT is_active WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);

    json_response(true, 'Status updated.');
}

if ($action === 'delete') {
    require_csrf_token();

    $id = (int)($_POST['id'] ?? 0);
    $stmt = $db->prepare("DELETE FROM recurring_transactions WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);

    json_response(true, 'Recurring transaction deleted.');
}

if ($action === 'list') {
    $stmt = $db->prepare("
        SELECT r.*, c.name as category_name, c.icon, c.color, c.bg_color
        FROM recurring_transactions r
        LEFT JOIN categories c ON r.category_id = c.id
        WHERE r.user_id = ?
        ORDER BY r.next_due_date ASC
    ");
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll();

    foreach ($items as &$item) {
        $item['formatted_amount'] = format_currency($item['amount']);
        $item['formatted_due_date'] = date('d M Y', strtotime($item['next_due_date']));
        $item['is_due_soon'] = ($item['next_due_date'] <= date('Y-m-d', strtotime('+3 days')));
        $item['is_overdue'] = ($item['next_due_date'] < date('Y-m-d'));
    }

    json_response(true, 'Recurring items loaded.', ['recurring' => $items]);
}

json_response(false, 'Invalid request action.', [], 400);

