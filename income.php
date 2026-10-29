<?php
/**
 * Income Management Screen
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_auth();

$pageTitle = "Income";
$user = current_user();
$userId = $user['id'];
$currencySymbol = $user['currency_symbol'] ?? DEFAULT_CURRENCY_SYMBOL;
$db = get_db();

$curMonth = (int)date('m');
$curYear = (int)date('Y');

// Total Income this month
$stmtSum = $db->prepare("
    SELECT 
        COALESCE(SUM(amount), 0) as total,
        COUNT(id) as count
    FROM transactions 
    WHERE user_id = ? AND type = 'income' AND MONTH(date) = ? AND YEAR(date) = ?
");
$stmtSum->execute([$userId, $curMonth, $curYear]);
$stats = $stmtSum->fetch();
$totalIncome = (float)$stats['total'];
$incomeCount = (int)$stats['count'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="dashboard.php" class="btn-icon-circle text-decoration-none">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div>
            <h3 class="fw-bold mb-0" style="letter-spacing: -0.4px;">Income</h3>
            <p class="text-secondary small mb-0">Record salary, freelance work and earnings</p>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="export/csv.php?type=transactions" class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm text-secondary">
            <i class="fa-solid fa-file-csv text-success me-1"></i> Export CSV
        </a>
        <button class="btn btn-sm btn-primary-smart px-3" data-bs-toggle="modal" data-bs-target="#quickAddModal" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
            <i class="fa-solid fa-plus me-1"></i> Add Income
        </button>
    </div>
</div>

<!-- KPI Cards Row -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-xl-6">
        <div class="stat-pill-card income-card h-100">
            <div class="stat-card-title">Total Earnings</div>
            <div class="stat-amount">
                <span><?= format_currency($totalIncome) ?></span>
                <i class="fa-solid fa-caret-up indicator-arrow"></i>
            </div>
            <div class="stat-period">This Month (<?= date('F Y') ?>)</div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-6">
        <div class="stat-pill-card h-100" style="background: #EDF7EE;">
            <div class="stat-card-title" style="color: #1E7538;">Recorded Inflows</div>
            <div class="stat-amount" style="color: #1E7538;">
                <span><?= number_format($incomeCount) ?></span>
                <small class="fs-6 fw-semibold text-muted ms-1">entries</small>
            </div>
            <div class="stat-period">Logged income deposits this month</div>
        </div>
    </div>
</div>

<!-- Income List -->
<div class="section-header">
    <h5 class="section-title">Income Sources</h5>
</div>

<div id="incomeContainer">
    <div class="text-center py-5 text-muted">
        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
        <span class="ms-2">Loading income streams...</span>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $.getJSON('api/transactions.php', { action: 'list', type: 'income' }, function(res) {
        const container = $('#incomeContainer');
        if (!res.success || res.count === 0) {
            container.html(`
                <div class="empty-state-box">
                    <div class="empty-state-icon" style="background: #EDF7EE; color: #1E7538;">
                        <i class="fa-solid fa-money-bill-wave"></i>
                    </div>
                    <h6>No income logged</h6>
                    <p>Tap "Add" or (+) to record salary, freelancing, or investments</p>
                </div>
            `);
            return;
        }

        let html = '';
        for (const [dayLabel, items] of Object.entries(res.grouped)) {
            html += `<div class="group-date-label">${dayLabel}</div><div class="transactions-group">`;
            for (const item of items) {
                html += `
                    <div class="transaction-card" id="tx-item-${item.id}">
                        <div class="transaction-left">
                            <div class="category-icon-box" style="background-color: ${item.bg_color || '#E8F5E9'}; color: ${item.color || '#4CAF50'};">
                                <i class="fa-solid ${item.icon || 'fa-money-bill-wave'}"></i>
                            </div>
                            <div class="transaction-details">
                                <span class="tx-title">${item.category_name || 'Income'}</span>
                                <span class="tx-sub">${item.notes || item.payment_method}</span>
                            </div>
                        </div>
                        <div class="transaction-right">
                            <span class="tx-amount income">${item.formatted_amount}</span>
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
