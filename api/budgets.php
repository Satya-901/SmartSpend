<?php
/**
 * Budgets API Endpoint
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

if ($action === 'set') {
    require_csrf_token();

    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $month = (int)($_POST['month'] ?? date('m'));
    $year = (int)($_POST['year'] ?? date('Y'));
    $amount = (float)($_POST['amount'] ?? 0);
    $threshold = (int)($_POST['alert_threshold'] ?? 80);

    if ($amount <= 0) {
        json_response(false, 'Budget amount must be greater than zero.', [], 400);
    }

    if ($month < 1 || $month > 12) {
        json_response(false, 'Invalid month.', [], 400);
    }

    // Insert or update on duplicate key
    $stmt = $db->prepare("
        INSERT INTO budgets (user_id, category_id, month, year, amount, alert_threshold)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE amount = VALUES(amount), alert_threshold = VALUES(alert_threshold)
    ");

    if ($stmt->execute([$userId, $categoryId, $month, $year, $amount, $threshold])) {
        // Run alert check
        check_budget_alerts($userId, $month, $year);
        json_response(true, 'Budget saved successfully!');
    } else {
        json_response(false, 'Failed to save budget.', [], 500);
    }
}

if ($action === 'delete') {
    require_csrf_token();

    $id = (int)($_POST['id'] ?? 0);
    $stmt = $db->prepare("DELETE FROM budgets WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);

    json_response(true, 'Budget deleted successfully.');
}

if ($action === 'list') {
    $month = (int)($_GET['month'] ?? date('m'));
    $year = (int)($_GET['year'] ?? date('Y'));

    $stmt = $db->prepare("
        SELECT b.*, c.name as category_name, c.icon, c.color, c.bg_color 
        FROM budgets b
        LEFT JOIN categories c ON b.category_id = c.id
        WHERE b.user_id = ? AND b.month = ? AND b.year = ?
        ORDER BY (b.category_id IS NULL) DESC, b.amount DESC
    ");
    $stmt->execute([$userId, $month, $year]);
    $budgets = $stmt->fetchAll();

    $totalBudget = 0;
    $totalSpent = 0;

    foreach ($budgets as &$b) {
        $catId = $b['category_id'];
        if ($catId) {
            $stmtSpent = $db->prepare("
                SELECT COALESCE(SUM(amount), 0) FROM transactions 
                WHERE user_id = ? AND type = 'expense' AND category_id = ? 
                AND MONTH(date) = ? AND YEAR(date) = ?
            ");
            $stmtSpent->execute([$userId, $catId, $month, $year]);
        } else {
            $stmtSpent = $db->prepare("
                SELECT COALESCE(SUM(amount), 0) FROM transactions 
                WHERE user_id = ? AND type = 'expense' 
                AND MONTH(date) = ? AND YEAR(date) = ?
            ");
            $stmtSpent->execute([$userId, $month, $year]);
        }

        $spent = (float)$stmtSpent->fetchColumn();
        $budgetAmt = (float)$b['amount'];
        $percentage = ($budgetAmt > 0) ? round(($spent / $budgetAmt) * 100, 1) : 0;
        $remaining = max(0, $budgetAmt - $spent);

        $b['spent'] = $spent;
        $b['percentage'] = $percentage;
        $b['remaining'] = $remaining;
        $b['formatted_budget'] = format_currency($budgetAmt);
        $b['formatted_spent'] = format_currency($spent);
        $b['formatted_remaining'] = format_currency($remaining);

        if ($b['category_id'] === null) {
            $b['category_name'] = 'Overall Monthly Budget';
            $b['icon'] = 'fa-wallet';
            $b['color'] = '#FF9F43';
            $b['bg_color'] = '#FFF3E0';
        }

        $totalBudget += $budgetAmt;
        $totalSpent += $spent;
    }

    json_response(true, 'Budgets loaded.', [
        'budgets' => $budgets,
        'total_budget' => $totalBudget,
        'total_spent' => $totalSpent,
        'formatted_total_budget' => format_currency($totalBudget),
        'formatted_total_spent' => format_currency($totalSpent)
    ]);
}

json_response(false, 'Invalid request action.', [], 400);

