<?php
/**
 * Savings Goals API Endpoint
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
    $targetAmount = (float)($_POST['target_amount'] ?? 0);
    $initialAmount = (float)($_POST['initial_amount'] ?? 0);
    $targetDate = trim($_POST['target_date'] ?? date('Y-m-d', strtotime('+6 months')));
    $icon = trim($_POST['category_icon'] ?? 'fa-bullseye');
    $color = trim($_POST['color'] ?? '#10B981');

    if (empty($title) || $targetAmount <= 0) {
        json_response(false, 'Goal title and target amount are required.', [], 400);
    }

    $stmt = $db->prepare("
        INSERT INTO savings_goals (user_id, title, target_amount, current_amount, target_date, category_icon, color, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
    ");

    if ($stmt->execute([$userId, $title, $targetAmount, $initialAmount, $targetDate, $icon, $color])) {
        $goalId = (int)$db->lastInsertId();

        if ($initialAmount > 0) {
            $stmtC = $db->prepare("
                INSERT INTO savings_contributions (goal_id, user_id, amount, type, date, notes)
                VALUES (?, ?, ?, 'deposit', CURDATE(), 'Initial deposit')
            ");
            $stmtC->execute([$goalId, $userId, $initialAmount]);
        }

        json_response(true, 'Savings goal created successfully!', ['goal_id' => $goalId]);
    } else {
        json_response(false, 'Failed to create goal.', [], 500);
    }
}

if ($action === 'contribute') {
    require_csrf_token();

    $goalId = (int)($_POST['goal_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $type = trim($_POST['type'] ?? 'deposit'); // 'deposit' or 'withdraw'
    $notes = trim($_POST['notes'] ?? '');

    $stmt = $db->prepare("SELECT * FROM savings_goals WHERE id = ? AND user_id = ?");
    $stmt->execute([$goalId, $userId]);
    $goal = $stmt->fetch();

    if (!$goal) {
        json_response(false, 'Goal not found.', [], 404);
    }

    if ($amount <= 0) {
        json_response(false, 'Amount must be greater than zero.', [], 400);
    }

    $current = (float)$goal['current_amount'];
    if ($type === 'withdraw') {
        if ($amount > $current) {
            json_response(false, 'Cannot withdraw more than current savings in this goal.', [], 400);
        }
        $newAmount = $current - $amount;
    } else {
        $newAmount = $current + $amount;
    }

    $target = (float)$goal['target_amount'];
    $newStatus = ($newAmount >= $target) ? 'achieved' : 'active';

    $db->beginTransaction();
    try {
        $stmtC = $db->prepare("
            INSERT INTO savings_contributions (goal_id, user_id, amount, type, date, notes)
            VALUES (?, ?, ?, ?, CURDATE(), ?)
        ");
        $stmtC->execute([$goalId, $userId, $amount, $type, $notes]);

        $stmtUp = $db->prepare("UPDATE savings_goals SET current_amount = ?, status = ? WHERE id = ?");
        $stmtUp->execute([$newAmount, $newStatus, $goalId]);

        $db->commit();
        json_response(true, ucfirst($type) . ' of ' . format_currency($amount) . ' recorded successfully!');
    } catch (Exception $e) {
        $db->rollBack();
        json_response(false, 'Transaction failed: ' . $e->getMessage(), [], 500);
    }
}

if ($action === 'delete') {
    require_csrf_token();

    $id = (int)($_POST['id'] ?? 0);
    $stmt = $db->prepare("DELETE FROM savings_goals WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);

    json_response(true, 'Savings goal deleted.');
}

if ($action === 'list') {
    $stmt = $db->prepare("SELECT * FROM savings_goals WHERE user_id = ? ORDER BY status ASC, target_date ASC");
    $stmt->execute([$userId]);
    $goals = $stmt->fetchAll();

    $totalSaved = 0;
    $totalTarget = 0;

    foreach ($goals as &$g) {
        $cur = (float)$g['current_amount'];
        $tgt = (float)$g['target_amount'];
        $pct = ($tgt > 0) ? min(100, round(($cur / $tgt) * 100, 1)) : 0;
        $rem = max(0, $tgt - $cur);

        $g['percentage'] = $pct;
        $g['remaining'] = $rem;
        $g['formatted_current'] = format_currency($cur);
        $g['formatted_target'] = format_currency($tgt);
        $g['formatted_remaining'] = format_currency($rem);
        $g['formatted_target_date'] = date('d M Y', strtotime($g['target_date']));

        $totalSaved += $cur;
        $totalTarget += $tgt;
    }

    json_response(true, 'Goals loaded.', [
        'goals' => $goals,
        'total_saved' => $totalSaved,
        'total_target' => $totalTarget,
        'formatted_total_saved' => format_currency($totalSaved),
        'formatted_total_target' => format_currency($totalTarget)
    ]);
}

json_response(false, 'Invalid request action.', [], 400);

