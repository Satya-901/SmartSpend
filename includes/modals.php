<?php
/**
 * Global Modals (Quick Add, EMI Payment, Receipt Viewer)
 */

$userCategoriesExpense = $currentUser ? get_user_categories($currentUser['id'], 'expense') : [];
$userCategoriesIncome = $currentUser ? get_user_categories($currentUser['id'], 'income') : [];

// Fetch user's active EMIs for quick installment payment
$activeEmisList = [];
if ($currentUser) {
    $db = get_db();
    $stmt = $db->prepare("SELECT id, loan_name, lender, emi_amount, outstanding_balance, due_day FROM emis WHERE user_id = ? AND status = 'active' ORDER BY loan_name ASC");
    $stmt->execute([$currentUser['id']]);
    $activeEmisList = $stmt->fetchAll();
}
?>

<!-- Quick Add Modal Sheet (Expense / Income / EMI Payment) -->
<div class="modal fade" id="quickAddModal" tabindex="-1" aria-labelledby="quickAddModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header pb-2">
                <ul class="nav nav-pills w-100 justify-content-center gap-2" id="quickAddTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-pill px-4 py-2 fw-bold" id="expense-tab" data-bs-toggle="pill" data-bs-target="#tab-expense" type="button" role="tab">
                            <i class="fa-solid fa-arrow-down-long me-1 text-danger"></i> Expense
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill px-4 py-2 fw-bold" id="income-tab" data-bs-toggle="pill" data-bs-target="#tab-income" type="button" role="tab">
                            <i class="fa-solid fa-arrow-up-long me-1 text-success"></i> Income
                        </button>
                    </li>
                    <?php if (!empty($activeEmisList)): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill px-3 py-2 fw-bold" id="emi-pay-tab" data-bs-toggle="pill" data-bs-target="#tab-emi-pay" type="button" role="tab">
                            <i class="fa-solid fa-landmark me-1 text-purple"></i> Pay EMI
                        </button>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="tab-content modal-body pt-3" id="quickAddTabContent">
                <!-- 1. Expense Form -->
                <div class="tab-pane fade show active" id="tab-expense" role="tabpanel">
                    <form id="addExpenseForm" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="type" value="expense">
                        
                        <div class="mb-3">
                            <label class="form-label">Amount (<?= htmlspecialchars($currencySymbol) ?>) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold text-danger"><?= htmlspecialchars($currencySymbol) ?></span>
                                <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-lg fw-bold" placeholder="0.00" required autofocus>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-7">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($userCategoriesExpense as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-5">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" class="form-select">
                                    <option value="UPI">UPI / GPay</option>
                                    <option value="Credit Card">Credit Card</option>
                                    <option value="Debit Card">Debit Card</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Net Banking">Net Banking</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-7">
                                <label class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-5">
                                <label class="form-label">Time</label>
                                <input type="time" name="time" class="form-control" value="<?= date('H:i') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Note / Merchant</label>
                            <input type="text" name="notes" class="form-control" placeholder="e.g. Burger King, Grocery store">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Attach Receipt (Optional)</label>
                            <input type="file" name="receipt" class="form-control" accept="image/*,.pdf">
                            <small class="text-muted" style="font-size: 0.72rem;">JPG, PNG, WEBP or PDF (Max 5MB)</small>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary-smart py-2">
                                <i class="fa-solid fa-plus me-1"></i> Save Expense
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 2. Income Form -->
                <div class="tab-pane fade" id="tab-income" role="tabpanel">
                    <form id="addIncomeForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="type" value="income">

                        <div class="mb-3">
                            <label class="form-label">Amount (<?= htmlspecialchars($currencySymbol) ?>) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold text-success"><?= htmlspecialchars($currencySymbol) ?></span>
                                <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-lg fw-bold" placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-7">
                                <label class="form-label">Income Source <span class="text-danger">*</span></label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($userCategoriesIncome as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-5">
                                <label class="form-label">Received Via</label>
                                <select name="payment_method" class="form-select">
                                    <option value="Net Banking">Bank Transfer</option>
                                    <option value="UPI">UPI</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-7">
                                <label class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-5">
                                <label class="form-label">Time</label>
                                <input type="time" name="time" class="form-control" value="<?= date('H:i') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Note / Description</label>
                            <input type="text" name="notes" class="form-control" placeholder="e.g. Monthly Salary, Freelance project">
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary-smart py-2" style="background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);">
                                <i class="fa-solid fa-plus me-1"></i> Save Income
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 3. EMI Pay Form -->
                <?php if (!empty($activeEmisList)): ?>
                <div class="tab-pane fade" id="tab-emi-pay" role="tabpanel">
                    <form id="quickPayEmiForm">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">Select Loan / EMI <span class="text-danger">*</span></label>
                            <select name="emi_id" id="quickEmiSelect" class="form-select" required>
                                <option value="">Choose an active EMI</option>
                                <?php foreach ($activeEmisList as $emi): ?>
                                    <option value="<?= $emi['id'] ?>" data-amount="<?= $emi['emi_amount'] ?>" data-balance="<?= $emi['outstanding_balance'] ?>">
                                        <?= htmlspecialchars($emi['loan_name']) ?> (<?= htmlspecialchars($emi['lender']) ?> - <?= format_currency($emi['emi_amount']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Installment Amount (<?= htmlspecialchars($currencySymbol) ?>) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="1" name="amount_paid" id="quickEmiAmount" class="form-control form-control-lg fw-bold" required>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">Payment Date <span class="text-danger">*</span></label>
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
                            <label class="form-label">Transaction Notes</label>
                            <input type="text" name="notes" class="form-control" placeholder="e.g. Monthly installment paid via auto-debit">
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary-smart py-2" style="background: linear-gradient(135deg, #9333EA 0%, #7E22CE 100%);">
                                <i class="fa-solid fa-check me-1"></i> Record EMI Payment
                            </button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Viewer Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-receipt me-2 text-warning"></i> Receipt Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-3" id="receiptModalBody">
                <!-- Injected via JavaScript -->
            </div>
        </div>
    </div>
</div>

