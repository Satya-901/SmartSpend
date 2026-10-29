<?php
/**
 * Transactions Screen (Screen 2 from mockup)
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_auth();

$pageTitle = "Transactions";
$user = current_user();
$userId = $user['id'];
$currencySymbol = $user['currency_symbol'] ?? DEFAULT_CURRENCY_SYMBOL;
$categories = get_user_categories($userId);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Title & Action Icons -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h3 class="fw-bold mb-0" style="letter-spacing: -0.4px;">Transactions</h3>
        <p class="text-secondary small mb-0">Search, filter and inspect your financial records</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="export/csv.php?type=transactions" class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm text-secondary" title="Download CSV">
            <i class="fa-solid fa-file-csv text-success me-1"></i> Export CSV
        </a>
        <a href="export/print_report.php" target="_blank" class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm text-secondary" title="Print Statement">
            <i class="fa-solid fa-file-pdf text-danger me-1"></i> Statement
        </a>
        <button type="button" class="btn btn-sm btn-primary-smart px-3" data-bs-toggle="modal" data-bs-target="#quickAddModal">
            <i class="fa-solid fa-plus me-1"></i> Add Transaction
        </button>
    </div>
</div>

<!-- Controls & Filter Toolbar -->
<div class="app-card p-3 mb-3 shadow-sm">
    <div class="row g-2 align-items-center">
        <!-- Search bar -->
        <div class="col-12 col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: 14px 0 0 14px; border-color: var(--border-soft);">
                    <i class="fa-solid fa-search"></i>
                </span>
                <input type="text" id="txSearchInput" class="form-control border-start-0 ps-0" placeholder="Search merchant, category, notes..." style="border-radius: 0 14px 14px 0; border-color: var(--border-soft);">
            </div>
        </div>

        <!-- Filter Pills: All, Income, Expense -->
        <div class="col-12 col-sm-6 col-md-4">
            <div class="filter-pills-row p-0 mb-0">
                <div class="pills-group w-100 justify-content-center">
                    <button class="filter-pill active flex-fill" data-type="all">All</button>
                    <button class="filter-pill flex-fill" data-type="income">Income</button>
                    <button class="filter-pill flex-fill" data-type="expense">Expense</button>
                </div>
            </div>
        </div>

        <!-- Advanced Filter Button -->
        <div class="col-12 col-sm-6 col-md-3 d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm py-2 w-100 rounded-3 d-flex align-items-center justify-content-center gap-1" data-bs-toggle="modal" data-bs-target="#filterModal">
                <i class="fa-solid fa-sliders"></i> Filter by Date / Cat
            </button>
        </div>
    </div>
</div>

<!-- Dynamic Summary Bar for Filtered Results -->
<div id="txSummaryBar" class="row g-2 mb-3" style="display: none;">
    <div class="col-4">
        <div class="stat-pill-card p-2 text-center" style="background: #EDF7EE;">
            <div class="small fw-semibold text-success">Total Income</div>
            <div class="fw-bold fs-6 text-success" id="summaryFilteredIncome">₹0</div>
        </div>
    </div>
    <div class="col-4">
        <div class="stat-pill-card p-2 text-center expense-card">
            <div class="small fw-semibold text-danger">Total Expense</div>
            <div class="fw-bold fs-6 text-danger" id="summaryFilteredExpense">₹0</div>
        </div>
    </div>
    <div class="col-4">
        <div class="stat-pill-card p-2 text-center" style="background: #FFF8E1;">
            <div class="small fw-semibold text-warning-emphasis">Net Balance</div>
            <div class="fw-bold fs-6 text-warning-emphasis" id="summaryFilteredNet">₹0</div>
        </div>
    </div>
</div>

<!-- Transactions Group Container (Dynamically rendered via AJAX) -->
<div id="transactionsListContainer">
    <div class="text-center py-5 text-muted">
        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
        <span class="ms-2">Loading transactions...</span>
    </div>
</div>

<!-- Filter Modal Sheet -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-sliders me-2 text-warning"></i> Filter Transactions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="filterForm">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select id="filterCategory" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?> (<?= ucfirst($cat['type']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">From Date</label>
                            <input type="date" id="filterDateFrom" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">To Date</label>
                            <input type="date" id="filterDateTo" class="form-control">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between gap-2 mt-4">
                        <button type="button" id="resetFiltersBtn" class="btn btn-light border flex-grow-1 rounded-3">Reset</button>
                        <button type="submit" class="btn btn-primary-smart flex-grow-1 rounded-3">Apply Filters</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Transaction Detail Sheet Modal -->
<div class="modal fade" id="txDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="txDetailTitle">Transaction Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="txDetailBody">
                <!-- Dynamically populated -->
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" id="modalDeleteTxBtn" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                    <i class="fa-solid fa-trash me-1"></i> Delete
                </button>
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentFilterType = 'all';
    let currentCategoryId = '';
    let currentDateFrom = '';
    let currentDateTo = '';
    let currentSearch = '';

    // Search Toggle
    $('#toggleSearchBtn').on('click', function() {
        $('#searchContainer').slideToggle(200, function() {
            if ($(this).is(':visible')) {
                $('#txSearchInput').focus();
            }
        });
    });

    $('#clearSearchBtn').on('click', function() {
        $('#txSearchInput').val('');
        currentSearch = '';
        loadTransactions();
    });

    let searchTimer = null;
    $('#txSearchInput').on('input', function() {
        clearTimeout(searchTimer);
        currentSearch = $(this).val().trim();
        searchTimer = setTimeout(loadTransactions, 350);
    });

    // Filter pills click
    $('.filter-pill').on('click', function() {
        $('.filter-pill').removeClass('active');
        $(this).addClass('active');
        currentFilterType = $(this).data('type');
        loadTransactions();
    });

    // Filter Modal apply
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        currentCategoryId = $('#filterCategory').val();
        currentDateFrom = $('#filterDateFrom').val();
        currentDateTo = $('#filterDateTo').val();
        $('#filterModal').modal('hide');
        loadTransactions();
    });

    $('#resetFiltersBtn').on('click', function() {
        $('#filterCategory').val('');
        $('#filterDateFrom').val('');
        $('#filterDateTo').val('');
        currentCategoryId = '';
        currentDateFrom = '';
        currentDateTo = '';
        $('#filterModal').modal('hide');
        loadTransactions();
    });

    function loadTransactions() {
        const container = $('#transactionsListContainer');
        container.html(`
            <div class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                <span class="ms-2">Loading transactions...</span>
            </div>
        `);

        $.getJSON('api/transactions.php', {
            action: 'list',
            type: currentFilterType,
            category_id: currentCategoryId,
            date_from: currentDateFrom,
            date_to: currentDateTo,
            search: currentSearch
        }, function(res) {
            if (!res.success) {
                container.html(`<div class="alert alert-danger">${res.message}</div>`);
                return;
            }

            if (res.count === 0) {
                $('#txSummaryBar').slideUp(200);
                container.html(`
                    <div class="empty-state-box">
                        <div class="empty-state-icon">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                        <h6>No transactions found</h6>
                        <p>No transactions match your current search or filter</p>
                    </div>
                `);
                return;
            }

            // Update Summary Bar
            $('#summaryFilteredIncome').text('+' + (res.formatted_total_income || '₹0'));
            $('#summaryFilteredExpense').text('-' + (res.formatted_total_expense || '₹0'));
            const netAmount = (res.total_income || 0) - (res.total_expense || 0);
            const netSign = (netAmount >= 0) ? '+' : '-';
            $('#summaryFilteredNet').text(netSign + '<?= $currencySymbol ?>' + Math.abs(netAmount).toLocaleString());
            $('#txSummaryBar').slideDown(200);

            let html = '';
            for (const [dayLabel, items] of Object.entries(res.grouped)) {
                html += `<div class="group-date-label">${escapeHtml(dayLabel)}</div>`;
                html += `<div class="transactions-group">`;

                for (const item of items) {
                    const iconBg = item.bg_color || '#FFF3E0';
                    const iconColor = item.color || '#FF9F43';
                    const iconClass = item.icon || 'fa-receipt';

                    html += `
                        <div class="transaction-card" data-tx='${JSON.stringify(item).replace(/'/g, "&apos;")}'>
                            <div class="transaction-left">
                                <div class="category-icon-box" style="background-color: ${iconBg}; color: ${iconColor};">
                                    <i class="fa-solid ${iconClass}"></i>
                                </div>
                                <div class="transaction-details">
                                    <span class="tx-title">${escapeHtml(item.category_name || 'Other')}</span>
                                    <span class="tx-sub">${escapeHtml(item.notes || item.payment_method)}</span>
                                </div>
                            </div>
                            <div class="transaction-right">
                                <span class="tx-amount ${item.type}">
                                    ${item.formatted_amount}
                                </span>
                                <span class="tx-date-sub">
                                    ${item.formatted_time || dayLabel}
                                </span>
                            </div>
                        </div>
                    `;
                }
                html += `</div>`;
            }

            container.html(html);
        }).fail(function() {
            container.html(`<div class="alert alert-danger">Error retrieving transactions.</div>`);
        });
    }

    // Click on transaction card to view details
    $(document).on('click', '.transaction-card', function() {
        const item = $(this).data('tx');
        if (!item) return;

        let receiptHtml = '';
        if (item.receipt_file) {
            receiptHtml = `
                <div class="mt-3 pt-3 border-top">
                    <label class="form-label fw-bold d-block">Receipt Attachment</label>
                    <button class="btn btn-outline-primary btn-sm rounded-pill px-3 view-receipt-btn" data-file="${item.receipt_file}">
                        <i class="fa-solid fa-paperclip me-1"></i> View Receipt
                    </button>
                </div>
            `;
        }

        $('#txDetailBody').html(`
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="category-icon-box" style="background-color: ${item.bg_color || '#FFF3E0'}; color: ${item.color || '#FF9F43'}; width: 50px; height: 50px; font-size: 1.3rem;">
                    <i class="fa-solid ${item.icon || 'fa-receipt'}"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0">${escapeHtml(item.category_name || 'Uncategorized')}</h5>
                    <span class="badge ${item.type === 'expense' ? 'badge-soft-danger' : 'badge-soft-success'}">${item.type.toUpperCase()}</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless table-sm mb-0">
                    <tr><td class="text-muted" style="width: 40%;">Amount:</td><td class="fw-bold fs-5 ${item.type === 'expense' ? 'text-danger' : 'text-success'}">${item.formatted_amount}</td></tr>
                    <tr><td class="text-muted">Date:</td><td class="fw-semibold">${item.date} ${item.time || ''}</td></tr>
                    <tr><td class="text-muted">Payment Method:</td><td class="fw-semibold">${escapeHtml(item.payment_method)}</td></tr>
                    <tr><td class="text-muted">Notes:</td><td>${escapeHtml(item.notes || 'None')}</td></tr>
                </table>
            </div>
            ${receiptHtml}
        `);

        $('#modalDeleteTxBtn').data('id', item.id);
        $('#txDetailModal').modal('show');
    });

    // Delete inside detail modal
    $('#modalDeleteTxBtn').on('click', function() {
        const txId = $(this).data('id');
        $('#txDetailModal').modal('hide');

        confirmAction('Delete Transaction?', 'Are you sure you want to remove this transaction?').then(res => {
            if (res.isConfirmed) {
                $.post('api/transactions.php', { action: 'delete', id: txId }, function(resp) {
                    if (resp.success) {
                        toastr.success(resp.message);
                        loadTransactions();
                    } else {
                        toastr.error(resp.message);
                    }
                }).fail(function(xhr) {
                    toastr.error(xhr.responseJSON ? xhr.responseJSON.message : 'Error deleting.');
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

    // Initial load
    loadTransactions();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
