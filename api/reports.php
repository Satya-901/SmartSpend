<?php
/**
 * Reports & Analytics API Endpoint
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();
header('Content-Type: application/json; charset=utf-8');

$userId = current_user_id();
$period = $_GET['period'] ?? 'this_month'; // this_month, last_month, this_week, this_year, all
$db = get_db();

// Determine date boundaries based on period
$today = date('Y-m-d');
switch ($period) {
    case 'this_week':
        $startDate = date('Y-m-d', strtotime('monday this week'));
        $endDate = date('Y-m-d', strtotime('sunday this week'));
        $prevStartDate = date('Y-m-d', strtotime('monday last week'));
        $prevEndDate = date('Y-m-d', strtotime('sunday last week'));
        break;
    case 'last_month':
        $startDate = date('Y-m-01', strtotime('first day of last month'));
        $endDate = date('Y-m-t', strtotime('last day of last month'));
        $prevStartDate = date('Y-m-01', strtotime('-2 months'));
        $prevEndDate = date('Y-m-t', strtotime('-2 months'));
        break;
    case 'this_year':
        $startDate = date('Y-01-01');
        $endDate = date('Y-12-31');
        $prevStartDate = date('Y-01-01', strtotime('-1 year'));
        $prevEndDate = date('Y-12-31', strtotime('-1 year'));
        break;
    case 'this_month':
    default:
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
        $prevStartDate = date('Y-m-01', strtotime('first day of last month'));
        $prevEndDate = date('Y-m-t', strtotime('last day of last month'));
        break;
}

// 1. Total Expenses and Total Income in current period
$stmtSum = $db->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as total_expense,
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as total_income
    FROM transactions 
    WHERE user_id = ? AND date BETWEEN ? AND ?
");
$stmtSum->execute([$userId, $startDate, $endDate]);
$totals = $stmtSum->fetch();
$totalExpense = (float)$totals['total_expense'];
$totalIncome = (float)$totals['total_income'];
$netSavings = $totalIncome - $totalExpense;

// 2. Previous period expenses for comparison percentage
$stmtPrev = $db->prepare("
    SELECT COALESCE(SUM(amount), 0) FROM transactions 
    WHERE user_id = ? AND type = 'expense' AND date BETWEEN ? AND ?
");
$stmtPrev->execute([$userId, $prevStartDate, $prevEndDate]);
$prevExpense = (float)$stmtPrev->fetchColumn();

$pctChange = 0;
$pctDirection = 'neutral';
if ($prevExpense > 0) {
    $pctChange = round((($totalExpense - $prevExpense) / $prevExpense) * 100, 1);
    $pctDirection = ($pctChange >= 0) ? 'up' : 'down';
}

// 3. Category Breakdown (Expenses Donut Chart)
$stmtCat = $db->prepare("
    SELECT 
        COALESCE(c.name, 'Others') as category_name,
        COALESCE(c.color, '#90A4AE') as color,
        COALESCE(c.icon, 'fa-shapes') as icon,
        SUM(t.amount) as category_total
    FROM transactions t
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ? AND t.type = 'expense' AND t.date BETWEEN ? AND ?
    GROUP BY t.category_id, c.name, c.color, c.icon
    ORDER BY category_total DESC
");
$stmtCat->execute([$userId, $startDate, $endDate]);
$categories = $stmtCat->fetchAll();

$donutLabels = [];
$donutData = [];
$donutColors = [];
$legendItems = [];

foreach ($categories as $cat) {
    $catAmt = (float)$cat['category_total'];
    $catPct = ($totalExpense > 0) ? round(($catAmt / $totalExpense) * 100) : 0;

    $donutLabels[] = $cat['category_name'];
    $donutData[] = $catAmt;
    $donutColors[] = $cat['color'];

    $legendItems[] = [
        'name' => $cat['category_name'],
        'amount' => $catAmt,
        'formatted_amount' => format_currency($catAmt),
        'percentage' => $catPct,
        'color' => $cat['color'],
        'icon' => $cat['icon']
    ];
}

// 4. Daily Spending Trend (Last 7 Days or Days of Week: Mon, Tue, Wed, Thu, Fri, Sat, Sun)
$trendDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$trendData = array_fill(0, 7, 0);

// We'll calculate current week (Mon-Sun)
$mon = date('Y-m-d', strtotime('monday this week'));
for ($i = 0; $i < 7; $i++) {
    $dayDate = date('Y-m-d', strtotime("+$i days", strtotime($mon)));
    $stmtDay = $db->prepare("
        SELECT COALESCE(SUM(amount), 0) FROM transactions 
        WHERE user_id = ? AND type = 'expense' AND date = ?
    ");
    $stmtDay->execute([$userId, $dayDate]);
    $trendData[$i] = (float)$stmtDay->fetchColumn();
}

// 5. Active EMIs summary
$stmtEmi = $db->prepare("
    SELECT 
        COUNT(*) as active_count,
        COALESCE(SUM(emi_amount), 0) as monthly_commitment,
        COALESCE(SUM(outstanding_balance), 0) as total_outstanding
    FROM emis 
    WHERE user_id = ? AND status = 'active'
");
$stmtEmi->execute([$userId]);
$emiStats = $stmtEmi->fetch();

json_response(true, 'Analytics data retrieved.', [
    'period' => $period,
    'total_expense' => $totalExpense,
    'formatted_total_expense' => format_currency($totalExpense),
    'total_income' => $totalIncome,
    'formatted_total_income' => format_currency($totalIncome),
    'net_savings' => $netSavings,
    'formatted_net_savings' => format_currency($netSavings),
    'prev_expense' => $prevExpense,
    'pct_change' => abs($pctChange),
    'pct_direction' => $pctDirection,
    'donut' => [
        'labels' => $donutLabels,
        'data' => $donutData,
        'colors' => $donutColors
    ],
    'legend' => $legendItems,
    'trend' => [
        'labels' => $trendDays,
        'data' => $trendData
    ],
    'emi_stats' => [
        'active_count' => (int)$emiStats['active_count'],
        'monthly_commitment' => (float)$emiStats['monthly_commitment'],
        'formatted_monthly_commitment' => format_currency($emiStats['monthly_commitment']),
        'total_outstanding' => (float)$emiStats['total_outstanding'],
        'formatted_total_outstanding' => format_currency($emiStats['total_outstanding'])
    ]
]);

