<?php
/**
 * Transactions API Endpoint
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

    $type = trim($_POST['type'] ?? 'expense');
    $amount = (float)($_POST['amount'] ?? 0);
    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $date = trim($_POST['date'] ?? date('Y-m-d'));
    $time = trim($_POST['time'] ?? date('H:i:s'));
    $paymentMethod = trim($_POST['payment_method'] ?? 'UPI');
    $notes = trim($_POST['notes'] ?? '');

    if (!in_array($type, ['expense', 'income'], true)) {
        json_response(false, 'Invalid transaction type.', [], 400);
    }

    if ($amount <= 0) {
        json_response(false, 'Amount must be greater than zero.', [], 400);
    }

    if (empty($date)) {
        json_response(false, 'Date is required.', [], 400);
    }

    $receiptPath = null;
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        try {
            $receiptPath = handle_file_upload($_FILES['receipt'], 'receipts');
        } catch (Exception $e) {
            json_response(false, $e->getMessage(), [], 400);
        }
    }

    $stmt = $db->prepare("
        INSERT INTO transactions (user_id, category_id, type, amount, date, time, payment_method, notes, receipt_file)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $success = $stmt->execute([$userId, $categoryId, $type, $amount, $date, $time, $paymentMethod, $notes, $receiptPath]);

    if ($success) {
        $txId = (int)$db->lastInsertId();

        // Check budget alerts if this is an expense
        if ($type === 'expense') {
            $txMonth = (int)date('m', strtotime($date));
            $txYear = (int)date('Y', strtotime($date));
            check_budget_alerts($userId, $txMonth, $txYear);
        }

        json_response(true, ucfirst($type) . ' added successfully!', ['transaction_id' => $txId]);
    } else {
        json_response(false, 'Failed to save transaction.', [], 500);
    }
}

if ($action === 'edit') {
    require_csrf_token();

    $id = (int)($_POST['id'] ?? 0);
    $type = trim($_POST['type'] ?? 'expense');
    $amount = (float)($_POST['amount'] ?? 0);
    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $date = trim($_POST['date'] ?? date('Y-m-d'));
    $time = trim($_POST['time'] ?? date('H:i:s'));
    $paymentMethod = trim($_POST['payment_method'] ?? 'UPI');
    $notes = trim($_POST['notes'] ?? '');

    // Check ownership
    $stmtCheck = $db->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ?");
    $stmtCheck->execute([$id, $userId]);
    $existing = $stmtCheck->fetch();

    if (!$existing) {
        json_response(false, 'Transaction not found.', [], 404);
    }

    if ($amount <= 0) {
        json_response(false, 'Amount must be greater than zero.', [], 400);
    }

    $receiptPath = $existing['receipt_file'];
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        try {
            $receiptPath = handle_file_upload($_FILES['receipt'], 'receipts');
            // Remove old receipt if exists
            if (!empty($existing['receipt_file']) && file_exists(__DIR__ . '/../' . $existing['receipt_file'])) {
                @unlink(__DIR__ . '/../' . $existing['receipt_file']);
            }
        } catch (Exception $e) {
            json_response(false, $e->getMessage(), [], 400);
        }
    }

    $stmt = $db->prepare("
        UPDATE transactions 
        SET category_id = ?, type = ?, amount = ?, date = ?, time = ?, payment_method = ?, notes = ?, receipt_file = ?
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([$categoryId, $type, $amount, $date, $time, $paymentMethod, $notes, $receiptPath, $id, $userId]);

    json_response(true, 'Transaction updated successfully!');
}

if ($action === 'delete') {
    require_csrf_token();

    $id = (int)($_POST['id'] ?? 0);
    
    // Check ownership
    $stmtCheck = $db->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ?");
    $stmtCheck->execute([$id, $userId]);
    $existing = $stmtCheck->fetch();

    if (!$existing) {
        json_response(false, 'Transaction not found.', [], 404);
    }

    // If attached to EMI payment, warn or prevent inconsistency
    if (!empty($existing['emi_id'])) {
        json_response(false, 'This transaction was automatically recorded from an EMI payment. Please manage or remove it from the EMI Management section.', [], 400);
    }

    if (!empty($existing['receipt_file']) && file_exists(__DIR__ . '/../' . $existing['receipt_file'])) {
        @unlink(__DIR__ . '/../' . $existing['receipt_file']);
    }

    $stmt = $db->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);

    json_response(true, 'Transaction deleted successfully.');
}

if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("
        SELECT t.*, c.name as category_name, c.icon, c.color, c.bg_color 
        FROM transactions t
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.id = ? AND t.user_id = ?
    ");
    $stmt->execute([$id, $userId]);
    $tx = $stmt->fetch();

    if (!$tx) {
        json_response(false, 'Transaction not found.', [], 404);
    }

    json_response(true, 'Transaction found.', ['transaction' => $tx]);
}

if ($action === 'list') {
    $type = trim($_GET['type'] ?? ''); // 'all', 'expense', 'income'
    $categoryId = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null;
    $dateFrom = trim($_GET['date_from'] ?? '');
    $dateTo = trim($_GET['date_to'] ?? '');
    $search = trim($_GET['search'] ?? '');
    $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 100;

    $sql = "
        SELECT t.*, c.name as category_name, c.icon, c.color, c.bg_color 
        FROM transactions t
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ?
    ";
    $params = [$userId];

    if ($type === 'expense' || $type === 'income') {
        $sql .= " AND t.type = ?";
        $params[] = $type;
    }

    if ($categoryId) {
        $sql .= " AND t.category_id = ?";
        $params[] = $categoryId;
    }

    if (!empty($dateFrom)) {
        $sql .= " AND t.date >= ?";
        $params[] = $dateFrom;
    }

    if (!empty($dateTo)) {
        $sql .= " AND t.date <= ?";
        $params[] = $dateTo;
    }

    if (!empty($search)) {
        $sql .= " AND (t.notes LIKE ? OR c.name LIKE ? OR t.payment_method LIKE ? OR t.amount LIKE ?)";
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $sql .= " ORDER BY t.date DESC, t.time DESC, t.id DESC LIMIT " . (int)$limit;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Group rows by relative day label: "Today", "Yesterday", or "12 May 2024"
    $grouped = [];
    $totalExpense = 0;
    $totalIncome = 0;

    foreach ($rows as $row) {
        $dayLabel = format_relative_day($row['date']);
        if (!isset($grouped[$dayLabel])) {
            $grouped[$dayLabel] = [];
        }

        // Format amount with sign
        $sign = ($row['type'] === 'expense') ? '-' : '+';
        $row['formatted_amount'] = $sign . format_currency($row['amount']);
        $row['formatted_time'] = !empty($row['time']) ? date('h:i A', strtotime($row['time'])) : '';

        if ($row['type'] === 'expense') {
            $totalExpense += (float)$row['amount'];
        } else {
            $totalIncome += (float)$row['amount'];
        }

        $grouped[$dayLabel][] = $row;
    }

    json_response(true, 'Transactions retrieved.', [
        'grouped' => $grouped,
        'count' => count($rows),
        'total_expense' => $totalExpense,
        'total_income' => $totalIncome,
        'formatted_total_expense' => format_currency($totalExpense),
        'formatted_total_income' => format_currency($totalIncome)
    ]);
}

json_response(false, 'Invalid request action.', [], 400);

