<?php
/**
 * Finance Calendar Events API Endpoint
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();
header('Content-Type: application/json; charset=utf-8');

$userId = current_user_id();
$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-t');
$db = get_db();

$events = [];

// 1. Transactions (Expenses and Income)
$stmt = $db->prepare("
    SELECT t.*, c.name as category_name 
    FROM transactions t
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ? AND t.date BETWEEN ? AND ?
");
$stmt->execute([$userId, substr($start, 0, 10), substr($end, 0, 10)]);
$txs = $stmt->fetchAll();

foreach ($txs as $tx) {
    $isExpense = ($tx['type'] === 'expense');
    $title = ($isExpense ? '-' : '+') . format_currency($tx['amount']) . ' ' . ($tx['category_name'] ?: $tx['notes']);
    $events[] = [
        'id' => 'tx_' . $tx['id'],
        'title' => $title,
        'start' => $tx['date'] . ($tx['time'] ? 'T' . $tx['time'] : ''),
        'backgroundColor' => $isExpense ? '#FFEBEE' : '#E8F5E9',
        'borderColor' => $isExpense ? '#FFCDD2' : '#C8E6C9',
        'textColor' => $isExpense ? '#C62828' : '#2E7D32',
        'extendedProps' => [
            'type' => $tx['type'],
            'amount' => $tx['amount'],
            'category' => $tx['category_name'],
            'notes' => $tx['notes'],
            'payment_method' => $tx['payment_method']
        ]
    ];
}

// 2. Active EMI Due Dates this period
$stmtEmi = $db->prepare("SELECT * FROM emis WHERE user_id = ? AND status = 'active'");
$stmtEmi->execute([$userId]);
$emis = $stmtEmi->fetchAll();

$periodStart = new DateTime(substr($start, 0, 10));
$periodEnd = new DateTime(substr($end, 0, 10));

foreach ($emis as $emi) {
    $dueDay = (int)$emi['due_day'];
    $cursor = clone $periodStart;
    $cursor->modify('first day of this month');

    while ($cursor <= $periodEnd) {
        $daysInMonth = (int)$cursor->format('t');
        $actualDay = min($dueDay, $daysInMonth);
        $dueDateTime = DateTime::createFromFormat('Y-m-d', $cursor->format('Y-m-') . sprintf('%02d', $actualDay));

        if ($dueDateTime >= $periodStart && $dueDateTime <= $periodEnd) {
            $events[] = [
                'id' => 'emi_due_' . $emi['id'] . '_' . $cursor->format('Ym'),
                'title' => '🏦 EMI Due: ' . $emi['loan_name'] . ' (' . format_currency($emi['emi_amount']) . ')',
                'start' => $dueDateTime->format('Y-m-d'),
                'backgroundColor' => '#F3E8FF',
                'borderColor' => '#D8B4FE',
                'textColor' => '#6B21A8',
                'extendedProps' => [
                    'type' => 'emi_due',
                    'lender' => $emi['lender'],
                    'amount' => $emi['emi_amount']
                ]
            ];
        }
        $cursor->modify('+1 month');
    }
}

// 3. Recurring Bills Next Due
$stmtRec = $db->prepare("SELECT * FROM recurring_transactions WHERE user_id = ? AND is_active = 1 AND next_due_date BETWEEN ? AND ?");
$stmtRec->execute([$userId, substr($start, 0, 10), substr($end, 0, 10)]);
$recurring = $stmtRec->fetchAll();

foreach ($recurring as $rec) {
    $events[] = [
        'id' => 'rec_' . $rec['id'],
        'title' => '🔄 Bill: ' . $rec['title'] . ' (' . format_currency($rec['amount']) . ')',
        'start' => $rec['next_due_date'],
        'backgroundColor' => '#FFF8E1',
        'borderColor' => '#FFE082',
        'textColor' => '#E65100',
        'extendedProps' => [
            'type' => 'recurring_bill',
            'amount' => $rec['amount']
        ]
    ];
}

echo json_encode($events);
exit;

