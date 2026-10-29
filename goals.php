<?php
/**
 * Savings Goals Screen
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_auth();

$pageTitle = "Savings Goals";
$user = current_user();
$userId = $user['id'];
$currencySymbol = $user['currency_symbol'] ?? DEFAULT_CURRENCY_SYMBOL;

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="dashboard.php" class="btn-icon-circle text-decoration-none">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <h3 class="fw-bold mb-0" style="letter-spacing: -0.4px;">Savings Goals</h3>
    </div>
    <button class="btn btn-sm btn-primary-smart px-3" data-bs-toggle="modal" data-bs-target="#addGoalModal"
        style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
        <i class="fa-solid fa-plus me-1"></i> New Goal
    </button>
</div>

<div class="stat-pill-card h-100" style="background: #EDF7EE;">
    <div class="stat-card-title" style="color: #1E7538;">Total Saved</div>
    <div class="stat-amount" style="color: #1E7538;" id="summaryTotalSaved">
        <span class="spinner-border spinner-border-sm"></span>
    </div>
    <div class="stat-period">Accumulated in active targets</div>
</div>
<div class="col-6 col-md-6">
    <div class="stat-pill-card h-100" style="background: #FFF8E1;">
        <div class="stat-card-title text-warning-emphasis">Total Target</div>
    </div>
    <div class="stat-period">Sum of all goal targets</div>
</div>
</div>
</div>

<div id="goalsListContainer">
    <div class="text-center py-5 text-muted">
        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
        <span class="ms-2">Loading savings targets...</span>
    </div>
</div>

<!-- Add Goal Modal -->
<div class="modal fade" id="addGoalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-bullseye text-success me-2"></i> Create Savings Goal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="goalForm">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Goal Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control"
                            placeholder="e.g. Emergency Fund, Goa Vacation, MacBook" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Target Amount (<?= htmlspecialchars($currencySymbol) ?>) <span
                                    class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="1" name="target_amount" class="form-control fw-bold"
                                placeholder="50000" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Initial Amount</label>
                            <input type="number" step="0.01" min="0" name="initial_amount" class="form-control"
                                value="0.00">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Target Date <span class="text-danger">*</span></label>
                            <input type="date" name="target_date" class="form-control"
                                value="<?= date('Y-m-d', strtotime('+6 months')) ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Theme Color</label>
                            <input type="color" name="color" class="form-control form-control-color w-100"
                                value="#10B981">
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" id="saveGoalBtn" class="btn btn-primary-smart py-2"
                            style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                            <i class="fa-solid fa-plus me-1"></i> Start Saving
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Contribute Modal (Deposit / Withdraw) -->
<div class="modal fade" id="contributeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contributeModalTitle">Add Funds to Goal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="contributeForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="goal_id" id="contribGoalId">

                    <div class="mb-3">
                        <label class="form-label">Action</label>
                        <select name="type" id="contribType" class="form-select">
                            <option value="deposit">Deposit Money (+)</option>
                            <option value="withdraw">Withdraw Money (-)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Amount (<?= htmlspecialchars($currencySymbol) ?>) <span
                                class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="1" name="amount"
                            class="form-control form-control-lg fw-bold" placeholder="0.00" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Monthly contribution">
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" id="saveContribBtn" class="btn btn-primary-smart py-2"
                            style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                            Confirm
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        function loadGoals() {
            const container = $('#goalsListContainer');
            container.html(`
            <div class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                <span class="ms-2">Loading savings targets...</span>
            </div>
        `);

            $.getJSON('api/goals.php', { action: 'list' }, function (res) {
                if (!res.success) {
                    container.html(`<div class="alert alert-danger">${res.message}</div>`);
                    return;
                }

                $('#summaryTotalSaved').text(res.formatted_total_saved);
                $('#summaryTotalTarget').text(res.formatted_total_target);

                if (res.goals.length === 0) {
                    container.html(`
                    <div class="empty-state-box">
                        <div class="empty-state-icon" style="background: #EDF7EE; color: #1E7538;">
                            <i class="fa-solid fa-bullseye"></i>
                        </div>
                        <h6>No savings goals created</h6>
                        <p>Click "New Goal" to set a target and build your savings</p>
                    </div>
                    return;
                }

                let html = '<div class="row g-3">';
                for (const g of res.goals) {
                    const isAchieved = (g.status === 'achieved');
                    const badge = isAchieved
                        ? `< span class= "badge badge-soft-success" > <i class="fa-solid fa-trophy me-1"></i> Achieved!</span > `
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="category-icon-box" style="background-color: ${g.color}20; color: ${g.color}; width: 40px; height: 40px;">
                                            <i class="fa-solid ${g.category_icon}"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold mb-0">${escapeHtml(g.title)}</h6>
                                            <small class="text-muted">Target: ${g.formatted_target}</small>
                                        </div>
                                    </div>
                                    <div>
                                        ${badge}
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                        <span class="text-muted fw-semibold">${g.percentage}% reached</span>
                                    </div>
                                    <div class="progress-smart" style="height: 10px;">
                                        <div class="progress-bar" role="progressbar" style="width: ${g.percentage}%; background-color: ${g.color};"></div>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted mt-2" style="font-size: 0.73rem;">
                                        <span>Remaining: <strong>${g.formatted_remaining}</strong></span>
                                        <span>Target: ${g.formatted_target_date}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top" style="border-color: var(--border-soft) !important;">
                                <button class="btn btn-outline-danger btn-sm rounded-pill px-2 delete-goal-btn" data-id="${g.id}" title="Delete Goal">
                                    <i class="fa-solid fa-trash"></i>
                                    </button>
                                    <button class="btn btn-primary-smart btn-sm rounded-pill px-3 open-contrib-btn" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);" data-id="${g.id}" data-title="${escapeHtml(g.title)}" data-type="deposit">
                                        <i class="fa-solid fa-plus me-1"></i> Deposit
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

        // Add Goal
        $('#goalForm').on('submit', function (e) {
            e.preventDefault();
            const submitBtn = $('#saveGoalBtn');
            submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Creating...');

            const formData = new FormData(this);
            formData.append('action', 'add');

            $.ajax({
                url: 'api/goals.php',
                type: 'POST',
                data: formData,
                if(res.success) {
                toastr.success(res.message);
                $('#addGoalModal').modal('hide');
                $('#goalForm')[0].reset();
                loadGoals();
            } else {
                toastr.error(res.message);
            }
        },
            complete: function () {
                submitBtn.prop('disabled', false).html('<i class="fa-solid fa-plus me-1"></i> Start Saving');
            }
            });
        });

    // Open Contribute Modal
    $(document).on('click', '.open-contrib-btn', function () {
        const id = $(this).data('id');
        const title = $(this).data('title');
        const type = $(this).data('type');

        $('#contribGoalId').val(id);
        $('#contribType').val(type);
        $('#contributeModalTitle').text((type === 'deposit' ? 'Add Funds to ' : 'Withdraw from ') + title);
        $('#contributeModal').modal('show');
    });

    // Save Contribution
    $('#contributeForm').on('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'contribute');

        $.ajax({
            url: 'api/goals.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.success) {
                    toastr.success(res.message);
                    $('#contributeModal').modal('hide');
                    $('#contributeForm')[0].reset();
                    loadGoals();
                } else {
                    toastr.error(res.message);
                }
            }
        });
    });

    // Delete Goal
    $(document).on('click', '.delete-goal-btn', function () {
        const id = $(this).data('id');
        confirmAction('Delete Goal?', 'Are you sure you want to remove this savings goal?').then(res => {
            if (res.isConfirmed) {
                $.post('api/goals.php', { action: 'delete', id: id }, function (resp) {
                    if (resp.success) {
                        toastr.success(resp.message);
                        $(`#goal-card-${id}`).slideUp(300, function () { $(this).remove(); });
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

    loadGoals();
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>