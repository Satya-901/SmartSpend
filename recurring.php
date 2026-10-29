<?php
/**
 * Recurring Transactions & Bills Screen
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_auth();

$pageTitle = "Recurring Bills & Subscriptions";
$user = current_user();
$userId = $user['id'];
$currencySymbol = $user['currency_symbol'] ?? DEFAULT_CURRENCY_SYMBOL;
$categories = get_user_categories($userId);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="dashboard.php" class="btn-icon-circle text-decoration-none">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <h3 class="fw-bold mb-0" style="letter-spacing: -0.4px;">Recurring</h3>
    </div>
    <button class="btn btn-sm btn-primary-smart px-3" data-bs-toggle="modal" data-bs-target="#addRecurringModal" style="background: linear-gradient(135deg, #0288D1 0%, #01579B 100%);">
        <i class="fa-solid fa-plus me-1"></i> New Bill
    </button>
</div>

<!-- Info banner -->
<div class="app-card border-0 p-3 mb-3 d-flex align-items-center gap-3" style="background: #E1F5FE;">
    <div class="category-icon-box" style="background: #B3E5FC; color: #0288D1; width: 40px; height: 40px;">
        <i class="fa-solid fa-arrows-rotate"></i>
    </div>
    <div>
        <h6 class="fw-bold mb-0" style="color: #01579B;">Automatic Reminders & Tracking</h6>
        <small class="text-secondary" style="font-size: 0.78rem;">Track subscriptions, rent and recurring bills</small>
    </div>
</div>

<!-- Recurring Items Container -->
<div id="recurringListContainer">
    <div class="text-center py-5 text-muted">
        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
        <span class="ms-2">Loading recurring transactions...</span>
    </div>
</div>

<!-- Add Recurring Modal -->
<div class="modal fade" id="addRecurringModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-arrows-rotate text-info me-2"></i> Add Recurring Rule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="recurringForm">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Title / Service <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Netflix, Rent, Broadband" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select">
                                <option value="expense">Expense (-)</option>
                                <option value="income">Income (+)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Amount (<?= htmlspecialchars($currencySymbol) ?>) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="1" name="amount" class="form-control fw-bold" placeholder="0.00" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Frequency</label>
                            <select name="frequency" class="form-select">
                                <option value="monthly" selected>Monthly</option>
                                <option value="weekly">Weekly</option>
                                <option value="daily">Daily</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Next Due Date <span class="text-danger">*</span></label>
                            <input type="date" name="next_due_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="UPI">UPI / Autopay</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Net Banking">Net Banking</option>
                            <option value="Cash">Cash</option>
                        </select>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" id="saveRecBtn" class="btn btn-primary-smart py-2" style="background: linear-gradient(135deg, #0288D1 0%, #01579B 100%);">
                            <i class="fa-solid fa-plus me-1"></i> Save Recurring Rule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function loadRecurring() {
        const container = $('#recurringListContainer');
        container.html(`
            <div class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                <span class="ms-2">Loading recurring items...</span>
            </div>
        `);

        $.getJSON('api/recurring.php', { action: 'list' }, function(res) {
            if (!res.success) {
                container.html(`<div class="alert alert-danger">${res.message}</div>`);
                return;
            }

            if (res.recurring.length === 0) {
                container.html(`
                    <div class="empty-state-box">
                        <div class="empty-state-icon" style="background: #E1F5FE; color: #0288D1;">
                            <i class="fa-solid fa-arrows-rotate"></i>
                        </div>
                        <h6>No recurring bills added</h6>
                        <p>Click "New Bill" to track subscriptions and automatic recurring payments</p>
                    </div>
                `);
                return;
            }

            let html = '<div class="row g-3">';
            for (const item of res.recurring) {
                let dueBadge = '';
                if (item.is_overdue) {
                    dueBadge = `<span class="badge badge-soft-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i> Overdue (${item.formatted_due_date})</span>`;
                } else if (item.is_due_soon) {
                    dueBadge = `<span class="badge badge-soft-warning"><i class="fa-regular fa-clock me-1"></i> Due Soon (${item.formatted_due_date})</span>`;
                } else {
                    dueBadge = `<span class="badge badge-soft-info">${item.formatted_due_date}</span>`;
                }

                const isExpense = (item.type === 'expense');

                html += `
                    <div class="col-md-6" id="rec-item-${item.id}">
                        <div class="app-card h-100 p-3 mb-0 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="category-icon-box" style="background-color: ${item.bg_color || '#FFF3E0'}; color: ${item.color || '#FF9F43'}; width: 44px; height: 44px;">
                                            <i class="fa-solid ${item.icon || 'fa-arrows-rotate'}"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold mb-0">${escapeHtml(item.title)}</h6>
                                            <small class="text-muted">${capitalize(item.frequency)} · ${escapeHtml(item.payment_method)}</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="fw-bold fs-6 ${isExpense ? 'text-danger' : 'text-success'}">${(isExpense ? '-' : '+')}${item.formatted_amount}</span>
                                        <div class="mt-1">${dueBadge}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions bar -->
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top" style="border-color: var(--border-soft) !important;">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input toggle-active-switch" type="checkbox" role="switch" data-id="${item.id}" ${item.is_active == 1 ? 'checked' : ''}>
                                    <label class="form-check-label small text-muted">${item.is_active == 1 ? 'Active' : 'Paused'}</label>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-outline-danger btn-sm rounded-pill px-2 delete-rec-btn" data-id="${item.id}" title="Delete Rule">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                    <button class="btn btn-primary-smart btn-sm rounded-pill px-3 process-due-btn" data-id="${item.id}">
                                        <i class="fa-solid fa-check me-1"></i> Mark Paid & Advance
                                    </button>
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

    // Add Recurring Form
    $('#recurringForm').on('submit', function(e) {
        e.preventDefault();
        const submitBtn = $('#saveRecBtn');
        submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');

        const formData = new FormData(this);
        formData.append('action', 'add');

        $.ajax({
            url: 'api/recurring.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    $('#addRecurringModal').modal('hide');
                    $('#recurringForm')[0].reset();
                    loadRecurring();
                } else {
                    toastr.error(res.message);
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="fa-solid fa-plus me-1"></i> Save Recurring Rule');
            }
        });
    });

    // Process Due Action
    $(document).on('click', '.process-due-btn', function() {
        const id = $(this).data('id');
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');

        $.post('api/recurring.php', { action: 'process_due', id: id }, function(res) {
            if (res.success) {
                toastr.success(res.message);
                loadRecurring();
            } else {
                toastr.error(res.message);
                btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Mark Paid & Advance');
            }
        });
    });

    // Toggle Active Switch
    $(document).on('change', '.toggle-active-switch', function() {
        const id = $(this).data('id');
        $.post('api/recurring.php', { action: 'toggle_active', id: id }, function(res) {
            if (res.success) {
                toastr.info(res.message);
            }
        });
    });

    // Delete
    $(document).on('click', '.delete-rec-btn', function() {
        const id = $(this).data('id');
        confirmAction('Delete Recurring Item?', 'Are you sure you want to delete this rule?').then(res => {
            if (res.isConfirmed) {
                $.post('api/recurring.php', { action: 'delete', id: id }, function(resp) {
                    if (resp.success) {
                        toastr.success(resp.message);
                        $(`#rec-item-${id}`).slideUp(300, function() { $(this).remove(); });
                    }
                });
            }
        });
    });

    function capitalize(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

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

    loadRecurring();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
