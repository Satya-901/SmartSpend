<?php
/**
 * Expenses Management Screen
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_auth();

$pageTitle = "Expenses";
$user = current_user();
$userId = $user['id'];
$currencySymbol = $user['currency_symbol'] ?? DEFAULT_CURRENCY_SYMBOL;
$db = get_db();

$curMonth = (int)date('m');
$curYear = (int)date('Y');

// Total Expense this month
$stmtSum = $db->prepare("
    SELECT 
        COALESCE(SUM(amount), 0) as total,
        COUNT(id) as count
    FROM transactions 
    WHERE user_id = ? AND type = 'expense' AND MONTH(date) = ? AND YEAR(date) = ?
");
$stmtSum->execute([$userId, $curMonth, $curYear]);
$stats = $stmtSum->fetch();
$totalExpense = (float)$stats['total'];
$expenseCount = (int)$stats['count'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="dashboard.php" class="btn-icon-circle text-decoration-none">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div>
            <h3 class="fw-bold mb-0" style="letter-spacing: -0.4px;">Expenses</h3>
            <p class="text-secondary small mb-0">Track and categorize your spending</p>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="export/csv.php?type=transactions" class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm text-secondary">
            <i class="fa-solid fa-file-csv text-success me-1"></i> Export CSV
        </a>
        <button class="btn btn-sm btn-primary-smart px-3" data-bs-toggle="modal" data-bs-target="#quickAddModal">
            <i class="fa-solid fa-plus me-1"></i> Add Expense
        </button>
    </div>
</div>

<!-- KPI Cards Row -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-xl-6">
        <div class="stat-pill-card expense-card h-100">
            <div class="stat-card-title">Total Spending</div>
            <div class="stat-amount">
                <span><?= format_currency($totalExpense) ?></span>
                <i class="fa-solid fa-caret-down indicator-arrow"></i>
            </div>
            <div class="stat-period">This Month (<?= date('F Y') ?>)</div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-6">
        <div class="stat-pill-card h-100" style="background: #FFF8E1;">
            <div class="stat-card-title text-warning-emphasis">Logged Outflows</div>
            <div class="stat-amount text-warning-emphasis">
                <span><?= number_format($expenseCount) ?></span>
                <small class="fs-6 fw-semibold text-muted ms-1">entries</small>
            </div>
            <div class="stat-period">Recorded transactions this month</div>
        </div>
    </div>
</div>

<!-- Expenses List -->
<div class="section-header">
    <h5 class="section-title">All Expenses</h5>
</div>

<div id="expensesContainer">
    <div class="text-center py-5 text-muted">
        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
        <span class="ms-2">Loading expenses...</span>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $.getJSON('api/transactions.php', { action: 'list', type: 'expense' }, function(res) {
        const container = $('#expensesContainer');
        if (!res.success || res.count === 0) {
            container.html(`
                <div class="empty-state-box">
                    <div class="empty-state-icon" style="background: #FEEFEF; color: #D33D3D;">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <h6>No expenses recorded</h6>
                    <p>Tap "Add" or (+) to record your expenses</p>
                </div>
            `);
            return;
        }

        let html = '';
        for (const [dayLabel, items] of Object.entries(res.grouped)) {
            html += `<div class="group-date-label">${dayLabel}</div><div class="transactions-group">`;
            for (const item of items) {
                let receiptIcon = item.receipt_file ? `<i class="fa-solid fa-paperclip text-muted ms-1" title="Has receipt"></i>` : '';
                html += `
                    <div class="transaction-card" id="tx-item-${item.id}">
                        <div class="transaction-left">
                            <div class="category-icon-box" style="background-color: ${item.bg_color || '#FFF3E0'}; color: ${item.color || '#FF9F43'};">
                                <i class="fa-solid ${item.icon || 'fa-receipt'}"></i>
                            </div>
                            <div class="transaction-details">
                                <span class="tx-title">${item.category_name || 'Other'} ${receiptIcon}</span>
                                <span class="tx-sub">${item.notes || item.payment_method}</span>
                            </div>
                        </div>
                        <div class="transaction-right">
                            <span class="tx-amount expense">${item.formatted_amount}</span>
                            <span class="tx-date-sub">${item.formatted_time || dayLabel}</span>
                        </div>
                    </div>
                `;
            }
            html += `</div>`;
        }
        container.html(html);
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
