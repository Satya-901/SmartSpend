<?php
/**
 * Printable / PDF Statement View
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();
$userId = current_user_id();
$user = current_user();
$db = get_db();

$month = (int)($_GET['month'] ?? date('m'));
$year = (int)($_GET['year'] ?? date('Y'));
$monthName = date('F Y', strtotime("{$year}-{$month}-01"));

// Totals
$stmtSum = $db->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as total_expense,
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as total_income
    FROM transactions 
    WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?
");
$stmtSum->execute([$userId, $month, $year]);
$totals = $stmtSum->fetch();

// All transactions for this month
$stmtTx = $db->prepare("
    SELECT t.*, COALESCE(c.name, 'Uncategorized') as category_name
    FROM transactions t
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ? AND MONTH(t.date) = ? AND YEAR(t.date) = ?
    ORDER BY t.date DESC, t.id DESC
");
$stmtTx->execute([$userId, $month, $year]);
$transactions = $stmtTx->fetchAll();

// Active EMIs
$stmtEmi = $db->prepare("SELECT * FROM emis WHERE user_id = ? AND status = 'active'");
$stmtEmi->execute([$userId]);
$emis = $stmtEmi->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Financial Statement - <?= htmlspecialchars($monthName) ?> | <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        @font-face {
            font-family: 'Redaction';
            font-style: normal;
            font-weight: 400;
            src: url('../assets/fonts/Redaction-Regular.woff2') format('woff2');
        }
        @font-face {
            font-family: 'Redaction';
            font-style: normal;
            font-weight: 700;
            src: url('../assets/fonts/Redaction-Bold.woff2') format('woff2');
        }
        body { font-family: 'Redaction', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; background: #fff; padding: 30px; }
        .statement-card { border: 1px solid #e0e0e0; border-radius: 12px; padding: 20px; margin-bottom: 25px; }
        .table th { background: #f8f9fa; font-size: 0.85rem; text-transform: uppercase; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

<div class="container" style="max-width: 900px;">
    <!-- Actions Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print pb-3 border-bottom">
        <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
        </a>
        <div class="d-flex gap-2">
            <a href="csv.php?type=transactions" class="btn btn-outline-success btn-sm">
                <i class="fa-solid fa-file-csv me-1"></i> Export CSV
            </a>
            <button onclick="window.print()" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-print me-1"></i> Print / Save as PDF
            </button>
        </div>
    </div>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="fw-bold mb-1 text-primary"><?= APP_NAME ?></h2>
            <h5 class="text-secondary mb-0">Financial Statement - <?= htmlspecialchars($monthName) ?></h5>
            <small class="text-muted">Generated on: <?= date('d M Y, h:i A') ?></small>
        </div>
        <div class="text-end">
            <h6 class="fw-bold mb-0"><?= htmlspecialchars($user['name']) ?></h6>
            <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
        </div>
    </div>

    <!-- Monthly Summary -->
    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="statement-card p-3 bg-light">
                <small class="text-muted fw-bold d-block">TOTAL INCOME</small>
                <h4 class="text-success fw-bold mb-0"><?= format_currency($totals['total_income']) ?></h4>
            </div>
        </div>
        <div class="col-4">
            <div class="statement-card p-3 bg-light">
                <small class="text-muted fw-bold d-block">TOTAL EXPENSES</small>
                <h4 class="text-danger fw-bold mb-0"><?= format_currency($totals['total_expense']) ?></h4>
            </div>
        </div>
        <div class="col-4">
            <div class="statement-card p-3 bg-light">
                <small class="text-muted fw-bold d-block">NET SAVINGS</small>
                <h4 class="text-primary fw-bold mb-0"><?= format_currency($totals['total_income'] - $totals['total_expense']) ?></h4>
            </div>
        </div>
    </div>

    <!-- Active EMIs -->
    <?php if (!empty($emis)): ?>
    <h5 class="fw-bold mb-2">Active Loan / EMI Commitments</h5>
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-sm align-middle">
            <thead>
                <tr>
                    <th>Loan Name</th>
                    <th>Lender</th>
                    <th>Principal</th>
                    <th>Rate</th>
                    <th>Monthly EMI</th>
                    <th>Outstanding</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($emis as $e): ?>
                <tr>
                    <td class="fw-bold"><?= htmlspecialchars($e['loan_name']) ?></td>
                    <td><?= htmlspecialchars($e['lender']) ?></td>
                    <td><?= format_currency($e['principal_amount']) ?></td>
                    <td><?= $e['interest_rate'] ?>%</td>
                    <td class="fw-bold text-danger"><?= format_currency($e['emi_amount']) ?></td>
                    <td><?= format_currency($e['outstanding_balance']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Transactions List -->
    <h5 class="fw-bold mb-2">Itemized Transactions</h5>
    <div class="table-responsive">
        <table class="table table-striped table-hover table-sm align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Description / Notes</th>
                    <th>Method</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr><td colspan="6" class="text-center py-3 text-muted">No transactions recorded for this period.</td></tr>
                <?php else: ?>
                    <?php foreach ($transactions as $tx): ?>
                    <tr>
                        <td><?= date('d M Y', strtotime($tx['date'])) ?></td>
                        <td>
                            <span class="badge <?= $tx['type'] === 'expense' ? 'bg-danger' : 'bg-success' ?>">
                                <?= ucfirst($tx['type']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($tx['category_name']) ?></td>
                        <td><?= htmlspecialchars($tx['notes'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($tx['payment_method']) ?></td>
                        <td class="text-end fw-bold <?= $tx['type'] === 'expense' ? 'text-danger' : 'text-success' ?>">
                            <?= ($tx['type'] === 'expense' ? '-' : '+') . format_currency($tx['amount']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>

