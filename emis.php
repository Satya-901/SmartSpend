<?php
/**
 * EMI & Loan Management Screen
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_auth();

$pageTitle = "EMI & Loan Management";
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
        <h3 class="fw-bold mb-0" style="letter-spacing: -0.4px;">EMI & Loans</h3>
    </div>
    <div class="d-flex gap-2">
        <a href="export/csv.php?type=emis" class="btn-icon-circle text-decoration-none" title="Export CSV">
            <i class="fa-solid fa-file-arrow-down"></i>
        </a>
        <button class="btn btn-sm btn-primary-smart px-3" data-bs-toggle="modal" data-bs-target="#addEmiModal" style="background: linear-gradient(135deg, #A855F7 0%, #7E22CE 100%);">
            <i class="fa-solid fa-plus me-1"></i> New Loan
        </button>
    </div>
</div>

<!-- EMI Summary Cards -->
<div class="row g-2 mb-3">
    <div class="col-6">
        <div class="stat-pill-card" style="background: #F3E8FF;">
            <div class="stat-card-title" style="color: #7E22CE;">Monthly Commitment</div>
            <div class="stat-amount" style="color: #6B21A8;" id="summaryCommitment">
                <span class="spinner-border spinner-border-sm"></span>
            </div>
            <div class="stat-period">Due every month</div>
        </div>
    </div>
    <div class="col-6">
        <div class="stat-pill-card" style="background: #FFF8E1;">
            <div class="stat-card-title text-warning-emphasis">Total Outstanding</div>
            <div class="stat-amount text-warning-emphasis" id="summaryOutstanding">
                <span class="spinner-border spinner-border-sm"></span>
            </div>
            <div class="stat-period">Remaining Principal</div>
        </div>
    </div>
</div>

<!-- Tabs: Active vs Completed -->
<div class="filter-pills-row mb-3">
    <div class="pills-group">
        <button class="filter-pill active emi-tab-btn" data-status="active">
            Active (<span id="activeCountBadge">0</span>)
        </button>
        <button class="filter-pill emi-tab-btn" data-status="completed">
            Completed / Closed
        </button>
    </div>
</div>

<!-- Loans List Container -->
<div id="emisListContainer">
    <div class="text-center py-5 text-muted">
        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
        <span class="ms-2">Loading loan accounts...</span>
    </div>
</div>

<!-- Add EMI Modal -->
<div class="modal fade" id="addEmiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-landmark text-purple me-2"></i> Add New Loan / EMI</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createEmiForm">
                    <?= csrf_field() ?>
                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label">Loan / Asset Name <span class="text-danger">*</span></label>
                            <input type="text" name="loan_name" class="form-control" placeholder="e.g. iPhone 15 Pro, Home Loan" required>
                        </div>
                        <div class="col-5">
                            <label class="form-label">Lender / Bank <span class="text-danger">*</span></label>
                            <input type="text" name="lender" class="form-control" placeholder="e.g. HDFC, SBI" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Principal Amount (<?= htmlspecialchars($currencySymbol) ?>) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="1" id="emiPrincipal" name="principal_amount" class="form-control" placeholder="100000" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Annual Interest (%)</label>
                            <input type="number" step="0.01" min="0" id="emiRate" name="interest_rate" class="form-control" value="0.00" placeholder="e.g. 10.5">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Tenure (Months) <span class="text-danger">*</span></label>
                            <input type="number" min="1" max="360" id="emiTenure" name="tenure_months" class="form-control" placeholder="e.g. 24" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Monthly EMI (<?= htmlspecialchars($currencySymbol) ?>)</label>
                            <input type="number" step="0.01" min="1" id="emiAmountInput" name="emi_amount" class="form-control" placeholder="Auto-calculated">
                            <small class="text-muted" style="font-size: 0.7rem;">Leave empty to auto-calculate</small>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Monthly Due Day (1-31)</label>
                            <input type="number" min="1" max="31" name="due_day" class="form-control" value="<?= (int)date('d') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Already Paid Installments (Optional)</label>
                        <input type="number" min="0" name="initial_paid_installments" class="form-control" value="0" placeholder="If ongoing loan">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Account number or reference">
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" id="saveEmiBtn" class="btn btn-primary-smart py-2" style="background: linear-gradient(135deg, #A855F7 0%, #7E22CE 100%);">
                            <i class="fa-solid fa-plus me-1"></i> Save Loan Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Pay Installment Modal -->
<div class="modal fade" id="payInstallmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-credit-card text-success me-2"></i> Record EMI Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="recordPaymentForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="emi_id" id="payEmiId">

                    <div class="app-card bg-light border-0 p-3 mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Loan Name:</span>
                            <span class="fw-bold" id="payLoanName">--</span>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <span class="text-muted small">Remaining Balance:</span>
                            <span class="fw-bold text-danger" id="payLoanBalance">--</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Payment Amount (<?= htmlspecialchars($currencySymbol) ?>) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="1" name="amount_paid" id="payAmountInput" class="form-control form-control-lg fw-bold" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Payment Date</label>
                            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="Bank Transfer">Auto-Debit / ECS</option>
                                <option value="UPI">UPI</option>
                                <option value="Net Banking">Net Banking</option>
                                <option value="Cheque">Cheque</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <input type="text" name="notes" class="form-control" placeholder="Transaction ref or bank remarks">
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" id="submitPaymentBtn" class="btn btn-primary-smart py-2" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                            <i class="fa-solid fa-check me-1"></i> Confirm Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- EMI Schedule & History Modal -->
<div class="modal fade" id="emiHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="historyModalTitle">Loan Schedule & History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="emiHistoryBody">
                <div class="text-center py-4">Loading history...</div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentStatus = 'active';

    function calculateEmiPreview() {
        const p = parseFloat($('#emiPrincipal').val()) || 0;
        const r = parseFloat($('#emiRate').val()) || 0;
        const n = parseInt($('#emiTenure').val()) || 0;

        if (p > 0 && n > 0) {
            let emi = 0;
            if (r > 0) {
                const monthlyRate = (r / 12) / 100;
                const pow = Math.pow(1 + monthlyRate, n);
                emi = (p * monthlyRate * pow) / (pow - 1);
            } else {
                emi = p / n;
            }
            $('#emiAmountInput').attr('placeholder', 'Auto: ' + emi.toFixed(2));
        }
    }

    $('#emiPrincipal, #emiRate, #emiTenure').on('input', calculateEmiPreview);

    // Filter tabs
    $('.emi-tab-btn').on('click', function() {
        $('.emi-tab-btn').removeClass('active');
        $(this).addClass('active');
        currentStatus = $(this).data('status');
        loadEmis();
    });

    function loadEmis() {
        const container = $('#emisListContainer');
        container.html(`
            <div class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                <span class="ms-2">Loading loan accounts...</span>
            </div>
        `);

        $.getJSON('api/emis.php', { action: 'list', status: currentStatus }, function(res) {
            if (!res.success) {
                container.html(`<div class="alert alert-danger">${res.message}</div>`);
                return;
            }

            $('#summaryCommitment').text(res.formatted_total_commitment);
            $('#summaryOutstanding').text(res.formatted_total_outstanding);
            $('#activeCountBadge').text(res.active_count);

            if (res.emis.length === 0) {
                container.html(`
                    <div class="empty-state-box">
                        <div class="empty-state-icon" style="background: #F3E8FF; color: #7E22CE;">
                            <i class="fa-solid fa-landmark"></i>
                        </div>
                        <h6>No ${currentStatus} loans found</h6>
                        <p>Click "New Loan" to add and track your EMI schedules</p>
                    </div>
                `);
                return;
            }

            let html = '<div class="row g-3">';
            for (const emi of res.emis) {
                const isPaidThisMonth = emi.paid_this_month;
                const payBadge = isPaidThisMonth 
                    ? `<span class="badge badge-soft-success"><i class="fa-solid fa-check me-1"></i> Paid this month</span>` 
                    : `<span class="badge badge-soft-warning"><i class="fa-regular fa-clock me-1"></i> Due on ${emi.due_day}th</span>`;

                html += `
                    <div class="col-md-6 col-xl-4" id="emi-card-${emi.id}">
                        <div class="app-card h-100 mb-0">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="text-truncate" style="max-width: 170px;">
                                    <h5 class="fw-bold mb-0 text-primary text-truncate">${escapeHtml(emi.loan_name)}</h5>
                                    <small class="text-muted fw-semibold text-truncate d-block">${escapeHtml(emi.lender)} · ${emi.interest_rate}% Interest</small>
                                </div>
                                <div>
                                    ${payBadge}
                                </div>
                            </div>

                            <div class="row g-2 my-2 py-2 border-top border-bottom" style="border-color: var(--border-soft) !important;">
                                <div class="col-4">
                                    <small class="text-muted d-block" style="font-size: 0.7rem; font-weight: 700;">MONTHLY EMI</small>
                                    <span class="fw-bold fs-6" style="color: #7E22CE;">${emi.formatted_emi}</span>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block" style="font-size: 0.7rem; font-weight: 700;">OUTSTANDING</small>
                                    <span class="fw-bold fs-6 text-danger">${emi.formatted_balance}</span>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block" style="font-size: 0.7rem; font-weight: 700;">PRINCIPAL</small>
                                    <span class="fw-bold fs-6">${emi.formatted_principal}</span>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="mt-2 mb-3">
                                <div class="d-flex justify-content-between text-muted mb-1" style="font-size: 0.75rem;">
                                    <span>Installments: <strong>${emi.paid_installments} of ${emi.tenure_months}</strong></span>
                                    <span>${emi.remaining_months} mos left (${emi.progress_pct}%)</span>
                                </div>
                                <div class="progress-smart">
                                    <div class="progress-bar" role="progressbar" style="width: ${emi.progress_pct}%; background-color: #A855F7;"></div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between align-items-center gap-2 pt-1 mt-auto">
                                <div>
                                    <button class="btn btn-outline-secondary btn-sm rounded-pill px-3 view-history-btn" data-id="${emi.id}">
                                        <i class="fa-solid fa-list-check me-1"></i> Schedule
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm rounded-pill px-2 delete-emi-btn" data-id="${emi.id}">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                                ${emi.status === 'active' ? `
                                    <button class="btn btn-primary-smart btn-sm px-3 rounded-pill open-pay-modal-btn" data-id="${emi.id}" data-name="${escapeHtml(emi.loan_name)}" data-amount="${emi.emi_amount}" data-balance="${emi.formatted_balance}">
                                        <i class="fa-solid fa-credit-card me-1"></i> Pay
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            }
            html += '</div>';

            container.html(html);
        });
    }

    // Add Loan Submission
    $('#createEmiForm').on('submit', function(e) {
        e.preventDefault();
        const submitBtn = $('#saveEmiBtn');
        submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Creating...');

        const formData = new FormData(this);
        formData.append('action', 'add');

        $.ajax({
            url: 'api/emis.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    $('#addEmiModal').modal('hide');
                    $('#createEmiForm')[0].reset();
                    loadEmis();
                } else {
                    toastr.error(res.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON ? xhr.responseJSON.message : 'Error creating EMI.');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="fa-solid fa-plus me-1"></i> Save Loan Account');
            }
        });
    });

    // Open Pay Installment Modal
    $(document).on('click', '.open-pay-modal-btn', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const amount = $(this).data('amount');
        const balance = $(this).data('balance');

        $('#payEmiId').val(id);
        $('#payLoanName').text(name);
        $('#payLoanBalance').text(balance);
        $('#payAmountInput').val(amount);

        $('#payInstallmentModal').modal('show');
    });

    // Record Payment Submission
    $('#recordPaymentForm').on('submit', function(e) {
        e.preventDefault();
        const submitBtn = $('#submitPaymentBtn');
        submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Processing...');

        const formData = new FormData(this);
        formData.append('action', 'record_payment');

        $.ajax({
            url: 'api/emis.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    $('#payInstallmentModal').modal('hide');
                    $('#recordPaymentForm')[0].reset();
                    loadEmis();
                } else {
                    toastr.error(res.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON ? xhr.responseJSON.message : 'Payment recording failed.');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Confirm Payment');
            }
        });
    });

    // View Schedule & Payment History
    $(document).on('click', '.view-history-btn', function() {
        const id = $(this).data('id');
        $('#emiHistoryBody').html('<div class="text-center py-4"><span class="spinner-border spinner-border-sm"></span> Loading details...</div>');
        $('#emiHistoryModal').modal('show');

        $.getJSON('api/emis.php', { action: 'get', id: id }, function(res) {
            if (!res.success) {
                $('#emiHistoryBody').html(`<div class="alert alert-danger">${res.message}</div>`);
                return;
            }

            const emi = res.emi;
            const payments = res.payments;

            $('#historyModalTitle').text(emi.loan_name + ' - Schedule & History');

            let pTable = '';
            if (payments.length === 0) {
                pTable = '<div class="text-muted text-center py-3">No payments recorded yet.</div>';
            } else {
                pTable = `
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Principal</th>
                                    <th>Interest</th>
                                    <th>Method</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                for (const p of payments) {
                    pTable += `
                        <tr>
                            <td class="fw-bold">${p.installment_number}</td>
                            <td>${p.payment_date}</td>
                            <td class="fw-bold text-primary"><?= $currencySymbol ?>${Number(p.amount_paid).toLocaleString()}</td>
                            <td class="text-success"><?= $currencySymbol ?>${Number(p.principal_component).toLocaleString()}</td>
                            <td class="text-muted"><?= $currencySymbol ?>${Number(p.interest_component).toLocaleString()}</td>
                            <td>${p.payment_method}</td>
                        </tr>
                    `;
                }
                pTable += `</tbody></table></div>`;
            }

            $('#emiHistoryBody').html(`
                <div class="row g-2 mb-3 bg-light p-3 rounded-4">
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block">Principal:</small>
                        <span class="fw-bold">${emi.formatted_principal}</span>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block">Monthly EMI:</small>
                        <span class="fw-bold" style="color: #7E22CE;">${emi.formatted_emi}</span>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block">Remaining Balance:</small>
                        <span class="fw-bold text-danger">${emi.formatted_outstanding}</span>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block">Progress:</small>
                        <span class="fw-bold">${emi.paid_count}/${emi.tenure_months} (${emi.progress_pct}%)</span>
                    </div>
                </div>
                <h6 class="fw-bold mb-2">Payment History (${payments.length} paid)</h6>
                ${pTable}
            `);
        });
    });

    // Delete EMI
    $(document).on('click', '.delete-emi-btn', function() {
        const id = $(this).data('id');
        confirmAction('Delete Loan Account?', 'This will permanently remove this loan record and its payment history!').then(res => {
            if (res.isConfirmed) {
                $.post('api/emis.php', { action: 'delete', id: id }, function(resp) {
                    if (resp.success) {
                        toastr.success(resp.message);
                        $(`#emi-card-${id}`).slideUp(300, function() { $(this).remove(); });
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

    loadEmis();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
