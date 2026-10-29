<?php
/**
 * Budgets Screen
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_auth();

$pageTitle = "Monthly Budgets";
$user = current_user();
$userId = $user['id'];
$currencySymbol = $user['currency_symbol'] ?? DEFAULT_CURRENCY_SYMBOL;
$categories = get_user_categories($userId, 'expense');

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="dashboard.php" class="btn-icon-circle text-decoration-none">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <h3 class="fw-bold mb-0" style="letter-spacing: -0.4px;">Budgets</h3>
    </div>
    <button class="btn btn-sm btn-primary-smart px-3" data-bs-toggle="modal" data-bs-target="#setBudgetModal">
        <i class="fa-solid fa-plus me-1"></i> Set Budget
    </button>
</div>

<!-- Month Selector & Summary Row -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="app-card p-3 h-100 d-flex flex-column justify-content-center">
            <small class="text-muted fw-semibold mb-2 text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Selected Period</small>
            <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded-4">
                <button class="btn btn-sm btn-white shadow-sm rounded-circle" id="prevMonthBtn" style="width: 34px; height: 34px;">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <span class="fw-bold fs-6" id="currentMonthLabel"><?= date('F Y') ?></span>
                <button class="btn btn-sm btn-white shadow-sm rounded-circle" id="nextMonthBtn" style="width: 34px; height: 34px;">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-pill-card h-100" style="background: #FFF8E1;">
            <div class="stat-card-title text-warning-emphasis">Total Budget</div>
            <div class="stat-amount text-warning-emphasis" id="summaryTotalBudget">
                <span class="spinner-border spinner-border-sm"></span>
            </div>
            <div class="stat-period">Target spending ceiling</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-pill-card expense-card h-100">
            <div class="stat-card-title">Total Spent</div>
            <div class="stat-amount" id="summaryTotalSpent">
                <span class="spinner-border spinner-border-sm"></span>
            </div>
            <div class="stat-period" id="summaryRemainingText">-- remaining</div>
        </div>
    </div>
</div>

<!-- Budgets List Container -->
<div id="budgetsListContainer">
    <div class="text-center py-5 text-muted">
        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
        <span class="ms-2">Loading budgets...</span>
    </div>
</div>

<!-- Set Budget Modal -->
<div class="modal fade" id="setBudgetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-wallet text-warning me-2"></i> Set Spending Budget</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="budgetForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="month" id="modalBudgetMonth" value="<?= date('m') ?>">
                    <input type="hidden" name="year" id="modalBudgetYear" value="<?= date('Y') ?>">

                    <div class="mb-3">
                        <label class="form-label">Scope / Category <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select">
                            <option value="">🌟 Overall Monthly Budget</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Budget Limit (<?= htmlspecialchars($currencySymbol) ?>) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="1" name="amount" class="form-control form-control-lg fw-bold" placeholder="0.00" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alert Warning Threshold</label>
                        <select name="alert_threshold" class="form-select">
                            <option value="70">Alert at 70% used</option>
                            <option value="80" selected>Alert at 80% used (Recommended)</option>
                            <option value="90">Alert at 90% used</option>
                            <option value="100">Alert only when exceeded (100%)</option>
                        </select>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" id="saveBudgetBtn" class="btn btn-primary-smart py-2">
                            <i class="fa-solid fa-check me-1"></i> Save Budget
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentDate = new Date();

    function updateMonthDisplay() {
        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        $('#currentMonthLabel').text(`${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}`);
        $('#modalBudgetMonth').val(currentDate.getMonth() + 1);
        $('#modalBudgetYear').val(currentDate.getFullYear());
        loadBudgets();
    }

    $('#prevMonthBtn').on('click', function() {
        currentDate.setMonth(currentDate.getMonth() - 1);
        updateMonthDisplay();
    });

    $('#nextMonthBtn').on('click', function() {
        currentDate.setMonth(currentDate.getMonth() + 1);
        updateMonthDisplay();
    });

    function loadBudgets() {
        const container = $('#budgetsListContainer');
        container.html(`
            <div class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                <span class="ms-2">Loading budgets...</span>
            </div>
        `);

        const m = currentDate.getMonth() + 1;
        const y = currentDate.getFullYear();

        $.getJSON('api/budgets.php', { action: 'list', month: m, year: y }, function(res) {
            if (!res.success) {
                container.html(`<div class="alert alert-danger">${res.message}</div>`);
                return;
            }

            $('#summaryTotalBudget').text(res.formatted_total_budget);
            $('#summaryTotalSpent').text(res.formatted_total_spent);
            const remaining = Math.max(0, res.total_budget - res.total_spent);
            $('#summaryRemainingText').text('<?= $currencySymbol ?>' + remaining.toLocaleString() + ' remaining');

            if (res.budgets.length === 0) {
                container.html(`
                    <div class="empty-state-box">
                        <div class="empty-state-icon" style="background: #FFF8E1; color: #FFA000;">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                        <h6>No budgets set for this month</h6>
                        <p>Click "Set Budget" to define spending limits and avoid overspending</p>
                    </div>
                `);
                return;
            }

            let html = '<div class="row g-3">';
            for (const b of res.budgets) {
                let barColor = '#10B981';
                let statusBadge = `<span class="badge badge-soft-success">On Track</span>`;

                if (b.percentage >= 100) {
                    barColor = '#EF4444';
                    statusBadge = `<span class="badge badge-soft-danger">Exceeded!</span>`;
                } else if (b.percentage >= b.alert_threshold) {
                    barColor = '#F59E0B';
                    statusBadge = `<span class="badge badge-soft-warning">Near Limit</span>`;
                }

                html += `
                    <div class="col-md-6 col-xl-4" id="budget-card-${b.id}">
                        <div class="app-card h-100 p-3 mb-0 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="category-icon-box" style="background-color: ${b.bg_color || '#FFF3E0'}; color: ${b.color || '#FF9F43'}; width: 40px; height: 40px; font-size: 1.05rem;">
                                            <i class="fa-solid ${b.icon || 'fa-wallet'}"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold mb-0">${escapeHtml(b.category_name)}</h6>
                                            <small class="text-muted">Limit: ${b.formatted_budget}</small>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        ${statusBadge}
                                        <button class="btn btn-link text-danger p-0 ms-1 delete-budget-btn" data-id="${b.id}" title="Delete Budget">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span class="fw-bold">Spent: ${b.formatted_spent}</span>
                                        <span class="text-muted fw-semibold">${b.percentage}% used</span>
                                    </div>
                                    <div class="progress-smart" style="height: 10px;">
                                        <div class="progress-bar" role="progressbar" style="width: ${Math.min(100, b.percentage)}%; background-color: ${barColor};"></div>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted mt-2" style="font-size: 0.73rem;">
                                        <span>Remaining: <strong>${b.formatted_remaining}</strong></span>
                                        <span>Alert threshold: ${b.alert_threshold}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
            html += '</div>';

            container.html(html);
        });
    }

    // Save Budget
    $('#budgetForm').on('submit', function(e) {
        e.preventDefault();
        const submitBtn = $('#saveBudgetBtn');
        submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');

        const formData = new FormData(this);
        formData.append('action', 'set');

        $.ajax({
            url: 'api/budgets.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    $('#setBudgetModal').modal('hide');
                    $('#budgetForm')[0].reset();
                    loadBudgets();
                } else {
                    toastr.error(res.message);
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Save Budget');
            }
        });
    });

    // Delete Budget
    $(document).on('click', '.delete-budget-btn', function() {
        const id = $(this).data('id');
        confirmAction('Remove Budget?', 'Are you sure you want to remove this budget limit?').then(res => {
            if (res.isConfirmed) {
                $.post('api/budgets.php', { action: 'delete', id: id }, function(resp) {
                    if (resp.success) {
                        toastr.success(resp.message);
                        $(`#budget-card-${id}`).slideUp(300, function() { $(this).remove(); });
                    } else {
                        toastr.error(resp.message);
                    }
                });
            }
        });
    });

    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/[&<>"'`=\/]/g, function (s) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
                '/': '&#x2F;',
                '`': '&#x60;',
                '=': '&#x3D;'
            }[s];
        });
    }

    updateMonthDisplay();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
