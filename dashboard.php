<?php
/**
 * Dashboard (Home Screen)
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_auth();

$pageTitle = "Dashboard";
$user = current_user();
$userId = $user['id'];
$currencySymbol = $user['currency_symbol'] ?? DEFAULT_CURRENCY_SYMBOL;
$db = get_db();

// 1. Fetch This Month Expenses and Income from DB
$curMonth = (int)date('m');
$curYear = (int)date('Y');

$stmtMonth = $db->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as monthly_expense,
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as monthly_income
    FROM transactions 
    WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?
");
$stmtMonth->execute([$userId, $curMonth, $curYear]);
$monthTotals = $stmtMonth->fetch();

$monthlyExpense = (float)$monthTotals['monthly_expense'];
$monthlyIncome = (float)$monthTotals['monthly_income'];
$netSavings = $monthlyIncome - $monthlyExpense;

// 2. Fetch Active EMIs
$stmtEmi = $db->prepare("
    SELECT 
        COUNT(*) as count,
        COALESCE(SUM(emi_amount), 0) as commitment,
        COALESCE(SUM(outstanding_balance), 0) as total_balance
    FROM emis 
    WHERE user_id = ? AND status = 'active'
");
$stmtEmi->execute([$userId]);
$emiStats = $stmtEmi->fetch();

// 3. Fetch Category breakdown for Donut Chart
$stmtCat = $db->prepare("
    SELECT 
        COALESCE(c.name, 'Others') as category_name,
        COALESCE(c.color, '#90A4AE') as color,
        SUM(t.amount) as category_total
    FROM transactions t
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ? AND t.type = 'expense' AND MONTH(t.date) = ? AND YEAR(t.date) = ?
    GROUP BY t.category_id, c.name, c.color
    ORDER BY category_total DESC
");
$stmtCat->execute([$userId, $curMonth, $curYear]);
$categoriesBreakdown = $stmtCat->fetchAll();

$chartLabels = [];
$chartData = [];
$chartColors = [];
$legendItems = [];

// Palette matching mockup donut
$pastelPalette = ['#F87171', '#FBBF24', '#F472B6', '#A78BFA', '#38BDF8', '#4ADE80', '#94A3B8'];
$colorIdx = 0;

foreach ($categoriesBreakdown as $cat) {
    $catAmt = (float)$cat['category_total'];
    $catPct = ($monthlyExpense > 0) ? round(($catAmt / $monthlyExpense) * 100) : 0;
    $sliceColor = $pastelPalette[$colorIdx % count($pastelPalette)];
    $colorIdx++;

    $chartLabels[] = $cat['category_name'];
    $chartData[] = $catAmt;
    $chartColors[] = $sliceColor;

    $legendItems[] = [
        'name' => $cat['category_name'],
        'amount' => $catAmt,
        'formatted_amount' => format_currency($catAmt),
        'percentage' => $catPct,
        'color' => $sliceColor
    ];
}

// 4. Fetch Recent Transactions (Limit 6)
$stmtRecent = $db->prepare("
    SELECT t.*, c.name as category_name, c.icon, c.color, c.bg_color 
    FROM transactions t
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ?
    ORDER BY t.date DESC, t.time DESC, t.id DESC
    LIMIT 6
");
$stmtRecent->execute([$userId]);
$recentTransactions = $stmtRecent->fetchAll();

// 5. Fetch Active EMIs for sidebar list
$stmtEmisList = $db->prepare("SELECT * FROM emis WHERE user_id = ? AND status = 'active' ORDER BY due_day ASC LIMIT 3");
$stmtEmisList->execute([$userId]);
$activeEmis = $stmtEmisList->fetchAll();

// 6. Fetch Active Budgets
$stmtBudgets = $db->prepare("
    SELECT b.*, COALESCE(c.name, 'Overall Budget') as category_name 
    FROM budgets b 
    LEFT JOIN categories c ON b.category_id = c.id 
    WHERE b.user_id = ? AND b.month = ? AND b.year = ? 
    LIMIT 3
");
$stmtBudgets->execute([$userId, $curMonth, $curYear]);
$userBudgets = $stmtBudgets->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Top 4 KPI Summary Cards Row (Mockup Style Responsive Grid) -->
<div class="summary-cards-grid">
    <!-- Expenses Card -->
    <a href="expenses.php" class="text-decoration-none">
        <div class="stat-pill-card expense-card h-100">
            <div class="stat-card-title">Expenses</div>
            <div class="stat-amount">
                <span><?= format_currency($monthlyExpense) ?></span>
                <i class="fa-solid fa-caret-down indicator-arrow"></i>
            </div>
            <div class="stat-period">This Month</div>
        </div>
    </a>

    <!-- Income Card -->
    <a href="income.php" class="text-decoration-none">
        <div class="stat-pill-card income-card h-100">
            <div class="stat-card-title">Income</div>
            <div class="stat-amount">
                <span><?= format_currency($monthlyIncome) ?></span>
                <i class="fa-solid fa-caret-up indicator-arrow"></i>
            </div>
            <div class="stat-period">This Month</div>
        </div>
    </a>

    <!-- Active EMIs Card -->
    <a href="emis.php" class="text-decoration-none">
        <div class="stat-pill-card emi-card h-100">
            <div class="stat-card-title">Active EMIs (<?= (int)$emiStats['count'] ?>)</div>
            <div class="stat-amount">
                <span><?= format_currency($emiStats['commitment']) ?></span>
                <span style="font-size: 0.8rem; font-weight: 600;">/mo</span>
            </div>
            <div class="stat-period">Monthly Commitment</div>
        </div>
    </a>

    <!-- Net Savings Card -->
    <div class="stat-pill-card savings-card h-100">
        <div class="stat-card-title">Net Savings</div>
        <div class="stat-amount">
            <span><?= ($netSavings >= 0 ? '+' : '') . format_currency($netSavings) ?></span>
        </div>
        <div class="stat-period">Income - Expenses</div>
    </div>
</div>

<!-- Main Application Workspace (2-Column Responsive Dashboard) -->
<div class="row g-3">
    <!-- Left Column: Spending Overview & Recent Transactions -->
    <div class="col-lg-8">
        <!-- Spending Overview Card (Donut Chart & Legend) -->
        <div class="overview-card">
            <div class="card-top-bar">
                <h4 class="card-heading">Spending Overview</h4>
                <div class="dropdown">
                    <button class="dropdown-pill dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        This Month
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-4">
                        <li><a class="dropdown-item active" href="dashboard.php">This Month</a></li>
                        <li><a class="dropdown-item" href="reports.php?period=last_month">Last Month</a></li>
                        <li><a class="dropdown-item" href="reports.php?period=this_year">This Year</a></li>
                    </ul>
                </div>
            </div>

            <?php if (empty($chartData)): ?>
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <h6>No spending recorded for this month</h6>
                    <p>Click "+ Add Transaction" or the amber button to log your first expense</p>
                </div>
            <?php else: ?>
                <div class="overview-chart-row">
                    <!-- Donut Chart with Centered Total -->
                    <div class="chart-container-relative">
                        <canvas id="spendingDonutChart"></canvas>
                        <div class="chart-center-label">
                            <span class="chart-center-amount"><?= format_currency($monthlyExpense) ?></span>
                            <span class="chart-center-sub">Total</span>
                        </div>
                    </div>

                    <!-- Category Legend List -->
                    <div class="category-legend-list">
                        <?php foreach ($legendItems as $item): ?>
                            <div class="legend-item">
                                <div class="legend-left">
                                    <span class="legend-chip" style="background-color: <?= htmlspecialchars($item['color']) ?>;"></span>
                                    <span class="text-truncate" style="max-width: 140px;"><?= htmlspecialchars($item['name']) ?></span>
                                </div>
                                <div class="legend-right">
                                    <span class="legend-amount"><?= $item['formatted_amount'] ?></span>
                                    <span class="legend-pct"><?= $item['percentage'] ?>%</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Transactions Section -->
        <div class="overview-card">
            <div class="card-top-bar mb-2">
                <h4 class="card-heading">Recent Transactions</h4>
                <a href="transactions.php" class="view-all-link">View All <i class="fa-solid fa-arrow-right ms-1"></i></a>
            </div>

            <div class="transactions-group mt-3">
                <?php if (empty($recentTransactions)): ?>
                    <div class="empty-state-box py-4">
                        <div class="empty-state-icon">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                        <h6>No transactions recorded yet</h6>
                        <p>Your itemized transactions will appear here once created</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentTransactions as $tx): ?>
                        <div class="transaction-card" id="tx-item-<?= $tx['id'] ?>">
                            <div class="transaction-left">
                                <div class="category-icon-box" style="background-color: <?= !empty($tx['bg_color']) ? htmlspecialchars($tx['bg_color']) : '#FFF3E0' ?>; color: <?= !empty($tx['color']) ? htmlspecialchars($tx['color']) : '#FF9F43' ?>;">
                                    <i class="fa-solid <?= !empty($tx['icon']) ? htmlspecialchars($tx['icon']) : 'fa-receipt' ?>"></i>
                                </div>
                                <div class="transaction-details">
                                    <span class="tx-title"><?= htmlspecialchars($tx['category_name'] ?? 'Other') ?></span>
                                    <span class="tx-sub"><?= htmlspecialchars($tx['notes'] ?: $tx['payment_method']) ?></span>
                                </div>
                            </div>
                            <div class="transaction-right">
                                <span class="tx-amount <?= $tx['type'] ?>">
                                    <?= ($tx['type'] === 'expense' ? '-' : '+') . format_currency($tx['amount']) ?>
                                </span>
                                <span class="tx-date-sub">
                                    <?= format_relative_day($tx['date']) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Loans & Commitments, Budgets, and Insights -->
    <div class="col-lg-4">
        <!-- Active EMIs Card -->
        <div class="overview-card mb-3">
            <div class="card-top-bar">
                <h5 class="card-heading" style="font-size: 1rem;"><i class="fa-solid fa-landmark text-purple me-1"></i> Loan Commitments</h5>
                <a href="emis.php" class="view-all-link">Manage</a>
            </div>

            <?php if (empty($activeEmis)): ?>
                <div class="text-center py-4 text-muted">
                    <p class="small mb-2">No active loans being tracked.</p>
                    <a href="emis.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">+ Add Loan / EMI</a>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($activeEmis as $emi): ?>
                        <div class="p-3 rounded-4" style="background: #FBF8F5; border: 1px solid var(--border-soft);">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="fw-bold mb-0 text-truncate" style="max-width: 160px;"><?= htmlspecialchars($emi['loan_name']) ?></h6>
                                <span class="badge badge-soft-purple"><?= format_currency($emi['emi_amount']) ?>/mo</span>
                            </div>
                            <small class="text-muted d-block mb-2"><?= htmlspecialchars($emi['lender']) ?> · Due day <?= $emi['due_day'] ?>th</small>
                            <div class="d-flex justify-content-between small text-muted mb-1" style="font-size: 0.72rem;">
                                <span>Outstanding: <strong><?= format_currency($emi['outstanding_balance']) ?></strong></span>
                                <span>Principal: <?= format_currency($emi['principal_amount']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Monthly Budgets Progress Widget -->
        <div class="overview-card mb-3">
            <div class="card-top-bar">
                <h5 class="card-heading" style="font-size: 1rem;"><i class="fa-solid fa-wallet text-warning me-1"></i> Monthly Budgets</h5>
                <a href="budgets.php" class="view-all-link">Set Budget</a>
            </div>

            <?php if (empty($userBudgets)): ?>
                <div class="text-center py-4 text-muted">
                    <p class="small mb-2">No spending limits set this month.</p>
                    <a href="budgets.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Set a Limit</a>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($userBudgets as $b): 
                        $bAmt = (float)$b['amount'];
                        $bSpent = 0;
                        if ($b['category_id']) {
                            $stmtS = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = ? AND category_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
                            $stmtS->execute([$userId, $b['category_id'], $curMonth, $curYear]);
                            $bSpent = (float)$stmtS->fetchColumn();
                        } else {
                            $bSpent = $monthlyExpense;
                        }
                        $bPct = ($bAmt > 0) ? min(100, round(($bSpent / $bAmt) * 100)) : 0;
                    ?>
                        <div>
                            <div class="d-flex justify-content-between small fw-bold mb-1">
                                <span><?= htmlspecialchars($b['category_name']) ?></span>
                                <span><?= format_currency($bSpent) ?> / <?= format_currency($bAmt) ?></span>
                            </div>
                            <div class="progress-smart">
                                <div class="progress-bar <?= $bPct >= 90 ? 'bg-danger' : ($bPct >= 75 ? 'bg-warning' : 'bg-success') ?>" style="width: <?= $bPct ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Encouragement Card (Mockup Style) -->
        <div class="encouragement-card mb-3">
            <div class="encouragement-content">
                <div class="encouragement-icon">
                    <i class="fa-solid fa-seedling"></i>
                </div>
                <div class="encouragement-text">
                    <h5>Smart Insights 🌱</h5>
                    <p>Track regularly to save more and reduce debt faster.</p>
                </div>
            </div>
            <div class="flex-shrink-0">
                <img src="assets/img/default-avatar.svg" width="38" height="38" alt="Cat Mascot" style="border-radius: 50%;">
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($chartData)): ?>
    const ctx = document.getElementById('spendingDonutChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                data: <?= json_encode($chartData) ?>,
                backgroundColor: <?= json_encode($chartColors) ?>,
                borderWidth: 2,
                borderColor: '#FFFFFF',
                hoverOffset: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ' ' + context.label + ': <?= $currencySymbol ?>' + Number(context.raw).toLocaleString();
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
